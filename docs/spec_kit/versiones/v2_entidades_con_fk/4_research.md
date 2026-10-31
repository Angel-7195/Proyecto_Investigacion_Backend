# 4. Investigación y decisiones — V2: entidades con claves foráneas

> **ESTADO (29-10-2026): DECISIONES TÉCNICAS APLICADAS Y CONTRASTADAS CON EL CÓDIGO; CIERRE FORMAL PENDIENTE.**  
> Este documento actualiza la investigación de V2 a partir del borrador original de `4_research.md`, el `2_spec.md` y el `3_plan.md` actualizados, el modelo de datos de V2, la metodología del proyecto y las carpetas finales compartidas `api_investigacion/` y `front_php/`. Se conserva la numeración **D-V2-01 a D-V2-13** para mantener trazabilidad y se incorporan decisiones derivadas de las pruebas. **«Implementado» o «probado durante el desarrollo» no equivale a aprobación formal, regresión posterior al merge ni publicación del tag `v2`.**

## Criterio de registro y estados

Cada decisión describe **contexto, alternativas, decisión adoptada o pendiente, consecuencias, evidencia y estado**. Se distinguen:

- **Implementado y contrastado:** existe en las carpetas de código compartidas; cuando se indica, también hay resultados funcionales comunicados durante el desarrollo.
- **Pendiente de decisión:** requiere acuerdo explícito del equipo; no se presenta una propuesta como requisito ya ejecutado.
- **Pendiente de verificación de cierre:** la solución existe, pero faltan evidencia formal, regresión integrada o cotejo con la rama definitiva.

## D-V2-01 — Reorganizar por recurso sin modificar contratos

**Contexto.** V2 añade diez entidades con FK a los seis recursos sin FK de V1. Una organización plana de servicios y repositorios dificulta localizar clases y revisar las dependencias.

**Alternativas.** (a) Mantener carpetas planas; (b) agrupar interfaces y clases por recurso; (c) sustituirlas por controladores y repositorios SQL genéricos.

**Decisión adoptada: (b).** Se conservan clases específicas por entidad, organizadas en `Repositorios_<Entidad>/` y `Servicios_<Entidad>/`. Los modelos y controladores continúan en `modelos/` y `controladores/` y las rutas públicas siguen siendo explícitas.

**Consecuencias.** Requiere rutas `require_once` coherentes y pruebas de sintaxis. Favorece mantenimiento sin alterar por sí sola la semántica de los endpoints; el riesgo principal está en sobrescribir archivos compartidos al integrar ramas.

**Evidencia.** Estructura final de `api_investigacion/repositorios/`, `api_investigacion/servicios/`, `api_investigacion/controladores/`; `servicios/ensamblador.php` e `index.php` cargan y enrutan recursos V1/V2.

**Estado:** **IMPLEMENTADO Y CONTRASTADO** (16 recursos). **Pendiente:** regresión de integración final y revisión de PR.

## D-V2-02 — Conservar las capas y el ensamblador único

**Contexto.** Las validaciones de FK y los nuevos recursos no deben mezclar persistencia, reglas de negocio y respuestas HTTP.

**Alternativas.** (a) Combinar SQL, validaciones y HTTP en un solo archivo; (b) mantener controlador → servicio → repositorio, con interfaces e inyección de dependencias.

**Decisión adoptada: (b).** Cada recurso usa modelo, `IRepositorio*`, repositorio MariaDB con PDO, `IServicio*`, servicio y controlador. `servicios/ensamblador.php` construye las dependencias y comparte la conexión PDO.

**Consecuencias.** Se incrementa el número de archivos, pero las pruebas y modificaciones pueden concentrarse en la capa correspondiente. Los servicios de V2 reutilizan repositorios de los recursos padre para validar referencias activas.

