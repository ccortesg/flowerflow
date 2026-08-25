import { expect, test, type Locator, type Page } from '@playwright/test';
import fs from 'node:fs/promises';
import path from 'node:path';

type Scene = {
  id: string;
  title: string;
  duration_seconds: number;
};

type Scenario = {
  judge_name: string;
  assignment_public_id: string;
  expected_total: string;
};

const tutorialRoot = path.resolve(import.meta.dirname, '..');
const mode = process.env.TUTORIAL_MODE ?? 'technical';
const finalMode = mode === 'final';
const timeline = JSON.parse(await fs.readFile(path.join(tutorialRoot, 'timeline.json'), 'utf8')) as { scenes: Scene[] };
const scenario = JSON.parse(await fs.readFile(path.join(tutorialRoot, '.runtime', 'scenario.json'), 'utf8')) as Scenario;
const markers: Array<{ id: string; title: string; start_seconds: number; end_seconds: number }> = [];
let recordingStartedAt = 0;

const rubric = [
  ['Relevancia del problema para Hermosillo y claridad del diagnóstico', '8.5', 'El diagnóstico identifica una necesidad urbana concreta y verificable.'],
  ['Calidad, claridad y originalidad de la solución', '8', 'La solución está explicada con claridad y propone una combinación pertinente.'],
  ['Participación ciudadana y coordinación municipal propuesta', '7.5', 'La coordinación es viable; conviene precisar mecanismos de seguimiento ciudadano.'],
  ['Impacto, sostenibilidad y posibilidad de medición', '9', 'Los indicadores permiten observar cobertura, uso y permanencia de los beneficios.']
] as const;

const generalComment = 'La propuesta presenta un diagnóstico claro y una solución factible, con beneficios públicos medibles. Destacan el uso de vegetación nativa y la implementación por etapas. Como mejora, conviene precisar responsables, mantenimiento y mecanismos de participación ciudadana durante el seguimiento.';

async function installPresentationLayer(page: Page): Promise<void> {
  await page.addStyleTag({ content: `
    #ff-tutorial-cursor{position:fixed;z-index:2147483647;width:24px;height:24px;border:3px solid #fff;background:#ff765f;border-radius:50%;box-shadow:0 2px 12px rgba(0,0,0,.38);pointer-events:none;transform:translate(-50%,-50%);left:94%;top:92%;transition:left .45s ease,top .45s ease}
    .ff-tutorial-focus{position:fixed;z-index:2147483645;border:5px solid #167c5b;border-radius:12px;box-shadow:0 0 0 5px rgba(255,118,95,.35);pointer-events:none;transition:all .25s ease}
    .ff-tutorial-label{position:fixed;z-index:2147483646;background:#0b5c42;color:#fff;padding:8px 14px;border-radius:999px;font:700 18px/1.2 Arial,sans-serif;box-shadow:0 3px 12px rgba(0,0,0,.28);pointer-events:none}
    .ff-tutorial-ripple{position:fixed;z-index:2147483647;width:24px;height:24px;border:4px solid #ff765f;border-radius:50%;transform:translate(-50%,-50%);animation:ff-ripple .7s ease-out forwards;pointer-events:none}
    @keyframes ff-ripple{to{width:90px;height:90px;opacity:0}}
  ` });
  await page.evaluate(() => {
    if (!document.getElementById('ff-tutorial-cursor')) {
      const cursor = document.createElement('div');
      cursor.id = 'ff-tutorial-cursor';
      cursor.setAttribute('aria-hidden', 'true');
      document.body.append(cursor);
    }
  });
}

async function highlight(page: Page, locator: Locator, label: string, linger = 900): Promise<void> {
  await locator.scrollIntoViewIfNeeded();
  const box = await locator.boundingBox();
  if (!box) throw new Error(`No se pudo ubicar el elemento: ${label}`);
  await page.evaluate(({ box, label }) => {
    document.querySelectorAll('.ff-tutorial-focus,.ff-tutorial-label').forEach((node) => node.remove());
    const focus = document.createElement('div');
    focus.className = 'ff-tutorial-focus';
    Object.assign(focus.style, { left: `${box.x - 8}px`, top: `${box.y - 8}px`, width: `${box.width + 16}px`, height: `${box.height + 16}px` });
    const badge = document.createElement('div');
    badge.className = 'ff-tutorial-label';
    badge.textContent = label;
    Object.assign(badge.style, { left: `${Math.max(18, box.x)}px`, top: `${Math.max(18, box.y - 52)}px` });
    const cursor = document.getElementById('ff-tutorial-cursor');
    if (cursor) Object.assign(cursor.style, { left: `${box.x + box.width / 2}px`, top: `${box.y + box.height / 2}px` });
    document.body.append(focus, badge);
  }, { box, label });
  await page.waitForTimeout(finalMode ? linger : 120);
}

