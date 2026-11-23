/**
 * Quiet switch of the "script loaded" console line: `<html data-nowo-console="quiet">` or
 * `globalThis.NOWO_CONSOLE = 'quiet'` silences it; anything else keeps the default.
 */
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { createBundleLogger } from './logger';

describe('scriptLoaded() quiet switch', () => {
  let consoleLog: ReturnType<typeof vi.spyOn>;

  beforeEach(() => {
    consoleLog = vi.spyOn(console, 'log').mockImplementation(() => undefined);
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
    delete document.documentElement.dataset.nowoConsole;
    delete (globalThis as { NOWO_CONSOLE?: unknown }).NOWO_CONSOLE;
  });

  it('prints by default', () => {
    createBundleLogger('test', { buildTime: '2026-11-23 14:05 UTC' }).scriptLoaded();
    expect(consoleLog).toHaveBeenCalledTimes(1);
    expect(String(consoleLog.mock.calls[0]?.[0])).toContain('script loaded');
  });

  it('prints nothing with <html data-nowo-console="quiet">', () => {
    document.documentElement.dataset.nowoConsole = 'quiet';
    createBundleLogger('test').scriptLoaded();
    expect(consoleLog).not.toHaveBeenCalled();
  });

  it('prints nothing with globalThis.NOWO_CONSOLE = "quiet"', () => {
    (globalThis as { NOWO_CONSOLE?: unknown }).NOWO_CONSOLE = 'quiet';
    createBundleLogger('test').scriptLoaded();
    expect(consoleLog).not.toHaveBeenCalled();
  });

  it('keeps the default output for other values', () => {
    document.documentElement.dataset.nowoConsole = 'loud';
    (globalThis as { NOWO_CONSOLE?: unknown }).NOWO_CONSOLE = 'verbose';
    createBundleLogger('test').scriptLoaded();
    expect(consoleLog).toHaveBeenCalledTimes(1);
  });

  it('prints when there is no document', () => {
    vi.stubGlobal('document', undefined);
    createBundleLogger('test').scriptLoaded();
    expect(consoleLog).toHaveBeenCalledTimes(1);
  });

  it('prints when reading the switch throws', () => {
    Object.defineProperty(globalThis, 'NOWO_CONSOLE', {
      configurable: true,
      get() {
        throw new Error('blocked');
      },
    });
    createBundleLogger('test').scriptLoaded();
    expect(consoleLog).toHaveBeenCalledTimes(1);
  });
});
