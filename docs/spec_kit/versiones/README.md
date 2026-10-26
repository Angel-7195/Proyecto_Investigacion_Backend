# Paquete de documentación Spec Kit V2 — Investigación

**Contenido:** borradores de `2_spec.md` a `9_checklist.md`, `GUIA_IA2.md` y **propuesta** para `0_mapa_versiones.md`.

## Cómo usarlo

1. Extraer el ZIP en una carpeta temporal.
2. Copiar `docs/spec_kit/versiones/v2_entidades_con_fk/` al repositorio **backend**, en la misma estructura. La constitución V1 no se reemplaza.
3. Comparar `docs/spec_kit/versiones/0_mapa_versiones_PROPUESTA.md` con el mapa actual y solo después de la revisión, integrar la versión específica del equipo con nombre `0_mapa_versiones.md`.
4. Revisar `2_spec.md` (clarificaciones) y el SQL `db/init.sql`. Resolver llaves/columnas antes de autorizar implementación.
5. Cerrar la compuerta de constitución (`3_plan.md`) y la revisión humana de requisitos (`9_checklist.md`).
6. Reorganizar primero las clases V1 sin modificar contratos; ejecutar regresión. Luego implementar una entidad V2 a la vez.
7. Al terminar: actualizar `7_quickstart.md` con **comandos y resultados reales**, ejecutar regresión y cerrar V2 con tag en main.

## No realizado en este paquete

No se movieron archivos PHP de los repositorios ni se verificaron esquemas, endpoints, contenedores o tags porque los repositorios actuales y `db/init.sql` no fueron suministrados. Por eso estos archivos dicen **BORRADOR** y contienen puntos pendientes de validación.

## Fuentes recibidas

`0_METODOLOGIA.md`, `modulo_investigacion.md`, los archivos V1 `2_spec.md` a `9_checklist.md`, `GUIA_IA1.md`, `0_mapa_versiones.md` y `1_constitution.md`. Existe una divergencia entre el mapa/constitución del ejemplo de una sola entidad V1 y la versión del módulo de aula con seis entidades V1. Se adopta para el borrador V2 el alcance del **módulo de aula** y se señala la necesidad de conciliación documental.
