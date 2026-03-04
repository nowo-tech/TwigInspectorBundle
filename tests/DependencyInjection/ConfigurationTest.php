<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Tests\DependencyInjection;

use Nowo\TwigInspectorBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

/**
 * Tests for Configuration.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class ConfigurationTest extends TestCase
{
    private Configuration $configuration;
    private Processor $processor;

    protected function setUp(): void
    {
        $this->configuration = new Configuration();
        $this->processor     = new Processor();
    }

    public function testGetConfigTreeBuilder(): void
    {
        $treeBuilder = $this->configuration->getConfigTreeBuilder();
        $this->assertNotNull($treeBuilder);
    }

    public function testDefaultConfiguration(): void
    {
        $config = $this->processor->processConfiguration($this->configuration, []);

        $this->assertSame(['.html.twig'], $config['enabled_extensions']);
        $this->assertSame([], $config['excluded_templates']);
        $this->assertSame([], $config['excluded_blocks']);
        $this->assertTrue($config['enable_metrics']);
        $this->assertFalse($config['inject_on_sub_requests']);
        $this->assertSame('twig_inspector_is_active', $config['cookie_name']);
    }

    public function testCustomConfiguration(): void
    {
        $configs = [
            [
                'enabled_extensions'     => ['.twig', '.xml.twig'],
                'excluded_templates'     => ['admin/*', 'email/*'],
                'excluded_blocks'        => ['javascript', 'head_*'],
                'enable_metrics'         => false,
                'inject_on_sub_requests' => true,
                'cookie_name'            => 'custom_cookie',
            ],
        ];

        $config = $this->processor->processConfiguration($this->configuration, $configs);

        $this->assertSame(['.twig', '.xml.twig'], $config['enabled_extensions']);
        $this->assertSame(['admin/*', 'email/*'], $config['excluded_templates']);
        $this->assertSame(['javascript', 'head_*'], $config['excluded_blocks']);
        $this->assertFalse($config['enable_metrics']);
        $this->assertTrue($config['inject_on_sub_requests']);
        $this->assertSame('custom_cookie', $config['cookie_name']);
    }

    public function testPartialConfiguration(): void
    {
        $configs = [
            [
                'excluded_templates' => ['admin/*'],
            ],
        ];

        $config = $this->processor->processConfiguration($this->configuration, $configs);

        // Should merge with defaults
        $this->assertSame(['.html.twig'], $config['enabled_extensions']);
        $this->assertSame(['admin/*'], $config['excluded_templates']);
        $this->assertSame([], $config['excluded_blocks']);
        $this->assertTrue($config['enable_metrics']);
        $this->assertFalse($config['inject_on_sub_requests']);
        $this->assertSame('twig_inspector_is_active', $config['cookie_name']);
    }

    public function testMultipleConfigFiles(): void
    {
        $configs = [
            [
                'excluded_templates' => ['admin/*'],
            ],
            [
                'excluded_blocks' => ['javascript'],
            ],
        ];

        $config = $this->processor->processConfiguration($this->configuration, $configs);

        // Should merge both configs
        $this->assertSame(['admin/*'], $config['excluded_templates']);
        $this->assertSame(['javascript'], $config['excluded_blocks']);
    }

    public function testDefaultConfigurationIncludesNewOptions(): void
    {
        $config = $this->processor->processConfiguration($this->configuration, []);

        $this->assertSame(0, $config['max_injection_depth']);
        $this->assertFalse($config['inject_on_sub_requests']);
        $this->assertSame([], $config['excluded_templates_regex']);
        $this->assertSame([], $config['excluded_templates_prefixes']);
        $this->assertSame([], $config['excluded_blocks_regex']);
        $this->assertSame('light', $config['overlay_theme']);
        $this->assertFalse($config['overlay_compact']);
        $this->assertFalse($config['reduced_motion']);
        $this->assertSame('Ctrl+Shift+T', $config['keyboard_shortcut']);
    }

    public function testCustomOverlayAndKeyboardConfig(): void
    {
        $configs = [
            [
                'overlay_theme'               => 'dark',
                'overlay_compact'             => true,
                'reduced_motion'              => true,
                'keyboard_shortcut'           => 'Ctrl+Alt+I',
                'max_injection_depth'         => 3,
                'excluded_templates_regex'    => ['/^email\\//'],
                'excluded_templates_prefixes' => ['@Admin/', 'components/'],
                'excluded_blocks_regex'       => ['/^head_/'],
            ],
        ];

        $config = $this->processor->processConfiguration($this->configuration, $configs);

        $this->assertSame('dark', $config['overlay_theme']);
        $this->assertTrue($config['overlay_compact']);
        $this->assertTrue($config['reduced_motion']);
        $this->assertSame('Ctrl+Alt+I', $config['keyboard_shortcut']);
        $this->assertSame(3, $config['max_injection_depth']);
        $this->assertSame(['/^email\\//'], $config['excluded_templates_regex']);
        $this->assertSame(['@Admin/', 'components/'], $config['excluded_templates_prefixes']);
        $this->assertSame(['/^head_/'], $config['excluded_blocks_regex']);
    }

    public function testOverlayThemeValidation(): void
    {
        $configs = [['overlay_theme' => 'auto']];
        $config  = $this->processor->processConfiguration($this->configuration, $configs);
        $this->assertSame('auto', $config['overlay_theme']);
    }

    public function testOverlayThemeInvalidThrows(): void
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);
        $this->expectExceptionMessage('overlay_theme must be one of');

        $this->processor->processConfiguration($this->configuration, [['overlay_theme' => 'invalid']]);
    }
}
