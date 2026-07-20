# Security Policy

## Table of contents

- [Security considerations for integrators](#security-considerations-for-integrators)
- [Bundle security measures](#bundle-security-measures)
- [Supported Versions](#supported-versions)
- [Reporting a Vulnerability](#reporting-a-vulnerability)
- [Release security checklist (12.4.1)](#release-security-checklist-1241)

## Security considerations for integrators

- **Development tool only**: Twig Inspector is intended for development and debugging. **Never enable it in production.**
- **Install with `--dev`**: `composer require nowo-tech/twig-inspector-bundle --dev` so the package is not present in production builds.
- **WebProfiler dependency**: The overlay and toolbar panel require `symfony/web-profiler-bundle`. Enabling WebProfiler (or this inspector) in production is a security anti-pattern.
- **Template paths**: The overlay shows template names and links that open in your IDE. Ensure your IDE/open-controller URLs are not exposed to untrusted users when the inspector is enabled.

## Threat model (dev tool)

| Asset / surface | What is exposed when active | Risk if enabled outside dev |
| --------------- | --------------------------- | --------------------------- |
| HTML comments | Template/block names, open-IDE URLs, unique ids | Structure of views; aids reconnaissance |
| Controller comments | FQCN::method, main vs fragment, root template | Application architecture disclosure |
| WebProfiler panel | Templates, blocks, controllers, timings, overlay config | Same + debug metadata |
| `/_template/{template}` | Redirect to IDE via `framework.ide` | Path disclosure; mitigated by env gate + path traversal checks |

**Residual risk:** An operator who bypasses Flex (registers the bundle for `all` / `prod`) used to get silent partial activation when `APP_DEBUG=1`. The DI extension now **hard-fails** outside `dev`/`test`. Runtime injectors also require an allowed environment, not only `kernel.debug`.

## Bundle security measures

The bundle includes measures to prevent path traversal and unauthorized access:

- **Hard-fail outside dev/test**: `NowoTwigInspectorExtension` throws `LogicException` when `kernel.environment` is not `dev` or `test` (fail-closed at container build)
- **Env + debug gate**: HTML comments and controller comments inject only when `kernel.debug` is true **and** the environment is `dev`/`test` (covers `prod` + `APP_DEBUG=1`)
- **Install command**: `nowo:twig-inspector:install` rejects `--env=prod` (and any non-dev/test env)
- **Template name validation**: Rejects path traversal attempts (`..`), null bytes, and absolute paths
- **File path verification**: Ensures resolved template paths are within allowed Twig template directories
- **Parameter validation**: Line numbers must be positive integers
- **Route restrictions**: Routes should only be available in `dev` and `test` environments
- **Prod environment block**: The "open in IDE" controller returns 404 outside `dev`/`test` even if routes were accidentally enabled (defense in depth)

Routes must be imported with `when@dev:` and `when@test:` in `config/routes.yaml`. The controller checks `kernel.environment` and returns 404 outside allowed envs without processing the request. The route pattern allows slashes in template names; security validations in the controller prevent path traversal.

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

## Reporting a Vulnerability

If you discover a security vulnerability in this project, please report it responsibly:

1. **Do not** open a public GitHub issue for security-sensitive bugs.
2. Send details to **[hectorfranco@nowo.tech](mailto:hectorfranco@nowo.tech)** (or the maintainers listed in [composer.json](../composer.json)).
3. Include a clear description, steps to reproduce, and impact if possible.
4. We will acknowledge receipt and work on a fix. We may ask for more information.
5. After a fix is released, we can coordinate on disclosure (e.g. a security advisory).

We appreciate your effort to report vulnerabilities privately so users can update before details are public.

## Release security checklist (12.4.1)

Before tagging a release, confirm:

| Item | Notes |
|------|--------|
| **SECURITY.md** | This document is current and linked from the README where applicable. |
| **`.gitignore` and `.env`** | `.env` and local env files are ignored; no committed secrets. |
| **No secrets in repo** | No API keys, passwords, or tokens in tracked files. |
| **Recipe / Flex** | Default recipe or installer templates do not ship production secrets. |
| **Input / output** | Inputs validated; outputs escaped in Twig/templates where user-controlled. |
| **Dependencies** | `composer audit` run; issues triaged. |
| **Logging** | Logs do not print secrets, tokens, or session identifiers unnecessarily. |
| **Cryptography** | If used: keys from secure config; never hardcoded. |
| **Permissions / exposure** | Bundle only for `dev`/`test`; hard-fail + install reject prod; no prod exposure. |
| **Limits / DoS** | Timeouts, size limits, rate limits where applicable. |

Record confirmation in the release PR or tag notes.

