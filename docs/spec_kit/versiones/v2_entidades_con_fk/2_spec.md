# 2. Especificación — V2: entidades con claves foráneas

> **ESTADO (29-10-2026): IMPLEMENTACIÓN FUNCIONAL REALIZADA; CIERRE DOCUMENTAL Y DE INTEGRACIÓN PENDIENTE.**
> Esta especificación es un **DELTA sobre V1**, no la reemplaza. Se actualizó contrastando el borrador previo, el esquema SQL compartido, los archivos finales `api_investigacion/` y `front_php/` y las pruebas funcionales comunicadas por el equipo. Las decisiones implementadas se identifican explícitamente; los asuntos sin aprobación o evidencia concluyente permanecen abiertos.

## 1. Objetivo y alcance

Implementar, en la API REST de Investigación y en el frontend PHP, las **diez entidades del módulo con claves foráneas**, conservando los seis recursos sin FK entregados en V1. El alcance acumulado comprende **16 recursos administrables**.

Cada recurso tiene modelo, interfaces de repositorio y servicio, implementaciones por capas, controlador, rutas HTTP y pantallas propias. El acceso a datos de la API se realiza mediante **PDO y MariaDB**; el frontend obtiene y envía información **únicamente por HTTP**, sin conexión directa a la base de datos. Los desplegables de claves foráneas muestran registros relacionados activos obtenidos desde la API.

| Recurso V2 | Función | Identificador |
|---|---|---|
| `docente` | Perfil del docente investigador; puede referenciar una línea principal | `cedula` (INT) |
| `grupo_investigacion` | Grupo de investigación; puede referenciar una universidad | `id` (INT) |
| `semillero` | Semillero vinculado a un grupo de investigación | `id` (INT) |
| `participa_grupo` | Participación de docente en grupo, con rol y fechas | (`docente_cedula`, `grupo_investigacion_id`) |
| `participa_semillero` | Participación de docente en semillero, con rol y fechas | (`docente`, `semillero`) |
| `grupo_linea` | Asociación de grupo con línea de investigación | (`grupo_investigacion`, `linea_investigacion`) |
| `semillero_linea` | Asociación de semillero con línea de investigación | (`semillero`, `linea_investigacion`) |
| `ac_linea` | Asociación de línea con área de conocimiento | (`linea_investigacion`, `area_conocimiento`) |
| `ods_linea` | Asociación de línea con objetivo de desarrollo sostenible | (`linea_investigacion`, `ods`) |
| `aa_linea` | Asociación de área de aplicación con línea de investigación | (`area_aplicacion`, `linea_investigacion`) |

**Alcance real de operaciones:** las tres entidades con PK simple y las dos participaciones admiten GET, POST, PUT, PATCH y DELETE. Las **otras cinco tablas puente** (`grupo_linea`, `semillero_linea`, `ac_linea`, `ods_linea`, `aa_linea`) admiten GET, POST y DELETE lógico; PUT/PATCH responden `405` porque no tienen datos editables fuera de la PK y `activo`. Por tanto, “CRUD de diez entidades” significa gestión funcional de los diez recursos, **no** que todos admitan las cinco operaciones HTTP.

**Base estructural:** las columnas, tipos, FK y PK corresponden al SQL del módulo revisado (`init(2).sql`, copia compartida) y al código entregado. Se conserva la estructura del esquema; el **cotejo formal con el `db/init.sql` exacto de la rama final** sigue siendo una verificación de cierre, no una modificación solicitada por esta especificación. Véase `5_data_model.md`.

## 2. Requisitos funcionales verificables

### RF-V2-01 — Listar activos

- Cada recurso tiene un GET de colección con registros que cumplen `activo = TRUE`.
- Una colección sin registros activos responde `204 No Content` (sin cuerpo).
- Si existen registros activos, la API responde `200` y el listado los contiene; las pantallas distinguen una lista vacía de una falla de conexión.

**Estado:** implementado y observado en las comprobaciones de V2.

### RF-V2-02 — Consultar por clave real

- Los recursos con PK simple se consultan por `cedula` o `id`.
- Las siete asociaciones se consultan mediante **ambos componentes de la PK**, en el orden de la tabla.
- Un registro inexistente o lógicamente retirado devuelve `404`.

