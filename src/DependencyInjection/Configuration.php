<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Defines the configuration tree for the Twig Inspector Bundle (nowo_twig_inspector).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Builds the config tree for nowo_twig_inspector (extensions, exclusions, overlay, etc.).
     *
     * @return TreeBuilder The config tree builder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('nowo_twig_inspector');

        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('enabled_extensions')
                    ->info('List of template file extensions to inspect (e.g., [".html.twig", ".twig"])')
                    ->defaultValue(['.html.twig'])
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('excluded_templates')
                    ->info('List of template names or patterns to exclude from inspection')
                    ->defaultValue([])
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('excluded_blocks')
                    ->info('List of block names or patterns to exclude from inspection')
                    ->defaultValue([])
                    ->prototype('scalar')->end()
                ->end()
                ->booleanNode('enable_metrics')
                    ->info('Enable collection of template usage metrics in DataCollector')
                    ->defaultTrue()
                ->end()
                /*->booleanNode('optimize_output_buffering')
                    ->info('Skip output buffering when inspector is disabled (performance optimization)')
                    ->defaultTrue()
                ->end()*/
                ->booleanNode('inject_on_sub_requests')
                    ->info('When true, inject comments also during sub-requests (e.g. when main content is rendered as fragment). Enable if all templates show "sub-request" and none get inspected.')
                    ->defaultValue(false)
                ->end()
                ->scalarNode('cookie_name')
                    ->info('Name of the cookie used to enable/disable the inspector')
                    ->defaultValue('twig_inspector_is_active')
                ->end()
                ->integerNode('max_injection_depth')
                    ->info('Maximum nesting depth for comment injection (0 = unlimited). Reduces overhead on very deep template trees.')
                    ->defaultValue(0)
                    ->min(0)
                ->end()
                ->arrayNode('excluded_templates_regex')
                    ->info('Additional exclusion patterns as regex (e.g. ["/^email\\\\//"]). Applied in addition to excluded_templates.')
                    ->defaultValue([])
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('excluded_templates_prefixes')
                    ->info('Template name prefixes to exclude (namespace-style, e.g. ["@Admin/", "components/"]).')
                    ->defaultValue([])
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('excluded_blocks_regex')
                    ->info('Additional block exclusion patterns as regex.')
                    ->defaultValue([])
                    ->prototype('scalar')->end()
                ->end()
                ->scalarNode('overlay_theme')
                    ->info('Overlay theme: "light", "dark", or "auto" (follow system preference).')
                    ->defaultValue('light')
                    ->validate()
                        ->ifNotInArray(['light', 'dark', 'auto'])
                        ->thenInvalid('overlay_theme must be one of: light, dark, auto.')
                    ->end()
                ->end()
                ->booleanNode('overlay_compact')
                    ->info('Use compact tooltip style for the overlay.')
                    ->defaultValue(false)
                ->end()
                ->booleanNode('reduced_motion')
                    ->info('Respect reduced motion (accessibility). When true or system prefers-reduced-motion, animations are minimized.')
                    ->defaultValue(false)
                ->end()
                ->scalarNode('keyboard_shortcut')
                    ->info('Keyboard shortcut to toggle inspector (e.g. "Ctrl+Shift+T"). Empty to disable.')
                    ->defaultValue('Ctrl+Shift+T')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
