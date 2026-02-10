<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Twig;

use Nowo\TwigInspectorBundle\BoxDrawings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;

/**
 * Twig extension that injects HTML comments before and after every block and template.
 * Comments contain template name, link, and a unique id for the inspector overlay.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class HtmlCommentsExtension extends AbstractExtension
{
    /** @var string|null Last wrapped content (used to detect nested blocks and update box style) */
    private ?string $previousContent = null;

    /** @var int Current nesting level for box-drawing style */
    private int $nestingLevel = 0;

    /**
     * Constructor.
     *
     * @param RequestStack          $requestStack              The request stack
     * @param UrlGeneratorInterface $urlGenerator              The URL generator for template links
     * @param BoxDrawings           $boxDrawings               The box-drawing character helper
     * @param array<string>         $enabledExtensions         Template extensions to inspect (e.g. ['.html.twig'])
     * @param array<string>         $excludedTemplates         Template names or wildcard patterns to exclude
     * @param array<string>         $excludedBlocks            Block names or wildcard patterns to exclude
     * @param string                $cookieName                Cookie name used to enable the inspector
     * @param int                   $maxInjectionDepth         Max nesting depth for comments (0 = unlimited)
     * @param array<string>         $excludedTemplatesRegex    Regex patterns for template exclusion
     * @param array<string>         $excludedTemplatesPrefixes Template name prefixes to exclude
     * @param array<string>         $excludedBlocksRegex       Regex patterns for block exclusion
     * @param bool                  $debug                     When false (e.g. prod), no injection to avoid any overhead
     */
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly BoxDrawings $boxDrawings,
        private readonly array $enabledExtensions = ['.html.twig'],
        private readonly array $excludedTemplates = [],
        private readonly array $excludedBlocks = [],
        private readonly string $cookieName = 'twig_inspector_is_active',
        private readonly int $maxInjectionDepth = 0,
        private readonly array $excludedTemplatesRegex = [],
        private readonly array $excludedTemplatesPrefixes = [],
        private readonly array $excludedBlocksRegex = [],
        private readonly bool $debug = true
    ) {
    }

    /**
     * Starts output buffering for a node.
     * Only starts buffering if the inspector is enabled and the node should be inspected.
     *
     * @param NodeReference $ref The node reference
     *
     * @return void
     */
    public function start(NodeReference $ref): void
    {
        if (!$this->shouldInspect($ref)) {
            return;
        }

        ob_start();
    }

    /**
     * Ends output buffering and adds comments.
     * Wraps the captured content with HTML comments containing template information.
     * Handles nested blocks by tracking nesting levels and updating box drawing styles.
     *
     * @param NodeReference $ref The node reference
     *
     * @return void
     */
    public function end(NodeReference $ref): void
    {
        if (!$this->shouldInspect($ref)) {
            return;
        }

        // Do not output if headers were already sent (e.g. error response) to avoid "Cannot modify header" warning
        if (headers_sent()) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            return;
        }

        if (ob_get_level() === 0) {
            return;
        }

        $content = ob_get_clean();

        if ($this->isSupported($content)) {
            if ($this->maxInjectionDepth > 0 && $this->nestingLevel > $this->maxInjectionDepth) {
                // Only echo when a parent buffer exists (e.g. Twig's render()), never to stdout
                if (ob_get_level() > 0) {
                    echo $content;
                }

                return;
            }
            // Check if this is a nested block (content contains previous content)
            if ((string) $this->previousContent !== '' && str_contains($content, (string) $this->previousContent)) {
                // If content changed, update box drawing style
                if (trim($content) !== trim((string) $this->previousContent)) {
                    $this->boxDrawings->blockChanged($this->nestingLevel);
                }

                ++$this->nestingLevel;
            } else {
                // Reset nesting level for new top-level block
                $this->nestingLevel = 0;
                $this->boxDrawings->blockChanged($this->nestingLevel);
            }

            // Wrap content with start and end comments
            $content = $this->getStartComment($ref) . $content . $this->getEndComment($ref);

            $this->previousContent = $content;
        }

        // Only echo when a parent buffer exists (e.g. Twig's render()), never to stdout
        if (!headers_sent() && ob_get_level() > 0) {
            echo $content;
        }
    }

    /**
     * Checks if the inspector should inspect the given node.
     * Only runs when the Web Profiler toolbar can be present (kernel.debug), then checks:
     * - A request is available
     * - The cookie is set to true
     * - The template file extension is in the enabled extensions list
     * - The template is not in the excluded templates list
     * - The block is not in the excluded blocks list
     *
     * @param NodeReference $ref The node reference
     *
     * @return bool True if should inspect, false otherwise
     */
    protected function shouldInspect(NodeReference $ref): bool
    {
        // No toolbar in prod / when debug is off: skip all work to avoid consuming resources
        if (!$this->debug) {
            return false;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (!$request instanceof Request || !$request->cookies->getBoolean($this->cookieName)) {
            return false;
        }

        // Do not inject on sub-requests (fragments, toolbar, etc.) to avoid "headers already sent"
        if ($this->requestStack->getParentRequest() !== null) {
            return false;
        }

        // Do not inject on Web Debug Toolbar / Profiler requests
        $path = $request->getPathInfo();
        if (str_starts_with($path, '/_wdt') || str_starts_with($path, '/_profiler')) {
            return false;
        }

        $template = $ref->getTemplate();

        // Check if template extension is enabled
        $extensionMatches = false;
        foreach ($this->enabledExtensions as $extension) {
            if (str_ends_with($template, $extension)) {
                $extensionMatches = true;
                break;
            }
        }

        if (!$extensionMatches) {
            return false;
        }

        // Check if template is excluded (wildcards, regex, prefixes)
        if ($this->isExcluded($template, $this->excludedTemplates)) {
            return false;
        }
        if ($this->isExcludedByRegex($template, $this->excludedTemplatesRegex)) {
            return false;
        }
        if ($this->isExcludedByPrefix($template, $this->excludedTemplatesPrefixes)) {
            return false;
        }

        // Check if block is excluded (wildcards, regex)
        $blockName = $ref->getName();
        if ($blockName !== $template && $this->isExcluded($blockName, $this->excludedBlocks)) {
            return false;
        }
        if ($blockName !== $template && $this->isExcludedByRegex($blockName, $this->excludedBlocksRegex)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if a name matches any exclusion pattern.
     *
     * @param string        $name     The name to check
     * @param array<string> $patterns List of patterns (supports wildcards with *)
     *
     * @return bool True if excluded, false otherwise
     */
    private function isExcluded(string $name, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            // Support wildcard patterns
            // Escape special regex characters, then replace * with .*
            $escaped = preg_quote($pattern, '/');
            $regex = '/^' . str_replace('\*', '.*', $escaped) . '$/';
            if (preg_match($regex, $name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if a name matches any regex exclusion pattern.
     *
     * @param string        $name    The name to check
     * @param array<string> $regexes List of regex patterns
     *
     * @return bool True if excluded, false otherwise
     */
    private function isExcludedByRegex(string $name, array $regexes): bool
    {
        foreach ($regexes as $regex) {
            if (@preg_match($regex, $name) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if a name has any of the given prefixes (namespace-style exclusion).
     *
     * @param string        $name     The name to check
     * @param array<string> $prefixes List of prefixes (e.g. ['@Admin/', 'components/'])
     *
     * @return bool True if excluded, false otherwise
     */
    private function isExcludedByPrefix(string $name, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the content is supported for inspection.
     * Only HTML content is supported, not plain text, JSON, or Backbone templates.
     *
     * @param string $string The content string
     *
     * @return bool True if supported, false otherwise
     */
    protected function isSupported(string $string): bool
    {
        // Check if content has HTML tags (if strip_tags returns the same string, it's plain text)
        if ($string === strip_tags($string)) {
            return false;
        }

        // Check if content starts with JSON brackets (faster than json_decode)
        $trimmed = trim($string);
        if ($trimmed !== '' && \in_array($trimmed[0], ['[', '{'], true)) {
            return false;
        }

        // Check if content is a Backbone template (contains <% %>)
        return !str_contains($string, '<%');
    }

    /**
     * Gets the start comment for a node.
     *
     * @param NodeReference $ref The node reference
     *
     * @return string The start comment
     */
    private function getStartComment(NodeReference $ref): string
    {
        $prefix = $this->boxDrawings->getStartCommentPrefix();

        return $this->getComment($prefix, $ref);
    }

    /**
     * Gets the end comment for a node.
     *
     * @param NodeReference $ref The node reference
     *
     * @return string The end comment
     */
    private function getEndComment(NodeReference $ref): string
    {
        $prefix = $this->boxDrawings->getEndCommentPrefix();

        return $this->getComment($prefix, $ref);
    }

    /**
     * Gets a comment string for a node.
     *
     * @param string        $prefix The comment prefix
     * @param NodeReference $ref    The node reference
     *
     * @return string The comment string
     */
    protected function getComment(string $prefix, NodeReference $ref): string
    {
        $link = $this->getLink($ref);

        return '<!-- ' . $prefix . ' ' . $ref->getName() . ' [' . $link . '] #' . $ref->getId() . '-->';
    }

    /**
     * Gets the link URL for a node.
     * Returns a fallback URL if the route is not available (e.g., in production).
     *
     * @param NodeReference $ref The node reference
     *
     * @return string The link URL or a fallback
     */
    protected function getLink(NodeReference $ref): string
    {
        try {
            return $this->urlGenerator->generate(
                'nowo_twig_inspector_template_link',
                [
                    'template' => $ref->getTemplate(),
                    'line' => $ref->getLine(),
                ]
            );
        } catch (RouteNotFoundException $e) {
            // Route not available (e.g., in production or routes not loaded)
            // Return a fallback that won't break the HTML comment
            return '/_template/' . urlencode($ref->getTemplate()) . '?line=' . $ref->getLine();
        }
    }
}
