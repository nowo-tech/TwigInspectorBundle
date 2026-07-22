<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\DependencyInjection;

use InvalidArgumentException;
use Nowo\TwigInspectorBundle\DevEnvironments;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

use function is_string;

/**
 * Dependency injection extension for the Twig Inspector Bundle.
 * Loads services, processes config, and wires the HtmlCommentsExtension and DataCollector.
 * Refuse to load outside dev/test when kernel.environment is set (fail-closed for prod).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class NowoTwigInspectorExtension extends Extension
{
    /**
     * Loads bundle services and applies the processed configuration to the container.
     *
     * @param array<string, mixed> $configs Raw config arrays (from config files)
     * @param ContainerBuilder $container Container builder to register parameters and definitions
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        if ($container->hasParameter('kernel.environment')) {
            $environment = $container->getParameter('kernel.environment');
            if (!is_string($environment)) {
                throw new InvalidArgumentException('Parameter "kernel.environment" must be a string.');
            }
            DevEnvironments::assertAllowed($environment);
        }

        $processor     = new Processor();
        $configuration = new Configuration();
        $config        = $processor->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        // Set configuration parameters
        $container->setParameter('nowo_twig_inspector.enabled_extensions', $config['enabled_extensions']);
        $container->setParameter('nowo_twig_inspector.excluded_templates', $config['excluded_templates']);
        $container->setParameter('nowo_twig_inspector.excluded_blocks', $config['excluded_blocks']);
        $container->setParameter('nowo_twig_inspector.excluded_templates_regex', $config['excluded_templates_regex']);
        $container->setParameter('nowo_twig_inspector.excluded_templates_prefixes', $config['excluded_templates_prefixes']);
        $container->setParameter('nowo_twig_inspector.excluded_blocks_regex', $config['excluded_blocks_regex']);
        $container->setParameter('nowo_twig_inspector.enable_metrics', $config['enable_metrics']);
        // $container->setParameter('nowo_twig_inspector.optimize_output_buffering', $config['optimize_output_buffering']);
        $container->setParameter('nowo_twig_inspector.inject_on_sub_requests', $config['inject_on_sub_requests']);
        $container->setParameter('nowo_twig_inspector.cookie_name', $config['cookie_name']);
        $container->setParameter('nowo_twig_inspector.max_injection_depth', $config['max_injection_depth']);
        $container->setParameter('nowo_twig_inspector.overlay_theme', $config['overlay_theme']);
        $container->setParameter('nowo_twig_inspector.overlay_compact', $config['overlay_compact']);
        $container->setParameter('nowo_twig_inspector.reduced_motion', $config['reduced_motion']);
        $container->setParameter('nowo_twig_inspector.keyboard_shortcut', $config['keyboard_shortcut']);

        // Configuration is passed via parameters; services.yaml uses %nowo_twig_inspector.xxx% references.
        // The following setArgument() calls were redundant and have been removed to avoid duplication.
        //
        // Discarded (HtmlCommentsExtension): services.yaml already binds args 3–11 to parameters.
        // $htmlCommentsExtensionDefinition = $container->getDefinition('Nowo\TwigInspectorBundle\Twig\HtmlCommentsExtension');
        // $htmlCommentsExtensionDefinition->setArgument(3, $config['enabled_extensions']);
        // $htmlCommentsExtensionDefinition->setArgument(4, $config['excluded_templates']);
        // $htmlCommentsExtensionDefinition->setArgument(5, $config['excluded_blocks']);
        // $htmlCommentsExtensionDefinition->setArgument(6, $config['cookie_name']);
        // $htmlCommentsExtensionDefinition->setArgument(7, $config['max_injection_depth']);
        // $htmlCommentsExtensionDefinition->setArgument(8, $config['excluded_templates_regex']);
        // $htmlCommentsExtensionDefinition->setArgument(9, $config['excluded_templates_prefixes']);
        // $htmlCommentsExtensionDefinition->setArgument(10, $config['excluded_blocks_regex']);
        // $htmlCommentsExtensionDefinition->setArgument(11, $config['inject_on_sub_requests']);
        //
        // Discarded (DataCollector): services.yaml already binds args 0–8 to references and parameters.
        // $collectorDefinition = $container->getDefinition('Nowo\TwigInspectorBundle\DataCollector\TwigInspectorCollector');
        // $collectorDefinition->setArgument(0, new Reference('Nowo\TwigInspectorBundle\EventSubscriber\ControllerRenderSubscriber'));
        // $collectorDefinition->setArgument(1, new Reference('request_stack'));
        // $collectorDefinition->setArgument(2, new Reference('twig'));
        // $collectorDefinition->setArgument(3, $config['cookie_name']);
        // $collectorDefinition->setArgument(4, $config['enable_metrics']);
        // $collectorDefinition->setArgument(5, $config['overlay_theme']);
        // $collectorDefinition->setArgument(6, $config['overlay_compact']);
        // $collectorDefinition->setArgument(7, $config['reduced_motion']);
        // $collectorDefinition->setArgument(8, $config['keyboard_shortcut']);
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
