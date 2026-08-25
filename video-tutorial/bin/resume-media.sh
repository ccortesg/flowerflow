#!/usr/bin/env bash
set -Eeuo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
TUTORIAL_ROOT="$PROJECT_ROOT/video-tutorial"
RUNTIME="$TUTORIAL_ROOT/.runtime"
DIST="$TUTORIAL_ROOT/dist"
RAW_VIDEO="$RUNTIME/test-results-final/tutorial-recorrido-completo-del-juez/video.webm"
cd "$PROJECT_ROOT"
[[ "$(pwd)" == "/home/ccortesg/workspace/flowerflow" ]]
[[ "$(git rev-parse --show-toplevel)" == "/home/ccortesg/workspace/flowerflow" ]]

if [[ ! -s "$RAW_VIDEO" ]]; then
  echo "No existe una grabación final reutilizable; ejecuta regenerate.sh --full." >&2
  exit 3
fi

for scene_id in $(seq -w 1 13); do
  [[ -s "$DIST/audio/scene-$scene_id.wav" ]]
done

node "$TUTORIAL_ROOT/scripts/update-timeline.mjs"
node "$TUTORIAL_ROOT/scripts/generate-subtitles.mjs"
node "$TUTORIAL_ROOT/scripts/validate-scene-sync.mjs"
bash "$TUTORIAL_ROOT/scripts/render-media.sh"
node "$TUTORIAL_ROOT/scripts/validate-output.mjs"

python3 - "$DIST/flower-flow-juez-mis-asignaciones.mp4" <<'PY'
import sys
data = open(sys.argv[1], 'rb').read(4_000_000)
if data.find(b'moov') < 0 or data.find(b'mdat') < 0 or data.find(b'moov') > data.find(b'mdat'):
    raise SystemExit('faststart no verificado: moov no precede a mdat')
PY

git diff --check -- video-tutorial
rm -rf "$RUNTIME"
echo "Tutorial multimedia regenerado y validado correctamente en $DIST"
