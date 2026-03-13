<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Tests\Unit\DataCollector;

use Exception;
use Nowo\TwigInspectorBundle\DataCollector\TwigInspectorCollector;
use Nowo\TwigInspectorBundle\EventSubscriber\ControllerRenderSubscriber;
use Nowo\TwigInspectorBundle\RequestStack\RequestStackMainOrMasterAdapter;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Twig\Environment;
use Twig\Extension\ExtensionInterface;
use Twig\Extension\ProfilerExtension;
use Twig\Profiler\Profile;

/**
 * Tests for TwigInspectorCollector.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class TwigInspectorCollectorTest extends TestCase
{
    private RequestStack $requestStack;
    private TwigInspectorCollector $collector;

    private function createControllerSubscriber(): ControllerRenderSubscriber
    {
        return new ControllerRenderSubscriber(new RequestStackMainOrMasterAdapter($this->requestStack));
    }

    /**
     * Creates a real Profile (Twig\Profiler\Profile is final) with the given template name and duration in seconds.
     */
    private function createProfileWithDuration(string $templateName, float $durationSeconds): Profile
    {
        $profile = new Profile($templateName, Profile::TEMPLATE, $templateName);
        $profile->enter();
        $ref        = new ReflectionClass($profile);
        $startsProp = $ref->getProperty('starts');
        $startsProp->setAccessible(true);
        $endsProp = $ref->getProperty('ends');
        $endsProp->setAccessible(true);
        $starts = $startsProp->getValue($profile);
        $endsProp->setValue($profile, ['wt' => $starts['wt'] + $durationSeconds]);

        return $profile;
    }

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $twig               = $this->createMock(Environment::class);
        $twig->method('hasExtension')->willReturn(false);
        $this->collector = new TwigInspectorCollector(
            $this->createControllerSubscriber(),
            $twig,
            'twig_inspector_is_active',
            true,
            'light',
            false,
            false,
            'Ctrl+Shift+T',
        );
    }

    public function testCollect(): void
    {
        $request  = new Request();
        $response = new Response();

        $this->collector->collect($request, $response);

        $data = $this->collector->getData();
        $this->assertArrayHasKey('enabled', $data);
        $this->assertArrayHasKey('templates', $data);
        $this->assertArrayHasKey('controllers', $data);
        $this->assertArrayHasKey('total_controllers', $data);
        $this->assertFalse($data['enabled']);
    }

    public function testCollectWithException(): void
    {
        $request   = new Request();
        $response  = new Response();
        $exception = new Exception('Test exception');

        $this->collector->collect($request, $response, $exception);

        $data = $this->collector->getData();
        $this->assertArrayHasKey('enabled', $data);
        $this->assertArrayHasKey('config', $data);
    }

    public function testReset(): void
    {
        // Should not throw any exception
        $this->collector->reset();

        $data = $this->collector->getData();
        $this->assertEmpty($data['templates']);
        $this->assertEmpty($data['blocks']);
        $this->assertEmpty($data['controllers']);
        $this->assertSame(0, $this->collector->getTotalTemplates());
        $this->assertSame(0, $this->collector->getTotalBlocks());
        $this->assertSame(0, $this->collector->getTotalControllers());
    }

    public function testGetName(): void
    {
        $this->assertSame('twig_inspector', $this->collector->getName());
    }

    public function testGetTotalTemplates(): void
    {
        $this->assertSame(0, $this->collector->getTotalTemplates());
    }

    public function testGetTotalBlocks(): void
    {
        $this->assertSame(0, $this->collector->getTotalBlocks());
    }

    public function testGetControllersAndTotalControllers(): void
    {
        $this->assertSame([], $this->collector->getControllers());
        $this->assertSame(0, $this->collector->getTotalControllers());

        $subscriber  = new ControllerRenderSubscriber(new RequestStackMainOrMasterAdapter($this->requestStack));
        $mainRequest = new Request();
        $subRequest  = new Request();
        $this->requestStack->push($mainRequest);
        $this->requestStack->push($subRequest);

        $kernel         = $this->createMock(HttpKernelInterface::class);
        $mainController = [new class {
            public function home(): void
            {
            }
        }, 'home'];
        $subController = [new class {
            public function __invoke(): void
            {
            }
        }, '__invoke'];

        $subscriber->onController(new ControllerEvent($kernel, $mainController, $mainRequest, HttpKernelInterface::MAIN_REQUEST));
        $subscriber->onController(new ControllerEvent($kernel, $subController, $subRequest, HttpKernelInterface::SUB_REQUEST));
        $subscriber->onController(new ControllerEvent($kernel, $subController, $subRequest, HttpKernelInterface::SUB_REQUEST));

        $collector = new TwigInspectorCollector(
            $subscriber,
            $this->createMock(Environment::class),
            'twig_inspector_is_active',
            true,
            'light',
            false,
            false,
            'Ctrl+Shift+T',
        );
        $collector->collect($mainRequest, new Response());

        $controllers = $collector->getControllers();
        $this->assertCount(2, $controllers);
        $this->assertSame(2, $collector->getTotalControllers());
        $mainByName = null;
        $subByName  = null;
        foreach ($controllers as $c) {
            if ($c['is_main']) {
                $mainByName = $c;
            } else {
                $subByName = $c;
            }
        }
        $this->assertNotNull($mainByName, 'One controller should be main');
        $this->assertSame(1, $mainByName['count']);
        $this->assertStringEndsWith('::home', $mainByName['name']);
        $this->assertNotNull($subByName, 'One controller should be sub');
        $this->assertSame(2, $subByName['count']);
        $this->assertStringEndsWith('::__invoke', $subByName['name']);
    }

    public function testCollectWithTemplateComments(): void
    {
        $request = new Request();
        $request->cookies->set('twig_inspector_is_active', '1'); // matches collector cookie_name
        $response = new Response();
        $response->setContent('<!-- ┏━ template1.html.twig [/_template/template1.html.twig?line=1] #id1-->');

        $this->collector->collect($request, $response);

        $templates = $this->collector->getTemplates();
        $this->assertCount(1, $templates);
        $this->assertSame('template1.html.twig', $templates[0]['name']);
        $this->assertSame(1, $templates[0]['count']);
        $this->assertSame(1, $this->collector->getTotalTemplates());
    }

    public function testCollectWithBlockComments(): void
    {
        $request = new Request();
        $request->cookies->set('twig_inspector_is_active', '1'); // matches collector cookie_name
        $response = new Response();
        $response->setContent('<!-- ┏━ block1 [/_template/template1.html.twig?line=1] #id1-->');

        $this->collector->collect($request, $response);

        $blocks = $this->collector->getBlocks();
        $this->assertCount(1, $blocks);
        $this->assertSame('template1.html.twig', $blocks[0]['template']);
        $this->assertSame('block1', $blocks[0]['name']);
        $this->assertSame(1, $blocks[0]['count']);
        $this->assertSame(1, $this->collector->getTotalBlocks());
    }

    public function testCollectWithMultipleTemplates(): void
    {
        $request = new Request();
        $request->cookies->set('twig_inspector_is_active', '1'); // matches collector cookie_name
        $response = new Response();
        $content  = '<!-- ┏━ template1.html.twig [/_template/template1.html.twig?line=1] #id1-->';
        $content .= '<!-- ┏━ template2.html.twig [/_template/template2.html.twig?line=1] #id2-->';
        $response->setContent($content);

        $this->collector->collect($request, $response);

        $this->assertSame(2, $this->collector->getTotalTemplates());
    }

    public function testIsEnabledWithCookie(): void
    {
        $request = new Request();
        $request->cookies->set('twig_inspector_is_active', '1'); // matches collector cookie_name
        $response = new Response();

        $this->collector->collect($request, $response);

        $this->assertTrue($this->collector->isEnabled());
    }

    public function testIsEnabledWithoutCookie(): void
    {
        $request  = new Request();
        $response = new Response();

        $this->collector->collect($request, $response);

        $this->assertFalse($this->collector->isEnabled());
    }

    public function testCollectWithEmptyContent(): void
    {
        $request = new Request();
        $request->cookies->set('twig_inspector_is_active', '1'); // matches collector cookie_name
        $response = new Response();
        $response->setContent('');

        $this->collector->collect($request, $response);

        $this->assertSame(0, $this->collector->getTotalTemplates());
        $this->assertSame(0, $this->collector->getTotalBlocks());
    }

    public function testCollectWithFalseContent(): void
    {
        $request = new Request();
        $request->cookies->set('twig_inspector_is_active', '1'); // matches collector cookie_name
        $response = $this->createMock(Response::class);
        $response->method('getContent')->willReturn(false);

        $this->collector->collect($request, $response);

        $this->assertSame(0, $this->collector->getTotalTemplates());
        $this->assertSame(0, $this->collector->getTotalBlocks());
    }

    public function testGetData(): void
    {
        // Test that getData() returns the expected structure
        $data = $this->collector->getData();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('templates', $data);
        $this->assertArrayHasKey('blocks', $data);
        $this->assertArrayHasKey('total_templates', $data);
        $this->assertArrayHasKey('total_blocks', $data);
        $this->assertArrayHasKey('enabled', $data);
        $this->assertArrayHasKey('template_times', $data);
        $this->assertArrayHasKey('config', $data);

        // Verify initial state
        $this->assertIsArray($data['templates']);
        $this->assertIsArray($data['blocks']);
        $this->assertIsInt($data['total_templates']);
        $this->assertIsInt($data['total_blocks']);
        $this->assertIsBool($data['enabled']);
        $this->assertIsArray($data['template_times']);
        $this->assertIsArray($data['config']);
    }

    public function testGetTemplateTimes(): void
    {
        $this->assertSame([], $this->collector->getTemplateTimes());
    }

    public function testGetConfig(): void
    {
        $request  = new Request();
        $response = new Response();
        $this->collector->collect($request, $response);
        $config = $this->collector->getConfig();
        $this->assertIsArray($config);
        $this->assertArrayHasKey('overlay_theme', $config);
        $this->assertArrayHasKey('keyboard_shortcut', $config);
        $this->assertArrayHasKey('cookie_name', $config);
        $this->assertArrayHasKey('overlay_compact', $config);
        $this->assertArrayHasKey('reduced_motion', $config);
        $this->assertSame('Ctrl+Shift+T', $config['keyboard_shortcut']);
        $this->assertSame('light', $config['overlay_theme']);
    }

    public function testLateCollectWhenMetricsDisabled(): void
    {
        $collector = new TwigInspectorCollector(
            $this->createControllerSubscriber(),
            $this->createMock(Environment::class),
            'twig_inspector_is_active',
            false, // enableMetrics = false
            'light',
            false,
            false,
            'Ctrl+Shift+T',
        );
        $request = new Request();
        $request->cookies->set('twig_inspector_is_active', '1');
        $response = new Response();
        $collector->collect($request, $response);
        $collector->lateCollect();
        $this->assertSame([], $collector->getTemplateTimes());
    }

    public function testLateCollectWhenNotEnabled(): void
    {
        $request  = new Request();
        $response = new Response();
        $this->collector->collect($request, $response);
        $this->collector->lateCollect();
        $this->assertSame([], $this->collector->getTemplateTimes());
    }

    public function testLateCollectWhenTwigIsNull(): void
    {
        $request = new Request();
        $request->cookies->set('twig_inspector_is_active', '1');
        $response = new Response();
        $this->collector->collect($request, $response);
        // After unserialize, twig is null (simulate profiler storage)
        $collector = unserialize(serialize($this->collector));
        $collector->lateCollect();
        $this->assertSame([], $collector->getTemplateTimes());
    }

    public function testSleepAndWakeup(): void
    {
        $request = new Request();
        $request->cookies->set('twig_inspector_is_active', '1');
        $response = new Response();
        $response->setContent('<!-- ┏━ t [/_template/t.html.twig?line=1] #id-->');
        $this->collector->collect($request, $response);
        $dataBefore = $this->collector->getData();
        $serialized = serialize($this->collector);
        $restored   = unserialize($serialized);
        $this->assertInstanceOf(TwigInspectorCollector::class, $restored);
        $dataAfter = $restored->getData();
        $this->assertSame($dataBefore['templates'], $dataAfter['templates']);
        $this->assertSame($dataBefore['total_templates'], $dataAfter['total_templates']);
        $this->assertSame([], $restored->getTemplateTimes());
    }

    public function testCollectWithMultipleBlocksSameTemplate(): void
    {
        $request = new Request();
        $request->cookies->set('twig_inspector_is_active', '1');
        $response = new Response();
        // Use ASCII prefix so regex matches on all CI environments (no Unicode encoding issues)
        $content = '<!-- x base [/_template/base.html.twig?line=1] #a-->';
        $content .= '<!-- x content [/_template/base.html.twig?line=2] #b-->';
        $content .= '<!-- x sidebar [/_template/base.html.twig?line=3] #c-->';
        $response->setContent($content);
        $this->collector->collect($request, $response);
        $this->assertSame(1, $this->collector->getTotalTemplates());
        $this->assertSame(2, $this->collector->getTotalBlocks());
    }

    public function testLateCollectPopulatesTemplateTimesWhenProfilerExtensionPresent(): void
    {
        $profile   = $this->createProfileWithDuration('demo/page.html.twig', 0.002);
        $extension = new ProfilerExtension($profile);

        $twig = $this->createMock(Environment::class);
        $twig->method('hasExtension')->willReturnMap([
            [\Symfony\Bridge\Twig\Extension\ProfilerExtension::class, false],
            [ProfilerExtension::class, true],
        ]);
        $twig->method('getExtension')->with(ProfilerExtension::class)->willReturn($extension);

        $collector = new TwigInspectorCollector(
            $this->createControllerSubscriber(),
            $twig,
            'twig_inspector_is_active',
            true,
            'light',
            false,
            false,
            'Ctrl+Shift+T',
        );
        $request = new Request();
        $request->cookies->set('twig_inspector_is_active', '1');
        $response = new Response();
        $collector->collect($request, $response);
        $collector->lateCollect();

        $times = $collector->getTemplateTimes();
        $this->assertArrayHasKey('demo/page.html.twig', $times);
        $this->assertEqualsWithDelta(2.0, $times['demo/page.html.twig'], 0.01, 'Template duration in ms (float precision)');
    }

    public function testLateCollectUsesSymfonyProfilerExtensionWhenPresent(): void
    {
        if (!class_exists(\Symfony\Bridge\Twig\Extension\ProfilerExtension::class)) {
            $this->markTestSkipped('Symfony Twig Bridge ProfilerExtension not available');
        }

        $profile   = $this->createProfileWithDuration('symfony_template.html.twig', 0.003);
        $extension = new ProfilerExtension($profile);

        $twig                 = $this->createMock(Environment::class);
        $symfonyProfilerClass = \Symfony\Bridge\Twig\Extension\ProfilerExtension::class;
        $twig->method('hasExtension')->willReturnMap([
            [$symfonyProfilerClass, true],
            [ProfilerExtension::class, false],
        ]);
        $twig->method('getExtension')->willReturnMap([
            [$symfonyProfilerClass, $extension],
            [ProfilerExtension::class, $extension],
        ]);

        $collector = new TwigInspectorCollector(
            $this->createControllerSubscriber(),
            $twig,
            'twig_inspector_is_active',
            true,
            'light',
            false,
            false,
            'Ctrl+Shift+T',
        );
        $request = new Request();
        $request->cookies->set('twig_inspector_is_active', '1');
        $response = new Response();
        $collector->collect($request, $response);
        $collector->lateCollect();

        $times = $collector->getTemplateTimes();
        $this->assertArrayHasKey('symfony_template.html.twig', $times);
        $this->assertEqualsWithDelta(3.0, $times['symfony_template.html.twig'], 0.01, 'Template duration in ms (float precision)');
    }

    public function testLateCollectReturnsEmptyWhenExtensionNotProfilerExtension(): void
    {
        $twig                 = $this->createMock(Environment::class);
        $symfonyProfilerClass = \Symfony\Bridge\Twig\Extension\ProfilerExtension::class;
        $twig->method('hasExtension')->willReturnMap([
            [$symfonyProfilerClass, false],
            [ProfilerExtension::class, true],
        ]);
        $twig->method('getExtension')->with(ProfilerExtension::class)->willReturn($this->createMock(ExtensionInterface::class));

        $collector = new TwigInspectorCollector(
            $this->createControllerSubscriber(),
            $twig,
            'twig_inspector_is_active',
            true,
            'light',
            false,
            false,
            'Ctrl+Shift+T',
        );
        $request = new Request();
        $request->cookies->set('twig_inspector_is_active', '1');
        $response = new Response();
        $collector->collect($request, $response);
        $collector->lateCollect();

        $this->assertSame([], $collector->getTemplateTimes());
    }

    public function testLateCollectReturnsEmptyWhenActivesEmpty(): void
    {
        $extension = $this->createMock(ProfilerExtension::class);
        $ref       = new ReflectionProperty(ProfilerExtension::class, 'actives');
        $ref->setAccessible(true);
        $ref->setValue($extension, []);

        $twig = $this->createMock(Environment::class);
        $twig->method('hasExtension')->willReturnMap([
            [\Symfony\Bridge\Twig\Extension\ProfilerExtension::class, false],
            [ProfilerExtension::class, true],
        ]);
        $twig->method('getExtension')->with(ProfilerExtension::class)->willReturn($extension);

        $collector = new TwigInspectorCollector(
            $this->createControllerSubscriber(),
            $twig,
            'twig_inspector_is_active',
            true,
            'light',
            false,
            false,
            'Ctrl+Shift+T',
        );
        $request = new Request();
        $request->cookies->set('twig_inspector_is_active', '1');
        $response = new Response();
        $collector->collect($request, $response);
        $collector->lateCollect();

        $this->assertSame([], $collector->getTemplateTimes());
    }

    public function testLateCollectAggregatesProfileWithChildren(): void
    {
        $childProfile  = $this->createProfileWithDuration('child.html.twig', 0.001);
        $parentProfile = new Profile('main', Profile::ROOT, 'main');
        $parentProfile->addProfile($childProfile);
        $parentProfile->enter();

        $extension = new ProfilerExtension($parentProfile);

        $twig = $this->createMock(Environment::class);
        $twig->method('hasExtension')->willReturnMap([
            [\Symfony\Bridge\Twig\Extension\ProfilerExtension::class, false],
            [ProfilerExtension::class, true],
        ]);
        $twig->method('getExtension')->with(ProfilerExtension::class)->willReturn($extension);

        $collector = new TwigInspectorCollector(
            $this->createControllerSubscriber(),
            $twig,
            'twig_inspector_is_active',
            true,
            'light',
            false,
            false,
            'Ctrl+Shift+T',
        );
        $request = new Request();
        $request->cookies->set('twig_inspector_is_active', '1');
        $response = new Response();
        $collector->collect($request, $response);
        $collector->lateCollect();

        $times = $collector->getTemplateTimes();
        $this->assertArrayHasKey('child.html.twig', $times);
        $this->assertEqualsWithDelta(1.0, $times['child.html.twig'], 0.01, 'Template duration in ms (float precision)');
    }
}
