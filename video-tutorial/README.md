# Video tutorial del rol Juez

Este paquete produce el tutorial **Flower Flow — Mis asignaciones y evaluación de propuestas** exclusivamente contra `flowerflow_testing` y con datos sintéticos. No modifica el código funcional de la plataforma.

## Requisitos

- PHP y dependencias Composer del proyecto.
- MySQL local accesible mediante `.env.testing`.
- Node.js 22 o compatible.
- Chromium de Playwright.
- Python 3 con soporte de entornos virtuales.
- FFmpeg y ffprobe con `libx264`, AAC, `loudnorm` y filtro `subtitles`.
- `OPENAI_API_KEY` definida en el entorno local para generar la voz. Nunca se guarda ni se imprime.

## Regeneración completa

```bash
cd /home/ccortesg/workspace/flowerflow
export OPENAI_API_KEY="configurada-localmente-sin-compartirla"
./video-tutorial/bin/regenerate.sh --full
```

## Validación técnica sin voz ni render final

```bash
cd /home/ccortesg/workspace/flowerflow
./video-tutorial/bin/regenerate.sh --technical
```

## Reanudar únicamente voz y multimedia

Si la grabación final y sus marcadores siguen en `.runtime`, y no cambió la duración
de ningún WAV, se puede repetir únicamente el render. El comando falla cerrado si
la línea de tiempo y la grabación ya no coinciden:

```bash
cd /home/ccortesg/workspace/flowerflow
./video-tutorial/bin/resume-media.sh
```

Si cambió la velocidad o duración de la narración, debe repetirse la grabación para
mantener sincronizadas las escenas. Los WAV actuales se reutilizan sin consumir la API:

```bash
cd /home/ccortesg/workspace/flowerflow
./video-tutorial/bin/regenerate.sh --record-render
```

El script:

1. comprueba el guard exacto de `flowerflow_testing`;
2. crea un dump privado y normalizado;
3. ejecuta la prueba focalizada del wizard;
4. crea el escenario sintético con Carlos Cortés;
5. autentica fuera de cámara;
6. ejecuta revisión visible cuando hay escritorio disponible;
7. ejecuta una corrida técnica con trace y lo elimina después de validarlo;
8. genera un WAV por escena mediante el CLI oficial de OpenAI;
9. mide duraciones, crea subtítulos y graba Chromium;
10. renderiza, normaliza y valida los MP4;
11. restaura la base y coteja el dump por SHA-256, incluso ante error o interrupción.

## Entregables

Los artefactos finales se escriben en `video-tutorial/dist/`. `node_modules`, `.venv`, `.runtime` y `dist` están ignorados por Git. El directorio `.runtime` se elimina al concluir correctamente.

## Seguridad

- El login no forma parte de la grabación.
- Toda cuenta usa el dominio reservado `example.test`.
- Las credenciales se generan aleatoriamente y sólo viven en `.runtime` con permisos restrictivos.
- El servidor usa almacenamiento temporal dentro de `video-tutorial/.runtime`.
- Correo, ledger, notificaciones y servicios externos permanecen deshabilitados, excepto la llamada autorizada a OpenAI para TTS.
- El `trap` restaura la base original y elimina cookies, trace, credenciales y configuración MySQL temporal.
