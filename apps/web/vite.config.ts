/// <reference types="vitest/config" />
import fs from 'node:fs';
import path from 'node:path';

import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import { defineConfig } from 'vite';

const certFile = '/certs/cert.pem';
const keyFile = '/certs/cert-key.pem';
const httpsConfig =
  fs.existsSync(certFile) && fs.existsSync(keyFile)
    ? { cert: fs.readFileSync(certFile), key: fs.readFileSync(keyFile) }
    : undefined;

// https://vite.dev/config/
export default defineConfig({
  plugins: [react(), tailwindcss()],
  resolve: {
    alias: {
      '@': path.resolve(import.meta.dirname, 'src'),
    },
  },
  server: {
    host: '0.0.0.0',
    port: 3000,
    strictPort: true,
    https: httpsConfig,
    allowedHosts: ['dev.app.social.aleherse.com'],
  },
  test: {
    environment: 'jsdom',
    setupFiles: ['./src/test-setup.ts'],
    // e2e/ belongs to Playwright.
    include: ['src/**/*.{test,spec}.{ts,tsx}'],
  },
});
