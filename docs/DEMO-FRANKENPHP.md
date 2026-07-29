# Demo applications with FrankenPHP (development and production)

## Table of contents

- [Overview](#overview)
- [What the demos include](#what-the-demos-include)
- [Development configuration](#development-configuration)
- [Production configuration](#production-configuration)
- [Switching classic vs worker (`FRANKENPHP_MODE`)](#switching-classic-vs-worker-frankenphp_mode)
- [Reproducing in another bundle](#reproducing-in-another-bundle)
- [Troubleshooting](#troubleshooting)

This document describes how the bundle’s demo applications run under **FrankenPHP** in Docker, and how to reproduce **development** (no cache, changes visible on refresh) and **production** (worker mode, cache enabled) configurations. The same approach can be used in other Symfony bundles or applications that ship a FrankenPHP-based demo.

---

## Overview

**The `demo/` folder is not shipped when the bundle is installed** (e.g. via `composer require nowo-tech/twig-inspector-bundle`). It is excluded from the Composer package (via `archive.exclude` in the bundle’s `composer.json`). The demo applications exist only in the bundle’s source repository and are intended for development, testing, and documentation. To run or modify the demos, use a clone of the bundle repository.

The demos use:

- **FrankenPHP** (Caddy + PHP) in a single container.
- **Docker Compose** with the app and the parent bundle mounted as volumes (`../..` → `/var/twig-inspector-bundle`).
- **Two Caddyfiles**: `Caddyfile` (production, with worker) and `Caddyfile.dev` (development, no worker).
- An **entrypoint** that selects classic vs worker Caddyfile from **`FRANKENPHP_MODE`** (`classic` \| `worker`, default **`worker`** in `.env.example`)

There is one demo: **demo/symfony8**. It has its own Dockerfile, docker-compose.yml and Makefile. From the bundle root you run e.g. `make -C demo/symfony8 up` (see the demo’s README for the URL and port).

The main difference between development and production is:

| Aspect | Development | Production |
|--------|-------------|------------|
| FrankenPHP worker mode | **Off** (one PHP process per request) | **On** (workers keep app in memory) |
| Twig cache | **Off** (`config/packages/dev/twig.yaml`) | **On** (default) |
| OPcache revalidation | Every request (`docker/php-dev.ini`) | Default (e.g. 2 seconds) |
| HTTP cache headers | `no-store`, `no-cache` (in Caddyfile.dev) | Omitted or cache-friendly |
| Symfony cache on startup | Cleared in Makefile before `up` | Not cleared (or warmup only) |
| `APP_ENV` / `APP_DEBUG` | `dev` / `1` | `prod` / `0` |

**Ports:** The demo uses `PORT` from its `.env` (Symfony 8 → **8003** by default). Override `PORT` in `.env` if needed.

---

## What the demos include

The demo applications are configured for **local development and debugging**:

- **Symfony Web Profiler** (`Symfony\Bundle\WebProfilerBundle\WebProfilerBundle`) — enabled in `dev` and `test` environments. Provides the debug toolbar and profiler at the bottom of each page.
- **Symfony Debug bundle** (`Symfony\Bundle\DebugBundle\DebugBundle`) — enabled in `dev` and `test`. Required for the profiler and improved error pages.
- **Twig Inspector Bundle** (`Nowo\TwigInspectorBundle\NowoTwigInspectorBundle`) — the bundle under test; enabled in `dev` and `test`. The demos are the bundle’s own test applications, so the inspector is the main feature to try.

Example `config/bundles.php` (Symfony 8 demo):

```php
<?php

declare(strict_types=1);

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class     => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class               => ['all' => true],
    Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class => ['dev' => true, 'test' => true],
    Nowo\TwigInspectorBundle\NowoTwigInspectorBundle::class   => ['dev' => true, 'test' => true],
];
```

In **production** (`APP_ENV=prod`), only bundles registered for `all` or `prod` are loaded, so Web Profiler and Twig Inspector are not active.

---

## Development configuration

Goal: every change to PHP, Twig or config is visible on the next browser refresh without restarting the container. No long-lived PHP workers; cache disabled or revalidated on every request.

### 1. Caddyfile (development)

Do **not** use FrankenPHP worker mode. Use plain `php_server` so each HTTP request is handled by a new PHP process. Add cache-busting headers so the browser does not serve a cached response.

In this bundle, the development Caddyfile is **docker/frankenphp/Caddyfile.dev** in each demo (e.g. `demo/symfony8/docker/frankenphp/Caddyfile.dev`):

```caddyfile
{
	skip_install_trust
}

:80 {
	root * /app/public
	encode zstd br gzip
	# Disable cache in development so changes are reflected immediately
	header Cache-Control "no-store, no-cache, must-revalidate, max-age=0"
	header Pragma "no-cache"
	# Classic mode (no worker): each request runs in a new PHP process so the app always responds and file changes are visible
	php_server
}
```

Important: there must be **no** `worker` directive inside `php_server`. If you use `worker /app/public/index.php 2`, PHP runs in long-lived workers and template changes will not appear until workers are restarted.

The demos’ Docker entrypoint copies this file over `/etc/frankenphp/Caddyfile` when `APP_ENV=dev`. The same Caddyfile.dev is mounted in docker-compose so you can edit it without rebuilding the image.

### 2. PHP configuration (development)

The demos include **docker/php-dev.ini** so OPcache rechecks file modification time on every request; recompiled Twig templates in `var/cache` are then picked up immediately.

- **demo/symfony8/docker/php-dev.ini**:

```ini
; Recheck compiled PHP files every request so Twig-compiled templates in var/cache are always fresh
opcache.revalidate_freq=0
```

- **docker-compose.yml** in each demo mounts it: `./docker/php-dev.ini:/usr/local/etc/php/conf.d/99-dev.ini:ro`. Do not use this file in production if you want normal OPcache behaviour.

### 3. Twig configuration (development)

The demos disable Twig’s compiled template cache in the dev environment so Twig recompiles from source on each request.

- **demo/symfony8/config/packages/dev/twig.yaml**:

```yaml
# Disable Twig cache in dev so template changes are visible on refresh
twig:
    cache: false
```

This file is loaded only when `APP_ENV=dev`. Do not add `cache: false` in the main `config/packages/twig.yaml` for all environments unless you want no Twig cache everywhere.

### 4. Docker Compose (development)

Each demo’s **docker-compose.yml** sets `APP_ENV=dev` and `APP_DEBUG=1`, and mounts:

- `.:/app`
- `../..:/var/twig-inspector-bundle`
- `./docker/frankenphp/Caddyfile.dev:/etc/frankenphp/Caddyfile.dev`
- `./docker/php-dev.ini:/usr/local/etc/php/conf.d/99-dev.ini:ro`

The entrypoint, when `APP_ENV=dev`, runs `cp /etc/frankenphp/Caddyfile.dev /etc/frankenphp/Caddyfile` before starting FrankenPHP. Default port: Symfony 8 → **8003** (see the demo’s `.env` or `PORT`).

### 5. Entrypoint (development-friendly)

The demos’ entrypoint creates `var/cache` and `var/log`, and when `APP_ENV=dev` overwrites the Caddyfile with the dev version (no worker). Composer install and Symfony cache clear are done by the **Makefile** (`make up`) in one-off containers before starting the server, so the main container starts with a warm app. No cache clear is performed inside the container on each start; run `make cache-clear` from the demo directory (or `make -C demo/symfony8 cache-clear` from the bundle root) if needed.

### 6. Start the demo (development)

From the bundle root:

```bash
make -C demo/symfony8 up
# → App ready at http://127.0.0.1:8003/ (or the PORT set in demo/symfony8/.env)
```

Or from inside the demo directory: `make up`. The Makefile runs `composer install` and `cache:clear` in one-off containers, then starts the stack and waits for the app to respond. After editing a Twig template or PHP file, refresh the browser; with worker mode off, Twig cache disabled and OPcache revalidating every request, changes should appear without restarting the container.

---

## Production configuration

Goal: maximum performance with worker mode and caching. No cache-busting headers; Twig and OPcache use their default caching behaviour.

### 1. Caddyfile (production)

Enable FrankenPHP worker mode so the application is booted once per worker and kept in memory. The default Caddyfile in the image (used when `APP_ENV` is not `dev`) is **docker/frankenphp/Caddyfile**:

```caddyfile
{
	skip_install_trust
}

:80 {
	root * /app/public
	encode zstd br gzip
	php_server {
		worker /app/public/index.php
	}
}
```

- `worker /app/public/index.php` — run worker process(es). You can add a number (e.g. `worker /app/public/index.php 2`) for more workers.
- Do **not** add `header Cache-Control "no-store"` etc. in production unless you explicitly want to disable HTTP caching.

### 2. PHP configuration (production)

Do **not** mount `php-dev.ini` (or any ini with `opcache.revalidate_freq=0`) in production. Rely on the image’s default OPcache settings.

### 3. Twig configuration (production)

Do **not** set `twig.cache: false` for production. In `APP_ENV=prod`, Twig uses the default cache (e.g. `var/cache/prod/twig`).

### 4. Docker Compose (production)

Use a production Compose file or override that:

- Does **not** set `APP_ENV=dev` — use `APP_ENV=prod` and `APP_DEBUG=0`.
- Does **not** mount `php-dev.ini`.
- The entrypoint will then use the default Caddyfile (with worker) copied in the image.

You can add a `docker-compose.prod.yml` that overrides `environment` and volumes if needed.

### 5. Build and run (production)

```bash
# From the demo directory
docker-compose build
docker-compose up -d
# Ensure APP_ENV=prod and APP_DEBUG=0 in the environment
```

Or from the bundle root: `make -C demo/symfony8 build` then adjust env to prod and run. Ensure the application is installed (e.g. `composer install --no-dev`) and the cache is warmed so the first request is fast.

---

## Switching classic vs worker (`FRANKENPHP_MODE`)

Mode is selected by **`FRANKENPHP_MODE`** (`classic` \| `worker`), not by `APP_ENV`. Default in `.env.example` / Compose is **`worker`**.

| Mode | Effect |
| --- | --- |
| `classic` | Entrypoint copies `Caddyfile.dev` (no worker; better for hot-reload / per-request PHP). |
| `worker` | Entrypoint uses the default worker-enabled Caddyfile. |

Demos still run with `APP_ENV=dev` and `APP_DEBUG=1` for Symfony. They use `config/packages/dev/twig.yaml` with `cache: false` and mount `docker/php-dev.ini` with `opcache.revalidate_freq=0`.

After changing `FRANKENPHP_MODE`, recreate the container so the entrypoint re-runs:

```bash
docker compose up -d --force-recreate
# or from bundle root:
make -C demo/symfony8 restart
```

---

## Reproducing in another bundle

To replicate this setup in another bundle or app:

1. **Dockerfile** — base image `dunglas/frankenphp:1-php8.x-alpine` (or your PHP version), install Composer and any extensions, copy the **default** Caddyfile (worker) and a **Caddyfile.dev** (classic / no worker) into the image; `COPY` a dedicated `docker/entrypoint.sh`.
2. **Two Caddyfiles** — e.g. `docker/frankenphp/Caddyfile` (worker: `php_server { worker ... }`) and `docker/frankenphp/Caddyfile.dev` (classic: `php_server` only). Optionally add no-cache headers in the classic Caddyfile.
3. **Entrypoint** — create `var/cache`, `var/log`, and switch Caddyfile from **`FRANKENPHP_MODE`** (`classic` → `Caddyfile.dev`, `worker` → default). Then `exec frankenphp run --config /etc/frankenphp/Caddyfile --adapter caddyfile`.
4. **Dev-only files (optional)** — `docker/php-dev.ini` with `opcache.revalidate_freq=0`; `config/packages/dev/twig.yaml` with `twig.cache: false`.
5. **Compose** — pass `FRANKENPHP_MODE=${FRANKENPHP_MODE:-worker}`; for Symfony demos keep `APP_ENV=dev`, `APP_DEBUG=1`.
6. **Bundles** — enable `WebProfilerBundle`, `DebugBundle`, and your bundle only for `dev` and `test` in `bundles.php` as needed.

This gives you a reproducible classic (hot-reload) vs worker FrankenPHP setup.

---

## Troubleshooting

### Changes to Twig or PHP do not appear on refresh

- Ensure **worker mode is off** in the Caddyfile used when `APP_ENV=dev` (the entrypoint copies `Caddyfile.dev`, which has no `worker` inside `php_server`).
- Optionally add `config/packages/dev/twig.yaml` with `twig.cache: false`.
- Optionally mount `docker/php-dev.ini` with `opcache.revalidate_freq=0`.
- Restart the container after changing the Caddyfile or mounted ini: `docker-compose restart` or `make -C demo/symfony8 restart`.
- Hard-refresh the browser (e.g. Ctrl+Shift+R) or try a private window.

### Web Profiler or Twig Inspector not visible

- Check `APP_ENV=dev` and `APP_DEBUG=1` in the container environment.
- Ensure `WebProfilerBundle`, `DebugBundle` and `NowoTwigInspectorBundle` are enabled for `dev` in `config/bundles.php`.
- Clear the Symfony cache: `docker-compose exec php php bin/console cache:clear` or `make -C demo/symfony8 cache-clear`.

### Demo does not respond or "make up" times out

- The Makefile runs `composer install` and `cache:clear` in one-off containers before starting the server, then waits for HTTP response on the configured port. Ensure the port in `.env` (e.g. `PORT=8003` for Symfony 8) is free and that the container starts (check `docker-compose logs php`).
- If the container exits, check that the entrypoint and Caddyfile are valid and that required env vars (e.g. `APP_SECRET`) are set.

### Production: slow first request or "cache cold"

- Warm the cache in the Dockerfile or entrypoint: `php bin/console cache:warmup --env=prod`.
- Ensure OPcache is enabled and not forced to revalidate every request (do not use `php-dev.ini` in production).

### Caddyfile changes have no effect

- The Caddyfile is read when FrankenPHP starts. Restart the container: `docker-compose restart` or `make -C demo/symfony8 restart`.
- In dev, the entrypoint copies `Caddyfile.dev` over the default; ensure you edited `Caddyfile.dev` and that it is mounted (or baked into the image) so the copy is up to date.
