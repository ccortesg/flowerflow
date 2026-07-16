# ADR 0005 — Fortify, Spatie Permission y Policies

Estado: aceptado para Fase 01 · 2026-07-15

## Decisión

Usar Laravel Fortify 1.37.2 para autenticación headless y vistas Blade propias; usar Spatie Laravel Permission 8.3.0 para roles/permisos y Policies de primera parte para autorización por recurso. PHP local efectivo es 8.3.31, compatible con Permission v8. Registro y recepción permanecen bajo flags.

## Razones y consecuencias

Evita implementar credenciales/2FA manualmente y evita un sistema RBAC paralelo. Los roles no sustituyen Policies: un participante sigue limitado a sus propios recursos. La EC2 deberá demostrar PHP 8.3+ antes de desplegar; si no, se requiere decisión separada, no un downgrade silencioso. CRUD visual de roles queda fuera de Fase 01.

## Verificación

Tests de login/verificación, rate limit, participante rechazado en `/panel`, admin admitido, IDOR y comando de administrador. Revisar recuperación 2FA en UAT.

## Adenda de contraseña y correo — 2026-07-15

La política única acepta desde 8 caracteres y conserva mayúscula, minúscula, número, símbolo y confirmación. `Password::defaults()` es autoritativo para registro, reset, cambio y comando administrativo; un componente Blade/JavaScript replica cada criterio como apoyo visual accesible.

Verificación y recuperación usan notificaciones propias `ShouldQueueAfterCommit` y cifradas. El acuse de propuesta comparte conexión `database`, cola `default`, cuatro intentos totales, backoff 60/300/900 y timeout de job de 30 segundos. Una falla al programar el job se registra sin correo ni contenido y produce aviso/reintento, nunca revierte la operación principal ni se convierte en HTTP 500.
