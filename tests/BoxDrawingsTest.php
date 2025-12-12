<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Tests;

use Nowo\TwigInspectorBundle\BoxDrawings;
use PHPUnit\Framework\TestCase;

/**
 * Tests for BoxDrawings.
 *
 * @package Nowo\TwigInspectorBundle\Tests
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2024 Nowo.tech
 */
final class BoxDrawingsTest extends TestCase
{
  private BoxDrawings $boxDrawings;

  protected function setUp(): void
  {
    $this->boxDrawings = new BoxDrawings();
  }

  public function testGetStartCommentPrefix(): void
  {
    $prefix = $this->boxDrawings->getStartCommentPrefix();

    $this->assertIsString($prefix);
    $this->assertNotEmpty($prefix);
  }

  public function testGetEndCommentPrefix(): void
  {
    $prefix = $this->boxDrawings->getEndCommentPrefix();

    $this->assertIsString($prefix);
    $this->assertNotEmpty($prefix);
  }

  public function testBlockChanged(): void
  {
    $initialStart = $this->boxDrawings->getStartCommentPrefix();
    $initialEnd   = $this->boxDrawings->getEndCommentPrefix();

    $this->boxDrawings->blockChanged(5);

    $newStart = $this->boxDrawings->getStartCommentPrefix();
    $newEnd   = $this->boxDrawings->getEndCommentPrefix();

    // After blockChanged, the prefix should change
    $this->assertNotSame($initialStart, $newStart);
    $this->assertNotSame($initialEnd, $newEnd);
  }

  public function testBlockChangedWithZeroLength(): void
  {
    $this->boxDrawings->blockChanged(0);

    // Should reset charset index
    $prefix = $this->boxDrawings->getStartCommentPrefix();
    $this->assertStringStartsWith('┏', $prefix);
  }

  public function testBlockChangedCyclesThroughCharsets(): void
  {
    $initialPrefix = $this->boxDrawings->getStartCommentPrefix();

    // Change block multiple times to cycle through charsets
    for ($i = 1; $i <= 4; $i++)
    {
      $this->boxDrawings->blockChanged($i);
    }

    // After cycling through all charsets, should reset to first
    $finalPrefix = $this->boxDrawings->getStartCommentPrefix();
    $this->assertStringStartsWith('┏', $finalPrefix);
  }

  public function testBlockChangedWithMaxCharsetIndex(): void
  {
    // Set to last charset index
    for ($i = 0; $i < 3; $i++)
    {
      $this->boxDrawings->blockChanged(1);
    }

    // Next change should reset to 0
    $this->boxDrawings->blockChanged(1);
    $prefix = $this->boxDrawings->getStartCommentPrefix();
    $this->assertStringStartsWith('┏', $prefix);
  }

  public function testGetStartCommentPrefixWithLength(): void
  {
    $this->boxDrawings->blockChanged(5);
    $prefix = $this->boxDrawings->getStartCommentPrefix();

    $this->assertStringStartsWith('╭', $prefix);
    $this->assertGreaterThan(1, strlen($prefix));
  }

  public function testGetEndCommentPrefixWithLength(): void
  {
    $this->boxDrawings->blockChanged(3);
    $prefix = $this->boxDrawings->getEndCommentPrefix();

    $this->assertStringStartsWith('╰', $prefix);
    $this->assertGreaterThan(1, strlen($prefix));
  }
}

