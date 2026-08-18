# Diagnóstico vigente por módulo y rol — 2026-08-17

**Corte de evidencia:** 2026-08-18 MST (`America/Hermosillo`)

**Checkout:** `/home/ccortesg/workspace/flowerflow`

**Rama/SHA auditados:** `codex/submission-deadline-extension` / `e0fa0455e61afcb38593b62ae0d983f75a92b210`
**Naturaleza:** auditoría local de código, documentación y configuración. El propietario confirmó la instalación productiva de los cambios actuales, pero no vinculó esa instalación a un SHA verificable.

**Estado productivo informado:** `OWNER_CONFIRMED_DEPLOYED`

**SHA productivo:** `PRODUCTION_RELEASE_SHA=POR_CONFIRMAR`

Este documento es la fuente vigente para responder “qué existe hoy”. Los documentos con fechas anteriores conservan historia, decisiones y evidencia de sus milestones, pero no deben usarse solos para inferir el estado actual.

> **Adenda Fase 02B M2 — 2026-08-18:** las decisiones permanecen `OWNER_APPROVED` y `P2B-BLOCK-001` quedó resuelto: cuatro jueces principales evaluarán todas las propuestas elegibles sin límite fijo; el quinto será exclusivamente sustituto con capacidad diez. M1/M2 quedaron implementados y verdes exclusivamente en local/test, incluido el perfil primary/substitute. La preparación 02B es 90 % y el avance funcional 20 %. M3 es la siguiente puerta; M4 continúa no implementado/no autorizado.

## Resultado ejecutivo

| Lectura | Avance | Interpretación correcta |
|---|---:|---|
| Producto maestro completo | **63 %** | Incluye sitio, identidad, elegibilidad, propuestas, backoffice, jueces, evaluación, ganadores, resultados, privacidad, operación y producción. M2 añade el ciclo operativo de cuenta juez; rúbrica, asignación/evaluación sustantiva y módulo 8 siguen sin existir. |
| Alcance local expresamente aprobado | **97 %** | Fase 01, Fase 02A, cuarta categoría, exportación, plazo, jurídicos v1.1 y M1/M2 están implementados, automatizados y recorridos por rol. Restan gates externos y de operación que este porcentaje local no acredita. |
| Runtime aislado del release candidate | **97 %** | El guard demuestra ambiente/base/cuenta exactos. `flowerflow_testing` terminó 14/14; M2 usó sólo cuatro cuentas sintéticas, correo array y sesiones database. Resultados permanecieron apagados. |
| Disponibilidad del runtime local primario | **42 %** | Se preservó sin tocarlo: el baseline previo tenía registro/recepción/admisibilidad apagados, cuatro migraciones funcionales pendientes y límite local de tres; el árbol agrega además la migración v1.1. No es el runtime autoridad del RC. |
| Preparación técnica independiente de la rama para producción | **34 %** | Sin cambio: hay código, documentos v1.1, suite y UAT local, pero no evidencia independiente de SHA/migraciones/flags, worker/scheduler, SMTP, integridad, smoke, capacidad o monitoreo productivos. |
| Paso de instalación productiva informado por el propietario | **100 % testimonial** | `OWNER_CONFIRMED_DEPLOYED`: el propietario confirma instalación y más de 50 propuestas reales. No se incorpora al porcentaje funcional ni sustituye evidencia técnica. |
| Verificación técnica independiente de producción en esta tarea | **0 %** | No hubo acceso a URL pública, AWS, EC2, SSH/SSM, APIs, MySQL, logs o servicios externos; `PRODUCTION_RELEASE_SHA=POR_CONFIRMAR`. |

La cifra **63 %** no contradice que el alcance local aprobado esté en **97 %**: la primera mide el plan maestro completo; la segunda, sólo los milestones ya autorizados. La confirmación del despliegue cambia una puerta operativa, pero no demuestra M1/M2 en producción ni aumenta evaluación sustantiva, ganadores o resultados.

## Evidencia verificada en este corte

- Baseline Git confirmado en rama `codex/submission-deadline-extension`; `HEAD`, `origin/codex/submission-deadline-extension` y ancestro común coinciden en `e0fa0455e61afcb38593b62ae0d983f75a92b210`. El primer corte de diseño era limpio; al iniciar M1 existían cambios documentales preexistentes, inventariados y preservados en su ExecPlan.
- El propietario confirma el 2026-08-18 que los cambios actuales fueron instalados, la plataforma sigue publicada y contiene más de 50 propuestas reales: `OWNER_CONFIRMED_DEPLOYED`. Codex no obtuvo evidencia productiva independiente y conserva `PRODUCTION_RELEASE_SHA=POR_CONFIRMAR`.
- Stack efectivo: Laravel 12.64.0, PHP 8.3.33, Node 22.23.1, Yarn 1.22.22 y MySQL client 8.0.46.
- 52 rutas propias al excluir rutas de paquetes; M2 añade `/juez/estado` y `/panel/jueces`.
- 133 archivos PHP bajo `app/`, 14 migraciones y 26 clases de prueba.
- `flowerflow_testing`: 14/14 migraciones aplicadas; M2 pasó forward/rollback/forward preservando un usuario sintético y el seeder no crea cuentas/perfiles juez.
- Suite completa vigente: **125 pruebas y 1,316 aserciones**; M1+M2 dirigido: 16 pruebas y 267 aserciones.
- Pint, Composer validate, requisitos de plataforma, Composer audit, JSON de menús y build Vite: verdes.
- Build: 784 módulos y tres assets; catálogo de 98 iconos verificado.
- Yarn: un advisory **bajo** en Quill 2.0.3 (`GHSA-v3m3-f69x-jf25`), sin versión corregida publicada. La sanitización servidor reduce el vector, pero no elimina la deuda de dependencia.
- UAT Firefox M2: alta/listado/detalle admin, pending/active/suspended, recovery y revocación efectiva de sesión, reactivación, 403/404, 1440/768/390, teclado/foco, reflow, simulación CSS zoom 200 % sin overflow y consola limpia; no se afirma zoom nativo.
- Scheduler de código: sólo purga horaria de XLSX. Se validó manualmente su comando en local; la ejecución real mediante cron/worker externo no fue verificada.

## Cómo se calcularon los porcentajes

Cada funcionalidad se revisó en cinco dimensiones, de 0 a 20 puntos cada una:

1. contrato de producto aprobado y no contradictorio;
2. modelo/backend y migración;
3. autorización, privacidad e invariantes;
4. interfaz utilizable por el rol;
5. prueba automatizada, navegador y operación.

`100 %` exige las cinco dimensiones cerradas. Una pantalla sin Policy, un backend sin interfaz o documentación sin código no se cuentan como funcionalidad terminada. El porcentaje del producto maestro usa estos pesos:

| Módulo | Peso | Avance | Aporte ponderado |
|---|---:|---:|---:|
| 1. Sitio público y convocatoria | 8 % | 95 % | 7.60 |
| 2. Identidad, cuenta y acceso | 10 % | 78 % | 7.80 |
| 3. Perfil, residencia y admisibilidad | 12 % | 88 % | 10.56 |
| 4. Proyecto, equipo, archivos y envío | 16 % | 73 % | 11.68 |
| 5. Backoffice y revisión | 12 % | 64 % | 7.68 |
| 6. Legal, contenido y configuración | 8 % | 84 % | 6.72 |
| 7. Jueces, asignación y evaluación | 12 % | 20 % | 2.40 |
| 8. Ganadores y resultados públicos | 6 % | 0 % | 0.00 |
| 9. Comunicaciones transaccionales | 5 % | 63 % | 3.15 |
| 10. Reportes, auditoría y privacidad | 5 % | 46 % | 2.30 |
| 11. QA, infraestructura y operación | 6 % | 48 % | 2.88 |
| **Total** | **100 %** |  | **62.77 % → 63 %** |

El peso mide importancia en el plan maestro, no esfuerzo consumido ni cobertura de líneas.

## Diagnóstico detallado por módulo

### 1. Sitio público y convocatoria — 95 %

| Funcionalidad principal | Avance | Estado/evidencia | Falta para 100 % |
|---|---:|---|---|
| Landing, propósito, proceso, FAQ y contacto | 98 % | Vista real, responsive/accesible, pruebas y UAT 1440/768/360, teclado, foco y zoom sin hallazgos. | Medición externa de rendimiento/SEO. |
| Convocatoria, categorías y fecha de cierre | 98 % | Cuatro categorías, máximo cuatro y cierre inclusivo confirmados en código, datos, PDF v1.1 y UAT. | Apertura formal y ciclo de estados persistido si se aprueba. |
| Documentos públicos | 99 % | Seis PDF inventariados; v1.1 activa por ruta/hash y vínculos recorridos. El propietario designó el binario físico v1.0 y aceptó conservar la discrepancia histórica de hash. | Validación externa posterior a la publicación del SHA. |
| Flags y estados visibles | 97 % | Runtime UAT fail-closed, resultados apagados y vista 503 propia accesible, de marca, sin estilos inline y compatible con CSP/mantenimiento. | Excepción administrativa/ciclo programado sólo si negocio lo aprueba. |
| SEO/noindex | 65 % | Canonical y noindex para panel/autenticado; `robots.txt` existe. | Open Graph propio en layout activo, sitemap y validación SEO externa. |

La versión vigente v1.1 ya alinea cuatro categorías, máximo cuatro y cierre al 23 de agosto. Los PDF v1.0 quedan sólo como historia; persiste un incidente de integridad porque el binario físico de Mecánica v1.0 no coincide con el hash registrado originalmente.

### 2. Identidad, cuenta y acceso — 78 %

| Funcionalidad principal | Avance | Estado/evidencia | Falta para 100 % |
|---|---:|---|---|
| Registro, login, logout y verificación | 98 % | Fortify, vistas propias, perfil transaccional, pruebas y UAT real con verificación controlada y correo array. | SMTP/entregabilidad externa y activación productiva. |
| Reset y política de contraseña | 90 % | Regla backend única, vistas accesibles, notificación cifrada en cola y no 500 al fallar dispatch. | Prueba de entregabilidad real y operación de `failed_jobs`. |
| RBAC/Policies y gates de shell | 98 % | Roles `participant`, `reviewer`, `judge`, `admin`; permiso exclusivo de juez, middleware de rol exacto, estado activo, rutas separadas y matriz positiva/negativa. | Mantener el escritor exclusivo en M3+ y automatizar inventario global de rutas/permisos. |
| Shell mínimo de juez M1/M2 | 100 % | `/juez` sólo con auth+verified+rol exacto+permiso+perfil active+flag; pending/suspended reciben estado seguro, UAT responsive y consola limpia. | Su contenido de evaluación pertenece a M4+ y no reduce el cierre de cuenta M2. |
| 2FA privilegiado | 82 % | El juez conserva 2FA opcional; recovery sólo admin con permiso, razón, contraseña, auditoría, aviso y revocación. | Entregabilidad/operación externa y políticas distintas para otros roles. |
| Confirmación de contraseña crítica | 60 % | Exportaciones y acciones M2 de suspensión/reactivación/recovery la exigen. | Aplicarla según riesgo a resoluciones, RBAC/configuración y acciones futuras. |
| Suspensión y revocación de sesiones de juez | 100 % | Estado respaldado, sesiones database fail-closed, remember token, auditoría, UI, pruebas y navegador. | Otros roles/casos pertenecen a un alcance separado. |
| Alta de juez | 100 % | Alta directa admin, correo normalizado, password aleatoria no expuesta, broker reset, activación por prerrequisitos y mail resiliente. | Invitaciones de integrantes pertenecen a otro contrato. |

M1 cerró el riesgo de descarte y M2 añadió perfil activo/suspensión/recovery sin relajar gates. Una cuenta pending/suspended sólo recibe estado seguro y una cuenta cero/multirol falla cerrada.

### 3. Perfil, residencia y admisibilidad — 88 %

| Funcionalidad principal | Avance | Estado/evidencia | Falta para 100 % |
|---|---:|---|---|
| Perfil y elegibilidad declarativa | 95 % | Edad, celular E.164, colonia, residencia declarada y consentimientos separados. | Política de rectificación/retención y verificación externa si se aprueba. |
| Expediente ligado a snapshot | 95 % | Uno por propuesta/version inmutable, backfill idempotente y estados respaldados. | Operación sobre staging y tratamiento de correcciones con nueva versión. |
| Aclaraciones append-only | 95 % | Solicitud, respuesta, anexos privados, cierre, mail/auditoría y UAT completa por participante/reviewer. | Regla final de plazos/escalamiento. |
| Residencia por representante/integrante | 90 % | Solicitud separada, upload privado, revisión y resolución humana. | Catálogo jurídico cerrado y vigencia aprobada. |
| Decisión admitida/no admitida | 95 % | Precondiciones, motivo visible, nota interna aislada, evento inmutable y UAT de admisión. | Reapertura excepcional autorizada y conexión aprobada con Fase 02B. |
| Retención | 40 % | Calcula fecha candidata y reporta en dry-run; no borra. | Regla aprobada post-ganadores, job de eliminación/anonimización, backup y auditoría de ejecución. |

