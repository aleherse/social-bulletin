import '@testing-library/jest-dom/vitest';
import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { App } from './App';
import { I18nProvider } from './providers/I18nProvider';
import { MuiProvider } from './providers/MuiProvider';

describe('App', () => {
  it('should render the home page at the root route', async () => {
    vi.fn().mockResolvedValue(null);

    render(
      <I18nProvider>
        <MuiProvider>
          <App />
        </MuiProvider>
      </I18nProvider>,
    );

    expect(await screen.findByText('Social Bulletin')).toBeInTheDocument();
  });
});
