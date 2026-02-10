# Twig Inspector Bundle

[![CI](https://github.com/nowo-tech/TwigInspectorBundle/actions/workflows/ci.yml/badge.svg)](https://github.com/nowo-tech/twig-inspector-bundle/actions/workflows/ci.yml) [![Packagist Version](https://img.shields.io/packagist/v/nowo-tech/twig-inspector-bundle.svg?style=flat)](https://packagist.org/packages/nowo-tech/twig-inspector-bundle) [![Packagist Downloads](https://img.shields.io/packagist/dt/nowo-tech/twig-inspector-bundle.svg)](https://packagist.org/packages/nowo-tech/twig-inspector-bundle) [![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE) [![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php)](https://php.net) [![Symfony](https://img.shields.io/badge/Symfony-6%20%7C%207%20%7C%208-000000?logo=symfony)](https://symfony.com) [![GitHub stars](https://img.shields.io/github/stars/nowo-tech/twig-inspector-bundle.svg?style=social&label=Star)](https://github.com/nowo-tech/twig-inspector-bundle)

> ⭐ **Found this useful?** [Install from Packagist](https://packagist.org/packages/nowo-tech/twig-inspector-bundle) · Give it a **star** on [GitHub](https://github.com/nowo-tech/TwigInspectorBundle) so more developers can find it.

**Twig Inspector Bundle** — Debug Twig templates directly in the browser. See which template or block rendered each HTML element, click to open it in your IDE, and use it from the Symfony Web Profiler. For Symfony 6, 7 and 8 · PHP 8.1+.

## Table of contents

- [Quick search terms](#quick-search-terms)
- [Features](#features)
- [Installation](#installation)
- [Usage](#usage)
- [Screenshots](#screenshots)
- [Configuration](#configuration)
- [Documentation](#documentation)
- [Requirements](#requirements)
- [Demo](#demo-projects)
- [Development & testing](#development)
- [Building assets](#building-assets)
- [CI/CD](#cicd)
- [License & author](#license)

## Quick search terms

Looking for **Twig debug**, **Twig inspector**, **Symfony template debug**, **template inspector**, **which template rendered this**, **Twig block finder**, **Web Profiler Twig**, **IDE open template**, **Twig development tool**? You're in the right place.

## Features

- ✅ Inspect Twig templates directly in the browser
- ✅ Visual overlay showing which templates render which HTML elements
- ✅ Click to open templates in your IDE
- ✅ Works with Symfony Web Profiler
- ✅ Cookie-based activation (no code changes needed)
- ✅ Supports nested blocks and templates
- ✅ **Configurable template/block exclusion** (with wildcard support)
- ✅ **Template usage metrics** in Web Profiler
- ✅ **Performance optimized** (skips processing when disabled)
- ✅ **Flexible configuration** for different use cases

## Installation

```bash
composer require nowo-tech/twig-inspector-bundle --dev
```

[![Install from Packagist](https://img.shields.io/badge/Packagist-install-777BB4?logo=composer)](https://packagist.org/packages/nowo-tech/twig-inspector-bundle)

With **Symfony Flex**, the recipe registers the bundle and adds config/routes automatically. Without Flex, see [docs/INSTALLATION.md](docs/INSTALLATION.md) for manual steps.

**Manual registration** in `config/bundles.php`:

```php
<?php

return [
    // ...
    Nowo\TwigInspectorBundle\NowoTwigInspectorBundle::class => ['dev' => true, 'test' => true],
];
```

## Usage

1. **Enable the bundle** (only in `dev` and `test` environments).

2. **Open your app** in the browser with the Symfony Web Profiler toolbar visible.

3. **In the toolbar**, find the **`</>`** icon (Twig Inspector) and open its dropdown.

4. **Enable the inspector**: check the **“Enable”** checkbox, then **reload the page** (the page reloads so Twig can inject the inspection comments).

5. **Turn on the overlay**: click the **`</>`** icon so it turns **green**.  
   - **Green** = overlay on (hover shows highlight and popup).  
   - **Yellow** = overlay off (click the icon again to turn it green and show the overlay).

6. **Move the mouse** over the page: each element under the cursor gets a **blue highlight** and a **popup** with the template name(s) that rendered it.

7. **Click an element** to open that template in your IDE (if several templates apply, you’ll pick one from a list). Press **Esc** to close the overlay picker.

**Summary:** Enable → reload → click `</>` so it’s green → hover to see templates (blue highlight + popup) → click to open in IDE. Use the **Filter** field to show only blocks whose template name or path matches (e.g. `header, footer`). See [docs/USAGE.md](docs/USAGE.md) for more detail.

### Screenshots

| Overlay tooltip (hover) | Toolbar dropdown |
|-------------------------|------------------|
| [![Overlay tooltip](docs/img/overlay-tooltip.png)](docs/img/overlay-tooltip.png) | [![Toolbar dropdown](docs/img/toolbar-dropdown.png)](docs/img/toolbar-dropdown.png) |

When the overlay is on (green icon), hovering over an element shows a **tooltip** with the template name(s). The **toolbar dropdown** lets you enable the inspector, filter by template name or path, and rescan the DOM.

| DevTools: HTML comments |
|-------------------------|
| [![DevTools HTML comments](docs/img/devtools-html-comments.png)](docs/img/devtools-html-comments.png) |

The bundle injects HTML comments around each Twig-rendered block so the overlay can map elements to templates. You can see them in the Elements panel (`data-twig-inspector-*` on `<html>` and comment nodes).

## How It Works

The bundle adds HTML comments before and after every Twig block and template:

```html
<!-- ┏━ template_name [/_template/template.html.twig] #abc123 -->
<div>Your content</div>
<!-- ┗━ template_name [/_template/template.html.twig] #abc123 -->
```

When enabled, a JavaScript overlay shows which templates correspond to which HTML elements, and clicking on them opens the template in your IDE.

## Configuration

The bundle works out of the box with default settings. **No configuration file is required** - the bundle uses sensible defaults defined in `Configuration.php`.

**Important**: The configuration file (`nowo_twig_inspector.yaml`) is **optional**. You only need to create it if you want to customize the default behavior.

### How Configuration Works

1. **Default Values**: The bundle uses default values from `Configuration.php` if no config file exists
2. **YAML Merging**: If a YAML file exists, Symfony automatically merges it with default values
3. **No Auto-Deletion**: When uninstalling the bundle, the YAML file is **not** automatically deleted (you may want to keep your custom configuration)

### Bundle Configuration

#### Symfony Flex Recipe (Automatic - Recommended)

**If the bundle is installed via Symfony Flex** (from Packagist), both the configuration file and routes will be created **automatically** during `composer require`:
- `config/packages/nowo_twig_inspector.yaml` (configuration)
- `config/routes.yaml` (route import, if file doesn't exist)

**You don't need to do anything else** - everything is set up automatically.

**Note**: Flex Recipes only work when the bundle is published in the official Symfony Flex repository (Packagist). If you're using a private bundle or installing from a Git repository, Flex Recipes won't work and you'll need to use the install command below.

#### Install Command (For manual installation or private bundles)

**Only use this command if:**
- You're installing a private bundle (not from Packagist)
- The Flex Recipe didn't create the file automatically
- You want to regenerate the configuration file for a different environment
- You want to recreate the file after deleting it

To create the configuration file manually:

```bash
php bin/console nowo:twig-inspector:install
```

This command will:
- Create the configuration file at `config/packages/dev/nowo_twig_inspector.yaml`
- Create or update `config/routes.yaml` with the bundle route import (if needed)
- Include all available options with comments
- Ask for confirmation if the config file already exists (use `--force` to overwrite)

You can also specify a different environment:

```bash
# For test environment
php bin/console nowo:twig-inspector:install --env=test

# Overwrite existing file
php bin/console nowo:twig-inspector:install --force
```

#### Manual Installation

Alternatively, you can create the configuration file manually:

```bash
# Create the configuration file (optional)
touch config/packages/dev/nowo_twig_inspector.yaml
```

Then configure the bundle behavior:

```yaml
# config/packages/dev/nowo_twig_inspector.yaml
nowo_twig_inspector:
    # List of template file extensions to inspect
    enabled_extensions:
        - '.html.twig'
        # - '.twig'  # Uncomment to also inspect .twig files
    
    # Templates to exclude from inspection (supports wildcards with *)
    excluded_templates:
        - 'admin/*'           # Exclude all admin templates
        - 'email/*.html.twig' # Exclude email templates
    
    # Blocks to exclude from inspection (supports wildcards with *)
    excluded_blocks:
        - 'javascript'        # Exclude 'javascript' block
        - 'head_*'            # Exclude blocks starting with 'head_'
    
    # Enable template usage metrics in Web Profiler
    enable_metrics: true
    
    # Optimize performance by skipping output buffering when disabled
    optimize_output_buffering: true
    
    # Custom cookie name for enabling/disabling inspector
    cookie_name: 'twig_inspector_is_active'
```

**Default Values** (used when no config file exists):

- `enabled_extensions`: `['.html.twig']` - Only `.html.twig` files are inspected
- `excluded_templates`: `[]` - No templates excluded by default
- `excluded_blocks`: `[]` - No blocks excluded by default
- `enable_metrics`: `true` - Metrics collection enabled
- `optimize_output_buffering`: `true` - Performance optimization enabled
- `cookie_name`: `'twig_inspector_is_active'` - Default cookie name

**Configuration Options:**

- `enabled_extensions`: Array of template file extensions to inspect (default: `['.html.twig']`)
- `excluded_templates`: Array of template names or patterns to exclude (supports `*` wildcard)
- `excluded_blocks`: Array of block names or patterns to exclude (supports `*` wildcard)
- `enable_metrics`: Enable collection of template usage statistics (default: `true`)
- `optimize_output_buffering`: Skip output buffering when inspector is disabled (default: `true`)
- `cookie_name`: Name of the cookie used to enable/disable inspector (default: `'twig_inspector_is_active'`)

**Note**: All configuration options are optional. If you don't create a config file, the bundle will use the default values listed above.

### Configuration File Behavior

**If YAML exists**: Symfony automatically loads and merges it with default values. Your custom configuration takes precedence over defaults.

**If YAML doesn't exist**: The bundle uses default values from `Configuration.php`. No file is required.

**When uninstalling**: The YAML file is **not automatically deleted**. This is intentional because:
- You may have customized the configuration
- You might want to keep it for reference
- Symfony cannot determine if the file is still needed
- You can manually delete it if desired: `rm config/packages/dev/nowo_twig_inspector.yaml`

For detailed information about how configuration works, see [docs/CONFIGURATION.md](docs/CONFIGURATION.md).

## Documentation

| Document | Description |
|----------|-------------|
| [**Installation**](docs/INSTALLATION.md) | Step-by-step install (Flex and manual), routes, IDE setup |
| [**Usage**](docs/USAGE.md) | How to use the overlay (green/yellow icon, highlight, popup, open in IDE) |
| [**Configuration**](docs/CONFIGURATION.md) | All options, defaults, and exclusion rules |
| [**Changelog**](docs/CHANGELOG.md) | Version history and changes |
| [**Upgrading**](docs/UPGRADING.md) | Upgrade notes between versions |
| [**Roadmap**](docs/ROADMAP.md) | Vision, 1.x focus, and future ideas |
| [**Security**](docs/SECURITY.md) | Reporting vulnerabilities and security notes |
| [**Contributing**](docs/CONTRIBUTING.md) | How to contribute and code style |
| [**Branching**](docs/BRANCHING.md) | Git workflow and release strategy |

### IDE Integration

To open templates in your IDE, configure the `ide` option in your Symfony configuration:

```yaml
# config/packages/dev/framework.yaml
framework:
    ide: 'phpstorm://open?file=%%f&line=%%l'
```

Supported IDEs:
- PhpStorm: `phpstorm://open?file=%%f&line=%%l`
- VS Code: `vscode://file/%%f:%%l`
- Sublime Text: `subl://open?url=file://%%f&line=%%l`
- Atom: `atom://core/open/file?filename=%%f&line=%%l`

### Security

See [docs/SECURITY.md](docs/SECURITY.md) for supported versions and how to report vulnerabilities.

The bundle includes security measures to prevent path traversal attacks and unauthorized access:

- **Template name validation**: Rejects path traversal attempts (`..`), null bytes, and absolute paths
- **File path verification**: Ensures resolved template paths are within allowed Twig template directories
- **Parameter validation**: Line numbers must be positive integers
- **Route restrictions**: Routes should only be available in `dev` and `test` environments

**Important**: The bundle routes are automatically added to `config/routes.yaml` during installation (via Flex Recipe or Install Command). The routes are restricted to `dev` and `test` environments:

```yaml
# config/routes.yaml (created automatically)
when@dev:
    nowo_twig_inspector:
        resource: '@NowoTwigInspectorBundle/Resources/config/routes.yaml'
```

**Note**: 
- If routes aren't configured or available (e.g., in production), the bundle handles this gracefully by using fallback URLs. This prevents "route does not exist" errors when generating template links.
- The route pattern allows slashes in template names to support templates in subdirectories (e.g., `admin/users/list.html.twig`). Security validations in the controller prevent path traversal attacks.

If you need to add the routes manually, add the above configuration to your `config/routes.yaml` file.

### Template Usage Metrics

When `enable_metrics` is set to `true` (default), the bundle collects template usage statistics that are available in the Symfony Web Profiler:

- **Templates used**: List of all templates rendered in the request
- **Blocks used**: List of all blocks rendered in the request
- **Usage counts**: How many times each template/block was used
- **Totals**: Total number of unique templates and blocks

Access the metrics by:
1. Opening the Symfony Web Profiler toolbar
2. Clicking on the `</>` (Twig Inspector) icon
3. Viewing the collected statistics

**Note**: Metrics are only collected when the inspector is enabled (cookie is set to `true`).

## Requirements

- PHP >= 8.1, < 8.6
- Symfony >= 6.0 || >= 7.0 || >= 8.0
- Symfony Web Profiler Bundle (for development)
- Twig >= 3.8 || >= 4.0

**Compatibility Notes:**
- The bundle is fully compatible with Twig 3.8 through 4.0
- Twig 3.15+ deprecation warnings are handled automatically
- See [UPGRADING.md](docs/UPGRADING.md) for version-specific upgrade instructions

## Demo projects

The bundle includes three demo projects, one for each supported Symfony version. Each demo has its own `docker-compose.yml` and can be run independently:

- **Symfony 6.4 Demo**: `demo/symfony6/` (Port 8001 by default, configurable via PORT env variable)
- **Symfony 7.0 Demo**: `demo/symfony7/` (Port 8001 by default, configurable via PORT env variable)
- **Symfony 8.0 Demo**: `demo/symfony8/` (Port 8001 by default, configurable via PORT env variable)

### Quick Start with Docker

Each demo can be started independently:

```bash
# Symfony 6.4 Demo
cd demo/symfony6
docker-compose up -d
docker-compose exec php composer install
# Access at: http://localhost:8001 (default port)

# Symfony 7.0 Demo
cd demo/symfony7
docker-compose up -d
docker-compose exec php composer install
# Access at: http://localhost:8001 (default port)

# Symfony 8.0 Demo
cd demo/symfony8
docker-compose up -d
docker-compose exec php composer install
# Access at: http://localhost:8001 (default port)
```

Or use the Makefile helper commands from the `demo/` directory:

```bash
cd demo

# Start and install Symfony 6.4 demo
make up-symfony6
make install-symfony6

# Start and install Symfony 7.0 demo
make up-symfony7
make install-symfony7

# Start and install Symfony 8.0 demo
make up-symfony8
make install-symfony8
```

Each demo includes:
- Independent `docker-compose.yml` for easy setup
- Complete test suite to verify bundle integration
- Code coverage configuration (100% coverage for demo code)
- Example controller and templates
- Web Profiler integration

### Running Demo Tests

Each demo has its own test suite with code coverage:

```bash
cd demo

# Run tests for a specific demo
make test-symfony6
make test-symfony7
make test-symfony8

# Run tests with coverage for a specific demo
make test-coverage-symfony6
make test-coverage-symfony7
make test-coverage-symfony8

# Run all tests with coverage
make test-coverage-all
```

Or directly in each demo directory:

```bash
cd demo/symfony6
docker-compose exec php composer test
docker-compose exec php composer test-coverage
```

See `demo/README.md` for more details.

## Development

### Using Docker (Recommended)

```bash
# Start the container
make up

# Install dependencies
make install

# Run tests
make test

# Run tests with coverage
make test-coverage

# Run all QA checks
make qa
```

### Without Docker

```bash
composer install
composer test
composer test-coverage
composer qa
```

## Testing

The bundle has **128 tests** and targets **97.5%+ code coverage**. CI runs tests on every push (PHP 8.1–8.5 × Symfony 6.4, 7.0, 8.0), plus code style and asset build. All tests are in `tests/`.

**Current coverage**: 97.55% (358/367 lines). The remaining lines are edge cases (e.g. filesystem or `file_get_contents` failures) that are hard to test without system-level stubs; the code handles them correctly.

### Running tests

```bash
# Run all tests
composer test

# Run tests with coverage report
composer test-coverage

# View coverage report
open coverage/index.html
```

### Test Structure

- `tests/NowoTwigInspectorBundleTest.php` - Bundle class tests
- `tests/DependencyInjection/` - Extension tests
- `tests/Controller/` - Controller tests
- `tests/DataCollector/` - Data collector tests
- `tests/Twig/` - Twig extension tests
- `tests/BoxDrawingsTest.php` - Box drawings utility tests

## Code Quality

The bundle uses PHP-CS-Fixer to enforce code style (PSR-12).

```bash
# Check code style
composer cs-check

# Fix code style
composer cs-fix
```

## CI/CD

[GitHub Actions](.github/workflows/ci.yml) run on every push and pull request:

- **Tests**: PHP 8.1–8.5 × Symfony 6.4, 7.0, 8.0 (matrix excludes invalid combinations)
- **Code style**: PHP-CS-Fixer; auto-fix on push to `main`/`master`
- **Coverage**: 97.5% minimum (validated on one matrix job)
- **Assets**: Frontend build (TypeScript + SCSS) verified in CI
- **Dependabot**: Dependency update PRs

## Building Assets

The bundle uses **TypeScript** and **SCSS** with **Vite**. To build:

```bash
# Install dependencies
pnpm install

# Production build (minified)
pnpm run build

# Development build (unminified)
pnpm run build:dev

# Watch for changes
pnpm run dev
# or: pnpm run watch
```

Or using Make:

```bash
make build-assets      # Production build
make build-assets-dev  # Development build
make watch-assets      # Watch mode
```

Output is written to `src/Resources/views/assets/dist/` (used by the Twig template):

- `index.min.js` — bundled TypeScript (IIFE)
- `style.min.css` — compiled SCSS

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

## Author

Created by [Héctor Franco Aceituno](https://github.com/HecFranco) at [Nowo.tech](https://nowo.tech)

Based on [Oro Twig Inspector](https://github.com/oroinc/twig-inspector) by Oro, Inc.

