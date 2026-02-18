# Contributing Guide

Thank you for your interest in contributing to Twig Inspector Bundle! This document provides guidelines for contributing to the project.

## Code of Conduct

This project adheres to a code of conduct. By participating, you are expected to uphold this code. Please report unacceptable behavior to hectorfranco@nowo.com.

## How can I contribute?

### Reporting Bugs

If you find a bug, please:

1. **Check that the bug hasn't already been reported** in the [issues](https://github.com/nowo-tech/twig-inspector-bundle/issues)
2. **Create a new issue** with:
   - A descriptive title
   - Steps to reproduce the problem
   - Expected behavior vs. actual behavior
   - PHP, Symfony, and bundle versions
   - Screenshots if relevant

### Suggesting Enhancements

Enhancement suggestions are welcome:

1. **Check that the enhancement hasn't already been suggested** in the [issues](https://github.com/nowo-tech/twig-inspector-bundle/issues)
2. **Create a new issue** with:
   - A descriptive title
   - Detailed description of the proposed enhancement
   - Use cases and benefits
   - Possible implementations (if you have them)

### Contributing Code

#### Development Environment Setup

1. **Fork the repository** on GitHub
2. **Clone your fork**:
   ```bash
   git clone https://github.com/your-username/twig-inspector-bundle.git
   cd twig-inspector-bundle
   ```
3. **Install dependencies**:
   ```bash
   # With Docker (recommended)
   make install
   
   # Without Docker
   composer install
   ```

#### Code Standards

The project follows these standards:

- **PSR-12**: PHP code style
- **PHP 8.1+**: Modern PHP features
- **Strict type hints**: `declare(strict_types=1);` in all files
- **PHP-CS-Fixer**: Used to maintain code consistency

**Before committing**:

```bash
# Check code style
make cs-check
# or
composer cs-check

# Automatically fix code style
make cs-fix
# or
composer cs-fix
```

#### Supported versions and CI

CI runs tests on **multiple PHP and Symfony versions**:

- **PHP**: 8.1, 8.2, 8.3, 8.4, 8.5
- **Symfony**: 6.4, 7.0, 8.0 (with exclusions for incompatible pairs, e.g. PHP 8.1 + Symfony 7/8, PHP 8.2/8.3 + Symfony 8.0)

Code and tests must work on all matrix combinations. When behavior differs between versions (e.g. optional packages, API changes), use `markTestSkipped()` or version checks instead of assuming a single environment.

#### Tests

**The project requires a minimum of 90% code coverage**. All tests must pass on every CI matrix combination before merging.

**Note**: Some edge cases (e.g., `file_get_contents` returning false, filesystem permission errors) are difficult to test without advanced PHP extensions (uopz/runkit7) or system-level permission changes. These cases are handled correctly in the code, and 90% is our realistic coverage target.

```bash
# Run all tests
make test
# or
composer test

# Run tests with coverage
make test-coverage
# or
composer test-coverage

# View coverage report
open coverage/index.html
```

**Test structure**:
- Tests must be in the `tests/` directory
- Each class must have its corresponding test
- Tests must be descriptive and cover edge cases
- Use mocks when appropriate

#### Pull Request Process

1. **Create a branch** from `main`:
   ```bash
   git checkout -b feature/my-new-feature
   # or
   git checkout -b fix/my-fix
   ```

2. **Make your changes**:
   - Write clean and well-documented code
   - Add tests for new features
   - Make sure all tests pass
   - Verify that coverage is at least 90%
   - Run `make qa` to verify everything

3. **Commit your changes**:
   ```bash
   git add .
   git commit -m "feat: feature description"
   # or
   git commit -m "fix: fix description"
   ```
   
   **Commit conventions**:
   - `feat:` New feature
   - `fix:` Bug fix
   - `docs:` Documentation changes
   - `test:` Add or modify tests
   - `refactor:` Code refactoring
   - `style:` Formatting changes (no functionality impact)
   - `chore:` Maintenance tasks

4. **Push to your fork**:
   ```bash
   git push origin feature/my-new-feature
   ```

5. **Create a Pull Request** on GitHub:
   - Clearly describe the changes
   - Mention any related issues
   - Add screenshots if relevant
   - Make sure CI passes

#### Checklist before PR

- [ ] Code follows PSR-12 standards
- [ ] Ran `make cs-fix` (or `composer cs-fix`)
- [ ] All tests pass (`make test`)
- [ ] Code coverage is at least 90% (`make test-coverage`)
- [ ] Added tests for new features
- [ ] Documentation is updated (if necessary)
- [ ] docs/CHANGELOG.md is updated (if necessary)
- [ ] Code is well commented
- [ ] No warnings or errors from PHPStan/Psalm (if used)

## Project Structure

```
twig-inspector-bundle/
├── src/                    # Bundle source code
│   ├── Controller/         # Controllers
│   ├── DataCollector/      # Data collectors for Web Profiler
│   ├── DependencyInjection/ # Bundle configuration
│   ├── Resources/          # Resources (templates, assets)
│   └── Twig/               # Twig extensions and nodes
├── tests/                  # Tests
├── demo/                   # Demo projects (Symfony 6.4, 7.0, 8.0)
├── .github/                # GitHub configuration
└── docs/                   # Additional documentation
```

## Asset Development

The bundle includes TypeScript and SCSS assets:

```bash
# Install Node dependencies
pnpm install

# Build for production
pnpm run build
# or
make build-assets

# Build for development
ppnpm run build:dev
# or
make build-assets-dev

# Watch mode
pnpm run watch
# or
make watch-assets
```

The build compiles TypeScript (`src/Resources/assets/src/*.ts`) to JavaScript. Output goes to `src/Resources/views/assets/dist/` (used by the collector Twig template). The bundle needs both: TS source for the build pipeline and compiled JS for runtime. Distributable assets in `src/Resources/public/assets/` are copied to the host project with `assets:install`.

## Demos

The project includes three independent demos to test the bundle with different Symfony versions. Each demo runs with **FrankenPHP** and **Caddy**; **HTTPS** is available where configured (see each demo’s `Caddyfile` and `docker-compose.yml`).

- `demo/symfony6/` - Symfony 6.4
- `demo/symfony7/` - Symfony 7.0
- `demo/symfony8/` - Symfony 8.0

To run a demo:

```bash
# Install dependencies
make install-symfony6  # or install-symfony7, install-symfony8

# Start containers
cd demo/symfony6 && docker-compose up -d

# Access the demo
# http://localhost:8001
```

## Branching Policy

For detailed information about branch naming conventions, workflow, and release process, see [BRANCHING.md](BRANCHING.md).

## Questions

If you have questions about how to contribute, you can:

- Open an issue on GitHub
- Contact the maintainers at hectorfranco@nowo.com

## Acknowledgments

Thank you for contributing to Twig Inspector Bundle. Your help makes this project better for everyone.