**Estado:** implementado; se probaron consultas individuales de las asociaciones y entidades principales.

### RF-V2-03 — Crear respetando esquema e integridad

- POST exige los campos obligatorios de cada tabla, con tipos y longitudes compatibles con el SQL; `activo` no se recibe del usuario.
- Las PK simples `docente.cedula`, `grupo_investigacion.id` y `semillero.id` **son suministradas por el cliente**, no autoincrementales.
- Las PK compuestas se envían con sus dos componentes; no existe `id` adicional.
- `semillero.grupo_investigacion` es obligatorio y debe referenciar un grupo **activo**.
- `docente.linea_investigacion_principal` y `grupo_investigacion.universidsad` admiten `null`; si se proporciona un identificador, su registro referenciado debe existir y estar activo.
- Al crear cualquiera de las siete asociaciones, ambas referencias deben existir y encontrarse activas.
- Una FK inexistente o inactiva origina un conflicto `409`; los cuerpos inválidos o incompletos se rechazan con `422`.

**Estado:** implementado y probado para las entidades V2 principales y para las asociaciones.

### RF-V2-04 — Reemplazar datos editables (PUT)

- `docente`, `grupo_investigacion` y `semillero` permiten reemplazar sus campos editables, sin modificar su PK ni `activo`.
- `participa_grupo` y `participa_semillero` permiten reemplazar `rol`, `fecha_inicio` y `fecha_fin`, sin cambiar la pareja identificadora; en PUT deben aparecer los tres campos, aunque `fecha_fin` admite `null`.
- `grupo_linea`, `semillero_linea`, `ac_linea`, `ods_linea` y `aa_linea` responden **`405 Method Not Allowed`** a PUT porque sus únicas columnas son las dos claves y `activo`.
- Si faltan campos obligatorios en un PUT aplicable, la validación devuelve `422`.

**Estado:** decisión implementada; PUT no permitido en las cinco asociaciones simples se comprobó mediante scripts HTTP.

### RF-V2-05 — Actualizar parcialmente (PATCH)

- En recursos con campos editables, PATCH cambia únicamente los campos enviados; los ausentes conservan su valor.
- Las PK y `activo` no se pueden editar. Los campos desconocidos, los tipos inválidos y los PATCH vacíos `{}` se rechazan con **`422`** en los controladores V2 revisados.
- En las dos participaciones, PATCH admite `rol`, `fecha_inicio` y `fecha_fin`; `fecha_fin` puede establecerse expresamente en `null`.
- Las cinco asociaciones simples responden **`405`** a PATCH por carecer de campos editables.

**Estado:** implementado. **Diferencia documental:** el borrador V2 exigía `400` para PATCH vacío, pero las comprobaciones y los controladores actuales usan `422`. Se registra el comportamiento real; si existe un contrato obligatorio previo que exige `400`, se debe acordar la corrección de código y documentación, sin presentarla como realizada.

### RF-V2-06 — Retirar mediante borrado lógico

- DELETE no elimina físicamente la fila: marca `activo = FALSE`.
- Un registro retirado no aparece en los listados ordinarios ni en la consulta individual (`404`).
- Un segundo DELETE sobre la misma clave retirada responde `404`.
- `activo` es interno y no puede editarse mediante POST, PUT o PATCH.

**Estado:** implementado; se verificó mediante respuestas HTTP y consultas manuales en phpMyAdmin para las cinco asociaciones de Miguel y las entidades correspondientes al desarrollo previo.

### RF-V2-07 — Integridad y respuesta de conflicto

- Los servicios comprueban las referencias activas antes de registrar asociaciones o modificar relaciones foráneas editables.
- Las combinaciones de PK duplicadas responden `409 Conflict`.
- **Una asociación retirada no se reactiva automáticamente:** al intentar crear otra vez su misma pareja de claves, responde `409` porque su PK física permanece ocupada.
- Los errores de integridad no deben revelar sentencias SQL, parámetros sensibles ni detalles internos en las respuestas HTTP.

**Estado:** implementado y probado para las asociaciones. **Punto abierto:** la política de retiro de registros padre que todavía tengan hijos activos no ha sido definida como regla general; el borrado lógico no la resuelve por sí solo. Véase C4.

