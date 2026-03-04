<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle;

use Nowo\TwigInspectorBundle\DependencyInjection\NowoTwigInspectorExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Symfony bundle that adds Twig Inspector: HTML comments and overlay to map rendered output to templates.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class NowoTwigInspectorBundle extends Bundle
{
    /**
     * Returns the DI extension for this bundle (NowoTwigInspectorExtension).
     *
     * @return ExtensionInterface|null The extension instance
     */
    public function getContainerExtension(): ?ExtensionInterface
    {
        if ($this->extension === null) {
            $this->extension = new NowoTwigInspectorExtension();
        }

        return $this->extension instanceof ExtensionInterface ? $this->extension : null;
    }
}
