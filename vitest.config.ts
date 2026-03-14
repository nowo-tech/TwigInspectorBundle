import { defineConfig } from 'vitest/config';

/**
 * Vitest configuration for Twig Inspector Bundle TypeScript unit tests.
 * Runs all `*.test.ts` under src/Resources/assets/src with jsdom and coverage.
 */
export default defineConfig({
  define: {
    __TWIG_INSPECTOR_BUILD_TIME__: JSON.stringify(new Date().toISOString()),
  },
  test: {
    environment: 'jsdom',
    globals: true,
    include: ['src/Resources/assets/src/**/*.test.ts'],
    coverage: {
      provider: 'v8',
      reporter: ['text', 'text-summary', 'html'],
      reportsDirectory: './coverage-ts',
      include: ['src/Resources/assets/src/**/*.ts'],
      exclude: [
        'src/Resources/assets/src/**/*.test.ts',
        '**/node_modules/**',
      ],
      thresholds: {
        lines: 100,
        statements: 100,
        functions: 100,
        // branches: una rama en Overlay (picker link) no la atribuye v8 → 99.58%
        branches: 99.5,
      },
    },
  },
  resolve: {
    extensions: ['.ts'],
  },
});
