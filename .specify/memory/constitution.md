# TwigInspectorBundle Constitution

## Core Principles

### I. Dev-only debugging tool

TwigInspectorBundle is a **Symfony dev/test debugging aid**. It must never expose template paths or IDE links in production environments. All IDE-opening routes and HTML injection gates require `kernel.debug`, cookie activation, and `dev`/`test` environment checks where applicable.

### II. Spec-first, test-proven

Product behavior is defined in `specs/001-baseline/spec.md` (GitHub Spec Kit) and `docs/SPEC-DRIVEN-DEVELOPMENT.md`. **PHPUnit**, **PHPStan**, and **Vitest** are the mechanical proof. Prose-only changes without tests are not acceptable for behavioral changes.

### III. 100% code inventory traceability

Every production source file under `src/` must map to at least one requirement or code-artifact row in the baseline spec (`specs/001-baseline/code-inventory.md`). New files require spec updates in the same PR.

### IV. Consumer contract vs demos

The Packagist contract covers documented configuration, services, routes (dev/test), profiler integration, and CLI install. **`demo/`** trees are illustrative only unless explicitly promoted to stable API in the spec.

### V. Symfony & Twig compatibility

Support Symfony 6|7|8 and Twig ≥3.8. Use compatibility adapters (e.g. `RequestStackMainOrMasterAdapter`) rather than forking logic per major version when a single abstraction suffices.

## Security Requirements

- `OpenTemplateController` must validate template names (no `..`, NUL, absolute paths) and resolved paths must belong to Twig loader directories.
- Routes under `/_template/` are registered only in `dev`/`test` and use a permissive firewall for local IDE redirects.
- Cookie name is configurable; default `twig_inspector_is_active`.

## Quality Gates

- `composer qa` / `make release-check` before merge.
- PHP line coverage floor enforced by project scripts (see README).
- TypeScript assets built via `pnpm build` when `Resources/assets/` changes.

## Governance

This constitution guides Spec Kit workflows (`/speckit-*` skills). Amendments require updating this file, the baseline spec if principles affect behavior, and a note in `docs/CHANGELOG.md` when consumer-visible.

**Version**: 1.0.0 | **Ratified**: 2026-07-07 | **Last Amended**: 2026-07-07
