import fs from 'node:fs/promises';
import path from 'node:path';
import { execFileSync } from 'node:child_process';

const tutorialRoot = path.resolve(import.meta.dirname, '..');
const timelinePath = path.join(tutorialRoot, 'timeline.json');
const audioDirectory = path.join(tutorialRoot, 'dist', 'audio');
const timeline = JSON.parse(await fs.readFile(timelinePath, 'utf8'));
let start = 0;

for (const scene of timeline.scenes) {
  const file = path.join(audioDirectory, `scene-${scene.id}.wav`);
  const duration = Number(execFileSync('ffprobe', [
    '-v', 'error', '-show_entries', 'format=duration', '-of', 'default=noprint_wrappers=1:nokey=1', file
  ], { encoding: 'utf8' }).trim());
  if (!Number.isFinite(duration) || duration <= 0) throw new Error(`Duración inválida para ${file}`);
  scene.audio_duration_seconds = Number(duration.toFixed(3));
  scene.duration_seconds = Number(Math.max(scene.minimum_visual_seconds, duration + 0.65).toFixed(3));
  scene.start_seconds = Number(start.toFixed(3));
  start += scene.duration_seconds;
}

timeline.total_duration_seconds = Number(start.toFixed(3));
timeline.measured_at = new Date().toISOString();
await fs.writeFile(timelinePath, `${JSON.stringify(timeline, null, 2)}\n`);
console.log(`Línea de tiempo actualizada: ${timeline.total_duration_seconds.toFixed(3)} segundos.`);
