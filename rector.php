<?php

declare(strict_types=1);

/**
 * Rector configuration for Twig Inspector Bundle.
 *
 * Ensures PHP 8.1+ and Symfony 6|7|8 compatibility; applies dead code, code quality,
 * and type declaration rules. Only the src/ directory is processed (tests are skipped).
 *
 * @see https://getrector.com/documentation
 */
use Rector\Config\RectorConfig;
use Rector\Symfony\CodeQuality\Rector\Class_\EventSubscriberToAttributeRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
    ])
    ->withPhpVersion(PhpVersion::PHP_82)
    ->withComposerBased(symfony: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
    )
    ->withSkip([
        __DIR__ . '/demo',
        __DIR__ . '/vendor',
        __DIR__ . '/tests', // Skip tests: some Symfony rules (e.g. RequestStack constructor) don't match Symfony's actual API
        __DIR__ . '/src/Command/InstallCommand.php', // Skip: help kept in setHelp() for Symfony Console 6.4 (CommandHelpToAttributeRector would break it)
        // Public getSubscribedEvents() and EventSubscriberInterface of these subscribers are shipped API (hosts and tests
        // read them); converting them to #[AsEventListener] would remove the public method and the interface.
        EventSubscriberToAttributeRector::class => [
            __DIR__ . '/src/EventSubscriber/ControllerCommentSubscriber.php',
            __DIR__ . '/src/EventSubscriber/ControllerRenderSubscriber.php',
        ],
    ]);
