# Roadmap

This document outlines the direction of Twig Inspector Bundle and helps contributors and users understand upcoming priorities.


## Table of contents

- [Vision](#vision)
- [Current focus (1.x)](#current-focus-1x)
- [Current status of “future ideas”](#current-status-of-future-ideas)
- [Possible future (2.x / ideas)](#possible-future-2x-ideas)
- [Community](#community)

## Vision

Twig Inspector Bundle aims to be the **go-to development tool** for Symfony developers who work with Twig: quickly see which template or block rendered each part of the page and open it in the IDE with one click. We focus on stability, compatibility with current Symfony and Twig versions, and a minimal, non-intrusive setup.

## Current focus (1.x)

- **Stability & compatibility**: Support Symfony 6.4, 7.x, 8.x and Twig 3.8–4.x. Fix regressions and deprecations as they appear.
- **Performance**: Keep overhead minimal when the inspector is disabled (e.g. early return in `shouldInspect()` when debug is off or cookie not set).
- **Documentation**: Clear install, config, and upgrade guides so new users can adopt the bundle quickly.
- **Testing**: Maintain high test coverage (90%+ target) and CI (PHP × Symfony matrix, code style, asset build).

No breaking changes are planned for the 1.x line; new options will be additive.

## Current status of “future ideas”

What is already implemented vs. what remains from the ideas below:

| Idea | Status | Notes |
|------|--------|--------|
| **Exclusions** | ✅ Done (partial) | `excluded_templates` and `excluded_blocks` with **wildcard** support (`*`) are implemented. Regex, namespace or PHP attribute-based exclusion are not. |
| **Metrics** | ✅ Done (partial) | Web Profiler shows templates used, blocks used, usage counts and totals. **Render time per template** is not implemented. |
| **IDE support** | ✅ Done (partial) | Uses Symfony’s `FileLinkFormatter` and `framework.ide` (PhpStorm, VS Code, Sublime, Atom). Broader schemes or platform-specific handling are not. |
| **UI (theme/layout)** | ❌ Not done | No dark mode, compact tooltip or overlay theme options yet. |

## Possible future (2.x / ideas)

Ideas under consideration (not committed):

- **UI**: Optional theme or layout tweaks for the overlay (e.g. dark mode, compact tooltip).
- **IDE support**: Broader list of IDE / editor URL schemes and platform-specific handling (e.g. Cursor, Fleet, remote/SSH).
- **Metrics**: Richer template/block usage stats in the Web Profiler (e.g. **render time per template**).
- **Exclusions**: More flexible exclusion (e.g. by namespace, explicit regex patterns, or PHP attributes on controllers).

Additional ideas (not committed):

- **Overlay UX**: Keyboard shortcut to toggle inspector; optional search/filter by template name in the page.
- **Fragments / AJAX**: Better support for inspecting content loaded via ESI, Hinclude or AJAX (e.g. re-scan DOM after load).
- **Profiler**: Optional “last N requests” view with template usage; export/import of exclusion config for team sharing.
- **Performance**: Optional max depth for comment injection to limit overhead on very deep template trees.
- **Accessibility**: Keep overlay non-intrusive when disabled; optional reduced-motion mode.
- **Ecosystem**: Compatibility with future PHP 8.6 and Symfony 9 when released.

A major version would only be considered if we introduce breaking changes (e.g. config structure, PHP/Symfony requirements).

## Community

- **Issues & PRs**: [GitHub Issues](https://github.com/nowo-tech/twig-inspector-bundle/issues) and [Pull Requests](https://github.com/nowo-tech/twig-inspector-bundle/pulls) are welcome.
- **Contributing**: See [CONTRIBUTING.md](CONTRIBUTING.md) for code style, tests, and branch policy.
- **Security**: See [SECURITY.md](SECURITY.md) for how to report vulnerabilities.

If you rely on Twig Inspector Bundle, consider giving it a **star** on GitHub so others can discover it more easily.
