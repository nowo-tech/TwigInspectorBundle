<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Tests\Unit\DependencyInjection;

use InvalidArgumentException;
use LogicException;
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

    public function testLoadSucceedsInDevEnvironment(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'dev');

        $this->extension->load([], $container);

        $this->assertTrue($container->hasParameter('nowo_twig_inspector.cookie_name'));
    }

    public function testLoadSucceedsInTestEnvironment(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $this->extension->load([], $container);

        $this->assertTrue($container->hasParameter('nowo_twig_inspector.cookie_name'));
    }

    public function testLoadThrowsInProdEnvironment(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('must not be enabled in the "prod" environment');

        $this->extension->load([], $container);
    }

    public function testLoadThrowsInStagingEnvironment(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'staging');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('must not be enabled in the "staging" environment');

        $this->extension->load([], $container);
    }

    public function testLoadThrowsWhenKernelEnvironmentIsNotAString(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 123);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter "kernel.environment" must be a string.');

        $this->extension->load([], $container);
    }
}
