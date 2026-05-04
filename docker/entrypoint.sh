#!/usr/bin/env bash
# =============================================================================
# SASO — container entrypoint
# -----------------------------------------------------------------------------
# Idempotently bootstraps the project-level `.env` file with secure random
# values for APP_KEY, DB_PASSWORD, and MARIADB_ROOT_PASSWORD before handing
# control to Apache. Existing values are left untouched; the script only
# fills blanks. Safe to re-run on every container start.
#
# This is the SECOND line of defence — `make up` on the host generates the
# same file before Compose evaluates docker-compose.yml. The host preflight
# is what allows the `${VAR:?...}` references in docker-compose.yml to
# resolve. This script only matters when someone runs `docker compose up`
# directly (without going through `make up`) inside an environment that
# already exported the required variables, OR when the bind-mounted project
# directory is missing a `.env` for some other reason.
# =============================================================================
set -euo pipefail

ENV_FILE="/var/www/html/saso/.env"
ENV_EXAMPLE="/var/www/html/saso/.env.example"

ensure_value() {
    local key="$1" generator="$2"

    # Add the line if missing entirely.
    if ! grep -qE "^${key}=" "$ENV_FILE"; then
        echo "${key}=" >> "$ENV_FILE"
    fi
    # Fill it only if blank.
    if grep -qE "^${key}=$" "$ENV_FILE"; then
        local value
        value="$(${generator})"
        # Use a different sed delimiter to dodge `/` and `=` in base64 output.
        sed -i "s|^${key}=$|${key}=${value}|" "$ENV_FILE"
        echo "[entrypoint] generated ${key}"
    fi
}

if [ -f "$ENV_EXAMPLE" ]; then
    if [ ! -f "$ENV_FILE" ]; then
        cp "$ENV_EXAMPLE" "$ENV_FILE"
        echo "[entrypoint] created .env from .env.example"
    fi

    ensure_value APP_KEY                  "openssl rand -base64 32"
    ensure_value DB_PASSWORD              "openssl rand -hex 16"
    ensure_value MARIADB_ROOT_PASSWORD    "openssl rand -hex 16"
fi

exec apache2-foreground
