# 9. Checklist — V2: Investigación

> **ESTADO AL 29-10-2026: DESARROLLO FUNCIONAL COMPLETADO; ACEPTACIÓN Y CIERRE FORMAL PENDIENTES.**
>
> **COMPUERTA 3 de requisitos y cierre.** Este documento **no queda aprobado** por haber sido actualizado. La compuerta de requisitos correspondía a la etapa **anterior a la programación**; no se declarará aprobada retroactivamente sin evidencia. La aceptación final debe verificarse **sobre las versiones integradas en `main` de ambos repositorios, antes del tag `v2`**, con revisión y firma del equipo.
>
> **Convención:** `[x]` = comprobado por inspección del código compartido o por resultados de pruebas comunicados durante el desarrollo; `[ ]` = decisión, ejecución, evidencia o aprobación todavía pendiente. Cuando un criterio original era mixto, se divide para distinguir la parte demostrada de la pendiente. Las pruebas descritas aquí **no se han vuelto a ejecutar** durante esta actualización documental.

## A. Especificación completa (requisito previo)

- [x] `2_spec.md` contempla **diez recursos V2 con FK** y la preservación de **seis recursos V1**, con alcance y criterios de aceptación identificados.
- [x] Las clarificaciones **C1–C12** están registradas con su estado real en `2_spec.md` §6: decisiones implementadas, decisiones parciales y cuestiones abiertas; no se presentan las pendientes como aprobadas.
- [ ] Obtener **aprobación expresa del equipo** de las clarificaciones y de los requisitos; especialmente **C4** (retiro de padres con hijos activos), **C7/C8** (cierre de V1 y constitución/mapa de versiones) y **C11** (exigencia de cURL).
- [x] Los nombres de las diez tablas V2, columnas, tipos, claves primarias, FK, nulabilidad y `activo` están documentados y contrastados con la **copia SQL utilizada durante el desarrollo** y con el código existente; ver `5_data_model.md`.
- [ ] Cotejar y firmar formalmente el modelo contra **`db/init.sql` vigente en la rama backend final**. El contraste con la copia `init(2).sql` / `init(3).sql` no sustituye este paso (**C1**).
- [x] Están implementadas las rutas de **dos segmentos** para las siete tablas de PK compuesta; el orden de cada clave figura en `6_contracts.md` y en `api_investigacion/index.php` (**C2/C3**).
- [ ] Registrar la **aprobación formal** de esas rutas y de sus criterios de aceptación por el equipo; la implementación no acredita por sí sola la compuerta previa.
- [x] La implementación rechaza con **`409`** el POST de una pareja compuesta ya existente, incluso si fue retirada lógicamente (**C10**).
- [ ] Definir y aprobar qué ocurre al **retirar un padre con asociaciones hijas activas**: bloquear el retiro, retirar también los hijos u otra política explícita; actualizar servicios y pruebas si procede (**C4**).
- [x] `6_contracts.md` documenta las rutas definitivas, métodos permitidos, cuerpos JSON, claves y respuestas observadas. Las anotaciones «pendiente» restantes identifican **asuntos reales de cierre**, no rutas V2 por inventar.
- [x] `3_plan.md` incluye un **chequeo técnico de constitución** que identifica la arquitectura real, el uso de variables de entorno y las desviaciones/pendientes.
- [ ] Verificar en ambos repositorios `.env` excluido de Git, `.env.example` sin secretos y obtener aprobación del chequeo de constitución y del mapa de versiones (**C8**).
- [x] Los **13 RF-V2** conservan criterios verificables en `2_spec.md`; el desarrollo por recurso aportó pruebas para sus comportamientos principales.
- [ ] Obtener **aceptación humana** de esos RF y completar evidencias de los criterios abiertos, sin afirmar aprobación anterior a la codificación.

## B. Refactorización de V1

