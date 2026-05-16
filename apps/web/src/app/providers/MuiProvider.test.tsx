import '@testing-library/jest-dom/vitest';
import { render, screen } from '@testing-library/react';
import { useTheme } from '@mui/material/styles';
import { describe, expect, it } from 'vitest';
import { MuiProvider } from './MuiProvider';

function ThemeConsumer() {
  const theme = useTheme();
  return <span data-testid="primary-color">{theme.palette.primary.main}</span>;
}

describe('MuiProvider', () => {
  it('should render children without error', () => {
    render(
      <MuiProvider>
        <p>child content</p>
      </MuiProvider>,
    );

    expect(screen.getByText('child content')).toBeInTheDocument();
  });

  it('should provide MUI theme context to children', () => {
    render(
      <MuiProvider>
        <ThemeConsumer />
      </MuiProvider>,
    );

    expect(screen.getByTestId('primary-color')).toHaveTextContent('#1976d2');
  });
});
