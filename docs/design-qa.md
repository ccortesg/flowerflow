# QA de diseño — landing pública Flower Flow V2

**Fecha:** 2026-07-15 (`America/Hermosillo`)  
**Rama:** `codex/ui-public-landing-v2`  
**Ruta:** `/`  
**Estado:** en progreso; gates automatizados verdes, captura de navegador pendiente por indisponibilidad del runtime integrado.

## Referencias y estados

- Escritorio: `mejora_flowerflow_escritoriov2.png`, consultada desde Downloads y no copiada al repositorio.
- Móvil: `mejora_flowerflow_movilv2.png`, consultada desde Downloads y no copiada al repositorio.
- Estado visual controlado: registro y recepción activos para comparar los CTA de las referencias.
- Estados funcionales adicionales: registro desactivado, recepción desactivada, público desactivado y competencia ausente cubiertos en Feature tests.

## Comparación de intención

| Área | Referencia | Implementación | Resultado actual |
|---|---|---|---|
| Header | Dos marcas, navegación compacta, CTA naranja; menú móvil | Header exclusivo de `/`, ambos logos, 4 anchors, login, CTA por flag y menú accesible | Implementado |
| Hero | Atardecer, titular dominante, dispositivo/premio, CTA | Contenedor naranja cálido, panorama derivado del cartel, título HTML, dispositivo derivado, cierre y estados reales | Implementado |
| Categorías | Tres tarjetas escritorio; filas compactas móvil | Grid 3 columnas y filas con ícono/texto/flecha bajo 768 px | Implementado |
| Proceso | Cuatro pasos 2×2 | Lista ordenada 2×2, con numeración e íconos existentes | Implementado |
| Requisitos | Seis elementos 3×2 | Grid 3×2; baja a 2 y 1 columna por contenido | Implementado |
| Premio | Bloque oscuro/naranja y dispositivo | Tarjeta carbón con visual autorizado, reglas y máximos en HTML | Implementado |
| Documentos/FAQ | Dos columnas y acordeón | PDF descargables y acordeón Bootstrap con ARIA explícito | Implementado |
| CTA/footer | Franja naranja y footer carbón | CTA por flag y footer con marcas, contacto y legales | Implementado |

## Gates ejecutados

- `PublicLandingTest`: 6 pruebas, 61 aserciones, verde.
- Vite: Node 22.23.1, Yarn 1.22.22, build verde y manifest generado.
- HTML/Blade: IDs de anchors, `aria-controls`, `aria-labelledby`, `aria-expanded`, un `h1` y texto legal autoritativo cubiertos por pruebas/revisión.
- Recursos: ambos WebP son locales, tienen dimensiones declaradas y hashes registrados; no hay URL remota ni asset Apple descargado.
- Regresión: `/login` conserva `ff-navbar` y no recibe el nuevo header/CTA.

## Gate visual pendiente

El runtime de navegador integrado de Codex no pudo inicializarse (`setupAtlasRuntime` encontró una colisión no recuperable en su global `process`) después del bootstrap y reinicio prescritos. No se sustituyó silenciosamente por Playwright externo porque requiere autorización expresa del usuario conforme al flujo de Product Design.

Pendiente capturar y comparar: 1366×768, 1280×800, 1440×900, 390×844 y 360×800; revisar también menú, accordion, anchors, consola, red y `scrollWidth <= innerWidth`. Este documento debe cambiar a `PASSED` sólo después de esa evidencia.
