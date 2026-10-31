# 8. Tareas — V2: Investigación

> **ESTADO AL 29-10-2026: IMPLEMENTACIÓN FUNCIONAL DE V2 COMPLETADA; CIERRE FORMAL PENDIENTE.**
>
> La V2 comprende **API y frontend como una sola entrega**, sobre los seis recursos heredados de V1. Las diez entidades de V2 se encuentran implementadas en las carpetas finales `api_investigacion/` y `front_php/` y se probaron durante el desarrollo. Este registro se actualizó con base en el código compartido, los documentos `2_spec.md` a `7_quickstart.md` revisados y los resultados de las pruebas comunicados por el equipo. **No equivale a una aprobación formal, una regresión integral posterior al merge ni a la publicación del tag `v2`.**
>
> **Convención:** `[x]` = actividad realizada con evidencia disponible; `[ ]` = actividad pendiente de ejecutar, decidir o acreditar. Cuando una actividad tiene evidencia **parcial**, se distingue en subactividades para evitar marcar como terminada una verificación todavía abierta. Los números de pruebas corresponden a resultados reportados durante el desarrollo y no a una nueva ejecución al actualizar este documento.

## Fase 0 — Cierre heredado y compuertas

- [ ] **Verificar cierre formal de V1:** revisar los criterios de aceptación, la integración en `main`, el eventual tag `v1` y conservar evidencia de regresión de los seis recursos.
- [ ] **Actualizar/verificar el mapa de versiones propio del equipo**, sin confundirlo con el mapa guía; dejar trazable qué se entrega en V1, V2, V3 y V4.
- [x] Registrar en `5_data_model.md` las diez tablas V2, sus columnas, claves primarias, claves foráneas, nulabilidad y nombres SQL originales.
- [ ] Cotejar formalmente ese modelo con el archivo **`db/init.sql` vigente en la rama final**, además de la copia SQL consultada durante el desarrollo (**C1**).
- [x] Resolver en la implementación las decisiones aplicadas **C2, C3, C5, C6, C9, C10 y C12** de `2_spec.md` (rutas compuestas; PK; FK activas; roles y fechas; PUT/PATCH de puentes; no reactivación; nombres de columnas).
- [ ] Cerrar **C4**: acordar la política de retiro lógico de entidades padre con hijos/asociaciones activas y, si procede, adecuar código, contratos y pruebas.
- [ ] Completar **C7 y C8**: evidencia del cierre heredado de V1 y revisión formal de constitución/mapa de versiones del equipo.
- [ ] Cerrar **C11**: confirmar si la metodología exige **cURL** literalmente y, en ese caso, migrar el cliente HTTP actual y comprobar la regresión. El frontend implementado utiliza `stream_context_create()` y `file_get_contents()`.
- [x] Actualizar el chequeo técnico de constitución de `3_plan.md` con las evidencias de arquitectura y las diferencias observadas.
- [ ] Obtener revisión y aprobación humana del chequeo de constitución; verificar también los requisitos de identidad visual y configuración pendientes.
- [ ] Completar y aprobar formalmente `9_checklist.md` antes de declarar cerrada V2. **La compuerta previa a codificación no se documentó como aprobada en su momento; no presentarla retroactivamente como cumplida.**

## Fase 1 — Reestructuración interna del backend V1

- [x] Crear las subcarpetas `repositorios/Repositorios_<EntidadV1>/` para los seis recursos de V1.
- [x] Ubicar en ellas las interfaces y las implementaciones MariaDB correspondientes. La estructura final fue comprobada; no se afirma que se empleó literalmente `git mv`.
- [x] Crear las subcarpetas `servicios/Servicios_<EntidadV1>/` para los seis recursos de V1.
- [x] Ubicar allí las interfaces y servicios de V1; conservar `servicios/ensamblador.php` como **único punto de ensamblaje**.
- [x] Ajustar los `require_once` y las referencias internas a la nueva estructura sin renombrar las clases públicas.
- [x] Conservar los seis recursos V1 dentro del enrutador, ensamblador y repositorios finales junto a los diez de V2.
- [ ] Adjuntar resultados reproducibles de **pruebas de capas y regresión de los seis recursos V1 después de la integración final**. La presencia de las clases y las pruebas realizadas antes no reemplaza esta comprobación.

