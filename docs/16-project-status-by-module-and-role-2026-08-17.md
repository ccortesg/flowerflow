# Diagnóstico vigente por módulo y rol — 2026-08-17

**Corte de evidencia:** 2026-08-17 22:58 MST (`America/Hermosillo`)

**Checkout:** `/home/ccortesg/workspace/flowerflow`

**Rama/SHA auditados:** `codex/submission-deadline-extension` / `e2f4345`
**Naturaleza:** auditoría local de código, documentación y configuración; no demuestra que este SHA esté desplegado.

Este documento es la fuente vigente para responder “qué existe hoy”. Los documentos con fechas anteriores conservan historia, decisiones y evidencia de sus milestones, pero no deben usarse solos para inferir el estado actual.

## Resultado ejecutivo

| Lectura | Avance | Interpretación correcta |
|---|---:|---|
| Producto maestro completo | **58 %** | Incluye sitio, identidad, elegibilidad, propuestas, backoffice, jueces, evaluación, ganadores, resultados, privacidad, operación y producción. La vertical recepción/admisibilidad ya cerró UAT; los módulos 7 y 8 siguen sin existir. |
| Alcance local expresamente aprobado | **96 %** | Fase 01, Fase 02A, cuarta categoría, exportación, plazo y sincronización jurídica v1.1 están implementadas, automatizadas y recorridas por rol. Restan decisiones de reaceptación, alcance temático de accesibilidad y evidencia histórica v1.0. |
| Runtime aislado del release candidate | **96 %** | `scripts/serve_local_testing.sh` demuestra ambiente/base/cuenta, exige catálogos, habilita sólo flags UAT y mantiene resultados apagados. `flowerflow_testing` terminó 12/12, sembrada y sin cuentas sintéticas. |
| Disponibilidad del runtime local primario | **42 %** | Se preservó sin tocarlo: el baseline previo tenía registro/recepción/admisibilidad apagados, cuatro migraciones funcionales pendientes y límite local de tres; el árbol agrega además la migración v1.1. No es el runtime autoridad del RC. |
| Preparación de la rama actual para producción | **34 %** | Hay código, documentos v1.1, suite y UAT local, pero no existe evidencia de staging, migración productiva, worker/scheduler, SMTP, backup/restore, capacidad, monitoreo ni autorización de despliegue. |

La cifra **58 %** no contradice que el alcance local aprobado esté en **96 %**: la primera mide el plan maestro completo. El producto ya tiene una vertical de recepción y admisibilidad validada; evaluación, ganadores, resultados y operación productiva siguen fuera o ausentes.

## Evidencia verificada en este corte

- Baseline Git confirmado en rama `codex/submission-deadline-extension` y SHA `e2f4345…`; el árbol ya estaba sucio por documentación/PDF proporcionados y se preservó sin stage, commit, push ni sobrescritura.
- Stack efectivo: Laravel 12.64.0, PHP 8.3.33, Node 22.23.1, Yarn 1.22.22 y MySQL client 8.0.46.
- 66 rutas totales registradas con flags actuales; 41 rutas propias al excluir rutas de paquetes.
- 104 archivos PHP bajo `app/`, 12 migraciones y 24 clases de prueba.
- `flowerflow_testing`: 12/12 migraciones aplicadas; seis versiones jurídicas, una v1.1 activa por tipo, cuatro categorías y cero usuarios/aceptaciones al cierre.
- Suite completa vigente: **109 pruebas y 1,049 aserciones**, sin fallos.
- Pint, Composer validate, requisitos de plataforma, Composer audit, JSON de menús y build Vite: verdes.
- Build: 784 módulos y tres assets; catálogo de 97 iconos verificado.
- Yarn: un advisory **bajo** en Quill 2.0.3 (`GHSA-v3m3-f69x-jf25`), sin versión corregida publicada. La sanitización servidor reduce el vector, pero no elimina la deuda de dependencia.
- UAT Firefox por rol en 1440/768/360: enlaces/documentos, alta y aceptación v1.1, perfil, cuatro propuestas/rechazo de quinta, archivo privado/envío/folio, aclaración/residencia/admisión, panel/paginación, 2FA, XLSX/expiración/purga, cierre, 403/404/IDOR, teclado, foco, zoom/reflow y consola.
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
| 2. Identidad, cuenta y acceso | 10 % | 64 % | 6.40 |
| 3. Perfil, residencia y admisibilidad | 12 % | 88 % | 10.56 |
| 4. Proyecto, equipo, archivos y envío | 16 % | 73 % | 11.68 |
| 5. Backoffice y revisión | 12 % | 64 % | 7.68 |
| 6. Legal, contenido y configuración | 8 % | 84 % | 6.72 |
| 7. Jueces, asignación y evaluación | 12 % | 0 % | 0.00 |
| 8. Ganadores y resultados públicos | 6 % | 0 % | 0.00 |
| 9. Comunicaciones transaccionales | 5 % | 60 % | 3.00 |
| 10. Reportes, auditoría y privacidad | 5 % | 42 % | 2.10 |
| 11. QA, infraestructura y operación | 6 % | 45 % | 2.70 |
| **Total** | **100 %** |  | **58.44 % → 58 %** |

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

