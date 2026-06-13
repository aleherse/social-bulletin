import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import react from '@vitejs/plugin-react';
import { defineConfig } from 'vite';

const dirname = path.dirname(fileURLToPath(import.meta.url));
const certDir = process.env.VITE_DEV_CERT_DIR ?? path.resolve(dirname, '../../docker/php/certs');
const certPath = path.join(certDir, 'socialbulletin.pem');
const keyPath = path.join(certDir, 'socialbulletin-key.pem');
const hasCertificate = fs.existsSync(certPath) && fs.existsSync(keyPath);

export default defineConfig({
  plugins: [react()],
  server: {
    host: '0.0.0.0',
    port: 3000,
    strictPort: true,
    hmr: {
      host: 'app.dev.social.aleherse.com',
      protocol: 'wss',
    },
    https: hasCertificate
      ? {
          cert: fs.readFileSync(certPath),
          key: fs.readFileSync(keyPath),
        }
      : undefined,
  },
  resolve: {
    alias: {
      '@/app': path.resolve(dirname, 'src/app'),
      '@/pages': path.resolve(dirname, 'src/pages'),
      '@/widgets': path.resolve(dirname, 'src/widgets'),
      '@/features': path.resolve(dirname, 'src/features'),
      '@/entities': path.resolve(dirname, 'src/entities'),
      '@/shared': path.resolve(dirname, 'src/shared'),
    },
  },
});
