# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed
- **CI/CD Compatibility**: Fixed CI workflow to handle different PHP versions correctly
  - Changed `composer install` to `composer update` in CI jobs to resolve dependencies based on PHP version
  - Updated `symfony/yaml` version constraint from `^8.0` to `^6.0 || ^7.0 || ^8.0` for PHP 8.2/8.3 compatibility
  - Removed `version` field from `composer.json` (Packagist detects version from Git tags automatically)

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

### Added
- **100% Code Coverage**: Complete test coverage (144/144 lines, 35/35 methods, 11/11 classes)
- Comprehensive test suite covering all edge cases
- GitHub Actions CI/CD pipeline
- Code style enforcement with PHP-CS-Fixer
- Automated dependency updates with Dependabot
- Three independent demo projects (Symfony 6.4, 7.0, and 8.0) with their own docker-compose.yml
- Test suites for each demo project
- Support for PHP 8.4 and 8.5
- CONTRIBUTING.md with contribution guidelines

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

