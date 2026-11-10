# Mapa de versiones — Investigación (propuesta revisada con la v3 del profesor)

> **No reemplaza automáticamente el mapa del equipo.** El `0_mapa_versiones.md` de los materiales suministrados describe una ruta antigua (v1 un solo recurso; v3 tablas con FK). El alcance que sigue proviene de `0_METODOLOGIA.md` y `modulo_investigacion.md` y debe ser acordado por el equipo.

| Versión | Alcance según documentación general del curso | Estado informado o previsto |
|---|---|---|
| v1 | CRUD API + frontend para seis tablas sin FK del módulo | **Terminada según información del proyecto; verificar tag `v1`** |
| v2 | CRUD API + frontend de diez tablas con FK, integridad y desplegables | **Terminada según usuario; PR #15 de backend integrado; en documentación 29-10 había pendientes de cierre, verificar tag `v2` y regresión** |
| v3 | Login JWT, sesiones, autorización por rol, menú por permisos, CRUD `usuario`/`rol`/`rol_usuario` exclusivo admin, logout | **En especificación** |
| v4 | Diez consultas multitabla (4+ tablas cada una), dashboard, imagen corporativa, páginas, responsive/PWA y publicación | **Pendiente** |

**Regla transversal:** cada versión agrega API **y** frontend, cierra con criterios de aceptación, regresión de las anteriores y tag vN en `main`.

## Aclaración de nomenclatura

El paquete `v3_segundo_motor.rar` **no** define el alcance de la v3 del proyecto de aula: corresponde a `proyecto_php3` del ejemplo docente. Aquí `v3_seguridad` integra JWT + sesiones + roles. Este mapa no debe reemplazar el del equipo sin revisión de PR y acuerdo.
