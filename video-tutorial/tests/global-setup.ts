import { chromium, type FullConfig } from '@playwright/test';
import fs from 'node:fs/promises';
import path from 'node:path';

type Scenario = { email: string; password: string };

export default async function globalSetup(config: FullConfig): Promise<void> {
  const tutorialRoot = path.resolve(import.meta.dirname, '..');
  const scenario = JSON.parse(
    await fs.readFile(path.join(tutorialRoot, '.runtime', 'scenario.json'), 'utf8')
  ) as Scenario;
  const baseURL = config.projects[0]?.use?.baseURL as string;
  const browser = await chromium.launch({ headless: process.env.TUTORIAL_MODE !== 'manual' });
  const context = await browser.newContext({
    locale: 'es-MX',
    timezoneId: 'America/Hermosillo',
    viewport: { width: 1920, height: 1080 }
  });
  const page = await context.newPage();
  await page.goto(`${baseURL}/login?context=judge`, { waitUntil: 'networkidle' });
  await page.getByLabel('Correo electrónico').fill(scenario.email);
  await page.locator('input[name="password"]').fill(scenario.password);
  await Promise.all([
    page.waitForURL('**/juez'),
    page.getByRole('button', { name: 'Entrar' }).click()
  ]);
  await context.storageState({ path: path.join(tutorialRoot, '.runtime', 'storage-state.json') });
  await context.close();
  await browser.close();
}
