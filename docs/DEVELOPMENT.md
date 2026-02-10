# Development & Testing

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

The bundle has **128+ tests** and targets **97.5%+ code coverage**. CI runs tests on every push (PHP 8.1–8.5 × Symfony 6.4, 7.0, 8.0), plus code style and asset build. All tests are in `tests/`.

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
- **Coverage**: 97.5% minimum (validated on one matrix job)
- **Assets**: Frontend build (TypeScript + SCSS) verified in CI
- **Dependabot**: Dependency update PRs

## Building assets

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
make watch-assets     # Watch mode
```

Output is written to `src/Resources/views/assets/dist/` (used by the Twig template):

- `index.min.js` — bundled TypeScript (IIFE)
- `style.min.css` — compiled SCSS

For contribution guidelines, see [CONTRIBUTING.md](CONTRIBUTING.md).
