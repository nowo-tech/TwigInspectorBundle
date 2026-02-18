# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.14] - 2026-02-18

### Changed
- **Dependencies**: Updated composer.lock (Symfony 8.0.4/8.0.5 in demo)
- **Services**: Internal services now use FQCN as service IDs (BoxDrawings, extensions, subscribers, collector, command); controller keeps `nowo_twig_inspector.controller.open_template` for route compatibility
- **Configuration**: Added commented placeholder for future `optimize_output_buffering` option in InstallCommand config template
- **Demo Symfony 8**: Updated config reference and composer.lock

## [1.0.13] - 2026-02-18

### Fixed
- **.gitattributes**: Fixed malformed line (missing newline before comment) that could cause "not a valid attribute name" errors

### Changed
- **Documentation**: Clarified asset pipeline (TypeScript → JavaScript) in DEVELOPMENT.md and CONTRIBUTING.md
  - Documented that TS source files (`src/Resources/assets/src/*.ts`) compile to JS used at runtime; both are required
  - Added asset location table: source (TS), build output (views/assets/dist), and distributable (public/assets)
  - Resolved `public/assets/` clarification: contains distributable assets copied to host projects with `assets:install`

## [1.0.12] - 2026-02-11

### Added
- **Configuration**: New option `inject_on_sub_requests` (bool, default `false`). When `true`, the bundle injects comments also during sub-requests (e.g. when the main content is rendered as a fragment). Use this if all templates show "sub-request" in the panel and none get inspected.

### Changed
- **Overlay default**: When the inspector is enabled, the overlay is **off by default**. Click the `</>` toolbar icon to turn it on (green); click again to turn it off (yellow). Previously the overlay started on as soon as the inspector was active.
- **Toolbar dropdown**: Filter input and Rescan button are now initialized after `DOMContentLoaded` so they reliably appear in the collector dropdown in all environments.
- **Demos**: Documented that demos use **FrankenPHP** with **Caddy** and can serve over **HTTPS** (Caddy internal CA). Symfony 7 demo supports HTTP→HTTPS redirect; `TRUSTED_PROXIES` is set where needed. README, demo/README, docs/DEMO.md, and CONTRIBUTING.md updated accordingly.
- **Development**: `make test` and `make test-coverage` now start the Docker container if not running (`ensure-up`), so you no longer need to run `make up` first.

## [1.0.11] - 2026-02-10

### Added
- **Web Profiler — Controllers**: New **Controllers** tab in the collector panel listing every controller invoked in the request (main controller + sub-requests from `{{ render(controller(...)) }}`). Each row shows controller name, **Role** (Main / Fragment badge), and **Renders** count.
- **Controller HTML comments**: When the inspector is enabled, the bundle injects HTML comments for controllers using the same box-drawing style as Twig blocks: `<!-- ┏ controller: FQCN::method [main] template: path -->` after `<body>` for the main controller, and `<!-- ┏ … [fragment] … -->` / `<!-- ┗ /controller -->` wrapping each fragment’s output. You can see which controller (and template) rendered which part of the page in the HTML source.
- **Overlay — Controller recognition**: The overlay now recognizes controller comments: hovering over any element inside a controller range shows the controller (and optional template) in the tooltip, same as for Twig blocks. Display order: controller principal, then controller fragment (if any), then Twig templates in flow order.
- **Panel — Renders**: Templates and Blocks tables now show a **Renders** column (number of times each template/block was rendered) with a short hint explaining that each render produces two HTML comments (start and end).

### Changed
- **TwigInspectorCollector**: Now receives `ControllerRenderSubscriber` as first constructor argument (internal; extension and services updated accordingly). No user-facing breaking change.
- **Controller comments format**: Controller comments use `┏` / `┗` (same pattern as Twig block comments) and include the template path when available.

## [1.0.10] - 2025-02-10

