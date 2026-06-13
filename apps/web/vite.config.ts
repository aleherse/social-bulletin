import path from 'node:path';
import fs from 'node:fs';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import { defineConfig } from 'vite';

function readHttpsConfig() {
  const certDir = path.resolve(__dirname, '../../docker/php/certs');
  if (!fs.existsSync(certDir)) {
    return undefined;
  }

  const files = fs.readdirSync(certDir);
  const certFile = files.find((f) => f.endsWith('.pem') && !f.includes('key'));
  const keyFile = files.find((f) => f.includes('key') && f.endsWith('.pem'));

  if (!certFile || !keyFile) {
    return undefined;
  }

  return {
    cert: fs.readFileSync(path.join(certDir, certFile)),
    key: fs.readFileSync(path.join(certDir, keyFile)),
  };
}

export default defineConfig({
  plugins: [react(), tailwindcss()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  server: {
    host: '0.0.0.0',
    port: 3000,
    https: readHttpsConfig(),
    allowedHosts: ['app.dev.social.aleherse.com'],
  },
});
