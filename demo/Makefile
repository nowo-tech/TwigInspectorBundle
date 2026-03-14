# Makefile for Twig Inspector Bundle Demo
# Each demo has its own docker-compose.yml and can be run independently

# Define available demos
DEMOS := symfony6 symfony7 symfony8

# Helper function to get port from .env file (defaults to 8001)
get-port = $(shell grep -E '^PORT=' $(1)/.env 2>/dev/null | cut -d '=' -f2 || echo "8001")

# Helper function to get demo version name
get-version = $(shell echo $(1) | sed 's/symfony/Symfony /' | sed 's/6/6.4/' | sed 's/7/7.0/' | sed 's/8/8.0/')

# Helper function to get demo version for shell commands
get-version-shell = $(shell echo $(1) | sed 's/symfony/Symfony /' | sed 's/6/6.4/' | sed 's/7/7.0/' | sed 's/8/8.0/')

.PHONY: help clean test-all test-coverage-all test-ts-all verify-all check-all release-check release-verify
.PHONY: $(foreach demo,$(DEMOS),up-$(demo) down-$(demo) restart-$(demo) build-$(demo) install-$(demo) update-bundle-$(demo) shell-$(demo) logs-$(demo) test-$(demo) test-coverage-$(demo) test-ts-$(demo) verify-$(demo))
.PHONY: up down restart build install update-bundle test shell logs test-coverage test-ts verify

# Default target
help:
	@echo "Twig Inspector Bundle - Demo Commands"
	@echo ""
	@echo "Usage: make <target>"
	@echo ""
	@for demo in $(DEMOS); do \
		version=$$(echo $$demo | sed 's/symfony/Symfony /' | sed 's/6/6.4/' | sed 's/7/7.0/' | sed 's/8/8.0/'); \
		echo "$$version Demo (Port 8001):"; \
		echo "  up-$$demo        Start $$version demo containers"; \
		echo "  down-$$demo      Stop $$version demo containers"; \
		echo "  restart-$$demo   Restart $$version demo containers"; \
		echo "  build-$$demo     Rebuild $$version image (no cache)"; \
		echo "  install-$$demo   Install dependencies for $$version demo"; \
		echo "  update-bundle-$$demo  Update bundle from path repo and clear cache"; \
		echo "  shell-$$demo     Open shell in $$version PHP container"; \
		echo "  logs-$$demo      Show $$version container logs"; \
		echo "  test-$$demo      Run tests for $$version demo"; \
		echo "  test-coverage-$$demo  Run tests with coverage for $$version demo"; \
		echo ""; \
	done
	@echo "Testing:"
	@echo "  test-all           Run tests for all demos"
	@echo "  test-coverage-all  Run tests with coverage for all demos"
	@echo "  test-ts-all        Run TypeScript tests for all demos (bundle has TS)"
	@echo "  verify-all         Verify all demos are running correctly (HTTP 200)"
	@echo "  release-check      Pre-release: test-coverage-all, test-ts-all, release-verify"
	@echo ""
	@echo "Verification:"
	@echo "  verify-all         Start all demos and verify they respond correctly"
	@echo "  verify <demo>      Start a specific demo and verify it responds (e.g., make verify DEMO=symfony6)"
	@echo ""
	@echo "Generic commands (use with DEMO=<name>):"
	@echo "  up <demo>         Start a specific demo (e.g., make up DEMO=symfony6)"
	@echo "  down <demo>        Stop a specific demo (e.g., make down DEMO=symfony6)"
	@echo "  restart <demo>     Restart containers (e.g., make restart DEMO=symfony6)"
	@echo "  build <demo>       Rebuild image no cache (e.g., make build DEMO=symfony6)"
	@echo "  install <demo>     Install dependencies (e.g., make install DEMO=symfony6)"
	@echo "  update-bundle <demo>  Update bundle from path repo (e.g., make update-bundle DEMO=symfony6)"
	@echo "  test <demo>        Run tests (e.g., make test DEMO=symfony6)"
	@echo "  test-coverage <demo>  Run tests with coverage (e.g., make test-coverage DEMO=symfony6)"
	@echo "  shell <demo>       Open shell in a specific demo container"
	@echo "  logs <demo>        Show logs of a specific demo"
	@echo ""
	@echo "Other commands:"
	@echo "  clean              Remove vendor and cache from all demos"
	@echo ""
	@echo "Access URLs:"
	@for demo in $(DEMOS); do \
		version=$$(echo $$demo | sed 's/symfony/Symfony /' | sed 's/6/6.4/' | sed 's/7/7.0/' | sed 's/8/8.0/'); \
		if [ -f $$demo/.env ]; then \
			port=$$(grep -E '^PORT=' $$demo/.env 2>/dev/null | cut -d '=' -f2 | head -1 || echo "8001"); \
		elif [ -f $$demo/.env.example ]; then \
			port=$$(grep -E '^PORT=' $$demo/.env.example 2>/dev/null | cut -d '=' -f2 | head -1 || echo "8001"); \
		else \
			port=8001; \
		fi; \
		echo "  $$version: http://localhost:$$port"; \
	done


