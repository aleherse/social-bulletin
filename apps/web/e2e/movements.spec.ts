import type { Page } from '@playwright/test';

import { expect, test } from './fixtures.ts';

// Constitution Principle II (the pyramid): these journeys cover the happy path
// of each user story plus the guest boundary, which spans session and routing.
// Validation and rejection cases are proven once lower down — required fields
// in `movement-form.test.tsx`, the empty-description refusal in
// `submit-movement-button.test.tsx`, `MovementSpec` and `movements.feature` —
// so they are deliberately absent here rather than missing.

interface MovementFields {
  title: string;
  category: string;
  area: string;
  location?: string;
  description?: string;
}

async function signIn(page: Page, email: string) {
  await page.goto('/');
  await page.getByLabel('Email').fill(email);
  await page.getByRole('button', { name: 'Continue' }).click();
  await page.getByRole('link', { name: 'My movements' }).click();
  await expect(page.getByRole('heading', { name: 'My movements' })).toBeVisible();
}

async function fillMovementForm(page: Page, fields: MovementFields) {
  await page.getByLabel('Title').fill(fields.title);
  await page.getByLabel('Category').selectOption({ label: fields.category });
  await page.getByLabel('Area').selectOption({ label: fields.area });

  if (fields.location !== undefined) {
    await page.getByLabel('Location').fill(fields.location);
  }

  if (fields.description !== undefined) {
    await page.getByLabel('Description').fill(fields.description);
  }
}

async function createDraft(page: Page, fields: MovementFields) {
  await page.getByRole('link', { name: 'New movement' }).click();
  await fillMovementForm(page, fields);
  await page.getByRole('button', { name: 'Save draft' }).click();
  await expect(page.getByRole('heading', { name: 'My movements' })).toBeVisible();
}

test('a guest is sent to sign in, then back to where they were going', async ({ page }) => {
  // ADR-0018: the route guard redirects and records the attempted path.
  await page.goto('/en/movements');

  await expect(page.getByLabel('Email')).toBeVisible();
  await expect(page).toHaveURL(/\/en\?next=%2Fmovements$/);

  await page.getByLabel('Email').fill('guest@example.com');
  await page.getByRole('button', { name: 'Continue' }).click();

  await expect(page.getByRole('heading', { name: 'My movements' })).toBeVisible();
  await expect(page).toHaveURL(/\/en\/movements$/);
});

test('an author drafts a movement, finds it listed, and submits it as a proposal', async ({
  page,
}) => {
  await signIn(page, 'author@example.com');
  await expect(page.getByText('You have not proposed any movements yet.')).toBeVisible();

  await createDraft(page, {
    title: 'Community Gardens for Everyone',
    category: 'Cooperative',
    area: 'Municipality',
    location: 'Sheffield',
    description: '## Why\n\nGardens for all.',
  });

  const row = page.getByRole('listitem').filter({ hasText: 'Community Gardens for Everyone' });
  await expect(row).toContainText('Municipality · Sheffield · Cooperative');
  await expect(row).toContainText('Draft');

  await page.getByRole('link', { name: 'Community Gardens for Everyone' }).click();
  // The markdown description is rendered, never executed.
  await expect(page.getByRole('heading', { name: 'Why' })).toBeVisible();
  await expect(page.getByText('Gardens for all.')).toBeVisible();

  await page.getByRole('button', { name: 'Submit proposal' }).click();
  await expect(page.getByText('Proposed')).toBeVisible();
  await expect(page.getByRole('button', { name: 'Submit proposal' })).toBeHidden();
});

test('an international movement is saved without a location', async ({ page }) => {
  await signIn(page, 'author@example.com');

  await page.getByRole('link', { name: 'New movement' }).click();
  await expect(page.getByLabel('Location')).toBeVisible();

  await fillMovementForm(page, {
    title: 'Stop Factory Farming Worldwide',
    category: 'Animal rights',
    area: 'International',
  });
  await expect(page.getByLabel('Location')).toBeHidden();

  await page.getByRole('button', { name: 'Save draft' }).click();

  const row = page.getByRole('listitem').filter({ hasText: 'Stop Factory Farming Worldwide' });
  await expect(row).toContainText('International · Animal rights');
});

test('an author edits a draft before submitting it', async ({ page }) => {
  await signIn(page, 'author@example.com');

  await createDraft(page, {
    title: 'Save the Bees',
    category: 'Animal rights',
    area: 'Region',
    location: 'Yorkshire',
  });

  await page.getByRole('link', { name: 'Save the Bees' }).click();
  await page.getByRole('link', { name: 'Edit' }).click();
  await expect(page).toHaveURL(/\/en\/movements\/[^/]+\/edit$/);

  // ADR-0018: a full document load of a deep link must survive the SPA
  // fallback (nginx `try_files`, CloudFront 403/404 -> /index.html).
  await page.reload();
  await expect(page.getByLabel('Title')).toHaveValue('Save the Bees');

  await page.getByLabel('Title').fill('Save All the Bees');
  await page.getByLabel('Category').selectOption({ label: 'Cooperative' });
  await page.getByLabel('Description').fill('Pollinators need us.');
  await page.getByRole('button', { name: 'Save draft' }).click();

  const row = page.getByRole('listitem').filter({ hasText: 'Save All the Bees' });
  await expect(row).toContainText('Region · Yorkshire · Cooperative');
  await expect(row).toContainText('Draft');
});
