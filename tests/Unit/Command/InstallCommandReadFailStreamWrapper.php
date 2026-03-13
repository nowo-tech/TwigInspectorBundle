<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Tests\Unit\Command;

use function in_array;

/**
 * Stream wrapper used in tests to make file_get_contents() return false
 * while file_exists()/is_file() still return true (url_stat reports a regular file).
 *
 * Register with: stream_wrapper_register('installreadfail', self::class);
 * Use path: installreadfail://dummy
 */
final class InstallCommandReadFailStreamWrapper
{
    private const PROTOCOL = 'installreadfail';

    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        // Fail open for read so file_get_contents() returns false
        return false;
    }

    /**
     * @return array{0: int, 1: int, 2: int, 3: int, 4: int, 5: int, 6: int, 7: int, 8: int, 9: int, 10: int, 11: int, 12: int}
     */
    public function url_stat(string $path, int $flags): array
    {
        // Report a regular file so Filesystem::exists() / is_file() returns true
        $mode = 0100644;

        return [
            0  => $mode,
            1  => 0,
            2  => $mode,
            3  => 0,
            4  => 0,
            5  => 0,
            6  => 0,
            7  => 0,
            8  => 0,
            9  => 0,
            10 => 0,
            11 => 0,
            12 => 0,
        ];
    }

    public static function register(): bool
    {
        if (in_array(self::PROTOCOL, stream_get_wrappers(), true)) {
            return true;
        }

        return stream_wrapper_register(self::PROTOCOL, self::class);
    }

    public static function unregister(): bool
    {
        if (!in_array(self::PROTOCOL, stream_get_wrappers(), true)) {
            return true;
        }

        return stream_wrapper_unregister(self::PROTOCOL);
    }
}
