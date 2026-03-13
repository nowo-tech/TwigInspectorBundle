<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Tests\Integration;

use Nowo\TwigInspectorBundle\Command\InstallCommand;
use Nowo\TwigInspectorBundle\Controller\OpenTemplateController;
use Nowo\TwigInspectorBundle\Tests\Kernel\TestKernel;
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

    public function testInstallCommandRunsAndCreatesConfigAndRoutes(): void
    {
        self::bootKernel();
        $projectDir = self::$kernel->getProjectDir();
        $command    = new InstallCommand($projectDir);
        $app        = new ConsoleApplication();
        $app->add($command);
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

    public function testRequestToOpenTemplateRouteReturnsRedirectOrNotFound(): void
    {
        self::bootKernel();
        $kernel  = self::$kernel;
        $request = Request::create('http://localhost/_template/base.html.twig', 'GET');

        $response = $kernel->handle($request, HttpKernelInterface::MAIN_REQUEST, true);

        $this->assertTrue(
            $response->isRedirection() || $response->getStatusCode() === 404,
            'Open template route should redirect to IDE or return 404 for invalid template. Got: ' . $response->getStatusCode(),
        );
    }
}
