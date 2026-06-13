import { test, expect } from '@playwright/test';

test('registration flow shows hello view after email submit', async ({ page }) => {
  await page.goto('/');
  await page.getByLabel('Email address').fill('walker@example.com');
  await page.getByRole('button', { name: 'Continue' }).click();
  await expect(page.getByRole('heading', { name: /Hello, walker@example.com/i })).toBeVisible();
  await page.getByRole('button', { name: 'Log out' }).click();
  await expect(page.getByLabel('Email address')).toBeVisible();
});
