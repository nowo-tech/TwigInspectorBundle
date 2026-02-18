# Makefile for Symfony 8.0 Demo (Twig Inspector Bundle)
# Run from this directory: make up, make install, etc.

.PHONY: help up down build install shell logs test test-coverage cache-clear link-bundle update-bundle

help:
	@echo "Symfony 8.0 Demo - Twig Inspector Bundle"
	@echo ""
	@echo "  make up             Start containers"
	@echo "  make down           Stop containers"
	@echo "  make build          Build images (no cache)"
	@echo "  make install        Install dependencies (composer install in container)"
	@echo "  make shell          Open shell in PHP container"
	@echo "  make logs           Show container logs"
	@echo "  make test           Run tests"
	@echo "  make test-coverage  Run tests with coverage"
	@echo "  make cache-clear    Clear Symfony cache"
	@echo "  make link-bundle    Use bundle from /var/twig-inspector-bundle (see demo/README.md)"
	@echo "  make update-bundle  Update nowo-tech/twig-inspector-bundle from path repo and clear cache"
	@echo ""

up:
	@if [ ! -f .env ]; then cp .env.example .env 2>/dev/null || true; fi
	docker-compose up -d

down:
	docker-compose down

build:
	docker-compose build --no-cache

install:
	docker-compose exec php composer install

shell:
	docker-compose exec php sh

logs:
	docker-compose logs -f

test:
	docker-compose exec -e APP_ENV=test php composer test

test-coverage:
	docker-compose exec php composer test-coverage

cache-clear:
	docker-compose exec php php bin/console cache:clear

link-bundle:
	docker-compose exec php sh -c "rm -rf vendor/nowo-tech/twig-inspector-bundle && ln -s /var/twig-inspector-bundle vendor/nowo-tech/twig-inspector-bundle && php bin/console cache:clear"
	@echo "Bundle linked to mounted source. Reload the page in the browser."

update-bundle:
	docker-compose exec php composer update nowo-tech/twig-inspector-bundle --no-interaction
	docker-compose exec php php bin/console cache:clear
	@echo "Bundle updated. Reload the page in the browser."