- [x] Los repositorios y servicios de las **seis entidades de V1** están agrupados en `repositorios/Repositorios_<Entidad>/` y `servicios/Servicios_<Entidad>/`, junto a sus interfaces.
- [x] `api_investigacion/servicios/ensamblador.php` concentra la creación de PDO y la composición de las dependencias de los **16 recursos**.
- [x] En las carpetas compartidas se ajustaron las referencias `require_once` e inclusiones para la estructura por entidad; la carga y la sintaxis de los archivos se revisaron durante el desarrollo.
- [x] El enrutador final conserva las rutas de las seis entidades de V1 junto con las diez de V2; no se sustituyeron por un enrutador SQL genérico.
- [ ] Ejecutar y registrar una **comparación contractual y pruebas de regresión** de V1 sobre la versión integrada, para asegurar que las rutas y los sobres de respuesta sean exactamente los esperados tras todos los merges.
- [ ] Adjuntar evidencia reproducible de las **pruebas de capas, API y frontend de las seis entidades V1**, ejecutadas después de la integración final; las pruebas anteriores no sustituyen esta evidencia.
- [x] Las vistas V1 están distribuidas en carpetas por recurso, y existen las vistas y la plantilla compartida en `front_php/vistas/`.
- [x] La distribución incluye **Bootstrap y CSS locales** dentro de `front_php/publico/`, sin depender del CDN para cargar esos archivos.
- [ ] Comprobar en la **versión final integrada** que todas las pantallas de V1, su plantilla y los recursos estáticos funcionen correctamente, incluido el escenario sin acceso a CDN.

## C. API y reglas de V2

- [x] Los **diez recursos de V2** están implementados en las capas del backend y disponen de operaciones coherentes con su modelo; **cinco asociaciones simples no admiten edición** y responden `405` a PUT/PATCH. «CRUD terminado» no significa que todas las tablas tengan PUT/PATCH.
- [x] Los repositorios de V2 usan **PDO y consultas preparadas**; las reglas de negocio y consultas SQL no se colocaron en los controladores ni en el frontend.
- [x] Los servicios usan interfaces de repositorio, aplican las reglas de negocio y no son responsables de generar respuestas HTTP.
- [x] Las **tres PK simples y siete PK compuestas** se representan con sus columnas originales. En particular, `ac_linea.area_conocimiento` es alfanumérica; `participa_grupo` y `participa_semillero` mantienen nombres de PK distintos.
- [x] Los servicios verifican **existencia y estado activo** de las referencias suministradas; las pruebas por entidad rechazaron FK inexistentes o inactivas con `409` en los casos ejecutados.
- [ ] Aplicar y comprobar la política que apruebe el equipo para **padres ya retirados que conservan hijos activos**, si la decisión **C4** exige modificar la implementación. La validación de FK al crear **no resuelve por sí sola** el retiro posterior del padre.
- [x] Las asociaciones duplicadas se rechazan (`409`), incluida la reinserción de una pareja lógicamente retirada; no hay reactivación automática.
- [x] DELETE realiza **borrado lógico** (`activo = FALSE`); se verificaron GET posteriores `404` y DELETE repetidos `404` en las pruebas aplicables.
- [x] Los listados y las consultas de ficha de los recursos V2 **ocultan registros retirados**, según la implementación y las comprobaciones HTTP por recurso.
- [x] PUT/PATCH de **`participa_grupo` y `participa_semillero`** conservan la PK inmutable; PUT exige campos editables completos, PATCH permite subconjuntos y PATCH vacío produce **`422`** en las pruebas realizadas. La expectativa antigua de **`400`** para PATCH `{}` se reemplazó por el comportamiento real, registrado en `6_contracts.md`.
- [x] PUT/PATCH de **`ac_linea`, `ods_linea`, `aa_linea`, `grupo_linea` y `semillero_linea`** devuelven `405`: no tienen atributos editables fuera de la PK y `activo`.
- [x] Los scripts y solicitudes ejecutados durante V2 acreditan respuestas previstas como `200`, `201`, `204`, `404`, `405`, `409` y `422` en sus escenarios correspondientes; `400` se contempla para ciertas solicitudes/rutas según el controlador.
- [ ] Ejecutar y evidenciar pruebas deliberadas de **`400` y `500` donde apliquen**, especialmente errores internos sin filtrar SQL ni credenciales, y una **regresión conjunta de los 16 recursos** sobre `main`.
- [x] Se corrigió el nombre de la propiedad **`$semillero`** en `modelos/SemilleroLinea.php`, evitando la advertencia de propiedades dinámicas de PHP 8.3; las **54 comprobaciones posteriores** se aprobaron.

