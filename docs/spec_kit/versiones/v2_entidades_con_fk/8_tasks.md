# 8. Tareas — V2: Investigación

> Orden sugerido; completar las casillas solo con evidencia. La V2 incluye API y frontend, no son dos entregas distintas.

> Marcas [x] basadas en los cambios y comprobaciones que el equipo reportó en la conversación. El checklist de cierre **requiere revisión humana** y evidencia de cada caso específico.

## Fase 0 — Cierre heredado y compuertas
- [ ] Revisar criterios, integración y tag `v1` en main; conservar evidencia de la regresión.
- [ ] Actualizar mapa de versiones del proyecto sin confundirlo con el mapa guía.
- [x] Transcribir `init(2).sql` del proyecto al modelo; queda pendiente cotejo con `db/init.sql` vigente.
- [ ] Resolver **C1–C12** de `2_spec.md` con el equipo.
- [ ] Revisar el chequeo de constitución de `3_plan.md`.
- [ ] Completar y aprobar los requisitos de `9_checklist.md` antes de codificar.

## Fase 1 — Reestructuración interna del backend V1
- [x] Crear subcarpetas de `repositorios/` para las seis entidades V1.
- [x] Mover interfaces e implementaciones (evidencia: estructura reorganizada; no se confirma el uso literal de `git mv`).
- [x] Crear subcarpetas de `servicios/` para las seis entidades V1.
- [x] Mover interfaces y servicios (evidencia: estructura reorganizada; no se confirma el uso literal de `git mv`); conservar `ensamblador.php` como único ensamblador.
- [x] Corregir `require_once`, rutas internas y referencias de pruebas sin renombrar clases.
- [ ] Ejecutar prueba de capas, pruebas HTTP y regresión de cada entidad tras moverla.

## Fase 2 — Reestructuración del frontend V1
- [x] Separar `vistas/` en `compartidas/` y subcarpetas de las seis entidades.
- [x] Actualizar las rutas internas de `include` y `require`.
- [x] Conservar el enrutador de URL externas y las llamadas HTTP actuales.
- [x] Crear/organizar hojas CSS locales sin romper `publico/` ni ruta de Bootstrap.
- [x] Probar los seis CRUD tras refactor (reportado por el equipo; falta una matriz de evidencias para cada código HTTP).
- [ ] Probar frontend disponible con la API apagada.

## Fase 3 — Recursos V2 con FK (API + pantalla por entidad)

Para **cada** recurso: modelo → interfaz repositorio → repositorio PDO → interfaz servicio → servicio → controlador → ensamblador → enrutador → cliente HTTP → vistas → pruebas.

- [ ] `docente`.
- [ ] `grupo_investigacion`.
- [ ] `semillero`.
- [ ] `participa_grupo`.
- [ ] `participa_semillero`.
- [ ] `grupo_linea`.
- [ ] `semillero_linea`.
- [ ] `ac_linea`.
- [ ] `ods_linea`.
- [ ] `aa_linea`.

**Importante:** el orden efectivo se ajusta a dependencias del DDL confirmado; las pantallas FK muestran opciones activas obtenidas de la API y no listas inventadas.

## Fase 4 — Pruebas y documentación
- [ ] Validar FK inexistentes y FK lógicamente retiradas.
- [ ] Validar duplicados de PK y de asociaciones.
- [ ] Validar PUT completo, PATCH parcial y PATCH vacío.
- [ ] Validar `activo` invisible y borrado lógico en las diez entidades.
- [ ] Validar política aprobada para retiros de padres con hijos.
- [ ] Ejecutar regresión completa de los 16 CRUD.
- [ ] Ejecutar pruebas de capas sin MariaDB.
- [ ] Ejecutar frontend con API apagada.
- [ ] Completar rutas y cuerpos definitivos en `6_contracts.md`.
- [ ] Reemplazar el quickstart provisional con comandos **probados**.
- [ ] Preparar colección de Postman o equivalente con casos felices/de error.
- [ ] Revisar manualmente `9_checklist.md`, integrar por PR y luego crear tag `v2` sobre main.