La Fase 02A está disponible en el runtime aislado del RC y cerró UAT. El runtime local primario permanece intacto/apagado y no es evidencia de disponibilidad productiva.

### 4. Proyecto, equipo, archivos y envío — 73 %

| Funcionalidad principal | Avance | Estado/evidencia | Falta para 100 % |
|---|---:|---|---|
| Borrador y wizard | 90 % | Cuatro pasos, guardado explícito por sección, navegación accesible y pruebas de preservación. | Autosave/versionado optimista si sigue siendo requisito; no simularlo en UI. |
| Equipo básico | 65 % | Representante más hasta cuatro integrantes, validación y límite transaccional. | Cuentas/invitaciones/aceptación de integrantes, membresía y colaboración. |
| Contenido enriquecido | 90 % | Delta, HTML y texto; sanitización al guardar/renderizar y pruebas XSS. | Resolver advisory de Quill cuando exista fix y corpus adicional. |
| Archivos privados | 90 % | Firma/MIME, nombres aleatorios, hash, cuota, Policy, auditoría, compensación y UAT de carga/descarga/IDOR. | Antivirus/cuarentena, storage productivo y alarmas. |
| Enlaces externos | 90 % | HTTPS/hosts exactos, sin fetch servidor, credenciales embebidas rechazadas. | Decisión de proveedores adicionales si negocio los requiere. |
| Envío final | 98 % | Lock, idempotencia, folio, snapshot, aceptación v1.1, evento/correo post-commit y UAT real. | Carga en staging y excepción tardía formal si se aprueba. |
| Corrección de propuesta enviada | 15 % | Existe aclaración, pero no edición controlada ni `SubmissionVersion` posterior. | Máquina de corrección, nueva versión, comparación, reenvío e historial. |
| Eliminar borrador/retirar propuesta | 5 % | Se elimina archivo de borrador; no la propuesta ni se usa `withdrawn` desde UI. | Decisión de negocio, endpoints, auditoría y ventana temporal. |

El runtime aislado valida cuatro propuestas y rechaza la quinta. El `.env` primario histórico conserva tres, no fue modificado y no debe usarse para este candidato.

### 5. Backoffice y revisión — 64 %

| Funcionalidad principal | Avance | Estado/evidencia | Falta para 100 % |
|---|---:|---|---|
| Dashboard | 88 % | Conteos, distribución por cuatro categorías, actividad reciente y UAT responsive. | Métricas aprobadas, rangos/fechas, masking y volumen. |
| Listado/detalle de propuestas | 88 % | Paginación servidor, filtros, PII/anexos por permisos y UAT con 31 filas/2 páginas. | Filtros adicionales, EXPLAIN/carga y vistas redactadas por alcance. |
| Revisión de admisibilidad | 95 % | Flujo completo, residencia, notas, mail/auditoría y UAT reviewer/participant. | Reapertura y SLA/escalamiento. |
| Exportación XLSX privada | 97 % | Cinco hojas, snapshots, links autenticados, ownership/password, UAT de generación/descarga/expiración/purga. | Worker/scheduler real y prueba de volumen externa. |
| Auditoría consultable | 35 % | Los eventos y `audit_logs` existen y son inmutables. | Rol/vista/búsqueda/export redactado para auditor. |
| Excepciones/reapertura | 10 % | Idempotencia de transiciones finales, sin flujo de reapertura. | Permisos, motivos, eventos, UI y notificación. |
| Asignación de jueces | 0 % | No existe dominio ni ruta. | Depende por completo del módulo 7. |

### 6. Legal, contenido y configuración — 84 %

| Funcionalidad principal | Avance | Estado/evidencia | Falta para 100 % |
|---|---:|---|---|
| Documentos y hashes | 96 % | Seis PDF preservados; v1.1 inmutable/activa por tipo, hashes exactos y 28 páginas revisadas. | Resolver recuperación/publicación inequívoca del original v1.0. |
| Aceptaciones | 94 % | Registro/envío guardan propósitos, FK, versión/hash real; pruebas y alta browser v1.1. El propietario resolvió continuidad v1.0 sin backfill. | Evidencia operativa autorizada en el ambiente objetivo y política futura de retención/ARCO. |
| Configuración tipada | 92 % | Flags, límites, fechas, jurídicos, colas, seguridad y hosts centralizados; runtime UAT valida drift/catálogos. | Validación equivalente en el proceso de release externo. |
| Categorías por datos | 92 % | Cuatro activas, ordenadas, sembradas y respaldadas por v1.1. | CRUD/admin sólo si se aprueba. |
| Coherencia jurídica vigente | 75 % | Cantidades, fecha, responsable y vínculos alineados; superposición y continuidad v1.0 resueltas por el propietario. | Mantener evidencia histórica y verificar futuras versiones/cambios sin sustitución silenciosa. |
| Licencia Materialize/Pixinvent | 0 % | No hay evidencia comercial en repositorio. | Confirmación documental antes de release. |

### 7. Jueces, asignación y evaluación — 20 %

| Funcionalidad principal | Avance | Estado/evidencia | Falta para 100 % |
|---|---:|---|---|
| Rol, permiso y exclusividad M1 | 100 % | Migración/seeder idempotentes; `AssignExclusiveBusinessRole`; admin no hereda el permiso exclusivo. | Reutilizar el contrato en todo escritor futuro. |
| Gates, redirección y shell mínimo M1 | 100 % | Participant/panel/judge separados; cero/multirol fail-closed; `/juez` vacío detrás de flag. | Mantener invariantemente en M3+. |
| Perfil y alta directa M2 | 100 % | `judge_profiles`, `/panel/jueces`, password propia, activación, suspensión/reactivación, sesiones y recovery 2FA; 165 aserciones M2 y UAT Firefox. | Operación de correo externa no forma parte del cierre local. |
| Rúbrica M3 | 0 % | Contrato aprobado, sin tablas/código. | Milestone separado. |
| Asignaciones/conflictos M4 | 0 % | Sin tablas/código; capacidad/cobertura ya definidas con cuatro principales y un sustituto. | Ejecutar sólo tras M3 verde y autorización M4 separada. |
| Paquete ciego M5 | 0 % | Sin proyección/allowlist implementada. | Milestone separado después de dependencias. |
| Evaluación/cálculo/reapertura M6–M7 | 0 % | Sin modelos, rutas, vistas ni puntajes. | Milestones separados; total sólo servidor y revisión append-only. |
| Notificaciones/QA/RC M8–M10 | 0 % | Sólo contrato documental. | Implementar y validar por milestone. |

La preparación documental/técnica es 90 %. M1/M2 no crearon asignaciones ni acceso a propuestas. La próxima tarea implementable es exclusivamente M3. `P2B-BLOCK-001` está resuelto mediante cuatro principales sin límite fijo y un quinto sustituto exclusivo con capacidad diez; M4 espera M3 verde y autorización propia.

### 8. Ganadores y resultados públicos — 0 %

Existe únicamente el flag `results=false` y copy informativo. No hay decisiones de ganador, cálculo/consolidación, permiso de declarar/publicar, preview, ruta pública, consentimiento de campos ni archivo 2026.

Pendientes: empate, categoría desierta, premio exacto, datos publicables, consentimiento, doble confirmación, auditoría, reversión y comunicación. La declaración debe permanecer separada de cualquier puntuación.

### 9. Comunicaciones transaccionales — 60 %

| Funcionalidad principal | Avance | Estado/evidencia | Falta para 100 % |
|---|---:|---|---|
| Verificación/reset | 90 % | HTML/texto en español, cola cifrada, retries y respuesta resiliente. | SMTP/entregabilidad y operación de fallos real. |
| Acuse de propuesta | 90 % | Folio/categoría, sin adjuntos, reenvío limitado y post-commit. | Métrica de entrega e idempotencia operativa por evento. |
| Admisibilidad | 90 % | Cinco variantes, sin contenido sensible, sin rollback ante falla y UAT con correo array. | Worker/SMTP, rebotes y buzón staging. |
| Alta/asignaciones/evaluación/resultados | 0 % | La alta existe; asignaciones/evaluación/resultados no. Las invitaciones de juez fueron descartadas. | Implementar por milestone; M4 ya tiene contrato de capacidad, pero no autorización. |
| Comunicaciones administrativas | 10 % | Sólo mailto/contacto; no hay módulo. | Permisos, plantillas, audiencia, auditoría y anti-duplicado. |

### 10. Reportes, auditoría y privacidad — 42 %

| Funcionalidad principal | Avance | Estado/evidencia | Falta para 100 % |
|---|---:|---|---|
| Conteos operativos | 70 % | Dashboard y distribución por categoría/estado. | Definiciones KPI, filtros temporales, volumen y export de agregados. |
| Exportación de propuestas | 97 % | XLSX privado completo/minimizado; UAT de descarga, expiración y purga. | Operación real del worker/scheduler y carga externa. |
| Auditoría backend | 70 % | Logs/eventos inmutables y descargas sensibles registradas. | UI/rol auditor, retención, búsqueda y export redactado. |
| Storage/retención diagnóstica | 55 % | Auditor read-only y reporte dry-run. | Reconciliación y eliminación autorizadas. |
| Reportes de jueces/conflictos/ganadores | 0 % | No existen. | Dependen de módulos 7/8. |
| Privacidad/ARCO | 0 % | No hay rol, intake, casos, SLA ni workflow. | Decisión legal y módulo separado con mínimo privilegio. |

### 11. QA, infraestructura y operación — 45 %

| Funcionalidad principal | Avance | Estado/evidencia | Falta para 100 % |
|---|---:|---|---|
| Pruebas automatizadas locales | 98 % | 125/1,316, MySQL protegido, legales v1.1, 503/CSP, M1/M2, función/capacidad de juez, archivos, permisos, estados, sesiones, fechas y concurrencia. | Cobertura de módulos futuros y CI permanente. |
| QA navegador | 95 % | UAT Firefox actual por cuatro roles, tres viewports, teclado/foco/zoom, IDOR, 2FA, XLSX y cierre. | Matriz multi-browser permanente y UAT firmada por owner. |
| Dependencias/build | 90 % | Locks, build reproducible, Composer limpio. | Resolver Quill bajo y automatizar el gate en CI. |
| Staging/producción actual | 10 % | `OWNER_CONFIRMED_DEPLOYED` acredita testimonialmente la instalación; no existe evidencia técnica independiente del SHA, migraciones, flags o smoke. | Evidencia separada de SHA/runtime, integridad, smoke/UAT por rol, servicios y monitoreo. |
| Backup/restore/rollback | 15 % | Estrategia escrita; no hay restore drill vigente. | Backup verificado, restauración medida y rollback ensayado. |
| Worker/scheduler/SMTP/monitoreo | 10 % | Configuración y comandos existen. | Servicios reales, alarmas, logs, `failed_jobs`, entregabilidad y capacidad. |

## Estado por rol de usuario

El seeder actual crea `participant`, `reviewer`, `judge` y `admin`. `judge` sólo tiene el permiso mínimo de shell y no se crean cuentas juez automáticamente; los demás actores del plan maestro no son cuentas operables.

