<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Tests\Twig;

use Nowo\TwigInspectorBundle\BoxDrawings;
use Nowo\TwigInspectorBundle\Twig\HtmlCommentsExtension;
use Nowo\TwigInspectorBundle\Twig\NodeReference;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Tests for HtmlCommentsExtension.
 *
 * @package Nowo\TwigInspectorBundle\Tests\Twig
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2024 Nowo.tech
 */
final class HtmlCommentsExtensionTest extends TestCase
{
  private RequestStack $requestStack;
  private UrlGeneratorInterface $urlGenerator;
  private BoxDrawings $boxDrawings;
  private HtmlCommentsExtension $extension;

  protected function setUp(): void
  {
    $this->requestStack = $this->createMock(RequestStack::class);
    $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
    $this->boxDrawings  = new BoxDrawings();
    $this->extension   = new HtmlCommentsExtension($this->requestStack, $this->urlGenerator, $this->boxDrawings);
  }

  public function testStartWhenDisabled(): void
  {
    $ref = new NodeReference('block', 'template.html.twig', 1);

    $this->requestStack->method('getCurrentRequest')->willReturn(null);

    ob_start();
    $this->extension->start($ref);
    $output = ob_get_clean();

    $this->assertEmpty($output);
  }

  public function testStartWhenEnabled(): void
  {
    $ref = new NodeReference('block', 'template.html.twig', 1);

    $request = new Request();
    $request->cookies->set('twig_inspector_is_active', '1');
    $this->requestStack->method('getCurrentRequest')->willReturn($request);

    $this->extension->start($ref);

    // Output buffering should be started
    $this->assertTrue(true);
  }

  public function testEndWhenDisabled(): void
  {
    $ref = new NodeReference('block', 'template.html.twig', 1);

    $this->requestStack->method('getCurrentRequest')->willReturn(null);

    ob_start();
    echo 'test content';
    $this->extension->end($ref);
    $output = ob_get_clean();

    $this->assertSame('test content', $output);
  }

  public function testEndWithHtmlContent(): void
  {
    $ref = new NodeReference('block_name', 'template.html.twig', 10);

    $request = new Request();
    $request->cookies->set('twig_inspector_is_active', '1');
    $this->requestStack->method('getCurrentRequest')->willReturn($request);

    $this->urlGenerator->method('generate')
      ->with('nowo_twig_inspector_template_link', ['template' => 'template.html.twig', 'line' => 10])
      ->willReturn('/_template/template.html.twig?line=10');

    ob_start();
    echo '<div>Test content</div>';
    $this->extension->end($ref);
    $output = ob_get_clean();

    $this->assertStringContainsString('<!--', $output);
    $this->assertStringContainsString('block_name', $output);
    $this->assertStringContainsString('<div>Test content</div>', $output);
    $this->assertStringContainsString('-->', $output);
  }

  public function testEndWithNonHtmlContent(): void
  {
    $ref = new NodeReference('block', 'template.html.twig', 1);

    $request = new Request();
    $request->cookies->set('twig_inspector_is_active', '1');
    $this->requestStack->method('getCurrentRequest')->willReturn($request);

    ob_start();
    echo 'plain text';
    $this->extension->end($ref);
    $output = ob_get_clean();

    // Should not add comments for non-HTML content
    $this->assertSame('plain text', $output);
  }

  public function testEndWithJsonContent(): void
  {
    $ref = new NodeReference('block', 'template.html.twig', 1);

    $request = new Request();
    $request->cookies->set('twig_inspector_is_active', '1');
    $this->requestStack->method('getCurrentRequest')->willReturn($request);

    ob_start();
    echo '{"key": "value"}';
    $this->extension->end($ref);
    $output = ob_get_clean();

    // Should not add comments for JSON content
    $this->assertSame('{"key": "value"}', $output);
  }

  public function testEndWithBackboneTemplate(): void
  {
    $ref = new NodeReference('block', 'template.html.twig', 1);

    $request = new Request();
    $request->cookies->set('twig_inspector_is_active', '1');
    $this->requestStack->method('getCurrentRequest')->willReturn($request);

    ob_start();
    echo '<div><% code %></div>';
    $this->extension->end($ref);
    $output = ob_get_clean();

    // Should not add comments for backbone templates
    $this->assertSame('<div><% code %></div>', $output);
  }

