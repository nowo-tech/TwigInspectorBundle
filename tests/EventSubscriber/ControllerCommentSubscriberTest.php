<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Tests\EventSubscriber;

use Nowo\TwigInspectorBundle\EventSubscriber\ControllerCommentSubscriber;
use Nowo\TwigInspectorBundle\Twig\HtmlCommentsExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class ControllerCommentSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = ControllerCommentSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(KernelEvents::RESPONSE, $events);
        $this->assertSame(['onKernelResponse', -512], $events[KernelEvents::RESPONSE]);
    }

    public function testOnKernelResponseDoesNotModifyResponseWhenDebugFalse(): void
    {
        $requestStack = new RequestStack();
        $request = new Request();
        $request->cookies->set('twig_inspector_is_active', '1');
        $requestStack->push($request);

        $subscriber = new ControllerCommentSubscriber($requestStack, 'twig_inspector_is_active', false);
        $response = new Response('<html><body>Hello</body></html>');
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $subscriber->onKernelResponse($event);

        $this->assertSame('<html><body>Hello</body></html>', $response->getContent());
    }

    public function testOnKernelResponseDoesNotModifyResponseWhenCookieNotSet(): void
    {
        $requestStack = new RequestStack();
        $request = new Request();
        $requestStack->push($request);

        $subscriber = new ControllerCommentSubscriber($requestStack, 'twig_inspector_is_active', true);
        $response = new Response('<html><body>Hello</body></html>');
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $subscriber->onKernelResponse($event);

        $this->assertSame('<html><body>Hello</body></html>', $response->getContent());
    }

    public function testOnKernelResponseInjectsCommentAfterBodyWhenCookieSet(): void
    {
        $requestStack = new RequestStack();
        $request = new Request();
        $request->cookies->set('twig_inspector_is_active', '1');
        $request->attributes->set('_controller', 'App\\Controller\\DemoController::home');
        $requestStack->push($request);

        $subscriber = new ControllerCommentSubscriber($requestStack, 'twig_inspector_is_active', true);
        $response = new Response('<html><body>Hello</body></html>');
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $subscriber->onKernelResponse($event);

        $content = $response->getContent();
        $this->assertStringContainsString('┏', (string) $content);
        $this->assertStringContainsString('controller:', (string) $content);
        $this->assertStringContainsString('[main]', (string) $content);
        $this->assertStringContainsString('<body>', (string) $content);
        $this->assertStringContainsString('Hello', (string) $content);
    }

    public function testOnKernelResponseInjectsCommentWithTemplateWhenRootTemplateAttributeSet(): void
    {
        $requestStack = new RequestStack();
        $request = new Request();
        $request->cookies->set('twig_inspector_is_active', '1');
        $request->attributes->set('_controller', 'App\\Controller\\DemoController::home');
        $request->attributes->set(HtmlCommentsExtension::REQUEST_ATTR_ROOT_TEMPLATE, 'demo/home.html.twig');
        $requestStack->push($request);

        $subscriber = new ControllerCommentSubscriber($requestStack, 'twig_inspector_is_active', true);
        $response = new Response('<html><body>Hello</body></html>');
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $subscriber->onKernelResponse($event);

        $content = $response->getContent();
        $this->assertStringContainsString('template:', (string) $content);
        $this->assertStringContainsString('demo/home.html.twig', (string) $content);
    }

    public function testOnKernelResponseWrapsFragmentContentWithCommentsWhenSubRequest(): void
    {
        $mainRequest = new Request();
        $mainRequest->cookies->set('twig_inspector_is_active', '1');
        $requestStack = new RequestStack();
        $requestStack->push($mainRequest);

        $fragmentRequest = new Request();
        $fragmentRequest->attributes->set('_controller', 'App\\Controller\\DemoController::fragment');
        $fragmentRequest->attributes->set(HtmlCommentsExtension::REQUEST_ATTR_ROOT_TEMPLATE, 'demo/_controller_fragment.html.twig');
        $requestStack->push($fragmentRequest);

        $subscriber = new ControllerCommentSubscriber($requestStack, 'twig_inspector_is_active', true);
        $fragmentContent = '<div class="info-box">Fragment content</div>';
        $response = new Response($fragmentContent);
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $fragmentRequest,
            HttpKernelInterface::SUB_REQUEST,
            $response
        );

        $subscriber->onKernelResponse($event);

        $content = (string) $response->getContent();
        $this->assertStringContainsString('┏', $content);
        $this->assertStringContainsString('controller:', $content);
        $this->assertStringContainsString('[fragment]', $content);
        $this->assertStringContainsString('template: demo/_controller_fragment.html.twig', $content);
        $this->assertStringContainsString('┗', $content);
        $this->assertStringContainsString('/controller', $content);
        $this->assertStringContainsString($fragmentContent, $content);
    }

    public function testOnKernelResponseSkipsWhenNoMasterRequest(): void
    {
        $requestStack = new RequestStack();
        $request = new Request();
        $request->cookies->set('twig_inspector_is_active', '1');
        $requestStack->push($request);
        $requestStack->pop();

        $subscriber = new ControllerCommentSubscriber($requestStack, 'twig_inspector_is_active', true);
        $response = new Response('<html><body>Hi</body></html>');
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $subscriber->onKernelResponse($event);

        $this->assertSame('<html><body>Hi</body></html>', $response->getContent());
    }

    public function testOnKernelResponseSkipsWhenPathIsWdt(): void
    {
        $requestStack = new RequestStack();
        $request = Request::create('/_wdt/abc123');
        $request->cookies->set('twig_inspector_is_active', '1');
        $request->attributes->set('_controller', 'App\\Controller\\DemoController::home');
        $requestStack->push($request);

        $subscriber = new ControllerCommentSubscriber($requestStack, 'twig_inspector_is_active', true);
        $response = new Response('<html><body>Hi</body></html>');
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $subscriber->onKernelResponse($event);

        $this->assertSame('<html><body>Hi</body></html>', $response->getContent());
    }

    public function testOnKernelResponseSkipsWhenPathIsProfiler(): void
    {
        $requestStack = new RequestStack();
        $request = Request::create('/_profiler/abc123');
        $request->cookies->set('twig_inspector_is_active', '1');
        $request->attributes->set('_controller', 'App\\Controller\\DemoController::home');
        $requestStack->push($request);

        $subscriber = new ControllerCommentSubscriber($requestStack, 'twig_inspector_is_active', true);
        $response = new Response('<html><body>Hi</body></html>');
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $subscriber->onKernelResponse($event);

        $this->assertSame('<html><body>Hi</body></html>', $response->getContent());
    }

    public function testOnKernelResponseSkipsWhenContentEmpty(): void
    {
        $requestStack = new RequestStack();
        $request = new Request();
        $request->cookies->set('twig_inspector_is_active', '1');
        $request->attributes->set('_controller', 'App\\Controller\\DemoController::home');
        $requestStack->push($request);

        $subscriber = new ControllerCommentSubscriber($requestStack, 'twig_inspector_is_active', true);
        $response = new Response('');
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $subscriber->onKernelResponse($event);

        $this->assertSame('', $response->getContent());
    }

    public function testOnKernelResponseSkipsWhenMainRequestContentNotHtml(): void
    {
        $requestStack = new RequestStack();
        $request = new Request();
        $request->cookies->set('twig_inspector_is_active', '1');
        $request->attributes->set('_controller', 'App\\Controller\\DemoController::home');
        $requestStack->push($request);

        $subscriber = new ControllerCommentSubscriber($requestStack, 'twig_inspector_is_active', true);
        $response = new Response('plain text');
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $subscriber->onKernelResponse($event);

        $this->assertSame('plain text', $response->getContent());
    }

    public function testOnKernelResponseSkipsWhenFragmentContentNotHtml(): void
    {
        $mainRequest = new Request();
        $mainRequest->cookies->set('twig_inspector_is_active', '1');
        $requestStack = new RequestStack();
        $requestStack->push($mainRequest);

        $fragmentRequest = new Request();
        $fragmentRequest->attributes->set('_controller', 'App\\Controller\\DemoController::fragment');
        $requestStack->push($fragmentRequest);

        $subscriber = new ControllerCommentSubscriber($requestStack, 'twig_inspector_is_active', true);
        $response = new Response('no tags here');
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $fragmentRequest,
            HttpKernelInterface::SUB_REQUEST,
            $response
        );

        $subscriber->onKernelResponse($event);

        $this->assertSame('no tags here', $response->getContent());
    }

    public function testOnKernelResponseInjectsAfterHtmlWhenNoBody(): void
    {
        $requestStack = new RequestStack();
        $request = new Request();
        $request->cookies->set('twig_inspector_is_active', '1');
        $request->attributes->set('_controller', 'App\\Controller\\DemoController::home');
        $requestStack->push($request);

        $subscriber = new ControllerCommentSubscriber($requestStack, 'twig_inspector_is_active', true);
        $content = '<html><head><title>Test</title></head></html>';
        $response = new Response($content);
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $subscriber->onKernelResponse($event);

        $output = (string) $response->getContent();
        $this->assertStringContainsString('┏', $output);
        $this->assertStringContainsString('controller:', $output);
        $this->assertStringContainsString('[main]', $output);
        $this->assertStringContainsString('<head><title>Test</title></head></html>', $output);
        $this->assertMatchesRegularExpression('/<html>\s*<!--\s*┏\s*controller:/', $output);
    }

    public function testOnKernelResponseInjectsAtStartWhenDoctypeButNoHtmlNorBody(): void
    {
        $requestStack = new RequestStack();
        $request = new Request();
        $request->cookies->set('twig_inspector_is_active', '1');
        $request->attributes->set('_controller', 'App\\Controller\\DemoController::home');
        $requestStack->push($request);

        $subscriber = new ControllerCommentSubscriber($requestStack, 'twig_inspector_is_active', true);
        $content = '<!DOCTYPE html><p>fragment</p>';
        $response = new Response($content);
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $subscriber->onKernelResponse($event);

        $output = (string) $response->getContent();
        $this->assertStringContainsString('<!-- ', $output);
        $this->assertStringContainsString('┏', $output);
        $this->assertStringContainsString('controller:', $output);
        $this->assertStringContainsString('[main]', $output);
        $this->assertStringContainsString('<!DOCTYPE html><p>fragment</p>', $output);
    }

    public function testOnKernelResponseControllerAsString(): void
    {
        $requestStack = new RequestStack();
        $request = new Request();
        $request->cookies->set('twig_inspector_is_active', '1');
        $request->attributes->set('_controller', 'App\\Controller\\DemoController::index');
        $requestStack->push($request);

        $subscriber = new ControllerCommentSubscriber($requestStack, 'twig_inspector_is_active', true);
        $response = new Response('<html><body>Ok</body></html>');
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $subscriber->onKernelResponse($event);

        $this->assertStringContainsString('App\\Controller\\DemoController::index', (string) $response->getContent());
    }

    public function testOnKernelResponseControllerUnknownWhenNotStringArrayOrClosure(): void
    {
        $requestStack = new RequestStack();
        $request = new Request();
        $request->cookies->set('twig_inspector_is_active', '1');
        $request->attributes->set('_controller', 123);
        $requestStack->push($request);

        $subscriber = new ControllerCommentSubscriber($requestStack, 'twig_inspector_is_active', true);
        $response = new Response('<html><body>Ok</body></html>');
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $subscriber->onKernelResponse($event);

        $this->assertStringContainsString('unknown', (string) $response->getContent());
    }

    public function testOnKernelResponseMainCommentWithoutTemplateWhenAttributeEmpty(): void
    {
        $requestStack = new RequestStack();
        $request = new Request();
        $request->cookies->set('twig_inspector_is_active', '1');
        $request->attributes->set('_controller', 'App\\Controller\\DemoController::home');
        $request->attributes->set(HtmlCommentsExtension::REQUEST_ATTR_ROOT_TEMPLATE, '');
        $requestStack->push($request);

        $subscriber = new ControllerCommentSubscriber($requestStack, 'twig_inspector_is_active', true);
        $response = new Response('<html><body>Ok</body></html>');
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $subscriber->onKernelResponse($event);

        $output = (string) $response->getContent();
        $this->assertStringContainsString('┏', $output);
        $this->assertStringContainsString('[main]', $output);
        $this->assertStringNotContainsString('template:', $output);
    }

    public function testFragmentCommentContainsClosingTag(): void
    {
        $mainRequest = new Request();
        $mainRequest->cookies->set('twig_inspector_is_active', '1');
        $requestStack = new RequestStack();
        $requestStack->push($mainRequest);

        $fragmentRequest = new Request();
        $fragmentRequest->attributes->set('_controller', 'App\\Controller\\DemoController::fragment');
        $requestStack->push($fragmentRequest);

        $subscriber = new ControllerCommentSubscriber($requestStack, 'twig_inspector_is_active', true);
        $response = new Response('<p>Hi</p>');
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $fragmentRequest,
            HttpKernelInterface::SUB_REQUEST,
            $response
        );

        $subscriber->onKernelResponse($event);

        $output = (string) $response->getContent();
        $this->assertStringContainsString('┗', $output);
        $this->assertStringContainsString('/controller', $output);
        $this->assertStringContainsString('<p>Hi</p>', $output);
    }

    /** Covers looksLikeHtml when content has only <body> (no DOCTYPE or <html> at start). */
    public function testOnKernelResponseInjectsWhenContentHasOnlyBodyTag(): void
    {
        $requestStack = new RequestStack();
        $request = new Request();
        $request->cookies->set('twig_inspector_is_active', '1');
        $request->attributes->set('_controller', 'App\\Controller\\DemoController::home');
        $requestStack->push($request);

        $subscriber = new ControllerCommentSubscriber($requestStack, 'twig_inspector_is_active', true);
        $content = "  \n <body>Hi</body>";
        $response = new Response($content);
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $subscriber->onKernelResponse($event);

        $output = (string) $response->getContent();
        $this->assertStringContainsString('┏', $output);
        $this->assertStringContainsString('[main]', $output);
        $this->assertStringContainsString('<body>', $output);
        $this->assertStringContainsString('Hi</body>', $output);
    }

    /** Covers controllerToString via reflection (string and unknown branches). */
    public function testControllerToStringViaReflection(): void
    {
        $subscriber = new ControllerCommentSubscriber(new RequestStack(), 'twig_inspector_is_active', true);
        $reflection = new \ReflectionClass($subscriber);
        $method = $reflection->getMethod('controllerToString');
        $method->setAccessible(true);

        $this->assertSame('App\\Controller::index', $method->invoke($subscriber, 'App\\Controller::index'));
        $this->assertSame('unknown', $method->invoke($subscriber, new \stdClass()));
    }

    /** Covers looksLikeHtml via reflection. */
    public function testLooksLikeHtmlViaReflection(): void
    {
        $subscriber = new ControllerCommentSubscriber(new RequestStack(), 'twig_inspector_is_active', true);
        $reflection = new \ReflectionClass($subscriber);
        $method = $reflection->getMethod('looksLikeHtml');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($subscriber, '<!DOCTYPE html><body>x</body>'));
        $this->assertTrue($method->invoke($subscriber, '<html><body>x</body></html>'));
        $this->assertTrue($method->invoke($subscriber, '  <body>x</body>'));
        $this->assertFalse($method->invoke($subscriber, 'plain text'));
    }

    /** Covers getMainOrMasterRequest via reflection when getMainRequest exists. */
    public function testGetMainOrMasterRequestViaReflection(): void
    {
        $requestStack = new RequestStack();
        $request = new Request();
        $requestStack->push($request);

        $subscriber = new ControllerCommentSubscriber($requestStack, 'twig_inspector_is_active', true);
        $reflection = new \ReflectionClass($subscriber);
        $method = $reflection->getMethod('getMainOrMasterRequest');
        $method->setAccessible(true);

        $this->assertSame($request, $method->invoke($subscriber));
    }
}
