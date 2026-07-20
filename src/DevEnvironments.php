<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle;

use LogicException;

use function in_array;
use function sprintf;

/**
 * Environments where Twig Inspector is allowed (dev/test only).
 * Production and other envs must never enable the bundle (WebProfiler + info disclosure).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class DevEnvironments
{
    /** @var list<string> */
    public const ALLOWED = ['dev', 'test'];

    public static function isAllowed(string $environment): bool
    {
        return in_array($environment, self::ALLOWED, true);
    }

    /**
     * @throws LogicException When the environment is not dev or test
     */
    public static function assertAllowed(string $environment): void
    {
        if (self::isAllowed($environment)) {
            return;
        }

        throw new LogicException(sprintf('NowoTwigInspectorBundle must not be enabled in the "%s" environment. Register it only for "dev" and "test" (composer require --dev). It depends on the WebProfiler toolbar and would expose template/controller structure.', $environment));
    }
}
