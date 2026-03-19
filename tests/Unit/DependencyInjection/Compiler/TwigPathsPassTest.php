<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Tests\Unit\DependencyInjection\Compiler;

use Nowo\TwigInspectorBundle\DependencyInjection\Compiler\TwigPathsPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class TwigPathsPassTest extends TestCase
{
    public function testProcessAddsTwigPathToNativeFilesystemLoader(): void
    {
        $container = new ContainerBuilder();
        $loaderDef = new Definition();
        $container->setDefinition('twig.loader.native_filesystem', $loaderDef);

        $pass = new TwigPathsPass();
        $pass->process($container);

        $calls = $loaderDef->getMethodCalls();
        self::assertNotEmpty($calls);

        $found = false;
        foreach ($calls as [$method, $args]) {
            if ($method !== 'addPath') {
                continue;
            }
            if (!isset($args[0], $args[1])) {
                continue;
            }
            if ($args[1] !== 'NowoTwigInspectorBundle') {
                continue;
            }
            self::assertStringEndsWith('/Resources/views', (string) $args[0]);
            $found = true;
            break;
        }

        self::assertTrue($found, 'Expected addPath call for NowoTwigInspectorBundle namespace.');
    }

    public function testProcessUsesTwigLoaderNativeWhenAliasExists(): void
    {
        $container = new ContainerBuilder();
        $loaderDef = new Definition();
        $container->setDefinition('twig.loader.native_filesystem', $loaderDef);
        $container->setAlias('twig.loader.native', 'twig.loader.native_filesystem');

        $pass = new TwigPathsPass();
        $pass->process($container);

        $calls = $loaderDef->getMethodCalls();
        self::assertNotEmpty($calls);
        $addPathCalls = array_filter($calls, static fn (array $c): bool => $c[0] === 'addPath' && ($c[1][1] ?? '') === 'NowoTwigInspectorBundle');
        self::assertCount(1, $addPathCalls);
    }

    public function testProcessDoesNothingWhenTwigLoaderNotDefined(): void
    {
        $container = new ContainerBuilder();

        $pass = new TwigPathsPass();
        $pass->process($container);

        self::assertTrue(true);
    }
}
