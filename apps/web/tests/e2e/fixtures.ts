import { execFileSync } from 'node:child_process';

import { test as base } from '@playwright/test';

export const test = base.extend({});

test.beforeEach(() => {
  execFileSync('dslr', ['restore', 'fixtures'], { stdio: 'inherit' });
});

export { expect } from '@playwright/test';