| Rol planificado | Avance funcional | Disponibilidad local actual | Acceso que sí existe | Pendientes para acceso completo |
|---|---:|---:|---|---|
| Visitante | 90 % | 96 % | Landing, cuatro categorías, FAQ, documentos v1.1, login, recuperación y registro UAT. | Resultados fuera de alcance, sitemap/OG y decisiones jurídicas residuales. |
| Participante | 82 % | 96 % | Auth, verificación controlada, perfil, cuatro propuestas, archivo privado, envío/folio, aceptaciones v1.1 y seguimiento de admisibilidad. | Corrección versionada, retiro, colaboración por cuentas y resultados futuros. |
| Revisor de elegibilidad | 90 % | 96 % | Panel, listado/detalle/descargas, aclaración, residencia, resolución y aislamiento de nota interna verificados. | Reapertura/SLA, 2FA obligatoria y password confirmation ampliada. |
| Administrador de convocatoria | 75 % | 94 % | Además del panel actual, gestiona alta/estado/suspensión/recovery de jueces con auditoría. | Rúbrica, asignaciones, evaluación, resultados, audit UI y operación productiva. |
| Integrante de equipo | 5 % | 0 % | Se guarda como fila dentro del equipo, sin identidad propia. | Invitación, cuenta, aceptación, permisos, acceso y baja. |
| Juez | 20 % | 30 % con flag test | Cuenta administrable, función primary/substitute, capacidad null/10, credencial propia, estados pending/active/suspended y `/juez` vacío; sin participant/panel/PII/archivos. | M3–M10 separados; M4 espera M3 verde y autorización propia. |
| Soporte de privacidad | 0 % | 0 % | Ninguno. | Rol/permisos y módulo ARCO aprobado. |
| Auditor | 10 % | 0 % | Existen datos de auditoría, no acceso dedicado. | Rol read-only, UI, scopes, masking y export. |
| Superadministrador | 32 % | 25 % | Comando seguro de admin, RBAC base y 2FA opcional. | Rol diferenciado, gestión de permisos/configuración, 2FA obligatoria, password confirm, break-glass y auditoría reforzada. |

### Matriz efectiva de los cuatro roles actuales

| Capacidad | participant | reviewer | admin | judge |
|---|---:|---:|---:|---:|
| Ver/editar perfil participante | Sí | No, 403 | No, 403 | No, 403 |
| Crear/editar/enviar propuesta | Sí con flags/perfil/plazo | No, 403 | No, 403 | No, 403 |
| Ver propuesta desde superficie participante | Sólo propia por Policy | No, 403 | No, 403 | No, 403 |
| Descargar anexo por ruta compartida | Sólo propio por Policy | Sí con permisos | Sí con permisos | No, 403 |
| Entrar a `/panel` | No | Sí | Sí | No, 403 |
| Entrar a `/juez` con flag activo | No, 403 | No, 403 | No, 403 | Sí; estado vacío sin datos |
| Entrar a `/juez` con flag apagado | No | No | No | 404; `/inicio` redirige a estado seguro |
| Operar admisibilidad | Responder/cargar sólo en expediente propio | Sí | Sí | No, 403 |
| Descargar residencia | Propia solicitud | Sí | Sí | No, 403 |
| Exportar XLSX global | No | No | Sí | No, 403 |
| Gestionar/recuperar 2FA | No desde navegación participante | Sí en su cuenta | Sí en su cuenta y recovery de juez con permiso/step-up | Enrolamiento propio opcional; recovery sólo admin, sin ver secretos |

Una cuenta autenticada sin rol o con múltiples roles no corresponde a ninguna columna válida: recibe `/cuenta/acceso`, sin navegación ni datos de participant, judge o panel.

La “disponibilidad local” de esta tabla corresponde exclusivamente al runtime aislado `flowerflow_testing`; no describe el `.env` primario ni producción.

## Desalineaciones entre código, runtime y documentación

1. **Runtime primario preservado:** `config/flowerflow.php` y `.env.example` usan cuatro; el `.env` primario auditado conserva tres. El RC no lo usa ni lo modificó.
2. **Migraciones:** el diagnóstico previo de `flowerflow` registró migraciones funcionales pendientes; no se reconectó por la prohibición expresa. `flowerflow_testing` quedó 14/14; M1/M2 pasaron forward/rollback/forward y M2 preservó un usuario sintético preexistente.
3. **Flags:** los defaults siguen apagando registro/recepción/resultados/admisibilidad. El script UAT los habilita temporalmente salvo resultados, después de validar base, catálogos y documentos activos.
4. **Producción:** el propietario registra `OWNER_CONFIRMED_DEPLOYED`, pero el SHA exacto y la evidencia técnica siguen `POR_CONFIRMAR`. No se debe convertir el SHA local `e0fa0455…` en SHA productivo por inferencia.
5. **2FA:** documentación antigua decía “2FA privilegiado” como terminado; el comportamiento actual es enrolamiento opcional, no enforcement.
6. **Plan maestro:** documentos históricos mezclaban el MVP completo con el alcance recortado Fase 01/02A. Este diagnóstico separa ambas medidas.
7. **Rutas demo:** los JSON heredados conservan entradas demo, pero el layout Flower Flow activo no los usa para la navegación funcional. Sigue siendo deuda de limpieza, no evidencia de acceso real.
8. **Evidencia jurídica v1.0:** el hash histórico de Mecánica (`42bd5e…`) no coincide con el binario hoy publicado bajo el mismo nombre (`3bcf31…`). v1.1 no reescribe ese antecedente.
9. **Error de cierre resuelto:** `resources/views/errors/503.blade.php` usa el layout y assets Vite normales, contenido accesible/de marca y cero estilos inline. Funciona para cierre por middleware y pre-render de mantenimiento; la prueba dirigida bajo CSP estricta y el render real móvil/escritorio quedaron verdes.
10. **Topología productiva confirmada:** `/var/www/flowerflow` es el checkout Git productivo directo y no existen `releases/current/shared`. El propietario informa que el VirtualHost `app.sguniformes.com.mx` apunta a esa ruta. Se registra literalmente sin inferir un cambio del host canónico `app.flowerflow.com.mx` ni autorizar cambios de Apache.

## Riesgos priorizados

### P0 — impiden declarar producción verificada independientemente o iniciar el milestone afectado de Fase 02B

1. `PRODUCTION_RELEASE_SHA=POR_CONFIRMAR`; migraciones, flags, integridad, smoke/UAT, worker/scheduler, SMTP y monitoreo carecen de evidencia independiente en esta tarea.
2. `P2B-BLOCK-001` está resuelto: cuatro principales sin límite fijo y quinto sustituto exclusivo con capacidad diez; el riesgo residual es superar diez sustituciones activas.

### P1 — deben cerrarse antes de ampliar roles o activar recepción

1. El propietario decidió 2FA opcional para juez; M2 implementó localmente recuperación administrativa, suspensión/revocación y password confirmation. Resta acreditar operación externa y mantener estos controles en releases futuros.
2. Decidir antimalware/storage productivo y resolver la operación del advisory bajo de Quill.

### P2 — calidad y mantenibilidad

1. CI con suite/build/auditorías y una herramienta E2E permanente.
2. Pruebas de carga/EXPLAIN con volumen aprobado.
3. Limpiar documentación/menús demo restantes y completar SEO.
4. Crear vista de auditor y matriz automatizada de permisos por ruta/rol.

P2 503/CSP quedó resuelto el 2026-08-18; ya no forma parte de esta lista.

## Siguiente secuencia óptima

1. **Autorizar y ejecutar sólo M3:** usar el prompt vigente para rúbrica global versionada en local/test.
2. **Cerrar M3 con evidencia:** no avanzar hasta migración reversible, suite, permisos negativos, UAT, build, rollback y documentación verdes.
3. **No mezclar M4 con M3:** M4 ya no tiene bloqueo decisorio, pero sólo puede proponerse después de M3 verde mediante autorización separada.
4. **Preservar la corrección de capacidad:** cuatro principales sin límite fijo; quinto sólo sustituciones, máximo diez activas y undécima rechazada.
5. **Mantener separados carriles futuros:** M3–M10 requieren sus propios gates; ganadores/resultados, ARCO, producción y cambios de infraestructura permanecen fuera.

## Prompt ejecutado para este release candidate

```text
Trabaja en `/home/ccortesg/workspace/flowerflow` y lee primero `AGENTS.md`, `.agent/PLANS.md`, `docs/16-project-status-by-module-and-role-2026-08-17.md`, el ExecPlan vigente y los ADR aplicables.

Objetivo: cerrar un release candidate exclusivamente local del SHA actual, incorporar de forma trazable los tres documentos jurídicos v1.1 recién proporcionados, sincronizar con ellos la documentación, los vínculos y la información aplicable del landing y los paneles, corregir la desalineación del runtime y completar UAT autenticada de las funcionalidades ya implementadas. No implementes jueces, evaluación, ganadores, resultados, ARCO completo, módulos futuros ni cambios en AWS/producción.

Antes de modificar o migrar:
1. confirma `pwd`, Git toplevel, rama, SHA, estado y diff;
2. usa únicamente MySQL local `flowerflow_testing`, usuario `flowerflow_testing_user`, con datos sintéticos;
3. demuestra `APP_ENV=testing`, driver MySQL, host loopback, base configurada y `SELECT DATABASE()`; si cualquier valor difiere, detente;
4. no muestres ni copies secretos y no uses `flowerflow` ni una base productiva;
5. crea/actualiza un ExecPlan específico y registra baseline, decisiones, evidencia, archivos, comandos, resultados y rollback;
6. preserva los cambios preexistentes y no alteres ni sobrescribas los PDF originales.

Insumos jurídicos v1.1 obligatorios:
- `public/documentos/2026/01_Mecanica_Convocatoria_Hermosillo_Florece_2026_v1.1.pdf`;
- `public/documentos/2026/02_Terminos_y_Condiciones_Plataforma_Flower_Flow_2026_v1.1.pdf`;
- `public/documentos/2026/03_Aviso_de_Privacidad_Plataforma_Flower_Flow_2026_v1.1.pdf`.

Tratamiento obligatorio de los PDF:
1. verifica que cada archivo exista, sea legible, sea un PDF válido y no sea un enlace que escape del repositorio; registra nombre, tamaño, número de páginas y SHA-256;
2. lee y analiza detalladamente todas las páginas de cada PDF. Usa extracción de texto y revisión visual de las páginas para validar encabezados, tablas, notas, fechas y cualquier contenido que la extracción no represente correctamente;
3. compara cada v1.1 contra su PDF v1.0 homólogo ya publicado y contra código, configuración, base, seeders, pruebas, vistas, correos y documentación actuales;
4. crea o actualiza una matriz de cambios jurídicos con: documento, página/sección v1.0, página/sección v1.1, cambio exacto, superficie afectada, acción, prueba y estado `VERIFIED`, `PENDING`, `POR_CONFIRMAR` o `PROPOSAL_NEEDED`;
5. no inventes ni reformules reglas, obligaciones, fechas, categorías, premios, elegibilidad, consentimientos, finalidades, retención, derechos, responsables o textos legales. Cita página y sección para cada cambio material;
6. si un PDF está corrupto, incompleto, es internamente contradictorio o no permite determinar vigencia o conducta funcional, registra el bloqueo y no implementes por inferencia la parte afectada;
7. conserva los PDF v1.0, sus hashes, registros y aceptaciones históricas. Incorpora v1.1 como nueva versión inmutable dentro del release candidate local; nunca reemplaces silenciosamente un archivo v1.0 ni reescribas una aceptación previa;
8. los archivos `:Zone.Identifier`, si existen, son metadatos de Windows y no documentos jurídicos: no los enlaces, no los incluyas en inventarios, build o release y repórtalos por separado sin exponer su contenido.

Sincronización jurídica y funcional autorizada en local/test:
- actualiza `docs/legal-change-log.md`, `docs/product-spec.md`, alcance, arquitectura/datos si aplica, UX, trazabilidad, QA, riesgos, preguntas abiertas, runbook, handoff y este diagnóstico para reflejar exactamente v1.1, distinguiendo contenido verificado, contradicciones resueltas, contradicciones aún abiertas y decisiones pendientes;
- revisa todas las referencias a v1.0 y todos los vínculos a los tres PDF mediante búsqueda global. Actualiza los enlaces vigentes a v1.1 en landing, `/documentos`, registro, login, perfil, envío de propuestas, footer, correos y paneles donde corresponda; conserva referencias a v1.0 sólo cuando sean evidencia histórica y etiquétalas como tales;
- actualiza la información visible del landing y de los paneles que v1.1 cambie o aclare —por ejemplo vigencia, fechas, categorías, número de propuestas, premios, requisitos, elegibilidad, contactos, privacidad, consentimientos o retención— únicamente cuando el PDF lo establezca de manera explícita;
- actualiza el registro versionado de documentos jurídicos, rutas, versión `1.1`, hashes y estado activo en seeders/configuración/código con una estrategia idempotente. Debe existir una versión vigente determinística por tipo sin borrar v1.0;
- no modifiques `legal_acceptances` históricas. Si el tratamiento de usuarios que aceptaron v1.0, la reaceptación de v1.1 o su fecha de efectividad no está inequívocamente definido y autorizado, documenta `PROPOSAL_NEEDED`, presenta causa, alternativas, impacto y recomendación, y no inventes la decisión;
- añade o actualiza pruebas para rutas y hashes v1.1, vigencia por tipo, preservación de v1.0, registro de la versión realmente aceptada, enlaces sin roturas, contenido coherente en superficies públicas/autenticadas y permisos por rol;
- verifica que no queden vínculos vigentes rotos ni textos contradictorios entre PDF, landing, formularios, paneles, documentación, configuración y pruebas;
- cualquier cambio de flujo, autorización, tratamiento de datos o regla de negocio que vaya más allá de trasladar fielmente v1.1 requiere primero una propuesta detallada y aprobación; continúa con todo lo no bloqueado.

Alcance autorizado local/test:
- recrear `flowerflow_testing` sólo después de pasar el guard exacto anterior;
- aplicar todas las migraciones y seeders del SHA de trabajo (baseline actual: 11 migraciones); si la implementación v1.1 requiere una migración adicional, debe ser revisada, idempotente hacia los datos existentes, reversible y ejecutarse sólo en `flowerflow_testing`;
- levantar la aplicación contra esa base con los límites, fechas y textos verificados en v1.1 y con flags temporales de registro, recepción, panel y admisibilidad habilitados; resultados deben permanecer apagados;
- crear exclusivamente cuentas y archivos sintéticos;
- ejecutar UAT real en escritorio, tablet y móvil para visitante, participante, reviewer y admin: documentos v1.1 y vínculos, registro/verificación controlada, aceptación y evidencia de la versión correcta, perfil, límite de propuestas y rechazo del excedente, wizard/archivo privado/envío/folio, aclaración/residencia/resolución, panel, paginación, 2FA, exportación XLSX, expiración y cierre conforme a la fecha verificada;
- verificar teclado, foco, zoom/reflow, consola, 403/404, IDOR, rate limits y timestamps Hermosillo/UTC;
- no enviar correo real: usa Mail fake/array o un buzón local aprobado;
- fuera de la sincronización v1.1 expresamente autorizada, no cambies código funcional salvo defectos reproducibles encontrados durante esta UAT; presenta primero causa, impacto y propuesta si el arreglo cambia UX o negocio.

Gates obligatorios finales: validación PDF, inventario y hashes de v1.0/v1.1, matriz jurídica con evidencia por página/sección, búsqueda de referencias obsoletas y vínculos rotos, pruebas específicas de versionado/aceptaciones/enlaces, `php artisan test`, `vendor/bin/pint --test`, Composer validate/platform/audit, Yarn audit documentando el advisory bajo conocido, `scripts/build_frontend_production.sh`, validación JSON, rutas, schedule, migraciones, `git diff --check` y revisión de secretos/PII. Actualiza el ExecPlan y toda la documentación afectada con resultados reales.

No hagas stage, commit, push ni despliegue. No toques EC2, DNS, TLS, SMTP real, AWS ni producción. Entrega:
1. resumen ejecutivo y decisión de go/no-go local;
2. matriz de cambios v1.0 -> v1.1 con evidencia por página/sección;
3. inventario y hashes de los seis PDF, sin exponer datos sensibles;
4. archivos cambiados y justificación;
5. comandos ejecutados y resultados;
6. hallazgos y UAT por rol;
7. contradicciones, `PENDING`, `POR_CONFIRMAR` y `PROPOSAL_NEEDED` restantes;
8. riesgos residuales, rollback y siguiente prompt recomendado.
```

