# Upgrading Guide

This guide helps you upgrade between versions of the Twig Inspector Bundle.

## Upgrading from 1.0.9 to 1.0.10

**No action required** — backward-compatible release.

### What’s new
- **Panel**: “How to use” is now a **tab** in the full collector panel (first tab). The toolbar dropdown no longer shows the long “How to use” text.
- **Filter**: You can filter by template **path** (not only name) and use **comma-separated** terms (e.g. `header, footer`). When the filter is active, matching blocks get persistent highlight frames.
- **Template timing**: If you saw “No template timing data” in the panel, it should now show times when the Twig profiler is enabled (Symfony Bridge extension is now detected).
- **Docs**: New [USAGE.md](USAGE.md), screenshots in README (`docs/img/`), and clearer overlay instructions.

No breaking changes; existing configuration and behavior are unchanged.

## Upgrading from 1.0.8 to 1.0.9

### Twig 4.0+ Compatibility

**No action required** - This is a backward-compatible fix.

The bundle has been updated to add the `#[\Twig\Attribute\YieldReady]` attribute to the `NodeStart` class to maintain compatibility with Twig 4.0+ and eliminate deprecation warnings.

**What changed:**
- Added `YieldReady` attribute to `NodeStart` class (matching `NodeEnd` which already had it)
- Internal implementation updated to avoid deprecation warnings in Twig 4.0+
- No breaking changes to the public API
- All existing functionality remains the same

**If you see deprecation warnings:**
- Update to version 1.0.9 or later
- The bundle is fully compatible with Twig 3.8 through 4.0

## Upgrading from 1.0.6 to 1.0.7

### Twig 3.15+ Compatibility

**No action required** - This is a backward-compatible fix.

The bundle has been updated to use `Twig\Node\BodyNode` instead of directly instantiating `Twig\Node\Node` to maintain compatibility with Twig 3.15+ and future Twig 4.0.

**What changed:**
- Internal implementation updated to avoid deprecation warnings
- No breaking changes to the public API
- All existing functionality remains the same

**If you see deprecation warnings:**
- Update to version 1.0.7 or later
- The bundle is fully compatible with Twig 3.8 through 4.0

## Upgrading from 1.0.5 to 1.0.6

### Code Coverage Target

**No action required** - This is a documentation update.

The minimum code coverage requirement has been adjusted from 100% to 97.5% to reflect realistic testing limitations for edge cases (filesystem permissions, etc.).

## Upgrading from 1.0.4 to 1.0.5

### Wildcard Pattern Matching Fix

**No action required** - This is a bug fix.

The wildcard pattern matching in template/block exclusion has been fixed. Patterns like `admin/*` and `email/*.html.twig` now work correctly.

## Upgrading from 1.0.3 to 1.0.4

### Template Names with Slashes

**No action required** - This is a bug fix.

The bundle now correctly handles template names with slashes (e.g., `admin/users/list.html.twig`). The route pattern has been updated to allow slashes while maintaining security against path traversal attacks.

## Upgrading from 1.0.2 to 1.0.3

### Automatic Route Configuration

**No action required** - Routes are now automatically configured.

The `InstallCommand` and Flex Recipe now automatically create/update `config/routes.yaml` with the bundle's route import. If you previously added the routes manually, the bundle will detect and not duplicate the import.

**Manual installation:**
If you prefer to configure routes manually, you can still do so by adding the following to `config/routes.yaml`:

```yaml
when@dev:
    nowo_twig_inspector:
        resource: '@NowoTwigInspectorBundle/Resources/config/routes.yaml'
```

## Upgrading from 1.0.1 to 1.0.2

### Configuration Changes

**No action required** - This is a backward-compatible update.

The configuration structure remains the same. All existing configurations will continue to work.

## Upgrading from 1.0.0 to 1.0.1

### Initial Release

**No action required** - This is the first stable release.

If you're upgrading from a pre-release version, ensure you have:
- PHP >= 8.1, < 8.6
- Symfony >= 6.0 || >= 7.0 || >= 8.0
- Twig >= 3.8 || >= 4.0
- Symfony Web Profiler Bundle (for development)

## General Upgrade Notes

### PHP Version Requirements

- **Minimum**: PHP 8.1
- **Maximum**: PHP < 8.6

### Symfony Version Requirements

- **Supported**: Symfony 6.0, 7.0, and 8.0
- The bundle is tested against all three major Symfony versions

### Twig Version Requirements

- **Minimum**: Twig 3.8
- **Maximum**: Twig 4.0 (and future versions)
- The bundle is fully compatible with Twig 3.8 through 4.0

### Breaking Changes

This bundle follows [Semantic Versioning](https://semver.org/), so:
- **Major versions** (x.0.0) may contain breaking changes
- **Minor versions** (1.x.0) add new features in a backward-compatible manner
- **Patch versions** (1.0.x) contain bug fixes and are backward-compatible

### Getting Help

If you encounter issues during upgrade:
1. Check the [CHANGELOG.md](CHANGELOG.md) for detailed changes
2. Review the [CONFIGURATION.md](CONFIGURATION.md) for configuration options
3. Open an issue on [GitHub](https://github.com/nowo-tech/twig-inspector-bundle/issues)

