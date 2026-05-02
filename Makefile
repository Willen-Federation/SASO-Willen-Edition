# =============================================================================
# SASO — Willen Edition / developer Makefile
# -----------------------------------------------------------------------------
# All targets default to the docker-compose stack. Run `make help` to see what
# is available. Every command is idempotent and safe to repeat.
# =============================================================================

# Use bash with strict flags so failures inside multi-line recipes propagate.
SHELL := /bin/bash
.SHELLFLAGS := -eu -o pipefail -c
.DEFAULT_GOAL := help

DC ?= docker compose
APP ?= app
DB ?= db
DB_ROOT_PASSWORD ?= rootpw
DB_NAME ?= saso_db

# Run a command inside the app container. -T disables a TTY so it works inside
# Make's piped output (composer install logs, phpunit, etc.).
EXEC_APP := $(DC) exec -T $(APP)
EXEC_DB := $(DC) exec -T $(DB)

.PHONY: help up up-sso down restart logs ps shell composer install update \
        test analyse cs-check cs-fix migrate migrate-status migrate-rollback \
        seed db-shell db-dump clean \
        tailadmin-build tailadmin-watch tailadmin-vendor tailadmin-fetch-cli \
        pa11y i18n-check

help:  ## Show available targets
	@printf "\nUsage: make <target>\n\n"
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | sort | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'
	@printf "\n"

# ----- Lifecycle -------------------------------------------------------------

up:  ## Build (if needed) and start the local stack (app + db + adminer)
	$(DC) up -d --build

up-sso:  ## Start the local stack with the optional Keycloak IdP profile
	$(DC) --profile sso up -d --build

down:  ## Stop and remove containers (keeps volumes)
	$(DC) down

down-clean:  ## Stop and remove containers AND volumes (DESTRUCTIVE)
	$(DC) down -v

restart:  ## Recreate the app container after Apache / PHP config changes
	$(DC) up -d --force-recreate $(APP)

logs:  ## Tail logs from every service (Ctrl-C to stop)
	$(DC) logs -f

ps:  ## Show container status
	$(DC) ps

shell:  ## Open a bash shell in the app container
	$(DC) exec $(APP) bash

# ----- Composer --------------------------------------------------------------

install:  ## Install Composer dependencies inside the app container
	$(EXEC_APP) composer install --no-interaction --no-progress

update:  ## Update Composer dependencies (refresh composer.lock)
	$(EXEC_APP) composer update --no-interaction --no-progress

composer:  ## Run an arbitrary composer command, e.g. `make composer args="require monolog/monolog"`
	$(EXEC_APP) composer $(args)

# ----- QA --------------------------------------------------------------------

test:  ## Run the PHPUnit unit suite
	$(EXEC_APP) vendor/bin/phpunit --testsuite=Unit --no-coverage

test-all:  ## Run all PHPUnit suites
	$(EXEC_APP) vendor/bin/phpunit --no-coverage

analyse:  ## Run PHPStan
	$(EXEC_APP) vendor/bin/phpstan analyse --no-progress

cs-check:  ## Verify PHP-CS-Fixer rules (read-only)
	$(EXEC_APP) vendor/bin/php-cs-fixer fix --dry-run --diff --no-interaction

cs-fix:  ## Apply PHP-CS-Fixer auto-fixes
	$(EXEC_APP) vendor/bin/php-cs-fixer fix --no-interaction

qa:  ## Run cs-check + analyse + test in sequence
	$(MAKE) cs-check
	$(MAKE) analyse
	$(MAKE) test

# ----- Database --------------------------------------------------------------

migrate:  ## Apply pending Phinx migrations against the dev DB
	$(EXEC_APP) vendor/bin/phinx migrate -e production

migrate-status:  ## Show applied vs pending Phinx migrations
	$(EXEC_APP) vendor/bin/phinx status -e production

migrate-rollback:  ## Roll back the most recent Phinx migration (DESTRUCTIVE)
	$(EXEC_APP) vendor/bin/phinx rollback -e production