**Evidencia.** `servicios/ensamblador.php` expone `crearConexion()` y `ensamblarControladores()`; las implementaciones de `ServicioSemilleroLinea`, `ServicioGrupoLinea` y las participaciones reciben interfaces de repositorio. Los controladores delegan el SQL.

**Estado:** **IMPLEMENTADO Y CONTRASTADO**. Se mantiene la comprobación general de regresión pendiente.

## D-V2-03 — Manejo de claves foráneas, duplicados y errores HTTP

**Contexto.** Las nuevas relaciones pueden fallar por PK duplicada, FK inexistente o inactiva, datos inválidos y problemas internos. No deben propagarse mensajes SQL a las pantallas.

**Alternativas.** (a) Exponer el mensaje original de PDO o convertir cualquier excepción en `500`; (b) validar referencias activas en servicio, traducir conflictos conocidos y reservar errores internos para fallos inesperados.

**Decisión adoptada: (b).** Los servicios consultan los registros relacionados activos antes de crear asociaciones. Los repositorios examinados capturan errores de integridad específicos de MariaDB: **1062** (PK/clave duplicada) y **1452** (FK inválida) y utilizan `ConflictoExcepcion`; los controladores devuelven los códigos HTTP correspondientes. Un cuerpo inválido se rechaza con `422` en los casos V2 comprobados.

**Consecuencias.** Las respuestas distinguen `404` (recurso no encontrado/retirado), `405` (método no permitido), `409` (conflicto) y `422` (validación). No debe confundirse un error de conexión con un conflicto de integridad: los errores no reconocidos deben seguir el manejo de error interno sin revelar SQL.

**Evidencia.** `excepciones/ConflictoExcepcion.php`, `excepciones/NoEncontradoExcepcion.php`, `repositorios/Repositorios_SemilleroLinea/RepositorioSemilleroLineaMariaDB.php`, servicios y controladores V2; los scripts HTTP de V2 aprobaron conflictos y validaciones de las asociaciones.

**Estado:** **IMPLEMENTADO Y PROBADO EN ESCENARIOS CONOCIDOS**. **Pendiente:** ensayo controlado de errores `500` y ausencia de filtración de detalles internos en la versión integrada.

## D-V2-04 — Respetar las claves primarias reales de las tablas puente

**Contexto.** Las siete asociaciones tienen PK de dos columnas. Inventar un identificador artificial cambiaría el SQL y rompería la correspondencia con las FK de la base de datos.

**Alternativas.** (a) Agregar una columna `id` a cada puente; (b) conservar las claves compuestas del DDL y utilizar ambas partes en las rutas de ficha.

**Decisión adoptada: (b).** Se utilizaron **tres PK simples V2 y siete compuestas**, sin crear columnas nuevas. Las fichas con PK compuesta se identifican mediante dos segmentos de URL, en este orden:

| Recurso | PK y segmentos de la ruta de ficha |
|---|---|
| `participa_grupo` | `docente_cedula` / `grupo_investigacion_id` |
| `participa_semillero` | `docente` / `semillero` |
| `ac_linea` | `linea_investigacion` / `area_conocimiento` |
| `ods_linea` | `linea_investigacion` / `ods` |
| `aa_linea` | `area_aplicacion` / `linea_investigacion` |
| `grupo_linea` | `grupo_investigacion` / `linea_investigacion` |
| `semillero_linea` | `semillero` / `linea_investigacion` |

En particular, `ac_linea.area_conocimiento` es **`VARCHAR(6)`**: la ruta y el modelo la tratan como texto, no como entero. Las PK simples `docente.cedula`, `grupo_investigacion.id` y `semillero.id` se reciben del cliente; no son `AUTO_INCREMENT` según el esquema revisado.

**Consecuencias.** Los contratos, enlaces HTML y operaciones DELETE deben conservar el orden de la PK. Cambiarlo produciría consultas a asociaciones incorrectas.

**Evidencia.** DDL consignado en `5_data_model.md` a partir de la copia SQL del módulo y bloques explícitos de rutas en `api_investigacion/index.php`; solicitudes de las asociaciones comprobadas durante V2.

