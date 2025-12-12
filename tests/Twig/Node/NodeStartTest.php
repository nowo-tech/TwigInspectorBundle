<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Tests\Twig\Node;

use Nowo\TwigInspectorBundle\Twig\Node\NodeStart;
use PHPUnit\Framework\TestCase;
use Twig\Compiler;

/**
 * Tests for NodeStart.
 *
 * @package Nowo\TwigInspectorBundle\Tests\Twig\Node
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2024 Nowo.tech
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

    $compiler->expects($this->exactly(2))
      ->method('write')
      ->withConsecutive(
        [$this->stringContains('$var123 = $this->env->getExtension(')],
        [$this->stringContains('$var123->start($var123_ref = new')]
      );

    $compiler->expects($this->once())
      ->method('repr')
      ->with('ExtensionName')
      ->willReturnSelf();

    $compiler->expects($this->exactly(2))
      ->method('raw')
      ->willReturnSelf();

    $node->compile($compiler);
  }
}

