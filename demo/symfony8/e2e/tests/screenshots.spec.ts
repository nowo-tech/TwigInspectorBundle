import { test, expect } from '@playwright/test';
import { mkdirSync } from 'node:fs';
import { resolve } from 'node:path';
import { showOverlay } from './helpers';

/**
 * REQ-DEMO-013 — Twig Inspector doc screenshots. The feature lives on top of
 * the demo page (overlay) and in the Web Profiler toolbar, so captures are the
 * demo viewport with the toolbar visible.
 */
const outDir = process.env.SCREENSHOT_DIR
  ? resolve(process.env.SCREENSHOT_DIR)
  : resolve(__dirname, '../../../../docs/images/demo');

test.use({ viewport: { width: 1280, height: 760 } });

test.beforeAll(() => {
  mkdirSync(outDir, { recursive: true });
});

test.beforeEach(async ({ context, baseURL }) => {
  await context.addCookies([{ name: 'twig_inspector_is_active', value: 'true', url: baseURL! }]);
});

test.describe('feature screenshots', () => {
  test('overlay tooltip on hover', async ({ page }) => {
    await page.goto('/');
    await showOverlay(page);
    // Close the toolbar dropdown opened by the icon click; keep only the page overlay.
    await page.addStyleTag({ content: '#_twig_inspector__icon .sf-toolbar-info { display: none !important; }' });
    const target = page.locator('.demo-header h1');
    const box = (await target.boundingBox())!;
    await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2);
    await page.mouse.move(box.x + box.width / 2 + 5, box.y + box.height / 2 + 2);
    await expect(page.locator('#_twig_inspector__overlay__info')).toBeVisible();
    await page.waitForTimeout(300);
    await page.screenshot({ path: resolve(outDir, 'overlay-tooltip.png') });
  });

  test('toolbar dropdown with filter', async ({ page }) => {
    await page.goto('/');
    await showOverlay(page);
    const icon = page.locator('#_twig_inspector__icon');
    await icon.hover();
    const filter = page.locator('#_twig_inspector__filter input, #_twig_inspector__filter_input').first();
    await expect(filter).toBeVisible();
    await filter.fill('header');
    await expect(page.locator('._twig_inspector__filter_highlight')).not.toHaveCount(0);
    await icon.hover();
    await page.waitForTimeout(300);
    await page.screenshot({ path: resolve(outDir, 'toolbar-dropdown.png') });
  });

  test('template HTML comments in the page source', async ({ page, baseURL }) => {
    await page.goto(`view-source:${baseURL}/`);
    await expect(page.locator('body')).toContainText('demo/_header.html.twig');
    await page.getByLabel('Line wrap').check().catch(() => undefined);
    await page.locator('td.line-content', { hasText: 'demo/_header.html.twig' }).first().evaluate((el) => el.scrollIntoView({ block: 'center' }));
    await page.screenshot({ path: resolve(outDir, 'html-comments.png') });
  });
});
