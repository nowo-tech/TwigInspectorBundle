# Feature Specification: TwigInspectorBundle baseline (100% code coverage)

**Feature Branch**: `001-baseline`  
**Created**: 2026-07-07  
**Status**: Active  
**Input**: Backfill GitHub Spec Kit baseline documenting 100% of production code in `src/`.

**Related docs**: [`docs/SPEC-DRIVEN-DEVELOPMENT.md`](../../docs/SPEC-DRIVEN-DEVELOPMENT.md), [`docs/CONFIGURATION.md`](../../docs/CONFIGURATION.md), [`docs/USAGE.md`](../../docs/USAGE.md)  
**Code inventory (traceability)**: [`code-inventory.md`](code-inventory.md)

---

## Summary

**Package**: `nowo-tech/twig-inspector-bundle`  
**Configuration root**: `nowo_twig_inspector`

Symfony **dev-only** bundle: injects Twig HTML comments, Web Profiler metrics, browser overlay, and click-to-open-in-IDE workflow. Production source count follows [`SPEC-KIT.md`](../../docs/SPEC-KIT.md): **37** hand-authored units under `src/` (legacy public JS/CSS counted; Vite `dist/` outputs documented separately).

---

## User Scenarios & Testing

### User Story 1 — Enable inspector from Web Profiler (Priority: P1)

As a Symfony developer, I enable Twig Inspector from the Web Profiler toolbar, reload the page, and turn on the overlay so I can see which Twig template rendered each HTML element.

**Why this priority**: Core value proposition of the bundle.

**Independent Test**: Load a dev app with the bundle, toggle the checkbox in the `</>` dropdown, reload, click the green toolbar icon, hover an element — blue highlight and template popup appear.

**Acceptance Scenarios**:

1. **Given** `kernel.debug=true` and the bundle registered in `dev`, **When** the integrator checks "Enable" in the profiler dropdown and reloads, **Then** cookie `twig_inspector_is_active=true` is set (name configurable via `cookie_name`).
2. **Given** the inspector cookie is active, **When** the toolbar icon is green, **Then** hovering DOM nodes shows overlay tooltips with template names parsed from HTML comments.
3. **Given** the inspector is disabled (cookie absent/false), **When** a page renders, **Then** `HtmlCommentsExtension` skips injection (no performance cost beyond compile-time visitor registration).

---

### User Story 2 — Click to open template in IDE (Priority: P1)

As a developer, I click a highlighted element to open the corresponding Twig template at the correct line in my IDE.

**Why this priority**: Primary workflow accelerator after visual inspection.

**Independent Test**: Click overlay element → browser navigates to `/_template/{name}?line=N` → redirect to IDE URL from `debug.file_link_formatter`.

**Acceptance Scenarios**:

1. **Given** `framework.ide` configured, **When** user clicks a single-template block, **Then** `OpenTemplateController` validates the template, resolves filesystem path, and redirects to IDE link.
2. **Given** environment is `prod`, **When** `/_template/...` is requested, **Then** response is 404.
3. **Given** template name contains `..` or NUL, **When** open is attempted, **Then** 404 without exposing paths.

---

### User Story 3 — Profiler metrics (Priority: P2)

As a developer, I inspect templates, blocks, and controllers used on the request in the Twig Inspector profiler panel, including optional render timings.

**Why this priority**: Complements overlay with aggregate metrics.

**Independent Test**: Open profiler panel `twig_inspector` after a request with inspector enabled — tables list templates, blocks, controllers; optional ms timings when `enable_metrics=true`.

**Acceptance Scenarios**:

1. **Given** HTML comments injected, **When** `TwigInspectorCollector::collect()` runs, **Then** templates and blocks are parsed from comment regex and deduplicated with render counts.
2. **Given** `enable_metrics=true` and Twig profiler active, **When** `lateCollect()` runs, **Then** per-template durations appear in the panel.
3. **Given** `ControllerRenderSubscriber` recorded controllers, **When** panel renders, **Then** main vs fragment badges and invocation counts display.

