import { expect, test } from './fixtures.ts';

test('a visitor registers, keeps the session across reloads, and logs out', async ({ page }) => {
  await page.goto('/');
  const emailInput = page.getByLabel('Email');
  await expect(emailInput).toBeVisible();

  await emailInput.fill('journey.user@example.com');
  await page.getByRole('button', { name: 'Continue' }).click();
  await expect(page.getByText('Hello, journey.user@example.com!')).toBeVisible();

  await page.reload();
  await expect(page.getByText('Hello, journey.user@example.com!')).toBeVisible();

  await page.getByRole('button', { name: 'Log out' }).click();
  await expect(page.getByLabel('Email')).toBeVisible();
});
