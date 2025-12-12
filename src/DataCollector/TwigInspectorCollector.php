<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Throwable;

/**
 * Empty data collector, required in order to add an icon to Symfony Debug Toolbar.
 *
 * @package Nowo\TwigInspectorBundle\DataCollector
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2024 Nowo.tech
 */
class TwigInspectorCollector implements DataCollectorInterface
{
  /**
   * Collects data for the given request and response.
   *
   * @param Request $request The request object
   * @param Response $response The response object
   * @param Throwable|null $exception The exception if any
   *
   * @return void
   */
  /**
   * Collects data for the given request and response.
   * This collector is intentionally empty as it's only used to add an icon to the Web Profiler toolbar.
   *
   * @param Request $request The request object
   * @param Response $response The response object
   * @param Throwable|null $exception The exception if any
   *
   * @return void
   */
  public function collect(Request $request, Response $response, ?Throwable $exception = null): void
  {
    // Empty collector, only used to add icon to toolbar
  }

  /**
   * Resets the data collector.
   *
   * @return void
   */
  /**
   * Resets the data collector.
   * This method is intentionally empty as there's no data to reset.
   *
   * @return void
   */
  public function reset(): void
  {
    // Nothing to reset
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
}