## Fase 2 — Reestructuración del frontend V1

- [x] Organizar `vistas/` en `compartidas/` y carpetas individuales para las seis entidades de V1.
- [x] Actualizar las rutas de `include` y `require` de las pantallas reorganizadas.
- [x] Mantener las URL externas del frontend, el enrutador `front_php/index.php` y el cliente HTTP `front_php/cliente_api.php`.
- [x] Conservar Bootstrap local, `publico/` y los estilos en `publico/css/estilos.css`.
- [x] Conservar las seis pantallas/CRUD de V1; su funcionamiento fue reportado por el equipo durante la evolución del proyecto.
- [ ] Registrar en una **matriz de regresión final** las pruebas de los seis CRUD V1 después del merge, incluyendo resultado por operación y evidencia.
- [ ] Probar el frontend con la **API detenida**: comprobar que sigue respondiendo, informa indisponibilidad y no intenta acceder directamente a MariaDB.

## Fase 3 — Recursos V2 con FK (API + pantalla por entidad)

Secuencia aplicada a cada recurso: **modelo → interfaz de repositorio → repositorio PDO → interfaz de servicio → servicio → controlador → ensamblador → enrutador → cliente HTTP → vistas → pruebas de API y de interfaz**. Se conservaron los recursos V1 y se implementaron los siguientes diez recursos de V2.

### 3.1. Entidades asignadas a Ángel

- [x] **`grupo_investigacion`**: modelo, capas, rutas, formulario/listado y pruebas funcionales; **19 comprobaciones HTTP aprobadas** y CRUD de frontend probado.
- [x] **`semillero`**: implementación de API y pantalla, validación del grupo padre activo y borrado lógico; **25 comprobaciones HTTP aprobadas** y CRUD de frontend probado.
- [x] **`docente`**: implementación de API y pantalla, incluida la FK opcional a línea principal; **30 comprobaciones HTTP aprobadas** y CRUD de frontend probado.
- [x] **`participa_grupo`**: clave compuesta (`docente_cedula`, `grupo_investigacion_id`), rol y fechas; **41 comprobaciones HTTP aprobadas** y CRUD de frontend probado.
- [x] **`participa_semillero`**: clave compuesta (`docente`, `semillero`), rol y fechas; **46 comprobaciones HTTP aprobadas** y CRUD de frontend probado.

### 3.2. Entidades asignadas a Miguel

- [x] **`ac_linea`**: clave compuesta (`linea_investigacion`, `area_conocimiento`), API y frontend; pruebas HTTP manuales de creación, consulta, conflicto, retiro y no reactivación, además de consulta SQL de `activo = 0`.
- [x] **`ods_linea`**: clave compuesta (`linea_investigacion`, `ods`), API y frontend; **50 comprobaciones HTTP aprobadas** y consulta SQL de retiro lógico.
- [x] **`aa_linea`**: clave compuesta (`area_aplicacion`, `linea_investigacion`), API y frontend; **50 comprobaciones HTTP aprobadas** y consulta SQL de retiro lógico.
- [x] **`grupo_linea`**: clave compuesta (`grupo_investigacion`, `linea_investigacion`), API y frontend; **50 comprobaciones HTTP aprobadas**. La limpieza lógica se verificó mediante los resultados de API; queda pendiente incorporar evidencia SQL manual específica al expediente de cierre, si se exige.
- [x] **`semillero_linea`**: clave compuesta (`semillero`, `linea_investigacion`), API y frontend; **54 comprobaciones HTTP aprobadas** tras corregir la propiedad `$semillero` del modelo para PHP 8.3, y consulta SQL de `activo = 0`.

