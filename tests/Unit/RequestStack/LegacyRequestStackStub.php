<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Tests\Unit\RequestStack;

use Nowo\TwigInspectorBundle\RequestStack\LegacyRequestStackInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test double that only exposes getCurrentRequest() and getMasterRequest() (no getMainRequest).
 * Used to cover the getMasterRequest() branch in RequestStackMainOrMasterAdapter.
 */
final class LegacyRequestStackStub implements LegacyRequestStackInterface
{
    public function __construct(
        private ?Request $currentRequest = null,
        private ?Request $masterRequest = null
    ) {
    }

    public function getCurrentRequest(): ?Request
    {
        return $this->currentRequest;
    }

    public function getMasterRequest(): ?Request
    {
        return $this->masterRequest;
    }
}
