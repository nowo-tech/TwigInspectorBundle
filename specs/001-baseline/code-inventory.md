# Code inventory — 100% traceability

**Baseline spec**: [`spec.md`](spec.md)  
**Package**: `nowo-tech/twig-inspector-bundle`  
**Last audited**: 2026-07-07

This file proves that **every production source artifact** under `src/` is referenced by the baseline specification. Test-only files under `tests/` and demo trees are out of Packagist scope unless promoted in the spec.

## PHP classes (`src/**/*.php`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `NowoTwigInspectorBundle.php` | Bundle entry | FR-BUNDLE-001 |
| `BoxDrawings.php` | HTML comment prefixes | FR-TWIG-003 |
| `Command/InstallCommand.php` | CLI install | FR-CLI-001 |
| `Controller/OpenTemplateController.php` | IDE open route | FR-IDE-001, FR-SEC-001 |
| `DataCollector/TwigInspectorCollector.php` | Web Profiler panel | FR-PROF-001, FR-PROF-002 |
| `DependencyInjection/Configuration.php` | Config tree | FR-CFG-001 |
| `DependencyInjection/NowoTwigInspectorExtension.php` | DI extension | FR-CFG-002 |
| `DependencyInjection/Compiler/TwigPathsPass.php` | Twig namespace path | FR-TWIG-001 |
| `EventSubscriber/ControllerCommentSubscriber.php` | Controller HTML comments | FR-CTRL-001 |
| `EventSubscriber/ControllerRenderSubscriber.php` | Controller metrics | FR-PROF-003 |
| `RequestStack/LegacyRequestStackInterface.php` | Symfony 5 compat | FR-COMPAT-001 |
| `RequestStack/MainOrMasterRequestProvider.php` | Request abstraction | FR-COMPAT-001 |
| `RequestStack/RequestStackMainOrMasterAdapter.php` | Main/master adapter | FR-COMPAT-001 |
| `Twig/DebugInfoNodeVisitor.php` | AST injection | FR-TWIG-002 |
| `Twig/HtmlCommentsExtension.php` | Runtime comment wrap | FR-TWIG-003, FR-TWIG-004 |
| `Twig/NodeReference.php` | Compile/runtime DTO | FR-TWIG-002 |
| `Twig/Node/NodeEnd.php` | Compiled end node | FR-TWIG-002 |
| `Twig/Node/NodeStart.php` | Compiled start node | FR-TWIG-002 |
| `Twig/TwigInspectorExtension.php` | Node visitor registration | FR-TWIG-002 |

## TypeScript production (`src/Resources/assets/src/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `index.ts` | Toolbar entry, cookie, shortcuts | FR-UI-001, FR-UI-002 |
| `config.ts` | Runtime config merge | FR-UI-003 |
| `types.ts` | Shared interfaces | FR-UI-003 |
| `models.ts` | Template/Block models | FR-UI-004 |
| `block-storage.ts` | DOM comment scan | FR-UI-005 |
| `overlay.ts` | Hover overlay & click | FR-UI-006 |
| `filter-match.ts` | Template filter | FR-UI-007 |
| `shortcut.ts` | Keyboard matching | FR-UI-002 |
| `logger.ts` | Debug logging | FR-UI-008 |
| `style.scss` | Overlay styling | FR-UI-009 |

## Legacy JavaScript (`src/Resources/public/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `assets/src/index.js` | Legacy overlay (ES5) | FR-UI-010 |
| `assets/src/style.css` | Legacy styles | FR-UI-010 |

## Symfony config (`src/Resources/config/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `services.yaml` | Service wiring | FR-DI-001 |
| `routes.yaml` | Dev/test route | FR-IDE-001 |
| `packages/security.yaml` | Firewall for `/_template/` | FR-SEC-002 |

## Twig views (`src/Resources/views/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Collector/template.html.twig` | Profiler panel UI | FR-PROF-001 |
| `Collector/toolbar_item.html.twig` | Toolbar icon | FR-UI-001 |
| `Icon/twig-inspector.svg` | Toolbar glyph | FR-UI-001 |

## Build artifacts (generated, not hand-edited)

| Output path | Produced by | Spec section |
| --- | --- | --- |
| `Resources/views/assets/dist/index.min.js` | `pnpm build` (Vite) | FR-BUILD-001 |
| `Resources/views/assets/dist/style.min.css` | `pnpm build` (Sass) | FR-BUILD-001 |
| `Resources/public/assets/dist/index.min.js` | Legacy build pipeline | FR-BUILD-001, FR-UI-010 |
| `Resources/public/assets/dist/style.min.css` | Legacy build pipeline | FR-BUILD-001, FR-UI-010 |

## Coverage summary

| Category | Files | Mapped |
| --- | ---: | ---: |
| PHP classes | 19 | 19 |
| TS/SCSS production | 10 | 10 |
| Legacy JS/CSS | 2 | 2 |
| YAML config | 3 | 3 |
| Twig/SVG views | 3 | 3 |
| **Total production sources** | **37** | **37** |

Build artifacts are documented as outputs of listed sources; they are not counted as separate authoring units.

Audit: `find src -type f ! -path '*/assets/dist/*' ! -name '*.test.ts' | wc -l` → **37** (includes legacy `Resources/public/assets/src/*`; excludes Vite `dist/` outputs listed above).
