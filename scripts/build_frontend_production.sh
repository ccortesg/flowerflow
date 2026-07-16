#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
NODE_PIN="22.23.1"
YARN_PIN="1.22.22"

fail() {
  printf 'Error: %s\n' "$1" >&2
  exit 1
}

if [[ "${EUID}" -eq 0 ]]; then
  fail "ejecuta el build con el usuario de despliegue, nunca con root ni sudo."
fi

if ! command -v node >/dev/null 2>&1; then
  fail "Node.js no está disponible. Instala Node ${NODE_PIN} con NVM para este usuario."
fi

actual_node="$(node --version)"
if [[ "$actual_node" != "v${NODE_PIN}" ]]; then
  fail "se requiere Node ${NODE_PIN}; versión observada: ${actual_node}. Ejecuta nvm install ${NODE_PIN} y nvm use ${NODE_PIN}."
fi

if ! command -v corepack >/dev/null 2>&1; then
  fail "Corepack no está disponible en el Node administrado por NVM. Instala corepack@0.35.0 sin sudo."
fi

corepack enable

cd "$ROOT"
corepack install

actual_yarn="$(corepack yarn --version)"
if [[ "$actual_yarn" != "$YARN_PIN" ]]; then
  fail "Yarn debe ser ${YARN_PIN}; Corepack resolvió ${actual_yarn}."
fi

corepack yarn install --frozen-lockfile --non-interactive
corepack yarn build

if [[ ! -s "$ROOT/public/build/manifest.json" ]]; then
  fail "Vite no generó public/build/manifest.json."
fi

printf 'Build frontend verificado: Node %s, Yarn %s.\n' "$(node --version)" "$actual_yarn"
