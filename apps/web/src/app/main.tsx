import React from 'react';
import { createRoot } from 'react-dom/client';

import { App } from './App';
import './index.css';
import { AppProviders } from './providers';

createRoot(document.getElementById('root')!).render(
  <React.StrictMode>
    <AppProviders>
      <App />
    </AppProviders>
  </React.StrictMode>,
);
