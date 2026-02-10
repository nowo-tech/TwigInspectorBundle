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
 * Node visitor that wraps each block and the template display with NodeStart/NodeEnd
 * so that HtmlCommentsExtension injects HTML comments (used by the inspector overlay).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class DebugInfoNodeVisitor implements NodeVisitorInterface
{
    /** @var string Extension class name (HtmlCommentsExtension) used in compiled templates */
    protected const EXTENSION_NAME = HtmlCommentsExtension::class;

    /**
     * Called before child nodes are visited. This visitor does not modify the node at this stage.
     *
     * @param Node        $node The current node
     * @param Environment $env  The Twig environment
     *
     * @return Node The node unchanged
     */
    public function enterNode(Node $node, Environment $env): Node
    {
        return $node;
    }

    /**
     * Called after child nodes are visited. Injects NodeStart/NodeEnd around display and block body.
     *
     * @param Node        $node The current node (ModuleNode or BlockNode)
     * @param Environment $env  The Twig environment
     *
     * @return Node The node with display_start/display_end or body wrapped
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
     * Wraps the given nodes in a BodyNode (Twig 3.8+).
     *
     * @param array<int, Node> $nodes Child nodes to wrap
     *
     * @return Node A BodyNode containing the children
     */
    private function createBodyNode(array $nodes): Node
    {
        // BodyNode exists since Twig 3.8 and is the recommended way to wrap nodes
        // This avoids deprecation warnings in Twig 3.15+ where Node cannot be instantiated directly
        return new BodyNode($nodes);
    }

    /**
     * Returns a stable variable name for the extension instance in compiled code.
     *
     * @return string Variable name (e.g. __inspector_<hash>)
     */
    private function getVarName(): string
    {
        return sprintf('__inspector_%s', hash('sha256', self::EXTENSION_NAME));
    }

    /**
     * Visitor priority (0 = default).
     *
     * @return int Priority value
     */
    public function getPriority(): int
    {
        return 0;
    }
}