### Evidencia funcional por entidad V2

| Responsable | Entidad | Evidencia conocida durante el desarrollo |
|---|---|---|
| Ángel | `grupo_investigacion` | 19 comprobaciones HTTP aprobadas; frontend probado |
| Ángel | `semillero` | 25 comprobaciones HTTP aprobadas; frontend probado |
| Ángel | `docente` | 30 comprobaciones HTTP aprobadas; frontend probado |
| Ángel | `participa_grupo` | 41 comprobaciones HTTP aprobadas; frontend probado |
| Ángel | `participa_semillero` | 46 comprobaciones HTTP aprobadas; frontend probado |
| Miguel | `ac_linea` | Pruebas HTTP manuales de alta, consulta, duplicados, retiro y no reactivación; comprobación SQL del retiro; frontend probado |
| Miguel | `ods_linea` | 50 comprobaciones HTTP aprobadas; comprobación SQL del retiro; frontend probado |
| Miguel | `aa_linea` | 50 comprobaciones HTTP aprobadas; comprobación SQL del retiro; frontend probado |
| Miguel | `grupo_linea` | 50 comprobaciones HTTP aprobadas; frontend probado; pendiente anexar comprobación SQL manual específica si se exige |
| Miguel | `semillero_linea` | 54 comprobaciones HTTP aprobadas tras corrección PHP 8.3; comprobación SQL del retiro; frontend probado |

> Los resultados anteriores provienen de las ejecuciones y capturas compartidas durante el desarrollo. **No equivalen a repetir las pruebas después de integrar los Pull Requests.** Las consultas SQL verificaron `activo = 0` en los casos señalados; no se infiere una evidencia adicional que no esté documentada.

## D. Frontend V2

- [x] Existen **rutas y pantallas para los diez recursos V2** en `front_php/` y las interfaces se probaron durante el desarrollo. Las cinco asociaciones simples disponen de **listado, registro, detalle y retiro**, sin edición, de acuerdo con su contrato.
- [x] Los formularios V2 utilizan **selectores de FK** consultados a través de GET de la API, mostrando nombres y conservando los identificadores de las referencias activas.
- [x] Los formularios de asociaciones revisados **mantienen la selección ante errores** y muestran mensajes de validación o de fallo de la API; existe un tratamiento análogo en las pantallas desarrolladas de V2.
- [ ] Registrar pruebas de aceptación **por pantalla** de conservación de entradas y legibilidad de errores, incluida la regresión de formularios V1 después del merge.
- [x] La interfaz distingue un **listado vacío (`204`)** de una respuesta fallida y presenta mensajes de ausencia de registros, en lugar de tratar el vacío como error.
- [x] La acción visible de las pantallas utiliza **«Retirar»** para expresar el borrado lógico; no se ofrece eliminación física como operación estándar de V2.
- [x] El cliente `front_php/cliente_api.php` consume **HTTP/JSON**, mediante `stream_context_create()` y `file_get_contents()`; no contiene conexión directa PDO a MariaDB.
- [ ] Resolver **C11**: confirmar si la metodología exige **cURL** literalmente. Si es obligatorio, migrar el cliente y repetir las pruebas; si existe una excepción admitida, registrarla con aprobación del equipo.
- [ ] Verificar que **no existan secretos de base de datos incluidos en Git** o en los archivos de configuración del repositorio frontend; se inspeccionó la separación de responsabilidades, pero no una auditoría final del repositorio.
- [ ] Realizar la prueba **con la API detenida**: el frontend debe seguir respondiendo como aplicación, mostrar indisponibilidad y no presentar datos inventados ni intentar conectarse a la base de datos directamente. Conservar la evidencia.
- [x] La aplicación conserva archivos locales de Bootstrap, JavaScript y CSS personalizado; el funcionamiento visual se comprobó durante las pruebas manuales de las pantallas V2.

