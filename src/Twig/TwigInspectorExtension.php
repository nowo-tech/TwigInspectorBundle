<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Twig;

use Twig\Extension\AbstractExtension;

/**
 * Twig extension that registers the node visitor used to inject inspector comments.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class TwigInspectorExtension extends AbstractExtension
{
    /**
     * Returns the list of node visitors (DebugInfoNodeVisitor) that wrap blocks and template display.
     *
     * @return array<int, DebugInfoNodeVisitor> Node visitors
     */
    public function getNodeVisitors(): array
    {
        return [new DebugInfoNodeVisitor()];
    }
}
