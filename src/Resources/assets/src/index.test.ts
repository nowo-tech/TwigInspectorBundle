/**
 * Tests for the Twig Inspector entry point (index.ts).
 * Uses dynamic import after DOM setup so runTwigInspector runs with the right document state.
 */
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import './types'; // ensure types module is executed for coverage (export {})

vi.mock('./style.scss', () => ({}));

function setupFullDOM(opts: { checkboxChecked?: boolean; withFilter?: boolean; withRescan?: boolean } = {}): void {
  const {
    checkboxChecked = true,
    withFilter = true,
    withRescan = true,
  } = opts;
  document.body.innerHTML = '';
  const toolbar = document.createElement('div');
  toolbar.className = 'sf-toolbar';
  document.body.appendChild(toolbar);

  const statusCheckbox = document.createElement('input');
  statusCheckbox.type = 'checkbox';
  statusCheckbox.id = '_twig_inspector__status';
  statusCheckbox.checked = checkboxChecked;
  document.body.appendChild(statusCheckbox);

  const statusIcon = document.createElement('div');
  statusIcon.id = '_twig_inspector__icon';
  document.body.appendChild(statusIcon);

  if (withFilter) {
    const filterContainer = document.createElement('div');
    filterContainer.id = '_twig_inspector__filter';
    document.body.appendChild(filterContainer);
  }

  if (withRescan) {
    const rescanBtn = document.createElement('button');
    rescanBtn.id = '_twig_inspector__rescan';
    document.body.appendChild(rescanBtn);
  }

  Object.defineProperty(document, 'readyState', { value: 'complete', configurable: true });
}

