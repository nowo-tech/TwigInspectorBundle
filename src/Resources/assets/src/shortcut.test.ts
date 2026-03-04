import { describe, it, expect } from 'vitest';
import { shortcutMatches } from './shortcut';

function keyEvent(options: Partial<KeyboardEvent> = {}): KeyboardEvent {
  return new KeyboardEvent('keydown', {
    key: 't',
    code: 'KeyT',
    ctrlKey: false,
    shiftKey: false,
    altKey: false,
    metaKey: false,
    ...options,
  });
}

describe('shortcutMatches', () => {
  it('returns false when shortcut is empty', () => {
    expect(shortcutMatches('', keyEvent())).toBe(false);
    expect(shortcutMatches('   ', keyEvent())).toBe(false);
  });

  it('returns false when event has no key', () => {
    const evt = keyEvent({ key: '' });
    expect(shortcutMatches('T', evt)).toBe(false);
  });

  it('matches single key (case-insensitive)', () => {
    expect(shortcutMatches('t', keyEvent({ key: 't' }))).toBe(true);
    expect(shortcutMatches('T', keyEvent({ key: 't' }))).toBe(true);
    expect(shortcutMatches('t', keyEvent({ key: 'T' }))).toBe(true);
    expect(shortcutMatches('t', keyEvent({ key: 'x', code: 'KeyX' }))).toBe(false);
  });

  it('matches key by code when key differs (KeyT)', () => {
    const evt = keyEvent({ key: 't', code: 'KeyT' });
    expect(shortcutMatches('t', evt)).toBe(true);
    const evtCode = keyEvent({ key: 'ñ', code: 'KeyT' });
    expect(shortcutMatches('t', evtCode)).toBe(true);
  });

  it('matches Ctrl+Shift+T', () => {
    const evt = keyEvent({ key: 'T', ctrlKey: true, shiftKey: true });
    expect(shortcutMatches('Ctrl+Shift+T', evt)).toBe(true);
    expect(shortcutMatches('ctrl+shift+t', evt)).toBe(true);
    expect(shortcutMatches('Ctrl+Shift+T', keyEvent({ key: 'T' }))).toBe(false);
    expect(shortcutMatches('Ctrl+Shift+T', keyEvent({ key: 'T', ctrlKey: true }))).toBe(false);
    expect(shortcutMatches('Ctrl+Shift+T', keyEvent({ key: 'T', shiftKey: true }))).toBe(false);
  });

  it('matches Meta+Shift+T (e.g. Mac)', () => {
    const evt = keyEvent({ key: 't', metaKey: true, shiftKey: true });
    expect(shortcutMatches('Meta+Shift+T', evt)).toBe(true);
    expect(shortcutMatches('meta+shift+t', evt)).toBe(true);
  });

  it('matches Alt+T', () => {
    const evt = keyEvent({ key: 't', altKey: true });
    expect(shortcutMatches('Alt+T', evt)).toBe(true);
    expect(shortcutMatches('Alt+T', keyEvent({ key: 't' }))).toBe(false);
  });

  it('requires all modifiers to match', () => {
    const evt = keyEvent({ key: 'T', ctrlKey: true, shiftKey: true });
    expect(shortcutMatches('Ctrl+Shift+T', evt)).toBe(true);
    const extraAlt = keyEvent({ key: 'T', ctrlKey: true, shiftKey: true, altKey: true });
    expect(shortcutMatches('Ctrl+Shift+T', extraAlt)).toBe(false);
  });

  it('trims shortcut parts', () => {
    expect(shortcutMatches('  Ctrl  +  Shift  +  T  ', keyEvent({ key: 'T', ctrlKey: true, shiftKey: true }))).toBe(true);
  });
});