## Prompt óptimo siguiente vigente — implementar únicamente Milestone 3

La fuente canónica, completa y ejecutable está en `docs/18-phase-02b-evaluation-decision-package-2026-08-18.md`, sección 21. Este diagnóstico conserva a continuación una versión operativa abreviada; cualquier ejecución debe usar el bloque canónico completo y toda actualización futura debe modificarse primero allí para evitar divergencia:

```text
Trabaja exclusivamente en el repositorio local `/home/ccortesg/workspace/flowerflow`.

Lee completamente antes de modificar: `AGENTS.md`, `.agent/PLANS.md`, `.agent/execplans/flowerflow-phase-02b-evaluation-design.md`, `.agent/execplans/flowerflow-phase-02b-m1-judge-rbac.md`, `.agent/execplans/flowerflow-phase-02b-m2-judge-profile-onboarding.md`, `docs/18-phase-02b-evaluation-decision-package-2026-08-18.md`, `docs/19-phase-02b-m2-implementation-report-2026-08-18.md`, `docs/16-project-status-by-module-and-role-2026-08-17.md`, `docs/product-spec.md`, `docs/01-functional-scope.md`, `docs/02-architecture.md`, `docs/03-data-model.md`, `docs/04-security-privacy.md`, `docs/05-ux-ui.md`, `docs/06-roadmap-backlog.md`, `docs/08-testing-qa.md`, `docs/09-risk-register.md`, `docs/10-open-questions.md`, `docs/11-operations-handoff.md`, `docs/requirements-traceability.md` y ADR 0001/0003/0004/0005/0006/0007/0008.

Objetivo: implementar exclusivamente el Milestone 3 de Fase 02B: rúbrica global versionada, validación de su contrato, ciclo administrativo borrador→activa→sustituida, inmutabilidad y trazabilidad. Conserva sin regresión M1 y M2.

No implementes asignaciones, paquetes ciegos, conflictos, evaluaciones, borradores de evaluación, captura de puntajes, consolidación, reapertura, notificaciones de evaluación, retención/purga, ganadores o resultados. No crees jueces ni cambies perfiles/capacidad. No implementes todavía un calculador de evaluaciones: M3 sólo persiste y valida el contrato que M6 consumirá; ningún total proveniente del navegador debe aceptarse ahora o después.

Contrato `OWNER_APPROVED`: rúbrica global para cuatro categorías; criterios exactos `pertinence`/Pertinencia/20 %, `clarity`/Claridad/20 %, `feasibility`/Viabilidad/25 %, `impact`/Impacto/25 % y `coherence`/Coherencia/10 %; escala 0–10, paso 0.5; total futuro 0–100 sólo servidor, cuatro decimales internos, dos visibles y `HALF_UP`; comentario general futuro obligatorio 100–2,000 y por criterio opcional hasta 1,000; cada evaluación futura conserva versión exacta; activa/referenciada nunca se modifica o borra; activar nueva versión sustituye la anterior sin reescribirla y existe como máximo una activa global por competencia.

M1/M2 están `GO LOCAL/TEST` y son invariantes. Registra `P2B-BLOCK-001=RESOLVED`: cuatro principales sin límite fijo y quinto sustituto exclusivo con capacidad diez. M3 no implementa asignaciones ni modifica esta decisión; M4 espera M3 verde y autorización propia.

Antes de modificar: confirma `pwd`, Git toplevel, rama, SHA local/remoto, ancestro común, status/diff/preexistentes; preserva cambios sin stage/commit/push/reset/clean; crea `.agent/execplans/flowerflow-phase-02b-m3-versioned-rubric.md`; usa sólo MySQL local `flowerflow_testing`/`flowerflow_testing_user`/loopback/datos sintéticos; antes de base demuestra `APP_ENV=testing`, MySQL, host/base/usuario exactos y `SELECT DATABASE()`; detente si difiere. No accedas a producción, URL pública, AWS, EC2, SSH/SSM, servicios externos o datos/logs productivos.

Implementación autorizada:
- migraciones aditivas/reversibles para versión de rúbrica ligada a competencia y criterios ordenados; ULID, versión positiva única, estados enum `draft`, `active`, `superseded`, precisión/escala/paso/límites de comentarios y actores/fechas de activación/sustitución;
- criterios con código estable, etiqueta, descripción aprobada nullable, peso, mínimo, máximo, paso y orden; FKs, índices, unicidades/checks MySQL y validación server-side;
- no inventar descripciones; si sólo existe el nombre, dejar null y `POR_CONFIRMAR` sin bloquear el contrato numérico;
- exigir exactamente cinco códigos/orden/pesos, suma 100.0000, escala 0–10, paso 0.5, precisión 4/2, `HALF_UP` y límites 100/2,000/1,000; rechazar faltantes, duplicados, extras, NaN, negativos o valores fuera de contrato;
- permisos `view evaluation rubrics`/`manage evaluation rubrics` sólo admin; no conceder `access judge workspace` al admin;
- modelos guarded, enums, Actions transaccionales, Requests/Policies para listar/ver/crear/editar draft/activar; activación con locks, contraseña admin, razón 20–1,000 y auditoría;
- draft editable sólo antes de activar y dentro del contrato; activa/sustituida inmutable/no eliminable. La nueva activa sustituye atómicamente la anterior;
- garantizar una activa con lock/transacción y restricción compatible; no fingir unicidad nullable que MySQL no garantice;
- `/panel/rubricas` accesible/paginado para admin, sin exponerlo al juez ni mostrar propuestas/evaluaciones;
- provisionar idempotentemente en local/test una versión 1 draft exacta, nunca activarla por migración ni sobrescribir divergencias; activación siempre explícita;
- no seeders productivos de evaluaciones/asignaciones ni cambios a propuestas, snapshots, aceptaciones, perfiles o sesiones;
- auditoría redactada y actualización integral de documentación. M4 sigue `NOT IMPLEMENTED / NOT AUTHORIZED`; `P2B-BLOCK-001` permanece resuelto.

Pruebas: forward/rollback/forward preserva usuarios/perfiles; provisionado doble deja una sola v1 exacta y divergencia falla; sólo admin accede; validación/mass assignment/ULID/IDOR negativos; activar exige contraseña/razón, deja una activa y sustituye sin pérdida; activa/sustituida son inmutables; concurrencia no deja dos activas; M1/M2 y suite completa verdes; `/juez` continúa vacío.

Gates tras el guard: migración/rollback/forward M3, pruebas dirigidas M3/M2/M1 y suite completa; Pint; Composer validate/platform/audit; Yarn audit con Quill bajo visible; build, JSON, rutas, schedule, migrate status, diff check, enlaces y scan secretos/PII; UAT Firefox local `/panel/rubricas` en escritorio/tableta/móvil, teclado/foco/reflow/consola/403/404/inmutabilidad.

No stage, commit, push, deploy ni producción/infra/SMTP real. Entrega `GO/NO-GO`, baseline/guard, modelo/versiones, archivos/comandos, matriz por rol, activación/sustitución/inmutabilidad/concurrencia, rollback, riesgos/auditoría, documentación y un siguiente prompt exacto limitado a M4 sólo si M3 queda verde; generarlo no autoriza ejecutarlo.
```

## Prompt histórico inmediato — Milestone 2 ejecutado localmente

