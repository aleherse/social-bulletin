import { defineConfig, devices } from '@playwright/test';

// ADR-0013: E2E journeys run against the nginx-served compiled frontend and
// the real API. `make web-e2e` builds the frontend before running.
export default defineConfig({
  testDir: './e2e',
  // Scenarios share one database snapshot, so they must not interleave.
  fullyParallel: false,
  workers: 1,
  reporter: 'list',
  use: {
    baseURL: process.env.PLAYWRIGHT_BASE_URL ?? 'https://dev.app.social.aleherse.com',
    trace: 'on-first-retry',
  },
  projects: [
    {
      name: 'chromium',
      use: {
        ...devices['Desktop Chrome'],
        // Full Chromium (not the headless shell): only it reads the NSS
        // store where the entrypoint trusts the mkcert root CA.
        channel: 'chromium',
        // Docker's default seccomp profile blocks the namespaces Chromium's
        // sandbox needs, and under a rootless engine the container is root.
        launchOptions: { args: ['--no-sandbox'] },
      },
    },
  ],
});
