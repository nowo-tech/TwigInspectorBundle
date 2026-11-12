<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Tests\Kernel;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * Host controller of the kept-alive test: renders a page whose marker comes from the request.
 */
final class HostPageController
{
    public function __construct(private readonly Environment $twig)
    {
    }

    public function __invoke(Request $request): Response
    {
        return new Response($this->twig->render('worker_page.html.twig', ['marker' => $request->query->getString('m')]));
    }
}