---

### User Story 4 — Install without Flex (Priority: P2)

As an integrator without Symfony Flex, I run a console command to publish config and routes.

**Independent Test**: `php bin/console nowo:twig-inspector:install --env=dev` creates YAML and appends route import.

**Acceptance Scenarios**:

1. **Given** no `config/packages/dev/nowo_twig_inspector.yaml`, **When** install runs, **Then** commented template YAML is written with documented defaults.
2. **Given** `config/routes.yaml` exists, **When** install runs, **Then** bundle route import is appended if missing.
3. **Given** config already exists, **When** install runs without `--force`, **Then** existing YAML is preserved.

---

### User Story 5 — Configure exclusions and overlay UX (Priority: P3)

As an integrator, I exclude noisy templates/blocks and tune overlay theme, compact mode, reduced motion, and keyboard shortcuts.

**Acceptance Scenarios**:

1. **Given** `excluded_templates` with wildcards, **When** matching template renders, **Then** no HTML comments wrap its output.
2. **Given** `overlay_theme=dark`, **When** overlay loads, **Then** `data-twig-inspector-theme` is applied on `<html>`.
3. **Given** `keyboard_shortcut=Ctrl+Shift+T`, **When** key chord pressed, **Then** overlay toggles without conflicting with empty shortcut (disabled).

---

### Edge Cases

- Sub-requests (fragments): Twig comment injection skipped by default (`inject_on_sub_requests=false`); controller comments still wrap fragment responses when cookie active on master request.
- Profiler/WDT paths (`/_wdt`, `/_profiler`): injection skipped to avoid breaking toolbar HTML.
- Non-HTML output (JSON, plain text, Backbone `<%`): `end()` echoes buffer without wrapping.
- `headers_sent` before `end()`: buffer cleaned, no partial comments.
- `max_injection_depth > 0`: deep nesting stops wrapping but still outputs content.
- Multiple templates on one element: click opens picker instead of immediate navigation.

---

## Requirements

### Bundle & DI

- **FR-BUNDLE-001**: `NowoTwigInspectorBundle` MUST register `TwigPathsPass` and expose alias `nowo_twig_inspector` via `NowoTwigInspectorExtension`.
- **FR-DI-001**: `services.yaml` MUST wire all public services documented below with autowire defaults; `OpenTemplateController` service id `nowo_twig_inspector.controller.open_template` is public.
- **FR-CFG-001**: `Configuration` MUST define the `nowo_twig_inspector` tree with keys: `enabled_extensions`, `excluded_templates`, `excluded_blocks`, `enable_metrics`, `inject_on_sub_requests`, `cookie_name`, `max_injection_depth`, `excluded_templates_regex`, `excluded_templates_prefixes`, `excluded_blocks_regex`, `overlay_theme`, `overlay_compact`, `reduced_motion`, `keyboard_shortcut`.
- **FR-CFG-002**: Extension MUST load `services.yaml` and set `%nowo_twig_inspector.*%` parameters from merged config.
- **FR-TWIG-001**: `TwigPathsPass` MUST `addPath()` for `Resources/views` under namespace `NowoTwigInspectorBundle` on the native loader (app overrides win).

### Twig compile-time injection

- **FR-TWIG-002**: `TwigInspectorExtension` registers `DebugInfoNodeVisitor`, which wraps `ModuleNode` display bodies and `BlockNode` bodies with compiled `NodeStart`/`NodeEnd` calling `HtmlCommentsExtension::start/end` with `NodeReference` (unique `uniqid()` per node).
- **FR-TWIG-003**: `HtmlCommentsExtension` MUST wrap output in paired HTML comments using `BoxDrawings` prefixes that cycle by nesting depth; format: `<!-- {prefix} {name} [{url}] #{id}-->` … `<!-- {endPrefix} {name} [{url}] #{id}-->`.
- **FR-TWIG-004**: `shouldInspect()` MUST gate on: `kernel.debug`, cookie on master/sub-request policy, excluded profiler paths, enabled file extension, template/block exclusion lists (wildcard, regex, prefix). `end()` MUST respect `max_injection_depth`, `headers_sent`, and content-type heuristics (`isSupported`).

