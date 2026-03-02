# Makefile for Twig Inspector Bundle
# Simplifies Docker commands for development.
# All dev targets (test, install, qa, etc.) use the root docker-compose.yml.

COMPOSE_FILE := docker-compose.yml
COMPOSE := docker-compose -f $(COMPOSE_FILE)
SERVICE_PHP := php

.PHONY: help up down shell install test test-coverage cs-check cs-fix qa clean setup-hooks test-up test-down test-shell assets assets-dev assets-watch assets-clean release-check release-check-demos composer-sync

# Default target
help:
	@echo "Twig Inspector Bundle - Development Commands"
	@echo ""
	@echo "Usage: make <target>"
	@echo ""
	@echo "Targets:"
	@echo "  up            Start Docker container"
	@echo "  down          Stop Docker container"
	@echo "  shell         Open shell in container"
	@echo "  install       Install Composer and pnpm dependencies"
	@echo "  test          Run PHPUnit tests (starts container if needed)"
	@echo "  test-coverage Run tests with code coverage (starts container if needed)"
	@echo "  test-up       Start test container"
	@echo "  test-down     Stop test container"
	@echo "  test-shell    Open shell in test container"
	@echo "  cs-check      Check code style"
	@echo "  cs-fix        Fix code style"
	@echo "  qa            Run all QA checks (cs-check + test)"
	@echo "  release-check Pre-release: cs-fix, cs-check, test-coverage, demo healthchecks"
	@echo "  composer-sync Validate composer.json and align composer.lock (no install)"
	@echo "  clean         Remove vendor and cache"
	@echo "  setup-hooks   Install git pre-commit hooks"
	@echo "  assets  Build TypeScript and SCSS assets"
	@echo "  assets-dev Build assets in development mode"
	@echo "  assets-watch  Watch assets for changes"
	@echo "  assets-clean  Clean built assets"
	@echo ""

# Build and start container (root docker-compose)
up:
	$(COMPOSE) build
	$(COMPOSE) up -d
	@echo "Installing dependencies..."
	$(COMPOSE) exec $(SERVICE_PHP) composer install --no-interaction
	@echo "✅ Container ready!"

# Stop container
down:
	$(COMPOSE) down

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
		echo "Starting container (root docker-compose)..."; \
		$(COMPOSE) up -d; \
		sleep 3; \
		$(COMPOSE) exec -T $(SERVICE_PHP) composer install --no-interaction; \
	fi

# Run tests (inside root docker-compose php service). Run 'make up' once to build and install deps.
test: ensure-up
	$(COMPOSE) exec $(SERVICE_PHP) composer test

# Run tests with coverage
test-coverage: ensure-up
	$(COMPOSE) exec $(SERVICE_PHP) composer test-coverage

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

# Run all QA
qa: ensure-up
	$(COMPOSE) exec -T $(SERVICE_PHP) composer qa

# Pre-release: cs-fix, cs-check, test-coverage, demo healthchecks
release-check: ensure-up composer-sync cs-fix cs-check test-coverage release-check-demos

release-check-demos:
	@$(MAKE) -C demo release-verify

composer-sync: ensure-up
	$(COMPOSE) exec -T $(SERVICE_PHP) composer validate --strict
	$(COMPOSE) exec -T $(SERVICE_PHP) composer update --no-install

# Clean vendor and cache — runs inside container
clean: ensure-up
	$(COMPOSE) exec -T $(SERVICE_PHP) sh -c "rm -rf vendor .phpunit.cache coverage coverage.xml .php-cs-fixer.cache"

# Setup git hooks for pre-commit checks
setup-hooks:
	chmod +x .githooks/pre-commit
	git config core.hooksPath .githooks
	@echo "✅ Git hooks installed! CS-check and tests will run before each commit."

# Build assets (TypeScript and SCSS) — runs inside container
assets: ensure-up
	@echo "Building assets..."
	$(COMPOSE) exec -T -e CI=true $(SERVICE_PHP) pnpm install
	$(COMPOSE) exec -T $(SERVICE_PHP) pnpm run build
	@echo "✅ Assets built!"

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

