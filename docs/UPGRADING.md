# Upgrading Guide

This guide helps you upgrade between versions of the Twig Inspector Bundle.


## Table of contents

- [Upgrading from 1.0.29 to the next release](#upgrading-from-1029-to-the-next-release)
- [Upgrading from 1.0.28 to 1.0.29](#upgrading-from-1028-to-1029)
- [Upgrading from 1.0.27 to 1.0.28](#upgrading-from-1027-to-1028)
- [Upgrading from 1.0.26 to 1.0.27](#upgrading-from-1026-to-1027)
- [Upgrading from 1.0.25 to 1.0.26](#upgrading-from-1025-to-1026)
- [Upgrading from 1.0.24 to 1.0.25](#upgrading-from-1024-to-1025)
- [Upgrading from 1.0.23 to 1.0.24](#upgrading-from-1023-to-1024)
- [Upgrading from 1.0.22 to 1.0.23](#upgrading-from-1022-to-1023)
- [Upgrading from 1.0.21 to 1.0.22](#upgrading-from-1021-to-1022)
- [Upgrading from 1.0.20 to 1.0.21](#upgrading-from-1020-to-1021)
  - [What changed](#what-changed)
- [Upgrading from 1.0.19 to 1.0.20](#upgrading-from-1019-to-1020)
  - [What changed](#what-changed)
- [Upgrading from 1.0.18 to 1.0.19](#upgrading-from-1018-to-1019)
  - [What changed](#what-changed)
- [Upgrading from 1.0.17 to 1.0.18](#upgrading-from-1017-to-1018)
  - [What changed](#what-changed)
- [Upgrading from 1.0.16 to 1.0.17](#upgrading-from-1016-to-1017)
  - [What changed](#what-changed)
- [Upgrading from 1.0.15 to 1.0.16](#upgrading-from-1015-to-1016)
  - [What changed](#what-changed)
  - [Action for contributors](#action-for-contributors)
- [Upgrading from 1.0.14 to 1.0.15](#upgrading-from-1014-to-1015)
  - [What changed](#what-changed)
  - [Action required](#action-required)
- [Upgrading from 1.0.13 to 1.0.14](#upgrading-from-1013-to-1014)
  - [What changed](#what-changed)
- [Upgrading from 1.0.12 to 1.0.13](#upgrading-from-1012-to-1013)
  - [What changed](#what-changed)
- [Upgrading from 1.0.11 to 1.0.12](#upgrading-from-1011-to-1012)
  - [What’s new](#whats-new)
- [Upgrading from 1.0.10 to 1.0.11](#upgrading-from-1010-to-1011)
  - [What’s new](#whats-new)
- [Upgrading from 1.0.9 to 1.0.10](#upgrading-from-109-to-1010)
  - [What’s new](#whats-new)
- [Upgrading from 1.0.8 to 1.0.9](#upgrading-from-108-to-109)
  - [Twig 4.0+ Compatibility](#twig-40-compatibility)
- [Upgrading from 1.0.7 to 1.0.8](#upgrading-from-107-to-108)
- [Upgrading from 1.0.6 to 1.0.7](#upgrading-from-106-to-107)
  - [Twig 3.15+ Compatibility](#twig-315-compatibility)
- [Upgrading from 1.0.5 to 1.0.6](#upgrading-from-105-to-106)
  - [Code Coverage Target](#code-coverage-target)
- [Upgrading from 1.0.4 to 1.0.5](#upgrading-from-104-to-105)
  - [Wildcard Pattern Matching Fix](#wildcard-pattern-matching-fix)
- [Upgrading from 1.0.3 to 1.0.4](#upgrading-from-103-to-104)
  - [Template Names with Slashes](#template-names-with-slashes)
- [Upgrading from 1.0.2 to 1.0.3](#upgrading-from-102-to-103)
  - [Automatic Route Configuration](#automatic-route-configuration)
- [Upgrading from 1.0.1 to 1.0.2](#upgrading-from-101-to-102)
  - [CI/CD and compatibility](#cicd-and-compatibility)
- [Upgrading from 1.0.0 to 1.0.1](#upgrading-from-100-to-101)
  - [Initial Release](#initial-release)
- [General Upgrade Notes](#general-upgrade-notes)
  - [PHP Version Requirements](#php-version-requirements)
  - [Symfony Version Requirements](#symfony-version-requirements)
  - [Twig Version Requirements](#twig-version-requirements)
  - [Breaking Changes](#breaking-changes)
  - [Getting Help](#getting-help)

## Upgrading from 1.0.29 to the next release

_Placeholder for the next release._

## Upgrading from 1.0.28 to 1.0.29

**No action required** — backward-compatible release for bundle users (frontend parsing/overlay refactor and Scrutinizer CI only).

### What changed

- **BlockStorage / Overlay**: Internal TypeScript changes for Twig block comment parsing and overlay behavior; the built asset `index.min.js` is updated. No configuration or public API changes for Symfony apps.
- **Scrutinizer**: Node 20, pinned pnpm via Corepack, frontend test run in external CI — relevant only for contributors/maintainers.

## Upgrading from 1.0.27 to 1.0.28

**No action required** — backward-compatible release for bundle users (internal refactors and contributor-facing file naming only).

### What changed

- **TypeScript file names**: Frontend sources under `src/Resources/assets/src` now use **kebab-case** basenames (e.g. `block-storage.ts`, `overlay.ts`, `filter-match.ts`). Imports in the repo are updated; consuming apps do not import these paths directly.
- **`logger.ts` / `overlay.ts`**: Internal refactor only (same public behavior).

## Upgrading from 1.0.26 to 1.0.27

**No action required** — backward-compatible release for bundle users.

### What changed

- **Repository automation**: Added `CODEOWNERS`, pull request template, and `sync-releases.yml` (GitHub Releases backfill for `v*` tags). No impact on runtime behavior.
- **Developer workflow**: PHP/TS coverage summary scripts live under `.scripts/`; `make test-coverage` and `make test-ts` call those paths. Root `.gitignore` lists `coverage-php.txt` and `coverage-ts.txt`.
- **Scrutinizer**: Node 16 in `.scrutinizer.yml` so external CI can install frontend dependencies with a current lockfile.
- **Demos**: Minor `.gitignore`, `.env.example`, `Makefile`, and lockfile updates for Symfony 6/7/8 demos; `docs/DEMO.md` refreshed.

## Upgrading from 1.0.25 to 1.0.26

**No action required** - backward-compatible release.

### What changed
- **Test/log noise reduction**: Integration fixtures now include `templates/base.html.twig`, and the open-template integration test uses that real template. This avoids the previous noisy `Template "base.html.twig" not found` log line during coverage runs.
- **Colored CLI output**: `composer test` and `composer test-coverage` now force PHPUnit colors (`--colors=always`) so colored output remains visible even when piped (e.g. with `tee`).
- **Coverage summary helper**: Added `.scripts/php-coverage-percent.sh` used by `make test-coverage` to print a final global PHP lines coverage summary with color bands (`<50` red, `50-85` orange, `>85` green).
- **Docs**: Updated setup and demo documentation for the FrankenPHP-based demo stack and refreshed related examples.

No configuration changes for bundle users.

## Upgrading from 1.0.24 to 1.0.25

**No action required** — backward-compatible release.

### What changed
- **Twig overrides**: A new compiler pass (`TwigPathsPass`) registers the bundle's views path with the native Twig loader. If you override bundle templates in `templates/bundles/NowoTwigInspectorBundle/`, they are now correctly consulted first (same behavior as other Symfony bundles).
- **Documentation**: New [USAGE.md](USAGE.md) with step-by-step usage, filter, shortcuts, full panel, overriding templates, and troubleshooting.
- **Dependencies**: Composer and demo app locks updated; code style (PHP CS Fixer) applied.

No configuration changes for bundle users.

## Upgrading from 1.0.23 to 1.0.24

**No action required** — backward-compatible release (CI and test compatibility only).

### What changed
- **CI**: The GitHub Actions workflow now pins `symfony/console` to the same major version as the rest of the Symfony stack, avoiding compatibility issues when running tests (e.g. PHP 8.4 with Symfony 6.4).
- **Tests**: The integration test that runs the install command uses `addCommand()` on Symfony Console 7.0+ and `add()` on 6.4, so CI passes on all supported matrix combinations.

No configuration or API changes for bundle users.

## Upgrading from 1.0.22 to 1.0.23

**No action required** — backward-compatible release (tests and coverage only).

### What changed
- **TypeScript tests**: New tests for the frontend entry point (`index.test.ts`), BlockStorage (sort and controller range with text node), and Overlay (filter highlights, click handler). Coverage now includes `index.ts` and `types.ts`; thresholds are 100% for lines/statements/functions and 99.5% for branches.
- **Overlay**: Click handling is in a dedicated `handleBlockClick()` method (internal refactor; no API or behavior change).
- **Development**: Running `make assets-test` enforces the new coverage thresholds. No impact on bundle users.

No configuration changes for bundle users.

## Upgrading from 1.0.21 to 1.0.22

**No action required** — backward-compatible release (test robustness only).

### What changed
- **Tests**: InstallCommand unit tests now normalize console output so they pass when SymfonyStyle wraps warning/note blocks with line breaks and borders. No change to bundle behavior or configuration.

No configuration changes for bundle users.

## Upgrading from 1.0.20 to 1.0.21

**No action required** — backward-compatible release (bug fix).

### What changed
- **InstallCommand**: Help text is now set in `configure()` instead of the `AsCommand` attribute so the command works with Symfony Console 6.4 (PHP 8.1). Output of `php bin/console nowo:twig-inspector:install --help` is unchanged.

No configuration changes for bundle users.

## Upgrading from 1.0.19 to 1.0.20

**No action required** — backward-compatible release (bug fix and tooling).

### What changed
- **DataCollector**: Service definition fixed so the collector receives the correct constructor arguments (`ControllerRenderSubscriber`, `?Twig\Environment`, and config parameters). If you had a custom decoration or override of this service, ensure it matches the constructor signature; normal installs are unaffected.
- **Development**: `make release-check` now runs TypeScript tests (`assets-test`) as well. No impact on bundle users.

No configuration changes for bundle users.

## Upgrading from 1.0.18 to 1.0.19

**No action required** — backward-compatible release.

### What changed
- **DataCollector**: Profiler serialization fix so the collector works correctly after unserialize (e.g. PHP 8.5, profiler storage). No configuration or API changes.
- **Demos**: Symfony 6 demo runs without FrankenPHP worker for stability; Symfony 7/8 demos unchanged. Demo Dockerfile entrypoints install Composer dependencies when missing.

No configuration changes for bundle users.

## Upgrading from 1.0.17 to 1.0.18

**No action required** — backward-compatible release (tooling and demos only).

### What changed
- **CI/Docker**: Single docker-compose for tests; root Dockerfile on PHP 8.2 with Node.js. If you rely on `docker-compose.test.yml`, use the main `docker-compose.yml` and Makefile targets instead.
- **Makefile**: New/updated targets for demos and container readiness; run `make help` for details.
- **Demos**: Each demo (Symfony 6, 7, 8) has a README and updated Makefile/docker-compose.

No configuration or API changes for bundle users.

## Upgrading from 1.0.16 to 1.0.17

**No action required** — backward-compatible release.

### What changed
- **Controller**: Defensive handling for non-string namespaces in FilesystemLoader path collection.
- **Demos**: HTTP on port 80 instead of HTTPS on 443. Rebuild images with `make build` if you run demos locally.
- **Development**: Asset and clean targets run inside the container; run `make up` first. Demo Makefiles: `make build`, `make update-bundle`.

No configuration changes required for bundle users.

## Upgrading from 1.0.15 to 1.0.16

**Action for contributors** — if you use the Makefile for assets, update your commands.

### What changed
- **DependencyInjection**: The extension no longer duplicates configuration via `setArgument()`. Configuration flows only through parameters; behavior is unchanged.
- **Makefile**: Asset targets renamed — `build-assets` → `assets`, `build-assets-dev` → `assets-dev`, `watch-assets` → `assets-watch`, `clean-assets` → `assets-clean`.

### Action for contributors
If you run `make build-assets`, use `make assets` instead. Run `make help` for the full target list. No changes needed for bundle users who don't contribute to the repo.

## Upgrading from 1.0.14 to 1.0.15

**Action recommended** — ensure routes are restricted to dev/test.

### What changed
- **Prod restriction**: The "open in IDE" controller now returns 404 in `prod` regardless of route configuration. This is a safety measure; you should still only import routes in dev/test.
- **Routes**: The recipe uses `when@dev:` and `when@test:` in `config/routes.yaml`. If you added routes manually without this restriction, update them as shown in INSTALLATION.md.
- **ChainLoader**: Template path validation now supports projects using `ChainLoader` (multiple Twig template directories). No configuration changes needed.

### Action required
If your `config/routes.yaml` imports Twig Inspector routes without `when@dev:` / `when@test:`, update it:

```yaml
when@dev:
    nowo_twig_inspector:
        resource: '@NowoTwigInspectorBundle/Resources/config/routes.yaml'

when@test:
    nowo_twig_inspector:
        resource: '@NowoTwigInspectorBundle/Resources/config/routes.yaml'
```

No breaking changes for projects already restricting routes to dev/test.

## Upgrading from 1.0.13 to 1.0.14

**No action required** — backward-compatible release.

### What changed
- **Internal**: Service IDs for some bundle services now use FQCN (fully qualified class names). If you referenced these by ID in your code (e.g. for decoration), update the reference. The controller `nowo_twig_inspector.controller.open_template` is unchanged for route compatibility.
- **Dependencies**: Composer lock file updated.

No breaking changes for typical usage; no configuration changes required.

## Upgrading from 1.0.12 to 1.0.13

**No action required** — documentation-only release.

### What changed
- **Documentation**: Asset pipeline (TypeScript source → JavaScript output) is now documented in DEVELOPMENT.md and CONTRIBUTING.md. The bundle requires both: TS for the build and compiled JS for runtime. Asset locations (`assets/src`, `views/assets/dist`, `public/assets`) are documented.
- **.gitattributes**: Fixed malformed line that could cause git attribute parsing errors.

No code or configuration changes; no breaking changes.

## Upgrading from 1.0.11 to 1.0.12

**No action required** — backward-compatible release.

### What’s new
- **`inject_on_sub_requests`**: New configuration option (bool, default `false`). When your application renders the main HTML during a sub-request (e.g. fragment-based layout), the inspector may show all templates as "sub-request" and not inject comments. Set `inject_on_sub_requests: true` in `config/packages/nowo_twig_inspector.yaml` to enable injection on sub-requests so the inspector works in those setups.
- **Overlay off by default**: With the inspector enabled, the overlay no longer starts on automatically. Click the `</>` icon in the toolbar to turn the overlay on (green); click again to turn it off (yellow). This avoids the overlay being active until you need it.
- **Toolbar dropdown**: Filter and Rescan in the collector dropdown are now initialized more reliably (after DOM ready), so they should always appear when you open the Twig Inspector dropdown.

No breaking changes; existing configuration and behavior are unchanged.

## Upgrading from 1.0.10 to 1.0.11

**No action required** — backward-compatible release.

### What’s new
- **Controllers tab**: In the Twig Inspector profiler panel, a new **Controllers** tab lists every controller invoked in the request. The **main** controller (the one that handled the page) is labeled **Main**; controllers used via `{{ render(controller(...)) }}` are labeled **Fragment**, with a **Renders** count per controller.
- **Controller HTML comments**: When the inspector is enabled, the HTML source includes comments for controllers in the same style as Twig blocks: `<!-- ┏ controller: FQCN::method [main] template: path -->` after `<body>` for the main controller, and `<!-- ┏ … [fragment] … -->` / `<!-- ┗ /controller -->` around each fragment’s output. Fragment output (e.g. a `<div>…</div>` from `render(controller(...))`) is now wrapped with these comments so you can see controller and template in the source.
- **Overlay and controller comments**: The overlay recognizes these controller comments: when you hover over an element inside a controller range, the tooltip shows the controller (and template when available), in order: controller principal, then controller fragment (if any), then Twig templates in flow order. You can filter by “controller”, “main”, or “fragment” like with template names.
- **Templates & Blocks panels**: The **Count** column was renamed to **Renders** and now shows the number of times each template or block was rendered (each render produces two HTML comments). A short hint in the panel explains this.

No breaking changes; existing configuration and behavior are unchanged.

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

## Upgrading from 1.0.7 to 1.0.8

**No action required** — backward-compatible release.

Packagist search optimization and documentation updates only. No code or configuration changes.

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

### CI/CD and compatibility

**No action required** — backward-compatible update.

CI/CD fixes (PHP/Symfony matrix, PHPUnit constraint for PHP 8.1, PHP-CS-Fixer). No configuration or API changes.

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

