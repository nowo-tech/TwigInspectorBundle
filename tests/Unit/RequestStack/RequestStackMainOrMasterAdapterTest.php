<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Tests\Unit\RequestStack;

use Nowo\TwigInspectorBundle\RequestStack\RequestStackMainOrMasterAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests for RequestStackMainOrMasterAdapter (main vs master request branch).
 */
final class RequestStackMainOrMasterAdapterTest extends TestCase
{
    /**
     * When the wrapped stack has getMainRequest() (Symfony 6+), getMainOrMasterRequest() returns getMainRequest().
     */
    public function testGetMainOrMasterRequestUsesGetMainRequestWhenPresent(): void
    {
        $mainRequest = new Request();
        $stack       = new RequestStack();
        $stack->push($mainRequest);

        $adapter = new RequestStackMainOrMasterAdapter($stack);

        $this->assertSame($mainRequest, $adapter->getMainOrMasterRequest());
        $this->assertSame($mainRequest, $adapter->getCurrentRequest());
    }

    /**
     * When the wrapped stack does not have getMainRequest() (legacy), getMainOrMasterRequest() returns getMasterRequest().
     */
    public function testGetMainOrMasterRequestUsesGetMasterRequestWhenGetMainRequestNotPresent(): void
    {
        $masterRequest = new Request();
        $stub          = new LegacyRequestStackStub($masterRequest, $masterRequest);

        $adapter = new RequestStackMainOrMasterAdapter($stub);

        $this->assertSame($masterRequest, $adapter->getMainOrMasterRequest());
        $this->assertSame($masterRequest, $adapter->getCurrentRequest());
    }

    /**
     * Legacy stub has no getMainRequest method, so the adapter uses getMasterRequest.
     */
    public function testLegacyStubHasNoGetMainRequest(): void
    {
        $stub = new LegacyRequestStackStub(null, null);
        $this->assertFalse(method_exists($stub, 'getMainRequest'));
        $this->assertTrue(method_exists($stub, 'getMasterRequest'));
    }
}
