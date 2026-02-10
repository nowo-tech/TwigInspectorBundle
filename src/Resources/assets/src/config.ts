/**
 * Inspector config: defaults, merge from toolbar, theme and accessibility.
 */

import type { InspectorConfig } from './types';

/** Default inspector config when `window.__twig_inspector_config` is not set. */
export const defaultConfig: InspectorConfig = {
  cookie_name: 'twig_inspector_is_active',
  overlay_theme: 'light',
  overlay_compact: false,
  reduced_motion: false,
  keyboard_shortcut: 'Ctrl+Shift+T',
};

/**
 * Returns the current inspector config (from toolbar or defaults).
 *
 * @returns Merged config object (cookie name, theme, compact, reduced motion, shortcut)
 */
export function getConfig(): InspectorConfig {
  const c = window.__twig_inspector_config || {};
  return {
    cookie_name: c.cookie_name ?? defaultConfig.cookie_name,
    overlay_theme: c.overlay_theme ?? defaultConfig.overlay_theme,
    overlay_compact: c.overlay_compact ?? defaultConfig.overlay_compact,
    reduced_motion: c.reduced_motion ?? defaultConfig.reduced_motion,
    keyboard_shortcut: c.keyboard_shortcut ?? defaultConfig.keyboard_shortcut,
  };
}

/**
 * Whether the user prefers reduced motion (accessibility).
 *
 * @returns True if the system or user preference requests reduced motion
 */
export function prefersReducedMotion(): boolean {
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

/**
 * Applies theme and accessibility settings to the document root (data attributes).
 *
 * @param config - Current inspector config (theme, compact, reduced_motion)
 * @returns void
 */
export function applyThemeAndAccessibility(config: InspectorConfig): void {
  const root = document.documentElement;
  const theme =
    config.overlay_theme === 'auto'
      ? window.matchMedia('(prefers-color-scheme: dark)').matches
        ? 'dark'
        : 'light'
      : config.overlay_theme;
  root.dataset.twigInspectorTheme = theme;
  root.dataset.twigInspectorCompact = config.overlay_compact ? 'true' : 'false';
  const reduce = config.reduced_motion || prefersReducedMotion();
  root.dataset.twigInspectorReducedMotion = reduce ? 'true' : 'false';
}
