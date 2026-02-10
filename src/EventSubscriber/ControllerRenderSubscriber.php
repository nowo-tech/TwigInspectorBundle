<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Records every controller invocation (main request + sub-requests from render(controller(...)))
 * so the Twig Inspector collector can display them in the profiler panel.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
final class ControllerRenderSubscriber implements EventSubscriberInterface
{
    /** @var array<int, list<array{name: string, is_main: bool}>> Master request object id => list of controller entries */
    private array $controllersByMasterRequest = [];

    /**
     * Constructor.
     *
     * @param RequestStack $requestStack The request stack (for main/master request)
     */
    public function __construct(
        private readonly RequestStack $requestStack
    ) {
    }

    /**
     * Subscribes to the controller and terminate events to record controller invocations.
     *
     * @return array<string, array{0: string, 1: int}> Event name => [method, priority]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onController', 0],
            KernelEvents::TERMINATE => ['onTerminate', 0],
        ];
    }

    /**
     * Records each controller invocation (main request or sub-request from render(controller())).
     *
     * @param ControllerEvent $event The controller event
     */
    public function onController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $master = $this->getMainOrMasterRequest();
        $master ??= $request;
        $key = spl_object_id($master);

        if (!isset($this->controllersByMasterRequest[$key])) {
            $this->controllersByMasterRequest[$key] = [];
        }

        $controller = $event->getController();
        $this->controllersByMasterRequest[$key][] = [
            'name' => $this->controllerToString($controller),
            'is_main' => $request === $master,
        ];
    }

    /**
     * Cleans up recorded controllers for the master request when the request terminates.
     *
     * @param TerminateEvent $event The terminate event
     */
    public function onTerminate(TerminateEvent $event): void
    {
        $request = $event->getRequest();
        $master = $this->getMainOrMasterRequest();
        if ($master === null || $request !== $master) {
            return;
        }
        $key = spl_object_id($request);
        unset($this->controllersByMasterRequest[$key]);
    }

    /**
     * Returns the main (or master) request from the stack (BC: getMainRequest vs getMasterRequest).
     */
    private function getMainOrMasterRequest(): ?Request
    {
        if (method_exists($this->requestStack, 'getMainRequest')) {
            return $this->requestStack->getMainRequest();
        }

        return $this->requestStack->getMasterRequest();
    }

    /**
     * Returns the list of controller strings recorded for the given (master) request.
     * Used by TwigInspectorCollector when collecting data.
     *
     * @param object $masterRequest The master (main) request object (used as key for stored controllers)
     *
     * @return list<array{name: string, count: int, is_main: bool}>
     */
    public function getControllersForRequest(object $masterRequest): array
    {
        $key = spl_object_id($masterRequest);
        $list = $this->controllersByMasterRequest[$key] ?? [];

        $byName = [];
        foreach ($list as $entry) {
            $name = $entry['name'];
            if (!isset($byName[$name])) {
                $byName[$name] = ['name' => $name, 'count' => 0, 'is_main' => false];
            }
            $byName[$name]['count']++;
            if ($entry['is_main']) {
                $byName[$name]['is_main'] = true;
            }
        }

        return array_values($byName);
    }

    /**
     * Converts a controller value (string, array, Closure, or other) to a display string.
     *
     * @param mixed $controller The controller from the event (e.g. 'App\Controller::index', [object, 'method'], Closure)
     *
     * @return string Display string (e.g. 'App\Controller::index', 'Closure', or 'unknown')
     */
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
