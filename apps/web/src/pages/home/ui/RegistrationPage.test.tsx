import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { RegistrationPage } from './RegistrationPage';
import * as client from '@/shared/api/client';

vi.mock('@/shared/api/client');
vi.mock('@/shared/i18n', () => ({
  useTranslation: () => ({
    t: (key: string, opts?: Record<string, string>) => {
      const map: Record<string, string> = {
        'registration.title': 'Welcome',
        'registration.emailLabel': 'Email',
        'registration.emailPlaceholder': 'you@example.com',
        'registration.submitButton': 'Continue',
        'registration.submitting': 'Please wait...',
        'errors.generic': 'Something went wrong.',
      };
      return opts?.email ? `${map[key] ?? key} ${opts.email}` : (map[key] ?? key);
    },
  }),
}));

function renderWithQuery(ui: React.ReactElement) {
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });
  return render(<QueryClientProvider client={queryClient}>{ui}</QueryClientProvider>);
}

describe('RegistrationPage', () => {
  it('calls onSuccess with email after registration', async () => {
    vi.mocked(client.apiFetch).mockResolvedValue({ email: 'test@example.com' });
    const onSuccess = vi.fn();

    renderWithQuery(<RegistrationPage onSuccess={onSuccess} />);

    fireEvent.change(screen.getByRole('textbox'), {
      target: { value: 'test@example.com' },
    });
    fireEvent.click(screen.getByRole('button', { name: /continue/i }));

    await waitFor(() => {
      expect(onSuccess).toHaveBeenCalledWith('test@example.com');
    });
  });
});
