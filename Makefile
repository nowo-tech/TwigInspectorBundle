# Makefile for Twig Inspector Bundle
# Simplifies Docker commands for development.
# All dev targets (test, install, qa, etc.) use the root docker-compose.yml.

COMPOSE_FILE := docker-compose.yml
# Prefer Compose V2 plugin (GitHub Actions / modern Docker Desktop); fall back to docker-compose V1 (REQ-MAKE-010).
COMPOSE_BIN := $(shell docker compose version >/dev/null 2>&1 && echo "docker compose" || echo "docker-compose")
COMPOSE     := $(COMPOSE_BIN) -f $(COMPOSE_FILE)
SERVICE_PHP := php

.PHONY: help up down down-dev build shell install test test-unit test-integration test-coverage coverage-php-percent coverage-ts-percent cs-check cs-fix qa clean setup-hooks test-up test-down test-shell assets assets-build assets-test assets-dev assets-watch assets-clean test-ts release-check release-check-demos demo-smoke composer-sync rector rector-dry phpstan update validate check-no-cursor-coauthor check-open-prs strip-cursor-coauthor-from-history

# Default target
help:
	@echo "Twig Inspector Bundle - Development Commands"
	@echo ""
	@echo "Usage: make <target>"
	@echo ""
	@echo "Targets:"
	@echo "  up            Start Docker container"
	@echo "  down          Stop Docker container"
	@echo "  down-dev      Stop root dev container (alias of down; non-destructive)"
	@echo "  build         Rebuild Docker image (no cache)"
	@echo "  shell         Open shell in container"
	@echo "  install       Install Composer and pnpm dependencies"
	@echo "  assets        Build TypeScript and SCSS assets (pnpm install + pnpm run build in container)"
	@echo "  test          Run all PHPUnit tests (unit + integration)"
	@echo "  test-unit     Run unit tests only (tests/Unit)"
	@echo "  test-integration Run integration tests only (tests/Integration)"
	@echo "  test-coverage Run tests with code coverage (starts container if needed)"
	@echo "  cs-check      Check code style"
	@echo "  cs-fix        Fix code style"
	@echo "  rector        Apply Rector refactoring"
	@echo "  rector-dry    Run Rector in dry-run mode"
	@echo "  phpstan       Run PHPStan static analysis"
	@echo "  qa            Run all QA checks (cs-check + test)"
	@echo "  release-check Pre-release: cs-fix, cs-check, rector-dry, phpstan, test-coverage, test-ts, demo healthchecks"
	@echo "  demo-smoke    REQ-TEST-011: boot FrankenPHP demo and assert HTTP 200"
	@echo "  check-open-prs REQ-REL-003: fail if unresolved open PRs"
	@echo "  composer-sync Validate composer.json and align composer.lock (no install)"
	@echo "  clean         Remove vendor and cache"
	@echo "  update        Update composer.lock (composer update)"
	@echo "  validate      Run composer validate --strict"
	@echo ""
	@echo "Bundle-specific:"
	@echo "  test-up       Start test container"
	@echo "  test-down     Stop test container"
	@echo "  test-shell    Open shell in test container"
	@echo "  assets-build  Same as assets (pnpm run build in container)"
	@echo "  test-ts       Run TypeScript (Vitest) tests with coverage (pnpm run test:coverage)"
	@echo "  assets-test   Alias of test-ts"
	@echo "  assets-dev    Build assets in development mode"
	@echo "  assets-watch  Watch assets for changes"
	@echo "  assets-clean  Clean built assets"
	@echo "  setup-hooks   Install git pre-commit hooks"
	@echo ""
	@echo "Demos:"
	@echo "  (use make -C demo or make -C demo/symfonyX)"
	@echo ""

# Rebuild Docker image (no cache)
build:
	$(COMPOSE) build --no-cache

# Build and start container (root $(COMPOSE))
up:
	$(COMPOSE) build
	$(COMPOSE) up -d
	@echo "Installing dependencies..."
	$(COMPOSE) exec $(SERVICE_PHP) composer install --no-interaction
	@echo "✅ Container ready!"

# Stop container
down:
	$(COMPOSE) down --remove-orphans

# REQ-MAKE-007: stop root dev environment without removing volumes
down-dev: down

# Open shell in container
shell:
	$(COMPOSE) exec $(SERVICE_PHP) sh

# Install dependencies (composer + pnpm for assets)
install: ensure-up
	$(COMPOSE) exec -T $(SERVICE_PHP) composer install
	$(COMPOSE) exec -T -e CI=true $(SERVICE_PHP) pnpm install
	@echo "✅ Dependencies installed (composer + pnpm)."

# Ensure root container is running (start if not). Used by cs-fix, cs-check, qa, install, test, test-coverage.
ensure-up:
	@if ! $(COMPOSE) exec -T $(SERVICE_PHP) true 2>/dev/null; then \
		echo "Starting container (root $(COMPOSE))..."; \
		$(COMPOSE) up -d; \
		sleep 3; \
		$(COMPOSE) exec -T $(SERVICE_PHP) composer install --no-interaction; \
	fi

# Run all tests (unit + integration)
test: ensure-up
	$(COMPOSE) exec $(SERVICE_PHP) composer test

# Run unit tests only (tests/Unit)
test-unit: ensure-up
	$(COMPOSE) exec $(SERVICE_PHP) vendor/bin/phpunit --testsuite unit

# Run integration tests only (tests/Integration)
test-integration: ensure-up
	$(COMPOSE) exec $(SERVICE_PHP) vendor/bin/phpunit --testsuite integration

