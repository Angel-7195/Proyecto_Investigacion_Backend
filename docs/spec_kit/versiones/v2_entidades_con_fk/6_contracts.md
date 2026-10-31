# 6. Contratos API — V2: diez entidades con claves foráneas

> **ESTADO (29-10-2026): CONTRATOS V2 IMPLEMENTADOS Y COMPROBADOS POR RECURSO; REGRESIÓN INTEGRAL Y CIERRE FORMAL PENDIENTES.**
> Documento actualizado a partir de `6_contracts.md` original, de `2_spec.md`, `3_plan.md`, `4_research.md` y `5_data_model.md` revisados, de los archivos finales `api_investigacion/index.php`, modelos y controladores, y de las pruebas HTTP comunicadas durante el desarrollo. **Es una ampliación (delta) de los contratos de V1**, que no se sustituyen. Los ejemplos utilizan identificadores **ilustrativos**: solo son válidos para ejecutar peticiones cuando los padres indicados existen y están activos.

## 0. Convenciones HTTP heredadas y precisadas

La API responde en JSON UTF-8 para peticiones con contenido (cabecera `Content-Type: application/json; charset=utf-8`). El cuerpo de las solicitudes de escritura se envía como `application/json`. La API no expone `activo` para crear o editar registros: su valor se controla internamente.

| Código | Uso observado o definido en los controladores V2 |
|---|---|
| `200 OK` | GET con datos; PUT/PATCH exitoso donde aplica; DELETE lógico exitoso. |
| `201 Created` | POST válido que crea el registro o la asociación. |
| `204 No Content` | GET de colección sin registros activos; **sin cuerpo de respuesta**. |
| `400 Bad Request` | Algunos controladores de PK simple lo usan al interceptar ciertos `InvalidArgumentException` procedentes del servicio. **No es el resultado universal de PATCH vacío**. |
| `404 Not Found` | Ficha inexistente o retirada; DELETE repetido; ruta no reconocida. |
| `405 Method Not Allowed` | Verbo no admitido en una ruta conocida; PUT/PATCH de las cinco asociaciones sin campos editables. |
| `409 Conflict` | PK/pareja duplicada, intento de reinserción de asociación retirada, o referencia FK inexistente/inactiva en los casos manejados por los servicios. |
| `422 Unprocessable Content` | JSON inválido, campos faltantes o extra, tipos/formatos incorrectos y PATCH sin datos en los controladores V2 revisados. |
| `500 Internal Server Error` | Error interno no clasificado; la respuesta de los controladores evita publicar directamente las excepciones SQL. |

**Precisión importante:** la redacción del borrador anterior que esperaba `400` para todo PATCH `{}` ya no corresponde a la implementación: por ejemplo, `participa_grupo` y `participa_semillero` devuelven `422`. No se debe convertir un fallo imprevisto de base de datos en `409` indiscriminadamente: el código distingue conflictos de integridad conocidos y otros errores.

### 0.1. Formas de respuesta observadas

**GET de colección con resultados (HTTP 200)**:

```json
{
  "recurso": "semillero_linea",
  "total": 1,
  "datos": [
    {"semillero": 20, "linea_investigacion": 12}
  ]
}
```

**GET de ficha (HTTP 200)**: devuelve **directamente** los campos del modelo (`toArray()`), sin el sobre `datos`:

```json
{"semillero": 20, "linea_investigacion": 12}
```

**POST exitoso (HTTP 201)**:

```json
{
  "estado": 201,
  "mensaje": "Asociación de semillero y línea creada.",
  "datos": {"semillero": 20, "linea_investigacion": 12}
}
```

**PUT/PATCH/DELETE exitosos (HTTP 200, cuando aplica)**:

```json
{"estado": 200, "mensaje": "Asociación retirada.", "filasAfectadas": 1}
```

**Cuerpo inválido (HTTP 422)**:

