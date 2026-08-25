import fs from 'node:fs/promises';
import path from 'node:path';

const tutorialRoot = path.resolve(import.meta.dirname, '..');
const timeline = JSON.parse(await fs.readFile(path.join(tutorialRoot, 'timeline.json'), 'utf8'));
const markers = JSON.parse(await fs.readFile(path.join(tutorialRoot, '.runtime', 'scene-markers-final.json'), 'utf8'));

if (markers.length !== timeline.scenes.length) {
  throw new Error(`Marcadores finales incompletos: ${markers.length} de ${timeline.scenes.length}.`);
}

const toleranceSeconds = 0.75;
let recoveredWaitSeconds = 0;
for (let index = 0; index < timeline.scenes.length; index += 1) {
  const expected = timeline.scenes[index];
  const actual = markers[index];
  if (String(actual.id) !== String(expected.id)) {
    throw new Error(`Escena fuera de orden en la posición ${index + 1}.`);
  }
  if (index > 0 && Math.abs(Number(actual.start_seconds) - Number(markers[index - 1].end_seconds)) > toleranceSeconds) {
    throw new Error(`Existe un hueco no recuperable antes de la escena ${expected.id}.`);
  }
  const actualDuration = Number(actual.end_seconds) - Number(actual.start_seconds);
  const expectedDuration = Number(expected.duration_seconds);
  if (actualDuration + toleranceSeconds < expectedDuration) {
    throw new Error(`La escena ${expected.id} no contiene suficiente video para su narración.`);
  }
  recoveredWaitSeconds += Math.max(0, actualDuration - expectedDuration);
}

console.log(`Marcadores visuales validados: ${markers.length} escenas; se normalizarán ${recoveredWaitSeconds.toFixed(3)} segundos de espera excedente.`);
