<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Tests\Kernel;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel;

use function dirname;

/**
 * Minimal kernel for integration tests.
 * Uses MicroKernelTrait; project dir is tests/Fixtures/app so config lives there.
 */
final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    /**
     * Project dir for the test app (tests/Fixtures/app). Ensures install command and config are isolated.
     */
    public function getProjectDir(): string
    {
        return dirname(__DIR__) . '/Fixtures/app';
    }
}
