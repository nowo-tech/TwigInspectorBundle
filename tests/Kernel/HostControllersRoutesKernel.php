<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Tests\Kernel;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

use function dirname;

/**
 * Test kernel whose routing is only the Symfony default `controllers: { resource: routing.controllers }`
 * (no import of the bundle routes.yaml): the bundle route must not be registered there.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class HostControllersRoutesKernel extends Kernel
{
    use MicroKernelTrait;

    public function getProjectDir(): string
    {
        return dirname(__DIR__) . '/Fixtures/app';
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir() . '/var/cache/host-controllers-' . $this->environment;
    }

    private function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('routing.controllers');
    }
}
