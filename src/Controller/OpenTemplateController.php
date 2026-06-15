<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Controller;

use RuntimeException;
use Symfony\Component\ErrorHandler\ErrorRenderer\FileLinkFormatter;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;

use function in_array;
use function is_string;
use function sprintf;

/**
 * Controller that redirects to an IDE file link for a given Twig template and line.
 * Used by the inspector overlay when clicking "open in IDE".
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class OpenTemplateController
{
    /** @var list<string> Environments where the "open in IDE" route is allowed (dev, test). In prod, returns 404. */
    private const ALLOWED_ENVIRONMENTS = ['dev', 'test'];

    /**
     * Constructor.
     *
     * @param Environment $twig The Twig environment (to resolve template path)
     * @param FileLinkFormatter $fileLinkFormatter Formats file path and line into an IDE URL
     * @param string $environment Kernel environment (e.g. dev, prod, test). Route returns 404 in prod.
     */
    public function __construct(
        private readonly Environment $twig,
        private readonly FileLinkFormatter $fileLinkFormatter,
        private readonly string $environment = 'dev'
    ) {
    }

    /**
     * Opens a Twig template in the IDE.
     * Loads the template, gets its file path, and generates an IDE link using the file link formatter.
     *
     * @param Request $request The request object containing the optional 'line' query parameter
     * @param string $template The template name to open
     *
     * @throws BadRequestException When the template name is invalid or contains path traversal
     * @throws NotFoundHttpException When the template cannot be found
     * @throws LoaderError When the template cannot be loaded
     * @throws RuntimeError When a runtime error occurs
     * @throws SyntaxError When a syntax error is found in the template
     *
     * @return RedirectResponse Redirect response to the IDE with the file path and line number
     */
    public function __invoke(Request $request, string $template): RedirectResponse
    {
        // Restrict to dev/test: in prod, return 404 even if routes were accidentally enabled
        if (!in_array($this->environment, self::ALLOWED_ENVIRONMENTS, true)) {
            throw new NotFoundHttpException();
        }

        // Security: Validate template name to prevent path traversal attacks
        $this->validateTemplateName($template);

        // Validate and sanitize line number
        $line = $request->query->getInt('line', 1);
        if ($line < 1) {
            throw new BadRequestException('Line number must be a positive integer.');
        }

        try {
            $templateWrapper = $this->twig->load($template);
            $file            = $templateWrapper->getSourceContext()->getPath();

            // Additional security: Verify the resolved file path is within allowed directories
            $this->validateFilePath($file);
        } catch (LoaderError $e) {
            throw new NotFoundHttpException(sprintf('Template "%s" not found.', $template), $e);
        }

        $url = $this->fileLinkFormatter->format($file, $line);
        if ($url === false || $url === '') {
            throw new RuntimeException('Could not generate file link.');
        }

        return new RedirectResponse($url);
    }

    /**
     * Validates the template name: non-empty, no path traversal (.. or NUL), no absolute paths.
     *
     * @param string $template Template name (e.g. @App/demo/home.html.twig or demo/home.html.twig)
     *
     * @throws BadRequestException When the template name is empty or contains invalid characters
     */
    private function validateTemplateName(string $template): void
    {
        // Reject empty template names
        if (trim($template) === '') {
            throw new BadRequestException('Template name cannot be empty.');
        }

        // Reject path traversal attempts
        if (str_contains($template, '..') || str_contains($template, "\0")) {
            throw new BadRequestException('Invalid template name: path traversal detected.');
        }

        // Reject absolute paths
        if (str_starts_with($template, '/') || preg_match('/^[a-zA-Z]:\\\\/', $template)) {
            throw new BadRequestException('Invalid template name: absolute paths are not allowed.');
        }
    }

    /**
     * Validates that the resolved file path lies inside one of the Twig loader paths.
     *
     * @param string $filePath Absolute path to the template file
     *
     * @throws BadRequestException When the path cannot be resolved or is outside allowed directories
     */
    private function validateFilePath(string $filePath): void
    {
        // Normalize the file path first
        $realFilePath = realpath($filePath);
        if ($realFilePath === false) {
            throw new BadRequestException('Template file path could not be resolved.');
        }

        // Get all Twig template paths from the loader (supports ChainLoader and FilesystemLoader)
        $loader = $this->twig->getLoader();
        $paths  = $this->collectFilesystemPaths($loader);

        if ($paths === []) {
            // No FilesystemLoader (e.g. ArrayLoader): rely on Twig's own security
            return;
        }

        // Check if the file is within any of the allowed Twig paths
        $isValid = false;
        foreach ($paths as $path) {
            $realPath = realpath($path);
            if ($realPath !== false && str_starts_with($realFilePath, $realPath)) {
                $isValid = true;
                break;
            }
        }

        if (!$isValid) {
            throw new BadRequestException('Template file is outside allowed Twig template directories.');
        }
    }

    /**
     * Collects all filesystem paths from the loader (ChainLoader, FilesystemLoader, or nested).
     *
     * @param LoaderInterface $loader The Twig loader
     *
     * @return list<string> Flat list of absolute or relative paths where templates may reside
     */
    private function collectFilesystemPaths(LoaderInterface $loader): array
    {
        $paths = [];

        if ($loader instanceof ChainLoader) {
            foreach ($loader->getLoaders() as $child) {
                $paths = array_merge($paths, $this->collectFilesystemPaths($child));
            }

            return $paths;
        }

        if ($loader instanceof FilesystemLoader) {
            foreach ($loader->getNamespaces() as $namespace) {
                if (!is_string($namespace)) {
                    continue;
                }
                $paths = array_merge($paths, $loader->getPaths($namespace));
            }
        }

        return $paths;
    }
}