**Estado:** **IMPLEMENTADO Y PROBADO**. **Pendiente de cierre:** cotejo formal de esta tabla con el `db/init.sql` definitivo de la rama del equipo.

## D-V2-05 — Desplegables FK cargados desde la API

**Contexto.** Escribir manualmente los identificadores de docentes, grupos, semilleros, líneas, áreas u ODS aumenta los errores y no informa si una referencia está retirada.

**Alternativas.** (a) Listas fijas en HTML o campos de texto; (b) consultar recursos relacionados mediante el cliente HTTP del frontend y mostrar opciones activas.

**Decisión adoptada: (b).** Los formularios consultan por HTTP los catálogos necesarios, muestran nombres legibles y envían los identificadores reales. Los servicios del backend **vuelven a comprobar** que las FK estén activas: no confían exclusivamente en los selectores.

**Consecuencias.** Si no hay opciones activas, el formulario no debe inventarlas. Si la API falla, el frontend debe diferenciar indisponibilidad de respuesta legítima sin datos.

**Evidencia.** `front_php/cliente_api.php`, las rutas `front_php/rutas/rutas_<entidad>.php` y los formularios `front_php/vistas/<entidad>/formulario.php` de las diez entidades; verificaciones manuales de los CRUD y scripts HTTP con FK inexistentes o inactivas.

**Estado:** **IMPLEMENTADO Y PROBADO EN FLUJOS DE USUARIO**. **Pendiente:** prueba integral documentada del frontend con la API apagada después del merge.

## D-V2-06 — Mantener PHP, HTML y Bootstrap local sin cambiar el stack

**Contexto.** El módulo académico se desarrolla en PHP puro y la V2 no requiere una SPA ni una conexión directa del frontend a MariaDB.

**Alternativas.** (a) Reescribir el frontend con otro framework o conectarlo a la BD; (b) conservar PHP server-side, plantillas HTML, CSS y Bootstrap local, consumiendo la API mediante HTTP.

**Decisión adoptada: (b), con una desviación tecnológica por resolver.** El frontend renderiza vistas PHP, utiliza `publico/bootstrap.min.css`, `publico/bootstrap.bundle.min.js` y `publico/css/estilos.css`, y se comunica con la API mediante `cliente_api.php`. No utiliza PDO ni SQL para consultar la base de datos.

**Diferencia observada:** el cliente HTTP usa **`stream_context_create()` + `file_get_contents()`**, no **cURL**. La sección 6 de `0_METODOLOGIA.md` prescribe cURL, por lo que **no se declara cumplimiento literal de esa exigencia**. Véase D-V2-13.

**Consecuencias.** Se conserva el frontend separado del backend y se limita el alcance visual de V2. Si el equipo debe usar cURL obligatoriamente, el cambio corresponde al cliente HTTP y requiere regresión de todas las pantallas, no una reescritura de los formularios.

**Evidencia.** `front_php/index.php`, `front_php/cliente_api.php`, `front_php/publico/`, `0_METODOLOGIA.md` §6 y `3_plan.md` §3.

**Estado:** **SEPARACIÓN Y STACK IMPLEMENTADOS; cURL PENDIENTE DE ADECUACIÓN O DECISIÓN FORMAL.**

## D-V2-07 — Borrado lógico y dependencias entre registros

**Contexto.** `DELETE` marca `activo = FALSE` y conserva la fila; InnoDB no aplica automáticamente restricciones de borrado físico cuando solamente cambia este indicador.

**Alternativas.** (a) Ejecutar DELETE físico y apoyarse en restricciones FK; (b) mantener borrado lógico y decidir qué ocurre si un padre tiene asociaciones hijas activas.

**Decisión adoptada parcialmente: (b).** Los recursos V2 utilizan retiro lógico y los listados ordinarios filtran `activo = TRUE`. Las referencias se verifican activas al **crear** relaciones. **No se ha formalizado una política general para retirar un padre que todavía tiene relaciones activas.**