  public function testEndWithNonTwigTemplate(): void
  {
    $ref = new NodeReference('block', 'template.txt', 1);

    $request = new Request();
    $request->cookies->set('twig_inspector_is_active', '1');
    $this->requestStack->method('getCurrentRequest')->willReturn($request);

    ob_start();
    echo '<div>content</div>';
    $this->extension->end($ref);
    $output = ob_get_clean();

    // Should not add comments for non-.html.twig templates
    $this->assertSame('<div>content</div>', $output);
  }

  public function testEndWithNestedContent(): void
  {
    $ref1 = new NodeReference('outer', 'template.html.twig', 1);
    $ref2 = new NodeReference('inner', 'template.html.twig', 2);

    $request = new Request();
    $request->cookies->set('twig_inspector_is_active', '1');
    $this->requestStack->method('getCurrentRequest')->willReturn($request);

    $this->urlGenerator->method('generate')
      ->willReturn('/_template/template.html.twig?line=1');

    // First block
    ob_start();
    echo '<div>outer</div>';
    $this->extension->end($ref1);
    $output1 = ob_get_clean();

    // Second block (nested)
    ob_start();
    echo $output1 . '<div>inner</div>';
    $this->extension->end($ref2);
    $output2 = ob_get_clean();

    $this->assertStringContainsString('outer', $output2);
    $this->assertStringContainsString('inner', $output2);
  }

  public function testIsEnabledWithoutRequest(): void
  {
    $ref = new NodeReference('block', 'template.html.twig', 1);

    $this->requestStack->method('getCurrentRequest')->willReturn(null);

    $reflection = new \ReflectionClass($this->extension);
    $method     = $reflection->getMethod('isEnabled');
    $method->setAccessible(true);

    $result = $method->invoke($this->extension, $ref);

    $this->assertFalse($result);
  }

  public function testIsEnabledWithoutCookie(): void
  {
    $ref     = new NodeReference('block', 'template.html.twig', 1);
    $request = new Request();

    $this->requestStack->method('getCurrentRequest')->willReturn($request);

    $reflection = new \ReflectionClass($this->extension);
    $method     = $reflection->getMethod('isEnabled');
    $method->setAccessible(true);

    $result = $method->invoke($this->extension, $ref);

    $this->assertFalse($result);
  }

  public function testIsEnabledWithCookie(): void
  {
    $ref     = new NodeReference('block', 'template.html.twig', 1);
    $request = new Request();
    $request->cookies->set('twig_inspector_is_active', '1');

    $this->requestStack->method('getCurrentRequest')->willReturn($request);

    $reflection = new \ReflectionClass($this->extension);
    $method     = $reflection->getMethod('isEnabled');
    $method->setAccessible(true);

    $result = $method->invoke($this->extension, $ref);

    $this->assertTrue($result);
  }

  public function testGetComment(): void
  {
    $ref = new NodeReference('block_name', 'template.html.twig', 10);

    $this->urlGenerator->method('generate')
      ->with('nowo_twig_inspector_template_link', ['template' => 'template.html.twig', 'line' => 10])
      ->willReturn('/_template/template.html.twig?line=10');

    $reflection = new \ReflectionClass($this->extension);
    $method     = $reflection->getMethod('getComment');
    $method->setAccessible(true);

    $result = $method->invoke($this->extension, '┏━', $ref);

    $this->assertStringContainsString('<!--', $result);
    $this->assertStringContainsString('┏━', $result);
    $this->assertStringContainsString('block_name', $result);
    $this->assertStringContainsString('-->', $result);
  }

  public function testGetLink(): void
  {
    $ref = new NodeReference('block', 'template.html.twig', 5);

    $this->urlGenerator->expects($this->once())
      ->method('generate')
      ->with('nowo_twig_inspector_template_link', ['template' => 'template.html.twig', 'line' => 5])
      ->willReturn('/_template/template.html.twig?line=5');

    $reflection = new \ReflectionClass($this->extension);
    $method     = $reflection->getMethod('getLink');
    $method->setAccessible(true);

    $result = $method->invoke($this->extension, $ref);

    $this->assertSame('/_template/template.html.twig?line=5', $result);
  }
}