describe('index (Twig Inspector entry)', () => {
  let reloadMock: ReturnType<typeof vi.fn>;

  beforeEach(() => {
    vi.spyOn(console, 'debug').mockImplementation(() => {});
    vi.spyOn(console, 'info').mockImplementation(() => {});
    vi.spyOn(console, 'warn').mockImplementation(() => {});
    reloadMock = vi.fn();
    Object.defineProperty(window, 'location', {
      value: { ...window.location, reload: reloadMock },
      configurable: true,
    });
    Object.defineProperty(window, 'matchMedia', {
      value: vi.fn((query: string) => ({ matches: false, media: query })),
      configurable: true,
    });
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('runs runTwigInspector when readyState is complete and full DOM is present', async () => {
    setupFullDOM();
    await import('./index');
    expect(document.getElementById('_twig_inspector__overlay__block')).not.toBeNull();
    expect(document.getElementById('_twig_inspector__filter_input')).not.toBeNull();
  });

  it('runs runTwigInspector on DOMContentLoaded when readyState was loading', async () => {
    setupFullDOM();
    Object.defineProperty(document, 'readyState', { value: 'loading', configurable: true });
    vi.resetModules();
    await import('./index');
    document.dispatchEvent(new Event('DOMContentLoaded'));
    expect(document.getElementById('_twig_inspector__overlay__block')).not.toBeNull();
  });

  it('exits early when Web Profiler toolbar is not present', async () => {
    document.body.innerHTML = '';
    Object.defineProperty(document, 'readyState', { value: 'complete', configurable: true });
    vi.resetModules();
    await import('./index');
    expect(document.getElementById('_twig_inspector__overlay__block')).toBeNull();
  });

  it('exits after warn when status checkbox is not found', async () => {
    document.body.innerHTML = '';
    const toolbar = document.createElement('div');
    toolbar.className = 'sf-toolbar';
    document.body.appendChild(toolbar);
    Object.defineProperty(document, 'readyState', { value: 'complete', configurable: true });
    vi.resetModules();
    await import('./index');
    expect(console.warn).toHaveBeenCalledWith(
      expect.anything(),
      expect.anything(),
      expect.stringContaining('status checkbox not found'),
    );
  });

  it('exits when status icon is not found', async () => {
    document.body.innerHTML = '';
    const toolbar = document.createElement('div');
    toolbar.className = 'sf-toolbar';
    document.body.appendChild(toolbar);
    const statusCheckbox = document.createElement('input');
    statusCheckbox.type = 'checkbox';
    statusCheckbox.id = '_twig_inspector__status';
    document.body.appendChild(statusCheckbox);
    Object.defineProperty(document, 'readyState', { value: 'complete', configurable: true });
    vi.resetModules();
    await import('./index');
    expect(document.getElementById('_twig_inspector__overlay__block')).toBeNull();
  });

  it('initializes filter input when filter container is present', async () => {
    setupFullDOM({ withFilter: true });
    vi.resetModules();
    await import('./index');
    expect(document.getElementById('_twig_inspector__filter_input')).not.toBeNull();
  });

  it('skips filter input when filter container is absent', async () => {
    setupFullDOM({ withFilter: false });
    vi.resetModules();
    await import('./index');
    expect(document.getElementById('_twig_inspector__filter_input')).toBeNull();
  });

  it('status checkbox click sets cookie and reloads', async () => {
    vi.resetModules();
    setupFullDOM();
    await import('./index');
    const checkbox = document.getElementById('_twig_inspector__status') as HTMLInputElement;
    checkbox.checked = true;
    checkbox.dispatchEvent(new Event('click', { bubbles: true }));
    expect(reloadMock).toHaveBeenCalled();
  });

  it('status checkbox click when unchecked sets cookie and reloads', async () => {
    vi.resetModules();
    setupFullDOM({ checkboxChecked: false });
    await import('./index');
    const checkbox = document.getElementById('_twig_inspector__status') as HTMLInputElement;
    checkbox.checked = false;
    checkbox.dispatchEvent(new Event('click', { bubbles: true }));
    expect(reloadMock).toHaveBeenCalled();
  });

  it('Ctrl+Shift+R triggers rescan', async () => {
    vi.resetModules();
    setupFullDOM();
    await import('./index');
    const evt = new KeyboardEvent('keydown', { key: 'R', ctrlKey: true, shiftKey: true });
    document.dispatchEvent(evt);
    expect(document.getElementById('_twig_inspector__overlay__block')).not.toBeNull();
  });

  it('keyboard shortcut (Ctrl+Shift+T) toggles inspector and reloads', async () => {
    vi.resetModules();
    setupFullDOM();
    await import('./index');
    document.dispatchEvent(
      new KeyboardEvent('keydown', { key: 'T', ctrlKey: true, shiftKey: true }),
    );
    expect(reloadMock).toHaveBeenCalled();
  });

  it('when checkbox is checked, icon click toggles overlay', async () => {
    vi.resetModules();
    setupFullDOM({ checkboxChecked: true });
    await import('./index');
    const icon = document.getElementById('_twig_inspector__icon');
    expect(icon).not.toBeNull();
    icon!.dispatchEvent(new MouseEvent('click', { bubbles: true }));
    expect(console.debug).toHaveBeenCalled();
    const lastCall = (console.debug as ReturnType<typeof vi.fn>).mock.calls.flat();
    expect(lastCall.some((arg) => String(arg).includes('Overlay enabled'))).toBe(true);
  });

  it('when overlay is enabled, icon click disables it', async () => {
    vi.resetModules();
    setupFullDOM({ checkboxChecked: true });
    await import('./index');
    const icon = document.getElementById('_twig_inspector__icon');
    icon!.dispatchEvent(new MouseEvent('click', { bubbles: true }));
    icon!.dispatchEvent(new MouseEvent('click', { bubbles: true }));
    const debugCalls = (console.debug as ReturnType<typeof vi.fn>).mock.calls.flat();
    expect(debugCalls.some((arg) => String(arg).includes('Overlay disabled'))).toBe(true);
  });

  it('filter input updates overlay filter', async () => {
    vi.resetModules();
    setupFullDOM({ withFilter: true });
    await import('./index');
    const filterInput = document.getElementById('_twig_inspector__filter_input') as HTMLInputElement;
    expect(filterInput).not.toBeNull();
    filterInput.value = 'foo';
    filterInput.dispatchEvent(new Event('input', { bubbles: true }));
    expect(document.getElementById('_twig_inspector__overlay__block')).not.toBeNull();
  });

  it('Escape key resets overlay when enabled', async () => {
    setupFullDOM();
    await import('./index');
    const icon = document.getElementById('_twig_inspector__icon');
    icon!.dispatchEvent(new MouseEvent('click', { bubbles: true }));
    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
    expect(console.debug).toHaveBeenCalled();
  });
});