```text
Trabaja exclusivamente en el repositorio local `/home/ccortesg/workspace/flowerflow`.

Lee completamente antes de modificar: `AGENTS.md`, `.agent/PLANS.md`, `.agent/execplans/flowerflow-phase-02b-evaluation-design.md`, `.agent/execplans/flowerflow-phase-02b-m1-judge-rbac.md`, `docs/18-phase-02b-evaluation-decision-package-2026-08-18.md`, `docs/16-project-status-by-module-and-role-2026-08-17.md`, `docs/product-spec.md`, `docs/01-functional-scope.md`, `docs/02-architecture.md`, `docs/03-data-model.md`, `docs/04-security-privacy.md`, `docs/05-ux-ui.md`, `docs/06-roadmap-backlog.md`, `docs/08-testing-qa.md`, `docs/09-risk-register.md`, `docs/10-open-questions.md`, `docs/11-operations-handoff.md`, `docs/requirements-traceability.md` y ADR 0001/0003/0004/0005/0006/0007/0008.

Objetivo: implementar exclusivamente el Milestone 2 de Fase 02B: perfil operativo de juez, alta directa controlada por `admin`, establecimiento seguro de credencial, activación por prerrequisitos, suspensión/reactivación, revocación de sesiones y recuperación administrativa de 2FA. Conserva sin regresión el aislamiento M1.

No implementes invitaciones, asignaciones, paquetes ciegos, conflictos, rúbricas, evaluaciones, puntajes, consolidación, reapertura de evaluaciones, recordatorios masivos, retención/purga, ganadores o resultados. La única notificación autorizada en M2 es la transaccional indispensable para alta/configuración de acceso, suspensión/reactivación y recuperación de 2FA del juez.

Contratos `OWNER_APPROVED` aplicables a M2:
1. `participant`, `reviewer`, `judge` y `admin` son roles estrictamente excluyentes.
2. El alta de juez es directa por `admin`; no existe invitación de juez ni tabla/token equivalente.
3. Cada perfil fija `max_active_assignments=8`; no maneja especialidad ni categorías.
4. El correo debe verificarse antes de acceder a `/juez`.
5. 2FA es opcional y su ausencia nunca bloquea al juez.
6. Sólo `admin` puede recuperar 2FA, con permiso separado, razón obligatoria de 20–1,000 caracteres, confirmación de contraseña, auditoría, notificación y revocación de sesiones.
7. Suspensión bloquea el shell y toda mutación futura de juez; reactivación sólo procede si correo y contraseña propia están establecidos.
8. M1 ya está `GO LOCAL/TEST`; su permiso exclusivo, roles exactos, rutas fail-closed, flag y matrices negativas son invariantes.

Preserva `P2B-BLOCK-001`: con más de 50 propuestas, cuatro jueces × ocho proyectos sólo aportan 32 cupos frente a al menos 204 requeridos, sin quinto juez sustituto. Este bloqueo no impide M2, pero prohíbe iniciar M4, crear asignaciones, elevar capacidades o cambiar el número de jueces por inferencia. M2 no debe auto-crear cuatro jueces ni imponer un máximo global de cuentas juez.

Antes de modificar:
1. confirma `pwd`, Git toplevel, rama, SHA local, SHA remoto, ancestro común, `git status --short`, `git diff` y archivos preexistentes modificados/no rastreados;
2. preserva todos los cambios preexistentes; no hagas stage, commit, push, reset, clean ni checkout destructivo;
3. crea `.agent/execplans/flowerflow-phase-02b-m2-judge-profile-onboarding.md` con baseline, alcance, decisiones, modelo, pasos, pruebas, resultados, riesgos y rollback;
4. usa exclusivamente MySQL local `flowerflow_testing`, usuario `flowerflow_testing_user`, host loopback y datos sintéticos;
5. antes de cualquier migración, seeder o prueba que pueda recrear esquema, demuestra sin exponer secretos: `APP_ENV=testing`, `DB_CONNECTION=mysql`, host `127.0.0.1` o `localhost`, base configurada `flowerflow_testing`, usuario configurado `flowerflow_testing_user` y `SELECT DATABASE()=flowerflow_testing`; si difiere cualquier valor, detente;
6. no accedas a producción, URL pública, AWS, EC2, SSH/SSM, servicios externos, MySQL productivo, logs productivos ni datos reales.

Implementación autorizada M2:
- crea una migración aditiva y reversible para `judge_profiles`, uno-a-uno con `users`, usando el patrón ULID público existente. Incluye como mínimo: `public_id`, `user_id` único, `status` respaldado por enum (`pending_setup`, `active`, `suspended`), `max_active_assignments` con valor fijo ocho, `created_by_user_id`, `password_initialized_at`, `activated_at`, `suspended_at`, `suspended_by_user_id`, `suspension_reason`, `reactivated_at`, `reactivated_by_user_id` y timestamps. No agregues especialidades, categorías, invitaciones ni datos de evaluación;
- protege integridad con FKs/índices/checks compatibles con MySQL y validación server-side. No modifiques ni backfillees usuarios existentes y no crees perfiles por seeder o migración;
- añade permisos administrativos mínimos y separados para ver/administrar jueces y recuperar 2FA. Sólo `admin` los recibe; `reviewer`, `participant` y `judge` no. Conserva `access judge workspace` como permiso exclusivo únicamente de `judge`;
- crea Actions/Services transaccionales y Form Requests/Policies para alta, activación derivada, suspensión, reactivación y recovery. Reutiliza `AssignExclusiveBusinessRole`; cualquier correo existente, cuenta con otro rol, perfil duplicado o carrera debe fallar sin mutación parcial ni sustitución de rol;
- el formulario admin de alta solicita sólo nombre y correo. Normaliza el correo, crea `users` + rol `judge` + perfil `pending_setup` en una transacción y asigna una contraseña aleatoria criptográficamente fuerte que nunca se muestra, registra ni envía;
- después del commit, envía mediante el dispatcher resiliente una plantilla HTML+texto de marca para establecer contraseña usando el broker/token seguro ya existente. No crees token de invitación ni incluyas contraseña, secretos TOTP, PII adicional o datos de propuestas. El fallo de correo no revierte la cuenta y debe quedar observable/reintentable conforme al patrón actual;
- integra el flujo de reset para fijar `password_initialized_at` sólo la primera vez que una cuenta juez establece su propia contraseña. No alteres el contrato de participantes. El correo se verifica mediante el flujo firmado vigente y nunca se marca verificado por inferencia administrativa;
- deriva `active` únicamente cuando existen `password_initialized_at` y `email_verified_at`, salvo que el perfil esté suspendido. La transición debe ser idempotente, transaccional y auditada; `pending_setup` no puede abrir `/juez` aunque el flag esté encendido;
- añade middleware/gate `judge.active` al shell M1. `pending_setup` y `suspended` reciben una pantalla segura, accesible y sin datos de negocio; no caen en participant/panel y no revelan propuestas;
- crea en `/panel/jueces` listado, alta y detalle mínimos para `admin`, con paginación, estados vacíos/error/éxito, acciones autorizadas y UI Materialize/Pixinvent. No agregues dashboard de asignaciones, filtros por especialidad, importación masiva ni exportación;
- suspensión y reactivación requieren confirmación de contraseña y razón de 20–1,000 caracteres. Suspender conserva usuario/rol/perfil, rota `remember_token`, revoca sesiones persistidas de la cuenta y bloquea inmediatamente `/juez`. Reactivar conserva la historia y sólo deja `active` si los prerrequisitos están completos; de lo contrario vuelve a `pending_setup`;
- recovery 2FA requiere permiso separado, confirmación de contraseña y razón de 20–1,000 caracteres. Elimina de forma segura secreto, recovery codes y confirmación TOTP del juez, rota `remember_token`, revoca sus sesiones, conserva actor/fecha/razón en auditoría y notifica al correo verificado. Nunca muestra secretos o recovery codes al admin;
- registra auditoría redactada para alta, correo de configuración solicitado/fallido, activación, suspensión, reactivación y recovery 2FA. Conserva actor real, sujeto por ID técnico, transición y timestamps UTC; no copies contraseñas, tokens, TOTP ni datos de propuestas;
- actualiza documentación, ADR-0008, trazabilidad, riesgos y registros vivos con el comportamiento realmente implementado. Mantén M3–M10 como no implementados y M4 como bloqueado.

Pruebas mínimas obligatorias:
- migración y seeder idempotentes preservan usuarios, roles/permisos y datos existentes; no crean jueces, perfiles, invitaciones ni asignaciones automáticamente;
- `admin` puede crear una cuenta sintética nueva y sólo obtiene un perfil `pending_setup` con rol exclusivo `judge` y capacidad ocho; ningún otro rol puede listar, ver, crear, suspender, reactivar o recuperar 2FA;
- correo existente participant/reviewer/admin/judge, duplicado de perfil, request repetido y carrera concurrente no sustituyen roles ni dejan filas parciales;
- contraseña/token nunca aparecen en respuesta, HTML, correo, audit metadata o logs; Mail fake/array prueba plantilla HTML+texto y fallo resiliente;
- establecer contraseña marca una sola vez `password_initialized_at`; verificar correo y completar ambos prerrequisitos activa de forma idempotente, en cualquier orden. Correo no verificado o contraseña no establecida mantiene `pending_setup`;
- judge activo conserva la matriz M1: sólo `/juez` con flag, nunca participant/panel/archivos/residencia/exportaciones. Pending, suspendido, sin rol y multirol fallan cerrados;
- suspensión revoca sesiones/remember token y bloquea accesos existentes/directos; reactivación exige razón, password confirmation y prerrequisitos;
- 2FA sigue opcional. Recovery sólo admin, exige razón/password confirmation, revoca sesiones, limpia material TOTP, audita/notifica y no concede acceso adicional;
- URLs directas, ULID alterado e IDOR devuelven 403/404 sin revelar existencia; mass assignment no permite cambiar rol, status, capacidad, actores o timestamps;
- regresión positiva de visitante, participant, reviewer y admin, incluida la suite M1 completa.

Validación final, únicamente después del guard MySQL:
- migración/rollback/forward M2 sólo en `flowerflow_testing`, incluyendo upgrade con usuarios sintéticos preexistentes;
- pruebas dirigidas M2, pruebas M1 y `php artisan test` completo;
- `vendor/bin/pint --test`;
- `composer validate --strict --no-check-publish`, `composer check-platform-reqs --no-dev` y `composer audit --locked`;
- `corepack yarn audit --groups dependencies --level moderate`, manteniendo visible el advisory bajo conocido si continúa;
- `scripts/build_frontend_production.sh`;
- validación JSON de menús, `php artisan route:list --except-vendor`, `php artisan schedule:list`, `php artisan migrate:status --env=testing`, `git diff --check`, enlaces Markdown y revisión de secretos/PII;
- QA real local con correo fake/array para admin y estados `pending_setup`/`active`/`suspended` de juez en escritorio, tableta y móvil; teclado, foco, zoom/reflow, consola, 403/404 e invalidación de sesión.

No hagas stage, commit, push ni despliegue. No toques producción, AWS, Apache, PHP-FPM, Supervisor, MySQL productivo, DNS, TLS o SMTP real. Usa exclusivamente cuentas y correos sintéticos.

Entrega:
1. resumen y decisión `GO/NO-GO` local de M2;
2. baseline Git y guard MySQL sin secretos;
3. modelo/estados/invariantes y archivos modificados;
4. comandos y resultados reales;
5. matriz efectiva por rol y estado del perfil/flag;
6. evidencia de alta, credencial segura, activación, suspensión, revocación y recovery 2FA;
7. migración, compatibilidad con datos existentes y rollback;
8. defectos, riesgos, auditoría y `P2B-BLOCK-001` preservado;
9. documentación/trazabilidad actualizadas;
10. siguiente prompt limitado a M3 —rúbrica versionada— sólo si M2 queda completamente verde. No autorices M4 mientras `P2B-BLOCK-001` siga abierto.
```

## Prompt histórico anterior — Milestone 1 ejecutado localmente

Las 21 decisiones ya están `OWNER_APPROVED`. Este prompt autoriza sólo M1; preserva `P2B-BLOCK-001` y no habilita M2–M10.