**Consecuencias.** Puede persistir una asociación físicamente válida que apunte a un padre lógicamente retirado. No se debe inventar una cascada lógica, un bloqueo de retiro ni una reactivación para ocultar esta decisión pendiente.

**Evidencia.** Repositorios V2 con `UPDATE ... SET activo = FALSE` y filtros `WHERE activo = TRUE`; resultados HTTP y comprobaciones en phpMyAdmin. Las pruebas incluyeron el retiro lógico de registros de ensayo.

**Estado:** **BORRADO LÓGICO IMPLEMENTADO Y PROBADO; POLÍTICA PADRE/HIJOS PENDIENTE** (C4 de `2_spec.md`).

## D-V2-08 — No anticipar V3 ni V4

**Contexto.** Según el mapa de versiones, la V2 se concentra en relaciones FK y su gestión en API/frontend. JWT, roles, consultas multitabla, dashboard, imagen corporativa formal y despliegue pertenecen a hitos posteriores.

**Alternativas.** (a) Agregar esas capacidades durante V2; (b) limitarse a los diez recursos con FK y la organización necesaria para conservar V1.

**Decisión adoptada: (b).** La implementación de V2 añade las relaciones y formularios correspondientes sin introducir endpoints de autenticación ni funcionalidades finales de V3/V4.

**Consecuencias.** El alcance funcional es verificable por versión. No se da por completado un manual de marca o la publicación solamente por utilizar Bootstrap y CSS local.

**Evidencia.** `0_mapa_versiones.md`, `modulo_investigacion.md`, `0_METODOLOGIA.md` y las carpetas recibidas de la API y frontend.

**Estado:** **ALCANCE V2 APLICADO**; la revisión de la constitución y criterios formales de presentación sigue pendiente.

## Fuentes internas de decisión

- `modulo_investigacion.md` y `0_mapa_versiones.md`: distribución de las versiones y las diez entidades con FK.
- `0_METODOLOGIA.md` y `1_constitution.md`: SDD, compuertas, arquitectura PHP, separación de repositorios, cliente HTTP, pruebas y condiciones de cierre.
- `2_spec.md` **actualizado** (RF-V2-01 a RF-V2-13, clarificaciones C1–C12) y `3_plan.md` **actualizado**: alcance, reglas y arquitectura observada.
- `5_data_model.md` **aún pendiente de actualización**: inventario de tablas, PK y FK derivado de la copia SQL `init(2).sql`; requiere cotejo con el `db/init.sql` definitivo.
- `api_investigacion/` compartida: enrutador, ensamblador, modelos, interfaces, repositorios, servicios y controladores finales de V1+V2.
- `front_php/` compartida: enrutador de pantallas, cliente HTTP, rutas, vistas y archivos estáticos finales.
- Salidas de pruebas HTTP PowerShell, comandos `php -l` y verificaciones de `activo = 0` en phpMyAdmin comunicadas durante el desarrollo. **Son evidencias de trabajo local por entidad, no una certificación de regresión final en `main`.**

## D-V2-09 — Inventario definitivo de PK/FK del módulo V2

**Contexto.** El borrador ya identificaba, sobre la copia del SQL, **3 PK simples asignadas y 7 PK compuestas**. Se necesitaba comprobar esa distribución contra la implementación.

**Alternativas.** (a) Normalizar todas las tablas a un `id`; (b) respetar los identificadores SQL específicos y crear rutas acordes.

**Decisión adoptada: (b).** La API y los modelos siguen las PK y FK de la copia SQL, incluidas FK opcionales (`docente.linea_investigacion_principal`, `grupo_investigacion.universidsad`) y obligatorias (`semillero.grupo_investigacion`, componentes de las asociaciones). Se evita generar IDs artificiales.

