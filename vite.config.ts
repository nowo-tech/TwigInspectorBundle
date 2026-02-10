import { defineConfig } from 'vitest/config';

/**
 * Vite config for Twig Inspector Bundle.
 * Builds TS + SCSS to IIFE and CSS in views/assets/dist for @NowoTwigInspector/assets/dist includes.
 */
export default defineConfig({
  test: {
    environment: 'jsdom',
    include: ['src/Resources/assets/src/**/*.test.ts'],
    globals: true,
  },
  build: {
    outDir: 'src/Resources/views/assets/dist',
    emptyOutDir: true,
    rollupOptions: {
      input: 'src/Resources/assets/src/index.ts',
      output: {
        format: 'iife',
        entryFileNames: 'index.min.js',
        assetFileNames: 'style.min.[extname]',
      },
    },
    minify: true,
    sourcemap: false,
  },
});
