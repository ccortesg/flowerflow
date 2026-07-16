#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LEGAL_SOURCE="$ROOT/formatos"
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

verify 42bd5ea13e491dc64a6520f0e26d9663e8e8f973b35a3febf226999118685aa2 "$LEGAL_SOURCE/01_Mecanica_Convocatoria_Hermosillo_Florece_2026.pdf"
verify ca5fdb36f7a35f8268458144348e66485e8870f55a2bdd9da59137143ef4f28c "$LEGAL_SOURCE/02_Terminos_y_Condiciones_Plataforma_Flower_Flow_2026.pdf"
verify 056355c0405984a239e97b5074fc6b78eef61570022f8f94c062919620cc6898 "$LEGAL_SOURCE/03_Aviso_de_Privacidad_Plataforma_Flower_Flow_2026.pdf"
verify ae72624747e19aa72c046fc51a5d55c816b9a20c881609e6c756e5e84cc68b37 "$IMAGE_SOURCE/logo_florecehermosillo.png"
verify 306ccc37eb66de1c8e33f33c3bab3c1aae8dd96543f0c8dada86d8d3bb2e82e0 "$IMAGE_SOURCE/logo_florecehermosillo_transparente.png"
verify fa4892150135dc2337168af677bee22a82eb621ba31b4ce57c7c2adf8782aca5 "$IMAGE_SOURCE/logo_flowerflow.png"
verify 472f0f876debb93e72044978bc872c7e6d912f7b67dbaebca2b592ff46f1baf5 "$IMAGE_SOURCE/logo_flowerflow_transparente.png"
verify 6fb16d3634135dfa2aa602831d90542c975767ebe46a8d43adb7f12654faebd9 "$IMAGE_SOURCE/poster_evento.png"

install -d -m 0755 "$LEGAL_DEST" "$IMAGE_DEST"
install -m 0644 "$LEGAL_SOURCE/01_Mecanica_Convocatoria_Hermosillo_Florece_2026.pdf" "$LEGAL_DEST/"
install -m 0644 "$LEGAL_SOURCE/02_Terminos_y_Condiciones_Plataforma_Flower_Flow_2026.pdf" "$LEGAL_DEST/"
install -m 0644 "$LEGAL_SOURCE/03_Aviso_de_Privacidad_Plataforma_Flower_Flow_2026.pdf" "$LEGAL_DEST/"
install -m 0644 "$IMAGE_SOURCE/logo_florecehermosillo.png" "$IMAGE_DEST/"
install -m 0644 "$IMAGE_SOURCE/logo_florecehermosillo_transparente.png" "$IMAGE_DEST/"
install -m 0644 "$IMAGE_SOURCE/logo_flowerflow.png" "$IMAGE_DEST/"
install -m 0644 "$IMAGE_SOURCE/logo_flowerflow_transparente.png" "$IMAGE_DEST/"
install -m 0644 "$IMAGE_SOURCE/poster_evento.png" "$IMAGE_DEST/"

echo "Activos autorizados publicados y verificados."