```json
{
  "estado": 422,
  "mensaje": "Datos inválidos.",
  "errores": ["El campo semillero es obligatorio."]
}
```

**Conflicto de asociación (HTTP 409)**:

```json
{
  "estado": 409,
  "mensaje": "Conflicto de datos.",
  "detalle": "La combinación ya existe o alguna referencia no está activa."
}
```

Los **mensajes y `detalle` varían según el controlador**; los ejemplos muestran las formas empleadas, no un texto único obligatorio para los dieciséis recursos. Un GET `204` no devuelve `{}`, `[]` ni un JSON de estado. El diagnóstico `GET /` responde con `mensaje`, `version` (`v2-en-desarrollo` en el código examinado) y `recursos` (16 nombres); ese rótulo de diagnóstico no acredita publicación formal de V2.

## 1. Rutas definitivas y claves

Las rutas son **explícitas** en `api_investigacion/index.php`: no existe una ruta dinámica genérica `/api/{tabla}`. La dirección de las fichas con PK compuesta incluye **dos segmentos, en el orden exacto de la PK**.

| Recurso V2 | Ruta de colección | Ruta individual | Clave/segmentos |
|---|---|---|---|
| Docente | `/api/docente` | `/api/docente/{cedula}` | `cedula`: INT |
| Grupo de investigación | `/api/grupo_investigacion` | `/api/grupo_investigacion/{id}` | `id`: INT |
| Semillero | `/api/semillero` | `/api/semillero/{id}` | `id`: INT |
| Participación en grupo | `/api/participa_grupo` | `/api/participa_grupo/{docente_cedula}/{grupo_investigacion_id}` | INT + INT |
| Participación en semillero | `/api/participa_semillero` | `/api/participa_semillero/{docente}/{semillero}` | INT + INT |
| Grupo–línea | `/api/grupo_linea` | `/api/grupo_linea/{grupo_investigacion}/{linea_investigacion}` | INT + INT |
| Semillero–línea | `/api/semillero_linea` | `/api/semillero_linea/{semillero}/{linea_investigacion}` | INT + INT |
| Área de conocimiento–línea | `/api/ac_linea` | `/api/ac_linea/{linea_investigacion}/{area_conocimiento}` | INT + VARCHAR(6) |
| ODS–línea | `/api/ods_linea` | `/api/ods_linea/{linea_investigacion}/{ods}` | INT + INT |
| Área de aplicación–línea | `/api/aa_linea` | `/api/aa_linea/{area_aplicacion}/{linea_investigacion}` | INT + INT |

**Particularidad `ac_linea`:** `area_conocimiento` es una **cadena**, por ejemplo `1A01`; no debe convertirse a número. La ruta la decodifica con `rawurldecode()`. Para las otras claves compuestas, el enrutador espera enteros positivos en la URL. Una ruta que **no coincide con el patrón** puede responder `404` desde el enrutador, antes de llegar a la validación del controlador.

### 1.1. Matriz real de operaciones

| Grupo de recursos | Colección | Ficha individual |
|---|---|---|
| `docente`, `grupo_investigacion`, `semillero` | GET `200/204`, POST `201` | GET `200`, PUT `200`, PATCH `200`, DELETE `200` |
| `participa_grupo`, `participa_semillero` | GET `200/204`, POST `201` | GET `200`, PUT `200`, PATCH `200`, DELETE `200` |
| `grupo_linea`, `semillero_linea`, `ac_linea`, `ods_linea`, `aa_linea` | GET `200/204`, POST `201` | GET `200`, DELETE `200`; PUT y PATCH → `405` |

Las operaciones de la tabla expresan **éxito**; los errores asociados a cada verbo se detallan en §§0 y 5. Un `POST` sobre una ficha y un `PUT`, `PATCH` o `DELETE` sobre la colección responden `405` donde el enrutador lo establece.

## 2. Contratos de entidades con clave primaria simple

### 2.1. `docente`