```text
Trabaja exclusivamente en el repositorio local `/home/ccortesg/workspace/flowerflow`.

Lee completamente antes de modificar: `AGENTS.md`, `.agent/PLANS.md`, `.agent/execplans/flowerflow-phase-02b-evaluation-design.md`, `docs/18-phase-02b-evaluation-decision-package-2026-08-18.md`, `docs/16-project-status-by-module-and-role-2026-08-17.md`, `docs/product-spec.md`, `docs/01-functional-scope.md`, `docs/02-architecture.md`, `docs/03-data-model.md`, `docs/04-security-privacy.md`, `docs/05-ux-ui.md`, `docs/06-roadmap-backlog.md`, `docs/09-risk-register.md`, `docs/10-open-questions.md`, `docs/11-operations-handoff.md`, `docs/requirements-traceability.md` y ADR 0001/0003/0004/0005/0006/0007/0008.

Objetivo: implementar exclusivamente el Milestone 1 de Fase 02B: rol `judge`, permisos mínimos, exclusividad de roles, gates explícitos de rutas, redirección/shell seguros y pruebas de aislamiento. No implementes todavía alta directa de jueces, `judge_profiles`, invitaciones, asignaciones, paquetes ciegos, conflictos, rúbricas, evaluaciones, puntajes, consolidación, reapertura, notificaciones, retención, ganadores o resultados.

Decisiones `OWNER_APPROVED` del 2026-08-18 que debes conservar como contrato futuro, sin implementarlas fuera de M1:
1. Roles `participant`, `reviewer`, `judge` y `admin` estrictamente excluyentes; ninguna combinación válida.
2. Alta de juez directa por `admin`; no habrá invitaciones de juez.
3. Cuatro jueces por propuesta en las cuatro categorías; los cuatro jueces disponibles evaluarían todas.
4. Asignación manual.
5. Máximo ocho asignaciones activas por juez, sin especialidad.
6. Ceguera simple estructural.
7. Visibles todos los campos sustantivos y anexos evaluables; siempre ocultos PII estructurada, residencia, notas internas, aclaraciones e historial de admisibilidad.
8. Anonimización automática estructural; el propietario acepta el riesgo de identidad dentro de texto, imágenes, enlaces y anexos sin bloqueo.
9. Rúbrica global: Pertinencia 20 %, Claridad 20 %, Viabilidad 25 %, Impacto 25 % y Coherencia 10 %.
10. Escala 0–10, paso 0.5, total ponderado 0–100, cuatro decimales internos, dos visibles y `HALF_UP`.
11. Comentario general obligatorio de 100–2,000 caracteres; comentarios por criterio opcionales hasta 1,000.
12. Media aritmética de cuatro evaluaciones válidas con igual peso.
13. Bloquear consolidación si falta cualquier evaluación; sin excepción administrativa.
14. Conflictos: relación personal/familiar, relación profesional/económica, participación en la propuesta u otro conflicto; “otro” exige explicación.
15. `admin` resuelve y reasigna a otro juez; varios jueces pueden evaluar una propuesta mediante asignaciones independientes.
16. Cierre global `2026-08-27 23:59:59 America/Hermosillo`.
17. `admin` puede reabrir hasta `2026-08-27 20:00:00 America/Hermosillo`, con razón de 20–1,000 caracteres, confirmación de contraseña y revisión append-only; juez o admin pueden modificar la revisión nueva hasta las 23:59:59, conservando actor real en auditoría.
18. 2FA opcional; recuperación autorizada por `admin` con trazabilidad.
19. Email mínimo para alta, asignación, conflicto resuelto, reasignación, envío, reapertura y cierre. Recordatorios de participantes el 20 y 22 de agosto de 2026 a las 09:00 Hermosillo para cuentas verificadas sin propuesta o con borrador, incluida una cuenta con otra propuesta enviada.
20. Retención 02B uniforme de 24 meses desde el cierre administrativo del ciclo de evaluación.
21. Empate técnico por igualdad del consolidado redondeado a dos decimales; resolución y ganador fuera de 02B.

Registra y preserva `P2B-BLOCK-001`: con más de 50 propuestas, cuatro jueces × ocho proyectos aportan sólo 32 cupos de asignación, suficientes para ocho propuestas con cuatro evaluaciones; se requieren al menos 204 cupos y no existe un quinto juez de reemplazo ante conflicto. Este bloqueo impide M4/asignaciones, pero no M1. No cambies, “corrijas” ni implementes por inferencia cantidad, capacidad o reemplazos.

Antes de modificar:
1. confirma `pwd`, Git toplevel, rama, SHA local, SHA remoto, ancestro común, `git status --short`, `git diff` y archivos preexistentes modificados/no rastreados;
2. preserva todos los cambios preexistentes; no hagas stage, commit, push, reset, clean ni checkout destructivo;
3. crea `.agent/execplans/flowerflow-phase-02b-m1-judge-rbac.md` con baseline, alcance, decisiones, pasos, pruebas, resultados, riesgos y rollback;
4. usa exclusivamente MySQL local `flowerflow_testing`, usuario `flowerflow_testing_user`, host loopback y datos sintéticos;
5. antes de cualquier migración, seeder o prueba que pueda recrear esquema, demuestra sin exponer secretos: `APP_ENV=testing`, `DB_CONNECTION=mysql`, host `127.0.0.1` o `localhost`, base configurada `flowerflow_testing`, usuario configurado `flowerflow_testing_user` y `SELECT DATABASE()=flowerflow_testing`; si difiere cualquier valor, detente;
6. no accedas a producción, URL pública, AWS, EC2, SSH/SSM, servicios externos, MySQL productivo, logs productivos ni datos reales.

Implementación autorizada M1:
- añadir de forma aditiva e idempotente el rol `judge` y únicamente el permiso base mínimo necesario para acceder al futuro shell de juez; no concederle permisos de participante, `view submissions`, panel, admisibilidad, residencia, exportaciones, configuración, usuarios, rúbricas, asignaciones o evaluaciones todavía;
- impedir que `admin`, `reviewer` o `participant` obtengan el permiso exclusivo del shell juez por una asignación global de “todos los permisos”;
- aplicar exclusividad fail-closed: una cuenta con cero roles o más de un rol no cae por descarte en ningún shell de negocio y recibe una pantalla segura sin datos; toda acción futura de asignación de rol debe rechazar combinaciones;
- proteger explícitamente rutas de participante con rol/capacidad `participant`, conservando además `auth`, `verified`, ownership, flags, fechas y Policies actuales;
- proteger `/panel` con las capacidades actuales de `reviewer`/`admin` y mantener sus permisos existentes;
- crear el gate y redirección segura de `judge` sin tratarlo como participante ni como panel administrativo. Si se crea una superficie mínima `/juez`, debe ser sólo un estado vacío/cerrado accesible y de marca, sin propuestas, PII ni controles de evaluación;
- añadir `FLOWERFLOW_EVALUATION_ENABLED=false` como flag fail-closed. Con flag apagado, el juez sigue sin caer al participante; con flag encendido en test sólo puede abrir la superficie mínima autorizada;
- no exigir 2FA al juez: la decisión aprobada es opcional. M1 no implementa recovery, suspensión o perfil activo, pero no debe bloquear su incorporación posterior;
- mantener rutas, contratos y UX actuales de visitante, participant, reviewer y admin salvo el gate mínimo necesario para cerrar el acceso por descarte;
- actualizar documentación, ADR 0008, trazabilidad, riesgos y registro vivo con comportamiento realmente implementado; conservar M2–M10 como no implementados.

Pruebas mínimas obligatorias:
- matriz positiva/negativa para visitante, participant, reviewer, admin, judge, autenticado sin rol y cuenta sintética con roles múltiples creada sólo para probar fallo cerrado;
- participant conserva inicio, perfil, propuestas, archivos y admisibilidad propia según flags/Policies actuales;
- reviewer/admin conservan `/panel` y no acceden al shell juez como juez;
- judge no accede a `/inicio`, `/perfil`, `/propuestas`, admisibilidad participante, `/panel`, descargas privadas, residencia ni exportaciones;
- judge sólo accede a la superficie mínima cuando el flag está habilitado y su correo está verificado;
- sin rol y multirol no reciben navegación ni datos de participant/judge/panel;
- URLs directas e IDs alterados continúan devolviendo 403/404 sin filtrar existencia;
- el mecanismo idempotente de rol/permisos preserva `participant`, `reviewer`, `admin` y usuarios/datos existentes, y no crea jueces ni asignaciones automáticamente.

Validación final, únicamente después del guard MySQL:
- migración/rollback/forward del cambio M1 si existe migración, sólo en `flowerflow_testing`;
- pruebas dirigidas M1 y `php artisan test` completo;
- `vendor/bin/pint --test`;
- `composer validate --strict --no-check-publish`, `composer check-platform-reqs --no-dev` y `composer audit --locked`;
- `corepack yarn audit --groups dependencies --level moderate`, documentando sin ocultar el advisory bajo conocido si continúa;
- `scripts/build_frontend_production.sh`;
- validación JSON de menús, `php artisan route:list --except-vendor`, `php artisan schedule:list`, `php artisan migrate:status --env=testing`, `git diff --check` y revisión de secretos/PII;
- QA real de la superficie mínima y rechazos por rol en escritorio, tableta y móvil, teclado, foco, zoom/reflow y consola, sólo con cuentas sintéticas y correo fake/array.

No hagas stage, commit, push ni despliegue. No toques producción, AWS, Apache, PHP-FPM, Supervisor, MySQL productivo, DNS, TLS o SMTP real.

Entrega:
1. resumen y decisión `GO/NO-GO` local de M1;
2. baseline Git y guard MySQL sin secretos;
3. archivos modificados y justificación;
4. comandos ejecutados y resultados reales;
5. matriz efectiva de acceso por rol y estado del flag;
6. defectos/hallazgos, riesgos y `P2B-BLOCK-001` preservado;
7. migración/compatibilidad/rollback;
8. documentación y trazabilidad actualizadas;
9. siguiente prompt limitado a M2 —perfil y alta directa— sólo si M1 queda completamente verde; no autorices M4 mientras `P2B-BLOCK-001` siga abierto.
```

## Prompt histórico — runbook de instalación y compilación ya ejecutado por el propietario

```text
Trabaja únicamente con el repositorio local `/home/ccortesg/workspace/flowerflow`. Lee primero `AGENTS.md`, `.agent/PLANS.md`, `.agent/execplans/flowerflow-legal-v1-1-local-release-candidate.md`, `docs/16-project-status-by-module-and-role-2026-08-17.md`, `docs/17-legal-v1-1-reconciliation-2026-08-17.md`, `docs/07-deployment-aws-ec2.md`, `docs/15-risk-reduction-release-runbook.md`, `docs/adr/0002-aws-ec2-ubuntu.md` y los demás ADR aplicables.

Objetivo: genera un runbook productivo exacto, concreto y listo para copiar/pegar para que yo, el propietario, actualice `https://app.flowerflow.com.mx/` con el `RELEASE_SHA` aprobado. Tu tarea es exclusivamente redactar los pasos y comandos para descargar el SHA en el checkout Git existente, activar mantenimiento, instalar Composer, limpiar cachés generadas, instalar/compilar Yarn/Vite, ejecutar migraciones aditivas, recompilar cachés, reiniciar el worker Flower Flow y salir de mantenimiento. No te conectes al servidor, no ejecutes comandos remotos, no uses AWS/SSM/SSH, no despliegues, no hagas stage, commit ni push y no afirmes resultados productivos.

El propietario confirma que los respaldos necesarios de base y archivos ya fueron realizados. No incluyas comandos de respaldo, restore drill, inventario, auditoría, smoke, consultas SQL, conteos, validaciones ni verificaciones previas/posteriores. Incluye el respaldo únicamente como precondición declarada por el propietario, sin volver a ejecutarlo. Enfoca la respuesta en instalación y compilación.

Contexto obligatorio:
- la plataforma ya está publicada y contiene más de 50 propuestas reales; cuentas, propuestas, borradores, archivos privados, folios, snapshots, revisiones y aceptaciones son datos que deben preservarse;
- la rama de origen es `codex/submission-deadline-extension`; el `RELEASE_SHA` final debe contener como ancestro `32adee0121ea20c557d4a4583680f8a5a62e146d` e incluir los cambios locales aprobados posteriores, incluida la vista 503;
- el runbook debe usar un marcador único y visible `<RELEASE_SHA_APROBADO>` porque el SHA definitivo será el commit publicado por el propietario; no sustituyas ese marcador por el HEAD local si todavía hay cambios sin commit;
- URL productiva: `https://app.flowerflow.com.mx/`;
- topología productiva real confirmada por el propietario: `/var/www/flowerflow` es el checkout Git directamente vinculado al repositorio de GitHub; no existen `/var/www/flowerflow/releases`, `/var/www/flowerflow/current` ni `/var/www/flowerflow/shared`;
- el propietario informa que el VirtualHost `app.sguniformes.com.mx` apunta a `/var/www/flowerflow`; registra ese dato sin cambiar, diagnosticar ni reconfigurar Apache, DNS, TLS o el host canónico público `app.flowerflow.com.mx`;
- remoto Git ya configurado en el checkout: `origin`; repositorio de referencia `https://github.com/ccortesg/flowerflow.git`;
- ejecutar Composer, Yarn y Artisan como el usuario de despliegue/aplicación, nunca como root;
- usar los locks existentes: `composer install`, nunca `composer update`; `scripts/build_frontend_production.sh`, nunca `yarn upgrade`, `npm update` ni edición manual de `public/build`;
- no ejecutar `migrate:fresh`, `migrate:refresh`, `migrate:reset`, `db:wipe`, `db:seed`, rollback de datos ni ningún comando destructivo. La única mutación de esquema permitida en el runbook es `php artisan migrate --force`;
- no ejecutar `git clean`, `git reset --hard`, `rm -rf` ni globs amplios; no borrar o sustituir `.env`, `storage`, base de datos, archivos privados, sesiones, logs o propuestas;
- resultados permanecen apagados y no se implementan jueces, evaluación, ganadores, ARCO completo o módulos futuros.

Decisiones vigentes del propietario que el runbook debe respetar:
1. Son cuatro categorías y máximo cuatro propuestas, una por categoría; accesibilidad permanece mencionada en Movilidad con Flow y Hermosillo sin Barreras, sin recategorización.
2. Las cuentas que aceptaron v1.0 se tratan operativamente como aceptantes de v1.1. No se fuerza reaceptación, no se bloquean cuentas/propuestas y no se modifica, duplica ni fabrica ninguna fila histórica de `legal_acceptances`. Las nuevas aceptaciones continúan registrando v1.1.
3. La Mecánica v1.0 que se conserva es `public/documentos/2026/01_Mecanica_Convocatoria_Hermosillo_Florece_2026.pdf`, SHA-256 físico `3bcf31ece0bd1bdbf4392908a27ec3812495dfa588091e9bbce9f7c4ea1e5cb3`. El hash histórico `42bd5e…` permanece documentado; no se sustituye el archivo ni se reescribe evidencia.
4. P2 503/CSP está resuelto y debe formar parte del `RELEASE_SHA`: `resources/views/errors/503.blade.php` usa layout/recursos Vite normales, contenido accesible y de marca, cero estilos inline y es compatible con cierre por middleware y `php artisan down --render="errors::503"`.
5. Documentos v1.1 vigentes e inmutables:
   - Mecánica: `11c399ca84735d7dbcb17174e192582c93589afa5100c0250753ca15def4db36`;
   - Términos: `4e3e6c272f9459b934004168bfccc26d6759a4dbce6c804c03afbb86cda6b144`;
   - Aviso de Privacidad: `041ae9704f80a0108ee69bb39b8646ee8098134573a730f801e4057642ae2da1`.

