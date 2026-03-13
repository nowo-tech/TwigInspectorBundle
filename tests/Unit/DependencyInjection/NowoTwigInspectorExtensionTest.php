<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Tests\Unit\DependencyInjection;

use Nowo\TwigInspectorBundle\DependencyInjection\NowoTwigInspectorExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests for NowoTwigInspectorExtension.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class NowoTwigInspectorExtensionTest extends TestCase
{
    private NowoTwigInspectorExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new NowoTwigInspectorExtension();
    }

    public function testGetAlias(): void
    {
        $this->assertSame('nowo_twig_inspector', $this->extension->getAlias());
    }

    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        // Should not throw any exception
        $this->extension->load([], $container);

        $this->assertTrue(true);
    }

    public function testLoadWithConfig(): void
    {
        $container = new ContainerBuilder();
        $configs   = [
            [
                'enabled_extensions' => ['.html.twig', '.twig'],
                'excluded_templates' => ['admin/*'],
            ],
        ];

        // Should not throw any exception with valid config
        $this->extension->load($configs, $container);

        $this->assertTrue(true);
    }

    public function testLoadSetsInjectOnSubRequestsParameter(): void
    {
        $container = new ContainerBuilder();
        $this->extension->load([], $container);

        $this->assertFalse($container->getParameter('nowo_twig_inspector.inject_on_sub_requests'));
    }

    public function testLoadWithInjectOnSubRequestsTrue(): void
    {
        $container = new ContainerBuilder();
        $this->extension->load([['inject_on_sub_requests' => true]], $container);

        $this->assertTrue($container->getParameter('nowo_twig_inspector.inject_on_sub_requests'));
    }
}