**Clave:** `cedula` (entero positivo suministrado por el cliente; **no** autogenerado). **FK opcional:** `linea_investigacion_principal` → `linea_investigacion.id`.

- **POST `/api/docente`:** requiere `cedula` y los quince campos editables que figuran abajo. `cat_minciencia` y `linea_investigacion_principal` aceptan `null`, pero el validador de POST/PUT exige que sus claves estén presentes; el resto de campos se exige con su tipo válido.
- **PUT `/api/docente/{cedula}`:** reemplaza los quince campos editables, sin `cedula` ni `activo` en el cuerpo.
- **PATCH `/api/docente/{cedula}`:** acepta uno o más campos editables; `{}` es `422`.
- **No editables:** `cedula` y `activo`; un FK no nulo debe existir y estar activo.

Ejemplo de **POST** (datos ilustrativos):

```json
{
  "cedula": 1000001,
  "nombres": "Ana",
  "apellidos": "Gómez",
  "genero": "Femenino",
  "cargo": "Docente",
  "fecha_nacimiento": "1990-06-15",
  "correo": "ana@example.org",
  "telefono": "3001234567",
  "url_cvlac": "https://example.org/cvlac",
  "fecha_actualizacion": "2026-10-01",
  "escalafon": "Asociado",
  "perfil": "Investigación aplicada en software",
  "cat_minciencia": null,
  "conv_minciencia": "2025",
  "nacionalidaad": "Colombiana",
  "linea_investigacion_principal": null
}
```

**Nota de compatibilidad:** `nacionalidaad` es la grafía real del SQL y el controlador. No renombrarla unilateralmente a `nacionalidad`. Los textos tienen los límites del DDL; `correo` se valida como dirección válida y las fechas en `YYYY-MM-DD`.

### 2.2. `grupo_investigacion`

**Clave:** `id` (entero suministrado por el cliente). **FK opcional:** `universidsad` → `universidad.id`.

- **POST:** obligatorios `id`, `nombre`, `fecha_fundacion`, `interno` y `ambito`. Opcionales `url_gruplac`, `categoria`, `convocatoria` y `universidsad` (pueden omitirse en POST o enviarse como `null`).
- **PUT:** todos los ocho campos editables deben figurar en el cuerpo, incluidos los opcionales si se sustituyen por `null`; no se incluye `id`.
- **PATCH:** uno o varios campos editables; `{}` es `422`.
- `interno` se valida con los enteros `0` o `1`; `fecha_fundacion` usa `YYYY-MM-DD`. Una universidad no nula debe existir y estar activa.

Ejemplo de **POST**:

```json
{
  "id": 1001,
  "nombre": "Grupo de investigación de ejemplo",
  "fecha_fundacion": "2024-01-15",
  "interno": 1,
  "ambito": "Institucional",
  "universidsad": null
}
```

**Nota:** el nombre **`universidsad`** es literal en el esquema y el controlador, no `universidad`; no modificarlo en este contrato sin migración acordada.

### 2.3. `semillero`

**Clave:** `id` (entero positivo suministrado). **FK obligatoria:** `grupo_investigacion` → `grupo_investigacion.id`.

**POST**:

```json
{
  "id": 2001,
  "nombre": "Semillero de ejemplo",
  "fecha_fundacion": "2025-02-10",
  "grupo_investigacion": 1001
}
```

- **POST:** los cuatro campos del ejemplo son obligatorios; el grupo debe existir y estar activo.
- **PUT:** requiere `nombre`, `fecha_fundacion`, `grupo_investigacion` (sin `id`).
- **PATCH:** uno o más de esos tres campos; `{}` devuelve `422`.
- **Inmutables por solicitud de edición:** `id` y `activo`.

## 3. Contratos de participación con clave compuesta y atributos editables

