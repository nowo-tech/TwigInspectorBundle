<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Tests\Integration\Worker;

use Nowo\TwigInspectorBundle\Tests\Kernel\WorkerKeptAliveKernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

use function gc_collect_cycles;
use function memory_get_usage;
use function sprintf;
use function str_contains;
use function substr_count;

/**
 * REQ-RUNTIME-002: ONE kernel (env test, kernel debug off like a production worker, the inspector's own debug flag forced on),
 * `services_resetter` / `kernel.reset` never called (FrankenPHP worker, kernel reset false).
 * N iterations alternate two request contexts through `http_kernel` handle() + terminate(): "alpha" has the
 * inspector cookie and its own marker, "beta" has no cookie and another marker. Alpha's output must always carry the
 * same, non-growing set of inspector comments; beta's output must carry none and nothing of alpha; memory stays flat.
 */
final class WorkerKeptAliveKernelTest extends TestCase
{
    private const ITERATIONS = 1200;
    private const WARM_UP    = 100;
    private const MARGIN     = 4 * 1024 * 1024;

    public function testAlternatingContextsInOneKernelLeakNothingAndKeepMemoryFlat(): void
    {
        $kernel = new WorkerKeptAliveKernel('test', false);
        $kernel->boot();

        try {
            $httpKernel = $kernel->getContainer()->get('http_kernel');
            self::assertInstanceOf(HttpKernelInterface::class, $httpKernel);
            self::assertInstanceOf(TerminableInterface::class, $httpKernel);

            $baseline      = 0;
            $alphaComments = null;
            for ($i = 0; $i < self::ITERATIONS; ++$i) {
                if ($i === self::WARM_UP) {
                    gc_collect_cycles();
                    $baseline = memory_get_usage();
                }

                $alpha   = $i % 2 === 0;
                $marker  = $alpha ? 'alpha-marker-text' : 'beta-marker-text';
                $request = Request::create('/page?m=' . $marker, 'GET', [], $alpha ? ['twig_inspector_is_active' => '1'] : []);

                $response = $httpKernel->handle($request);
                self::assertSame(200, $response->getStatusCode(), sprintf('iteration %d', $i));
                $httpKernel->terminate($request, $response);
                $html = (string) $response->getContent();

                self::assertStringContainsString($marker, $html, sprintf('iteration %d', $i));
                if ($alpha) {
                    $comments = substr_count($html, '<!--');
                    self::assertGreaterThan(0, $comments, sprintf('iteration %d: inspector comments missing', $i));
                    $alphaComments ??= $comments;
                    self::assertSame($alphaComments, $comments, sprintf('iteration %d: comment count drifted (state carried over)', $i));
                    self::assertStringContainsString('worker_page.html.twig', $html, sprintf('iteration %d', $i));
                } else {
                    self::assertFalse(str_contains($html, '<!--'), sprintf('iteration %d: inspector comments for a request without the cookie', $i));
                    self::assertFalse(str_contains($html, 'alpha-marker-text'), sprintf('iteration %d: other context in output', $i));
                }
            }

            gc_collect_cycles();
            $growth = memory_get_usage() - $baseline;
            self::assertLessThan(self::MARGIN, $growth, sprintf('memory grew by %d bytes over %d iterations', $growth, self::ITERATIONS - self::WARM_UP));
        } finally {
            $kernel->shutdown();
            (new Filesystem())->remove($kernel->getBaseTempDir());
        }
    }
}