Genera una única secuencia Bash con `set -euo pipefail` y variables explícitas al inicio. Debe:
1. fijar únicamente `APP_DIR=/var/www/flowerflow`, `RELEASE_SHA='<RELEASE_SHA_APROBADO>'` y `SUPERVISOR_PROGRAM='<PROGRAMA_SUPERVISOR_FLOWERFLOW>'`; no usar `$HOME`, `~`, rutas de releases ni directorios temporales de despliegue;
2. entrar a `APP_DIR` y descargar referencias sin cambiar todavía el código mediante `git fetch --prune origin`;
3. activar mantenimiento desde el código actualmente instalado con `php artisan down --retry=60`; no usar todavía `--render="errors::503"` porque la versión productiva previa puede no contener la vista nueva;
4. cambiar el mismo checkout al SHA exacto mediante `git checkout --detach "$RELEASE_SHA"`; no usar `git pull`, `git reset --hard`, `git clean`, clone nuevo ni symlinks;
5. ejecutar en `/var/www/flowerflow` `composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader --no-progress`, `php artisan optimize:clear` y `scripts/build_frontend_production.sh`;
6. después de instalar y compilar el SHA nuevo, reemplazar la página temporal de mantenimiento con `APP_URL=https://app.flowerflow.com.mx php artisan down --render="errors::503" --retry=60`;
7. ejecutar exclusivamente `php artisan migrate --force`; no incluir seeders;
8. ejecutar `php artisan optimize`, `php artisan queue:restart` y reiniciar únicamente `$SUPERVISOR_PROGRAM`; no reiniciar Apache, PHP-FPM, MySQL, Supervisor completo ni servicios de otras aplicaciones;
9. finalizar con `php artisan up`;
10. no crear, mover, enlazar ni eliminar carpetas de releases, `current`, `shared`, `.env` o `storage`.

No presentes alternativas ni la topología futura de releases. Si el nombre de Supervisor no está documentado de manera inequívoca, conserva el marcador indicado y no inventes uno. No inventes usuarios, hostnames, llaves, credenciales, secretos ni nombres de servicios. No incluyas comandos de comprobación. Entrega únicamente:
1. una nota breve de alcance indicando que tú no ejecutarás el despliegue y que el backup fue confirmado por el propietario;
2. la lista de marcadores que debo sustituir;
3. un solo bloque Bash continuo, comentado por etapas, en el orden exacto de ejecución;
4. tres advertencias finales: no usar seeders/comandos destructivos, no alterar `legal_acceptances` históricas y no ejecutar `git clean/reset --hard` ni borrar `.env`/`storage`.
```

## Prompt anterior inmediato — sustituido por la instrucción de sólo generar comandos

```text
Trabaja con el repositorio `/home/ccortesg/workspace/flowerflow` y con la plataforma productiva ya publicada en `https://app.flowerflow.com.mx/`. Lee primero `AGENTS.md`, `.agent/PLANS.md`, `.agent/execplans/flowerflow-legal-v1-1-local-release-candidate.md`, `docs/16-project-status-by-module-and-role-2026-08-17.md`, `docs/17-legal-v1-1-reconciliation-2026-08-17.md`, `docs/07-deployment-aws-ec2.md`, `docs/15-risk-reduction-release-runbook.md`, `docs/adr/0002-aws-ec2-ubuntu.md` y los demás ADR aplicables.

Objetivo: preparar y ejecutar, dentro de una ventana de mantenimiento expresamente confirmada en esta tarea, el despliegue productivo de los cambios ya aprobados de Flower Flow: fecha de cierre del 23 de agosto de 2026 a las 23:59:59 `America/Hermosillo`, cuatro categorías, máximo cuatro propuestas, documentos jurídicos v1.1, vínculos/identidad legal, versionado de aceptaciones nuevas, correcciones de landing/panel y migraciones pendientes del SHA aprobado. La plataforma ya está operando y contiene más de 50 propuestas reales; preservarlas, junto con cuentas, archivos privados, folios, snapshots, revisiones y aceptaciones, es un invariante absoluto.

No implementes jueces, evaluación, ganadores, resultados, ARCO completo, módulos futuros ni cambios de negocio adicionales. No modifiques `administratec` ni ninguna de las otras aplicaciones que comparten la EC2. No hagas cambios globales de Ubuntu, Apache, PHP, MySQL, Node o Composer.

Decisiones del propietario que sustituyen los pendientes anteriores:
1. Accesibilidad: se acepta definitivamente que la Mecánica v1.1 mencione accesibilidad tanto en Movilidad con Flow como en Hermosillo sin Barreras. Conserva código, textos, categorías y propuestas exactamente como están. Marca R62/Q-LEGAL-001 como `RESOLVED / OWNER ACCEPTED`; no propongas ni implementes recategorización.
2. Reaceptación: quienes aceptaron v1.0 deben tratarse operativamente como si v1.1 estuviera aceptada, porque los cambios fueron comunicados a todas las personas y se limitaron, según decisión del propietario, a fecha y nueva categoría. No fuerces reaceptación al login ni bloquees cuentas/propuestas históricas. No reescribas, dupliques ni falsifiques filas de `legal_acceptances`: las aceptaciones v1.0 siguen siendo evidencia histórica real y la equivalencia es una política autorizada de continuidad. Nuevas cuentas, cambios de consentimiento y nuevos envíos deben continuar registrando la versión v1.1 realmente mostrada.
3. Mecánica v1.0: el propietario confirma que la versión que debe conservarse permanece en `public/documentos/2026/01_Mecanica_Convocatoria_Hermosillo_Florece_2026.pdf`, SHA-256 físico actual `3bcf31ece0bd1bdbf4392908a27ec3812495dfa588091e9bbce9f7c4ea1e5cb3`. No la renombres, sustituyas, borres ni recuperes otra copia para este despliegue. Conserva en documentación el hecho histórico de que el registro original guardó `42bd5e…`; trátalo como discrepancia histórica conocida y aceptada por el propietario, no como bloqueo productivo, y nunca cambies aceptaciones previas para ocultarla.
4. Error 503/CSP P2: es una deuda visual no bloqueante. La página 503 predeterminada usa estilos inline que generan dos reportes CSP cuando la política está en Report-Only; no altera la regla de cierre, permisos, propuestas ni datos. No incluyas una página 503 nueva en este despliegue. Mantén CSP estricta en Report-Only; la corrección visual queda para un cambio posterior aprobado.

Baseline que debes demostrar antes de preparar el release:
- el candidato funcional aprobado está contenido en la rama `codex/submission-deadline-extension`; el SHA observado y sincronizado antes de esta actualización documental fue `32adee0121ea20c557d4a4583680f8a5a62e146d`;
- si existe un commit posterior, debe contener `32adee0121ea20c557d4a4583680f8a5a62e146d` como ancestro, estar sincronizado con `origin` y ser aprobado explícitamente como `RELEASE_SHA` inmutable;
- Mecánica v1.1: `11c399ca84735d7dbcb17174e192582c93589afa5100c0250753ca15def4db36`;
- Términos v1.1: `4e3e6c272f9459b934004168bfccc26d6759a4dbce6c804c03afbb86cda6b144`;
- Aviso v1.1: `041ae9704f80a0108ee69bb39b8646ee8098134573a730f801e4057642ae2da1`;
- gates locales: 107 pruebas/1,031 aserciones, 12 migraciones, Pint/Composer/build verdes y un advisory bajo conocido de Quill 2.0.3;
- la evidencia pública previa al despliegue muestra cuatro categorías/máximo cuatro, pero todavía textos de cierre al 15 de agosto; el despliegue debe corregirlos al 23 de agosto.

Reglas de seguridad obligatorias por existir datos reales:
- aunque se haya solicitado una secuencia sin verificaciones ni respaldos, no ejecutes mutaciones productivas sin preflight read-only, backup consistente, punto de rollback y confirmación de ventana: `AGENTS.md`, `.agent/PLANS.md` y el ADR 0002 los hacen gates obligatorios;
- no uses `migrate:fresh`, `db:wipe`, `schema:drop`, `db:seed`, `migrate:reset`, `migrate:refresh`, rollback masivo ni comandos equivalentes;
- no borres ni limpies la base, `shared/storage`, `.env`, archivos privados, exports, logs activos, sesiones, propuestas o releases de rollback;
- no muestres secretos, credenciales, PII, nombres de participantes, títulos de propuestas ni contenido de archivos;
- no uses `git pull` sobre el webroot vivo ni sobrescribas el checkout actual. Prepara un release inmutable nuevo y cambia el symlink de forma atómica;
- ejecuta Composer, Node/Yarn y Artisan con el usuario de despliegue/aplicación, nunca como root; usa `sudo` sólo para la operación mínima ya autorizada de servicio/symlink si la topología real lo requiere;
- resultados deben permanecer apagados. No abras ni cierres recepción por una variable temporal distinta de la fecha/configuración aprobada.

Fase 1 — preflight productivo read-only y resolución de valores exactos:
1. confirma localmente `pwd`, Git toplevel, rama, `RELEASE_SHA`, ancestro `32adee…`, status y sincronización con `origin`;
2. accede mediante el mecanismo autorizado existente, preferentemente SSM o el SSH individual ya configurado; no inventes hostname, usuario, llave o perfil AWS;
3. identifica sin imprimir secretos: usuario efectivo de despliegue/PHP, checkout o symlink activo, `DocumentRoot`, release actual, `.env` compartido, storage compartido, PHP/SAPI, Node/NVM, Composer, base configurada por nombre solamente, worker Supervisor y cron del scheduler;
4. confirma si la topología real es `/var/www/flowerflow/current -> /var/www/flowerflow/releases/<release>` o la variante histórica `/var/www/flowerflow-current -> /var/www/flowerflow-releases/<release>`. No mezcles ambas. Resuelve y registra valores absolutos para `FLOWERFLOW_RELEASES_DIR`, `FLOWERFLOW_CURRENT_LINK`, `FLOWERFLOW_SHARED_ENV`, `FLOWERFLOW_SHARED_STORAGE`, `FLOWERFLOW_PREVIOUS_RELEASE` y `FLOWERFLOW_RELEASE_DIR`;
5. registra conteos agregados, no PII: propuestas totales, borradores/enviadas, usuarios, archivos y aceptaciones; estos conteos deben permanecer iguales salvo filas creadas legítimamente durante la ventana;
6. ejecuta el mecanismo productivo de backup ya aprobado para MySQL y storage privado, registra ubicación/fecha/resultado sin mostrar credenciales y confirma que el punto de restauración corresponde inmediatamente antes del deploy. Si no existe un mecanismo aprobado o falla, detente;
7. crea/actualiza un ExecPlan productivo con comandos resueltos, responsables, ventana, rollback y registro vivo. Presenta el plan y solicita una confirmación final inmediatamente antes de `artisan down`; no consideres la solicitud de comandos como autorización para saltar esa confirmación.

Fase 2 — preparación del release fuera del webroot activo:
1. define variables con rutas absolutas ya verificadas; no uses `$HOME`, `~`, globs amplios ni variables sin resolver;
2. crea un directorio nuevo con timestamp y SHA bajo el directorio real de releases;
3. descarga el código sin modificar el release activo:
   - `git clone --no-checkout https://github.com/ccortesg/flowerflow.git "$FLOWERFLOW_RELEASE_DIR"`;
   - `git -C "$FLOWERFLOW_RELEASE_DIR" fetch --prune origin`;
   - `git -C "$FLOWERFLOW_RELEASE_DIR" checkout --detach "$RELEASE_SHA"`;
