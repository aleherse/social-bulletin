import { execSync } from 'node:child_process';

import { test as base } from '@playwright/test';

// ADR-0015: every scenario starts from the DSLR `fixtures` snapshot created
// by `make db`.
export const test = base.extend<{ restoreDatabaseSnapshot: void }>({
  restoreDatabaseSnapshot: [
    async ({}, use) => {
      execSync('dslr --url "$DSLR_DATABASE_URL" restore fixtures', { shell: '/bin/sh' });
      await use();
    },
    { auto: true },
  ],
});

export { expect } from '@playwright/test';
