# ADR 0007 — Exportaciones XLSX privadas de propuestas

Estado: aceptado · 2026-08-11

## Contexto

El panel necesita exportar borradores y propuestas enviadas con datos de contacto, proyecto, equipos y referencias a anexos. Estos datos contienen PII y contenido confidencial; una exportación cliente o una URL pública ampliaría el riesgo de fuga y no respetaría las Policies actuales.

## Decisión

- Generar XLSX exclusivamente en backend mediante `openspout/openspout` 4.32.0, compatible con PHP 8.3 y fijado en `composer.lock`.
- Ejecutar la generación mediante job después del commit, con estado persistido, reintentos acotados y archivo en un disk privado `serve=false`.
- Limitar creación y descarga a `export submissions`, confirmación reciente de contraseña y ownership del export.
- Expirar y purgar el XLSX 24 horas después de completarlo; conservar sólo auditoría redactada.
- Usar el snapshot inmutable para propuestas enviadas y el registro actual para borradores.
- Separar el libro en hojas para propuestas, contactos, integrantes, archivos y enlaces externos.
- Escribir entrada de usuario como texto literal para impedir inyección de fórmulas.
- Los enlaces de anexos apuntan a rutas estables con autenticación y Policy; no son URLs públicas ni tokens bearer.
- Excluir fecha de nacimiento, comprobantes de residencia, aclaraciones, notas internas, secretos, sesiones, IP/user-agent, rutas de storage, hashes y claves técnicas.

## Consecuencias

- Se requiere un worker y scheduler operativos antes de producción.
- El XLSX descargado deja de estar bajo control técnico de la aplicación; la UI debe identificarlo como confidencial.
- La base añade `submission_exports`, sin modificar propuestas existentes.
- La dependencia y sus extensiones se verifican en local/staging/producción y se registran en `docs/dependency-register.md`.
- Un usuario que abre un enlace desde Excel debe iniciar sesión si no tiene una sesión válida; la revocación de permisos se aplica en cada descarga.

## Alternativas rechazadas

- DataTables/JSZip en navegador: expone el dataset completo al cliente y aumenta el consumo de memoria.
- CSV único: no representa limpiamente relaciones uno-a-muchos ni ofrece el contrato XLSX solicitado.
- URLs firmadas anónimas: quien posea el libro podría descargar anexos hasta el vencimiento.
- Un renglón por propuesta con listas concatenadas: pierde estructura, complica validación y puede truncar celdas.

## Validación

Pruebas de permisos y ownership, generación/reintento/fallo/expiración, snapshots, fechas, enlaces autenticados, texto hostil, lectura independiente del XLSX, volumen sintético, auditoría, suite completa y QA visual del panel.