Ambas tablas comparten las reglas: `rol` es texto no vacío de máximo **15** caracteres; `fecha_inicio` es `YYYY-MM-DD`; `fecha_fin` es `YYYY-MM-DD` o `null`; si `fecha_fin` existe, no puede anteceder a `fecha_inicio`. Para PUT/PATCH solo son editables **`rol`, `fecha_inicio` y `fecha_fin`**. La validación de coherencia también se aplica sobre los valores resultantes en el servicio. Los dos registros padre deben existir y estar activos al crear la participación.

### 3.1. `participa_grupo`

**POST `/api/participa_grupo`:**

```json
{
  "docente_cedula": 1000001,
  "grupo_investigacion_id": 1001,
  "rol": "Investigador",
  "fecha_inicio": "2026-01-15",
  "fecha_fin": null
}
```

- **Ficha:** `/api/participa_grupo/1000001/1001` (primero docente, después grupo).
- **PUT:** requiere el cuerpo completo de `rol`, `fecha_inicio`, `fecha_fin`; **no** incluye `docente_cedula` ni `grupo_investigacion_id`.
- **PATCH:** acepta un subconjunto no vacío; admite `{"fecha_fin":null}`; `{}` y las claves editadas indebidamente dan `422`.
- **DELETE:** retiro lógico de esa pareja; GET posterior `404`; segundo DELETE `404`.

### 3.2. `participa_semillero`

**POST `/api/participa_semillero`:**

```json
{
  "docente": 1000001,
  "semillero": 2001,
  "rol": "Investigador",
  "fecha_inicio": "2026-01-15",
  "fecha_fin": null
}
```

- **Ficha:** `/api/participa_semillero/1000001/2001` (primero docente, después semillero).
- **PUT:** exige `rol`, `fecha_inicio`, `fecha_fin`; no permite `docente` ni `semillero` en el cuerpo.
- **PATCH:** uno o más campos editables; `{}` y cambios de PK/`activo` devuelven `422`. Se admite `fecha_fin: null`.
- **DELETE:** igual que en `participa_grupo`, retiro lógico identificado por la pareja.

Los ejemplos describen los **nombres JSON reales**, que difieren entre ambas tablas: `docente_cedula`/`grupo_investigacion_id` **no** deben reemplazarse por `docente`/`semillero` en `participa_grupo`.

## 4. Contratos de las cinco asociaciones simples

Estas tablas tienen **solamente los dos componentes de PK y el indicador interno `activo`**. Por eso:

1. **POST:** admite **exactamente las dos claves**; no se acepta `activo` ni un campo adicional.
2. **GET de ficha:** usa las dos claves en el orden indicado; devuelve únicamente esos dos campos.
3. **PUT/PATCH de ficha:** responde `405` incluso si se envía un cuerpo, porque no hay atributos editables.
4. **DELETE de ficha:** `200` con `filasAfectadas`; la fila queda con `activo = FALSE` en MariaDB.
5. **Segundo DELETE o GET después de retirar:** `404`. Un POST de la misma pareja, incluso retirada, responde `409`; **no hay reactivación implícita**.

| Recurso | JSON obligatorio en POST (ejemplo) | Ruta de ficha de ejemplo |
|---|---|---|
| `ac_linea` | `{"linea_investigacion":12,"area_conocimiento":"1A01"}` | `/api/ac_linea/12/1A01` |
| `ods_linea` | `{"linea_investigacion":12,"ods":4}` | `/api/ods_linea/12/4` |
| `aa_linea` | `{"area_aplicacion":7,"linea_investigacion":12}` | `/api/aa_linea/7/12` |
| `grupo_linea` | `{"grupo_investigacion":1001,"linea_investigacion":12}` | `/api/grupo_linea/1001/12` |
| `semillero_linea` | `{"semillero":2001,"linea_investigacion":12}` | `/api/semillero_linea/2001/12` |

**Referencias FK que deben estar activas en POST:**