**Consecuencias.** Las interfaces, validaciones y contratos deben usar nombres exactos, incluido `ods` para el recurso `ods_linea` aunque la tabla padre sea `objetivo_desarrollo_sostenible`.

**Evidencia.** `5_data_model.md`, `api_investigacion/modelos/` y repositorios V2.

**Estado:** **CONTRASTADO CON EL CÓDIGO COMPARTIDO; COTEJO FINAL DEL DDL PENDIENTE** (C1).

## D-V2-10 — Conservar nombres históricos de columnas SQL

**Contexto.** La copia del DDL utiliza `docente.nacionalidaad` y `grupo_investigacion.universidsad`, grafías poco habituales pero presentes en la base de datos.

**Alternativas.** (a) Corregirlas silenciosamente desde modelos o consultas; (b) conservarlas hasta realizar una migración autorizada.

**Decisión adoptada: (b).** La implementación y los contratos V2 respetan ambos nombres literales, sin alterar el esquema.

**Consecuencias.** Los desarrolladores deben utilizar las grafías existentes; una futura corrección requiere migración, revisión de interfaces, API, frontend, pruebas y documentación.

**Evidencia.** `5_data_model.md`, repositorios/modelos de `Docente` y `GrupoInvestigacion`, `2_spec.md` C12.

**Estado:** **IMPLEMENTADO**; cualquier renombramiento futuro queda fuera del alcance de esta actualización.

## D-V2-11 — Mantener rutas y organización ya existentes en los repositorios

**Contexto.** El borrador de V2 proponía reorganizar código, pero la V1 ya contaba con subcarpetas de repositorios/servicios y vistas/rutas por recurso. Volver a refactorizarlo añadía riesgo sin necesidad funcional.

**Alternativas.** (a) Imponer una estructura distinta por completo; (b) ampliar las convenciones reales existentes e integrar progresivamente cada recurso.

**Decisión adoptada: (b).** Los nuevos archivos siguen la estructura V1, y los cambios compartidos se concentran en `api_investigacion/index.php`, `api_investigacion/servicios/ensamblador.php`, `front_php/index.php`, `front_php/cliente_api.php`, `front_php/vistas/inicio.php` y `front_php/vistas/compartidas/plantilla.php`.

**Consecuencias.** Los cambios sucesivos sobre archivos compartidos exigen comparar la versión más reciente antes de sobrescribirla; el trabajo en ramas y mediante PR es relevante para evitar pérdida de rutas.

**Evidencia.** Árboles actuales de ambos proyectos y organización descrita en `3_plan.md` §§2–4.

**Estado:** **IMPLEMENTADO Y CONTRASTADO**; la integración final de ramas aún necesita verificación.

## D-V2-12 — Operaciones de asociaciones, claves inmutables y no reactivación

**Contexto.** Cinco asociaciones simples solo tienen dos componentes de PK y `activo`; otras dos participaciones incluyen rol y fechas. Un retiro lógico conserva la PK y puede generar conflicto al intentar insertar otra vez la misma pareja.

**Alternativas.** (a) Permitir cambios de PK por PUT/PATCH o reactivar automáticamente parejas retiradas; (b) mantener PK inmutable, actualizar únicamente campos no identificadores y devolver conflicto al intentar repetir una PK retirada.

**Decisión adoptada: (b).**

1. `ac_linea`, `ods_linea`, `aa_linea`, `grupo_linea` y `semillero_linea`: **GET, POST y DELETE lógico**; PUT/PATCH responden **`405`** por ausencia de campos editables.
2. `participa_grupo` y `participa_semillero`: GET, POST, PUT, PATCH y DELETE; solo se reemplazan/actualizan **`rol`, `fecha_inicio` y `fecha_fin`**, sin cambiar las dos claves; `fecha_fin` admite `null` y se valida su coherencia con la fecha inicial.
3. Una pareja retirada **no se reactiva automáticamente**; intentar crearla nuevamente con POST devuelve **`409`**. El segundo DELETE devuelve `404`.

