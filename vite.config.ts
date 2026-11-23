import { createHash } from 'node:crypto';
import { readFileSync, readdirSync, statSync, writeFileSync } from 'node:fs';
import { defineConfig } from 'vite';

/**
 * Deterministic source hash: a hash of the TypeScript/CSS sources and pnpm-lock.yaml. It only changes when the code or
 * the dependencies change; it is the change detector of buildTimeLabel() below.
 */
function sourceBuildId(dir: string): string {
  const root = new URL(`${dir}/`, import.meta.url);
  const hash = createHash('sha256');
  const files = (readdirSync(root, { recursive: true }) as string[])
    .filter((file) => !file.includes('.test.') && statSync(new URL(file, root)).isFile())
    .sort();
  for (const file of files) {
    hash.update(file).update(readFileSync(new URL(file, root)));
  }
  hash.update(readFileSync(new URL('pnpm-lock.yaml', import.meta.url)));

  return hash.digest('hex').slice(0, 12);
}

/**
 * Readable, reproducible build time for the "script loaded" console line ("2026-11-23 14:05 UTC").
 * The committed `build-info.json` ({ sourceHash, builtAt }) keeps it stable: when the source hash equals the stored
 * one, the stored `builtAt` is reused (a rebuild of unchanged sources never rewrites the committed assets); when it
 * differs, the new hash is stored with the current UTC minute. Commit `build-info.json` with the rebuilt assets: a CI
 * dist-freshness rebuild of a clean checkout then reproduces the same bytes, and fails visibly if it was forgotten.
 */
function buildTimeLabel(dir: string): string {
  const sourceHash = sourceBuildId(dir);
  const file = new URL('build-info.json', import.meta.url);
  let info: { sourceHash?: string; builtAt?: string } = {};
  try {
    info = JSON.parse(readFileSync(file, 'utf8')) as typeof info;
  } catch {
    // Missing or unreadable: written below.
  }
  if (info.sourceHash !== sourceHash || typeof info.builtAt !== 'string') {
    info = { sourceHash, builtAt: `${new Date().toISOString().slice(0, 16)}Z` };
    writeFileSync(file, `${JSON.stringify(info, null, 2)}\n`);
    console.warn(`build-info.json updated (${info.builtAt}): commit it together with the rebuilt assets.`);
  }

  return (info.builtAt as string).replace('T', ' ').replace('Z', ' UTC');
}

/**
 * Vite config for Twig Inspector Bundle.
 * Builds TS + SCSS to IIFE and CSS in views/assets/dist for @NowoTwigInspector/assets/dist includes.
 * For Vitest (tests and coverage), see vitest.config.ts.
 */
export default defineConfig({
  define: {
    __TWIG_INSPECTOR_BUILD_TIME__: JSON.stringify(buildTimeLabel('src/Resources/assets/src')),
  },
  build: {
    outDir: 'src/Resources/views/assets/dist',
    emptyOutDir: true,
    rollupOptions: {
      input: 'src/Resources/assets/src/index.ts',
      output: {
        format: 'iife',
        entryFileNames: 'index.min.js',
        assetFileNames: '[name]-[hash][extname]',
      },
    },
    minify: true,
    sourcemap: false,
  },
});