| Recurso | Padre 1 | Padre 2 |
|---|---|---|
| `ac_linea` | `linea_investigacion.id` | `area_conocimiento.id` (cadena VARCHAR(6)) |
| `ods_linea` | `linea_investigacion.id` | `objetivo_desarrollo_sostenible.id` |
| `aa_linea` | `area_aplicacion.id` | `linea_investigacion.id` |
| `grupo_linea` | `grupo_investigacion.id` | `linea_investigacion.id` |
| `semillero_linea` | `semillero.id` | `linea_investigacion.id` |

En los controladores V2 contrastados, las claves enteras se validan como enteros positivos (no cadenas numéricas en el JSON). Los scripts HTTP de las asociaciones comprobaron `422` para claves ausentes o incorrectas y `409` para referencias no activas.

## 5. Errores, reglas de integridad y borrado lógico

### 5.1. Reglas comunes

- **Filtrado de activos:** GET de colección y ficha omite registros con `activo = FALSE`.
- **Claves editables:** PK de entidades simples solo en POST; PK compuestas únicamente en POST y URL de ficha. Ningún controlador V2 acepta modificar `activo` por solicitud.
- **Restricciones FK:** los servicios validan la existencia y el estado activo del padre antes de crear vínculos; algunos casos de FK duplicadas/inexistentes se traducen a `409` mediante excepciones específicas.
- **Integridad SQL:** se preservan las PK/FK declaradas en MariaDB y las consultas preparadas PDO. Un `activo = FALSE` no elimina la restricción de unicidad.
- **Retiro lógico:** `DELETE` modifica `activo`; no se emite borrado físico. Las consultas SQL manuales de la V2 verificaron `activo = 0` para las asociaciones probadas.
- **Reinserción de pares retirados:** las siete asociaciones no reactivan la misma combinación por POST; devuelve `409`.

### 5.2. Respuestas no exitosas (ejemplos de contrato)

| Caso | Respuesta esperada |
|---|---|
| POST duplicado de asociación o pareja retirada | `409`, sobre `estado`/`mensaje`/`detalle` |
| POST con FK padre inexistente o inactiva | `409` en los casos controlados y comprobados |
| POST con campo faltante, extra, `activo`, tipo incorrecto | `422`, sobre `estado`/`mensaje`/`errores` |
| JSON mal formado | `422`, producido por `index.php` antes del controlador |
| PUT/PATCH sobre una de las cinco asociaciones simples | `405`, mensaje del controlador correspondiente |
| GET de ficha inexistente/inactiva | `404`, sobre `estado`/`mensaje`/`detalle` |
| GET de ruta inexistente | `404`, mensaje `Ruta no encontrada.` y detalle de método/ruta |
| DELETE de ficha ya retirada | `404` |
| PATCH vacío de participaciones | `422`, **no 400** |
| Error interno no clasificado | `500`, sobre genérico sin SQL de detalle |

**Decisión abierta:** no se ha acordado una política general sobre qué debe suceder si se retira una entidad padre que todavía conserva asociaciones activas (rechazo, cascada lógica o revisión de enlaces). Las pruebas de creación validan FK activas, pero eso **no implica** que haya una política global de cascada lógica al retirar padres. No documentar una cascada como implementada sin aprobarla y probarla.

## 6. Contrato frontend, comprobaciones y compatibilidad con V1

### 6.1. Selectores para FK en `front_php`

- Los formularios consumen GET de `universidad`, `grupo_investigacion`, `semillero`, `docente`, `linea_investigacion`, `area_conocimiento`, `objetivo_desarrollo_sostenible` y `area_aplicacion` según la entidad.
- Los desplegables presentan los registros activos y envían los identificadores en el JSON de POST/PUT/PATCH aplicables.
- Si GET devuelve `204`, no se deben inventar registros ni mostrar datos falsos; si la API está desconectada, se informa el error en la pantalla.
- Los formularios de participación permiten editar rol y fechas, pero dejan las claves compuestas inmutables.
- Las cinco asociaciones simples ofrecen registro, listado, detalle y retiro; no muestran edición que el backend no admite.
- El cliente `front_php/cliente_api.php` utiliza **`stream_context_create()` y `file_get_contents()`** para llamadas HTTP; no emplea cURL actualmente. Por ello, la exigencia literal de cURL de `0_METODOLOGIA.md` sigue pendiente de revisión/adecuación, aunque la separación frontend–API sí está implementada.

