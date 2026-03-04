/**
 * Keyboard shortcut parsing and matching for the Twig Inspector toggle.
 *
 * Supports modifier keys: Ctrl, Shift, Alt, Meta. Key can be given by key or code (e.g. KeyT).
 */

/**
 * Checks whether a keyboard event matches the given shortcut string.
 *
 * @param shortcut - Shortcut string (e.g. "Ctrl+Shift+T", "Meta+Shift+T").
 * @param evt - Keyboard event to test.
 * @returns True if the event matches the shortcut (modifiers and key).
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
    evt.code.toLowerCase() === ('key' + (key as string).toUpperCase()).toLowerCase();
  return (
    keyMatch &&
    evt.ctrlKey === wantCtrl &&
    evt.shiftKey === wantShift &&
    evt.altKey === wantAlt &&
    evt.metaKey === wantMeta
  );
}
