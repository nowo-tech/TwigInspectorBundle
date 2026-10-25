import { createHash } from 'node:crypto';
import { readdirSync, readFileSync, statSync } from 'node:fs';
import { defineConfig } from 'vite';

/**
 * Deterministic build id: a hash of the TypeScript/CSS sources and pnpm-lock.yaml. It only changes when the code or
 * the dependencies change, so rebuilding unchanged sources (e.g. in `make release-check`) leaves the committed bundle
 * byte-identical (the former wall-clock stamp rewrote it on every build).
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
 * Vite config for Twig Inspector Bundle.
 * Builds TS + SCSS to IIFE and CSS in views/assets/dist for @NowoTwigInspector/assets/dist includes.
 * For Vitest (tests and coverage), see vitest.config.ts.
 */
export default defineConfig({
  define: {
    __TWIG_INSPECTOR_BUILD_TIME__: JSON.stringify(sourceBuildId('src/Resources/assets/src')),
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
