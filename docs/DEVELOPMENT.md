# Development & Testing


## Table of contents

- [Using Docker (Recommended)](#using-docker-recommended)
- [Without Docker](#without-docker)
- [Testing](#testing)
  - [Running tests](#running-tests)
  - [Test structure](#test-structure)
- [Code quality](#code-quality)
- [CI/CD](#cicd)
- [Building assets](#building-assets)

## Using Docker (Recommended)

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

## Without Docker

```bash
composer install
composer test
composer test-coverage
composer qa
```

## Testing

The bundle has **150+ tests** and targets **90%+ code coverage**. CI runs tests on **multiple PHP and Symfony versions** (PHP 8.1–8.5 × Symfony 6.4, 7.0, 8.0; see exclusions in `.github/workflows/ci.yml`), plus code style and asset build. All tests are in `tests/`. Code and tests must be compatible with every matrix combination.

**Current coverage**: 90%+ (validated in CI). Remaining uncovered code is mostly edge cases (e.g. `file_get_contents` failure when file is unreadable) that are hard to test without system-level stubs; the code handles them correctly.

### Running tests

```bash
# Run all tests
composer test

# Run tests with coverage report
composer test-coverage

# View coverage report
open coverage/index.html
```

### Test structure

- `tests/NowoTwigInspectorBundleTest.php` - Bundle class tests
- `tests/DependencyInjection/` - Extension tests
- `tests/Controller/` - Controller tests
- `tests/DataCollector/` - Data collector tests
- `tests/Twig/` - Twig extension tests
- `tests/BoxDrawingsTest.php` - Box drawings utility tests

## Code quality

The bundle uses PHP-CS-Fixer to enforce code style (PSR-12).

```bash
# Check code style
composer cs-check

# Fix code style
composer cs-fix
```

## CI/CD

[GitHub Actions](../.github/workflows/ci.yml) run on every push and pull request:

- **Tests**: PHP 8.1–8.5 × Symfony 6.4, 7.0, 8.0 (matrix excludes invalid combinations)
- **Code style**: PHP-CS-Fixer; auto-fix on push to `main`/`master`
- **Coverage**: 90% minimum (validated on PHP 8.2 + Symfony 7.0 and in the standalone coverage job)
- **Assets**: Frontend build (TypeScript + SCSS) verified in CI
- **Dependabot**: Dependency update PRs

## Building assets

The bundle uses **TypeScript** and **SCSS** with **Vite**. The flow is: TS (source) → Vite compiles → JS (output used by projects that install the bundle). **Both are required**: TS for development/build, and the compiled JS for runtime.

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
make watch-assets     # Watch mode
```

**Asset locations:**

| Path | Purpose |
|------|---------|
| `src/Resources/assets/src/*.ts` | TypeScript source — compiled by Vite |
| `src/Resources/views/assets/dist/` | Build output — used by the collector Twig template (`@NowoTwigInspector`) |
| `src/Resources/public/assets/` | Distributable assets — copied to the host project with `assets:install` |

Main output in `src/Resources/views/assets/dist/`:

- `index.min.js` — bundled TypeScript (IIFE)
- `style.min.css` — compiled SCSS

For contribution guidelines, see [CONTRIBUTING.md](CONTRIBUTING.md).
