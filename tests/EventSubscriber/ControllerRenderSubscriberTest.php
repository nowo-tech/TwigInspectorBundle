<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Tests\EventSubscriber;

use Nowo\TwigInspectorBundle\EventSubscriber\ControllerRenderSubscriber;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class ControllerRenderSubscriberTest extends TestCase
{
    private RequestStack $requestStack;
    private ControllerRenderSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->subscriber   = new ControllerRenderSubscriber($this->requestStack);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = ControllerRenderSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(KernelEvents::CONTROLLER, $events);
        $this->assertArrayHasKey(KernelEvents::TERMINATE, $events);
    }

    public function testGetControllersForRequestReturnsEmptyForUnknownRequest(): void
    {
        $request     = new Request();
        $controllers = $this->subscriber->getControllersForRequest($request);
        $this->assertSame([], $controllers);
    }

    public function testOnControllerRecordsControllerAndGetControllersForRequestReturnsIt(): void
    {
        $request = new Request();
        $this->requestStack->push($request);

        $kernel     = $this->createMock(HttpKernelInterface::class);
        $controller = static fn () => null;
        $event      = new ControllerEvent($kernel, $controller, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->subscriber->onController($event);

        $controllers = $this->subscriber->getControllersForRequest($request);
        $this->assertCount(1, $controllers);
        $this->assertSame('Closure', $controllers[0]['name']);
        $this->assertSame(1, $controllers[0]['count']);
        $this->assertTrue($controllers[0]['is_main']);
    }

    public function testOnControllerWithArrayController(): void
    {
        $request = new Request();
        $this->requestStack->push($request);

        $kernel     = $this->createMock(HttpKernelInterface::class);
        $controller = [new class {
            public function home(): void
            {
            }
        }, 'home'];
        $event = new ControllerEvent($kernel, $controller, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->subscriber->onController($event);

        $controllers = $this->subscriber->getControllersForRequest($request);
        $this->assertCount(1, $controllers);
        $this->assertStringEndsWith('::home', $controllers[0]['name']);
        $this->assertTrue($controllers[0]['is_main']);
    }

    public function testOnControllerRecordsMultipleInvocationsAndMergesByCount(): void
    {
        $mainRequest = new Request();
        $this->requestStack->push($mainRequest);
        $fragmentRequest = new Request();
        $this->requestStack->push($fragmentRequest);

        $kernel     = $this->createMock(HttpKernelInterface::class);
        $controller = [new class {
            public function fragment(): void
            {
            }
        }, 'fragment'];

        $this->subscriber->onController(new ControllerEvent($kernel, $controller, $fragmentRequest, HttpKernelInterface::SUB_REQUEST));
        $this->subscriber->onController(new ControllerEvent($kernel, $controller, $fragmentRequest, HttpKernelInterface::SUB_REQUEST));

        $controllers = $this->subscriber->getControllersForRequest($mainRequest);
        $this->assertCount(1, $controllers);
        $this->assertSame(2, $controllers[0]['count']);
        $this->assertFalse($controllers[0]['is_main']);
    }

    public function testOnTerminateCleansUpControllersForMasterRequest(): void
    {
        $request = new Request();
        $this->requestStack->push($request);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event  = new ControllerEvent($kernel, static fn () => null, $request, HttpKernelInterface::MAIN_REQUEST);
        $this->subscriber->onController($event);

        $this->assertCount(1, $this->subscriber->getControllersForRequest($request));

        $terminateEvent = new TerminateEvent($kernel, $request, new HttpResponse());
        $this->subscriber->onTerminate($terminateEvent);

        $this->assertSame([], $this->subscriber->getControllersForRequest($request));
    }

    public function testOnTerminateDoesNotCleanUpForSubRequest(): void
    {
        $mainRequest = new Request();
        $this->requestStack->push($mainRequest);
        $subRequest = new Request();
        $this->requestStack->push($subRequest);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $this->subscriber->onController(new ControllerEvent($kernel, static fn () => null, $mainRequest, HttpKernelInterface::MAIN_REQUEST));
        $this->subscriber->onController(new ControllerEvent($kernel, static fn () => null, $subRequest, HttpKernelInterface::SUB_REQUEST));

        $terminateEvent = new TerminateEvent($kernel, $subRequest, new HttpResponse());
        $this->subscriber->onTerminate($terminateEvent);

        $this->assertCount(1, $this->subscriber->getControllersForRequest($mainRequest));
    }

    /**
     * When RequestStack::getMainRequest() returns null (e.g. stack empty), subscriber uses request as master.
     */
    public function testOnControllerWhenMainRequestIsNullUsesRequestAsMaster(): void
    {
        $requestStack = $this->createStub(RequestStack::class);
        $requestStack->method('getMainRequest')->willReturn(null);

        $subscriber = new ControllerRenderSubscriber($requestStack);
        $request    = new Request();
        $kernel     = $this->createMock(HttpKernelInterface::class);
        $event      = new ControllerEvent($kernel, static fn () => null, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onController($event);

        $controllers = $subscriber->getControllersForRequest($request);
        $this->assertCount(1, $controllers);
        $this->assertSame('Closure', $controllers[0]['name']);
        $this->assertTrue($controllers[0]['is_main']);
    }

    /** Covers controllerToString via reflection (string and unknown branches). */
    public function testControllerToStringViaReflection(): void
    {
        $subscriber = new ControllerRenderSubscriber(new RequestStack());
        $reflection = new ReflectionClass($subscriber);
        $method     = $reflection->getMethod('controllerToString');
        $method->setAccessible(true);

        $this->assertSame('App\\Controller::index', $method->invoke($subscriber, 'App\\Controller::index'));
        $this->assertSame('unknown', $method->invoke($subscriber, new stdClass()));
    }

    /** Covers getMainOrMasterRequest via reflection. */
    public function testGetMainOrMasterRequestViaReflection(): void
    {
        $requestStack = new RequestStack();
        $request      = new Request();
        $requestStack->push($request);

        $subscriber = new ControllerRenderSubscriber($requestStack);
        $reflection = new ReflectionClass($subscriber);
        $method     = $reflection->getMethod('getMainOrMasterRequest');
        $method->setAccessible(true);

        $this->assertSame($request, $method->invoke($subscriber));
    }
}