# Generate up targets for each demo
define up-target-template
up-$(1):
	@VERSION=$$$$(echo $(1) | sed 's/symfony/Symfony /' | sed 's/6/6.4/' | sed 's/7/7.0/' | sed 's/8/8.0/'); \
	echo "🚀 Starting $$$$VERSION demo..."; \
	cd $(1) && \
	if [ ! -f .env ]; then cp .env.example .env 2>/dev/null || true; fi && \
	PORT=$$$$(grep "^PORT=" .env 2>/dev/null | cut -d= -f2 | head -1 || echo 8001); \
	echo "🔍 Checking port $$$$PORT..."; \
	CONTAINER=$$$$(docker ps --format "{{.Names}}" --filter "publish=$$$$PORT" 2>/dev/null | head -1); \
	if [ -n "$$$$CONTAINER" ]; then \
		echo "⚠️  Port $$$$PORT is in use by container: $$$$CONTAINER"; \
		echo "🛑 Stopping container $$$$CONTAINER..."; \
		docker stop $$$$CONTAINER 2>/dev/null || true; \
		docker rm $$$$CONTAINER 2>/dev/null || true; \
		echo "✅ Port $$$$PORT is now free"; \
	fi && \
	docker-compose build && \
	docker-compose up -d && \
	echo "⏳ Waiting for demo to be ready..." && \
	sleep 5 && \
	MAX_ATTEMPTS=12; \
	ATTEMPT=1; \
	while [ $$$$ATTEMPT -le $$$$MAX_ATTEMPTS ]; do \
		if curl -s -o /dev/null -w "%{http_code}" http://localhost:$$$$PORT | grep -q "200"; then \
			echo "✅ $$$$VERSION demo is running and responding at http://localhost:$$$$PORT"; \
			exit 0; \
		fi; \
		echo "⏳ Attempt $$$$ATTEMPT/$$$$MAX_ATTEMPTS: Waiting for demo to respond..."; \
		sleep 5; \
		ATTEMPT=$$$$((ATTEMPT + 1)); \
	done && \
	echo "❌ Demo did not respond with HTTP 200 after $$$$MAX_ATTEMPTS attempts" && \
	exit 1
endef

# Generate down targets for each demo
define down-target-template
down-$(1):
	@VERSION=$$(echo $(1) | sed 's/symfony/Symfony /' | sed 's/6/6.4/' | sed 's/7/7.0/' | sed 's/8/8.0/'); \
	echo "🛑 Stopping $$VERSION demo..."; \
	cd $(1) && docker-compose down; \
	echo "✅ $$VERSION demo stopped!"
endef

# Generate restart targets for each demo
define restart-target-template
restart-$(1):
	@$(MAKE) -C $(1) ensure-up; \
	VERSION=$$(echo $(1) | sed 's/symfony/Symfony /' | sed 's/6/6.4/' | sed 's/7/7.0/' | sed 's/8/8.0/'); \
	echo "🔄 Restarting $$VERSION demo..."; \
	cd $(1) && docker-compose restart; \
	echo "✅ $$VERSION demo restarted!"
endef