### RF-V2-08 — Selectores de claves foráneas

- En formularios PHP, los valores de universidad, grupo, docente, semillero, línea, ODS y áreas relacionadas se solicitan a los recursos correspondientes de la API.
- Los desplegables muestran nombres o descripciones comprensibles y envían los identificadores reales.
- No deben ofrecerse opciones de referencias lógicamente retiradas.
- La API valida nuevamente la FK al guardar, incluso cuando el valor provino de un desplegable.
- Una API no disponible debe mostrarse como indisponibilidad y no como un listado legítimamente vacío.

**Estado:** implementado en los formularios de V2; las pantallas fueron probadas manualmente durante el desarrollo. La verificación integral con API detenida sigue pendiente de evidenciarse.

### RF-V2-09 — Claves compuestas y reglas de participaciones

- Las siete asociaciones emplean **PK compuesta de dos columnas** y se identifican con dos segmentos de URL.
- En `ac_linea`, `area_conocimiento` es **VARCHAR(6)** y se trata como texto; no debe convertirse a entero.
- `participa_grupo` y `participa_semillero` contienen `rol` (`VARCHAR(15)`), `fecha_inicio` (`DATE`) y `fecha_fin` (`DATE NULL`).
- `rol` debe ser texto no vacío de hasta 15 caracteres; las fechas informadas deben ser válidas con formato `YYYY-MM-DD`.
- `fecha_fin` puede ser `null`; si existe, no puede ser anterior a `fecha_inicio`.
- Las claves compuestas no se cambian mediante PUT/PATCH. Si se necesita una asociación distinta, se registra otra pareja y se retira la anterior.
- No se establece aquí un catálogo cerrado de roles porque el esquema revisado no lo impone y el código actual valida longitud y presencia, no una enumeración.

**Estado:** implementado y comprobado, incluidas validaciones de fechas, PATCH parcial y claves inmutables en las dos participaciones.

### RF-V2-10 — Arquitectura y separación frontend/backend

- Backend: PHP 8.3, clases y contratos por recurso, controlador → servicio → repositorio → PDO/MariaDB, con ensamblaje de dependencias en `servicios/ensamblador.php`.
- Frontend: PHP con vistas y rutas por recurso, HTML, CSS y Bootstrap local, sin consultas SQL ni acceso PDO.
- Comunicación: **HTTP y JSON** mediante `front_php/cliente_api.php`.
- **Tecnología efectiva observada:** `stream_context_create()` + `file_get_contents()`. La metodología académica menciona **cURL**; decidir si debe migrarse antes del cierre, sin afirmar que ya está implementado.

**Estado:** separación de capas y consumo HTTP implementados. Revisión de exigencia de cURL: pendiente (C11).

### RF-V2-11 — Compatibilidad con V1

- Mantener las seis entidades V1 y sus rutas, cuerpos JSON, excepciones y borrado lógico.
- No introducir regresiones por la incorporación de V2 ni por la reorganización de carpetas.
- Realizar una regresión integral de V1 y V2 **después de fusionar las ramas**; no darla por aprobada sin su registro.

**Estado:** las seis entidades V1 y las diez V2 se encuentran presentes en las carpetas consolidadas; la regresión integral posterior al merge queda pendiente de evidencia.

### RF-V2-12 — Alcance por versión

No incorporar JWT, autenticación por roles, dashboards, PWA, publicación ni consultas analíticas de V3/V4 en el cierre de V2.

**Estado:** se respeta la separación de alcance según los archivos revisados.

### RF-V2-13 — Organización del código sin alterar contratos públicos

- Conservar controladores y modelos por entidad.
- Organizar repositorios en `repositorios/Repositorios_<Entidad>/` y servicios en `servicios/Servicios_<Entidad>/`.
- Organizar frontend en `rutas/rutas_<entidad>.php` y `vistas/<entidad>/`.
- Mantener `api_investigacion/index.php`, `servicios/ensamblador.php`, `front_php/index.php` y `front_php/cliente_api.php` como puntos compartidos de integración.
- Utilizar rutas explícitas y consultas SQL parametrizadas, no un CRUD SQL genérico por nombre de tabla.

**Estado:** implementado y reflejado en las carpetas finales compartidas.

## 3. Flujos de usuario

