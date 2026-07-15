# ADR-0001: Monolito modular Laravel con Blade y Materialize

- **Estado:** Proposed
- **Fecha:** 2026-07-15
- **Decisores:** producto, líder técnico y operación

## Contexto

El repositorio ya declara Laravel 12 y Materialize/Pixinvent 3.0.0. No contiene dominio ni autenticación funcional. El MVP debe operar antes del 15 de agosto de 2026 y compartir infraestructura EC2 con Administratec sin compartir datos o procesos.

## Decisión

Conservar Laravel 12 como monolito modular, vistas Blade y JavaScript por página construido con Vite. Reutilizar layouts Materialize y separar módulos por capacidades de negocio, con Policies, Form Requests, Actions/Services, enums y eventos/jobs cuando aporten aislamiento.

## Consecuencias

### Positivas

- Menor tiempo de implementación y despliegue.
- Una transacción puede cubrir envío, revisión y evaluación.
- Reutiliza la licencia/plantilla sujeto a confirmación.
- Menor superficie operativa que SPA o microservicios.

### Negativas

- Requiere disciplina para no mezclar PII, elegibilidad y evaluación.
- Un fallo de proceso puede afectar el monolito; se mitiga con workers/colas y límites.
- La plantilla incluye muchos assets demo que deben racionalizarse cuidadosamente.

## Alternativas

- SPA: rechazada por plazo, doble validación y ausencia de base.
- Microservicios: rechazados por costo operativo y consistencia.
- Actualizar Laravel: diferido; no hay baseline reproducible ni autorización de versión mayor.

## Criterio para aceptar

Confirmar licencia de Materialize, fijar dependencias, completar baseline ejecutable y aprobar fronteras de módulos.

