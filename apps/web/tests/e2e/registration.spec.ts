import { expect, test } from './fixtures';

test('shows registration form when unauthenticated', async ({ page }) => {
  await page.goto('/');

  await expect(page.getByRole('textbox', { name: /email address/i })).toBeVisible();
});

test('registers a new user and shows the hello view', async ({ page }) => {
  await page.goto('/');
  await page.getByRole('textbox', { name: /email address/i }).fill('new-e2e-user@example.com');
  await page.getByRole('button', { name: /continue/i }).click();

  await expect(page.getByText('Hello, new-e2e-user@example.com')).toBeVisible();
});

test('authenticates an existing user and restores hello view from cookie', async ({ page }) => {
  await page.goto('/');
  await page.getByRole('textbox', { name: /email address/i }).fill('existing-e2e-user@example.com');
  await page.getByRole('button', { name: /continue/i }).click();
  await expect(page.getByText('Hello, existing-e2e-user@example.com')).toBeVisible();

  await page.reload();

  await expect(page.getByText('Hello, existing-e2e-user@example.com')).toBeVisible();
});
