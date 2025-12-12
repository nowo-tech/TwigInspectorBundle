<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Tests\Twig\Node;

use Nowo\TwigInspectorBundle\Twig\Node\NodeEnd;
use PHPUnit\Framework\TestCase;
use Twig\Compiler;

/**
 * Tests for NodeEnd.
 *
 * @package Nowo\TwigInspectorBundle\Tests\Twig\Node
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2024 Nowo.tech
 */
final class NodeEndTest extends TestCase
{
  public function testConstructor(): void
  {
    $node = new NodeEnd('var_name');

    $this->assertSame('var_name', $node->getAttribute('var_name'));
  }

  public function testCompile(): void
  {
    $node     = new NodeEnd('var123');
    $compiler = $this->createMock(Compiler::class);

    $compiler->expects($this->exactly(2))
      ->method('write')
      ->withConsecutive(
        ["\n"],
        [$this->stringContains('$var123->end($var123_ref')]
      );

    $node->compile($compiler);
  }
}

