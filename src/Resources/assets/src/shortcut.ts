/**
 * Keyboard shortcut parsing and matching.
 */

/**
 * Parses a shortcut string (e.g. "Ctrl+Shift+T") and checks if the keyboard event matches.
 * @param shortcut - Shortcut string (e.g. "Ctrl+Shift+T", "Meta+Shift+T").
 * @param evt - Keyboard event.
 * @returns True if the event matches the shortcut.
 */
export function shortcutMatches(shortcut: string, evt: KeyboardEvent): boolean {
  if (!shortcut || !evt.key) {
    return false;
  }
  const parts = shortcut.toLowerCase().split('+').map((p) => p.trim());
  const key = parts.pop();
  const wantCtrl = parts.includes('ctrl');
  const wantShift = parts.includes('shift');
  const wantAlt = parts.includes('alt');
  const wantMeta = parts.includes('meta');
  const keyMatch =
    evt.key.toLowerCase() === key ||
    evt.code.toLowerCase() === 'key' + (key as string).toUpperCase();
  return (
    keyMatch &&
    evt.ctrlKey === wantCtrl &&
    evt.shiftKey === wantShift &&
    evt.altKey === wantAlt &&
    evt.metaKey === wantMeta
  );
}
