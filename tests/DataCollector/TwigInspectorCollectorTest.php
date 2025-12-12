<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Tests\DataCollector;

use Nowo\TwigInspectorBundle\DataCollector\TwigInspectorCollector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests for TwigInspectorCollector.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2024 Nowo.tech
 */
final class TwigInspectorCollectorTest extends TestCase
{
    private TwigInspectorCollector $collector;

    protected function setUp(): void
    {
        $this->collector = new TwigInspectorCollector();
    }

    public function testCollect(): void
    {
        $request = new Request();
        $response = new Response();

        // Should not throw any exception
        $this->collector->collect($request, $response);

        $this->assertTrue(true);
    }

    public function testCollectWithException(): void
    {
        $request = new Request();
        $response = new Response();
        $exception = new \Exception('Test exception');

        // Should not throw any exception
        $this->collector->collect($request, $response, $exception);

        $this->assertTrue(true);
    }

    public function testReset(): void
    {
        // Should not throw any exception
        $this->collector->reset();

        $this->assertTrue(true);
    }

    public function testGetName(): void
    {
        $this->assertSame('twig_inspector', $this->collector->getName());
    }
}
