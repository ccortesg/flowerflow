#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
IMAGE_SOURCE="$ROOT/imagen"
LEGAL_DEST="$ROOT/public/documentos/2026"
IMAGE_DEST="$ROOT/public/assets/flowerflow"

verify() {
  local expected="$1"
  local file="$2"
  local actual
  actual="$(sha256sum "$file" | cut -d' ' -f1)"
  if [[ "$actual" != "$expected" ]]; then
    echo "Hash inválido: $file" >&2
    exit 1
  fi
}

verify 11c399ca84735d7dbcb17174e192582c93589afa5100c0250753ca15def4db36 "$LEGAL_DEST/01_Mecanica_Convocatoria_Hermosillo_Florece_2026_v1.1.pdf"
verify 4e3e6c272f9459b934004168bfccc26d6759a4dbce6c804c03afbb86cda6b144 "$LEGAL_DEST/02_Terminos_y_Condiciones_Plataforma_Flower_Flow_2026_v1.1.pdf"
verify 041ae9704f80a0108ee69bb39b8646ee8098134573a730f801e4057642ae2da1 "$LEGAL_DEST/03_Aviso_de_Privacidad_Plataforma_Flower_Flow_2026_v1.1.pdf"

if [[ "${1:-}" == "--verify-legal-only" ]]; then
  echo "PDF jurídicos v1.1 verificados."
  exit 0
fi

if [[ $# -gt 0 ]]; then
  echo "Uso: $0 [--verify-legal-only]" >&2
  exit 64
fi

verify ae72624747e19aa72c046fc51a5d55c816b9a20c881609e6c756e5e84cc68b37 "$IMAGE_SOURCE/logo_florecehermosillo.png"
verify 306ccc37eb66de1c8e33f33c3bab3c1aae8dd96543f0c8dada86d8d3bb2e82e0 "$IMAGE_SOURCE/logo_florecehermosillo_transparente.png"
verify fa4892150135dc2337168af677bee22a82eb621ba31b4ce57c7c2adf8782aca5 "$IMAGE_SOURCE/logo_flowerflow.png"
verify 472f0f876debb93e72044978bc872c7e6d912f7b67dbaebca2b592ff46f1baf5 "$IMAGE_SOURCE/logo_flowerflow_transparente.png"
verify 6fb16d3634135dfa2aa602831d90542c975767ebe46a8d43adb7f12654faebd9 "$IMAGE_SOURCE/poster_evento.png"

install -d -m 0755 "$LEGAL_DEST" "$IMAGE_DEST"
install -m 0644 "$IMAGE_SOURCE/logo_florecehermosillo.png" "$IMAGE_DEST/"
install -m 0644 "$IMAGE_SOURCE/logo_florecehermosillo_transparente.png" "$IMAGE_DEST/"
install -m 0644 "$IMAGE_SOURCE/logo_flowerflow.png" "$IMAGE_DEST/"
install -m 0644 "$IMAGE_SOURCE/logo_flowerflow_transparente.png" "$IMAGE_DEST/"
install -m 0644 "$IMAGE_SOURCE/poster_evento.png" "$IMAGE_DEST/"

echo "PDF jurídicos v1.1 y activos gráficos autorizados verificados."