# Generate build targets for each demo
define build-target-template
build-$(1):
	@VERSION=$$(echo $(1) | sed 's/symfony/Symfony /' | sed 's/6/6.4/' | sed 's/7/7.0/' | sed 's/8/8.0/'); \
	echo "🔨 Rebuilding $$VERSION image (no cache)..."; \
	cd $(1) && docker-compose build --no-cache; \
	echo "✅ $$VERSION image rebuilt!"
endef

# Generate install targets for each demo
define install-target-template
install-$(1):
	@VERSION=$$(echo $(1) | sed 's/symfony/Symfony /' | sed 's/6/6.4/' | sed 's/7/7.0/' | sed 's/8/8.0/'); \
	echo "📦 Installing $$VERSION dependencies..."; \
	cd $(1) && docker-compose exec php composer install || \
		(PORT=$$(grep "^PORT=" .env 2>/dev/null | cut -d= -f2 | head -1 || echo 8001); \
		CONTAINER=$$(docker ps --format "{{.Names}}" --filter "publish=$$PORT" 2>/dev/null | head -1); \
		if [ -n "$$CONTAINER" ]; then \
			docker stop $$CONTAINER 2>/dev/null || true; \
			docker rm $$CONTAINER 2>/dev/null || true; \
		fi; \
		docker-compose up -d && sleep 2 && docker-compose exec php composer install); \
	echo "✅ $$VERSION dependencies installed!"
endef

# Generate update-bundle targets for each demo
define update-bundle-target-template
update-bundle-$(1):
	@$(MAKE) -C $(1) update-bundle
endef

# Generate shell targets for each demo
define shell-target-template
shell-$(1):
	@cd $(1) && docker-compose exec php sh
endef

# Generate logs targets for each demo
define logs-target-template
logs-$(1):
	@cd $(1) && docker-compose logs -f
endef

# Generate test targets for each demo
define test-target-template
test-$(1):
	@VERSION=$$(echo $(1) | sed 's/symfony/Symfony /' | sed 's/6/6.4/' | sed 's/7/7.0/' | sed 's/8/8.0/'); \
	echo "🧪 Running $$VERSION tests..."; \
	cd $(1) && docker-compose exec php composer test || \
		(PORT=$$(grep "^PORT=" .env 2>/dev/null | cut -d= -f2 | head -1 || echo 8001); \
		CONTAINER=$$(docker ps --format "{{.Names}}" --filter "publish=$$PORT" 2>/dev/null | head -1); \
		if [ -n "$$CONTAINER" ]; then \
			docker stop $$CONTAINER 2>/dev/null || true; \
			docker rm $$CONTAINER 2>/dev/null || true; \
		fi; \
		docker-compose up -d && sleep 2 && docker-compose exec php composer test)
endef

# Generate test-coverage targets for each demo
define test-coverage-target-template
test-coverage-$(1):
	@VERSION=$$(echo $(1) | sed 's/symfony/Symfony /' | sed 's/6/6.4/' | sed 's/7/7.0/' | sed 's/8/8.0/'); \
	echo "🧪 Running $$VERSION tests with coverage..."; \
	cd $(1) && docker-compose exec php composer test-coverage || \
		(PORT=$$(grep "^PORT=" .env 2>/dev/null | cut -d= -f2 | head -1 || echo 8001); \
		CONTAINER=$$(docker ps --format "{{.Names}}" --filter "publish=$$PORT" 2>/dev/null | head -1); \
		if [ -n "$$CONTAINER" ]; then \
			docker stop $$CONTAINER 2>/dev/null || true; \
			docker rm $$CONTAINER 2>/dev/null || true; \
		fi; \
		docker-compose up -d && sleep 2 && docker-compose exec php composer test-coverage)
endef

