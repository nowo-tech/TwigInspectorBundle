<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\EventSubscriber;

use Closure;
use Nowo\TwigInspectorBundle\RequestStack\MainOrMasterRequestProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Service\ResetInterface;
use WeakMap;

use function count;
use function is_array;
use function is_object;
use function is_string;

/**
 * Records every controller invocation (main request + sub-requests from render(controller(...)))
 * so the Twig Inspector collector can display them in the profiler panel.
 *
 * Entries are keyed by the main request object (weakly), removed on kernel.terminate and dropped by
 * {@see reset()}, so nothing accumulates in a long-running worker even without kernel.reset.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class ControllerRenderSubscriber implements EventSubscriberInterface, ResetInterface
{
    /** @var WeakMap<object, list<array{name: string, is_main: bool}>> Master request => list of controller entries */
    private WeakMap $controllersByMasterRequest;

    /**
     * Constructor.
     *
     * @param MainOrMasterRequestProvider $mainOrMasterProvider Provides main/master request
     */
    public function __construct(
        private readonly MainOrMasterRequestProvider $mainOrMasterProvider
    ) {
        $this->controllersByMasterRequest = new WeakMap();
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
            KernelEvents::TERMINATE  => ['onTerminate', 0],
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
        $master  = $this->mainOrMasterProvider->getMainOrMasterRequest();
        $master ??= $request;

        $entries   = $this->controllersByMasterRequest[$master] ?? [];
        $entries[] = [
            'name'    => $this->controllerToString($event->getController()),
            'is_main' => $request === $master,
        ];
        $this->controllersByMasterRequest[$master] = $entries;
    }

    /**
     * Cleans up recorded controllers for the terminating request.
     * The request stack is already empty at kernel.terminate, so the event request is used directly.
     *
     * @param TerminateEvent $event The terminate event
     */
    public function onTerminate(TerminateEvent $event): void
    {
        unset($this->controllersByMasterRequest[$event->getRequest()]);
    }

    /**
     * Drops every recorded controller list (kernel.reset).
     */
    public function reset(): void
    {
        $this->controllersByMasterRequest = new WeakMap();
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
        $list = $this->controllersByMasterRequest[$masterRequest] ?? [];

        $byName = [];
        foreach ($list as $entry) {
            $name = $entry['name'];
            if (!isset($byName[$name])) {
                $byName[$name] = ['name' => $name, 'count' => 0, 'is_main' => false];
            }
            ++$byName[$name]['count'];
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
