# FrankenPHP worker mode audit (kernel not reset between requests)

| Field | Value |
|-------|-------|
| Package | `nowo-tech/twig-inspector-bundle` (`symfony-bundle`, dev/test only) |
| Audited revision | `v1.1.5` |
| Audit date | 2026-09-25 |
| Method | Manual review of every PHP file under `src/` (Twig extensions, node visitor, nodes, subscribers, data collector, controller, command, DI extension, compiler pass, `Resources/config/*.yaml`); HttpKernel request-stack behaviour cross-checked in the installed `symfony/http-kernel` v7.4.18 (dev dependency) |
| **Verdict** | ✅ **Viable under scenario B** — fully compatible with FrankenPHP worker when the kernel is not reset between requests; controller map is freed on terminate (and weakly keyed), rendering / owned buffers and collector state reset per main request |
| Remediation (2026-09-23 / 2026-09-25) | W-01, W-02, W-03, W-04 resolved (`src/EventSubscriber/ControllerRenderSubscriber.php`, `src/Twig/HtmlCommentsExtension.php`, `src/BoxDrawings.php`, `src/DataCollector/TwigInspectorCollector.php`). Regression tests simulate consecutive requests on the same instances without `reset()` (`tests/Unit/EventSubscriber/ControllerRenderSubscriberTest.php`, `tests/Unit/Twig/HtmlCommentsExtensionTest.php`, `tests/Unit/DataCollector/TwigInspectorCollectorTest.php`) and assert `kernel.reset` registration (`tests/Integration/BundleIntegrationTest.php`) |

## Execution model assumed

FrankenPHP worker mode boots the Symfony kernel once per worker and serves many requests with the same container. This audit assumes the **strict** variant: the kernel is **not** rebooted between requests, so every shared service, static property and PHP global survives from one request to the next. Two scenarios are evaluated:

- **A — kernel not rebooted, `services_resetter` still runs:** services tagged `kernel.reset` (or implementing `ResetInterface`) are reset between requests.
- **B — no reset at all:** nothing is reset; any per-request state kept in a service leaks into the next request.

A bundle that is safe under **B** is safe under **A** and under classic mode / PHP-FPM.

The bundle refuses to load outside `dev` / `test` (`src/DependencyInjection/NowoTwigInspectorExtension.php:35-41`, `src/DevEnvironments.php`), so everything below only matters for developers running FrankenPHP in worker mode locally (as the demo does).

## Summary

| Area | Status | Notes |
|------|--------|-------|
| Mutable state in shared services | ✅ (was ⚠️ Medium) | `ControllerRenderSubscriber` uses a `WeakMap<Request, …>` cleared on terminate; `HtmlCommentsExtension` / `BoxDrawings` state is reset when a new main request is detected |
| Static properties / `static` locals | ✅ | None; `DevEnvironments` only has constants and pure static methods |
| `ResetInterface` / `kernel.reset` coverage | ✅ | `ControllerRenderSubscriber`, `HtmlCommentsExtension`, `BoxDrawings` implement `ResetInterface` (tagged `kernel.reset` through autoconfiguration); the collector is reset by the profiler and at the start of `collect()` |
| Request / user / locale captured in services | ✅ | `RequestStack` is read at call time; nothing captured in constructors |
| Superglobals, `$_ENV`, `putenv`, `ini_set`, `setlocale`, timezone | ✅ | None |
| Doctrine / EntityManager | ✅ N/A | No persistence |
| Output, headers, `exit`, shutdown functions | ✅ (was ⚠️ Low accepted) | `HtmlCommentsExtension` tracks owned `ob_*` levels and discards only those on `reset()` / new main request (W-04) |
| Resources (files, sockets, cURL) held open | ✅ | None |
| Memory growth across requests | ✅ (was ⚠️ Medium) | Controller entries removed on terminate and freed with their request; `previousContent` dropped on the next main request |
| Blocking I/O and timeouts | ✅ | Only `realpath()` on template paths in `OpenTemplateController` |
| Third-party static state | ✅ | Reads Twig `ProfilerExtension::$actives` by reflection (Symfony-owned, reset by the profiler) |
| PHPStan FrankenPHP rulesets | ✅ | `ruleset-classic.neon` + `ruleset-worker.neon` included in `phpstan.neon.dist:18-19` |