## E. Cierre e integración de V2

- [x] Se terminaron y probaron **por separado** las diez entidades V2 en backend y frontend; los seis recursos V1 permanecen en las carpetas finales compartidas.
- [ ] Ejecutar y documentar una **regresión de aceptación de los 16 recursos** sobre la versión integrada en `main` de **ambos repositorios**, incluyendo los métodos aplicables, selectores FK y retiro lógico. No marcarla como realizada por sumar pruebas individuales anteriores.
- [x] `7_quickstart.md` ahora contiene los comandos utilizados en el entorno de desarrollo, los puertos **8110/8111/8105/13330**, los endpoints y ejemplos de pruebas HTTP.
- [ ] Seguir el quickstart en un **entorno limpio** o reproducible a partir de la versión final de ambos repositorios y adjuntar los resultados, sin depender de datos temporales anteriores.
- [x] `2_spec.md` a `8_tasks.md` fueron actualizados para describir los diez recursos V2, el modelo, los contratos y las evidencias de desarrollo conocidas.
- [ ] Comparar la documentación con el **DDL y el código de las ramas finalmente fusionadas**, y comprobar que no haya cambios de esquema o contrato sin registrar.
- [ ] Verificar con Git que **`.env` no está versionado**, que `.env.example` no contiene secretos reales y que las variables empleadas en Docker corresponden a las descritas en la documentación.
- [ ] Revisar y dejar constancia de ramas, commits, **Pull Requests y merges a `main`** en backend y frontend. El trabajo con ramas durante el desarrollo no demuestra por sí solo la integración definitiva.
- [ ] Resolver formalmente las decisiones abiertas **C4 y C11**, más las aclaraciones sobre cierre V1, constitución y mapa de versiones (**C7/C8**).
- [ ] Obtener la **revisión humana** del equipo, cerrar los criterios de aceptación, registrar observaciones y firmas responsables; esta actualización no equivale a firma ni aceptación.
- [ ] Crear o verificar el **tag `v2` desde `main` solamente después** de la aprobación, si forma parte del procedimiento académico.

### Resumen de compuerta de V2

| Aspecto | Situación al 29-10-2026 | Acción para cerrar |
|---|---|---|
| Desarrollo backend V2 | **10/10 recursos implementados y probados por entidad** | Regresión integrada y resolución de C4 si afecta código |
| Desarrollo frontend V2 | **10/10 pantallas probadas durante el desarrollo** | Regresión posmerge; prueba API apagada; decisión C11 |
| V1 heredada | **6/6 recursos conservados en las carpetas** | Evidencia de regresión y cierre V1 |
| Datos y contratos | Documentados y contrastados con copia SQL y código compartido | Cotejo con `db/init.sql` vigente en `main` |
| Documentación Spec Kit | `2_spec.md` a `9_checklist.md` actualizados | Revisión y aprobación del equipo; `GUIA_IA2.md` si aplica |
| GitHub y entrega | **Sin evidencia documental suficiente de cierre final** | Verificar PR, merges, tag y aprobación |

**Resultado de esta revisión:** **CHECKLIST ACTUALIZADO — COMPUERTA NO APROBADA TODAVÍA.** La V2 tiene sus diez entidades funcionales implementadas; **el cierre formal se mantiene pendiente** hasta que se acrediten los puntos no marcados.

**Revisó (nombre y rol):** ____________________________________

**Fecha de revisión y aprobación:** _____________________________

**Resultado de la compuerta:** [ ] Aprobado  [ ] Aprobado con observaciones  [ ] No aprobado

**Observaciones de cierre / evidencias anexas:**

________________________________________________________________________

________________________________________________________________________

**Observaciones para V3:**

________________________________________________________________________
