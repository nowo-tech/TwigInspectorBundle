<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\DataCollector;

use Nowo\TwigInspectorBundle\EventSubscriber\ControllerRenderSubscriber;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\DataCollector\LateDataCollectorInterface;
use Throwable;
use Twig\Environment;
use Twig\Extension\ProfilerExtension;
use Twig\Profiler\Profile;

use function count;
use function is_array;

use const PATHINFO_FILENAME;
use const PREG_SET_ORDER;
use const SORT_NUMERIC;

/**
 * Web Profiler data collector for the Twig Inspector.
 * Collects template and block usage from HTML comments and optional Twig profiler timings.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class TwigInspectorCollector implements DataCollectorInterface, LateDataCollectorInterface
{
    /** @var array{templates: array, blocks: array, controllers: array, template_times: array, total_templates: int, total_blocks: int, total_controllers: int, enabled: bool, config: array} Collected data for the profiler panel */
    private array $data = [
        'templates'         => [],
        'blocks'            => [],
        'controllers'       => [],
        'template_times'    => [],
        'total_templates'   => 0,
        'total_blocks'      => 0,
        'total_controllers' => 0,
        'enabled'           => false,
        'config'            => [],
    ];

    /**
     * Constructor.
     *
     * @param ControllerRenderSubscriber $controllerRenderSubscriber Records controller invocations (main + sub-requests)
     * @param RequestStack $requestStack The request stack
     * @param Environment|null $twig The Twig environment (for template times; null after unserialize)
     * @param string $cookieName Cookie name used to enable the inspector
     * @param bool $enableMetrics Whether to collect template render times from Twig profiler
     * @param string $overlayTheme Overlay theme: "light", "dark", or "auto"
     * @param bool $overlayCompact Use compact tooltip style
     * @param bool $reducedMotion Respect reduced-motion preference
     * @param string $keyboardShortcut Keyboard shortcut to toggle inspector (e.g. "Ctrl+Shift+T")
     */
    public function __construct(
        private readonly ControllerRenderSubscriber $controllerRenderSubscriber,
        private readonly RequestStack $requestStack,
        private ?Environment $twig = null,
        private readonly string $cookieName = 'twig_inspector_is_active',
        private readonly bool $enableMetrics = true,
        private readonly string $overlayTheme = 'light',
        private readonly bool $overlayCompact = false,
        private readonly bool $reducedMotion = false,
        private readonly string $keyboardShortcut = 'Ctrl+Shift+T'
    ) {
    }

    /**
     * Serialize only collected data; exclude Twig Environment (may contain closures) and service refs.
     * The profiler storage serializes collector instances; after unserialize only getData() etc. are used.
     *
     * @return array{data: array}
     */
    public function __serialize(): array
    {
        return ['data' => $this->data];
    }

    /**
     * Restore collected data after unserialization. Twig is set to null (not serialized).
     *
     * @param array{data?: array} $data
     */
    public function __unserialize(array $data): void
    {
        $this->data = $data['data'] ?? [
            'templates'         => [],
            'blocks'            => [],
            'controllers'       => [],
            'template_times'    => [],
            'total_templates'   => 0,
            'total_blocks'      => 0,
            'total_controllers' => 0,
            'enabled'           => false,
            'config'            => [],
        ];
        $this->twig = null;
    }

    /**
     * Collects data for the given request and response.
     * Analyzes the response content to extract template usage statistics.
     *
     * @param Request $request The request object
     * @param Response $response The response object
     * @param Throwable|null $exception The exception if any
     */
    public function collect(Request $request, Response $response, ?Throwable $exception = null): void
    {
        $this->data['enabled'] = $request->cookies->getBoolean($this->cookieName, false);
        $this->data['config']  = [
            'cookie_name'       => $this->cookieName,
            'overlay_theme'     => $this->overlayTheme,
            'overlay_compact'   => $this->overlayCompact,
            'reduced_motion'    => $this->reducedMotion,
            'keyboard_shortcut' => $this->keyboardShortcut,
        ];

        $this->data['controllers']       = $this->controllerRenderSubscriber->getControllersForRequest($request);
        $this->data['total_controllers'] = count($this->data['controllers']);

        if (!$this->data['enabled']) {
            return;
        }

        $content = $response->getContent();
        if ($content === false) {
            return;
        }

        // Extract template information from HTML comments (prefix: box-drawing \S+ or any non-space)
        $pattern = '/<!--\s+\S+\s+([^\s]+)\s+\[([^\]]+)\]\s+#(\w+)-->/u';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        $templates = [];
        $blocks    = [];

        foreach ($matches as $match) {
            $name = $match[1];
            $link = $match[2];
            $id   = $match[3];

            // Extract template name from link
            if (preg_match('/\/_template\/([^?]+)/', $link, $linkMatch)) {
                $templateName          = urldecode($linkMatch[1]);
                $templateBaseName      = pathinfo($templateName, PATHINFO_FILENAME);
                $templateNameFirstPart = strtok($templateName, '.');

                // Determine if it's a template or a block
                // Template: name equals full template name, pathinfo filename (e.g. base.html), or first segment (e.g. base for base.html.twig)
                if ($name === $templateName || $name === $templateBaseName || $name === $templateNameFirstPart) {
                    // It's a template
                    if (!isset($templates[$templateName])) {
                        $templates[$templateName] = [
                            'name'  => $templateName,
                            'count' => 0,
                            'ids'   => [],
                        ];
                    }
                    ++$templates[$templateName]['count'];
                    $templates[$templateName]['ids'][] = $id;
                } else {
                    // It's a block
                    $blockKey = $templateName . '::' . $name;
                    if (!isset($blocks[$blockKey])) {
                        $blocks[$blockKey] = [
                            'template' => $templateName,
                            'name'     => $name,
                            'count'    => 0,
                            'ids'      => [],
                        ];
                    }
                    ++$blocks[$blockKey]['count'];
                    $blocks[$blockKey]['ids'][] = $id;
                }
            }
        }

        $this->data['templates']       = array_values($templates);
        $this->data['blocks']          = array_values($blocks);
        $this->data['total_templates'] = count($templates);
        $this->data['total_blocks']    = count($blocks);
    }

    /**
     * Late collect: runs after response is sent. Used to gather Twig profiler template times.
     */
    public function lateCollect(): void
    {
        if (!$this->enableMetrics || !($this->data['enabled'] ?? false)) {
            return;
        }

        $this->data['template_times'] = $this->collectTemplateTimes();
    }

    /**
     * Collects template render durations from Twig profiler profile.
     *
     * @return array<string, float> Template name => duration in milliseconds
     */
    private function collectTemplateTimes(): array
    {
        if ($this->twig === null) {
            return [];
        }

        // Symfony registers Symfony\Bridge\Twig\Extension\ProfilerExtension (extends Twig's); look it up by that class first
        $extension = null;
        if (class_exists(\Symfony\Bridge\Twig\Extension\ProfilerExtension::class) && $this->twig->hasExtension(\Symfony\Bridge\Twig\Extension\ProfilerExtension::class)) {
            $extension = $this->twig->getExtension(\Symfony\Bridge\Twig\Extension\ProfilerExtension::class);
        }
        if ($extension === null && $this->twig->hasExtension(ProfilerExtension::class)) {
            $extension = $this->twig->getExtension(ProfilerExtension::class);
        }
        if (!$extension instanceof ProfilerExtension) {
            return [];
        }

        $profile = $this->getProfileFromExtension($extension);
        if (!$profile instanceof Profile) {
            return [];
        }

        $times = [];
        $this->aggregateTemplateTimes($profile, $times);
        arsort($times, SORT_NUMERIC);

        return $times;
    }

    /**
     * Gets the root Profile from Twig's ProfilerExtension (it is stored in a private property).
     *
     * @param ProfilerExtension $extension The profiler extension
     *
     * @return Profile|null The root profile or null
     */
    private function getProfileFromExtension(ProfilerExtension $extension): ?Profile
    {
        try {
            $r = new ReflectionProperty($extension, 'actives');
            $r->setAccessible(true);
            $actives = $r->getValue($extension);

            return is_array($actives) && isset($actives[0]) && $actives[0] instanceof Profile ? $actives[0] : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Recursively aggregates template durations from a Twig Profile.
     *
     * @param Profile $profile The profile node
     * @param array<string, float> $times Accumulator: template name => total ms
     */
    private function aggregateTemplateTimes(Profile $profile, array &$times): void
    {
        if ($profile->isTemplate()) {
            $name       = $profile->getTemplate();
            $durationMs = $profile->getDuration() * 1000;
            if (!isset($times[$name])) {
                $times[$name] = 0.0;
            }
            $times[$name] += $durationMs;
        }

        foreach ($profile as $child) {
            if ($child instanceof Profile) {
                $this->aggregateTemplateTimes($child, $times);
            }
        }
    }

    /**
     * Resets the data collector.
     */
    public function reset(): void
    {
        $this->data = [
            'templates'         => [],
            'blocks'            => [],
            'controllers'       => [],
            'template_times'    => [],
            'total_templates'   => 0,
            'total_blocks'      => 0,
            'total_controllers' => 0,
            'enabled'           => false,
            'config'            => [],
        ];
    }

    /**
     * Returns the name of the collector.
     *
     * @return string The collector name
     */
    public function getName(): string
    {
        return 'twig_inspector';
    }

    /**
     * Gets the collected data.
     *
     * @return array<string, mixed> The collected data
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Gets template usage statistics.
     *
     * @return array<string, mixed> Template statistics
     */
    public function getTemplates(): array
    {
        return $this->data['templates'] ?? [];
    }

    /**
     * Gets block usage statistics.
     *
     * @return array<string, mixed> Block statistics
     */
    public function getBlocks(): array
    {
        return $this->data['blocks'] ?? [];
    }

    /**
     * Gets total number of unique templates.
     *
     * @return int Total templates
     */
    public function getTotalTemplates(): int
    {
        return $this->data['total_templates'] ?? 0;
    }

    /**
     * Gets total number of unique blocks.
     *
     * @return int Total blocks
     */
    public function getTotalBlocks(): int
    {
        return $this->data['total_blocks'] ?? 0;
    }

    /**
     * Gets controller invocations (main request + sub-requests from render(controller(...))).
     *
     * @return list<array{name: string, count: int, is_main: bool}> Controller entries (is_main = true for the main request controller)
     */
    public function getControllers(): array
    {
        return $this->data['controllers'] ?? [];
    }

    /**
     * Gets total number of unique controllers invoked.
     *
     * @return int Total controllers
     */
    public function getTotalControllers(): int
    {
        return $this->data['total_controllers'] ?? 0;
    }

    /**
     * Checks if inspector is enabled for this request.
     *
     * @return bool True if enabled
     */
    public function isEnabled(): bool
    {
        return $this->data['enabled'] ?? false;
    }

    /**
     * Gets template render times (template name => duration in ms).
     * Only populated when enable_metrics is true and Twig profiler is available.
     *
     * @return array<string, float> Template times
     */
    public function getTemplateTimes(): array
    {
        return $this->data['template_times'] ?? [];
    }

    /**
     * Gets frontend config (overlay theme, compact, reduced_motion, keyboard_shortcut).
     *
     * @return array<string, mixed> Config for the inspector UI
     */
    public function getConfig(): array
    {
        return $this->data['config'] ?? [];
    }
}