### 2. Identidad, cuenta y acceso — 64 %

| Funcionalidad principal | Avance | Estado/evidencia | Falta para 100 % |
|---|---:|---|---|
| Registro, login, logout y verificación | 98 % | Fortify, vistas propias, perfil transaccional, pruebas y UAT real con verificación controlada y correo array. | SMTP/entregabilidad externa y activación productiva. |
| Reset y política de contraseña | 90 % | Regla backend única, vistas accesibles, notificación cifrada en cola y no 500 al fallar dispatch. | Prueba de entregabilidad real y operación de `failed_jobs`. |
| RBAC/Policies actuales | 85 % | Roles sembrados `participant`, `reviewer`, `admin`; permisos granulares y Policies de recursos. | Adoptar nombres/roles finales del plan maestro y matriz completa por ruta. |
| 2FA privilegiado | 70 % | Alta, confirmación, desafío TOTP y recuperación están automatizados y se recorrieron en navegador. | Hoy es **opcional**; falta exigirlo y definir recuperación operativa. |
| Confirmación de contraseña crítica | 40 % | Se exige en creación/descarga de exportaciones. | Aplicarla según riesgo a resoluciones, gestión RBAC/configuración y acciones futuras. |
| Suspensión y revocación de sesiones | 0 % | No existe estado de suspensión ni flujo de revocación administrativa. | Modelo, middleware, acción, auditoría, UI y pruebas. |
| Invitaciones de integrante/juez | 0 % | No hay entidad, token ni endpoints. | Contrato de negocio, expiración, un solo uso, aceptación y pruebas. |

Riesgo de evolución: las rutas participantes exigen `auth` y `verified`, no el rol `participant`. Un futuro `judge` sin permisos de panel queda fuera del backoffice, pero podría caer en el shell participante; antes de crear ese rol debe definirse si los roles son excluyentes o acumulables y añadir el gate correspondiente.

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
| Aceptaciones | 94 % | Registro/envío guardan propósitos, FK, versión/hash real; pruebas y alta browser v1.1. | Decidir reaceptación de cuentas v1.0. |
| Configuración tipada | 92 % | Flags, límites, fechas, jurídicos, colas, seguridad y hosts centralizados; runtime UAT valida drift/catálogos. | Validación equivalente en el proceso de release externo. |
| Categorías por datos | 92 % | Cuatro activas, ordenadas, sembradas y respaldadas por v1.1. | CRUD/admin sólo si se aprueba. |
| Coherencia jurídica vigente | 75 % | Cantidades, fecha, responsable y vínculos alineados; matriz página/sección completa. | Confirmar superposición de accesibilidad y política de reaceptación. |
| Licencia Materialize/Pixinvent | 0 % | No hay evidencia comercial en repositorio. | Confirmación documental antes de release. |

### 7. Jueces, asignación y evaluación — 0 %

No existen rol sembrado, perfiles, invitaciones, asignaciones, conflictos, rúbricas, criterios, evaluaciones, puntuaciones, rutas, Policies, vistas ni pruebas funcionales. Sólo hay diseño documental y pruebas negativas que simulan un rol `judge` sin permisos para demostrar que no accede a admisibilidad.

Pendientes bloqueantes antes de programar: número de jueces, evaluación ciega, reglas de asignación, recusación/conflicto, rúbrica versionada, rangos/pesos, cierre, reapertura, desempate y operación. Deben aprobarse en una propuesta/ExecPlan separado; no se deben inferir.

### 8. Ganadores y resultados públicos — 0 %

Existe únicamente el flag `results=false` y copy informativo. No hay decisiones de ganador, cálculo/consolidación, permiso de declarar/publicar, preview, ruta pública, consentimiento de campos ni archivo 2026.

Pendientes: empate, categoría desierta, premio exacto, datos publicables, consentimiento, doble confirmación, auditoría, reversión y comunicación. La declaración debe permanecer separada de cualquier puntuación.

### 9. Comunicaciones transaccionales — 60 %

