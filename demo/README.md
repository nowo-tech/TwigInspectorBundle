# Twig Inspector Bundle - Demo

This directory contains three demo projects, one for each supported Symfony version (6.4, 7.0, and 8.0), demonstrating the usage of the Twig Inspector Bundle.

**Bundle source:** Each demo uses the bundle from the **parent repository** (path repository in `composer.json`), not from Packagist. When you run `composer install` or `composer update` inside a demo, `nowo-tech/twig-inspector-bundle` is resolved from the bundle root (`../../`), so you always test against the current code in the repo.

**Makefiles:** The `demo/` directory has a central [Makefile](Makefile) (e.g. `make up-symfony8`). Each demo also has its **own Makefile** inside `demo/symfony6/`, `demo/symfony7/`, and `demo/symfony8/`, so you can run `make up`, `make install`, `make link-bundle`, etc. from inside any demo folder.

## Features

- Three separate demo projects for Symfony 6.4, 7.0, and 8.0
- Simple demo pages with multiple Twig blocks
- **Symfony Web Profiler integration** — Fully configured and enabled
  - Toolbar at the bottom of pages
  - Profiler accessible at `/_profiler`
  - WDT (Web Debug Toolbar) at `/_wdt`
- Visual demonstration of template inspection
- **Docker stack: FrankenPHP + Caddy** — Demos run with [FrankenPHP](https://frankenphp.dev/) and [Caddy](https://caddyserver.com/). By default the **Caddyfiles only expose HTTP** (`:80` in the container → host `PORT`, usually **8001**). See `docker/frankenphp/Caddyfile`, `Caddyfile.dev`, and `docker-compose.yml` in each demo.
- Independent Docker containers for each demo

## Requirements

- Docker and Docker Compose
- Or PHP 8.1+ to 8.5 (8.2+ for Symfony 8.0) and Composer (for local development)

## Quick Start with Docker

Each demo has its own `docker-compose.yml` and can be run independently. You can start any demo you want:

**Important**: Before starting a demo, copy `.env.example` to `.env`:
```bash
cd demo/symfony6
cp .env.example .env
# Optionally generate a new APP_SECRET: openssl rand -hex 32
# The .env.example includes: APP_ENV=dev, APP_SECRET (placeholder), APP_DEBUG=1, PORT=8001
# Note: Symfony 7.0 and 8.0 also include DEFAULT_URI for routing configuration
```

### Symfony 6.4 Demo

```bash
# Navigate to the demo directory
cd demo/symfony6

# Copy .env.example to .env if not already done
cp .env.example .env

# Start containers
docker-compose up -d

# Install dependencies
docker-compose exec php composer install

# Access at: http://localhost:8001 (port configured in .env file)
```

Or using the Makefile from the `demo/` directory:

```bash
cd demo
make up-symfony6
make install-symfony6

# Or verify that the demo is running correctly
make verify DEMO=symfony6
```

### Symfony 7.0 Demo

```bash
# Navigate to the demo directory
cd demo/symfony7

# Copy .env.example to .env if not already done
cp .env.example .env

# Start containers
docker-compose up -d

# Install dependencies
docker-compose exec php composer install

# Access at: http://localhost:8001 (port configured in .env file, default: 8001)
```

Or using the Makefile:

```bash
cd demo
make up-symfony7
make install-symfony7

# Or verify that the demo is running correctly
make verify DEMO=symfony7
```

### Symfony 8.0 Demo

```bash
# Navigate to the demo directory
cd demo/symfony8

# Copy .env.example to .env if not already done
cp .env.example .env

# Start containers
docker-compose up -d

# Install dependencies
docker-compose exec php composer install

# Access at: http://localhost:8001 (port configured in .env file, default: 8001)
```

Or using the Makefile:

```bash
cd demo
make up-symfony8
make install-symfony8

# Or verify that the demo is running correctly
make verify DEMO=symfony8
```

### Web Profiler

All demos come with **Symfony Web Profiler** pre-configured and enabled:

- **Toolbar**: Visible at the bottom of every page in `dev` environment
- **Profiler**: Access detailed profiling information at `/_profiler`
- **WDT**: Web Debug Toolbar available at `/_wdt`
- **Twig Inspector Integration**: The `</>` icon appears in the toolbar

The Web Profiler is automatically enabled in `dev` and `test` environments. No additional configuration is needed.

### Enable Twig Inspector

For each demo:

1. Look at the bottom of the page for the Symfony Web Profiler toolbar
2. Find the `</>` icon (Twig Inspector)
3. Check the "enable" checkbox
4. The page will reload with inspection enabled
5. Hover over HTML elements to see which templates rendered them
6. Click on elements to open templates in your IDE

### Seeing bundle changes in Docker (menu, toolbar link, templates)

Each demo mounts the **bundle root** at `/var/twig-inspector-bundle` so code and template changes in the repo are visible in the container. If you ran `composer install` on the **host**, the symlink in `vendor/` points to a path that does not exist inside the container, so the demo won’t show your latest changes.

**Do this once per demo** (from the demo directory, e.g. `demo/symfony8`):

```bash
# Use the mounted bundle inside the container
docker-compose exec php composer config repositories.0.url /var/twig-inspector-bundle
docker-compose exec php composer update nowo-tech/twig-inspector-bundle --no-interaction

# Clear Symfony cache so templates and config are refreshed
docker-compose exec php php bin/console cache:clear
```

After that, changes in the bundle (e.g. collector menu, toolbar link, templates) will appear after reloading the page. If they don’t, run `cache:clear` again.

### Stop Containers

Stop a specific demo:

```bash
# Stop Symfony 6.4 demo
cd demo/symfony6
docker-compose down

# Or using Makefile
cd demo
make down-symfony6
```

Similar commands for Symfony 7.0 (`make down-symfony7`) and Symfony 8.0 (`make down-symfony8`).

## Local Development (without Docker)

### Symfony 6.4 Demo

1. **Navigate to the demo directory:**
   ```bash
   cd demo/symfony6
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Start the Symfony server:**
   ```bash
   symfony server:start
   ```

### Symfony 7.0 Demo

1. **Navigate to the demo directory:**
   ```bash
   cd demo/symfony7
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Start the Symfony server:**
   ```bash
   symfony server:start
   ```

### Symfony 8.0 Demo

1. **Navigate to the demo directory:**
   ```bash
   cd demo/symfony8
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Start the Symfony server:**
   ```bash
   symfony server:start
   ```

## What's Included

Each demo includes:

- **DemoController**: A simple controller with a demo page
- **Template**: A Twig template with multiple blocks and includes
- **Docker setup**: **FrankenPHP** image (`dunglas/frankenphp`), `docker/frankenphp/Caddyfile` (worker in prod-style runs) and `Caddyfile.dev` (no worker in dev), entrypoint that selects the dev Caddyfile when `APP_ENV=dev`, and `docker-compose.yml` mapping host `PORT` → container **:80**
- **Dockerfile**: FrankenPHP-based image with Composer; not PHP-FPM + Nginx
- **Web Profiler**: Pre-configured Symfony Web Profiler for development
  - Enabled in `dev` and `test` environments
  - Accessible at `/_profiler` route
  - Toolbar visible at the bottom of pages
- **Twig Inspector configuration**: Example file with common options
  - Located at `config/packages/nowo_twig_inspector.yaml`
  - For the full option list see [docs/CONFIGURATION.md](../docs/CONFIGURATION.md) (overlay theme, shortcuts, regex exclusions, etc.)

## Demo Structure

```
demo/
├── symfony6/               # Symfony 6.4 demo (host PORT default 8001)
│   ├── docker-compose.yml  # Publishes PORT:80, mounts bundle at /var/twig-inspector-bundle
│   ├── Dockerfile          # FrankenPHP (Caddy + PHP)
│   ├── docker/frankenphp/  # Caddyfile, Caddyfile.dev
│   ├── Makefile            # up, install, ensure-up, test, …
│   ├── composer.json       # Path repo to ../../
│   ├── .env.example
│   ├── config/packages/nowo_twig_inspector.yaml
│   └── ...
├── symfony7/               # Symfony 7.0 demo (same layout)
│   └── ...
├── symfony8/               # Symfony 8.0 demo (same layout)
│   └── ...
└── Makefile                # Helper commands for all demos (up-symfony6, …)
```

Each demo is independent: own `docker-compose.yml`, Dockerfile, and FrankenPHP/Caddy config. See [docs/DEMO-FRANKENPHP.md](../docs/DEMO-FRANKENPHP.md) for dev vs prod behaviour.

**Note**: Before starting a demo, copy `.env.example` to `.env` in the demo directory:
```bash
cd demo/symfony6
cp .env.example .env
# Edit .env and set your APP_SECRET (or generate one with: openssl rand -hex 32)
# The .env.example file includes standard Symfony variables:
# - APP_ENV=dev
# - APP_SECRET=change_this_secret_key_to_a_random_value (replace with your secret)
# - APP_DEBUG=1
# - PORT=8001 (change if needed for multiple demos)
# - DEFAULT_URI=http://localhost (required for Symfony 7.0 and 8.0 routing configuration)
```

## How It Works

The bundle automatically:

1. Adds HTML comments before and after every Twig block and template
2. Provides a JavaScript overlay that shows which templates correspond to which HTML elements
3. Allows clicking on elements to open templates in your IDE
4. Integrates with Symfony Web Profiler toolbar

## Configuration

### IDE Configuration

To open templates in your IDE, configure the `ide` option in each demo's `config/packages/framework.yaml`:

```yaml
framework:
    ide: 'phpstorm://open?file=%%f&line=%%l'
```

Supported IDEs:
- **PhpStorm**: `phpstorm://open?file=%%f&line=%%l`
- **VS Code**: `vscode://file/%%f:%%l`
- **Sublime Text**: `subl://open?url=file://%%f&line=%%l`
- **Atom**: `atom://core/open/file?filename=%%f&line=%%l`

### Twig Inspector Bundle Configuration

**Note**: The configuration file is **optional**. The bundle works with default settings without any configuration file.

#### Automatic Installation

**In these demos**: The bundle is loaded from the repo via the path repository in each demo's `composer.json` (`repositories` → `path` `../../`). No Packagist install is used.

**If you install the bundle from Packagist** in your own project: Flex will create the config and routes during `composer require`. No command needed.

**If installing manually or from Git** (without path repo): You can create the configuration file and set up routes using the install command:

```bash
php bin/console nowo:twig-inspector:install
```

**Note (Docker):** The bundle is only loaded in `dev` and `test`. Each demo's `docker-compose.yml` sets `APP_ENV=dev` so the command is available when you run `docker-compose exec php php bin/console ...`. If you see *"There are no commands defined in the nowo:twig-inspector namespace"*, run with `--env=dev` or ensure `APP_ENV=dev` in the container.

This command creates:
- Configuration file with all available options and helpful comments
- Routes file (`config/routes.yaml`) with the bundle route import (if needed)

#### Example Configuration

Each demo includes an **example** configuration file at `config/packages/nowo_twig_inspector.yaml` with the most common options. The full reference (including `overlay_theme`, `keyboard_shortcut`, regex exclusions, etc.) is in [docs/CONFIGURATION.md](../docs/CONFIGURATION.md).

The example configuration shows:

```yaml
nowo_twig_inspector:
    # Template extensions to inspect
    enabled_extensions:
        - '.html.twig'
    
    # Templates to exclude (supports wildcards)
    excluded_templates:
        # - 'admin/*'
        # - 'email/*.html.twig'
    
    # Blocks to exclude (supports wildcards)
    excluded_blocks:
        # - 'javascript'
        # - 'head_*'
    
    # Enable template usage metrics
    enable_metrics: true
    
    # Inject on sub-requests (e.g. when main content is rendered as fragment)
    inject_on_sub_requests: false
    
    # Custom cookie name
    cookie_name: 'twig_inspector_is_active'
```

**Key Features:**
- **Exclude templates/blocks**: Use wildcards (`*`) to exclude specific templates or blocks from inspection
- **Template metrics**: View template and block usage statistics in the Web Profiler
- **Performance**: Automatic optimization when inspector is disabled
- **Flexible configuration**: Customize which templates are inspected

For more details, see the main [README.md](../README.md#configuration).

## Testing

Each demo includes its own test suite to verify that the Twig Inspector Bundle works correctly with the specific Symfony version.

### Run Tests

```bash
# Run tests for Symfony 6.4 demo
cd demo/symfony6
vendor/bin/phpunit

# Run tests for Symfony 7.0 demo
cd demo/symfony7
vendor/bin/phpunit

# Run tests for Symfony 8.0 demo
cd demo/symfony8
vendor/bin/phpunit
```

Or using the Makefile from the `demo/` directory:

```bash
cd demo

# Run tests for a specific demo
make test-symfony6
make test-symfony7
make test-symfony8

# Run all tests
make test-all
```

### Run Tests with Code Coverage

Each demo includes code coverage configuration. To generate coverage reports:

```bash
# Run tests with coverage for a specific demo
cd demo/symfony6
docker-compose exec php composer test-coverage

# Or using the Makefile
cd demo
make test-coverage-symfony6
make test-coverage-symfony7
make test-coverage-symfony8

# Run all demos with coverage
make test-coverage-all
```

Coverage reports are generated in:
- HTML: `demo/symfony6/coverage/index.html` (and similar for other demos)
- Clover XML: `demo/symfony6/coverage.xml` (and similar for other demos)

### Test Structure

Each demo includes:

- **Controller Tests**: Verify that the demo controller works correctly
- **Bundle Integration Tests**: Verify that the Twig Inspector Bundle is properly integrated
- **Code Coverage**: 100% coverage for demo application code (DemoController and Kernel are fully tested)

## Verification

You can verify that all demos are running and responding correctly:

```bash
cd demo

# Verify all demos (starts and checks each one sequentially)
make verify-all

# Or verify a specific demo
make verify DEMO=symfony6
```

The `verify-all` command will:
1. Start each demo sequentially (symfony6, symfony7, symfony8)
2. Check that each demo responds with HTTP 200
3. Show a summary with successful/failed demos
4. Display access URLs for successfully verified demos

**Example output:**
```
🚀 Starting and verifying all demos...

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📦 Processing Symfony 6.4 demo (symfony6)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🔍 Verifying Symfony 6.4 demo...
✅ Symfony 6.4 demo is running and responding at http://localhost:8001 (HTTP 200)
✅ Symfony 6.4 demo verified successfully

[... similar for other demos ...]

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📊 Verification Summary
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✅ Successful: 3/3
✅ All demos verified successfully!
```

## Troubleshooting

### Ports already in use

All demos use port **8001** by default. If this port is already in use, you can customize it in two ways:

**Option 1: Using environment variable (Recommended)**

Create a `.env` file in the demo directory:

```bash
# demo/symfony6/.env (or symfony7, symfony8)
PORT=8001  # Default port for all demos
# Change to a different port if you need to run multiple demos simultaneously
PORT=8002  # Example: if you want to run a second demo
```

Then restart the containers:

```bash
docker-compose down
docker-compose up -d
```

**Option 2: Directly in docker-compose.yml**

Edit the `docker-compose.yml` file:

```yaml
ports:
  - "8083:80"  # Change to any available port
```

**Note**: All demos use port **8001** by default. If you need to run multiple demos simultaneously, you can change the PORT variable in each demo's `.env` file to use different ports (e.g., 8001, 8002, 8003).

The Makefile automatically checks if a port is in use and stops any containers using it before starting a new demo.

### Web Profiler not showing

Make sure you're accessing the application in `dev` environment. The bundle only works in `dev` and `test` environments.

### IDE links not working

Configure the `ide` option in `config/packages/framework.yaml` as shown above.

## License

This demo is part of the Twig Inspector Bundle project and follows the same MIT license.
