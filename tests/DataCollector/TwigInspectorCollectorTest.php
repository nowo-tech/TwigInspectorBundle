<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Tests\DataCollector;

use Nowo\TwigInspectorBundle\DataCollector\TwigInspectorCollector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * Tests for TwigInspectorCollector.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
final class TwigInspectorCollectorTest extends TestCase
{
    private RequestStack $requestStack;
    private TwigInspectorCollector $collector;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $twig = $this->createMock(Environment::class);
        $twig->method('hasExtension')->willReturn(false);
        $this->collector = new TwigInspectorCollector(
            $this->requestStack,
            $twig,
            'twig_inspector_is_active',
            true,
            'light',
            false,
            false,
            'Ctrl+Shift+T'
        );
    }

    public function testCollect(): void
    {
        $request = new Request();
        $response = new Response();

        // Should not throw any exception
        $this->collector->collect($request, $response);

        $this->assertTrue(true);
    }

    public function testCollectWithException(): void
    {
        $request = new Request();
        $response = new Response();
        $exception = new \Exception('Test exception');

        // Should not throw any exception
        $this->collector->collect($request, $response, $exception);

        $this->assertTrue(true);
    }

    public function testReset(): void
    {
        // Should not throw any exception
        $this->collector->reset();

        $data = $this->collector->getData();
        $this->assertEmpty($data['templates']);
        $this->assertEmpty($data['blocks']);
        $this->assertSame(0, $this->collector->getTotalTemplates());
        $this->assertSame(0, $this->collector->getTotalBlocks());
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
        $content = '<!-- ┏━ template1.html.twig [/_template/template1.html.twig?line=1] #id1-->';
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
        $request = new Request();
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
        $request = new Request();
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
            $this->requestStack,
            $this->createMock(Environment::class),
            'twig_inspector_is_active',
            false, // enableMetrics = false
            'light',
            false,
            false,
            'Ctrl+Shift+T'
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
        $request = new Request();
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
        $this->collector->__wakeup();
        $this->collector->lateCollect();
        $this->assertSame([], $this->collector->getTemplateTimes());
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
        $restored = unserialize($serialized);
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
}
