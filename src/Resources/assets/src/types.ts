/**
 * Shared types and global declarations for the Twig Inspector.
 */

declare global {
  interface Window {
    /** Optional config injected by the profiler toolbar (cookie name, theme, shortcut, etc.). */
    __twig_inspector_config?: InspectorConfigRaw;
  }
}

/** Config shape injected by the toolbar (all optional). */
export interface InspectorConfigRaw {
  cookie_name?: string;
  overlay_theme?: string;
  overlay_compact?: boolean;
  reduced_motion?: boolean;
  keyboard_shortcut?: string;
}

/** Resolved inspector config (all keys present). */
export interface InspectorConfig {
  cookie_name: string;
  overlay_theme: string;
  overlay_compact: boolean;
  reduced_motion: boolean;
  keyboard_shortcut: string;
}

/**
 * Template metadata shown in the overlay (display name and link to open in IDE).
 */
export interface Template {
  /** Display name (e.g. "template.html.twig [/path]"). */
  name: string;
  /** URL to open in the IDE (e.g. /_template/... or ide://...). */
  link: string;
}

/**
 * A DOM element (block) with its associated Twig template(s).
 * One element can correspond to multiple templates when blocks are nested.
 */
export interface Block {
  /** Index in the storage arrays. */
  index: number;
  /** The DOM element. */
  element: HTMLElement;
  /** Templates that rendered this block. */
  templates: Template[];
  /** Renders template names as HTML for the tooltip. */
  toString(): string;
}

export {};
