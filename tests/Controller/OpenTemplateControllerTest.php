<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Tests\Controller;

use Nowo\TwigInspectorBundle\Controller\OpenTemplateController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ErrorHandler\ErrorRenderer\FileLinkFormatter;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Source;
use Twig\TemplateWrapper;

/**
 * Tests for OpenTemplateController.
 *
 * @package Nowo\TwigInspectorBundle\Tests\Controller
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2024 Nowo.tech
 */
final class OpenTemplateControllerTest extends TestCase
{
  private Environment $twig;
  private FileLinkFormatter $fileLinkFormatter;
  private OpenTemplateController $controller;

  protected function setUp(): void
  {
    $this->twig             = $this->createMock(Environment::class);
    $this->fileLinkFormatter = $this->createMock(FileLinkFormatter::class);
    $this->controller       = new OpenTemplateController($this->twig, $this->fileLinkFormatter);
  }

  public function testInvokeWithDefaultLine(): void
  {
    $request  = new Request();
    $template = 'test.html.twig';

    $source = $this->createMock(Source::class);
    $source->method('getPath')->willReturn('/path/to/test.html.twig');

    $templateWrapper = $this->createMock(TemplateWrapper::class);
    $templateWrapper->method('getSourceContext')->willReturn($source);

    $this->twig->expects($this->once())
      ->method('load')
      ->with($template)
      ->willReturn($templateWrapper);

    $this->fileLinkFormatter->expects($this->once())
      ->method('format')
      ->with('/path/to/test.html.twig', 1)
      ->willReturn('phpstorm://open?file=/path/to/test.html.twig&line=1');

    $response = ($this->controller)($request, $template);

    $this->assertInstanceOf(RedirectResponse::class, $response);
    $this->assertSame('phpstorm://open?file=/path/to/test.html.twig&line=1', $response->getTargetUrl());
  }

  public function testInvokeWithCustomLine(): void
  {
    $request  = new Request(['line' => 42]);
    $template = 'test.html.twig';

    $source = $this->createMock(Source::class);
    $source->method('getPath')->willReturn('/path/to/test.html.twig');

    $templateWrapper = $this->createMock(TemplateWrapper::class);
    $templateWrapper->method('getSourceContext')->willReturn($source);

    $this->twig->expects($this->once())
      ->method('load')
      ->with($template)
      ->willReturn($templateWrapper);

    $this->fileLinkFormatter->expects($this->once())
      ->method('format')
      ->with('/path/to/test.html.twig', 42)
      ->willReturn('phpstorm://open?file=/path/to/test.html.twig&line=42');

    $response = ($this->controller)($request, $template);

    $this->assertInstanceOf(RedirectResponse::class, $response);
    $this->assertSame('phpstorm://open?file=/path/to/test.html.twig&line=42', $response->getTargetUrl());
  }

  public function testInvokeWithStringLine(): void
  {
    $request  = new Request(['line' => '100']);
    $template = 'test.html.twig';

    $source = $this->createMock(Source::class);
    $source->method('getPath')->willReturn('/path/to/test.html.twig');

    $templateWrapper = $this->createMock(TemplateWrapper::class);
    $templateWrapper->method('getSourceContext')->willReturn($source);

    $this->twig->expects($this->once())
      ->method('load')
      ->with($template)
      ->willReturn($templateWrapper);

    $this->fileLinkFormatter->expects($this->once())
      ->method('format')
      ->with('/path/to/test.html.twig', 100)
      ->willReturn('phpstorm://open?file=/path/to/test.html.twig&line=100');

    $response = ($this->controller)($request, $template);

    $this->assertInstanceOf(RedirectResponse::class, $response);
  }
}

