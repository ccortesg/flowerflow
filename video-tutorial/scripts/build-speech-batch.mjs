import fs from 'node:fs/promises';
import path from 'node:path';

const tutorialRoot = path.resolve(import.meta.dirname, '..');
const runtime = path.join(tutorialRoot, '.runtime');
const timeline = JSON.parse(await fs.readFile(path.join(tutorialRoot, 'timeline.json'), 'utf8'));
const jobs = timeline.scenes.map((scene) => JSON.stringify({
  input: scene.narration,
  out: `scene-${scene.id}.wav`
}));
await fs.mkdir(runtime, { recursive: true, mode: 0o700 });
await fs.writeFile(path.join(runtime, 'speech-batch.jsonl'), `${jobs.join('\n')}\n`, { mode: 0o600 });
console.log(`Lote de voz preparado: ${jobs.length} escenas.`);
