<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\EventSubscriber;

use Closure;
use Nowo\TwigInspectorBundle\RequestStack\MainOrMasterRequestProvider;
use Nowo\TwigInspectorBundle\Twig\HtmlCommentsExtension;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use function count;
use function is_array;
use function is_object;
use function is_string;
use function strlen;

/**
 * Injects HTML comments for each controller (main + fragments) when Twig Inspector is enabled.
 * Each comment includes: render type (main/fragment), controller and template used.
 * Template name is set by HtmlCommentsExtension during Twig render.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class ControllerCommentSubscriber implements EventSubscriberInterface
{
    /** Box-drawing opening for controller comment (same as Twig blocks: ┏). */
    private const COMMENT_OPEN = '┏';
    /** Box-drawing closing for fragment controller comment (same as Twig blocks: ┗). */
    private const COMMENT_CLOSE = '┗';

    /**
     * Constructor.
     *
     * @param MainOrMasterRequestProvider $mainOrMasterProvider Provides main/master request (and current)
     * @param string $cookieName Cookie name used to enable the inspector (e.g. twig_inspector_is_active)
     * @param bool $debug When false, no comments are injected (e.g. in production)
     */
    public function __construct(
        private readonly MainOrMasterRequestProvider $mainOrMasterProvider,
        private readonly string $cookieName,
        private readonly bool $debug = true
    ) {
    }

    /**
     * Subscribes to the kernel response event to inject controller comments.
     *
     * @return array<string, array{0: string, 1: int}> Event name => [method, priority]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            // Run early so we modify the controller response before the profiler wraps it
            KernelEvents::RESPONSE => ['onKernelResponse', -512],
        ];
    }

    /**
     * Injects controller HTML comments into the response when inspector is enabled.
     * Skips when debug is off, cookie is not set, or path is _wdt/_profiler.
     *
     * @param ResponseEvent $event The kernel response event
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$this->debug) {
            return;
        }

        $request  = $event->getRequest();
        $response = $event->getResponse();

        $master = $this->mainOrMasterProvider->getMainOrMasterRequest();
        if (!$master instanceof Request) {
            return;
        }

        if (!$master->cookies->getBoolean($this->cookieName, false)) {
            return;
        }

        $path = $master->getPathInfo();
        if (str_starts_with($path, '/_wdt') || str_starts_with($path, '/_profiler')) {
            return;
        }

        $content = $response->getContent();
        if ($content === false) {
            return;
        }
        if ($content === '') {
            return;
        }

        $controller    = $request->attributes->get('_controller');
        $controllerStr = $this->controllerToString($controller);
        $templateName  = $request->attributes->get(HtmlCommentsExtension::REQUEST_ATTR_ROOT_TEMPLATE);
        $templateName  = is_string($templateName) ? $templateName : null;
        $isMain        = $request === $master;

        if ($isMain) {
            if (!$this->looksLikeHtml($content)) {
                return;
            }
            $this->injectMainControllerComment($response, $content, $controllerStr, $templateName);
        } else {
            // Fragment (sub-request from render(controller())): wrap any content that looks like HTML
            if (!$this->looksLikeHtmlFragment($content)) {
                return;
            }
            $this->wrapFragmentWithComments($response, $content, $controllerStr, $templateName);
        }
    }

    /**
     * Injects the main controller comment after <body>, after <html>, or at the start of the content.
     *
     * @param Response $response The response to modify
     * @param string $content Current response content
     * @param string $controllerStr Controller string (e.g. FQCN::method)
     * @param string|null $templateName Optional template path from the request attribute
     */
    private function injectMainControllerComment(Response $response, string $content, string $controllerStr, ?string $templateName): void
    {
        $comment = "\n" . $this->buildComment($controllerStr, 'main', true, $templateName) . "\n";
        if (preg_match('/<body[^>]*>/iu', $content, $m)) {
            $pos     = strpos($content, $m[0]) + strlen($m[0]);
            $content = substr_replace($content, $comment, $pos, 0);
        } elseif (preg_match('/<html[^>]*>/iu', $content, $m)) {
            // Fallback: inject right after <html> or at start
            $pos     = strpos($content, $m[0]) + strlen($m[0]);
            $content = substr_replace($content, $comment, $pos, 0);
        } else {
            $content = $comment . $content;
        }
        $response->setContent($content);
    }

    /**
     * Wraps fragment content (sub-request output) with start and end controller comments.
     *
     * @param Response $response The response to modify
     * @param string $content Fragment content (e.g. HTML from render(controller()))
     * @param string $controllerStr Controller string (e.g. FQCN::method)
     * @param string|null $templateName Optional template path
     */
    private function wrapFragmentWithComments(Response $response, string $content, string $controllerStr, ?string $templateName): void
    {
        $start = $this->buildComment($controllerStr, 'fragment', true, $templateName);
        $end   = $this->buildComment($controllerStr, 'fragment', false, $templateName);
        $response->setContent($start . "\n" . $content . "\n" . $end);
    }

    /**
     * Builds an HTML comment string for controller (start or end).
     * Sanitizes controller and template strings to avoid breaking HTML comments.
     *
     * @param string $controllerStr Controller string (e.g. FQCN::method)
     * @param string $role Role label: 'main' or 'fragment'
     * @param bool $isStart True for opening comment (┏), false for closing (┗)
     * @param string|null $templateName Optional template path (only used when $isStart is true)
     *
     * @return string HTML comment (e.g. <!-- ┏ controller: FQCN::method [main] -->)
     */
    private function buildComment(string $controllerStr, string $role, bool $isStart, ?string $templateName = null): string
    {
        $safeController = str_replace(['--', '>'], ['', ''], $controllerStr);
        $safeTemplate   = $templateName !== null && $templateName !== '' ? str_replace(['--', '>'], ['', ''], $templateName) : null;
        if ($isStart) {
            $parts = ['controller:', $safeController, '[' . $role . ']'];
            if ($safeTemplate !== null) {
                $parts[] = 'template:';
                $parts[] = $safeTemplate;
            }

            return '<!-- ' . self::COMMENT_OPEN . ' ' . implode(' ', $parts) . ' -->';
        }

        return '<!-- ' . self::COMMENT_CLOSE . ' /controller -->';
    }

    /**
     * Checks whether the content looks like a full HTML page (DOCTYPE, html, or body tag).
     * Used to skip injection on plain text or non-HTML responses.
     *
     * @param string $content Response content to check
     *
     * @return bool True if content appears to be HTML (starts with DOCTYPE/html or contains body tag)
     */
    private function looksLikeHtml(string $content): bool
    {
        $trimmed = ltrim($content, " \t\n\r\x0B\x0C\xEF\xBB\xBF");
        if (str_starts_with($trimmed, '<!DOCTYPE') || str_starts_with($trimmed, '<html')) {
            return true;
        }

        return (bool) preg_match('/<body[^>]*>/iu', $content)

        ;
    }

    /**
     * Checks whether the content looks like an HTML fragment (contains angle-bracket tags).
     * Used for sub-request output (e.g. render(controller())) that may be a small HTML chunk.
     *
     * @param string $content Fragment content to check
     *
     * @return bool True if content contains at least one HTML-like tag (e.g. <div>...</div>)
     */
    private function looksLikeHtmlFragment(string $content): bool
    {
        return str_contains($content, '<') && str_contains($content, '>');
    }

    /**
     * Converts a controller value (string, array, Closure, or other) to a display string.
     *
     * @param mixed $controller The controller from the request (e.g. 'App\Controller::index', [object, 'method'], Closure)
     *
     * @return string Display string (e.g. 'App\Controller::index', 'Closure', or 'unknown')
     */
    private function controllerToString(mixed $controller): string
    {
        if (is_string($controller)) {
            return $controller;
        }
        if (is_array($controller) && count($controller) === 2) {
            $class = is_object($controller[0]) ? $controller[0]::class : (string) $controller[0];

            return $class . '::' . $controller[1];
        }
        if ($controller instanceof Closure) {
            return 'Closure';
        }

        return 'unknown';
    }
}
