# syntax=docker/dockerfile:1
#
# Production image: FrankenPHP serves HTTP through Laravel Octane, and the
# php CLI that runs the queue and the scheduler is the same binary.

# The Filament panel themes (resources/css/filament/*/theme.css) import CSS from
# vendor/, so the frontend stage needs a composer install first. This base ships
# no composer binary; the static one from the official image runs on
# FrankenPHP's PHP. unzip lets composer unpack dist archives without compiling
# the zip extension in this stage too.
FROM dunglas/frankenphp:1.13.4-php8.4 AS php-deps
WORKDIR /app
RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY . .
# --no-scripts: post-autoload-dump boots the app and needs extensions that only
# the final stage installs; those scripts run there instead.
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-dev --ignore-platform-reqs --optimize-autoloader --no-scripts

FROM node:22-bookworm-slim AS frontend
WORKDIR /build
COPY . .
COPY --from=php-deps /app/vendor ./vendor
RUN --mount=type=cache,target=/root/.npm \
    npm ci --no-audit --no-fund && npm run build

FROM dunglas/frankenphp:1.13.4-php8.4

# intl = Filament runtime requirement
# zip = composer platform requirement (and file exports)
# pcntl = Octane worker signal handling
# pdo_pgsql / pdo_mysql = production database drivers (pdo_sqlite ships with the base)
# Add redis here and set REDIS_CLIENT=phpredis to use the native Redis client.
RUN install-php-extensions intl zip pcntl pdo_pgsql pdo_mysql

RUN apt-get update \
    && apt-get install -y --no-install-recommends supervisor \
    && rm -rf /var/lib/apt/lists/*

# Platforms such as Coolify route to container port 80. The Octane worker runs
# as www-data, which can only bind a privileged port with this capability, so
# no PHP process needs root.
RUN setcap cap_net_bind_service=+ep "$(command -v frankenphp)"

# Caddy autosaves its config and keeps state under these base-image directories;
# owned by root they make every start log write errors.
RUN chown -R www-data:www-data /config/caddy /data/caddy

COPY docker/php.ini /usr/local/etc/php/conf.d/99-app.ini

WORKDIR /app
COPY . .

# public/frankenphp-worker.php is gitignored, so a clean checkout lacks it.
# Octane copies the stub at runtime, but as www-data against a root-owned
# public/, so the worker would never boot; create it at build time instead.
COPY --from=php-deps /app/vendor ./vendor
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/app/public storage/logs bootstrap/cache \
    && cp vendor/laravel/octane/src/Commands/stubs/frankenphp-worker.php public/frankenphp-worker.php \
    && php artisan package:discover --ansi \
    && php artisan filament:upgrade \
    && chown -R www-data:www-data storage bootstrap/cache database

# public/build is gitignored; the Vite output comes from the frontend stage.
COPY --from=frontend /build/public/build ./public/build

COPY docker/supervisord.conf /etc/app-supervisord.conf
COPY docker/worker-*.conf /etc/supervisor/conf.d/
COPY docker/Caddyfile /etc/app/Caddyfile

# Supervisor is PID 1 and runs the web server, the queue worker and the
# scheduler in one container.
ENTRYPOINT ["supervisord", "-n", "-c", "/etc/app-supervisord.conf"]

# The Octane worker listens on $PORT (default 8000); set PORT=80 behind a proxy
# that routes to port 80.
EXPOSE 80
