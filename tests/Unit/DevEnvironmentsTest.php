<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Tests\Unit;

use LogicException;
use Nowo\TwigInspectorBundle\DevEnvironments;
use PHPUnit\Framework\TestCase;

/**
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class DevEnvironmentsTest extends TestCase
{
    public function testIsAllowedForDevAndTest(): void
    {
        $this->assertTrue(DevEnvironments::isAllowed('dev'));
        $this->assertTrue(DevEnvironments::isAllowed('test'));
    }

    public function testIsAllowedRejectsProdAndOthers(): void
    {
        $this->assertFalse(DevEnvironments::isAllowed('prod'));
        $this->assertFalse(DevEnvironments::isAllowed('staging'));
    }

    public function testAssertAllowedPassesForDev(): void
    {
        DevEnvironments::assertAllowed('dev');
        $this->assertTrue(true);
    }

    public function testAssertAllowedThrowsForProd(): void
    {
        $this->expectException(LogicException::class);
        DevEnvironments::assertAllowed('prod');
    }
}
