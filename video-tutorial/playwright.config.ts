import { defineConfig } from '@playwright/test';
import path from 'node:path';

const tutorialRoot = path.resolve(import.meta.dirname);
const mode = process.env.TUTORIAL_MODE ?? 'technical';

export default defineConfig({
  testDir: path.join(tutorialRoot, 'tests'),
  testMatch: 'tutorial.spec.ts',
  globalSetup: path.join(tutorialRoot, 'tests', 'global-setup.ts'),
  outputDir: path.join(tutorialRoot, '.runtime', `test-results-${mode}`),
  timeout: 12 * 60 * 1000,
  expect: { timeout: 15_000 },
  fullyParallel: false,
  workers: 1,
  retries: 0,
  reporter: [['line']],
  use: {
    baseURL: process.env.TUTORIAL_BASE_URL ?? 'http://127.0.0.1:8787',
    browserName: 'chromium',
    headless: mode !== 'manual',
    viewport: { width: 1920, height: 1080 },
    locale: 'es-MX',
    timezoneId: 'America/Hermosillo',
    colorScheme: 'light',
    storageState: path.join(tutorialRoot, '.runtime', 'storage-state.json'),
    trace: mode === 'technical' ? 'on' : 'off',
    video: mode === 'final' ? { mode: 'on', size: { width: 1920, height: 1080 } } : 'off',
    screenshot: 'only-on-failure',
    actionTimeout: 15_000,
    navigationTimeout: 30_000
  }
});
