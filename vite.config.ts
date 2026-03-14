import { defineConfig } from 'vite';

/**
 * Vite config for Twig Inspector Bundle.
 * Builds TS + SCSS to IIFE and CSS in views/assets/dist for @NowoTwigInspector/assets/dist includes.
 * For Vitest (tests and coverage), see vitest.config.ts.
 */
export default defineConfig({
  define: {
    __TWIG_INSPECTOR_BUILD_TIME__: JSON.stringify(new Date().toISOString()),
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