seed:  ## Run Phinx seed classes (idempotent if writers use upsert)
	$(EXEC_APP) vendor/bin/phinx seed:run -e production

db-shell:  ## Open a MariaDB client inside the db container
	$(DC) exec $(DB) mariadb -uroot -p$(DB_ROOT_PASSWORD) $(DB_NAME)

db-dump:  ## Dump the current schema + data to ./db-dump.sql
	$(EXEC_DB) mariadb-dump -uroot -p$(DB_ROOT_PASSWORD) $(DB_NAME) > db-dump.sql
	@echo "→ db-dump.sql"

# ----- Misc ------------------------------------------------------------------

clean:  ## Remove local build artifacts (does NOT touch containers / volumes)
	rm -rf .phpunit.cache .php-cs-fixer.cache vendor/
	@echo "Removed local caches and vendor/. Run \`make install\` to restore."

# ----- TailAdmin / Tailwind --------------------------------------------------
# The tailwindcss standalone CLI is a single static binary that does NOT need
# Node in production (Composer-only deploys stay Node-free). The binary is
# fetched into tools/ on demand and gitignored; the built CSS at
# css/tailadmin.css IS committed so deployments need no Node toolchain.

TAILWINDCSS_VERSION ?= v3.4.17
TAILWINDCSS_BIN     ?= tools/tailwindcss
TAILWINDCSS_URL     ?= https://github.com/tailwindlabs/tailwindcss/releases/download/$(TAILWINDCSS_VERSION)/tailwindcss-linux-x64

tailadmin-fetch-cli:  ## Fetch the tailwindcss standalone CLI (one-time)
	@mkdir -p tools
	@if [ ! -x $(TAILWINDCSS_BIN) ]; then \
		echo "→ Fetching tailwindcss $(TAILWINDCSS_VERSION)…"; \
		curl -fsSL -o $(TAILWINDCSS_BIN) $(TAILWINDCSS_URL); \
		chmod +x $(TAILWINDCSS_BIN); \
	else \
		echo "→ tools/tailwindcss already present (skip)"; \
	fi

tailadmin-build: tailadmin-fetch-cli  ## Build css/tailadmin.css from tailadmin/input.css
	$(TAILWINDCSS_BIN) -c tailadmin/tailwind.config.js -i tailadmin/input.css -o css/tailadmin.css --minify

tailadmin-watch: tailadmin-fetch-cli  ## Rebuild on every change (development)
	$(TAILWINDCSS_BIN) -c tailadmin/tailwind.config.js -i tailadmin/input.css -o css/tailadmin.css --watch

tailadmin-vendor:  ## Re-download vendored Alpine.js bundles (pinned versions)
	@echo "→ Vendoring alpine.js + plugins"
	curl -fsSL -o js/alpine.min.js         https://unpkg.com/alpinejs@3.14.1/dist/cdn.min.js
	curl -fsSL -o js/alpine-persist.min.js https://unpkg.com/@alpinejs/persist@3.14.1/dist/cdn.min.js
	curl -fsSL -o js/alpine-focus.min.js   https://unpkg.com/@alpinejs/focus@3.14.1/dist/cdn.min.js
	@echo "→ Vendored to js/alpine*.min.js"

# ----- Accessibility / i18n smoke tests --------------------------------------

PA11Y_BASE_URL ?= http://localhost:8080
pa11y:  ## Run pa11y AA scans against routes in tests/ui/pa11y.routes.json
	@if ! command -v jq >/dev/null; then echo "jq required: apt-get install -y jq"; exit 1; fi
	@for path in $$(jq -r '.routes[]' tests/ui/pa11y.routes.json); do \
		echo "→ $$path"; \
		docker run --rm --network=host pa11y/pa11y --standard WCAG2AA "$(PA11Y_BASE_URL)$$path" || exit 1; \
	done

i18n-check:  ## Render every legacy template under both locales and fail on missing keys
	$(EXEC_APP) php tests/i18n/missing-keys.php
