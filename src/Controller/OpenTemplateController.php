<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Controller;

use Symfony\Component\ErrorHandler\ErrorRenderer\FileLinkFormatter;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\TemplateWrapper;

/**
 * Open Twig template in an IDE by template name at the line.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2024 Nowo.tech
 */
class OpenTemplateController
{
    /**
     * Constructor.
     *
     * @param Environment       $twig              The Twig environment
     * @param FileLinkFormatter $fileLinkFormatter The file link formatter
     */
    public function __construct(
        private readonly Environment $twig,
        private readonly FileLinkFormatter $fileLinkFormatter
    ) {
    }

    /**
     * Opens a Twig template in the IDE.
     *
     * @param Request $request  The request object
     * @param string  $template The template name
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     *
     * @return RedirectResponse Redirect response to the IDE
     */
    /**
     * Opens a Twig template in the IDE.
     * Loads the template, gets its file path, and generates an IDE link using the file link formatter.
     *
     * @param Request $request  The request object containing the optional 'line' query parameter
     * @param string  $template The template name to open
     *
     * @throws LoaderError  When the template cannot be loaded
     * @throws RuntimeError When a runtime error occurs
     * @throws SyntaxError  When a syntax error is found in the template
     *
     * @return RedirectResponse Redirect response to the IDE with the file path and line number
     */
    public function __invoke(Request $request, string $template): RedirectResponse
    {
        $line = (int) $request->query->get('line', 1);

        /** @var TemplateWrapper $templateWrapper */
        $templateWrapper = $this->twig->load($template);
        $file = $templateWrapper->getSourceContext()->getPath();

        $url = $this->fileLinkFormatter->format($file, $line);

        return new RedirectResponse($url);
    }
}
