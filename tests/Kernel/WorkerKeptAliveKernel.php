<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Tests\Kernel;

use Nowo\TwigInspectorBundle\EventSubscriber\ControllerCommentSubscriber;
use Nowo\TwigInspectorBundle\NowoTwigInspectorBundle;
use Nowo\TwigInspectorBundle\Twig\HtmlCommentsExtension;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

use function dirname;
use function getmypid;
use function sys_get_temp_dir;

/**
 * Host-like kernel (env test, kernel debug off, inspector forced on) for the kept-alive worker test: the bundle routes are
 * imported and one host route renders a template that extends a base with a block.
 */
final class WorkerKeptAliveKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new NowoTwigInspectorBundle();
    }

    public function getProjectDir(): string
    {
        return dirname(__DIR__) . '/Fixtures/worker_app';
    }

    public function getCacheDir(): string
    {
        return $this->getBaseTempDir() . '/' . $this->environment;
    }

    public function getLogDir(): string
    {
        return $this->getBaseTempDir() . '/log';
    }

    public function getBaseTempDir(): string
    {
        return sys_get_temp_dir() . '/nowo-twig-inspector-bundle-worker/' . getmypid();
    }

    /**
     * Symfony's own debug services (traceable dispatcher, stopwatch, debug logger, ...) rely on `kernel.reset` and keep
     * data per request when it never runs, which says nothing about this bundle. So the kernel itself runs with debug
     * off (as a production worker does) and only the inspector's own `debug` argument is forced on.
     */
    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new class implements CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                foreach ([HtmlCommentsExtension::class, ControllerCommentSubscriber::class] as $id) {
                    $definition = $container->getDefinition($id);
                    foreach ($definition->getArguments() as $index => $argument) {
                        if ($argument === '%kernel.debug%') {
                            $definition->replaceArgument($index, true);
                        }
                    }
                }
            }
        });
    }

    private function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret'                => 'test-secret',
            'test'                  => true,
            'http_method_override'  => false,
            'handle_all_throwables' => true,
            'php_errors'            => ['log' => true],
            'router'                => ['utf8' => true],
            'property_info'         => ['enabled' => false],
            'assets'                => ['enabled' => false],
        ]);
        $container->extension('twig', ['paths' => [dirname(__DIR__) . '/Fixtures/worker_templates']]);
        $container->services()
            ->set(HostPageController::class)
                ->autowire()
                ->public()
                ->tag('controller.service_arguments');
    }

    private function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('@NowoTwigInspectorBundle/Resources/config/routes.yaml');
        $routes->add('host_page', '/page')->controller(HostPageController::class);
    }
}
