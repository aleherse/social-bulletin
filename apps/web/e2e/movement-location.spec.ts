import { expect, test } from './fixtures.ts';
import { clickMapAt } from './support/map.ts';
import { signIn } from './support/session.ts';

// Constitution Principle II (the pyramid): one happy path per user story, against
// the real lookup endpoint and the snapshot's fixture places. The lookup's
// failure modes are proven in Vitest and Behat; `/tiles/**` is aborted for every
// journey (fixtures.ts), so these also prove the map works without a basemap.

test('an author pins a neighbourhood on the map and saves its name', async ({ page }) => {
  await signIn(page, 'author@example.com');
  await page.getByRole('link', { name: 'New movement' }).click();

  await page.getByLabel('Title').fill('Huelin Community Kitchen');
  await page.getByLabel('Category').selectOption({ label: 'Cooperative' });
  await page.getByLabel('Area').selectOption({ label: 'Neighborhood' });

  await clickMapAt(page, { lat: 36.7033, lon: -4.4366 });

  await expect(page.getByRole('img', { name: 'Pinned location' })).toBeVisible();
  await expect(page.getByRole('textbox', { name: 'Location', exact: true })).toHaveValue('Huelin');

  await page.getByRole('button', { name: 'Save draft' }).click();

  const row = page.getByRole('listitem').filter({ hasText: 'Huelin Community Kitchen' });
  await expect(row).toContainText('Neighborhood · Huelin · Cooperative');
});
