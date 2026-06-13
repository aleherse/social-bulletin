import { execSync } from 'node:child_process';
import { expect, test } from '@playwright/test';

test.describe('registration walking skeleton', () => {
  test.beforeEach(async ({ page }) => {
    try {
      execSync('dslr restore fixtures', { stdio: 'ignore' });
    } catch {
      // Snapshot may not exist before `make db`; scenario remains self-contained.
    }

    await page.goto('/');
  });

  test('creates or authenticates a user, reads current user on reload, and logs out', async ({
    page,
  }) => {
    const email = `person-${Date.now()}@example.com`;

    await expect(page.getByRole('heading', { name: 'Join SocialBulletin' })).toBeVisible();
    await page.getByLabel('Email address').fill(email);
    await page.getByRole('button', { name: 'Continue' }).click();

    await expect(page.getByRole('heading', { name: `Hello, ${email}` })).toBeVisible();

    await page.reload();
    await expect(page.getByRole('heading', { name: `Hello, ${email}` })).toBeVisible();

    await page.getByRole('button', { name: 'Log out' }).click();
    await expect(page.getByRole('heading', { name: 'Join SocialBulletin' })).toBeVisible();
  });
});
