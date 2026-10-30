# Security Policy

## Table of contents

- [Security considerations for integrators](#security-considerations-for-integrators)
- [Threat model (dev tool)](#threat-model-dev-tool)
- [Bundle security measures](#bundle-security-measures)
- [Attack surface](#attack-surface)
- [Threats and mitigations](#threats-and-mitigations)
- [Browser script and CSP notes](#browser-script-and-csp-notes)
- [Secrets and cryptography](#secrets-and-cryptography)
- [Logging](#logging)
- [Dependencies and updates](#dependencies-and-updates)
- [Permissions and exposure](#permissions-and-exposure)
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

## Attack surface

| Input | Source | Where it is handled |
| ----- | ------ | ------------------- |
| Bundle configuration (`nowo_twig_inspector.*`) | Operator (YAML, trusted) | `Configuration` tree (typed, validated enums); `NowoTwigInspectorExtension` hard-fails outside `dev`/`test` |
| `GET /_template/{template}?line=N` | Browser of the developer (route imported for `dev`/`test` only) | `OpenTemplateController`: env gate, template name validation, path containment, positive line |
| Inspector cookie (`cookie_name`, default `twig_inspector_is_active`) | Browser | Only read as an on/off flag; never stored or echoed |
| Rendered HTML (template names in HTML comments) | Application templates, possibly showing user content | Parsed by the overlay script (`block-storage.ts`) and shown as **text only** (see below) |
| CLI `nowo:twig-inspector:install` | Developer shell | Rejects any env other than `dev`/`test`; writes only under `config/` of the project |

There are no forms, no Messenger handlers, no outgoing HTTP requests, no subprocesses and no persistence.

## Threats and mitigations

| Threat | Applies? | Mitigation |
| ------ | -------- | ---------- |
| Path traversal / arbitrary file disclosure (`/_template/{template}`) | Yes | Rejects `..`, null bytes and absolute paths; the resolved file must live inside a registered Twig path; 404 outside `dev`/`test` |
| Open redirect | Low | The redirect target is built by Symfony `FileLinkFormatter` from `framework.ide` (operator config) and the resolved template path, never from a raw URL parameter |
| DOM XSS in the overlay tooltip | Yes (dev only) | Template names come from HTML comments in the page, which a crafted user-content comment could imitate. Since the 2026-10-30 hardening the tooltip is built with text nodes and `<br>` elements (`Overlay::renderInfo`), never `innerHTML` (REQ-UX-001) |
| Script-context injection of the overlay config | Low | The profiler toolbar serialises `collector.config` with `JSON_HEX_TAG`, `JSON_HEX_AMP`, `JSON_HEX_APOS` and `JSON_HEX_QUOT`, so a configured value cannot close the `<script>` element |
| Reflected/stored XSS in the profiler panel | Low | Panel values are rendered through Twig auto-escaping; no `|raw` on collected data |
| CSRF | No | The only HTTP endpoint is a side-effect-free `GET` redirect (no state change, no session write) |
| Injection / code execution, deserialization, SSRF | No | No eval, no shell, no `unserialize()` of request data, no outgoing requests |
| Information disclosure in production | Yes (misconfiguration) | Hard-fail at container build outside `dev`/`test`; runtime env + debug gate; Flex recipe registers the bundle for `dev`/`test` only |

## Browser script and CSP notes

- The overlay is one IIFE (`src/Resources/views/assets/dist/index.min.js`) inlined by the WebProfiler toolbar template together with a small inline config `<script>` and an inline tab script in the profiler panel. These follow the WebProfiler model: a strict `script-src` without `'unsafe-inline'` (or a nonce that the profiler templates do not receive) blocks the overlay. Relax the CSP **in `dev` only** (the Symfony WebProfiler needs the same), never in production.
- The script uses no `eval`, no `new Function` and no inline event-handler attributes; DOM updates use `textContent`, `createElement` and `replaceChildren`.

## Secrets and cryptography

The bundle uses no cryptography, keys, tokens or credentials and stores nothing. Nothing in the repository, the Flex recipe or the generated config contains secrets; do not add any to `config/packages/dev/nowo_twig_inspector.yaml`.

## Logging

- PHP: the bundle writes no log entries (no PSR-3 logger, no `error_log`, no `dump`).
- Browser: the overlay logs to the developer console (`[twig-inspector]` prefix): script load with build time, overlay toggles, filter changes and selected template names. It never logs cookies, request headers, session data or form values.

## Dependencies and updates

- Runtime dependencies are Symfony components and Twig only (`composer.json` `require`); the frontend has dev-only build dependencies (Vite, Sass, Vitest) and ships a prebuilt IIFE with no runtime npm packages.
- CI runs `composer audit --locked` on every push and pull request (`.github/workflows/ci.yml`); a reported advisory fails the build and is fixed before the next release.
- Dependabot opens weekly update PRs for Composer and GitHub Actions; security updates are merged and released as a patch version as soon as CI is green. The pnpm build toolchain is refreshed by the maintainers (`pnpm update`, `pnpm audit`) and the CI `frontend` job rebuilds the assets and runs Vitest on every change.
- Locally: `make update` / `composer audit` and `pnpm audit` before tagging (see the release checklist below).

## Permissions and exposure

- Exposed only in `dev`/`test`: the `/_template/{template}` route (import it with `when@dev` / `when@test`), the HTML comments, the overlay script and the WebProfiler panel.
- There is no role check because the tool is never reachable in production: the container refuses to build outside `dev`/`test` and the controller returns 404 in any other environment. If a shared `dev` instance is reachable by other people, protect it at the network or firewall level as you would protect the WebProfiler.

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
| **AI security audit (REQ-SEC-004)** | Grade **Pass (good)** / risk **Low** (re-audit 2026-07-29: dev/test only, DI hard-fail elsewhere). Recorded in the Nowo monorepo `BUNDLES_SECURITY_ANALYSIS.md`. |

Record confirmation in the release PR or tag notes.

