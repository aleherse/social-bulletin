import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';

import './index.css';
import { AppProviders } from './app/providers/index.tsx';
import { HomePage } from './pages/home';

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <AppProviders>
      <HomePage />
    </AppProviders>
  </StrictMode>,
);
