import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  defaultConfig,
  getConfig,
  applyThemeAndAccessibility,
  prefersReducedMotion,
} from './config';

/** jsdom does not provide matchMedia; mock it for config tests that need it. */
function mockMatchMedia(matches = false): void {
  Object.defineProperty(window, 'matchMedia', {
    writable: true,
    value: vi.fn((query: string) => ({ matches, media: query })),
  });
}

describe('defaultConfig', () => {
  it('has expected keys and defaults', () => {
    expect(defaultConfig.cookie_name).toBe('twig_inspector_is_active');
    expect(defaultConfig.overlay_theme).toBe('light');
    expect(defaultConfig.overlay_compact).toBe(false);
    expect(defaultConfig.reduced_motion).toBe(false);
    expect(defaultConfig.keyboard_shortcut).toBe('Ctrl+Shift+T');
  });
});

describe('getConfig', () => {
  const originalConfig = (window as Window & { __twig_inspector_config?: object }).__twig_inspector_config;

  beforeEach(() => {
    (window as Window & { __twig_inspector_config?: object }).__twig_inspector_config = undefined;
  });

  afterEach(() => {
    (window as Window & { __twig_inspector_config?: object }).__twig_inspector_config = originalConfig;
  });

  it('returns default config when window.__twig_inspector_config is not set', () => {
    const config = getConfig();
    expect(config.cookie_name).toBe('twig_inspector_is_active');
    expect(config.overlay_theme).toBe('light');
    expect(config.keyboard_shortcut).toBe('Ctrl+Shift+T');
  });

  it('merges partial config from window.__twig_inspector_config', () => {
    (window as Window & { __twig_inspector_config?: object }).__twig_inspector_config = {
      overlay_theme: 'dark',
      keyboard_shortcut: 'Meta+Shift+T',
    };
    const config = getConfig();
    expect(config.overlay_theme).toBe('dark');
    expect(config.keyboard_shortcut).toBe('Meta+Shift+T');
    expect(config.cookie_name).toBe('twig_inspector_is_active');
  });

  it('uses defaults for missing keys', () => {
    (window as Window & { __twig_inspector_config?: object }).__twig_inspector_config = {};
    const config = getConfig();
    expect(config).toEqual(defaultConfig);
  });

  it('merges cookie_name and overlay_compact from window config', () => {
    (window as Window & { __twig_inspector_config?: object }).__twig_inspector_config = {
      cookie_name: 'custom_cookie',
      overlay_compact: true,
    };
    const config = getConfig();
    expect(config.cookie_name).toBe('custom_cookie');
    expect(config.overlay_compact).toBe(true);
    expect(config.overlay_theme).toBe('light');
  });
});

describe('prefersReducedMotion', () => {
  it('returns boolean from matchMedia', () => {
    mockMatchMedia(false);
    expect(prefersReducedMotion()).toBe(false);
    mockMatchMedia(true);
    expect(prefersReducedMotion()).toBe(true);
  });
});

describe('applyThemeAndAccessibility', () => {
  beforeEach(() => {
    mockMatchMedia(false);
    delete document.documentElement.dataset.twigInspectorTheme;
    delete document.documentElement.dataset.twigInspectorCompact;
    delete document.documentElement.dataset.twigInspectorReducedMotion;
  });

  it('sets data attributes for light theme and compact false', () => {
    applyThemeAndAccessibility({
      ...defaultConfig,
      overlay_theme: 'light',
      overlay_compact: false,
      reduced_motion: false,
    });
    expect(document.documentElement.dataset.twigInspectorTheme).toBe('light');
    expect(document.documentElement.dataset.twigInspectorCompact).toBe('false');
    expect(document.documentElement.dataset.twigInspectorReducedMotion).toBeDefined();
  });

  it('sets dark theme when overlay_theme is dark', () => {
    applyThemeAndAccessibility({
      ...defaultConfig,
      overlay_theme: 'dark',
    });
    expect(document.documentElement.dataset.twigInspectorTheme).toBe('dark');
  });

  it('sets compact true when overlay_compact is true', () => {
    applyThemeAndAccessibility({
      ...defaultConfig,
      overlay_compact: true,
    });
    expect(document.documentElement.dataset.twigInspectorCompact).toBe('true');
  });

  it('respects reduced_motion from config', () => {
    applyThemeAndAccessibility({
      ...defaultConfig,
      reduced_motion: true,
    });
    expect(document.documentElement.dataset.twigInspectorReducedMotion).toBe('true');
  });

  it('uses prefers-color-scheme for overlay_theme auto (dark)', () => {
    const matchMediaMock = vi.spyOn(window, 'matchMedia').mockImplementation((query: string) => {
      if (query === '(prefers-color-scheme: dark)') {
        return { matches: true } as MediaQueryList;
      }
      return { matches: false } as MediaQueryList;
    });
    applyThemeAndAccessibility({
      ...defaultConfig,
      overlay_theme: 'auto',
    });
    expect(document.documentElement.dataset.twigInspectorTheme).toBe('dark');
    matchMediaMock.mockRestore();
  });

  it('uses prefers-color-scheme for overlay_theme auto (light)', () => {
    const matchMediaMock = vi.spyOn(window, 'matchMedia').mockImplementation((query: string) => {
      if (query === '(prefers-color-scheme: dark)') {
        return { matches: false } as MediaQueryList;
      }
      return { matches: false } as MediaQueryList;
    });
    applyThemeAndAccessibility({
      ...defaultConfig,
      overlay_theme: 'auto',
    });
    expect(document.documentElement.dataset.twigInspectorTheme).toBe('light');
    matchMediaMock.mockRestore();
  });
});
