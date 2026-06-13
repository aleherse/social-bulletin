import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import { readFileSync, existsSync } from 'fs';
import { resolve } from 'path';

const certsDir = resolve(__dirname, '../../docker/php/certs');
const certPath = resolve(certsDir, 'vite.crt');
const keyPath = resolve(certsDir, 'vite.key');

const httpsConfig =
  existsSync(certPath) && existsSync(keyPath)
    ? { cert: readFileSync(certPath), key: readFileSync(keyPath) }
    : true;

// https://vite.dev/config/
export default defineConfig({
  plugins: [react(), tailwindcss()],
  resolve: {
    alias: {
      '@': resolve(__dirname, './src'),
    },
  },
  server: {
    host: true,
    port: 3000,
    strictPort: true,
    https: httpsConfig,
  },
});
