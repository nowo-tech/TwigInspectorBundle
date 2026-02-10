<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Nowo\TwigInspectorBundle\Twig\HtmlCommentsExtension;

/**
 * Injects HTML comments for each controller (main + fragments) when Twig Inspector is enabled.
 * Each comment includes: render type (main/fragment), controller and template used.
 * Template name is set by HtmlCommentsExtension during Twig render.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
final class ControllerCommentSubscriber implements EventSubscriberInterface
{
    /** Box-drawing opening for controller comment (same as Twig blocks: ┏). */
    private const COMMENT_OPEN = '┏';
    /** Box-drawing closing for fragment controller comment (same as Twig blocks: ┗). */
    private const COMMENT_CLOSE = '┗';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly string $cookieName,
        private readonly bool $debug = true
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Run early so we modify the controller response before the profiler wraps it
            KernelEvents::RESPONSE => ['onKernelResponse', -512],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$this->debug) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        $master = $this->getMainOrMasterRequest();
        if ($master === null) {
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
        $content = (string) $content;
        if ($content === '') {
            return;
        }

        $controller = $request->attributes->get('_controller');
        $controllerStr = $this->controllerToString($controller);
        $templateName = $request->attributes->get(HtmlCommentsExtension::REQUEST_ATTR_ROOT_TEMPLATE);
        $templateName = \is_string($templateName) ? $templateName : null;
        $isMain = $request === $master;

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

    private function injectMainControllerComment(Response $response, string $content, string $controllerStr, ?string $templateName): void
    {
        $comment = "\n" . $this->buildComment($controllerStr, 'main', true, $templateName) . "\n";
        if (preg_match('/<body[^>]*>/iu', $content, $m)) {
            $pos = strpos($content, $m[0]) + \strlen($m[0]);
            $content = substr_replace($content, $comment, $pos, 0);
        } else {
            // Fallback: inject right after <html> or at start
            if (preg_match('/<html[^>]*>/iu', $content, $m)) {
                $pos = strpos($content, $m[0]) + \strlen($m[0]);
                $content = substr_replace($content, $comment, $pos, 0);
            } else {
                $content = $comment . $content;
            }
        }
        $response->setContent($content);
    }

    private function wrapFragmentWithComments(Response $response, string $content, string $controllerStr, ?string $templateName): void
    {
        $start = $this->buildComment($controllerStr, 'fragment', true, $templateName);
        $end = $this->buildComment($controllerStr, 'fragment', false, $templateName);
        $response->setContent($start . "\n" . $content . "\n" . $end);
    }

    private function buildComment(string $controllerStr, string $role, bool $isStart, ?string $templateName = null): string
    {
        $safeController = str_replace(['--', '>'], ['', ''], $controllerStr);
        $safeTemplate = $templateName !== null && $templateName !== '' ? str_replace(['--', '>'], ['', ''], $templateName) : null;
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

    /** Full page: DOCTYPE, html or body. */
    private function looksLikeHtml(string $content): bool
    {
        $trimmed = ltrim($content, " \t\n\r\x0B\x0C\xEF\xBB\xBF");
        if (str_starts_with($trimmed, '<!DOCTYPE') || str_starts_with($trimmed, '<html')) {
            return true;
        }
        if (preg_match('/<body[^>]*>/iu', $content)) {
            return true;
        }

        return false;
    }

    /** Fragment (sub-request): any chunk that contains HTML tags, e.g. <div>...</div>. */
    private function looksLikeHtmlFragment(string $content): bool
    {
        return str_contains($content, '<') && str_contains($content, '>');
    }

    private function getMainOrMasterRequest(): ?Request
    {
        if (method_exists($this->requestStack, 'getMainRequest')) {
            return $this->requestStack->getMainRequest();
        }
        /** @var callable(): ?Request $getMaster */
        $getMaster = [$this->requestStack, 'getMasterRequest'];

        return $getMaster();
    }

    private function controllerToString(mixed $controller): string
    {
        if (\is_string($controller)) {
            return $controller;
        }
        if (\is_array($controller) && \count($controller) === 2) {
            $class = \is_object($controller[0]) ? $controller[0]::class : (string) $controller[0];

            return $class . '::' . (string) $controller[1];
        }
        if ($controller instanceof \Closure) {
            return 'Closure';
        }

        return 'unknown';
    }
}
