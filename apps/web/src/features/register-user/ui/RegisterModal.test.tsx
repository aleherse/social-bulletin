import '@testing-library/jest-dom/vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { RegisterModal } from './RegisterModal';
import { I18nProvider } from '../../../app/providers/I18nProvider';
import { MuiProvider } from '../../../app/providers/MuiProvider';
import { MemoryRouter } from 'react-router-dom';
import { ApiConflictError } from '../api/registerUser';

function renderWithProviders(ui: React.ReactElement) {
  return render(
    <MemoryRouter>
      <I18nProvider>
        <MuiProvider>{ui}</MuiProvider>
      </I18nProvider>
    </MemoryRouter>,
  );
}

describe('RegisterModal', () => {
  it('should render the registration form when open', () => {
    renderWithProviders(
      <RegisterModal open onClose={vi.fn()} registerUser={vi.fn()} />,
    );

    expect(screen.getByLabelText('Email address')).toBeInTheDocument();
    expect(screen.getByLabelText('Password')).toBeInTheDocument();
    expect(screen.getByLabelText('Confirm password')).toBeInTheDocument();
  });

  it('should not render when closed', () => {
    renderWithProviders(
      <RegisterModal open={false} onClose={vi.fn()} registerUser={vi.fn()} />,
    );

    expect(screen.queryByLabelText('Email address')).not.toBeInTheDocument();
  });

  it('should stay open and show error message on 409 conflict', async () => {
    const user = userEvent.setup();
    const registerUserMock = vi.fn().mockRejectedValue(new ApiConflictError());

    renderWithProviders(
      <RegisterModal open onClose={vi.fn()} registerUser={registerUserMock} />,
    );

    await user.type(screen.getByLabelText('Email address'), 'dup@example.com');
    await user.type(screen.getByLabelText('Password'), 'Str0ng!Pass');
    await user.type(screen.getByLabelText('Confirm password'), 'Str0ng!Pass');
    await user.click(screen.getByRole('checkbox'));
    await user.click(screen.getByRole('button', { name: /create account/i }));

    await waitFor(() => {
      expect(screen.getByText(/already registered/i)).toBeInTheDocument();
    });
    expect(screen.getByLabelText('Email address')).toBeInTheDocument();
  });

  it('should call onClose and onSuccess after successful registration', async () => {
    const user = userEvent.setup();
    const onClose = vi.fn();
    const onSuccess = vi.fn();
    const registerUserMock = vi.fn().mockResolvedValue({ userId: 'new-uuid' });

    renderWithProviders(
      <RegisterModal open onClose={onClose} onSuccess={onSuccess} registerUser={registerUserMock} />,
    );

    await user.type(screen.getByLabelText('Email address'), 'new@example.com');
    await user.type(screen.getByLabelText('Password'), 'Str0ng!Pass');
    await user.type(screen.getByLabelText('Confirm password'), 'Str0ng!Pass');
    await user.click(screen.getByRole('checkbox'));
    await user.click(screen.getByRole('button', { name: /create account/i }));

    await waitFor(() => {
      expect(onSuccess).toHaveBeenCalledWith('new-uuid');
    });
    expect(onClose).toHaveBeenCalled();
  });
});
