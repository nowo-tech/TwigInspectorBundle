# Makefile for Symfony 6.4 Demo (Twig Inspector Bundle)
# Run from this directory: make up, make install, etc.

.PHONY: help up down build install shell logs test test-coverage test-ts assets-test cache-clear link-bundle update-bundle ensure-up

help:
	@echo "Symfony 6.4 Demo - Twig Inspector Bundle"
	@echo ""
	@echo "  make up             Start containers"
	@echo "  make down           Stop containers"
	@echo "  make build          Build images (no cache)"
	@echo "  make install        Install dependencies (composer install in container)"
	@echo "  make shell          Open shell in PHP container"
	@echo "  make logs           Show container logs"
	@echo "  make test           Run tests"
	@echo "  make test-coverage  Run tests with coverage"
	@echo "  make test-ts        Run TypeScript tests (no-op if demo has no Vitest)"
	@echo "  make assets-test    Alias of test-ts"
	@echo "  make cache-clear    Clear Symfony cache"
	@echo "  make link-bundle    Use bundle from /var/twig-inspector-bundle (see demo/README.md)"
	@echo "  make update-bundle  Update nowo-tech/twig-inspector-bundle from path repo and clear cache"
	@echo ""

up:
	@if [ ! -f .env ]; then cp .env.example .env 2>/dev/null || true; fi
	docker-compose up -d
	@echo "Waiting for container to be ready..."
	@sleep 5
	@echo "Installing dependencies..."
	@docker-compose exec -T -e COMPOSER_MEMORY_LIMIT=-1 php composer install --no-interaction
	@echo "App ready (see .env for PORT)."

down:
	docker-compose down

restart: ensure-up
	docker-compose restart

build:
	docker-compose build --no-cache

ensure-up:
	@if ! docker-compose exec -T php true 2>/dev/null; then \
		echo "Starting container..."; \
		docker-compose up -d; \
		sleep 3; \
	fi

install: ensure-up
	docker-compose exec -T -e COMPOSER_MEMORY_LIMIT=-1 php composer install --no-interaction

shell: ensure-up
	docker-compose exec php sh

logs:
	docker-compose logs -f

test: ensure-up
	docker-compose exec -e APP_ENV=test php composer test

test-coverage: ensure-up
	docker-compose exec php composer test-coverage

# TypeScript tests (no-op in this demo; bundle has TS, root runs test-ts)
test-ts:
	@echo "No TypeScript tests in this demo (bundle TS tests run from root)."
assets-test: test-ts

cache-clear: ensure-up
	docker-compose exec php php bin/console cache:clear

link-bundle: ensure-up
	docker-compose exec php sh -c "rm -rf vendor/nowo-tech/twig-inspector-bundle && ln -s /var/twig-inspector-bundle vendor/nowo-tech/twig-inspector-bundle && php bin/console cache:clear"
	@echo "Bundle linked to mounted source. Reload the page in the browser."

update-bundle: ensure-up
	docker-compose exec php composer update nowo-tech/twig-inspector-bundle --no-interaction
	docker-compose exec php php bin/console cache:clear
	@echo "Bundle updated. Reload the page in the browser."