**Consecuencias.** Para asociar otros identificadores se debe crear una pareja nueva y retirar la anterior, no modificar la PK. Esta regla ha de describirse igual en `6_contracts.md` y en los formularios.

**Evidencia.** Controladores y servicios de las siete asociaciones; pruebas manuales de `ac_linea`, scripts de `ods_linea`, `aa_linea`, `grupo_linea`, `semillero_linea` y pruebas de las dos participaciones.

**Estado:** **RESUELTO, IMPLEMENTADO Y PROBADO** (C9 y C10). **Diferencia de validación:** PATCH vacío `{}` devuelve `422` en los controladores V2 revisados, no el `400` descrito en algunos borradores; al actualizar `6_contracts.md` debe usarse el comportamiento real o acordarse antes un cambio de código.

## D-V2-13 — Cliente HTTP frente a la exigencia académica de cURL

**Contexto.** `0_METODOLOGIA.md` §6 establece frontend PHP que consume la API mediante **cURL**. En la implementación compartida, el cliente utiliza los streams HTTP nativos de PHP.

**Alternativas.** (a) Conservar `stream_context_create()` y `file_get_contents()` y documentar explícitamente la desviación; (b) migrar `front_php/cliente_api.php` a cURL y volver a probar todas las pantallas.

**Decisión de implementación observada: (a).** El frontend actual funciona mediante HTTP y no accede directamente a la base de datos, pero **la exigencia literal de cURL sigue sin cumplirse**. No se ha documentado dispensa o aprobación del equipo para esa diferencia.

**Consecuencias.** La separación API/frontend está implementada; el cumplimiento metodológico de cURL continúa pendiente. Si se elige (b), verificar soporte cURL de la imagen PHP y ejecutar regresión de respuestas HTTP, formularios y errores de conexión.

**Evidencia.** `front_php/cliente_api.php` (`stream_context_create`, `file_get_contents`) y `0_METODOLOGIA.md` §6.

**Estado:** **PENDIENTE DE ADECUACIÓN O APROBACIÓN FORMAL** (C11). No presentar cURL como implementado.

## D-V2-14 — Verificación por recurso y limpieza de datos de prueba

**Contexto.** Las entidades usan PK y FK que pueden depender de recursos creados antes. Probar con datos permanentes o retirados previamente ocasiona falsos fallos por duplicado o integridad.

**Alternativas.** (a) Probar únicamente de manera manual sobre registros existentes; (b) realizar validaciones de sintaxis, scripts HTTP con datos temporales coherentes y comprobación manual SQL de borrado lógico.

**Decisión aplicada: (b).** El equipo verificó `php -l`, reconstruyó los servicios Docker modificados, probó endpoints de colección y ejecutó scripts HTTP que crean datos de prueba mediante la API y los retiran lógicamente. El campo `activo = 0` se verificó en phpMyAdmin cuando correspondía. Las tablas con FK de varios niveles, como `semillero_linea`, necesitaron crear un grupo padre antes del semillero temporal.

**Evidencia reportada durante V2:**

| Recurso | Comprobaciones HTTP comunicadas |
|---|---:|
| `grupo_investigacion` | 19 |
| `semillero` | 25 |
| `docente` | 30 |
| `participa_grupo` | 41 |
| `participa_semillero` | 46 |
| `ac_linea` | Pruebas HTTP manuales y SQL de borrado lógico |
| `ods_linea` | 50 |
| `aa_linea` | 50 |
| `grupo_linea` | 50 |
| `semillero_linea` | 54 (después de la corrección de PHP 8.3) |

El frontend de las diez entidades fue probado manualmente durante el desarrollo. Las cantidades representan **comprobaciones por recurso**, no una única suite end-to-end de todo V1+V2.