1. **Crear un semillero:** el usuario abre «Semilleros», completa el formulario, selecciona un grupo activo obtenido de la API y registra el semillero. La API verifica el grupo antes de persistir.
2. **Rechazar FK inactiva:** si un grupo, docente, semillero u otra referencia seleccionada fue retirada, la API rechaza la operación con `409`; la pantalla debe comunicar el conflicto sin inventar referencias.
3. **Gestionar una participación:** el usuario asocia un docente a un grupo o semillero, asigna rol y fechas, consulta el vínculo, modifica únicamente esos atributos o lo retira.
4. **Gestionar las cinco asociaciones simples:** el usuario selecciona dos registros activos, registra la pareja, la consulta y puede retirarla. No hay formulario de edición para cambiar las dos PK.
5. **Evitar duplicados y reactivación implícita:** una pareja ya almacenada, activa o retirada, no se registra otra vez; la API responde `409`.
6. **Consultar una colección vacía:** el frontend representa el `204` como ausencia de registros, sin tratarlo como error de servidor.
7. **API no disponible:** el frontend debe seguir sirviendo las páginas y mostrar indisponibilidad sin acceder directamente a MariaDB. La conducta está contemplada en el cliente HTTP; falta evidenciar la prueba integral de desconexión para el cierre.

## 4. Fuera del alcance

- Autenticación JWT, perfiles de usuario, autorización por roles o permisos de V3.
- Tablas, columnas o modificaciones de PK/FK no contempladas en el DDL del módulo.
- Conversión automática de asociaciones retiradas en asociaciones activas mediante POST.
- CRUD SQL genérico que permita operar cualquier nombre de tabla desde la URL.
- Acceso directo del frontend a la base de datos, ni reutilización de clases de repositorio en las vistas.
- Consultas analíticas complejas, gráficos, dashboard, PWA o despliegue propios de V4.

## 5. Criterios de aceptación y evidencia disponible

| Criterio | Condición de aceptación | Evidencia/estado al 29-10-2026 |
|---|---|---|
| CA-01 | Diez recursos V2 implementados en API y frontend, preservando seis V1 | **Implementado** en las carpetas consolidadas; probado por entidad durante el desarrollo |
| CA-02 | Listados activos (`200`) y vacíos (`204`) | **Comprobado** en las pruebas funcionales realizadas |
| CA-03 | Creación (`201`) y consulta individual (`200`) por PK simple o compuesta | **Comprobado** |
| CA-04 | Las FK faltantes/inactivas no se aceptan (`409`) | **Comprobado** en scripts de pruebas de las asociaciones |
| CA-05 | Una pareja duplicada, incluso retirada, no se reactiva (`409`) | **Comprobado** |
| CA-06 | Las dos participaciones admiten PUT/PATCH solo sobre rol y fechas | **Comprobado** en las pruebas del backend y frontend |
| CA-07 | Las cinco asociaciones sin atributos editables rechazan PUT/PATCH (`405`) | **Comprobado** en scripts HTTP |
| CA-08 | DELETE es lógico; GET posterior y segundo DELETE dan `404` | **Comprobado**, con verificaciones adicionales en phpMyAdmin |
| CA-09 | Campos incorrectos, prohibidos o incompletos se rechazan (`422`) | **Comprobado** en los casos ejecutados; 400 del borrador no es el comportamiento confirmado de PATCH vacío |
| CA-10 | Frontend consume HTTP, ofrece selectores de FK y no usa PDO | **Verificado en código** y mediante uso manual de las vistas |
| CA-11 | El frontend sigue respondiendo con la API detenida, sin datos alternativos | **Pendiente de prueba de aceptación integral documentada** |
| CA-12 | Regresión completa de los seis recursos V1 y diez V2 tras merge | **Pendiente de evidencia posterior a integración** |
| CA-13 | Respuestas de error `500` sin filtrar SQL y manejo de fallos inesperados | **Manejo implementado en controladores revisados; falta ensayo específico controlado de fallo** |
| CA-14 | Quickstart, contratos, tareas y checklist coinciden con el código entregado | **En actualización documental** (`3_plan.md` a `9_checklist.md`) |
| CA-15 | Aprobación del equipo, PR/merge y eventual etiqueta `v2` | **Pendiente de comprobación**; no se da por cerrado solo por las pruebas locales |

