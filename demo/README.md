# Twig Inspector Bundle - Demo

This directory contains three demo projects, one for each supported Symfony version (6.4, 7.0, and 8.0), demonstrating the usage of the Twig Inspector Bundle.

## Features

- Three separate demo projects for Symfony 6.4, 7.0, and 8.0
- Simple demo pages with multiple Twig blocks
- **Symfony Web Profiler integration** - Fully configured and enabled
  - Toolbar at the bottom of pages
  - Profiler accessible at `/_profiler`
  - WDT (Web Debug Toolbar) at `/_wdt`
- Visual demonstration of template inspection
- Docker setup for easy development
- Independent Docker containers for each demo

## Requirements

- Docker and Docker Compose
- Or PHP 8.1+ to 8.5 (8.2+ for Symfony 8.0) and Composer (for local development)

## Quick Start with Docker

Each demo has its own `docker-compose.yml` and can be run independently. You can start any demo you want:

### Symfony 6.4 Demo

```bash
# Navigate to the demo directory
cd demo/demo-symfony6

# Start containers
docker-compose up -d

# Install dependencies
docker-compose exec php composer install

# Access at: http://localhost:8001 (default port, configurable via PORT env variable)
```

Or using the Makefile from the `demo/` directory:

```bash
cd demo
make up-symfony6
make install-symfony6
```

### Symfony 7.0 Demo

```bash
# Navigate to the demo directory
cd demo/demo-symfony7

# Start containers
docker-compose up -d

# Install dependencies
docker-compose exec php composer install

# Access at: http://localhost:8001 (default port, configurable via PORT env variable)
```

Or using the Makefile:

```bash
cd demo
make up-symfony7
make install-symfony7
```

### Symfony 8.0 Demo

```bash
# Navigate to the demo directory
cd demo/demo-symfony8

# Start containers
docker-compose up -d

# Install dependencies
docker-compose exec php composer install

# Access at: http://localhost:8001 (default port, configurable via PORT env variable)
```

Or using the Makefile:

```bash
cd demo
make up-symfony8
make install-symfony8
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

### Stop Containers

Stop a specific demo:

```bash
# Stop Symfony 6.4 demo
cd demo/demo-symfony6
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
   cd demo/demo-symfony6
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
   cd demo/demo-symfony7
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
   cd demo/demo-symfony8
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
- **Docker Setup**: Complete Docker configuration with PHP-FPM and Nginx
- **Dockerfile**: Custom PHP-FPM image with Composer pre-installed
- **Web Profiler**: Pre-configured Symfony Web Profiler for development
  - Enabled in `dev` and `test` environments
  - Accessible at `/_profiler` route
  - Toolbar visible at the bottom of pages

## Demo Structure

```
demo/
├── demo-symfony6/          # Symfony 6.4 demo (Port 8001 by default)
│   ├── docker-compose.yml  # Independent docker-compose for this demo
│   ├── Dockerfile          # PHP-FPM image with Composer
│   ├── nginx.conf          # Nginx configuration
│   ├── composer.json       # Dependencies including Web Profiler
│   ├── .env                # Optional: PORT variable (default: 8001)
│   └── ...
├── demo-symfony7/          # Symfony 7.0 demo (Port 8001 by default)
│   ├── docker-compose.yml  # Independent docker-compose for this demo
│   ├── Dockerfile          # PHP-FPM image with Composer
│   ├── nginx.conf          # Nginx configuration
│   ├── composer.json       # Dependencies including Web Profiler
│   ├── .env                # Optional: PORT variable (default: 8001)
│   └── ...
├── demo-symfony8/          # Symfony 8.0 demo (Port 8001 by default)
│   ├── docker-compose.yml  # Independent docker-compose for this demo
│   ├── Dockerfile          # PHP-FPM image with Composer
│   ├── nginx.conf          # Nginx configuration
│   ├── composer.json       # Dependencies including Web Profiler
│   ├── .env                # Optional: PORT variable (default: 8001)
│   └── ...
└── Makefile                # Helper commands for all demos
```

Each demo is completely independent with its own `docker-compose.yml` and `nginx.conf`.

## How It Works

The bundle automatically:

1. Adds HTML comments before and after every Twig block and template
2. Provides a JavaScript overlay that shows which templates correspond to which HTML elements
3. Allows clicking on elements to open templates in your IDE
4. Integrates with Symfony Web Profiler toolbar

## IDE Configuration

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

## Testing

Each demo includes its own test suite to verify that the Twig Inspector Bundle works correctly with the specific Symfony version.

### Run Tests

```bash
# Run tests for Symfony 6.4 demo
cd demo/demo-symfony6
vendor/bin/phpunit

# Run tests for Symfony 7.0 demo
cd demo/demo-symfony7
vendor/bin/phpunit

# Run tests for Symfony 8.0 demo
cd demo/demo-symfony8
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

### Test Structure

Each demo includes:

- **Controller Tests**: Verify that the demo controller works correctly
- **Bundle Integration Tests**: Verify that the Twig Inspector Bundle is properly integrated

## Troubleshooting

### Ports already in use

All demos use port **8001** by default. If this port is already in use, you can customize it in two ways:

**Option 1: Using environment variable (Recommended)**

Create a `.env` file in the demo directory:

```bash
# demo/demo-symfony6/.env
PORT=8083
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

**Note**: All demos use port 8001 by default. You can run multiple demos by changing the PORT variable in each demo's `.env` file.

### Web Profiler not showing

Make sure you're accessing the application in `dev` environment. The bundle only works in `dev` and `test` environments.

### IDE links not working

Configure the `ide` option in `config/packages/framework.yaml` as shown above.

## License

This demo is part of the Twig Inspector Bundle project and follows the same MIT license.
