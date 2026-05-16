import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { App } from './app/App';
import { I18nProvider } from './app/providers/I18nProvider';
import { MuiProvider } from './app/providers/MuiProvider';

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <I18nProvider>
      <MuiProvider>
        <App />
      </MuiProvider>
    </I18nProvider>
  </StrictMode>,
);
