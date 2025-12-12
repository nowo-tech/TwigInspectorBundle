# Twig Inspector Bundle

Symfony bundle that adds the possibility to find Twig templates and blocks used for rendering HTML pages faster during development.

## Features

- ✅ Inspect Twig templates directly in the browser
- ✅ Visual overlay showing which templates render which HTML elements
- ✅ Click to open templates in your IDE
- ✅ Works with Symfony Web Profiler
- ✅ Cookie-based activation (no code changes needed)
- ✅ Supports nested blocks and templates

## Installation

```bash
composer require nowo-tech/twig-inspector-bundle:^1.0.1 --dev
```

Then, register the bundle in your `config/bundles.php`:

```php
<?php

return [
    // ...
    Nowo\TwigInspectorBundle\NowoTwigInspectorBundle::class => ['dev' => true, 'test' => true],
];
```

## Usage

1. **Enable the bundle** (only in `dev` and `test` environments)

2. **Access your application** with Symfony Web Profiler enabled

3. **In the Web Profiler toolbar**, you'll see a new icon `</>`

4. **Enable Twig Inspector** by checking the checkbox in the toolbar

5. **Hover over HTML elements** in your page to see which Twig templates rendered them

6. **Click on elements** to open the template in your IDE

## How It Works

The bundle adds HTML comments before and after every Twig block and template:

```html
<!-- ┏━ template_name [/_template/template.html.twig] #abc123 -->
<div>Your content</div>
<!-- ┗━ template_name [/_template/template.html.twig] #abc123 -->
```

When enabled, a JavaScript overlay shows which templates correspond to which HTML elements, and clicking on them opens the template in your IDE.

## Configuration

The bundle works out of the box with default settings. No configuration is required.

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

## Requirements

- PHP >= 8.1, < 8.6
- Symfony >= 6.0 || >= 7.0 || >= 8.0
- Symfony Web Profiler Bundle (for development)
- Twig >= 3.8 || >= 4.0

## Demo Projects

The bundle includes three demo projects, one for each supported Symfony version. Each demo has its own `docker-compose.yml` and can be run independently:

- **Symfony 6.4 Demo**: `demo/demo-symfony6/` (Port 8001 by default, configurable via PORT env variable)
- **Symfony 7.0 Demo**: `demo/demo-symfony7/` (Port 8001 by default, configurable via PORT env variable)
- **Symfony 8.0 Demo**: `demo/demo-symfony8/` (Port 8001 by default, configurable via PORT env variable)

### Quick Start with Docker

Each demo can be started independently:

```bash
# Symfony 6.4 Demo
cd demo/demo-symfony6
docker-compose up -d
docker-compose exec php composer install
# Access at: http://localhost:8001 (default port)

# Symfony 7.0 Demo
cd demo/demo-symfony7
docker-compose up -d
docker-compose exec php composer install
# Access at: http://localhost:8001 (default port)

# Symfony 8.0 Demo
cd demo/demo-symfony8
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
- Example controller and templates
- Web Profiler integration

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

The bundle has **100% code coverage** (144/144 lines, 35/35 methods, 11/11 classes). All tests are located in the `tests/` directory.

### Running Tests

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

The bundle uses GitHub Actions for continuous integration:

- **Tests**: Runs on PHP 8.1, 8.2, 8.3, 8.4, and 8.5 with Symfony 6.4, 7.0, and 8.0
  - PHP 8.1: Symfony 6.4 and 7.0 (Symfony 8.0 requires PHP 8.2+)
  - PHP 8.2: Symfony 6.4, 7.0, and 8.0
  - PHP 8.3: Symfony 6.4, 7.0, and 8.0
  - PHP 8.4: Symfony 6.4, 7.0, and 8.0
  - PHP 8.5: Symfony 6.4, 7.0, and 8.0
- **Code Style**: Automatically fixes code style on push
- **Coverage**: Validates 100% code coverage requirement
- **Dependabot**: Automatically updates dependencies

See `.github/workflows/ci.yml` for details.

## Building Assets

The bundle uses TypeScript and SCSS for its frontend assets. To build them:

```bash
# Install dependencies
npm install

# Build for production
npm run build

# Build for development
npm run build:dev

# Watch for changes
npm run watch
```

Or using Make:

```bash
make build-assets      # Production build
make build-assets-dev  # Development build
make watch-assets      # Watch mode
```

The built files are located in `src/Resources/assets/dist/`:
- `index.min.js` - Compiled TypeScript
- `style.min.css` - Compiled SCSS

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

## Author

Created by [Héctor Franco Aceituno](https://github.com/HecFranco) at [Nowo.tech](https://nowo.tech)

Based on [Oro Twig Inspector](https://github.com/oroinc/twig-inspector) by Oro, Inc.