**Consecuencias.** Los datos temporales quedan físicamente en MariaDB con `activo = FALSE` tras el retiro. Una ejecución interrumpida puede requerir limpieza lógica manual; los scripts no deben borrar tablas ni volúmenes. Para Docker con `COPY . .` se reconstruyen los servicios después de copiar PHP.

**Estado:** **PRUEBAS POR RECURSO REALIZADAS; REGRESIÓN INTEGRAL POSMERGE PENDIENTE**.

## D-V2-15 — Compatibilidad con PHP 8.3 y propiedades declaradas

**Contexto.** Durante las primeras pruebas de `semillero_linea`, la creación de una asociación produjo un aviso de PHP 8.3 por intentar asignar una propiedad dinámica `$semillero`, lo que escribió salida antes de los encabezados HTTP y alteró el código recibido por el script.

**Alternativas.** (a) Ocultar avisos para que no aparezcan; (b) declarar correctamente la propiedad en el modelo y repetir las pruebas.

**Decisión aplicada: (b).** En `modelos/SemilleroLinea.php` se corrigió la propiedad declarada a `private int $semillero;`, coherente con el constructor y los métodos que la utilizan. No se cambió la arquitectura ni se suprimieron las advertencias globalmente.

**Consecuencias.** El modelo deja de producir ese aviso y la API puede enviar el estado HTTP `201` sin contenido prematuro en ese flujo. La corrección debe conservarse en el código integrado.

**Evidencia.** `api_investigacion/modelos/SemilleroLinea.php` compartido y la repetición de `pruebas_semillero_linea_v2.ps1` con **54/54 comprobaciones aprobadas**.

**Estado:** **CORREGIDO Y VERIFICADO EN PRUEBAS REALES**.

## Resumen de decisiones y compuerta pendiente

| Decisión | Estado actual | Relación con especificación/plan |
|---|---|---|
| D-V2-01, 02, 11 | Arquitectura y carpetas implementadas | RF-V2-10, RF-V2-13; `3_plan.md` §§1–4 |
| D-V2-03, 05 | FK activas, conflictos y selectores implementados | RF-V2-03, 07, 08 |
| D-V2-04, 09, 10 | PK/FK y nombres SQL respetados; cotejo final pendiente | RF-V2-02, 09; C1, C2, C3, C12 |
| D-V2-06, 08 | PHP y separación HTTP implementados; alcance V3/V4 excluido | RF-V2-10, 12; C8, C11 |
| D-V2-07 | Borrado lógico probado; política padre/hijos sin resolver | RF-V2-06, 07; **C4 pendiente** |
| D-V2-12 | PUT/PATCH de puentes `405`, sin reactivación `409` | RF-V2-04, 05, 07, 09; C9 y C10 |
| D-V2-13 | Uso literal de cURL sin cumplir | **C11 pendiente** |
| D-V2-14, 15 | Pruebas por entidad y corrección de PHP 8.3 | `3_plan.md` §6 |

**Condiciones aún abiertas antes de declarar V2 cerrada:**

1. Decidir la política de retiro lógico de padres con asociaciones activas (**C4**), documentar sus contratos y probarla si exige cambios.
2. Resolver el requisito explícito de **cURL** (**C11**), o dejar constancia formal de la decisión autorizada.
3. Cotejar el `db/init.sql` exacto de la rama final con `5_data_model.md` y el código (**C1**).
4. Ejecutar y registrar una **regresión integrada V1+V2 después del merge**, incluido frontend con API apagada y tratamiento controlado de errores internos.
5. Verificar la revisión/aprobación humana del Spec Kit, los PR y merges de ambos repositorios y el eventual **tag `v2`** en `main`. No se presume completado ninguno de estos pasos.

**Alcance de esta actualización:** se modifica **solo** `4_research.md`. El código PHP y los demás documentos del Spec Kit no se alteran. `5_data_model.md`, `6_contracts.md`, `7_quickstart.md`, `8_tasks.md` y `9_checklist.md` continuarán revisándose documento por documento.
