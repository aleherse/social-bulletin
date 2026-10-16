import type { Page } from '@playwright/test';

import { expect } from '../fixtures.ts';

export async function signIn(page: Page, email: string) {
  await page.goto('/');
  await page.getByLabel('Email').fill(email);
  await page.getByRole('button', { name: 'Continue' }).click();
  await page.getByRole('link', { name: 'My movements' }).click();
  await expect(page.getByRole('heading', { name: 'My movements' })).toBeVisible();
}
