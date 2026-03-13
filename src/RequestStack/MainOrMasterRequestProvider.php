<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\RequestStack;

use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the current request and the "main" (or legacy "master") request.
 * Used by subscribers so they can depend on this abstraction instead of RequestStack directly,
 * allowing tests to cover the getMasterRequest() branch when getMainRequest() is not present.
 */
interface MainOrMasterRequestProvider
{
    public function getCurrentRequest(): ?Request;

    /**
     * Returns the main request (Symfony 6+) or master request (Symfony 5.x).
     */
    public function getMainOrMasterRequest(): ?Request;
}