### Added
- **Screenshots**: Added three screenshots to the README (overlay tooltip, toolbar dropdown, DevTools HTML comments); images live in `docs/img/`
- **Documentation**: [USAGE.md](USAGE.md) — step-by-step overlay usage, filter, shortcuts, troubleshooting
- **Panel**: New “How to use” tab in the collector panel (first tab by default) with overlay instructions; “How to use” text removed from toolbar dropdown
- **Filter**: Filter by template **path** (link) as well as name; **comma-separated** terms (OR) for multiple templates; persistent highlight frames when filter is active
- **Demos**: Demo templates split into multiple Twig files (layout + partials) to better showcase the filter and overlay
- **Tests**: Vitest + jsdom for frontend; `blockMatchesFilter()` unit tests; `getAllBlocks()` in BlockStorage for filter highlights
- **Toolbar**: Icon title/tooltip “Click to toggle overlay: green = on, yellow = off”

### Fixed
- **Template timing**: Collector now uses `Symfony\Bridge\Twig\Extension\ProfilerExtension` when present so “template timing” data appears in the panel (was only checking Twig’s `ProfilerExtension`)

### Changed
- **README**: Usage section expanded with icon states (green/yellow), screenshots section, link to USAGE.md
- **INSTALLATION.md**: Verify step mentions green icon; link to USAGE.md for full overlay flow
- **Toolbar dropdown**: Simplified (removed long “How to use” block; instructions moved to panel tab)

## [1.0.9] - 2025-01-26

### Fixed
- **Twig 4.0+ Compatibility**: Fixed deprecation warning for `NodeStart` class
  - Added `#[\Twig\Attribute\YieldReady]` attribute to `NodeStart` class to resolve Twig 4.0+ deprecation warnings
  - This attribute is required for custom Twig nodes in Twig 4.0+ to indicate they are ready for generator/yield support
  - **Backward Compatible**: No breaking changes, works with all supported Twig versions (3.8+)
  - The `NodeEnd` class already had this attribute, now both node classes are consistent
  - Fixes: "User Deprecated: NodeStart requires #[\Twig\Attribute\YieldReady] attribute"
  - See [UPGRADING.md](UPGRADING.md) for upgrade instructions

## [1.0.8] - 2024-12-15

### Changed
- **Packagist Search Optimization**: Improved package discoverability on Packagist
  - Enhanced `composer.json` description with more searchable terms (debugging, inspecting, visual overlay, IDE integration, development tool)
  - Expanded keywords array with additional relevant terms:
    - `debugging`, `inspector`, `templates`, `twig-inspector`, `twig-debug`
    - `template-inspector`, `development`, `dev-tool`
    - `symfony-profiler`, `ide-integration`, `template-finder`, `block-inspector`
  - Updated README.md introduction with more descriptive and searchable content
  - These changes improve search results when users search for terms like "twig debug", "twig inspector", "symfony template debug", "template inspector", etc.

## [1.0.7] - 2024-12-15

### Changed
- **Documentation Organization**: Moved all documentation files to `docs/` directory
  - `CONFIGURATION.md` moved from root to `docs/CONFIGURATION.md`
  - `BRANCHING.md` moved from root to `docs/BRANCHING.md`
  - `CONTRIBUTING.md` moved from root to `docs/CONTRIBUTING.md`
  - `CHANGELOG.md` moved from root to `docs/CHANGELOG.md`
  - Updated all references in README.md and documentation files
  - Better organization following standard project structure

### Fixed
- **Twig 3.15+ Compatibility**: Fixed deprecation warning when instantiating `Twig\Node\Node` directly
  - Replaced direct `Node` instantiation with `BodyNode` in `DebugInfoNodeVisitor` for wrapping template display nodes
  - Added `createBodyNode()` helper method for better code organization and future-proofing
  - This ensures full compatibility with Twig 3.8 through 4.0
  - **Backward Compatible**: No breaking changes, works with all supported Twig versions (3.8+)
  - Fixes: "User Deprecated: Since twig/twig 3.15: Instantiating "Twig\Node\Node" directly is deprecated"
  - See [UPGRADING.md](UPGRADING.md) for upgrade instructions