### Controller instrumentation

- **FR-CTRL-001**: `ControllerCommentSubscriber` on `kernel.response` (-512) MUST inject controller boundary comments when inspector active: `<!-- ┏ controller: {class}::{method} [main|fragment] template: {path} -->` with closing `<!-- ┗ /controller -->` for fragments; uses `HtmlCommentsExtension::REQUEST_ATTR_ROOT_TEMPLATE` when set.
- **FR-PROF-003**: `ControllerRenderSubscriber` MUST record controller callables per master request on `kernel.controller` and expose aggregated `{name, count, is_main}` via `getControllersForRequest()`.

### IDE route & security

- **FR-IDE-001**: Route `nowo_twig_inspector_template_link` path `/_template/{template}` (requirements: no NUL), defaults `line=1`, `frontend=true`; controller `OpenTemplateController` validates name/path and redirects via `FileLinkFormatter`.
- **FR-SEC-001**: Open template MUST 404 outside `dev`/`test`; resolved filesystem path MUST be under Twig loader paths (supports `ChainLoader`).
- **FR-SEC-002**: Security config MUST expose firewall `twig_inspector` for `^/_template/` with `security: false` (local IDE redirect only).

### Web Profiler

- **FR-PROF-001**: `TwigInspectorCollector` (id `twig_inspector`, priority 260) template `@NowoTwigInspectorBundle/Collector/template.html.twig` MUST expose templates, blocks, controllers, enabled flag, overlay config JSON (`window.__twig_inspector_config`), and load built `index.min.js`.
- **FR-PROF-002**: Collector MUST parse Twig comment regex `/<!--\s+\S+\s+([^\s]+)\s+\[([^\]]+)\]\s+#(\w+)-->/u` and classify block vs template; optional `lateCollect()` adds Twig profiler timings when `enable_metrics=true`.

### CLI

- **FR-CLI-001**: `nowo:twig-inspector:install` MUST write env-specific YAML from `CONFIG_TEMPLATE` and ensure routes import; options `--env` (default `dev`), `--force`.

### Symfony compatibility

- **FR-COMPAT-001**: `RequestStackMainOrMasterAdapter` MUST implement `MainOrMasterRequestProvider` using `getMainRequest()` when available else legacy `getMasterRequest()`.

### Frontend overlay (TypeScript)

- **FR-UI-001**: `index.ts` MUST initialize only when `.sf-toolbar` exists; wire cookie checkbox reload, toolbar icon green/yellow states, and `#_twig_inspector__icon` click toggle.
- **FR-UI-002**: Keyboard: configurable shortcut toggles overlay; `Ctrl+Shift+R` rescans DOM; `Escape` closes overlay (`shortcut.ts`).
- **FR-UI-003**: `config.ts` merges `window.__twig_inspector_config` with defaults; applies theme/accessibility data attributes on `<html>`.
- **FR-UI-004**: `models.ts` defines `TemplateClass` and `BlockClass` (tooltip HTML via `toString()`).
- **FR-UI-005**: `BlockStorage.collectData()` scans DOM for Twig and controller comments; `find`/`create`/`findOrCreate` map elements to blocks; sort order: controllers before templates, main before fragment.
- **FR-UI-006**: `Overlay` handles hover veil, filter highlights, single vs multi-template click (picker), `rescan()`, `freeze()`.
- **FR-UI-007**: `filter-match.ts` OR-comma-separated case-insensitive match on template name/link.
- **FR-UI-008**: `logger.ts` provides namespaced bundle logger with test hook `clearBundleLoggerForTest`.
- **FR-UI-009**: `style.scss` styles overlay, filter veils, dark/compact/reduced-motion via `data-twig-inspector-*` attributes.
- **FR-UI-010**: Legacy `Resources/public/assets/src/index.js` + `style.css` remain supported for non-Vite consumers (reduced feature parity documented in README).

