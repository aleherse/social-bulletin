import react from '@vitejs/plugin-react';
import { defineConfig } from 'vitest/config';

const devAllowedHost = process.env.DEV_ALLOWED_HOST;

export default defineConfig({
  plugins: [react()],
  server: {
    host: true,
    port: 5173,
    allowedHosts: devAllowedHost ? [devAllowedHost] : [],
  },
  test: {
    environment: 'jsdom',
    setupFiles: './src/test/setup.ts',
    exclude: ['**/node_modules/**', '**/e2e/**'],
  },
});