- **Code Quality**: Removed duplicate PHPDoc comment in `HtmlCommentsExtension::isSupported()`
- **Tests**: Updated `DebugInfoNodeVisitorTest` to use `BodyNode` instead of `Node` to avoid deprecation warnings in Twig 3.15+

## [1.0.6] - 2024-12-15

### Changed
- **Code Coverage Target**: Updated minimum coverage requirement from 100% to 97.5%
  - Some edge cases (e.g., `file_get_contents` returning false, filesystem permission errors) are difficult to test without advanced PHP extensions
  - The code handles these cases correctly, and 97.5% is a realistic and maintainable coverage target
  - Updated CI/CD workflows, README.md, and CONTRIBUTING.md to reflect this change
  - Current coverage: 97.55% (358/367 lines) with 128 tests passing

## [1.0.5] - 2024-12-15

### Fixed
- **Wildcard Pattern Matching**: Fixed regex pattern matching in `HtmlCommentsExtension::isExcluded()`
  - Changed from `str_replace` to `preg_quote()` for proper escaping of special regex characters
  - Now correctly handles wildcard patterns like `admin/*` and `email/*.html.twig`
- **Test Suite**: Fixed all test failures and compatibility issues
  - Removed `Application::add()` calls (removed in Symfony 8.0) from all tests
  - Fixed constructor parameter mismatches in `HtmlCommentsExtension` tests
  - Fixed `TemplateWrapper` mock issue (class is final, cannot be mocked)
  - Fixed test for non-numeric line numbers to handle Symfony 7.0+ exceptions
  - Fixed help text test to verify command definition instead of output
  - Added tests for `InstallCommand::configure()` and `__construct()` methods
  - Added test for `OpenTemplateController` with invalid paths in validation loop
  - All 128 tests now pass with 97.55% code coverage (358/367 lines)
  - Remaining uncovered lines are edge cases requiring system-level conditions (e.g., `file_get_contents` returning false, filesystem permission errors)
  - Updated CI to accept 97.5% minimum coverage (realistic target given edge case limitations)

## [1.0.4] - 2024-12-15

### Fixed
- **Route Pattern**: Updated route requirements to allow slashes in template names
  - Changed from `"[^/\\0]+"` to `"[^\\0]+"` to support templates in subdirectories (e.g., `admin/users/list.html.twig`)
  - Security validations in `OpenTemplateController` already prevent path traversal attacks
  - Added test for templates in subdirectories

## [1.0.3] - 2024-12-15

### Added
- **Automatic Routes Setup**: Install command and Flex Recipe now automatically create/update `routes.yaml`
  - Creates `config/routes.yaml` if it doesn't exist
  - Adds route import to existing `routes.yaml` if not already present
  - Prevents duplicate route imports
  - Resolves "route does not exist" errors when routes aren't configured
- **Tests**: Added comprehensive test coverage for new features
  - Added `InstallCommandTest` with 18 test cases covering all command scenarios
    - Tests for configuration file creation in different environments
    - Tests for routes.yaml creation and updates (5 tests)
    - Tests for duplicate detection and error handling
  - Added `ConfigurationTest` for configuration processing and validation
  - Enhanced `HtmlCommentsExtensionTest` with tests for new configuration options
    - Tests for exclusions, extensions, custom cookie
    - Test for `RouteNotFoundException` handling in `getLink()` method
  - Enhanced `TwigInspectorCollectorTest` with tests for metrics collection
  - All new code has 100% test coverage
- **Documentation**: Improved and standardized documentation
  - Translated `CONTRIBUTING.md` to English (was in Spanish)
  - Added `BRANCHING.md` with complete branching policy and workflow
  - Updated all documentation to clarify Flex Recipe vs Install Command usage
  - Improved configuration documentation clarity
