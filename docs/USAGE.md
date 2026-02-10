# Using the Twig Inspector

This guide explains how to use the inspector overlay and the Web Profiler panel.

## Quick overview

- **Toolbar icon `</>`** — Opens the Twig Inspector dropdown. When the inspector is enabled (checkbox + reload), **clicking the icon** toggles the overlay:
  - **Green icon** = overlay **on**: moving the mouse over the page shows a blue highlight and a popup with the template name(s).
  - **Yellow icon** = overlay **off**: the overlay is hidden; click the icon again to turn it green and show it.
- **Blue highlight** — Indicates the HTML element under the cursor that is associated with one or more Twig templates.
- **Popup** — Shows the template name(s) that rendered that element. Click the element to open the template in your IDE.

## Step-by-step

### 1. Enable the inspector

1. In the Symfony Web Profiler toolbar (bottom of the page), find the **`</>`** icon.
2. Click it to open the Twig Inspector dropdown.
3. Check **“Enable”** (inspector … *reloads page*).
4. The page reloads. The inspector is now active (cookie is set).

### 2. Show the overlay (green icon)

1. After reload, the **`</>`** icon may be **yellow** (overlay off) or **green** (overlay on).
2. **Click the `</>` icon** so it turns **green**. The overlay is now active.
3. If the icon is already green, the overlay is already on; you can start hovering over the page.

### 3. Hover to see templates

1. **Move the mouse** over the content of the page (not the toolbar).
2. For each element under the cursor that was rendered by Twig:
   - A **blue semi-transparent highlight** appears over the element.
   - A **popup** (tooltip) shows the template name(s), e.g. `demo/_header.html.twig`, `base.html.twig`.
3. Moving to another element updates the highlight and popup.

### 4. Open template in IDE

1. **Click** the highlighted element (or the popup).
2. If a single template rendered it, the browser opens the link and your IDE opens the file (if [IDE integration](INSTALLATION.md#ide-integration-optional) is configured).
3. If several templates apply (e.g. nested blocks), a small **picker** appears; click the template you want to open.
4. Press **Esc** to close the picker or reset the overlay.

### 5. Hide the overlay (yellow icon)

1. **Click the `</>` icon** again. It turns **yellow** and the overlay disappears.
2. The inspector is still enabled (cookie is still set). Click the icon again to show the overlay (green) without reloading.

### 6. Disable the inspector

1. Open the dropdown and **uncheck “Enable”**.
2. The page reloads and the inspector is off (cookie cleared).

## Filter

In the dropdown, the **Filter** field lets you limit which blocks are highlighted:

- **Empty** — All blocks are shown (default).
- **One term** — Only blocks whose template **name** or **path** contains that text (case-insensitive), e.g. `header`, `templates/demo`.
- **Several terms** — Separate with commas for OR logic, e.g. `header, footer, instructions`.

When the filter is not empty, **persistent colored frames** (veils) are drawn around all matching blocks so you can see at a glance which parts of the page match.

## Shortcuts

- **Ctrl+Shift+T** (or the configured shortcut) — Toggle the inspector on/off (same as the checkbox; reloads).
- **Ctrl+Shift+R** — Rescan the DOM (e.g. after AJAX or dynamic content).
- **Esc** — Close the overlay picker or reset the overlay.

## Full panel

Click **“View full panel →”** in the dropdown (or open the Twig Inspector panel from the profiler) to see:

- **Templates** — Template render times (if Twig profiler is enabled) and list of templates used in the request.
- **Blocks** — List of blocks and their templates.
- **Tips & tools** — “How to use the overlay” and Twig performance tips.
- **Configuration example** — Copy-paste config snippets.

## Troubleshooting

- **No blue highlight / no popup** — Ensure the icon is **green** (click it). Ensure the inspector is **enabled** (checkbox checked and page reloaded).
- **“No template timing data”** — The Twig profiler may be disabled; template timings are optional. The overlay and “open in IDE” still work.
- **Click does not open IDE** — Configure `framework.ide` in `config/packages/dev/framework.yaml` (see [Installation – IDE integration](INSTALLATION.md#ide-integration-optional)).