A worker demo exists: `demo/symfony8/docker/frankenphp/Caddyfile:15` declares a `worker` block (`Caddyfile.dev` runs in classic mode).

## Services reviewed

| Service | Shared | Mutable state | Scenario A | Scenario B |
|---------|--------|---------------|------------|------------|
| `Nowo\TwigInspectorBundle\EventSubscriber\ControllerRenderSubscriber` | yes, `kernel.reset` | `WeakMap $controllersByMasterRequest` (removed on terminate) | ✅ | ✅ |
| `Nowo\TwigInspectorBundle\Twig\HtmlCommentsExtension` | yes (Twig extension), `kernel.reset` | `?string $previousContent`, `int $nestingLevel`, `WeakReference` to its main request, `list<int> $ownedBufferLevels` | ✅ | ✅ reset per main request; owned buffers discarded |
| `Nowo\TwigInspectorBundle\BoxDrawings` | yes, `kernel.reset` | `int $charsetIndex`, `int $length` (reset by the extension) | ✅ | ✅ |
| `Nowo\TwigInspectorBundle\DataCollector\TwigInspectorCollector` | yes (`data_collector`) | `array $data`, reset by the profiler and at the start of `collect()` | ✅ | ✅ |
| `Nowo\TwigInspectorBundle\EventSubscriber\ControllerCommentSubscriber` | yes | none (`readonly`) | ✅ | ✅ |
| `Nowo\TwigInspectorBundle\Twig\TwigInspectorExtension` | yes | none (creates `DebugInfoNodeVisitor` at compile time) | ✅ | ✅ |
| `Nowo\TwigInspectorBundle\RequestStack\RequestStackMainOrMasterAdapter` | yes | none (`readonly` stack) | ✅ | ✅ |
| `nowo_twig_inspector.controller.open_template` (`OpenTemplateController`) | yes (public) | none (`readonly`) | ✅ | ✅ |
| `Nowo\TwigInspectorBundle\Command\InstallCommand` | CLI only | not on the HTTP path | N/A | N/A |

`DebugInfoNodeVisitor`, `NodeStart`, `NodeEnd` only run at template compile time. `NodeReference` is a per-render value object (`uniqid()` id).

## Findings

### W-01 — `ControllerRenderSubscriber` never removes its per-request entries (Medium)

- **Where:** `src/EventSubscriber/ControllerRenderSubscriber.php:30` (`$controllersByMasterRequest`), `:60-76` (`onController()` appends under `spl_object_id($master)`), `:83-92` (`onTerminate()`), `:102-105` (`getControllersForRequest()`).
- **Worker impact:** `onTerminate()` only unsets the entry when `getMainOrMasterRequest()` returns the terminating request. On `kernel.terminate` the request stack is already empty: `HttpKernel::handle()` pops the request in its `finally` block (`vendor/symfony/http-kernel/HttpKernel.php:93` in v7.4.18) and `terminate()` does not push it back (`:110-118`). So `$master` is `null`, the method returns early and the entry is never removed. The subscriber has no `reset()`, so this happens under A and B. In classic mode the process dies and the bug is invisible; in a worker the array lives for the whole worker. Because PHP recycles `spl_object_id()` values once the old `Request` is freed, new requests often reuse an old key and keep appending to it: the list grows on every request, and the Twig Inspector profiler panel shows controllers (with inflated counts) from earlier requests. Memory grows with the number of controllers executed since the worker started. It exposes controller class names only, and only in dev/test.
- **Recommendation:** clear the map in `onTerminate()` using `$event->getRequest()` directly (no request-stack lookup) or on `kernel.finish_request` for the main request, and implement `ResetInterface` (`reset(): $this->controllersByMasterRequest = []`) as a safety net. Keying by `WeakMap<Request, list<...>>` would also remove the id-reuse problem.
- **Status:** Resolved — the map is a `WeakMap` keyed by the main request object (no `spl_object_id()` reuse), `onTerminate()` unsets the entry for `$event->getRequest()` without consulting the (empty) request stack, and `reset()` drops everything (`src/EventSubscriber/ControllerRenderSubscriber.php`). Entries of a request that never reaches `kernel.terminate` are freed with the request object.

