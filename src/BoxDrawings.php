<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle;

use function count;

/**
 * Generates box-drawing character prefixes for start and end HTML comment tags.
 * Used to visually distinguish nested Twig blocks in the inspector overlay.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class BoxDrawings
{
    /** @var array<int, array<int, string>> Box-drawing character sets: [start, line, end] per set */
    protected const CHARSETS = [
        ['┏', '━', '┗'],
        ['╭', '─', '╰'],
        ['╔', '═', '╚'],
        ['┎', '─', '┖'],
    ];

    /** @var int Current character set index (0–3) */
    private int $charsetIndex = 0;

    /** @var int Repeat count for the line character between start/end */
    private int $length = 0;

    /**
     * Gets the start comment prefix.
     *
     * @return string The start comment prefix
     */
    public function getStartCommentPrefix(): string
    {
        $prefix = $this->getCharset()[0];

        return $prefix . str_repeat((string) $this->getCharset()[1], $this->length);
    }

    /**
     * Gets the end comment prefix.
     *
     * @return string The end comment prefix
     */
    public function getEndCommentPrefix(): string
    {
        $prefix = $this->getCharset()[2];

        return $prefix . str_repeat((string) $this->getCharset()[1], $this->length);
    }

    /**
     * Handles block changes and updates charset index.
     * Cycles through different box drawing character sets to visually distinguish nested blocks.
     * Resets to the first charset when reaching the last one or when length is zero.
     *
     * @param int $length The length of the block
     */
    public function blockChanged(int $length): void
    {
        $this->length = $length;
        ++$this->charsetIndex;

        // Reset to first charset if we've reached the last one or length is zero
        if ($length === 0 || count(self::CHARSETS) - 1 === $this->charsetIndex) {
            $this->charsetIndex = 0;
        }
    }

    /**
     * Gets the current charset.
     *
     * @return array<int, string> The charset array
     */
    private function getCharset(): array
    {
        return self::CHARSETS[$this->charsetIndex];
    }
}