async function clickAnnotated(page: Page, locator: Locator, label: string): Promise<void> {
  await highlight(page, locator, label, 800);
  const box = await locator.boundingBox();
  if (!box) throw new Error(`No se pudo ubicar el clic: ${label}`);
  await page.evaluate(({ x, y }) => {
    const ripple = document.createElement('div');
    ripple.className = 'ff-tutorial-ripple';
    Object.assign(ripple.style, { left: `${x}px`, top: `${y}px` });
    document.body.append(ripple);
    window.setTimeout(() => ripple.remove(), 800);
  }, { x: box.x + box.width / 2, y: box.y + box.height / 2 });
  await page.waitForTimeout(finalMode ? 350 : 50);
  await locator.click();
}

async function brandedCard(page: Page, kind: 'opening' | 'closing'): Promise<void> {
  const flower = await fs.readFile(path.resolve(tutorialRoot, '..', 'public/assets/flowerflow/logo_flowerflow_transparente.png'));
  const florece = await fs.readFile(path.resolve(tutorialRoot, '..', 'public/assets/flowerflow/logo_florecehermosillo_transparente.png'));
  const flowerSrc = `data:image/png;base64,${flower.toString('base64')}`;
  const floreceSrc = `data:image/png;base64,${florece.toString('base64')}`;
  const title = kind === 'opening' ? 'Mis asignaciones' : 'Evaluación completada';
  const subtitle = kind === 'opening'
    ? 'Cómo evaluar una propuesta asignada'
    : 'Revisaste el proyecto, guardaste avances y enviaste tu evaluación';
  const disclosure = kind === 'closing' ? '<p class="disclosure">Narración generada mediante inteligencia artificial</p>' : '';
  await page.setContent(`<!doctype html><html lang="es"><head><meta charset="utf-8"><style>
    *{box-sizing:border-box}body{margin:0;width:100vw;height:100vh;overflow:hidden;background:linear-gradient(135deg,#f5f0e7 0%,#fff 55%,#dff3e9 100%);font-family:Arial,sans-serif;color:#173c32;display:grid;place-items:center}.frame{width:84%;padding:70px 90px;border-left:18px solid #167c5b;background:rgba(255,255,255,.94);box-shadow:0 26px 80px rgba(11,92,66,.2);border-radius:22px}.logos{display:flex;align-items:center;justify-content:space-between;gap:60px;margin-bottom:58px}.logos img{max-height:115px;max-width:390px;object-fit:contain}p.kicker{margin:0 0 14px;text-transform:uppercase;letter-spacing:.18em;color:#c45140;font-size:24px;font-weight:800}h1{font-size:76px;line-height:1.05;margin:0 0 24px;color:#0b5c42}p.subtitle{font-size:38px;line-height:1.3;margin:0;max-width:1250px}.disclosure{margin:42px 0 0;padding-top:24px;border-top:2px solid #d6e5df;font-size:24px;color:#526b63}
  </style></head><body><main class="frame"><div class="logos"><img alt="Flower Flow" src="${flowerSrc}"><img alt="Florece Hermosillo" src="${floreceSrc}"></div><p class="kicker">Tutorial del rol Juez</p><h1>${title}</h1><p class="subtitle">${subtitle}</p>${disclosure}</main></body></html>`);
}

async function scene(page: Page, id: string, action: () => Promise<void>): Promise<void> {
  const definition = timeline.scenes.find((item) => item.id === id);
  if (!definition) throw new Error(`Escena desconocida ${id}`);
  const started = Date.now();
  const startSeconds = (started - recordingStartedAt) / 1000;
  await action();
  const actionSeconds = (Date.now() - started) / 1000;
  const desired = finalMode ? definition.duration_seconds : id === '09' ? 32 : 0.35;
  if (desired > actionSeconds) await page.waitForTimeout((desired - actionSeconds) * 1000);
  markers.push({ id, title: definition.title, start_seconds: startSeconds, end_seconds: (Date.now() - recordingStartedAt) / 1000 });
  await fs.writeFile(path.join(tutorialRoot, '.runtime', `scene-markers-${mode}.json`), JSON.stringify(markers, null, 2));
}

