# Reporte de revisión — v3 Seguridad, módulo Investigación

**Fecha de revisión documental:** 10 de noviembre de 2026.  
**Estado:** borrador actualizado; no aprobado por equipo/profesor; pruebas de v3 no ejecutadas.

## Fuentes revisadas

- `0_METODOLOGIA.md` (ruta del aula, tres compuertas, §6.4 JWT y §7 seguridad).
- `modulo_investigacion.md` (v3: JWT, middleware, permisos, tres CRUD y logout).
- Spec Kit entregado en `v2_entidades_con_fk(1).rar` (9 documentos, incluye los contratos reales documentados de v2).
- `init(4).sql` de Investigación (DDL de `usuario`, `rol` y `rol_usuario`, sin semillas admin).
- `v3_segundo_motor.rar` del profesor (9 documentos) **solo como patrón metodológico**.
- Borrador previo de `v3_seguridad` con decisión registrada de roles y primer admin.

## Resultado por archivo

| Archivo | Ajuste realizado |
|---|---|
| `2_spec.md` | Distingue alcance requerido y propuestas; añade flujos, glosario y definición de terminado |
| `3_plan.md` | Expone delta de archivos y orden de verificaciones sin agregar segundo motor |
| `4_research.md` | Incorpora decisiones D-v3-17 a D-v3-20 con alternativas y consecuencias |
| `5_data_model.md` | Conserva las tres tablas del SQL; agrega invariantes y procedimiento en volumen persistente |
| `6_contracts.md` | Amplía códigos y sobres JSON, campos exactos y navegación frontend propuesta |
| `7_quickstart.md` | Añade pruebas negativas de HTTP y registro de evidencias sin revelar secretos |
| `8_tasks.md` | Vincula RF → fases → CA; recomienda integración por PR |
| `9_checklist.md` | Distingue **calidad documental antes de programar** y **cierre funcional**; elimina preaprobaciones |
| `GUIA_IA3.md` | Adapta los dos caminos del profesor (chat/IDE), con revisión de diffs y pruebas |
| Archivos complementarios | Actualizan comparación, bootstrap, SQL y mapa sin inventar verificación |

## Confirmado por fuentes (no es una prueba de código actual)

1. El curso exige **dos repositorios separados**, PHP puro, capas, JWT firmado, sesiones del frontend, rutas explícitas y permisos admin/consulta.
2. V2 documenta 16 recursos; en cinco asociaciones simples PUT/PATCH → `405`; el PATCH vacío de controladores V2 revisados → `422` (V1 contemplaba `400`).
3. `usuario` y `rol` tienen PK autoincrementales, `username`, `email` y `rol.nombre` son `UNIQUE`.
4. `rol_usuario` tiene PK compuesta y FKs con `ON DELETE CASCADE`, **sin `activo`**.
5. No hay semillas de seguridad en el `init.sql` entregado. El acuerdo del usuario es **sembrar solo roles públicos en SQL y crear el primer administrador mediante PHP CLI**, con hash y variables fuera de Git.
6. La v3 del ejemplo del profesor es de **segundo motor de datos**, distinta de la del proyecto de Investigación.

## Clarificaciones sin resolver (bloquean la compuerta 1)

- C3: otros roles / suma de privilegios si un usuario tiene varios.
- C4: matriz final, rutas públicas, precedencia de 404/405 y 401/403.
- C5: orquestación real en Docker y recuperación de cuentas ambiguas; la estrategia de bootstrap **sí está acordada**.
- C6: JWT (duración, claims, efecto de roles desactivados) y control del secreto.
- C7: alcance del logout y revocación o no de tokens previamente emitidos.
- C8: cómo cumplir CRUD del puente sin `activo`: DELETE físico excepcional, cambio de DDL aprobado o reasignación por operación atómica; protección de último admin.
- C9: cuerpo definitivo de login, contraseña en PUT/PATCH y políticas de usuario.
- C10: rutas frontend, cookies y CSRF de formularios.
- C11: cierre técnico V2 (cURL vs streams, regresión y tags); la v2 está declarada terminada por el usuario, falta contrastar contra código vivo.
- Verificar `SHOW CREATE TABLE` en base activa, `docker-compose.yml`, router/API y frontend actuales.

## Cómo utilizar estos archivos

1. Revisar y aprobar **primero** las clarificaciones de `2_spec.md` con el compañero y profesor cuando proceda.
2. Completar el chequeo artículo por artículo de `3_plan.md` contra la **constitución vigente del equipo**.
3. Pasar y firmar la sección documental de `9_checklist.md` **antes de programar**.
4. Solo después implementar y documentar pruebas de `7_quickstart.md` con evidencia real.
5. Integrar por la rama `feature/angel-investigacion-v3` y PR; no trabajar directamente en `main`.

No se modificaron ni ejecutaron los archivos de la aplicación, el esquema SQL, Docker ni credenciales. **No se afirma que las pruebas de V3 hayan pasado.**