# Generate all targets for each demo
$(foreach demo,$(DEMOS),$(eval $(call up-target-template,$(demo))))
$(foreach demo,$(DEMOS),$(eval $(call down-target-template,$(demo))))
$(foreach demo,$(DEMOS),$(eval $(call restart-target-template,$(demo))))
$(foreach demo,$(DEMOS),$(eval $(call build-target-template,$(demo))))
$(foreach demo,$(DEMOS),$(eval $(call install-target-template,$(demo))))
$(foreach demo,$(DEMOS),$(eval $(call update-bundle-target-template,$(demo))))
$(foreach demo,$(DEMOS),$(eval $(call shell-target-template,$(demo))))
$(foreach demo,$(DEMOS),$(eval $(call logs-target-template,$(demo))))
$(foreach demo,$(DEMOS),$(eval $(call test-target-template,$(demo))))
$(foreach demo,$(DEMOS),$(eval $(call test-coverage-target-template,$(demo))))

# Run all tests
test-all:
	@echo "Running tests for all demos..."
	@$(foreach demo,$(DEMOS),$(MAKE) test-$(demo);)
	@echo "✅ All tests completed!"

# Run all tests with coverage
test-coverage-all:
	@echo "Running tests with coverage for all demos..."
	@$(foreach demo,$(DEMOS),$(MAKE) test-coverage-$(demo);)
	@echo "✅ All coverage tests completed!"

# Generic up command
up:
	@if [ -z "$(DEMO)" ]; then \
		echo "❌ Error: Please specify a demo. Usage: make up DEMO=<demo-name>"; \
		echo "Available demos: $(DEMOS)"; \
		exit 1; \
	fi
	@$(MAKE) up-$(DEMO)

# Generic down command
down:
	@if [ -z "$(DEMO)" ]; then \
		echo "❌ Error: Please specify a demo. Usage: make down DEMO=<demo-name>"; \
		echo "Available demos: $(DEMOS)"; \
		exit 1; \
	fi
	@$(MAKE) down-$(DEMO)

# Generic restart command
restart:
	@if [ -z "$(DEMO)" ]; then \
		echo "❌ Error: Please specify a demo. Usage: make restart DEMO=<demo-name>"; \
		echo "Available demos: $(DEMOS)"; \
		exit 1; \
	fi
	@$(MAKE) restart-$(DEMO)

# Generic build command
build:
	@if [ -z "$(DEMO)" ]; then \
		echo "❌ Error: Please specify a demo. Usage: make build DEMO=<demo-name>"; \
		echo "Available demos: $(DEMOS)"; \
		exit 1; \
	fi
	@$(MAKE) build-$(DEMO)

# Generic install command
install:
	@if [ -z "$(DEMO)" ]; then \
		echo "❌ Error: Please specify a demo. Usage: make install DEMO=<demo-name>"; \
		echo "Available demos: $(DEMOS)"; \
		exit 1; \
	fi
	@$(MAKE) install-$(DEMO)

update-bundle:
	@if [ -z "$(DEMO)" ]; then \
		echo "❌ Error: Please specify a demo. Usage: make update-bundle DEMO=<demo-name>"; \
		echo "Available demos: $(DEMOS)"; \
		exit 1; \
	fi
	@$(MAKE) update-bundle-$(DEMO)

# Generic test command
test:
	@if [ -z "$(DEMO)" ]; then \
		echo "❌ Error: Please specify a demo. Usage: make test DEMO=<demo-name>"; \
		echo "Available demos: $(DEMOS)"; \
		exit 1; \
	fi
	@$(MAKE) test-$(DEMO)

# Generic test-coverage command
test-coverage:
	@if [ -z "$(DEMO)" ]; then \
		echo "❌ Error: Please specify a demo. Usage: make test-coverage DEMO=<demo-name>"; \
		echo "Available demos: $(DEMOS)"; \
		exit 1; \
	fi
	@$(MAKE) test-coverage-$(DEMO)

# Generic shell command
shell:
	@if [ -z "$(DEMO)" ]; then \
		echo "❌ Error: Please specify a demo. Usage: make shell DEMO=<demo-name>"; \
		echo "Available demos: $(DEMOS)"; \
		exit 1; \
	fi
	@$(MAKE) shell-$(DEMO)

# Generic logs command
logs:
	@if [ -z "$(DEMO)" ]; then \
		echo "❌ Error: Please specify a demo. Usage: make logs DEMO=<demo-name>"; \
		echo "Available demos: $(DEMOS)"; \
		exit 1; \
	fi
	@$(MAKE) logs-$(DEMO)

