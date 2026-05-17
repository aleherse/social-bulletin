import '@testing-library/jest-dom/vitest';
import { render, screen, waitFor } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { MemoryRouter } from 'react-router-dom';
import { HomePage } from './HomePage';
import { I18nProvider } from '../../../app/providers/I18nProvider';
import { MuiProvider } from '../../../app/providers/MuiProvider';

function renderWithProviders(ui: React.ReactElement) {
  return render(
    <MemoryRouter>
      <I18nProvider>
        <MuiProvider>{ui}</MuiProvider>
      </I18nProvider>
    </MemoryRouter>,
  );
}

describe('HomePage', () => {
  it('should show Register button when unauthenticated (fetchMe returns null)', async () => {
    const fetchMe = vi.fn().mockResolvedValue(null);

    renderWithProviders(<HomePage fetchMe={fetchMe} />);

    expect(await screen.findByRole('button', { name: /register/i })).toBeInTheDocument();
  });

  it('should hide Register button when authenticated (fetchMe returns userId)', async () => {
    const fetchMe = vi.fn().mockResolvedValue({ userId: 'some-uuid' });

    renderWithProviders(<HomePage fetchMe={fetchMe} />);

    await waitFor(() => expect(fetchMe).toHaveBeenCalled());

    await waitFor(() => {
      expect(screen.queryByRole('button', { name: /register/i })).not.toBeInTheDocument();
    });
  });
});
