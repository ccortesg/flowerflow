#!/usr/bin/env bash
set -Eeuo pipefail

TUTORIAL_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
RUNTIME="$TUTORIAL_ROOT/.runtime"
DIST="$TUTORIAL_ROOT/dist"
AUDIO="$DIST/audio"
TIMELINE="$TUTORIAL_ROOT/timeline.json"

mkdir -p "$RUNTIME/audio-segments" "$DIST"

TOTAL_DURATION="$(node -e "const t=require(process.argv[1]); process.stdout.write(String(t.total_duration_seconds))" "$TIMELINE")"

rm -f "$RUNTIME/audio-segments"/*.wav "$RUNTIME/audio-concat.txt"
node - "$TIMELINE" "$RUNTIME/audio-plan.tsv" <<'NODE'
const fs = require('fs');
const t = JSON.parse(fs.readFileSync(process.argv[2], 'utf8'));
fs.writeFileSync(process.argv[3], t.scenes.map(s => `${s.id}\t${s.duration_seconds}`).join('\n') + '\n');
NODE

while IFS=$'\t' read -r SCENE_ID SCENE_DURATION; do
  INPUT="$AUDIO/scene-$SCENE_ID.wav"
  OUTPUT="$RUNTIME/audio-segments/scene-$SCENE_ID.wav"
  ffmpeg -hide_banner -loglevel error -nostdin -y -i "$INPUT" \
    -af "apad=pad_dur=${SCENE_DURATION}" -t "$SCENE_DURATION" -ar 48000 -ac 2 -c:a pcm_s16le "$OUTPUT"
  printf "file '%s'\n" "$OUTPUT" >> "$RUNTIME/audio-concat.txt"
done < "$RUNTIME/audio-plan.tsv"

ffmpeg -hide_banner -loglevel error -y -f concat -safe 0 -i "$RUNTIME/audio-concat.txt" -c:a pcm_s16le -ar 48000 -ac 2 "$AUDIO/narration.es-MX.wav"

PASS1_LOG="$RUNTIME/loudnorm-pass1.log"
ffmpeg -hide_banner -nostats -i "$AUDIO/narration.es-MX.wav" \
  -af "loudnorm=I=-16:TP=-1.7:LRA=11:print_format=json" -f null - 2>"$PASS1_LOG"
node - "$PASS1_LOG" "$RUNTIME/loudnorm-measured.env" <<'NODE'
const fs = require('fs');
const raw = fs.readFileSync(process.argv[2], 'utf8');
const match = raw.match(/\{\s*"input_i"[\s\S]*?\}/g);
if (!match) throw new Error('No se encontró medición loudnorm de primera pasada.');
const m = JSON.parse(match.at(-1));
fs.writeFileSync(process.argv[3], [
  `INPUT_I=${m.input_i}`, `INPUT_TP=${m.input_tp}`, `INPUT_LRA=${m.input_lra}`,
  `INPUT_THRESH=${m.input_thresh}`, `TARGET_OFFSET=${m.target_offset}`
].join('\n') + '\n');
NODE
source "$RUNTIME/loudnorm-measured.env"

NORMALIZED="$RUNTIME/narration-normalized.wav"
ffmpeg -hide_banner -loglevel error -y -i "$AUDIO/narration.es-MX.wav" \
  -af "loudnorm=I=-16:TP=-1.7:LRA=11:measured_I=${INPUT_I}:measured_TP=${INPUT_TP}:measured_LRA=${INPUT_LRA}:measured_thresh=${INPUT_THRESH}:offset=${TARGET_OFFSET}:linear=true:print_format=json" \
  -ar 48000 -ac 2 -c:a pcm_s24le "$NORMALIZED"

RAW_VIDEO="$(find "$RUNTIME/test-results-final" -type f -name video.webm -print -quit)"
if [[ -z "$RAW_VIDEO" || ! -s "$RAW_VIDEO" ]]; then
  echo "No se encontró la grabación final de Playwright." >&2
  exit 3
fi

VISUAL_FILTER="$RUNTIME/visual-filter.txt"
node - "$TIMELINE" "$RUNTIME/scene-markers-final.json" "$VISUAL_FILTER" <<'NODE'
const fs = require('fs');
const timeline = JSON.parse(fs.readFileSync(process.argv[2], 'utf8'));
const markers = JSON.parse(fs.readFileSync(process.argv[3], 'utf8'));
const filters = [];
const labels = [];

timeline.scenes.forEach((scene, index) => {
  const marker = markers[index];
  const target = Number(scene.duration_seconds);
  if (index === timeline.scenes.length - 1) {
    const cardDuration = 2.5;
    const contentDuration = target - cardDuration;
    const cardStart = Number(marker.end_seconds) - cardDuration;
    filters.push(`[0:v]trim=start=${Number(marker.start_seconds).toFixed(3)}:duration=${contentDuration.toFixed(3)},setpts=PTS-STARTPTS[v${index}a]`);
    filters.push(`[0:v]trim=start=${cardStart.toFixed(3)}:duration=${cardDuration.toFixed(3)},setpts=PTS-STARTPTS[v${index}b]`);
    filters.push(`[v${index}a][v${index}b]concat=n=2:v=1:a=0[v${index}]`);
  } else {
    filters.push(`[0:v]trim=start=${Number(marker.start_seconds).toFixed(3)}:duration=${target.toFixed(3)},setpts=PTS-STARTPTS[v${index}]`);
  }
  labels.push(`[v${index}]`);
});

filters.push(`${labels.join('')}concat=n=${labels.length}:v=1:a=0,fps=30,scale=1920:1080:flags=lanczos,format=yuv420p[v]`);
fs.writeFileSync(process.argv[4], `${filters.join(';\n')}\n`);
NODE

CLEAN="$DIST/flower-flow-juez-mis-asignaciones.mp4"
ffmpeg -hide_banner -loglevel error -y -i "$RAW_VIDEO" -i "$NORMALIZED" \
  -filter_complex_script "$VISUAL_FILTER" \
  -map "[v]" -map 1:a:0 -t "$TOTAL_DURATION" \
  -c:v libx264 -preset medium -crf 18 -pix_fmt yuv420p \
  -c:a aac -b:a 192k -ar 48000 -ac 2 -movflags +faststart "$CLEAN"

SUBTITLED="$DIST/flower-flow-juez-mis-asignaciones-subtitulado.mp4"
SUBTITLE_FILTER="subtitles=${DIST//:/\\:}/flower-flow-juez-mis-asignaciones.es-MX.srt:force_style='FontName=DejaVu Sans,FontSize=22,PrimaryColour=&H00FFFFFF,OutlineColour=&H00173C32,BorderStyle=1,Outline=2,Shadow=1,MarginV=42'"
ffmpeg -hide_banner -loglevel error -y -i "$CLEAN" -vf "$SUBTITLE_FILTER" \
  -c:v libx264 -preset medium -crf 18 -pix_fmt yuv420p -r 30 \
  -c:a copy -movflags +faststart "$SUBTITLED"

ffprobe -v error -show_format -show_streams -of json "$CLEAN" > "$DIST/media-metadata.json"
LOUDNESS_LOG="$RUNTIME/loudness-final.log"
ffmpeg -hide_banner -nostats -i "$CLEAN" -af "loudnorm=I=-16:TP=-1.5:LRA=11:print_format=json" -f null - 2>"$LOUDNESS_LOG"
node - "$LOUDNESS_LOG" "$DIST/loudness-analysis.json" <<'NODE'
const fs = require('fs');
const raw = fs.readFileSync(process.argv[2], 'utf8');
const match = raw.match(/\{\s*"input_i"[\s\S]*?\}/g);
if (!match) throw new Error('No se encontró el análisis loudnorm final.');
fs.writeFileSync(process.argv[3], JSON.stringify(JSON.parse(match.at(-1)), null, 2) + '\n');
NODE

echo "Render multimedia completado."
