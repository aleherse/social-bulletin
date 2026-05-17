import { test, expect, Page } from '@playwright/test';

async function clearTestUsers(page: Page) {
  await page.request.delete('/api/test/users');
}

async function fillRegistrationForm(
  page: Page,
  opts: {
    email?: string;
    password?: string;
    confirmPassword?: string;
    acceptTerms?: boolean;
  } = {},
) {
  const {
    email = 'test@example.com',
    password = 'Str0ng!P@ssw0rd',
    confirmPassword = password,
    acceptTerms = true,
  } = opts;

  if (email) {
    await page.getByLabel('Email address').fill(email);
  }
  if (password) {
    await page.getByLabel('Password').fill(password);
  }
  if (confirmPassword) {
    await page.getByLabel('Confirm password').fill(confirmPassword);
  }
  if (acceptTerms) {
    await page.getByRole('checkbox').check();
  }
}

test.describe('User Registration Journey', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
    await page.waitForLoadState('networkidle');
  });

  test('Register button is visible when unauthenticated', async ({ page }) => {
    await expect(page.getByRole('button', { name: /register/i })).toBeVisible();
  });

  test('Clicking Register opens modal with all fields', async ({ page }) => {
    await page.getByRole('button', { name: /register/i }).click();

    await expect(page.getByLabel('Email address')).toBeVisible();
    await expect(page.getByLabel('Password')).toBeVisible();
    await expect(page.getByLabel('Confirm password')).toBeVisible();
    await expect(page.getByRole('checkbox')).toBeVisible();
    await expect(page.getByRole('button', { name: /create account/i })).toBeVisible();
  });

  test('Mismatched passwords disable submit button', async ({ page }) => {
    await page.getByRole('button', { name: /register/i }).click();

    await fillRegistrationForm(page, {
      password: 'Str0ng!P@ssw0rd',
      confirmPassword: 'DifferentPass1!',
    });

    await expect(page.getByRole('button', { name: /create account/i })).toBeDisabled();
  });

  test('T&C unchecked disables submit button', async ({ page }) => {
    await page.getByRole('button', { name: /register/i }).click();

    await fillRegistrationForm(page, { acceptTerms: false });

    await expect(page.getByRole('button', { name: /create account/i })).toBeDisabled();
  });

  test('T&C checkbox links to terms page', async ({ page }) => {
    await page.getByRole('button', { name: /register/i }).click();

    const termsLink = page.getByRole('link', { name: /terms and conditions/i });
    await expect(termsLink).toBeVisible();
    await expect(termsLink).toHaveAttribute('href', '/terms');
  });

  test('Happy path registration redirects to homepage with button hidden', async ({ page }) => {
    const uniqueEmail = `e2e-${Date.now()}@example.com`;

    await page.getByRole('button', { name: /register/i }).click();
    await fillRegistrationForm(page, { email: uniqueEmail });
    await page.getByRole('button', { name: /create account/i }).click();

    await expect(page.getByRole('button', { name: /register/i })).not.toBeVisible({
      timeout: 5000,
    });
  });

  test('Duplicate email shows inline error without closing modal', async ({ page }) => {
    const duplicateEmail = `dup-e2e-${Date.now()}@example.com`;

    await page.getByRole('button', { name: /register/i }).click();
    await fillRegistrationForm(page, { email: duplicateEmail });
    await page.getByRole('button', { name: /create account/i }).click();

    await page.waitForSelector('[role="dialog"]', { state: 'hidden', timeout: 3000 }).catch(() => {});
    await page.goto('/');
    await page.waitForLoadState('networkidle');

    await page.getByRole('button', { name: /register/i }).click();
    await fillRegistrationForm(page, { email: duplicateEmail });
    await page.getByRole('button', { name: /create account/i }).click();

    await expect(page.getByText(/already registered/i)).toBeVisible({ timeout: 5000 });
    await expect(page.getByLabel('Email address')).toBeVisible();
  });
});
