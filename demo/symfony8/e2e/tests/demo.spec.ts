import { test, expect } from '@playwright/test';
import { showOverlay } from './helpers';


/**
 * REQ-DEMO-013 — Twig Inspector happy path: the toolbar item is rendered, the
 * inspector cookie makes Twig emit template comments, and hovering an element
 * shows the template overlay.
 */
test.describe('demo happy path', () => {
  test('home renders the demo and the inspector toolbar item', async ({ page }) => {
    const response = await page.goto('/');
    expect(response?.ok()).toBeTruthy();
    await expect(page.locator('.demo-header')).toBeVisible();
    await expect(page.locator('#_twig_inspector__icon')).toBeVisible();
  });

  test('enabled inspector injects template comments and shows the overlay on hover', async ({ page, context, baseURL }) => {
    await context.addCookies([{ name: 'twig_inspector_is_active', value: 'true', url: baseURL! }]);
    const response = await page.goto('/');
    const html = await response!.text();
    expect(html).toContain('demo/home.html.twig');
    expect(html).toMatch(/<!--\s*\S*\s*demo\/_header\.html\.twig/);
    await showOverlay(page);
    await page.locator('.demo-header h1').hover();
    await expect(page.locator('#_twig_inspector__overlay__info')).toBeVisible();
    await expect(page.locator('#_twig_inspector__overlay__info')).toContainText('.html.twig');
  });
});
