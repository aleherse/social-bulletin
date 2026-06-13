import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vitest/config';

const dirname = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
  test: {
    include: ['src/**/*.test.{ts,tsx}'],
    environment: 'jsdom',
    setupFiles: './src/app/test/setup.ts',
    globals: true,
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