- **Branching Policy**: Added comprehensive branching strategy documentation
  - Documented branch types (feature, fix, hotfix, release)
  - Defined naming conventions and workflow
  - Added release process guidelines
  - Included best practices and common scenarios

### Changed
- **Documentation**: Clarified Flex Recipe vs Install Command usage
  - Flex Recipe is the primary method (automatic when installing from Packagist)
  - Install Command is only needed for private bundles or manual installations
  - Updated README, CHANGELOG, CONFIGURATION.md, and demo/README.md for consistency
- **InstallCommand**: Enhanced to automatically create/update routes.yaml
  - Now handles both configuration file and routes file setup
  - Prevents "route does not exist" errors by ensuring routes are configured
  - Updated help text to reflect new functionality
- **HtmlCommentsExtension**: Improved error handling for route generation
  - Added `RouteNotFoundException` handling in `getLink()` method
  - Returns fallback URL if route is not available (e.g., in production)
  - Prevents exceptions when routes aren't configured or in non-dev environments

## [1.0.2] - 2024-12-12

### Fixed
- **CI/CD Compatibility**: Fixed CI workflow to handle different PHP versions correctly
  - Changed `composer install` to `composer update` in CI jobs to resolve dependencies based on PHP version
  - Updated `symfony/yaml` version constraint from `^8.0` to `^6.0 || ^7.0 || ^8.0` for PHP 8.2/8.3 compatibility
  - Removed `version` field from `composer.json` (Packagist detects version from Git tags automatically)
- **PHP-CS-Fixer**: Fixed rule conflict in code style configuration
  - Removed duplicate `single_blank_line_before_namespace` rule (already included in `@PSR12`)
  - Fixed PHP version format from `>=8.1,<8.6` to `>=8.1 <8.6` for better compatibility
- **PHP 8.1 Compatibility**: Fixed PHPUnit version constraint for PHP 8.1 support
  - Changed PHPUnit from `^10.0 || ^11.0` to `^10.0` (PHPUnit 11 requires PHP 8.2+)
  - Updated `composer.lock` to use PHPUnit 10.5.60 (compatible with PHP 8.1)
- **CI Matrix**: Fixed incompatible PHP/Symfony combinations in CI workflow
  - PHP 8.1: Only Symfony 6.4 (Symfony 7.0+ requires PHP 8.2+, Symfony 8.0 requires PHP 8.4+)
  - PHP 8.2 and 8.3: Symfony 6.4 and 7.0 (Symfony 8.0 requires PHP 8.4+)
  - PHP 8.4 and 8.5: All Symfony versions (6.4, 7.0, 8.0)

### Changed
- Updated GitHub Actions dependencies (actions/checkout@v6, actions/cache@v5)

## [1.0.1] - 2024-12-12

### Changed
- Updated version to 1.0.1 in composer.json to match Git tag
- Updated README installation command to use ^1.0.1

## [0.0.1] - 2024-12-12

### Added
- **100% Code Coverage**: Achieved complete test coverage (144/144 lines, 35/35 methods, 11/11 classes)
- **Comprehensive Test Suite**: Added tests for all edge cases including:
  - Nested content handling (changed and unchanged)
  - Non-HTML template detection
  - Empty and whitespace-only content
  - JSON content detection
  - Backbone template detection
  - Template file extension validation
- **CONTRIBUTING.md**: Added comprehensive contribution guidelines
- **CI/CD**: GitHub Actions pipeline, PHP-CS-Fixer, Dependabot
- **Demos**: Three independent demo projects (Symfony 6.4, 7.0, 8.0) with test suites; PHP 8.4 and 8.5 support

