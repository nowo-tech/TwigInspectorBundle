<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Extension class that loads and manages the TwigInspector bundle configuration.
 * Handles service definitions and configuration processing.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2024 Nowo.tech
 */
class NowoTwigInspectorExtension extends Extension
{
    /**
     * Loads the services configuration and processes the bundle configuration.
     * Loads the services.yaml file from the bundle's Resources/config directory.
     *
     * @param array<string, mixed> $configs   Array of configuration values
     * @param ContainerBuilder     $container The container builder object
     *
     * @return void
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
    }

    /**
     * Returns the alias name of the extension.
     * This alias is used in configuration files to reference this extension.
     *
     * @return string The alias name of the extension (nowo_twig_inspector)
     */
    public function getAlias(): string
    {
        return 'nowo_twig_inspector';
    }
}