4. enlaza únicamente el `.env` y storage compartidos ya existentes. No generes `APP_KEY`, no copies `.env.example` y no crees una base nueva. Conserva el esqueleto `storage` del release mediante movimiento explícito dentro del directorio nuevo antes de crear el symlink; no uses `rm -rf` sobre rutas compartidas;
5. instala PHP en el release nuevo, sin paquetes de desarrollo ni actualizaciones de lock: `composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader --no-progress`;
6. ejecuta `php artisan package:discover --ansi` si no quedó ejecutado por Composer;
7. limpia únicamente cachés generadas dentro del release nuevo con `php artisan optimize:clear`;
8. usa NVM del usuario de despliegue con Node 22.23.1 y ejecuta `scripts/build_frontend_production.sh`; este script usa Corepack/Yarn 1.22.22, `yarn install --frozen-lockfile` y `vite build`;
9. no instales dependencias globales, no ejecutes `composer update`, `yarn upgrade`, `npm update`, `apt`, cambios de PHP/Apache ni edites `public/build` manualmente;
10. compila cachés productivas en el release nuevo con `php artisan optimize` sólo después de enlazar el `.env` real.

Fase 3 — ventana de mantenimiento y migración preservando datos:
1. después de la confirmación final del propietario, ejecuta `php artisan down --retry=60` desde el release actualmente activo;
2. registra nuevamente los conteos agregados y detén la ventana si difieren sin explicación;
3. desde el release nuevo ejecuta exclusivamente `php artisan migrate --force`. No ejecutes seeders en producción: las migraciones pendientes incorporan la categoría, fecha, exportación/permisos y documentos v1.1 de forma controlada;
4. si cualquier migración falla o encuentra un estado inesperado, no fuerces datos ni edites la tabla `migrations`; conserva mantenimiento, registra el error redactado y ejecuta el rollback operativo definido antes de continuar;
5. no modifiques ni hagas backfill de `legal_acceptances` por la equivalencia v1.0 -> v1.1. Verifica sólo que nuevas aceptaciones sigan apuntando a documentos activos v1.1 y que las históricas permanezcan intactas.

Fase 4 — switch atómico y procesos:
1. crea un symlink temporal junto a `FLOWERFLOW_CURRENT_LINK` apuntando a `FLOWERFLOW_RELEASE_DIR` y sustitúyelo atómicamente con `mv -Tf`; no elimines el release anterior;
2. si la producción todavía usa un checkout vivo sin symlink, no improvises la conversión: detente y presenta el cambio exacto de `DocumentRoot`/symlink para aprobación separada;
3. desde el nuevo current ejecuta `php artisan optimize` y `php artisan queue:restart`;
4. reinicia únicamente el programa Supervisor exacto de Flower Flow si el inventario demuestra que es necesario. No uses comodines ni reinicies Supervisor, Apache, PHP-FPM o servicios de otras aplicaciones;
5. el cron/scheduler debe continuar apuntando al symlink `current`; no dupliques entradas;
6. ejecuta `php artisan up` desde el nuevo release;
7. no recargues Apache si el `DocumentRoot` ya apunta al symlink actual. Si realmente cambió configuración Apache, exige `apache2ctl configtest` y usa recarga graceful, nunca restart.

Fase 5 — smoke y criterios de aceptación:
- `/`, `/documentos`, `/register`, `/login`, `/panel/login` y `/up` responden sin 500;
- landing y panel muestran 23 de agosto de 2026, cuatro categorías y máximo cuatro propuestas;
- los tres vínculos vigentes descargan v1.1 y sus hashes coinciden;
- una cuenta histórica v1.0 puede entrar y continuar sin reaceptación forzada;
- una nueva aceptación o envío posterior registra v1.1;
- los conteos de más de 50 propuestas, usuarios, archivos, folios, snapshots, revisiones y aceptaciones históricas no disminuyen;
- participante conserva acceso a propuestas propias; reviewer/admin conservan panel, archivos privados, admisibilidad, paginación y exportación;
- resultados siguen apagados; no aparecen enlaces vigentes a v1.0 salvo historia explícita;
- assets no devuelven 404, logs no muestran excepciones nuevas y las demás aplicaciones de la EC2 no sufren cambios.

Rollback:
- ante 500, pérdida de assets, fallo de permisos, migración incompleta, degradación o impacto cruzado, vuelve atómicamente `FLOWERFLOW_CURRENT_LINK` a `FLOWERFLOW_PREVIOUS_RELEASE`, limpia/compila sólo cachés de ese release, reinicia únicamente el worker Flower Flow y ejecuta `artisan up`;
- no reviertas automáticamente migraciones ni datos: el código previo debe evaluarse contra el esquema aditivo. Los `down()` de fecha/documentos no se ejecutan con datos reales sin una autorización separada porque podrían reactivar reglas antiguas;
- conserva el release fallido, logs y backup para diagnóstico; no borres releases ni limpies storage durante la ventana.

Documentación obligatoria en la misma tarea:
- actualiza `docs/09-risk-register.md`, `docs/10-open-questions.md`, `docs/legal-change-log.md`, `docs/16-project-status-by-module-and-role-2026-08-17.md`, `docs/17-legal-v1-1-reconciliation-2026-08-17.md`, handoff y ExecPlan para reflejar las tres decisiones del propietario;
- registra el P2 de la página 503 como deuda visual aceptada/no bloqueante;
- distingue con claridad SHA local, SHA publicado, migraciones aplicadas, flags reales y evidencia productiva.

Entrega final:
1. `RELEASE_SHA`, release anterior/nuevo y topología utilizada;
2. comandos exactos ejecutados, con secretos/PII redactados;
3. backup/punto de recuperación y rollback preparado;
4. migraciones ejecutadas y resultado;
5. conteos agregados antes/después;
6. smoke por rol y vínculos/hashes v1.1;
7. estado de workers/scheduler y aplicaciones vecinas;
8. archivos/documentación actualizados;
9. decisión final `GO`, `ROLLED BACK` o `BLOCKED`, con riesgos residuales.
```

## Prompt histórico anterior — sustituido por las decisiones del 2026-08-18

```text
Trabaja en `/home/ccortesg/workspace/flowerflow` y lee primero `AGENTS.md`, `.agent/PLANS.md`, `.agent/execplans/flowerflow-legal-v1-1-local-release-candidate.md`, `docs/16-project-status-by-module-and-role-2026-08-17.md`, `docs/17-legal-v1-1-reconciliation-2026-08-17.md`, `docs/legal-change-log.md`, `docs/10-open-questions.md` y los ADR aplicables.

Objetivo: congelar y revisar el release candidate local ya validado, preparar un expediente de decisión preciso para los tres pendientes jurídicos/evidenciales restantes y dejar lista —sin ejecutarla— la siguiente puerta de release. No implementes jueces, evaluación, ganadores, resultados, ARCO completo, reaceptación, cambios de categorías, despliegue ni cambios en AWS/producción.

Baseline que debes verificar, no asumir:
- rama `codex/submission-deadline-extension`, SHA base `e2f4345dd7ec8c2e0b8285a06b5f560e3c3118d3` y árbol con cambios preexistentes no publicados;
- Mecánica v1.1 definitiva: `public/documentos/2026/01_Mecanica_Convocatoria_Hermosillo_Florece_2026_v1.1.pdf`, 866607 bytes, 5 páginas, SHA-256 `11c399ca84735d7dbcb17174e192582c93589afa5100c0250753ca15def4db36`;
- Términos v1.1 SHA-256 `4e3e6c272f9459b934004168bfccc26d6759a4dbce6c804c03afbb86cda6b144`;
- Aviso v1.1 SHA-256 `041ae9704f80a0108ee69bb39b8646ee8098134573a730f801e4057642ae2da1`;
- gates vigentes: 107 pruebas/1,031 aserciones, 12 migraciones de test, Pint/Composer/build verdes y un advisory bajo conocido de Quill 2.0.3.

Antes de cualquier cambio:
1. confirma `pwd`, Git toplevel, rama, SHA, status y diff, preservando todo cambio preexistente;
2. recalcula los hashes de los seis PDF y verifica que los tres v1.1 sean archivos regulares, válidos, no symlinks y estén dentro del repositorio;
3. si el PDF definitivo de Mecánica o cualquiera de sus metadatos difiere del baseline anterior, detente: registra el nuevo artefacto como otra sustitución pre-release y no continúes con un freeze obsoleto;
4. no leas el contenido de `:Zone.Identifier`, no lo enlaces y confirma que sigue excluido;
5. si debes consultar datos, usa únicamente MySQL `flowerflow_testing` con `flowerflow_testing_user` y demuestra `APP_ENV=testing`, driver MySQL, host loopback, base configurada y `SELECT DATABASE()`; no uses `flowerflow` ni producción;
6. crea o actualiza un ExecPlan específico de cierre de decisiones. No hagas stage, commit, push ni despliegue.

Trabajo autorizado:

A. Congelamiento e integridad de evidencia
- confirma que config, migración, seeder, pruebas, vistas y documentación apuntan exactamente a los tres hashes v1.1 definitivos y que sólo hay una versión activa determinística por tipo;
- conserva v1.0/v1.1 y todas las aceptaciones sin mutarlas;
- demuestra desde el historial Git, en una ruta temporal ignorada y sin publicar/copiar sobre el árbol, si el binario original de Mecánica v1.0 puede recuperarse con el SHA-256 histórico `42bd5ea13e491dc64a6520f0e26d9663e8e8f973b35a3febf226999118685aa2`;
- no restaures, renombres, publiques ni reemplaces ningún PDF. Prepara una propuesta de publicación histórica inmutable con ruta/nombre nuevos, impacto en vínculos y aceptaciones, verificación y rollback, para aprobación del propietario.

B. Expediente de decisiones jurídicas, sin implementar por inferencia
- Accesibilidad: cita Mecánica v1.1 p. 2 y presenta alternativas claras para la superposición entre Movilidad con Flow y Hermosillo sin Barreras; incluye impacto en clasificación, UI, datos existentes, comunicación y pruebas. Recomienda una opción, pero conserva `POR_CONFIRMAR` hasta aprobación.
- Reaceptación: presenta alternativas para cuentas con v1.0 —sólo nuevas cuentas/envíos, siguiente login o bloqueo previo al siguiente envío— con fecha de efectividad, audiencia, wording, evidencia, UX, correo, soporte, auditoría y rollback. Conserva `PROPOSAL_NEEDED`; no crees backfill ni modifiques `legal_acceptances`.
- Evidencia v1.0: documenta causa, alcance y opciones para publicar el original `42bd5e…` junto al binario actual `3bcf31…` sin confundir ni reescribir historia.
- Para cada decisión entrega: pregunta exacta, hechos verificados, alternativas, recomendación, impacto, archivos que cambiarían, migración sí/no, pruebas, riesgos y criterio de aceptación.

C. Puerta externa preparada, no ejecutada
- actualiza sólo documentación/ExecPlan para enumerar evidencias aún necesarias: aprobación del owner, licencia Pixinvent, staging, SMTP/buzón, storage, worker/scheduler, backup y restore medido, RPO/RTO, capacidad, monitoreo, plan de migración y rollback;
- no accedas a EC2, AWS, DNS, TLS, SMTP real, bases externas ni producción. Si una comprobación externa fuera necesaria, conviértela en comando read-only propuesto y requisito de autorización para una tarea posterior;
- mantén resultados y módulos futuros fuera de alcance.

Validación final:
- búsqueda global de hashes/rutas/referencias v1.0-v1.1 y textos obsoletos de tres categorías/tres propuestas, distinguiendo historia de referencias vigentes;
- `LegalDocumentsV11Test`, suite completa, Pint, Composer validate/platform/audit, Yarn audit, build, JSON, rutas, schedule, migrate:status de testing, lint, `git diff --check` y revisión de secretos/PII;
- no declares producción lista por una suite local verde.

Entrega:
1. decisión de freeze `GO/NO-GO` local con hash exacto de los tres v1.1;
2. expediente de las tres decisiones con recomendación y texto exacto que el propietario debe aprobar;
3. evidencia read-only de recuperabilidad del v1.0 original, sin publicarlo;
4. lista de archivos modificados y justificación;
5. comandos/resultados y estado final de Git/base aislada;
6. riesgos `PENDING`, `POR_CONFIRMAR` y `PROPOSAL_NEEDED` restantes;
7. checklist de autorización para un preflight externo posterior;
8. prompt exacto posterior, condicionado a las decisiones que el propietario apruebe.
```

## Regla de actualización

Actualizar este documento cuando cambie una ruta, permiso, rol, módulo, decisión de negocio, migración aplicada al ambiente objetivo, evidencia UAT o estado productivo. Conservar siempre separados código disponible, runtime activado y despliegue comprobado.