# Run tests with coverage (no -T so coverage is shown in console with colors)
test-coverage: ensure-up
	$(COMPOSE) exec $(SERVICE_PHP) composer test-coverage | tee coverage-php.txt
	sh ./.scripts/php-coverage-percent.sh coverage-php.txt

# Start container (same as up; alias for test workflow)
test-up:
	$(COMPOSE) build
	$(COMPOSE) up -d
	@echo "Installing dependencies..."
	$(COMPOSE) exec -T $(SERVICE_PHP) composer install --no-interaction
	@echo "✅ Container ready!"

# Stop container
test-down:
	$(COMPOSE) down

# Open shell in container
test-shell:
	$(COMPOSE) exec $(SERVICE_PHP) sh

# Check code style
cs-check: ensure-up
	$(COMPOSE) exec -T $(SERVICE_PHP) composer cs-check

# Fix code style
cs-fix: ensure-up
	$(COMPOSE) exec -T $(SERVICE_PHP) composer cs-fix

rector: ensure-up
	$(COMPOSE) exec -T $(SERVICE_PHP) composer rector

rector-dry: ensure-up
	$(COMPOSE) exec -T $(SERVICE_PHP) composer rector-dry

phpstan: ensure-up
	$(COMPOSE) exec -T $(SERVICE_PHP) composer phpstan

# Run all QA
qa: ensure-up
	$(COMPOSE) exec -T $(SERVICE_PHP) composer qa

# Update composer.lock
update: ensure-up
	$(COMPOSE) exec -T $(SERVICE_PHP) composer update --no-interaction

# Validate composer.json
validate: ensure-up
	$(COMPOSE) exec -T $(SERVICE_PHP) composer validate --strict

# Pre-release: cs-fix, cs-check, rector-dry, phpstan, test-coverage, demo healthchecks
release-check: check-no-cursor-coauthor check-open-prs ensure-up composer-sync cs-fix cs-check rector-dry phpstan test-coverage test-ts release-check-demos

release-check-demos:
	@$(MAKE) -C demo release-check

demo-smoke:
	@$(MAKE) -C demo demo-smoke

composer-sync: ensure-up
	$(COMPOSE) exec -T $(SERVICE_PHP) composer validate --strict
	$(COMPOSE) exec -T $(SERVICE_PHP) composer update --no-install

# Clean vendor and cache — runs inside container
clean: ensure-up
	$(COMPOSE) exec -T $(SERVICE_PHP) sh -c "rm -rf vendor .phpunit.cache coverage coverage.xml .php-cs-fixer.cache"

# Setup git hooks for pre-commit checks
check-no-cursor-coauthor:
	@chmod +x .scripts/check-no-cursor-coauthor.sh
	@./.scripts/check-no-cursor-coauthor.sh HEAD

check-open-prs:
	@chmod +x .scripts/check-open-prs.sh
	@bash .scripts/check-open-prs.sh

setup-hooks:
	@chmod +x .githooks/pre-commit 2>/dev/null || true
	@chmod +x .githooks/commit-msg 2>/dev/null || true
	@git config core.hooksPath .githooks
	@echo "✅ Git hooks installed (.githooks — includes commit-msg for REQ-GIT-001)."

# Build assets (TypeScript and SCSS) — runs inside container
assets: ensure-up
	@echo "Building assets..."
	$(COMPOSE) exec -T -e CI=true $(SERVICE_PHP) pnpm install
	$(COMPOSE) exec -T $(SERVICE_PHP) pnpm run build
	@echo "✅ Assets built!"

# Alias: pnpm build inside container (same as assets; use 'make assets' for install + build)
assets-build: assets

# Run Vitest tests for TypeScript with coverage — runs inside container
test-ts: ensure-up
	@echo "Running TypeScript tests (Vitest) with coverage..."
	$(COMPOSE) exec -T -e CI=true $(SERVICE_PHP) pnpm install
	$(COMPOSE) exec -T $(SERVICE_PHP) pnpm run test:coverage | tee coverage-ts.txt
	sh ./.scripts/ts-coverage-percent.sh coverage-ts.txt
	@echo "✅ TypeScript tests done!"

# Alias of test-ts (deprecated name; use test-ts)
assets-test: test-ts

# Build assets in development mode — runs inside container
assets-dev: ensure-up
	@echo "Building assets in development mode..."
	$(COMPOSE) exec -T -e CI=true $(SERVICE_PHP) pnpm install
	$(COMPOSE) exec -T $(SERVICE_PHP) pnpm run build:dev
	@echo "✅ Assets built!"

# Watch assets for changes (Vite watch) — runs inside container (interactive)
assets-watch: ensure-up
	@echo "Watching assets for changes..."
	$(COMPOSE) exec -e CI=true $(SERVICE_PHP) sh -c "pnpm install && pnpm run watch"

# Clean built assets — runs inside container
assets-clean: ensure-up
	@echo "Cleaning built assets..."
	$(COMPOSE) exec -T $(SERVICE_PHP) pnpm run clean
	@echo "✅ Assets cleaned!"



# REQ-MAKE-008: update-deps (REQ-MAKE-008)
BUNDLE_ROOT := $(abspath $(dir $(lastword $(MAKEFILE_LIST))))
# Optional: monorepo helper absent on standalone GitHub Actions checkout (REQ-MAKE-009).
-include $(BUNDLE_ROOT)/../.scripts/Makefile.update-deps.mk

strip-cursor-coauthor-from-history:
	@chmod +x .scripts/strip-cursor-coauthor-from-history.sh
	@./.scripts/strip-cursor-coauthor-from-history.sh main