### 3.3. Reglas transversales implementadas

- [x] Exponer en la API los **16 recursos** acumulados: seis de V1 y diez de V2.
- [x] Registrar rutas individuales con **dos segmentos en el orden de la PK real** para las siete asociaciones V2.
- [x] Validar por servicio que las FK suministradas correspondan a padres existentes y activos.
- [x] Utilizar PDO con consultas preparadas en los repositorios; no ejecutar SQL desde los controladores ni desde el frontend.
- [x] Consultar solamente registros activos y ejecutar DELETE lógico mediante `activo = FALSE`.
- [x] Rechazar el POST de una asociación cuya pareja compuesta ya exista, incluso si fue retirada (`409`, sin reactivación implícita).
- [x] En `participa_grupo` y `participa_semillero`, permitir PUT/PATCH sobre rol y fechas y conservar inmutables las dos claves.
- [x] En **las cinco tablas puente simples** (`ac_linea`, `ods_linea`, `aa_linea`, `grupo_linea`, `semillero_linea`), responder **`405` para PUT/PATCH**, pues no tienen campos editables fuera de la PK y `activo`.
- [x] Mostrar en los formularios desplegables de FK con opciones activas obtenidas de la API, y no valores inventados.
- [x] Integrar en `front_php` listado, registro, consulta de detalle y retiro de las cinco tablas puente; las entidades con campos editables cuentan además con actualización.

**Nota funcional:** «Diez recursos terminados» no significa que las cinco tablas puente simples tengan PUT/PATCH: la ausencia de edición en ellas **es el contrato implementado**, no una tarea faltante. Las **diez pantallas V2** fueron probadas durante el desarrollo; falta aún la regresión conjunta **después del merge final**.

## Fase 4 — Pruebas, documentación e integración

### 4.1. Pruebas realizadas durante el desarrollo

- [x] Comprobar mediante peticiones HTTP que las FK inexistentes e inactivas se rechazan en las asociaciones V2 probadas (`409`).
- [x] Comprobar que las PK/parejas duplicadas se rechazan (`409`) y que una pareja retirada no se reactiva por POST.
- [x] Comprobar PUT completo, PATCH parcial y PATCH vacío **donde existen campos editables**, especialmente en las dos participaciones; los casos inválidos de estas devolvieron `422`.
- [x] Comprobar que PUT/PATCH devuelven `405` en las cinco tablas puente sin campos editables.
- [x] Comprobar la prohibición de editar las PK compuestas y el campo `activo` en los casos cubiertos por los scripts de pruebas.
- [x] Comprobar por API la operación DELETE lógico, GET posterior `404` y DELETE repetido `404` en los casos ejecutados.
- [x] Comprobar en phpMyAdmin el estado `activo = 0` de varias entidades, entre ellas `ac_linea`, `ods_linea`, `aa_linea`, `participa_semillero` y `semillero_linea`.
- [x] Ejecutar las comprobaciones HTTP por entidad; los resultados conocidos se detallan en la **Fase 3** y en `3_plan.md` §6.
- [x] Corregir en `modelos/SemilleroLinea.php` la propiedad mal declarada que producía advertencias por propiedades dinámicas bajo PHP 8.3; las 54 pruebas posteriores pasaron.
- [x] Usar scripts PowerShell HTTP como **equivalente funcional de una colección de pruebas por entidad** para casos válidos y de error en las entidades automatizadas. `ac_linea` se comprobó manualmente.
- [ ] Unificar/versionar, si se requiere para la entrega, las evidencias y scripts de pruebas de todas las entidades en el repositorio definitivo o en un anexo de aceptación.

### 4.2. Documentación

