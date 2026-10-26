# Mapa de versiones — Módulo de Investigación (propuesta para el equipo)

> Este mapa es para el **proyecto de aula del equipo**, no para el ejemplo didáctico `v1_area_conocimiento` del profesor. Antes de reemplazar el mapa existente, revisar y aprobar con el equipo. Estados no comprobados se dejan expresamente por confirmar.

| Versión | Alcance acumulativo según `modulo_investigacion.md` y `0_METODOLOGIA.md` | Estado |
|---|---|---|
| V1 | CRUD de las **6 tablas sin FK**, API + frontend | Implementación declarada por el equipo; **cierre con criterios y tag por verificar** |
| V2 | CRUD de las **10 tablas con FK**, desplegables desde API e integridad referencial; regresión de V1 | Spec Kit en borrador, pendiente de compuertas e implementación |
| V3 | Autenticación JWT y roles; `usuario`, `rol`, `rol_usuario` | Pendiente |
| V4 | 10 consultas multitabla, dashboard, imagen corporativa, responsive/PWA y publicación | Pendiente |

## Entidades V1
`area_conocimiento`, `objetivo_desarrollo_sostenible`, `area_aplicacion`, `termino_clave`, `universidad`, `linea_investigacion`.

## Entidades V2
`docente`, `grupo_investigacion`, `semillero`, `participa_grupo`, `participa_semillero`, `grupo_linea`, `semillero_linea`, `ac_linea`, `ods_linea`, `aa_linea`.

## Cierre de cada versión
Criterios de aceptación + regresión completa + revisión humana del checklist + PR y merge a main + tag `vN`. Una versión cerrada no se reabre: los ajustes se documentan en la siguiente.
