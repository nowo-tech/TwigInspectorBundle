<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\RequestStack;

use Symfony\Component\HttpFoundation\Request;

/**
 * Minimal request stack contract (getCurrentRequest + getMasterRequest).
 * Used so the main-or-master adapter can accept either Symfony RequestStack or a test double
 * that only exposes getMasterRequest() (Symfony 5.x style), without getMainRequest().
 *
 * @internal Used for compatibility and testing
 */
interface LegacyRequestStackInterface
{
    public function getCurrentRequest(): ?Request;

    public function getMasterRequest(): ?Request;
}
