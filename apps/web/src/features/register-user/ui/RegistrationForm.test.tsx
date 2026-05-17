import '@testing-library/jest-dom/vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { RegistrationForm } from './RegistrationForm';
import { I18nProvider } from '../../../app/providers/I18nProvider';
import { MuiProvider } from '../../../app/providers/MuiProvider';
import { MemoryRouter } from 'react-router-dom';

function renderWithProviders(ui: React.ReactElement) {
  return render(
    <MemoryRouter>
      <I18nProvider>
        <MuiProvider>{ui}</MuiProvider>
      </I18nProvider>
    </MemoryRouter>,
  );
}

describe('RegistrationForm', () => {
  it('should disable submit button when passwords do not match', async () => {
    const user = userEvent.setup();
    const onSubmit = vi.fn();

    renderWithProviders(<RegistrationForm onSubmit={onSubmit} />);

    await user.type(screen.getByLabelText('Email address'), 'user@example.com');
    await user.type(screen.getByLabelText('Password'), 'Str0ng!Pass');
    await user.type(screen.getByLabelText('Confirm password'), 'Different!Pass');
    await user.click(screen.getByRole('checkbox'));

    expect(screen.getByRole('button', { name: /create account/i })).toBeDisabled();
  });

  it('should enable submit button when passwords match and terms are accepted', async () => {
    const user = userEvent.setup();
    const onSubmit = vi.fn();

    renderWithProviders(<RegistrationForm onSubmit={onSubmit} />);

    await user.type(screen.getByLabelText('Email address'), 'user@example.com');
    await user.type(screen.getByLabelText('Password'), 'Str0ng!Pass');
    await user.type(screen.getByLabelText('Confirm password'), 'Str0ng!Pass');
    await user.click(screen.getByRole('checkbox'));

    expect(screen.getByRole('button', { name: /create account/i })).toBeEnabled();
  });

  it('should display field errors from fieldErrors prop', () => {
    renderWithProviders(
      <RegistrationForm
        onSubmit={vi.fn()}
        fieldErrors={{ email: 'Email is already taken.', password: 'Too weak.' }}
      />,
    );

    expect(screen.getByText('Email is already taken.')).toBeInTheDocument();
    expect(screen.getByText('Too weak.')).toBeInTheDocument();
  });

  it('should block submit when terms are not checked', async () => {
    const user = userEvent.setup();
    const onSubmit = vi.fn();

    renderWithProviders(<RegistrationForm onSubmit={onSubmit} />);

    await user.type(screen.getByLabelText('Email address'), 'user@example.com');
    await user.type(screen.getByLabelText('Password'), 'Str0ng!Pass');
    await user.type(screen.getByLabelText('Confirm password'), 'Str0ng!Pass');

    expect(screen.getByRole('button', { name: /create account/i })).toBeDisabled();
  });
});
