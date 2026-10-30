<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Tests\Integration;

use Nowo\TwigInspectorBundle\BoxDrawings;
use Nowo\TwigInspectorBundle\Command\InstallCommand;
use Nowo\TwigInspectorBundle\Controller\OpenTemplateController;
use Nowo\TwigInspectorBundle\EventSubscriber\ControllerRenderSubscriber;
use Nowo\TwigInspectorBundle\Tests\Kernel\TestKernel;
use Nowo\TwigInspectorBundle\Twig\HtmlCommentsExtension;
use ReflectionProperty;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Integration tests: kernel boots with the bundle and services are available.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class BundleIntegrationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testKernelBoots(): void
    {
        self::bootKernel();
        $this->assertTrue(self::getContainer()->has('kernel'));
    }

    public function testBundleServicesAreRegistered(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $this->assertTrue($container->has('nowo_twig_inspector.controller.open_template'), 'OpenTemplateController (public) should be registered');
        $application = new Application(self::$kernel);
        $this->assertTrue($application->has('nowo:twig-inspector:install'), 'Install command should be registered');
    }

    public function testStatefulServicesAreRegisteredForKernelReset(): void
    {
        self::bootKernel();
        $resetter = self::getContainer()->get('services_resetter');

        $resetMethods = (new ReflectionProperty($resetter, 'resetMethods'))->getValue($resetter);
        $this->assertIsArray($resetMethods);

        foreach ([HtmlCommentsExtension::class, BoxDrawings::class, ControllerRenderSubscriber::class] as $serviceId) {
            $this->assertArrayHasKey($serviceId, $resetMethods, $serviceId . ' should be tagged kernel.reset');
        }
    }

    public function testInstallCommandRunsAndCreatesConfigAndRoutes(): void
    {
        self::bootKernel();
        $projectDir = self::$kernel->getProjectDir();
        $command    = new InstallCommand($projectDir);
        $app        = new ConsoleApplication();
        if (method_exists($app, 'addCommand')) {
            $app->addCommand($command);
        } else {
            $app->add($command);
        }
        $app->setAutoExit(false);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['--force' => true]);

        $this->assertSame(0, $commandTester->getStatusCode());

        $configFile = $projectDir . '/config/packages/dev/nowo_twig_inspector.yaml';
        $routesFile = $projectDir . '/config/routes.yaml';

        $this->assertFileExists($configFile);
        $this->assertFileExists($routesFile);

        $configContent = file_get_contents($configFile);
        $this->assertStringContainsString('nowo_twig_inspector', $configContent);
        $this->assertStringContainsString('enabled_extensions', $configContent);

        $routesContent = file_get_contents($routesFile);
        $this->assertStringContainsString('NowoTwigInspectorBundle', $routesContent);
    }

    public function testOpenTemplateControllerIsCallable(): void
    {
        self::bootKernel();
        $controller = self::getContainer()->get('nowo_twig_inspector.controller.open_template');
        $this->assertInstanceOf(OpenTemplateController::class, $controller);
        $this->assertIsCallable($controller);
    }

    public function testRequestToOpenTemplateRouteReturnsRedirect(): void
    {
        self::bootKernel();
        $kernel  = self::$kernel;
        $request = Request::create('http://localhost/_template/base.html.twig', 'GET');

        $response = $kernel->handle($request, HttpKernelInterface::MAIN_REQUEST, true);

        $this->assertTrue($response->isRedirection(), 'Open template route should redirect to IDE.');
    }

    /**
     * REQ-SF-004: the route comes from #[Route] on the controller, imported through the
     * BC path '@NowoTwigInspectorBundle/Resources/config/routes.yaml'; name, path and options are unchanged.
     */
    public function testOpenTemplateRouteIsDeclaredByAttributeAndKeepsItsContract(): void
    {
        self::bootKernel();
        $router = self::getContainer()->get('router');
        $route  = $router->getRouteCollection()->get('nowo_twig_inspector_template_link');

        $this->assertNotNull($route, 'Route nowo_twig_inspector_template_link must be imported.');
        $this->assertSame('/_template/{template}', $route->getPath());
        $this->assertSame('[^\0]+', $route->getRequirement('template'));
        $this->assertSame(1, $route->getDefault('line'));
        $this->assertTrue($route->getOption('frontend'));
        $this->assertSame(OpenTemplateController::class, $route->getDefault('_controller'));
        $this->assertTrue(self::getContainer()->has(OpenTemplateController::class), 'Controller must be resolvable by FQCN.');
        $this->assertSame(
            '/_template/admin/users/list.html.twig?line=7',
            $router->generate('nowo_twig_inspector_template_link', ['template' => 'admin/users/list.html.twig', 'line' => 7]),
        );
    }
}
