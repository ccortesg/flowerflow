import fs from 'node:fs/promises';
import path from 'node:path';

const tutorialRoot = path.resolve(import.meta.dirname, '..');
const dist = path.join(tutorialRoot, 'dist');
const timeline = JSON.parse(await fs.readFile(path.join(tutorialRoot, 'timeline.json'), 'utf8'));

function wrap(text, width = 43) {
  const words = text.trim().split(/\s+/);
  const lines = [];
  let current = '';
  for (const word of words) {
    if (!current || `${current} ${word}`.length <= width) current = current ? `${current} ${word}` : word;
    else { lines.push(current); current = word; }
  }
  if (current) lines.push(current);
  if (lines.length <= 2) return lines.join('\n');
  const midpoint = Math.ceil(words.length / 2);
  return [words.slice(0, midpoint).join(' '), words.slice(midpoint).join(' ')].join('\n');
}

function chunks(text) {
  const sentences = text.match(/[^.!?]+[.!?]+|[^.!?]+$/g) ?? [text];
  const result = [];
  for (const sentence of sentences.map((value) => value.trim()).filter(Boolean)) {
    if (sentence.length <= 82) { result.push(sentence); continue; }
    const words = sentence.split(/\s+/);
    let chunk = '';
    for (const word of words) {
      if (!chunk || `${chunk} ${word}`.length <= 82) chunk = chunk ? `${chunk} ${word}` : word;
      else { result.push(chunk); chunk = word; }
    }
    if (chunk) result.push(chunk);
  }
  return result;
}

function srtTime(seconds) {
  const ms = Math.round(seconds * 1000);
  const h = Math.floor(ms / 3_600_000);
  const m = Math.floor((ms % 3_600_000) / 60_000);
  const s = Math.floor((ms % 60_000) / 1000);
  const millis = ms % 1000;
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')},${String(millis).padStart(3, '0')}`;
}

function vttTime(seconds) { return srtTime(seconds).replace(',', '.'); }

const cues = [];
for (const scene of timeline.scenes) {
  const parts = chunks(scene.narration);
  const audioStart = Number(scene.start_seconds);
  const audioDuration = Number(scene.audio_duration_seconds);
  const weights = parts.map((part) => Math.max(1, part.length));
  const totalWeight = weights.reduce((a, b) => a + b, 0);
  let cursor = audioStart;
  parts.forEach((part, index) => {
    const share = audioDuration * (weights[index] / totalWeight);
    const end = index === parts.length - 1 ? audioStart + audioDuration : cursor + share;
    cues.push({ start: cursor, end, text: wrap(part) });
    cursor = end;
  });
}

const srt = cues.map((cue, index) => `${index + 1}\n${srtTime(cue.start)} --> ${srtTime(cue.end)}\n${cue.text}\n`).join('\n');
const vtt = `WEBVTT\n\n${cues.map((cue) => `${vttTime(cue.start)} --> ${vttTime(cue.end)}\n${cue.text}\n`).join('\n')}`;
await fs.writeFile(path.join(dist, 'flower-flow-juez-mis-asignaciones.es-MX.srt'), srt);
await fs.writeFile(path.join(dist, 'flower-flow-juez-mis-asignaciones.es-MX.vtt'), vtt);
await fs.copyFile(path.join(tutorialRoot, 'narration.es-MX.md'), path.join(dist, 'transcripcion.es-MX.md'));
console.log(`Subtítulos generados: ${cues.length} bloques.`);
