import { execSync } from 'node:child_process';

import { test as base } from '@playwright/test';

export const test = base.extend<{ restoreDatabaseSnapshot: void; stubMapTiles: void }>({
  // ADR-0013: every scenario starts from the DSLR `fixtures` snapshot created
  // by `make db`.
  restoreDatabaseSnapshot: [
    async ({}, use) => {
      execSync('dslr --url "$DSLR_DATABASE_URL" restore fixtures', { shell: '/bin/sh' });
      await use();
    },
    { auto: true },
  ],
  // ADR-0022: no journey needs the tiles archive; the map works without a basemap.
  stubMapTiles: [
    async ({ page }, use) => {
      await page.route('**/tiles/**', (route) => route.abort());
      await use();
    },
    { auto: true },
  ],
});

export { expect } from '@playwright/test';
