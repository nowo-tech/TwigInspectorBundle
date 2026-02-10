<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle;

use Nowo\TwigInspectorBundle\DependencyInjection\NowoTwigInspectorExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Symfony bundle that adds Twig Inspector: HTML comments and overlay to map rendered output to templates.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
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
        if (null === $this->extension) {
            $this->extension = new NowoTwigInspectorExtension();
        }

        return $this->extension;
    }
}