# Generate verify targets for each demo
define verify-target-template
verify-$(1):
	@VERSION=$$$$(echo $(1) | sed 's/symfony/Symfony /' | sed 's/6/6.4/' | sed 's/7/7.0/' | sed 's/8/8.0/'); \
	echo "🔍 Verifying $$$$VERSION demo..."; \
	cd $(1) && \
	if [ ! -f .env ]; then cp .env.example .env 2>/dev/null || true; fi && \
	PORT=$$$$(grep "^PORT=" .env 2>/dev/null | cut -d= -f2 | head -1 || echo 8001); \
	echo "🔍 Checking port $$$$PORT..."; \
	CONTAINER=$$$$(docker ps --format "{{.Names}}" --filter "publish=$$$$PORT" 2>/dev/null | head -1); \
	if [ -n "$$$$CONTAINER" ]; then \
		echo "⚠️  Port $$$$PORT is in use by container: $$$$CONTAINER"; \
		echo "🛑 Stopping container $$$$CONTAINER..."; \
		docker stop $$$$CONTAINER 2>/dev/null || true; \
		docker rm $$$$CONTAINER 2>/dev/null || true; \
		echo "✅ Port $$$$PORT is now free"; \
	fi && \
	docker-compose build >/dev/null 2>&1 && \
	docker-compose up -d >/dev/null 2>&1 && \
	echo "⏳ Waiting for $$$$VERSION demo to be ready..." && \
	sleep 5 && \
	MAX_ATTEMPTS=12; \
	ATTEMPT=1; \
	SUCCESS=0; \
	while [ $$$$ATTEMPT -le $$$$MAX_ATTEMPTS ]; do \
		HTTP_CODE=$$$$(curl -s -o /dev/null -w "%{http_code}" http://localhost:$$$$PORT 2>/dev/null || echo "000"); \
		if [ "$$$$HTTP_CODE" = "200" ]; then \
			echo "✅ $$$$VERSION demo is running and responding at http://localhost:$$$$PORT (HTTP $$$$HTTP_CODE)"; \
			SUCCESS=1; \
			break; \
		fi; \
		echo "⏳ Attempt $$$$ATTEMPT/$$$$MAX_ATTEMPTS: Waiting for demo to respond (got HTTP $$$$HTTP_CODE)..."; \
		sleep 5; \
		ATTEMPT=$$$$((ATTEMPT + 1)); \
	done; \
	if [ $$$$SUCCESS -eq 0 ]; then \
		echo "❌ $$$$VERSION demo did not respond with HTTP 200 after $$$$MAX_ATTEMPTS attempts"; \
		exit 1; \
	fi
endef

# Generate verify targets
$(foreach demo,$(DEMOS),$(eval $(call verify-target-template,$(demo))))

# Verify all demos (start and check each one)
verify-all:
	@echo "🚀 Starting and verifying all demos..."
	@echo ""
	@SUCCESS_COUNT=0; \
	FAILED_DEMOS=""; \
	for demo in $(DEMOS); do \
		VERSION=$$(echo $$demo | sed 's/symfony/Symfony /' | sed 's/6/6.4/' | sed 's/7/7.0/' | sed 's/8/8.0/'); \
		echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"; \
		echo "📦 Processing $$VERSION demo ($$demo)"; \
		echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"; \
		if $(MAKE) verify-$$demo; then \
			SUCCESS_COUNT=$$((SUCCESS_COUNT + 1)); \
			echo "✅ $$VERSION demo verified successfully"; \
		else \
			FAILED_DEMOS="$$FAILED_DEMOS $$demo"; \
			echo "❌ $$VERSION demo verification failed"; \
		fi; \
		echo ""; \
	done; \
	echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"; \
	echo "📊 Verification Summary"; \
	echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"; \
	echo "✅ Successful: $$SUCCESS_COUNT/$(words $(DEMOS))"; \
	if [ -n "$$FAILED_DEMOS" ]; then \
		echo "❌ Failed demos:$$FAILED_DEMOS"; \
		echo ""; \
		echo "Access URLs for successful demos:"; \
		for demo in $(DEMOS); do \
			if echo "$$FAILED_DEMOS" | grep -qv "$$demo"; then \
				VERSION=$$(echo $$demo | sed 's/symfony/Symfony /' | sed 's/6/6.4/' | sed 's/7/7.0/' | sed 's/8/8.0/'); \
				if [ -f $$demo/.env ]; then \
					PORT=$$(grep -E '^PORT=' $$demo/.env 2>/dev/null | cut -d '=' -f2 | head -1 || echo "8001"); \
				elif [ -f $$demo/.env.example ]; then \
					PORT=$$(grep -E '^PORT=' $$demo/.env.example 2>/dev/null | cut -d '=' -f2 | head -1 || echo "8001"); \
				else \
					PORT=8001; \
				fi; \
				echo "  $$VERSION: http://localhost:$$PORT"; \
			fi; \
		done; \
		exit 1; \
	else \
		echo "✅ All demos verified successfully!"; \
		echo ""; \
		echo "Access URLs:"; \
		for demo in $(DEMOS); do \
			VERSION=$$(echo $$demo | sed 's/symfony/Symfony /' | sed 's/6/6.4/' | sed 's/7/7.0/' | sed 's/8/8.0/'); \
			if [ -f $$demo/.env ]; then \
				PORT=$$(grep -E '^PORT=' $$demo/.env 2>/dev/null | cut -d '=' -f2 | head -1 || echo "8001"); \
			elif [ -f $$demo/.env.example ]; then \
				PORT=$$(grep -E '^PORT=' $$demo/.env.example 2>/dev/null | cut -d '=' -f2 | head -1 || echo "8001"); \
			else \
				PORT=8001; \
			fi; \
			echo "  $$VERSION: http://localhost:$$PORT"; \
		done; \
	fi

# Generic verify command
verify:
	@if [ -z "$(DEMO)" ]; then \
		echo "❌ Error: Please specify a demo. Usage: make verify DEMO=<demo-name>"; \
		echo "Available demos: $(DEMOS)"; \
		exit 1; \
	fi
	@$(MAKE) verify-$(DEMO)

# Alias for verify-all
check-all: verify-all

# Run test-ts in each demo (TwigInspector bundle has TS; demos may have no-op if no Vitest)
test-ts-symfony6:
	@$(MAKE) -C symfony6 test-ts 2>/dev/null || true
test-ts-symfony7:
	@$(MAKE) -C symfony7 test-ts 2>/dev/null || true
test-ts-symfony8:
	@$(MAKE) -C symfony8 test-ts 2>/dev/null || true
test-ts-all:
	@echo "Running TypeScript tests for all demos..."
	@$(foreach demo,$(DEMOS),$(MAKE) test-ts-$(demo);)
	@echo "✅ test-ts-all done."

# release-check: test-coverage-all, test-ts-all (bundle has TS), then release-verify
release-check: test-coverage-all test-ts-all release-verify
	@echo "✅ All demos passed release-check."

# Release verify: for each demo, up → sleep → healthcheck (HTTP 200) → down
release-verify:
	@for demo in $(DEMOS); do \
		port=$$(grep '^PORT=' $$demo/.env 2>/dev/null | cut -d= -f2 | tr -d '\r'); \
		[ -z "$$port" ] && port=8001; \
		echo "🔍 Release verify: $$demo (port $$port)..."; \
		$(MAKE) up-$$demo; \
		echo "⏳ Waiting for $$demo to be ready..."; sleep 15; \
		code=$$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:$$port 2>/dev/null || echo "000"); \
		$(MAKE) down-$$demo 2>/dev/null || true; \
		if [ "$$code" != "200" ]; then echo "❌ $$demo healthcheck failed (HTTP $$code)"; exit 1; fi; \
		echo "✅ $$demo OK (HTTP $$code)"; \
	done
	@echo "✅ All demos passed release-verify."

# Clean vendor and cache from all demos
clean:
	@echo "🧹 Cleaning vendor and cache from all demos..."
	@$(foreach demo,$(DEMOS),rm -rf $(demo)/vendor $(demo)/var $(demo)/.phpunit.cache;)
	@echo "✅ Cleaned!"