| Funcionalidad principal | Avance | Estado/evidencia | Falta para 100 % |
|---|---:|---|---|
| Verificación/reset | 90 % | HTML/texto en español, cola cifrada, retries y respuesta resiliente. | SMTP/entregabilidad y operación de fallos real. |
| Acuse de propuesta | 90 % | Folio/categoría, sin adjuntos, reenvío limitado y post-commit. | Métrica de entrega e idempotencia operativa por evento. |
| Admisibilidad | 90 % | Cinco variantes, sin contenido sensible, sin rollback ante falla y UAT con correo array. | Worker/SMTP, rebotes y buzón staging. |
| Invitaciones/asignaciones/evaluación/resultados | 0 % | No existen porque los módulos tampoco. | Implementar después de aprobar Fase 02B/cierre. |
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
| Pruebas automatizadas locales | 98 % | 109/1,049, MySQL protegido, legales v1.1, 503/CSP, archivos, permisos, fechas y concurrencia. | Cobertura de módulos futuros y CI permanente. |
| QA navegador | 95 % | UAT Firefox actual por cuatro roles, tres viewports, teclado/foco/zoom, IDOR, 2FA, XLSX y cierre. | Matriz multi-browser permanente y UAT firmada por owner. |
| Dependencias/build | 90 % | Locks, build reproducible, Composer limpio. | Resolver Quill bajo y automatizar el gate en CI. |
| Staging/producción actual | 10 % | Runbooks y una release histórica pública documentada. | Inventario autorizado, staging, release del SHA actual y smoke por rol. |
| Backup/restore/rollback | 15 % | Estrategia escrita; no hay restore drill vigente. | Backup verificado, restauración medida y rollback ensayado. |
| Worker/scheduler/SMTP/monitoreo | 10 % | Configuración y comandos existen. | Servicios reales, alarmas, logs, `failed_jobs`, entregabilidad y capacidad. |

## Estado por rol de usuario

Los únicos roles creados por el seeder actual son `participant`, `reviewer` y `admin`. Los demás son roles del plan maestro, no cuentas operables.

| Rol planificado | Avance funcional | Disponibilidad local actual | Acceso que sí existe | Pendientes para acceso completo |
|---|---:|---:|---|---|
| Visitante | 90 % | 96 % | Landing, cuatro categorías, FAQ, documentos v1.1, login, recuperación y registro UAT. | Resultados fuera de alcance, sitemap/OG y decisiones jurídicas residuales. |
| Participante | 82 % | 96 % | Auth, verificación controlada, perfil, cuatro propuestas, archivo privado, envío/folio, aceptaciones v1.1 y seguimiento de admisibilidad. | Corrección versionada, retiro, colaboración por cuentas y resultados futuros. |
| Revisor de elegibilidad | 90 % | 96 % | Panel, listado/detalle/descargas, aclaración, residencia, resolución y aislamiento de nota interna verificados. | Reapertura/SLA, 2FA obligatoria y password confirmation ampliada. |
| Administrador de convocatoria | 68 % | 92 % | Dashboard, paginación, propuestas, descargas, admisibilidad, 2FA opcional y XLSX completo/expirable. | Gestión de configuración/roles, jueces, resultados, audit UI y operación productiva. |
| Integrante de equipo | 5 % | 0 % | Se guarda como fila dentro del equipo, sin identidad propia. | Invitación, cuenta, aceptación, permisos, acceso y baja. |
| Juez | 0 % | 0 % | Sólo existe como concepto documental y actor negativo de una prueba. | Módulo 7 completo y aislamiento explícito de rutas participantes. |
| Soporte de privacidad | 0 % | 0 % | Ninguno. | Rol/permisos y módulo ARCO aprobado. |
| Auditor | 10 % | 0 % | Existen datos de auditoría, no acceso dedicado. | Rol read-only, UI, scopes, masking y export. |
| Superadministrador | 32 % | 25 % | Comando seguro de admin, RBAC base y 2FA opcional. | Rol diferenciado, gestión de permisos/configuración, 2FA obligatoria, password confirm, break-glass y auditoría reforzada. |

### Matriz efectiva de los tres roles actuales

| Capacidad | participant | reviewer | admin |
|---|---:|---:|---:|
| Ver/editar perfil propio | Sí | Técnicamente sí, aunque `/inicio` redirige al panel | Técnicamente sí, aunque `/inicio` redirige al panel |
| Crear/editar/enviar propuesta | Sí con flags/perfil/plazo; personal organizador se bloquea al finalizar | Bloqueado al finalizar; las rutas previas no tienen middleware de rol | Bloqueado al finalizar; las rutas previas no tienen middleware de rol |
| Ver propuesta propia | Sí | Sí si propia o por `view submissions` | Sí si propia o por `view submissions` |
| Entrar a `/panel` | No | Sí | Sí |
| Ver propuestas de todas las cuentas | No | Sí | Sí |
| Descargar anexos de propuesta | Propios | Sí | Sí |
| Operar admisibilidad | Responder/cargar sólo en expediente propio | Sí | Sí |
| Descargar residencia | Propia solicitud | Sí | Sí |
| Exportar XLSX global | No | No | Sí |
| Gestionar 2FA de su cuenta | No desde navegación participante | Sí | Sí |

