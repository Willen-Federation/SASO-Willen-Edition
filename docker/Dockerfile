# =============================================================================
# SASO — Willen Edition / development image
# -----------------------------------------------------------------------------
# Mirrors the deployment surface most operators run on (mod_php under Apache
# 2.4 + .htaccess), so behaviour observed during development matches the
# eventual shared-hosting target. fpm + nginx is intentionally avoided here —
# .htaccess parsing semantics differ slightly between mod_php and external
# FastCGI setups, and we do not want development to mask that.
# =============================================================================
FROM php:8.2-apache

ARG DEBIAN_FRONTEND=noninteractive

# --- System packages required by the PHP extensions and TCPDF ----------------
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        git \
        unzip \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libzip-dev \
        libicu-dev \
        libxml2-dev \
        libonig-dev \
    && rm -rf /var/lib/apt/lists/*

# --- PHP extensions -----------------------------------------------------------
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        gd \
        zip \
        intl \
        mbstring \
        opcache \
        exif

# --- Apache modules used by the .htaccess scaffold ---------------------------
RUN a2enmod rewrite headers expires deflate

# --- Composer (pinned major version) -----------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# --- Local config overrides --------------------------------------------------
COPY docker/php/saso.ini /usr/local/etc/php/conf.d/zz-saso.ini
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# --- Entrypoint: idempotent .env bootstrap before Apache starts --------------
COPY docker/entrypoint.sh /usr/local/bin/saso-entrypoint
RUN chmod +x /usr/local/bin/saso-entrypoint

# Bind-mount target — the actual project files arrive at runtime via
# docker-compose volumes so edits are picked up without rebuilding the image.
WORKDIR /var/www/html

# Apache binds to 80 inside the container; docker-compose maps it to 8080.
EXPOSE 80

CMD ["saso-entrypoint"]
