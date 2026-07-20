# Installation

This guide covers installing Twig Inspector Bundle in a Symfony application.


## Table of contents

- [Requirements](#requirements)
- [Install with Composer](#install-with-composer)
- [Register the bundle](#register-the-bundle)
  - [With Symfony Flex](#with-symfony-flex)
  - [Without Flex (manual)](#without-flex-manual)
  - [Install command (config + routes)](#install-command-config-routes)
- [IDE integration (optional)](#ide-integration-optional)
- [Verify](#verify)
- [Upgrading](#upgrading)

## Requirements

- **PHP** >= 8.1, < 8.6
- **Symfony** >= 6.0 || >= 7.0 || >= 8.0
- **Symfony Web Profiler Bundle** (dev environment) — required for toolbar, collector and "open in IDE" feature
- **Twig** >= 3.8 || >= 4.0

> **Important:** Twig Inspector is a **development tool only**. Do **not** enable it in production (`prod`). Register the bundle for `dev` and `test` only. The DI extension **hard-fails** outside those environments; injectors also require `dev`/`test` (not only `kernel.debug`); the "open in IDE" route returns 404 outside allowed envs; `nowo:twig-inspector:install --env=prod` is rejected. See [Security](SECURITY.md) for the threat model.

## Install with Composer

```bash
composer require nowo-tech/twig-inspector-bundle --dev
```

Use `^1.0` (or the latest stable, e.g. `^1.0.9`) to stay on the current major version.

## Register the bundle

### With Symfony Flex

If you use [Symfony Flex](https://symfony.com/doc/current/setup/flex.html) and the bundle is installed from Packagist, the recipe will:

- Register the bundle in `config/bundles.php`
- Create `config/packages/nowo_twig_inspector.yaml` (optional config)
- Add the bundle routes to `config/routes.yaml` for `dev` and `test` environments

You do **not** need to edit any file manually.

### Without Flex (manual)

1. **Register the bundle** in `config/bundles.php`:

```php
<?php

return [
    // ...
    Nowo\TwigInspectorBundle\NowoTwigInspectorBundle::class => ['dev' => true, 'test' => true],
];
```

2. **Add routes** (so “open in IDE” links work). In `config/routes.yaml`:

```yaml
when@dev:
    nowo_twig_inspector:
        resource: '@NowoTwigInspectorBundle/Resources/config/routes.yaml'

when@test:
    nowo_twig_inspector:
        resource: '@NowoTwigInspectorBundle/Resources/config/routes.yaml'
```

**Import must be restricted to `dev` and `test`** — never in `prod`. For the **test** environment, add the `when@test:` block as shown above. If the route is imported without `when@dev`/`when@test` (e.g. in prod), the controller returns 404 as a safety measure — but you must not register the bundle for prod.

3. **Optional config**: Create `config/packages/dev/nowo_twig_inspector.yaml` (or use the install command below). If you skip this, the bundle uses defaults from `Configuration.php`.

### Install command (config + routes)

If the Flex recipe did not run (e.g. private repo or Git install), you can generate the config and ensure routes are set up:

```bash
php bin/console nowo:twig-inspector:install
```

This creates `config/packages/dev/nowo_twig_inspector.yaml` and updates `config/routes.yaml` if needed. Use `--env=test` for the test environment, and `--force` to overwrite an existing config file.

## IDE integration (optional)

To open templates in your IDE when you click the overlay, set the `framework.ide` option:

```yaml
# config/packages/dev/framework.yaml (or framework.yaml)
framework:
    ide: 'phpstorm://open?file=%%f&line=%%l'
```

Examples:

- **PhpStorm**: `phpstorm://open?file=%%f&line=%%l`
- **VS Code**: `vscode://file/%%f:%%l`
- **Cursor**: `cursor://file/%%f:%%l` (or same as VS Code if your Cursor setup uses it)
- **JetBrains Fleet**: `fleet://open?path=%%f&line=%%l` (or use PhpStorm URL if Fleet shares the same handler)
- **Sublime Text**: `subl://open?url=file://%%f&line=%%l`

## Verify

1. Clear cache: `php bin/console cache:clear --env=dev`
2. Open any page with Web Profiler enabled.
3. In the toolbar you should see a `</>` icon (Twig Inspector).
4. Check the “Enable” checkbox and reload; then **click the `</>` icon** so it turns **green**, and hover over the page to see the blue highlight and popup.

For the full overlay flow (green/yellow icon, highlight, popup, open in IDE), see [USAGE.md](USAGE.md).

## Upgrading

See [UPGRADING.md](UPGRADING.md) for version-specific upgrade notes.