La “disponibilidad local” de esta tabla corresponde exclusivamente al runtime aislado `flowerflow_testing`; no describe el `.env` primario ni producción.

## Desalineaciones entre código, runtime y documentación

1. **Runtime primario preservado:** `config/flowerflow.php` y `.env.example` usan cuatro; el `.env` primario auditado conserva tres. El RC no lo usa ni lo modificó.
2. **Migraciones:** el diagnóstico previo de `flowerflow` registró cuatro migraciones funcionales pendientes; no se reconectó por la prohibición expresa y el árbol agrega ahora v1.1. `flowerflow_testing` quedó 12/12.
3. **Flags:** los defaults siguen apagando registro/recepción/resultados/admisibilidad. El script UAT los habilita temporalmente salvo resultados, después de validar base, catálogos y documentos activos.
4. **Producción:** existe evidencia pública histórica del commit `26256e3`, no evidencia de despliegue de `e2f4345`. No se debe extrapolar.
5. **2FA:** documentación antigua decía “2FA privilegiado” como terminado; el comportamiento actual es enrolamiento opcional, no enforcement.
6. **Plan maestro:** documentos históricos mezclaban el MVP completo con el alcance recortado Fase 01/02A. Este diagnóstico separa ambas medidas.
7. **Rutas demo:** los JSON heredados conservan entradas demo, pero el layout Flower Flow activo no los usa para la navegación funcional. Sigue siendo deuda de limpieza, no evidencia de acceso real.
8. **Evidencia jurídica v1.0:** el hash histórico de Mecánica (`42bd5e…`) no coincide con el binario hoy publicado bajo el mismo nombre (`3bcf31…`). v1.1 no reescribe ese antecedente.
9. **Error de cierre resuelto:** `resources/views/errors/503.blade.php` usa el layout y assets Vite normales, contenido accesible/de marca y cero estilos inline. Funciona para cierre por middleware y pre-render de mantenimiento; la prueba dirigida bajo CSP estricta y el render real móvil/escritorio quedaron verdes.
10. **Topología productiva confirmada:** `/var/www/flowerflow` es el checkout Git productivo directo y no existen `releases/current/shared`. El propietario informa que el VirtualHost `app.sguniformes.com.mx` apunta a esa ruta. Se registra literalmente sin inferir un cambio del host canónico `app.flowerflow.com.mx` ni autorizar cambios de Apache.

## Riesgos priorizados

### P0 — impiden declarar producción lista, no el RC local

1. Sin staging/UAT del propietario, backup restaurado, inventario/capacidad EC2, worker/scheduler, SMTP ni monitoreo para el SHA actual.
2. No hay autorización de migración, release, despliegue ni rollback productivo.
3. Fase 02B y resultados carecen de reglas aprobadas; implementarlos ahora exigiría inventar negocio.

### P1 — deben cerrarse antes de ampliar roles o activar recepción

1. Exigir 2FA a roles privilegiados, completar password confirmation y suspensión/revocación.
2. Definir exclusión o compatibilidad entre `participant` y futuros roles antes de sembrar `judge`.
3. Decidir antimalware/storage productivo y resolver la operación del advisory bajo de Quill.

### P2 — calidad y mantenibilidad

1. CI con suite/build/auditorías y una herramienta E2E permanente.
2. Pruebas de carga/EXPLAIN con volumen aprobado.
3. Limpiar documentación/menús demo restantes y completar SEO.
4. Crear vista de auditor y matriz automatizada de permisos por ruta/rol.

P2 503/CSP quedó resuelto el 2026-08-18; ya no forma parte de esta lista.

## Siguiente secuencia óptima

1. **Publicar un SHA exacto:** incluir v1.1, las decisiones documentadas y la vista 503 resuelta; no sustituir nuevamente los PDF bajo la misma identidad.
2. **Generar el runbook ejecutable por el propietario:** sólo comandos para actualizar el checkout directo `/var/www/flowerflow`, instalar Composer, compilar Yarn/Vite, limpiar/recrear cachés, migrar de forma aditiva y reiniciar procesos. No crear ni asumir `releases/current/shared`; Codex no accede ni ejecuta producción.
3. **Ejecutar por el propietario:** usar el respaldo que ya confirmó haber realizado, preservar las más de 50 propuestas y no ejecutar seeders ni comandos destructivos.
4. **Después del release operativo**, diseñar Fase 02B en un ExecPlan separado; jueces/evaluación y ganadores/resultados permanecen fuera de alcance.

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

## Prompt óptimo siguiente — generar el runbook de instalación y compilación para ejecución del propietario

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