test('recorrido completo del juez', async ({ page }) => {
  const consoleErrors: string[] = [];
  page.on('console', (message) => { if (message.type() === 'error') consoleErrors.push(message.text()); });
  page.on('pageerror', (error) => consoleErrors.push(error.message));
  recordingStartedAt = Date.now();

  await scene(page, '01', async () => { await brandedCard(page, 'opening'); });

  await scene(page, '02', async () => {
    await page.goto('/juez/asignaciones', { waitUntil: 'networkidle' });
    await expect(page.getByRole('heading', { name: 'Mis asignaciones' })).toBeVisible();
    await installPresentationLayer(page);
    await highlight(page, page.getByRole('heading', { name: 'Mis asignaciones' }), 'Mis asignaciones');
  });

  await scene(page, '03', async () => {
    const cards = page.getByRole('article');
    await expect(cards).toHaveCount(1);
    await highlight(page, cards, 'Tu asignación vigente', 1300);
    await expect(cards.getByRole('link', { name: 'Iniciar evaluación' })).toBeVisible();
    await expect(cards).toContainText('Hermosillo');
  });

  await scene(page, '04', async () => {
    const open = page.getByRole('link', { name: 'Iniciar evaluación' });
    await clickAnnotated(page, open, 'Abrir asignación');
    await expect(page.getByRole('heading', { name: 'Inicio de evaluación' })).toBeVisible();
    await installPresentationLayer(page);
    await expect(page.getByRole('link', { name: 'Declarar conflicto' })).toBeVisible();
    await highlight(page, page.getByRole('link', { name: 'Declarar conflicto' }), 'Sólo si existe un conflicto real');
  });

  await scene(page, '05', async () => {
    const start = page.getByRole('button', { name: 'Iniciar evaluación' });
    await clickAnnotated(page, start, 'Iniciar borrador');
    await expect(page).toHaveURL(new RegExp(`/juez/asignaciones/${scenario.assignment_public_id}/proyecto$`));
    await expect(page.getByRole('heading', { name: 'Proyecto asignado' })).toBeVisible();
    await installPresentationLayer(page);
  });

  await scene(page, '06', async () => {
    await highlight(page, page.getByText('Anonimización estructural.', { exact: true }), 'Paquete ciego');
    await expect(page.getByText('Corredores de sombra para espacios públicos', { exact: true })).toBeVisible();
    await highlight(page, page.getByRole('link', { name: 'Exportar PDF' }), 'Exportar PDF');
    await highlight(page, page.getByRole('link', { name: 'Exportar Excel' }), 'Exportar Excel');
  });

  await scene(page, '07', async () => {
    const next = page.getByRole('link', { name: 'Ir a evaluación', exact: true });
    await clickAnnotated(page, next, 'Ir a evaluación');
    await expect(page.getByRole('heading', { name: 'Evaluación — revisión 1' })).toBeVisible();
    await installPresentationLayer(page);
    await expect(page.getByRole('group')).toHaveCount(4);
    await highlight(page, page.getByText('Disponible al capturar todos los criterios.', { exact: true }), 'Total calculado por el servidor');
  });

  await scene(page, '08', async () => {
    for (const [label, score, comment] of rubric) {
      const group = page.getByRole('group', { name: `${label} — 25.0000 %` });
      await expect(group).toBeVisible();
      await group.getByLabel('Puntaje').fill(score);
      await group.getByLabel('Comentario del criterio (opcional)').fill(comment);
    }
    await page.getByLabel('Comentario general (opcional en borrador)').fill(generalComment);
    await expect(page.getByText('Cambios sin guardar', { exact: true })).toBeVisible();
    await highlight(page, page.getByText('Cambios sin guardar', { exact: true }), 'Cambios pendientes');
  });

  await scene(page, '09', async () => {
    const status = page.locator('[data-autosave-status]');
    await expect(status).toContainText('Guardado automáticamente', { timeout: 45_000 });
    await expect(page.locator('[data-evaluation-total]')).toContainText(`${scenario.expected_total} de 100.00`);
    await expect(page.locator('[data-evaluation-progress-text]')).toHaveText('4 de 4 criterios capturados');
    await highlight(page, page.locator('[data-evaluation-total]'), 'Total del servidor: 82.50');
  });

  await scene(page, '10', async () => {
    const review = page.getByRole('button', { name: 'Revisar y enviar' });
    await clickAnnotated(page, review, 'Revisar y enviar');
    await expect(page.getByRole('heading', { name: 'Revisar y enviar' })).toBeVisible();
    await installPresentationLayer(page);
    await expect(page.getByText('82.50 de 100.00', { exact: false })).toBeVisible();
    await highlight(page, page.getByText('El envío es inmutable.', { exact: true }), 'Última revisión');
  });

  await scene(page, '11', async () => {
    const confirmation = page.getByLabel('Confirmo que revisé los datos y deseo enviar esta evaluación de forma inmutable.');
    await highlight(page, confirmation, 'Confirmación expresa');
    await confirmation.check();
    const submit = page.getByRole('button', { name: 'Enviar evaluación' });
    await clickAnnotated(page, submit, 'Enviar evaluación');
    await expect(page.getByText('La evaluación quedó enviada y sellada.', { exact: false })).toBeVisible();
    await installPresentationLayer(page);
  });

  await scene(page, '12', async () => {
    await expect(page.getByText('La evaluación fue enviada.', { exact: false })).toBeVisible();
    await expect(page.getByRole('textbox')).toHaveCount(0);
    await expect(page.getByRole('button', { name: 'Guardar borrador' })).toHaveCount(0);
    await highlight(page, page.getByText('La evaluación fue enviada.', { exact: false }), 'Revisión sellada y sólo lectura');
  });

  await scene(page, '13', async () => {
    const duration = timeline.scenes.find((item) => item.id === '13')?.duration_seconds ?? 12;
    if (finalMode && duration > 3) await page.waitForTimeout((duration - 2.5) * 1000);
    await brandedCard(page, 'closing');
    await page.waitForTimeout(finalMode ? 2500 : 200);
  });

  expect(consoleErrors, `Errores de consola: ${consoleErrors.join(' | ')}`).toEqual([]);
  await fs.writeFile(path.join(tutorialRoot, '.runtime', `console-${mode}.json`), JSON.stringify(consoleErrors, null, 2));
});
