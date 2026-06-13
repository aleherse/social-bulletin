import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import { RegistrationForm } from '@/features/auth/ui/RegistrationForm';
import { I18nProvider } from '@/shared/i18n';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

describe('RegistrationForm', () => {
  it('renders email field and submit button', () => {
    const client = new QueryClient();
    render(
      <I18nProvider>
        <QueryClientProvider client={client}>
          <RegistrationForm />
        </QueryClientProvider>
      </I18nProvider>,
    );

    expect(screen.getByLabelText(/email address/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /continue/i })).toBeInTheDocument();
  });
});
