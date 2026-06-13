import { test, expect } from '@playwright/test';

test.describe('Registration flow', () => {
  test.beforeEach(async ({ context }) => {
    await context.clearCookies();
  });

  test('registers a new user and shows hello view', async ({ page }) => {
    await page.route('**/api/me', (route) => route.fulfill({ status: 401, body: '{}' }));
    await page.route('**/api/register', (route) =>
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ email: 'e2e@example.com' }),
      }),
    );

    await page.goto('/');

    await expect(page.getByRole('textbox')).toBeVisible();
    await page.getByRole('textbox').fill('e2e@example.com');
    await page.getByRole('button', { name: /continue/i }).click();

    await expect(page.getByText('e2e@example.com')).toBeVisible();
  });

  test('logout returns to registration form', async ({ page }) => {
    await page.route('**/api/me', (route) =>
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ id: '123', email: 'e2e@example.com' }),
      }),
    );
    await page.route('**/api/logout', (route) =>
      route.fulfill({ status: 200, body: '{}' }),
    );

    await page.goto('/');

    await expect(page.getByText('e2e@example.com')).toBeVisible();
    await page.getByRole('button', { name: /log out/i }).click();

    await expect(page.getByRole('textbox')).toBeVisible();
  });
});
