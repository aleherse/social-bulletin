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

test('an author pins their current position and saves its name', async ({ context, page }) => {
  await context.grantPermissions(['geolocation']);
  await context.setGeolocation({ latitude: 36.7033, longitude: -4.4366 });

  await signIn(page, 'author@example.com');
  await page.getByRole('link', { name: 'New movement' }).click();

  await page.getByLabel('Title').fill('Málaga Tenants Union');
  await page.getByLabel('Category').selectOption({ label: 'Cooperative' });
  await page.getByLabel('Area').selectOption({ label: 'Municipality' });

  await page.getByRole('button', { name: 'Use my location' }).click();

  await expect(page.getByRole('img', { name: 'Pinned location' })).toBeVisible();
  await expect(page.getByRole('textbox', { name: 'Location', exact: true })).toHaveValue('Málaga');

  await page.getByRole('button', { name: 'Save draft' }).click();

  const row = page.getByRole('listitem').filter({ hasText: 'Málaga Tenants Union' });
  await expect(row).toContainText('Municipality · Málaga · Cooperative');
});
