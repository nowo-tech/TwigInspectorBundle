<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Twig;

use Nowo\TwigInspectorBundle\Twig\Node\{NodeEnd, NodeStart};
use Twig\Environment;
use Twig\Node\BlockNode;
use Twig\Node\BodyNode;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\NodeVisitor\NodeVisitorInterface;

/**
 * Inspired by {@see ProfilerNodeVisitor}
 * Modify generated Twig template to add comments before and after every block and template.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class DebugInfoNodeVisitor implements NodeVisitorInterface
{
    protected const EXTENSION_NAME = HtmlCommentsExtension::class;

    /**
     * Called before child nodes are visited.
     *
     * @param Node        $node The node
     * @param Environment $env  The Twig environment
     *
     * @return Node The modified node
     */
    public function enterNode(Node $node, Environment $env): Node
    {
        return $node;
    }

    /**
     * Called after child nodes are visited.
     * Modifies ModuleNode and BlockNode to inject NodeStart and NodeEnd nodes
     * that will generate HTML comments during template rendering.
     *
     * @param Node        $node The node
     * @param Environment $env  The Twig environment
     *
     * @return Node The modified node
     */
    public function leaveNode(Node $node, Environment $env): Node
    {
        $varName = $this->getVarName();

        // Wrap template display with start/end comments
        if ($node instanceof ModuleNode) {
            $node->setNode(
                'display_start',
                $this->createBodyNode(
                    [
                        new NodeStart(
                            self::EXTENSION_NAME,
                            $node->getTemplateName(),
                            $node->getTemplateLine(),
                            $varName
                        ),
                        $node->getNode('display_start'),
                    ]
                )
            );
            $node->setNode(
                'display_end',
                $this->createBodyNode(
                    [
                        new NodeEnd($varName),
                        $node->getNode('display_end'),
                    ]
                )
            );
        }
        // Wrap block body with start/end comments
        elseif ($node instanceof BlockNode) {
            $node->setNode(
                'body',
                $this->createBodyNode(
                    [
                        new NodeStart(
                            self::EXTENSION_NAME,
                            $node->getAttribute('name'),
                            $node->getTemplateLine(),
                            $varName
                        ),
                        $node->getNode('body'),
                        new NodeEnd($varName),
                    ]
                )
            );
        }

        return $node;
    }

    /**
     * Creates a body node for wrapping child nodes.
     * Uses BodyNode (available since Twig 3.8) for compatibility with Twig 3.15+.
     *
     * @param array<Node> $nodes Child nodes to wrap
     *
     * @return Node The body node instance
     */
    private function createBodyNode(array $nodes): Node
    {
        // BodyNode exists since Twig 3.8 and is the recommended way to wrap nodes
        // This avoids deprecation warnings in Twig 3.15+ where Node cannot be instantiated directly
        return new BodyNode($nodes);
    }

    /**
     * Gets a unique variable name for the inspector extension instance.
     * The variable name is based on a hash of the extension class name to ensure consistency.
     *
     * @return string The variable name (e.g., '__inspector_abc123...')
     */
    private function getVarName(): string
    {
        return sprintf('__inspector_%s', hash('sha256', self::EXTENSION_NAME));
    }

    /**
     * Gets the priority of the visitor.
     *
     * @return int The priority
     */
    public function getPriority(): int
    {
        return 0;
    }
}
