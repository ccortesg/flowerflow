import fs from 'node:fs/promises';
import path from 'node:path';

const tutorialRoot = path.resolve(import.meta.dirname, '..');
const dist = path.join(tutorialRoot, 'dist');
const metadata = JSON.parse(await fs.readFile(path.join(dist, 'media-metadata.json'), 'utf8'));
const loudness = JSON.parse(await fs.readFile(path.join(dist, 'loudness-analysis.json'), 'utf8'));
const timeline = JSON.parse(await fs.readFile(path.join(tutorialRoot, 'timeline.json'), 'utf8'));
const video = metadata.streams.find((stream) => stream.codec_type === 'video');
const audio = metadata.streams.find((stream) => stream.codec_type === 'audio');
const duration = Number(metadata.format.duration);
const integrated = Number(loudness.input_i);
const truePeak = Number(loudness.input_tp);
const spokenWords = timeline.scenes.map((scene) => scene.narration).join(' ').trim().split(/\s+/).length;
const spokenMinutes = timeline.scenes.reduce((sum, scene) => sum + Number(scene.audio_duration_seconds), 0) / 60;
const wpm = spokenWords / spokenMinutes;
const checks = [
  ['video H.264', video?.codec_name === 'h264'],
  ['resolución 1920x1080', video?.width === 1920 && video?.height === 1080],
  ['pixel format yuv420p', video?.pix_fmt === 'yuv420p'],
  ['30 fps', Math.abs(evalRate(video?.avg_frame_rate) - 30) < 0.01],
  ['audio AAC', audio?.codec_name === 'aac'],
  ['48 kHz', Number(audio?.sample_rate) === 48000],
  ['audio estéreo', Number(audio?.channels) === 2],
  ['duración entre 6 y 7 minutos', duration >= 360 && duration <= 420],
  ['loudness -16 LUFS ±0.5', integrated >= -16.5 && integrated <= -15.5],
  ['true peak <= -1.5 dBTP', truePeak <= -1.5],
  ['ritmo entre 145 y 155 ppm', wpm >= 145 && wpm <= 155]
];

function evalRate(value) {
  const [left, right] = String(value ?? '0/1').split('/').map(Number);
  return right ? left / right : 0;
}

const failures = checks.filter(([, ok]) => !ok);
const report = `# Informe de validación\n\n` +
  `- Resultado multimedia: **${failures.length ? 'NO-GO' : 'GO'}**\n` +
  `- Duración: ${duration.toFixed(3)} s\n` +
  `- Resolución: ${video?.width}×${video?.height}\n` +
  `- Video: ${video?.codec_name}, ${video?.pix_fmt}, ${evalRate(video?.avg_frame_rate).toFixed(2)} fps\n` +
  `- Audio: ${audio?.codec_name}, ${audio?.sample_rate} Hz, ${audio?.channels} canales\n` +
  `- Loudness integrado: ${integrated.toFixed(2)} LUFS\n` +
  `- True peak: ${truePeak.toFixed(2)} dBTP\n` +
  `- Narración: ${spokenWords} palabras; ${wpm.toFixed(1)} palabras por minuto de voz\n\n` +
  `## Comprobaciones\n\n` + checks.map(([name, ok]) => `- [${ok ? 'x' : ' '}] ${name}`).join('\n') + '\n';
await fs.writeFile(path.join(dist, 'validation-report.md'), report);
if (failures.length) {
  console.error(`Fallaron: ${failures.map(([name]) => name).join(', ')}`);
  process.exit(2);
}
console.log('Validación multimedia: GO.');
