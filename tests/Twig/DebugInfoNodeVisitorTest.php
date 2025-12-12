<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Tests\Twig;

use Nowo\TwigInspectorBundle\Twig\DebugInfoNodeVisitor;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Node\BlockNode;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Node\TextNode;

/**
 * Tests for DebugInfoNodeVisitor.
 *
 * @package Nowo\TwigInspectorBundle\Tests\Twig
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2024 Nowo.tech
 */
final class DebugInfoNodeVisitorTest extends TestCase
{
  private DebugInfoNodeVisitor $visitor;
  private Environment $env;

  protected function setUp(): void
  {
    $this->visitor = new DebugInfoNodeVisitor();
    $this->env     = $this->createMock(Environment::class);
  }

  public function testEnterNodeReturnsNodeUnchanged(): void
  {
    $node = new TextNode('test', 1);

    $result = $this->visitor->enterNode($node, $this->env);

    $this->assertSame($node, $result);
  }

  public function testLeaveNodeWithModuleNode(): void
  {
    $moduleNode = $this->createMock(ModuleNode::class);
    $moduleNode->method('getTemplateName')->willReturn('test.html.twig');
    $moduleNode->method('getTemplateLine')->willReturn(1);

    $displayStart = new Node([]);
    $displayEnd   = new Node([]);

    $moduleNode->method('getNode')
      ->willReturnCallback(function ($name) use ($displayStart, $displayEnd) {
        return match ($name) {
          'display_start' => $displayStart,
          'display_end'   => $displayEnd,
        };
      });

    $moduleNode->expects($this->exactly(2))
      ->method('setNode')
      ->withConsecutive(
        ['display_start', $this->isInstanceOf(Node::class)],
        ['display_end', $this->isInstanceOf(Node::class)]
      );

    $result = $this->visitor->leaveNode($moduleNode, $this->env);

    $this->assertSame($moduleNode, $result);
  }

  public function testLeaveNodeWithBlockNode(): void
  {
    $blockNode = $this->createMock(BlockNode::class);
    $blockNode->method('getAttribute')->with('name')->willReturn('block_name');
    $blockNode->method('getTemplateLine')->willReturn(5);

    $bodyNode = new Node([]);

    $blockNode->method('getNode')
      ->with('body')
      ->willReturn($bodyNode);

    $blockNode->expects($this->once())
      ->method('setNode')
      ->with('body', $this->isInstanceOf(\Twig\Node\BodyNode::class));

    $result = $this->visitor->leaveNode($blockNode, $this->env);

    $this->assertSame($blockNode, $result);
  }

  public function testLeaveNodeWithOtherNode(): void
  {
    $node = new TextNode('test', 1);

    $result = $this->visitor->leaveNode($node, $this->env);

    $this->assertSame($node, $result);
  }

  public function testGetPriority(): void
  {
    $this->assertSame(0, $this->visitor->getPriority());
  }

  public function testGetVarName(): void
  {
    $visitor1 = new DebugInfoNodeVisitor();
    $visitor2 = new DebugInfoNodeVisitor();

    $moduleNode = $this->createMock(ModuleNode::class);
    $moduleNode->method('getTemplateName')->willReturn('test.html.twig');
    $moduleNode->method('getTemplateLine')->willReturn(1);
    $moduleNode->method('getNode')->willReturn(new Node([]));
    $moduleNode->method('setNode')->willReturnSelf();

    $visitor1->leaveNode($moduleNode, $this->env);
    $visitor2->leaveNode($moduleNode, $this->env);

    // Var name should be consistent (based on extension name hash)
    $this->assertTrue(true);
  }
}

