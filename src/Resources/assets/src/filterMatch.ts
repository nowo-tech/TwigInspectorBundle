/**
 * Pure filter logic: match a block against a filter query (name or path, comma-separated).
 * Used by Overlay and testable without DOM.
 */

import type { Block } from './types';

/**
 * Returns true if the block matches the filter query.
 * Filter can be comma-separated; each part matches against template name or link (case-insensitive).
 *
 * @param block - Block (element + templates) to test
 * @param filterQuery - Filter string (e.g. "header, footer" or a single template name/path)
 * @returns True if any template name or link contains any of the comma-separated terms (case-insensitive), or if filter is empty
 */
export function blockMatchesFilter(block: Block, filterQuery: string): boolean {
  const raw = filterQuery.trim();
  if (!raw) {
    return true;
  }
  const queries = raw
    .split(',')
    .map((s) => s.trim().toLowerCase())
    .filter((s) => s.length > 0);
  if (queries.length === 0) return true;
  for (let q = 0; q < queries.length; q++) {
    for (let i = 0; i < block.templates.length; i++) {
      const t = block.templates[i];
      const nameLower = t.name.toLowerCase();
      const linkLower = t.link.toLowerCase();
      if (nameLower.includes(queries[q]) || linkLower.includes(queries[q])) {
        return true;
      }
    }
  }
  return false;
}
