import { describe, expect, it } from 'vitest';

import { isValidEmail } from './email.ts';

describe('isValidEmail', () => {
  it.each(['user@example.com', 'first.last@sub.example.co.uk', '  padded@example.com  '])(
    'accepts %s',
    (email) => {
      expect(isValidEmail(email)).toBe(true);
    },
  );

  it.each(['', '   ', 'not-an-email', 'missing@domain', '@example.com', 'two words@example.com'])(
    'rejects %s',
    (email) => {
      expect(isValidEmail(email)).toBe(false);
    },
  );
});
