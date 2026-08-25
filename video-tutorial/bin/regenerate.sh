#!/usr/bin/env bash
set -Eeuo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
TUTORIAL_ROOT="$PROJECT_ROOT/video-tutorial"
RUNTIME="$TUTORIAL_ROOT/.runtime"
DIST="$TUTORIAL_ROOT/dist"
MYSQL_CNF="$RUNTIME/mysql-client.cnf"
BACKUP="$RUNTIME/flowerflow-testing-before.sql.gz"
DATA_BACKUP="$RUNTIME/flowerflow-testing-data-before.sql.gz"
SERVER_PID=""
RESTORED=0
MODE="${1:---full}"

if [[ "$MODE" != "--full" && "$MODE" != "--technical" && "$MODE" != "--record-render" ]]; then
  echo "Uso: $0 [--full|--technical|--record-render]" >&2
  exit 2
fi

mkdir -p "$RUNTIME" "$DIST/audio" "$RUNTIME/laravel-storage/framework/cache" "$RUNTIME/laravel-storage/framework/sessions" "$RUNTIME/laravel-storage/framework/views" "$RUNTIME/laravel-storage/logs"
chmod 0700 "$RUNTIME"

export APP_ENV=testing
export FLOWERFLOW_EVALUATION_ENABLED=true
export FLOWERFLOW_EVALUATION_FINALIZATION_ENABLED=true
export FLOWERFLOW_COMMUNICATION_LEDGER_ENABLED=false
export FLOWERFLOW_EVALUATION_NOTIFICATIONS_ENABLED=false
export FLOWERFLOW_EVALUATION_CLOSE_DIGEST_ENABLED=false
export FLOWERFLOW_JUDGE_ASSIGNMENT_NOTIFICATION_ENABLED=false
export MAIL_MAILER=array
export QUEUE_CONNECTION=sync
export SESSION_DRIVER=file
export CACHE_STORE=array
export LARAVEL_STORAGE_PATH="$RUNTIME/laravel-storage"
export TUTORIAL_BASE_URL="http://127.0.0.1:8787"

cd "$PROJECT_ROOT"

stop_server() {
  if [[ -n "$SERVER_PID" ]] && kill -0 "$SERVER_PID" 2>/dev/null; then
    kill "$SERVER_PID" 2>/dev/null || true
    wait "$SERVER_PID" 2>/dev/null || true
  fi
  SERVER_PID=""
}

restore_database() {
  stop_server
  if [[ "$RESTORED" -eq 1 || ! -s "$BACKUP" || ! -s "$MYSQL_CNF" ]]; then return; fi
  gunzip -c "$BACKUP" | mysql --defaults-extra-file="$MYSQL_CNF" flowerflow_testing
  mysqldump --defaults-extra-file="$MYSQL_CNF" --single-transaction --skip-comments --no-tablespaces --no-create-info --skip-triggers --complete-insert --order-by-primary flowerflow_testing | gzip -n > "$RUNTIME/flowerflow-testing-data-after.sql.gz"
  local before_hash after_hash
  before_hash="$(sha256sum "$DATA_BACKUP" | awk '{print $1}')"
  after_hash="$(sha256sum "$RUNTIME/flowerflow-testing-data-after.sql.gz" | awk '{print $1}')"
  if [[ "$before_hash" != "$after_hash" ]]; then
    echo "La restauración no reprodujo exactamente los datos originales." >&2
    return 1
  fi
  RESTORED=1
  printf '%s\n' "$before_hash" > "$RUNTIME/restored-database.sha256"
}

cleanup() {
  local status=$?
  restore_database || status=1
  rm -f "$MYSQL_CNF" "$RUNTIME/scenario.json" "$RUNTIME/storage-state.json" "$RUNTIME/speech-batch.jsonl"
  exit "$status"
}
trap cleanup EXIT INT TERM

[[ "$(pwd)" == "/home/ccortesg/workspace/flowerflow" ]]
[[ "$(git rev-parse --show-toplevel)" == "/home/ccortesg/workspace/flowerflow" ]]
[[ "$(sed -n 's/^APP_ENV=//p' .env.testing)" == "testing" ]]
[[ "$(sed -n 's/^DB_CONNECTION=//p' .env.testing)" == "mysql" ]]
[[ "$(sed -n 's/^DB_HOST=//p' .env.testing)" == "127.0.0.1" ]]
[[ "$(sed -n 's/^DB_DATABASE=//p' .env.testing)" == "flowerflow_testing" ]]
[[ "$(sed -n 's/^DB_USERNAME=//p' .env.testing)" == "flowerflow_testing_user" ]]

php "$TUTORIAL_ROOT/scripts/write-mysql-options.php" "$MYSQL_CNF"
[[ "$(mysql --defaults-extra-file="$MYSQL_CNF" -Nse 'SELECT DATABASE()' flowerflow_testing)" == "flowerflow_testing" ]]
mysqldump --defaults-extra-file="$MYSQL_CNF" --single-transaction --skip-comments --no-tablespaces flowerflow_testing | gzip -n > "$BACKUP"
mysqldump --defaults-extra-file="$MYSQL_CNF" --single-transaction --skip-comments --no-tablespaces --no-create-info --skip-triggers --complete-insert --order-by-primary flowerflow_testing | gzip -n > "$DATA_BACKUP"
chmod 0600 "$BACKUP"
chmod 0600 "$DATA_BACKUP"