- [x] Actualizar `2_spec.md`: requisitos, alcance, criterios y clarificaciones C1–C12 con estado real.
- [x] Actualizar `3_plan.md`: arquitectura, estructura de carpetas, secuencia de integración y compuertas pendientes.
- [x] Actualizar `4_research.md`: decisiones técnicas, evidencia y cuestiones no resueltas.
- [x] Actualizar `5_data_model.md`: diez tablas, columnas, PK/FK, relaciones y borrado lógico.
- [x] Actualizar `6_contracts.md`: rutas definitivas, métodos permitidos, cuerpos y códigos HTTP.
- [x] Actualizar `7_quickstart.md`: ejecución con Docker, puertos reales, pruebas y solución de problemas.
- [x] **Actualizar `8_tasks.md`** a partir de las evidencias disponibles, sin declarar cerrado el proyecto.
- [ ] Revisar y actualizar `9_checklist.md`; completar evidencias, observaciones y firmas de aceptación.
- [ ] Revisar y actualizar `GUIA_IA2.md` si el equipo la usa como instructivo vigente para mantenimiento o nuevas contribuciones.

### 4.3. Pendientes de aceptación y cierre de V2

- [ ] **Definir y aplicar C4:** comportamiento al retirar un padre que aún tenga asociaciones hijas activas; añadir pruebas según la decisión aprobada.
- [ ] **Resolver C11 (cURL):** revisar el requerimiento de metodología frente al cliente HTTP existente; migrar y probar si es obligatorio, o registrar excepción aprobada.
- [ ] Realizar el **cotejo formal del DDL** con `db/init.sql` de la rama final y comprobar que no haya cambios de columnas o relaciones sin documentar.
- [ ] Ejecutar y documentar la **regresión integral de los 16 recursos después de integrar las ramas** (API y pantallas). Respetar que solo cinco de las diez entidades V2 permiten PUT/PATCH.
- [ ] Ejecutar las **pruebas de capas sin MariaDB** del repositorio (`pruebas/prueba_capas.php` y demás pruebas aplicables) sobre la versión final y guardar los resultados.
- [ ] Probar de forma controlada el **frontend con la API apagada** y comprobar los mensajes de indisponibilidad sin acceso directo a la BD.
- [ ] Comprobar manejo de errores internos `500` sin exposición de detalles de SQL ni credenciales.
- [ ] Verificar `.env` fuera de Git, `.env.example` sin secretos, configuración Compose y cualquier requisito visual/marca aplicable.
- [ ] Revisar las ramas y Pull Requests de **ambos repositorios**, resolver conflictos, confirmar los merges a `main` y conservar referencias de PR/commits.
- [ ] Completar la revisión humana de la constitución, las clarificaciones y el `9_checklist.md`, con responsable y fecha.
- [ ] **Solo después del cierre aprobado**, crear/verificar el tag `v2` en `main`, si así lo establece el procedimiento de entrega.

## Resumen del estado de V2

| Área | Estado al 29-10-2026 | Próxima evidencia necesaria |
|---|---|---|
| Backend V2 | **10/10 entidades implementadas y probadas por recurso** | Regresión posmerge e incidencias de aceptación |
| Frontend V2 | **10/10 interfaces implementadas y probadas durante el desarrollo** | Regresión conjunta posmerge y prueba con API apagada |
| Base V1 | **6 recursos conservados en ambas carpetas** | Evidencia final del cierre V1 y regresión |
| Modelo, contratos y quickstart | **Documentos `2_spec.md`–`8_tasks.md` actualizados** | Cotejo final con código integrado |
| Decisiones abiertas | **C4, C11 y compuertas de V1/constitución** | Resolución aprobada por el equipo |
| Entrega GitHub | **Sin evidencia suficiente de cierre formal en esta documentación** | PR, merge, tag y aceptación documentados |

**Criterio para declarar V2 cerrada:** pasar la regresión y las pruebas de aceptación sobre el código integrado, resolver las decisiones obligatorias pendientes, comprobar `9_checklist.md` y obtener aprobación del equipo. Haber terminado las diez entidades durante el desarrollo es un avance funcional, **no por sí solo el cierre formal**.
