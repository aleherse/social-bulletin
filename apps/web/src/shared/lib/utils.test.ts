import { describe, expect, it } from 'vitest';

import { cn } from './utils.ts';

describe('cn', () => {
  it('joins class names and drops falsy values', () => {
    expect(cn('a', null, undefined, 'c')).toBe('a c');
  });

  it('lets later tailwind classes win over earlier conflicting ones', () => {
    expect(cn('p-2', 'p-4')).toBe('p-4');
  });
});
