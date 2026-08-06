# Auditoría integral y réplica física a WSL

## Objetivo

Auditar Flower Flow sin modificar código funcional, consolidar el handoff documental y replicar físicamente el repositorio de `/mnt/c/wamp64/www/flowerflow` a `/home/ccortesg/workspace/flowerflow`, incluida la metadata Git y los archivos ignorados.

## Restricciones

- Sólo se pueden editar documentos.
- Sin instalaciones, migraciones, despliegue, Git mutativo ni limpieza.
- No revelar secretos.
- No sobrescribir un destino existente.
- La comparación de contenido y la comparación Git son gates independientes.

## Progreso

- [x] Línea base de ruta, Git, tamaño, espacio, procesos e ignorados.
- [x] Inventario de arquitectura, dominio, configuración, pruebas, documentos e historial.
- [x] Validaciones no destructivas con resultados y bloqueos explícitos.
- [x] Creación del handoff y reconciliación documental.
- [ ] Revisión final del diff exclusivamente documental.
- [ ] Dos pasadas de copia física con `rsync` sin exclusiones ni `--delete`.
- [ ] Comparación dry-run del working tree excluyendo sólo `.git/`.
- [ ] Comparación por archivos temporales de HEAD, rama, estado, refs, remotos, stashes, tags, ramas y submódulos.
- [ ] `git fsck --full` en destino y comprobación ligera desde WSL.
- [ ] Actualización de la evidencia final de migración.

## Evidencia de validación

| Validación | Resultado |
|---|---|
| Sintaxis PHP | PASS: 141 archivos, 0 fallos. |
| JSON | PASS: cuatro manifiestos. |
| Composer validate/platform reqs | PASS. |
| Route list | PASS: 59 rutas. |
| Migrate status | PASS de lectura; migración Fase 02A pendiente. |
| Unit test seguro | PASS: 1 prueba, 5 aserciones. |
| Build Vite aislado | PASS: 2,218 módulos; warning de chunks. |
| Pint rastreado | FAIL: 10 archivos con deuda. |
| Composer audit | FAIL: 5 advisories de Guzzle. |
| Yarn audit producción | FAIL: 7 bajos, 37 moderados, 38 altos, 4 críticos. |
| Suite completa | BLOQUEADA: `phpunit.xml` no aísla una DB desechable. |

## Decisiones

- El código y las validaciones reproducibles prevalecen sobre snapshots históricos.
- Los fallos se documentan; no se corrigen dentro de esta tarea.
- La metadata del worktree externo se conserva. La raíz actual es el checkout primario y `.git` es un directorio real interno, por lo que la réplica del repositorio principal puede continuar.
- La copia incluye `.env`, dependencias, builds, logs, ignorados y untracked, pero ningún valor secreto se transcribe a documentación o salida.

## Hallazgos y riesgos

- El worktree enlazado físico vive fuera de la raíz y no se replica como segunda carpeta.
- El ambiente de pruebas no falla cerrado respecto de la base principal.
- Las auditorías de dependencias no están verdes.
- La base local principal no tiene aplicada la migración de Fase 02A.
- No se verificó producción ni GitHub en vivo.

## Rollback

No hay rollback funcional: sólo existen cambios documentales no stageados. La fuente Windows se conserva. El destino no debe eliminarse automáticamente si una verificación falla; se reportará el bloqueo para decisión del usuario.
