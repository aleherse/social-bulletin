import { expect, test } from './fixtures.ts';

test('nginx serves the compiled frontend over trusted https', async ({ page }) => {
  await page.goto('/');

  await expect(page.locator('#root')).not.toBeEmpty();
});
