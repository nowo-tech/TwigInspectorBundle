import { expect, type Page } from '@playwright/test';

/** Click the toolbar `</>` icon until it turns green (overlay on). */
export async function showOverlay(page: Page): Promise<void> {
  const icon = page.locator('#_twig_inspector__icon');
  await expect(icon).toBeVisible();
  for (let i = 0; i < 2; i++) {
    if (await icon.evaluate((el) => el.className.includes('green'))) {
      return;
    }
    await icon.locator('.sf-toolbar-icon').click();
  }
  await expect(icon).toHaveClass(/green/);
}