### Build

- **FR-BUILD-001**: Vite (`vite.config.ts`) MUST emit IIFE `Resources/views/assets/dist/index.min.js` and `style.min.css` from `Resources/assets/src/`; maintainers run `pnpm build` before release when TS/SCSS changes.

---

## Key Entities

- **NodeReference**: `{id, name, template, line}` — links compiled Twig nodes to runtime extension calls.
- **InspectorConfig**: `{cookie_name, overlay_theme, overlay_compact, reduced_motion, keyboard_shortcut, ...}` — serialized to profiler panel for JS.
- **Block** (frontend): DOM element + attached template list + index for overlay.
- **Processed metrics row**: `{name, count, is_main?}` for templates, blocks, or controllers in collector data.

---

## Success Criteria

- **SC-001**: 100% of production files in `src/` appear in [`code-inventory.md`](code-inventory.md) with requirement IDs (37/37 mapped).
- **SC-002**: Inspector activation adds parseable HTML comments on a standard Twig HTML page in `dev` with cookie enabled.
- **SC-003**: Click-to-IDE flow succeeds with Symfony `framework.ide` configured in demo apps.
- **SC-004**: PHPUnit + PHPStan + Vitest pass in CI (`composer qa`).
- **SC-005**: No template path disclosure when bundle disabled, cookie off, or `kernel.debug=false`.

---

## Assumptions

- Integrators register the bundle only in `dev`/`test` (recommended `--dev` dependency).
- Symfony Web Profiler toolbar is present (`symfony/web-profiler-bundle`).
- IDE opening requires valid `framework.ide` URL template in the application.
- HTML comment parsing relies on UTF-8 pages; box-drawing prefixes are intentional markers.
- Demos under `demo/` illustrate integration but are not Packagist API.

---

## Configuration reference (normative defaults)

| Key | Default | Behavior |
| --- | --- | --- |
| `enabled_extensions` | `['.html.twig']` | Only matching template paths get comments |
| `excluded_templates` | `[]` | Wildcard `*` patterns skip templates |
| `excluded_blocks` | `[]` | Wildcard patterns skip blocks |
| `enable_metrics` | `true` | Twig profiler timings in panel |
| `inject_on_sub_requests` | `false` | Fragment Twig injection off by default |
| `cookie_name` | `twig_inspector_is_active` | Activation cookie |
| `max_injection_depth` | `0` | Unlimited nesting (`0` = no cap) |
| `excluded_templates_regex` | `[]` | PCRE exclusions |
| `excluded_templates_prefixes` | `[]` | Prefix exclusions (e.g. `@Admin/`) |
| `excluded_blocks_regex` | `[]` | Block PCRE exclusions |
| `overlay_theme` | `light` | `light` \| `dark` \| `auto` |
| `overlay_compact` | `false` | Compact tooltips |
| `reduced_motion` | `false` | Accessibility: reduce animations |
| `keyboard_shortcut` | `Ctrl+Shift+T` | Empty string disables shortcut |

---

## Explicit non-goals

- Production debugging or enabling inspector in `prod`.
- Guaranteeing overlay behavior on non-HTML responses.
- Pixel-perfect Word/HTML fidelity (N/A — not a document processor).
- Committing built assets without running `pnpm build` when sources change (maintainer responsibility).

---

## Validation

| Check | Command |
| --- | --- |
| Full QA | `composer qa` or `make release-check` |
| PHP tests | `vendor/bin/phpunit` |
| Static analysis | `vendor/bin/phpstan analyse` |
| TS tests | `pnpm test` |
| Code inventory | Verify `code-inventory.md` row count matches `find src -type f` production set |

When changing behavior, update this spec, `code-inventory.md` if files added/removed, tests, and integrator docs (`USAGE.md`, `CONFIGURATION.md`, `CHANGELOG.md`).
