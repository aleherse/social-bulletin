import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import { existsSync, readFileSync } from 'node:fs';
import { fileURLToPath, URL } from 'node:url';

const certificatePath = '../../docker/nginx/certs/bulletin.local.pem';
const keyPath = '../../docker/nginx/certs/bulletin.local-key.pem';
const https =
  existsSync(certificatePath) && existsSync(keyPath)
    ? {
        cert: readFileSync(certificatePath),
        key: readFileSync(keyPath),
      }
    : undefined;

export default defineConfig({
  plugins: [react(), tailwindcss()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    host: true,
    port: 3000,
    https,
    allowedHosts: [process.env.DEV_ALLOWED_HOST ?? 'app.bulletin.local'],
  },
});
