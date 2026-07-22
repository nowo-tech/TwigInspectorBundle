<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Tests\Unit\Twig\Node;

use Nowo\TwigInspectorBundle\Twig\Node\NodeStart;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Compiler;

/**
 * Tests for NodeStart.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class NodeStartTest extends TestCase
{
    public function testConstructor(): void
    {
        $node = new NodeStart('ExtensionName', 'block_name', 42, 'var_name');

        $this->assertSame('ExtensionName', $node->getAttribute('extension_name'));
        $this->assertSame('block_name', $node->getAttribute('name'));
        $this->assertSame(42, $node->getAttribute('line'));
        $this->assertSame('var_name', $node->getAttribute('var_name'));
    }

    public function testCompile(): void
    {
        $node     = new NodeStart('ExtensionName', 'block_name', 10, 'var123');
        $compiler = $this->createMock(Compiler::class);

        $writeCallCount = 0;
        $compiler->expects($this->exactly(2))
          ->method('write')
          ->willReturnCallback(function (string $arg) use (&$writeCallCount, $compiler): MockObject {
              ++$writeCallCount;
              if ($writeCallCount === 1) {
                  $this->assertStringContainsString('$var123 = $this->env->getExtension(', $arg);
              } else {
                  $this->assertStringContainsString('$var123->start($var123_ref = new', $arg);
              }

              return $compiler;
          });

        $reprCallCount = 0;
        $compiler->expects($this->exactly(3))
          ->method('repr')
          ->willReturnCallback(function ($arg) use (&$reprCallCount, $compiler): MockObject {
              ++$reprCallCount;
              if ($reprCallCount === 1) {
                  $this->assertSame('ExtensionName', $arg);
              } elseif ($reprCallCount === 2) {
                  $this->assertSame('block_name', $arg);
              } else {
                  $this->assertSame(10, $arg);
              }

              return $compiler;
          });

        $rawCallCount = 0;
        $compiler->expects($this->exactly(3))
          ->method('raw')
          ->willReturnCallback(function ($arg) use (&$rawCallCount, $compiler): MockObject {
              ++$rawCallCount;
              if ($rawCallCount === 1) {
                  $this->assertSame(");\n", $arg);
              } elseif ($rawCallCount === 2) {
                  $this->assertSame(', $this->getTemplateName(), ', $arg);
              } else {
                  $this->assertSame("));\n\n", $arg);
              }

              return $compiler;
          });

        $node->compile($compiler);
    }
}
