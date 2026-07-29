<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\RequestStack;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Adapts RequestStack (or a legacy-style stack) to MainOrMasterRequestProvider.
 * Uses getMainRequest() on Symfony RequestStack; otherwise getMasterRequest() (legacy stub).
 *
 * @internal Used by the bundle DI and tests
 */
final class RequestStackMainOrMasterAdapter implements MainOrMasterRequestProvider
{
    public function __construct(
        private readonly RequestStack|LegacyRequestStackInterface $stack
    ) {
    }

    public function getCurrentRequest(): ?Request
    {
        return $this->stack->getCurrentRequest();
    }

    public function getMainOrMasterRequest(): ?Request
    {
        if ($this->stack instanceof RequestStack) {
            return $this->stack->getMainRequest();
        }

        return $this->stack->getMasterRequest();
    }
}