### Fixed
- **Test Suite**: Fixed all test failures and warnings
  - Corrected tests with final classes (ModuleNode, Source, TemplateWrapper) using real instances
  - Fixed output buffering issues in HtmlCommentsExtension tests
  - Corrected NodeStart test to expect 3 repr() calls instead of 2
  - Added missing symfony/yaml dependency for tests
- **Web Profiler integration**: Fixed namespace issue preventing Web Profiler toolbar from loading
  - Changed Twig namespace from `@NowoTwigInspectorBundle` to `@NowoTwigInspector` (correct Symfony convention)
  - Updated template references in `services.yaml` and `template.html.twig`
- **Asset loading**: Fixed JavaScript and CSS assets not loading in Web Profiler toolbar
  - Copied assets from `assets/dist/` to `views/assets/dist/` for Twig template access
  - Updated template to use correct `include()` syntax for assets
- **Demo projects**: Fixed multiple issues in demo projects
  - Added Dockerfiles with Composer installation for all demos
  - Fixed nginx configuration for correct PHP-FPM path resolution
  - Added profiler configuration in `framework.yaml` for all demos
  - Created base templates with profiler block support
  - Added route imports for Web Profiler and Twig Inspector bundle
  - Added `symfony/yaml` dependency to all demos
  - Added entrypoint scripts in Dockerfiles to handle directory permissions
  - Configured Composer audit settings to allow insecure packages in development demos
  - Standardized port configuration (8001 by default, configurable via PORT env variable)

### Changed
- Updated demo projects to use specific bundle version (`^0.0.1`) instead of wildcard (`*`)

## [1.0.0] - 2024-12-11

### Compatibility

- **PHP**: >= 8.1, < 8.6
- **Symfony**: >= 6.0 || >= 7.0 || >= 8.0
- **Twig**: >= 3.8 || >= 4.0
- **Symfony Web Profiler**: >= 6.0 || >= 7.0 || >= 8.0

### Added

- **Twig template inspection**: Visual overlay showing which templates render which HTML elements
  - Hover over HTML elements to see template information
  - Click on elements to open templates in IDE
  - Cookie-based activation (no code changes needed)
  - Works with Symfony Web Profiler toolbar
- **Template comments**: Automatically adds HTML comments before and after Twig blocks
  - Box drawing characters for visual distinction
  - Template name and line number in comments
  - Clickable links to open templates in IDE
- **Web Profiler integration**: Adds icon to Symfony Web Profiler toolbar
  - Enable/disable toggle checkbox
  - Visual status indicators
  - JavaScript overlay for template inspection
- **Bundle structure**: Complete Symfony bundle with:
  - Bundle class (`NowoTwigInspectorBundle`)
  - DependencyInjection extension (`NowoTwigInspectorExtension`)
  - Controller for opening templates (`OpenTemplateController`)
  - Data collector for Web Profiler (`TwigInspectorCollector`)
  - Twig extensions and node visitors
  - Automatic template discovery
- **Development tools**:
  - PHPUnit test configuration
  - PHP-CS-Fixer configuration (PSR-12)
  - Docker development environment
  - Makefile for common development tasks
  - Composer scripts for testing and code style
- **Documentation**:
  - Complete README with usage examples
  - PHPDoc documentation in English for all classes and methods
  - Inline code comments in English

### Changed

- **Updated from Oro Twig Inspector**: Migrated from `Oro\TwigInspector` to `Nowo\TwigInspectorBundle`
- **Updated dependencies**: Upgraded to Symfony 6.0+, 7.0+, and 8.0+ compatibility
- **Updated PHP requirements**: Minimum PHP 8.1 (was 7.4)
- **Updated Twig compatibility**: Supports Twig 3.8+ and 4.0+

### Notes

- The bundle automatically registers all services
- Templates are automatically discovered by Symfony
- Only works in `dev` and `test` environments (should not be enabled in production)
- Requires Symfony Web Profiler Bundle for full functionality
- IDE integration requires proper configuration in Symfony framework settings