### 6.2. Evidencias de comprobación por entidad

Se ejecutaron pruebas HTTP de V2 sobre Docker/MariaDB para cada entidad durante su desarrollo. Se verificaron creación, consulta, actualización (donde existe), duplicados, referencias inválidas o inactivas, retiro lógico y rechazo de reinserciones. Para las cinco asociaciones simples se documentaron comprobaciones manuales e integrales; en particular:

| Entidad | Evidencia reportada durante el desarrollo |
|---|---|
| `grupo_investigacion` | 19 comprobaciones HTTP aprobadas |
| `semillero` | 25 comprobaciones HTTP aprobadas |
| `docente` | 30 comprobaciones HTTP aprobadas |
| `participa_grupo` | 41 comprobaciones HTTP aprobadas |
| `participa_semillero` | 46 comprobaciones HTTP aprobadas |
| `ac_linea` | Comprobaciones manuales de GET/POST/duplicado/DELETE/GET retirado y `activo=0` |
| `ods_linea` | 50 comprobaciones HTTP aprobadas; SQL `activo=0` |
| `aa_linea` | 50 comprobaciones HTTP aprobadas; SQL `activo=0` |
| `grupo_linea` | 50 comprobaciones HTTP aprobadas; frontend probado; consulta SQL manual pendiente de evidencia explícita en este cierre |
| `semillero_linea` | 54 comprobaciones HTTP aprobadas tras corregir propiedad del modelo para PHP 8.3; SQL `activo=0` |

Esta tabla registra **evidencia de pruebas por recurso** comunicada durante el desarrollo. No es evidencia por sí sola de regresión de **los 16 recursos integrados en `main` después del merge**.

### 6.3. Lista de verificación contractual y pendientes

- [x] Definidas en `index.php` las **diez rutas V2** de colección y sus fichas.
- [x] Confirmadas **tres claves simples y siete compuestas** sin identificadores adicionales.
- [x] Diferenciadas **cinco entidades editables** (tres principales + dos participaciones) de **cinco asociaciones no editables** por PUT/PATCH.
- [x] Verificados los formatos básicos de respuesta `200/201/204/404/405/409/422` en las pruebas por recurso.
- [x] Documentados campos, nulabilidad y nombres históricos `universidsad` y `nacionalidaad`.
- [x] Documentado rechazo de reactivación de asociaciones retiradas.
- [ ] Acordar y verificar la política de retiro de padres con asociaciones activas.
- [ ] Confirmar si la exigencia metodológica de cURL implica adaptar `cliente_api.php`.
- [ ] Ejecutar regresión total de V1+V2 desde las ramas definitivamente integradas y guardar evidencia.
- [ ] Cotejar el SQL de referencia con `db/init.sql` de la rama final y cerrar revisión formal y tag `v2` cuando corresponda.

**Contratos V1 invariables:** siguen vigentes los seis recursos sin FK (`area_conocimiento`, `objetivo_desarrollo_sostenible`, `area_aplicacion`, `termino_clave`, `universidad`, `linea_investigacion`) con sus rutas y respuestas previas. La reorganización interna de `repositorios/` y `servicios/` no altera los endpoints V1.

**Trazabilidad:** `2_spec.md` (requisitos y clarificaciones); `3_plan.md` (arquitectura y pruebas); `4_research.md` (decisiones); `5_data_model.md` (DDL y dependencias); `api_investigacion/index.php`, `controladores/`, `modelos/`, `servicios/` y `repositorios/`; pruebas HTTP y verificaciones manuales del equipo. Los procedimientos de arranque y validación estarán detallados en `7_quickstart.md`.