**Resumen de evidencias conocidas:** se reportaron resultados aprobatorios para `grupo_investigacion` (19 comprobaciones HTTP), `semillero` (25), `docente` (30), `participa_grupo` (41), `participa_semillero` (46), `ods_linea` (50), `aa_linea` (50), `grupo_linea` (50) y `semillero_linea` (54). `ac_linea` se validó mediante peticiones manuales y consulta SQL de borrado lógico. Las diez pantallas V2 fueron utilizadas en el frontend según las pruebas comunicadas. Estos datos son **evidencia de pruebas durante el desarrollo**, no sustituyen por sí solos una regresión completa después del merge.

## 6. Clarificaciones y decisiones de V2 — compuerta de cierre

Se conserva la numeración C1–C12 del borrador para poder rastrear las decisiones sin reescribir su historia.

| ID | Tema | Resolución o estado real |
|---|---|---|
| **C1** | Esquema SQL y claves | **Resuelto a nivel de implementación:** se conservan las diez entidades con sus columnas, PK y FK de la copia SQL del módulo. **Pendiente de cierre:** cotejo formal con el `db/init.sql` de la rama final |
| **C2** | Rutas de las siete PK compuestas | **Resuelto:** dos segmentos en el orden de las claves SQL; implementados en `api_investigacion/index.php` |
| **C3** | Identificadores suministrados y manejo de asociaciones | **Resuelto:** las tres PK simples V2 se suministran por cliente, y las parejas se identifican con ambas claves sin `id` extra |
| **C4** | Retiro lógico de padres con hijos activos | **PENDIENTE DE DECISIÓN DEL EQUIPO.** Las implementaciones actuales de retiro no establecen una política general de bloqueo/cascada lógica; no presumir que InnoDB impide la inconsistencia lógica |
| **C5** | Referencias activas y FK opcionales | **Resuelto para creación y actualización de FK:** referencias no nulas deben existir y estar activas; `docente.linea_investigacion_principal` y `grupo_investigacion.universidsad` admiten `null` |
| **C6** | Roles y fechas de participación | **Resuelto en código:** rol no vacío, longitud máxima 15, fechas `YYYY-MM-DD` válidas, `fecha_fin` nullable y nunca anterior a `fecha_inicio`; sin catálogo enumerado impuesto |
| **C7** | Cierre heredado de V1 | **PARCIAL:** los seis CRUD están presentes. **Pendiente:** constancia de checklist final de V1, revisión de tag y regresión tras merge de V2 |
| **C8** | Constitución y mapa de versiones | **PENDIENTE DE REVISIÓN FORMAL:** confirmar que `1_constitution.md` y el mapa específico del equipo coincidan con las reglas y entregables acordados |
| **C9** | PUT/PATCH en cinco tablas puente simples | **Resuelto y probado:** `405`, porque solo existen PK y `activo` |
| **C10** | Asociación con PK retirada | **Resuelto y probado:** no se reactiva mediante POST; devuelve `409` |
| **C11** | Cliente HTTP y exigencia de cURL | **PENDIENTE:** el frontend usa actualmente `file_get_contents()` y `stream_context_create()`. Funciona mediante HTTP, pero debe validarse contra la exigencia metodológica de cURL |
| **C12** | Nombres históricos de columnas SQL | **Resuelto para V2 actual:** conservar literalmente `docente.nacionalidaad` y `grupo_investigacion.universidsad`; cualquier renombramiento posterior requiere migración aprobada |

**Responsables de la revisión final:** equipo del Proyecto de Investigación (Ángel y Miguel).  
**Fecha de actualización del documento:** 29 de octubre de 2026.  
**Estado de aprobación formal:** pendiente de confirmación del equipo y del flujo de integración de GitHub.

---

### Alcance de esta actualización

Este archivo **sustituye únicamente la especificación V2 (`2_spec.md`)**. No modifica el código ni implica que `3_plan.md`, `4_research.md`, `5_data_model.md`, `6_contracts.md`, `7_quickstart.md`, `8_tasks.md` o `9_checklist.md` ya estén actualizados. Las divergencias con dichos documentos se corregirán en los siguientes pasos del Spec Kit, de forma individual.
