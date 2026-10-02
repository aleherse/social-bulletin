import { expect, test } from './fixtures.ts';

const viewports = [
  { name: 'phone (320 px)', width: 320, height: 568 },
  { name: 'desktop', width: 1280, height: 720 },
];

for (const viewport of viewports) {
  test(`the hero is readable without scrolling on a ${viewport.name} viewport`, async ({
    page,
  }) => {
    await page.setViewportSize({ width: viewport.width, height: viewport.height });
    await page.goto('/');

    const headline = page.getByRole('heading', { level: 1, name: 'Welcome to Social Bulletin' });
    const description = page.getByText(
      'Your place to stay up to date with the latest social movements events',
    );

    await expect(headline).toBeInViewport({ ratio: 1 });
    await expect(description).toBeInViewport({ ratio: 1 });

    const overflows = await page.evaluate(
      () => document.documentElement.scrollWidth > document.documentElement.clientWidth,
    );

    expect(overflows).toBe(false);
  });
}