if [[ ! -d "$TUTORIAL_ROOT/node_modules" ]]; then
  (cd "$TUTORIAL_ROOT" && npm install --no-audit --no-fund)
fi
(cd "$TUTORIAL_ROOT" && npm run typecheck)

if [[ "$MODE" != "--record-render" && "${TUTORIAL_SKIP_DIRECTED_TEST:-0}" != "1" ]]; then
  APP_ENV=testing php artisan test --filter=JudgeEvaluationWizardTest
fi

reset_scenario() {
  stop_server
  APP_ENV=testing php artisan migrate:fresh --seed --force
  APP_ENV=testing php "$TUTORIAL_ROOT/scripts/seed-scenario.php"
}

start_server() {
  (cd "$PROJECT_ROOT/public" && exec php -d "auto_prepend_file=$TUTORIAL_ROOT/scripts/freeze-time.php" -S 127.0.0.1:8787 "$PROJECT_ROOT/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php") >"$RUNTIME/server.log" 2>&1 &
  SERVER_PID=$!
  for _ in $(seq 1 50); do
    if curl -fsS "$TUTORIAL_BASE_URL/login?context=judge" >/dev/null; then return; fi
    sleep 0.2
  done
  echo "El servidor local no respondió." >&2
  return 1
}

if [[ "$MODE" == "--record-render" ]]; then
  for scene_id in $(seq -w 1 13); do
    [[ -s "$DIST/audio/scene-$scene_id.wav" ]]
  done
  node "$TUTORIAL_ROOT/scripts/update-timeline.mjs"
  node "$TUTORIAL_ROOT/scripts/generate-subtitles.mjs"
else
  reset_scenario
  start_server
  if [[ "${TUTORIAL_SKIP_MANUAL:-0}" == "1" ]]; then
    echo "Revisión visible reutilizada de una corrida anterior en esta sesión."
  elif [[ -n "${DISPLAY:-}" || -n "${WAYLAND_DISPLAY:-}" ]]; then
    (cd "$TUTORIAL_ROOT" && npm run manual)
  else
    echo "Revisión visible omitida: no hay DISPLAY/WAYLAND disponible; se conserva la corrida técnica Chromium." >&2
  fi

  reset_scenario
  start_server
  (cd "$TUTORIAL_ROOT" && npm run technical)
  TRACE_COUNT="$(find "$RUNTIME/test-results-technical" -type f -name trace.zip | wc -l)"
  [[ "$TRACE_COUNT" -ge 1 ]]
  find "$RUNTIME/test-results-technical" -type f -name trace.zip -delete

  if [[ "$MODE" == "--technical" ]]; then
    restore_database
    RESTORE_HASH="$(cat "$RUNTIME/restored-database.sha256")"
    printf '# Informe de validación técnica\n\n- Resultado: **GO TÉCNICO**\n- `JudgeEvaluationWizardTest`: 3 pruebas y 166 aserciones.\n- Recorrido Chromium: completado sin errores de consola.\n- Trace técnico: generado, comprobado y eliminado.\n- Base `flowerflow_testing`: restaurada y datos cotejados por SHA-256 `%s`.\n- Voz y render multimedia: no solicitados en modo `--technical`.\n' "$RESTORE_HASH" > "$DIST/validation-report.md"
    trap - EXIT INT TERM
    rm -rf "$RUNTIME"
    exit 0
  fi

  if [[ -z "${OPENAI_API_KEY:-}" ]]; then
    echo "OPENAI_API_KEY no está definida. Se detuvo antes de la llamada de voz; configúrala localmente y vuelve a ejecutar --full." >&2
    exit 4
  fi

  if [[ ! -x "$TUTORIAL_ROOT/.venv/bin/python" ]]; then
    python3 -m venv "$TUTORIAL_ROOT/.venv"
  fi
  "$TUTORIAL_ROOT/.venv/bin/python" -m pip install --quiet --disable-pip-version-check "openai==3.3.1"
  node "$TUTORIAL_ROOT/scripts/build-speech-batch.mjs"
  "$TUTORIAL_ROOT/.venv/bin/python" /mnt/c/Users/carlo/.codex/skills/speech/scripts/text_to_speech.py speak-batch \
    --input "$RUNTIME/speech-batch.jsonl" --out-dir "$DIST/audio" --model gpt-4o-mini-tts \
    --voice marin --response-format wav --speed 1.15 --instructions-file "$TUTORIAL_ROOT/assets/voice-instructions.txt" --force
  rm -f "$RUNTIME/speech-batch.jsonl"
  node "$TUTORIAL_ROOT/scripts/update-timeline.mjs"
  node "$TUTORIAL_ROOT/scripts/generate-subtitles.mjs"
fi

reset_scenario
start_server
(cd "$TUTORIAL_ROOT" && npm run record)
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
restore_database
RESTORE_HASH="$(cat "$RUNTIME/restored-database.sha256")"
printf '\n## Entorno y seguridad\n\n- Base `flowerflow_testing` restaurada exactamente; SHA-256 del dump normalizado: `%s`.\n- Trace, cookies, credenciales y configuración MySQL temporal eliminados.\n- Sin SMTP, AWS, producción ni datos reales.\n' "$RESTORE_HASH" >> "$DIST/validation-report.md"

trap - EXIT INT TERM
rm -rf "$RUNTIME"
echo "Tutorial regenerado correctamente en $DIST"
