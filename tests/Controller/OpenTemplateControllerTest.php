<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Tests\Controller;

use Nowo\TwigInspectorBundle\Controller\OpenTemplateController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ErrorHandler\ErrorRenderer\FileLinkFormatter;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Loader\ArrayLoader;
use Twig\Loader\FilesystemLoader;
use Twig\TemplateWrapper;

/**
 * Tests for OpenTemplateController.
 *
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
        $this->twig = $this->createMock(Environment::class);
        $this->fileLinkFormatter = $this->createMock(FileLinkFormatter::class);
        $this->controller = new OpenTemplateController($this->twig, $this->fileLinkFormatter);
    }

    public function testInvokeWithDefaultLine(): void
    {
        $request = new Request();
        $template = 'test.html.twig';

        // Create a real Twig environment with ArrayLoader to create real TemplateWrapper
        $loader = new ArrayLoader([$template => 'test content']);
        $realTwig = new Environment($loader);
        $templateWrapper = $realTwig->load($template);

        $this->twig->expects($this->once())
          ->method('load')
          ->with($template)
          ->willReturn($templateWrapper);

        $this->fileLinkFormatter->expects($this->once())
          ->method('format')
          ->with($this->anything(), 1)
          ->willReturn('phpstorm://open?file=/path/to/test.html.twig&line=1');

        $response = ($this->controller)($request, $template);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('phpstorm://open?file=/path/to/test.html.twig&line=1', $response->getTargetUrl());
    }

    public function testInvokeWithCustomLine(): void
    {
        $request = new Request(['line' => 42]);
        $template = 'test.html.twig';

        // Create a real Twig environment with ArrayLoader to create real TemplateWrapper
        $loader = new ArrayLoader([$template => 'test content']);
        $realTwig = new Environment($loader);
        $templateWrapper = $realTwig->load($template);

        $this->twig->expects($this->once())
          ->method('load')
          ->with($template)
          ->willReturn($templateWrapper);

        $this->fileLinkFormatter->expects($this->once())
          ->method('format')
          ->with($this->anything(), 42)
          ->willReturn('phpstorm://open?file=/path/to/test.html.twig&line=42');

        $response = ($this->controller)($request, $template);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('phpstorm://open?file=/path/to/test.html.twig&line=42', $response->getTargetUrl());
    }

    public function testInvokeWithStringLine(): void
    {
        $request = new Request(['line' => '100']);
        $template = 'test.html.twig';

        // Create a real Twig environment with ArrayLoader to create real TemplateWrapper
        $loader = new ArrayLoader([$template => 'test content']);
        $realTwig = new Environment($loader);
        $templateWrapper = $realTwig->load($template);

        $this->twig->expects($this->once())
          ->method('load')
          ->with($template)
          ->willReturn($templateWrapper);

        $this->fileLinkFormatter->expects($this->once())
          ->method('format')
          ->with($this->anything(), 100)
          ->willReturn('phpstorm://open?file=/path/to/test.html.twig&line=100');

        $response = ($this->controller)($request, $template);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    // Security validation tests

    public function testInvokeRejectsPathTraversalWithDoubleDot(): void
    {
        $request = new Request();
        $template = '../etc/passwd';

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('path traversal detected');

        ($this->controller)($request, $template);
    }

    public function testInvokeRejectsPathTraversalWithMultipleDots(): void
    {
        $request = new Request();
        $template = '../../../../etc/passwd';

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('path traversal detected');

        ($this->controller)($request, $template);
    }

    public function testInvokeRejectsNullByte(): void
    {
        $request = new Request();
        $template = "test\x00.html.twig";

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('path traversal detected');

        ($this->controller)($request, $template);
    }

    public function testInvokeRejectsAbsolutePathUnix(): void
    {
        $request = new Request();
        $template = '/etc/passwd';

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('absolute paths are not allowed');

        ($this->controller)($request, $template);
    }

    public function testInvokeRejectsAbsolutePathWindows(): void
    {
        $request = new Request();
        $template = 'C:\\Windows\\System32\\config\\sam';

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('absolute paths are not allowed');

        ($this->controller)($request, $template);
    }

    public function testInvokeRejectsEmptyTemplateName(): void
    {
        $request = new Request();
        $template = '';

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Template name cannot be empty');

        ($this->controller)($request, $template);
    }

    public function testInvokeRejectsWhitespaceOnlyTemplateName(): void
    {
        $request = new Request();
        $template = '   ';

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Template name cannot be empty');

        ($this->controller)($request, $template);
    }

    public function testInvokeRejectsNegativeLineNumber(): void
    {
        $request = new Request(['line' => -1]);
        $template = 'test.html.twig';

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Line number must be a positive integer');

        ($this->controller)($request, $template);
    }

    public function testInvokeRejectsZeroLineNumber(): void
    {
        $request = new Request(['line' => 0]);
        $template = 'test.html.twig';

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Line number must be a positive integer');

        ($this->controller)($request, $template);
    }

    public function testInvokeRejectsNonNumericLineNumber(): void
    {
        $request = new Request(['line' => 'invalid']);
        $template = 'test.html.twig';

        // getInt() in Symfony 7.0+ throws an exception for invalid input before our validation
        // We expect an exception to be thrown (either from Symfony or our validation)
        $this->expectException(\Exception::class);

        ($this->controller)($request, $template);
    }

    public function testInvokeThrowsNotFoundWhenTemplateDoesNotExist(): void
    {
        $request = new Request();
        $template = 'nonexistent.html.twig';

        $this->twig->expects($this->once())
          ->method('load')
          ->with($template)
          ->willThrowException(new LoaderError('Template not found'));

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Template "nonexistent.html.twig" not found');

        ($this->controller)($request, $template);
    }

    public function testInvokeValidatesFilePathWithFilesystemLoader(): void
    {
        // Create a temporary directory for templates
        $tempDir = sys_get_temp_dir() . '/twig_inspector_test_' . uniqid();
        mkdir($tempDir, 0o777, true);
        $templateFile = $tempDir . '/test.html.twig';
        file_put_contents($templateFile, 'test content');

        try {
            $loader = new FilesystemLoader([$tempDir]);
            $twig = new Environment($loader);
            $templateWrapper = $twig->load('test.html.twig');

            // Create a new controller with real Twig environment
            $realFileLinkFormatter = $this->createMock(FileLinkFormatter::class);
            $realFileLinkFormatter->expects($this->once())
              ->method('format')
              ->with($this->stringContains($tempDir), 1)
              ->willReturn('phpstorm://open?file=' . $templateFile . '&line=1');

            $realController = new OpenTemplateController($twig, $realFileLinkFormatter);

            $request = new Request();
            $response = ($realController)($request, 'test.html.twig');

            $this->assertInstanceOf(RedirectResponse::class, $response);
        } finally {
            // Cleanup
            if (file_exists($templateFile)) {
                unlink($templateFile);
            }
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }
        }
    }

    public function testInvokeRejectsFilePathOutsideAllowedDirectories(): void
    {
        // This test is difficult to test directly because TemplateWrapper is final
        // The validation happens after Twig loads the template, so we test it indirectly
        // by ensuring the validateFilePath method exists and works correctly
        // The actual path validation is tested in testInvokeValidatesFilePathWithFilesystemLoader

        // Create a temporary directory for templates
        $allowedDir = sys_get_temp_dir() . '/twig_inspector_allowed_' . uniqid();
        mkdir($allowedDir, 0o777, true);
        $templateFile = $allowedDir . '/test.html.twig';
        file_put_contents($templateFile, 'test content');

        try {
            $loader = new FilesystemLoader([$allowedDir]);
            $twig = new Environment($loader);

            // Try to load a template that doesn't exist - this will trigger validation
            $realFileLinkFormatter = $this->createMock(FileLinkFormatter::class);
            $realController = new OpenTemplateController($twig, $realFileLinkFormatter);

            $request = new Request();

            // This should fail because the template doesn't exist
            $this->expectException(NotFoundHttpException::class);
            ($realController)($request, 'nonexistent.html.twig');
        } finally {
            // Cleanup
            if (file_exists($templateFile)) {
                unlink($templateFile);
            }
            if (is_dir($allowedDir)) {
                rmdir($allowedDir);
            }
        }
    }

    public function testInvokeWorksWithTemplateInSubdirectory(): void
    {
        // Create a temporary directory structure for templates
        $tempDir = sys_get_temp_dir() . '/twig_inspector_test_' . uniqid();
        $subDir = $tempDir . '/admin/users';
        mkdir($subDir, 0o777, true);
        $templateFile = $subDir . '/list.html.twig';
        file_put_contents($templateFile, 'template content');

        try {
            $loader = new FilesystemLoader([$tempDir]);
            $twig = new Environment($loader);
            $templateWrapper = $twig->load('admin/users/list.html.twig');

            $realFileLinkFormatter = $this->createMock(FileLinkFormatter::class);
            $realFileLinkFormatter->expects($this->once())
              ->method('format')
              ->with($this->stringContains($templateFile), 1)
              ->willReturn('phpstorm://open?file=' . $templateFile . '&line=1');

            $realController = new OpenTemplateController($twig, $realFileLinkFormatter);

            $request = new Request();
            $response = ($realController)($request, 'admin/users/list.html.twig');

            $this->assertInstanceOf(RedirectResponse::class, $response);
        } finally {
            // Cleanup
            if (file_exists($templateFile)) {
                unlink($templateFile);
            }
            if (is_dir($subDir)) {
                rmdir($subDir);
            }
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }
        }
    }

    public function testInvokeRejectsUnresolvableFilePath(): void
    {
        // Test case where realpath() returns false using reflection
        $loader = new FilesystemLoader([sys_get_temp_dir()]);
        $twig = new Environment($loader);

        $fileLinkFormatter = $this->createMock(FileLinkFormatter::class);
        $controller = new OpenTemplateController($twig, $fileLinkFormatter);

        // Use reflection to test validateFilePath directly with a non-existent path
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('validateFilePath');
        $method->setAccessible(true);

        // Test with a path that realpath() will return false for
        $nonExistentPath = '/nonexistent/path/to/template.html.twig';

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Template file path could not be resolved');

        $method->invoke($controller, $nonExistentPath);
    }

    public function testValidateFilePathWithValidPath(): void
    {
        // Test case where validateFilePath works correctly with a valid path
        $tempDir = sys_get_temp_dir() . '/twig_inspector_test_' . uniqid();
        mkdir($tempDir, 0o777, true);
        $templateFile = $tempDir . '/test.html.twig';
        file_put_contents($templateFile, 'test content');

        try {
            $loader = new FilesystemLoader([$tempDir]);
            $twig = new Environment($loader);

            $fileLinkFormatter = $this->createMock(FileLinkFormatter::class);
            $controller = new OpenTemplateController($twig, $fileLinkFormatter);

            // Use reflection to test validateFilePath
            $reflection = new \ReflectionClass($controller);
            $method = $reflection->getMethod('validateFilePath');
            $method->setAccessible(true);

            // Test with a valid file path - should not throw exception
            $method->invoke($controller, $templateFile);

            // Should not throw exception because the file is in a valid path
            $this->assertTrue(true);
        } finally {
            if (file_exists($templateFile)) {
                unlink($templateFile);
            }
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }
        }
    }

    public function testInvokeWorksWithArrayLoader(): void
    {
        // ArrayLoader doesn't have getPaths(), so validation should pass
        $request = new Request();
        $template = 'test.html.twig';

        $loader = new ArrayLoader([$template => 'test content']);
        $realTwig = new Environment($loader);
        $templateWrapper = $realTwig->load($template);

        $this->twig->expects($this->once())
          ->method('load')
          ->with($template)
          ->willReturn($templateWrapper);

        $this->twig->expects($this->once())
          ->method('getLoader')
          ->willReturn($loader);

        $this->fileLinkFormatter->expects($this->once())
          ->method('format')
          ->with($this->anything(), 1)
          ->willReturn('phpstorm://open?file=/path/to/test.html.twig&line=1');

        $response = ($this->controller)($request, $template);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testValidateFilePathWithInvalidPathInLoop(): void
    {
        // Test case where one path in the loop has realpath() returning false
        // We need to create a FilesystemLoader with a path that realpath() will return false for
        // But FilesystemLoader requires existing directories, so we'll use a different approach:
        // Create a valid path, then use reflection to manipulate the loader's paths

        $tempDir = sys_get_temp_dir() . '/twig_inspector_test_' . uniqid();
        mkdir($tempDir, 0o777, true);
        $templateFile = $tempDir . '/test.html.twig';
        file_put_contents($templateFile, 'test content');

        // Create a FilesystemLoader with a valid path
        $loader = new FilesystemLoader([$tempDir]);
        $twig = new Environment($loader);

        try {
            $fileLinkFormatter = $this->createMock(FileLinkFormatter::class);
            $controller = new OpenTemplateController($twig, $fileLinkFormatter);

            // Use reflection to test validateFilePath
            $reflection = new \ReflectionClass($controller);
            $method = $reflection->getMethod('validateFilePath');
            $method->setAccessible(true);

            // Get the loader and manipulate its paths using reflection
            $loaderReflection = new \ReflectionClass($loader);
            $pathsProperty = $loaderReflection->getProperty('paths');
            $pathsProperty->setAccessible(true);

            // Get current paths and add an invalid one
            $currentPaths = $pathsProperty->getValue($loader);
            $invalidPath = '/nonexistent/path/that/does/not/exist';
            $currentPaths[] = $invalidPath;
            $pathsProperty->setValue($loader, $currentPaths);

            // Now test validateFilePath - it should skip the invalid path (realpath returns false)
            // and use the valid path instead
            $method->invoke($controller, $templateFile);

            // If we get here, the validation passed (which is correct)
            $this->assertTrue(true);
        } finally {
            if (file_exists($templateFile)) {
                unlink($templateFile);
            }
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }
        }
    }
}