### W-02 — Twig comment rendering state carries over between requests (Low)

- **Where:** `src/Twig/HtmlCommentsExtension.php:27` (`$previousContent`), `:30` (`$nestingLevel`), updated in `end()` at `:132-148`; `src/BoxDrawings.php:26` (`$charsetIndex`), `:30` (`$length`), updated in `blockChanged()` at `:63-72`.
- **Worker impact:** the first block rendered in a new request is compared with the last wrapped content of the previous request, and the box-drawing charset continues from where the previous request left off. This only changes which box characters appear in the HTML comments (and, in rare cases, the nesting level shown). `$previousContent` also keeps one copy of the last wrapped block (up to the size of a full page) in memory per worker. No data crosses users in a way that matters, since the bundle is dev-only and the content is the developer's own HTML.
- **Recommendation:** reset `previousContent`, `nestingLevel` and the `BoxDrawings` counters at the start of each main request (or implement `ResetInterface` on both services and tag them `kernel.reset`).
- **Status:** Resolved — both classes implement `ResetInterface` (`BoxDrawings::reset()`, `HtmlCommentsExtension::reset()` which also resets `BoxDrawings`); `HtmlCommentsExtension::end()` remembers the main request with a `WeakReference` and calls `reset()` when a different main request is rendering, so the state never outlives the request even without `kernel.reset` (`src/Twig/HtmlCommentsExtension.php`, `src/BoxDrawings.php`).

### W-03 — Collector keeps previous data when the inspector is disabled (Low, scenario B only)

- **Where:** `src/DataCollector/TwigInspectorCollector.php:117-137` (`collect()` returns before overwriting `templates`, `blocks`, totals when the cookie is off), `:198-204` (`template_times` only written by `lateCollect()`), `:290-303` (`reset()`).
- **Worker impact:** under A the profiler calls `reset()` between requests, so the data is fresh. Under B, a request without the inspector cookie keeps the template/block lists and times of the previous inspected request in the serialized profile. Dev-only, profiler panel only.
- **Recommendation:** start `collect()` with `$this->reset()` so each profile is self-contained.
- **Status:** Resolved — `collect()` now starts with `$this->reset()` (`src/DataCollector/TwigInspectorCollector.php`).

### W-04 — Output buffers opened in `start()` depend on Twig to be closed on exceptions (Low)

- **Where:** `src/Twig/HtmlCommentsExtension.php` (`ob_start()` in `start()`, `ob_get_clean()` / `ob_end_clean()` in `end()` / `reset()`).
- **Worker impact:** if a template throws between `start()` and `end()`, a leftover buffer could stay open in the worker when Twig does not unwind buffers (e.g. `use_yield: true`).
- **Recommendation:** track buffer levels opened by the extension and close only those on `reset()` / new main request.
- **Status:** Resolved — `ownedBufferLevels` records each level after `ob_start()`; `end()` / `reset()` / new-main-request discard only matching owned levels, without closing unrelated outer buffers (`src/Twig/HtmlCommentsExtension.php`). Covered by `testResetClosesLeakedOwnedBuffersWithoutTouchingOuterBuffers`.

No other findings. There are no static properties, superglobals or persistent resources.

## Usage recommendations in worker mode

- Register the bundle only for `dev` / `test` (the extension already fails closed otherwise).
- The "Controllers" section of the profiler panel is reliable in worker mode (W-01 resolved).
- Worker mode without kernel reboot and without `services_resetter` is supported (scenario B).
- Subclasses of `HtmlCommentsExtension` / `BoxDrawings` must not add more cross-request state without implementing `reset()`.

## Re-audit triggers

Re-run this audit when a change adds: a property to any subscriber, Twig extension or `BoxDrawings`; a new listener on `kernel.terminate` or `kernel.finish_request`; a change in how `HtmlCommentsExtension` uses output buffering; or support for loading the bundle outside dev/test.
