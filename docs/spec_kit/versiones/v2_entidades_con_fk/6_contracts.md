# 6. Contratos API — V2: diez entidades con FK

> **BORRADOR DE CONTRATO, NO DEFINICIÓN FINAL.** Se preserva íntegramente `6_contracts.md` de V1. Las PK, columnas y FK ya se cotejaron con la copia SQL suministrada (`init(2).sql`), pero las rutas individuales de PK compuesta, los cuerpos de ejemplo y las reglas de asociaciones siguen sujetos a aprobación y cotejo con el `db/init.sql` de la rama real.

## 0. Convención heredada de V1

- `200` GET exitoso, PUT, PATCH y DELETE lógicos.
- `204` GET listado sin registros activos, sin cuerpo.
- `201` POST exitoso.
- `400` operación sin contenido aplicable, como PATCH `{}`.
- `404` registro inexistente o retirado.
- `405` método no permitido para ruta conocida.
- `409` conflicto de integridad (referencia FK inexistente/incompatible, PK o asociación duplicada) **si está confirmado y traducido**.
- `422` cuerpo con forma incorrecta, campos no permitidos, faltantes o valores inválidos.
- `500` fallo imprevisto, sin filtrar mensaje SQL.

Reutilizar los sobres de V1, adaptando `recurso`, mensajes y `datos`; por ejemplo:

```json
{"estado":409,"mensaje":"Conflicto de datos.","detalle":"No se puede completar la operación con la referencia proporcionada."}
```

No garantizar que todo fallo de FK será 409 hasta instrumentar y probar traducción de excepciones (en particular, errores del motor diferentes de integridad).

## 1. Rutas base propuestas, específicas por entidad

| Recurso | Colección (propuesta) | Ruta individual |
|---|---|---|
| Docentes | `/api/docente` | PK simple `cedula` INT; propuesta: `/api/docente/{cedula}` |
| Grupos de investigación | `/api/grupo_investigacion` | PK simple `id` INT; propuesta: `/api/grupo_investigacion/{id}` |
| Semilleros | `/api/semillero` | PK simple `id` INT; propuesta: `/api/semillero/{id}` |
| Participación en grupo | `/api/participa_grupo` | PK compuesta `docente_cedula`, `grupo_investigacion_id` |
| Participación en semillero | `/api/participa_semillero` | PK compuesta `docente`, `semillero` |
| Grupo y línea | `/api/grupo_linea` | PK compuesta `grupo_investigacion`, `linea_investigacion` |
| Semillero y línea | `/api/semillero_linea` | PK compuesta `semillero`, `linea_investigacion` |
| Área conocimiento y línea | `/api/ac_linea` | PK compuesta `linea_investigacion`, `area_conocimiento` (`VARCHAR(6)`) |
| ODS y línea | `/api/ods_linea` | PK compuesta `linea_investigacion`, `ods` |
| Área aplicación y línea | `/api/aa_linea` | PK compuesta `area_aplicacion`, `linea_investigacion` |

Las rutas colección se proponen por analogía con las rutas singulares y explícitas de V1. Deben acordarse y probarse antes de programarlas. Nunca usar `/api/{tabla}`.

## 2. Operaciones por colección

| Método | Ruta | Resultado esperado |
|---|---|---|
| `GET` | `/api/recurso_especifico` | `200 {"recurso":"...","total":N,"datos":[...]}` o `204` |
| `POST` | `/api/recurso_especifico` | `201 {"estado":201,"mensaje":"...","datos":{...}}` o `409`/`422` |

Para cada ruta individual determinada por el modelo (no necesariamente un solo segmento):

| Método | Ruta individual real | Resultado esperado |
|---|---|---|
| `GET` | identificador real | `200` ficha; `404` ausente/inactiva |
| `PUT` | identificador real | `200` reemplazo; `422` si falta campo; `409` si viola FK |
| `PATCH` | identificador real | `200` modificación parcial; `400` si `{}`; `422` cuerpo inválido |
| `DELETE` | identificador real | `200` retiro lógico; `404` segundo retiro; reglas con hijos aún por decidir |

## 3. Forma de los cuerpos: pendiente de DDL

No codificar un JSON ficticio. Para cada entidad completar **antes de implementar**:

```text
Recurso: ________________
Colección definitiva: /api/________________
Ruta individual definitiva: __________________________
Identificador(s) y tipos: ___________________________
POST (campos obligatorios/optativos): _______________
PUT (todos los editables obligatorios): ______________
PATCH (editables permitidos): _______________________
Reglas FK (activos, nulabilidad, 409): ______________
Campos NO editables: PK y activo (salvo particularidades documentadas)
Mensajes de error y de éxito en español: ____________
```

**Propuesta para discusión, NO aprobada:** para los siete recursos con PK compuesta, emplear dos segmentos de ruta en el **orden exacto de PK del SQL**: `/api/grupo_linea/{grupo_investigacion}/{linea_investigacion}` o `/api/ac_linea/{linea_investigacion}/{area_conocimiento}`. Todos los componentes deben codificarse/decodificarse sin ambigüedad. El equipo debe aprobar los patrones y el comportamiento de métodos HTTP antes de que se conviertan en contrato definitivo.

## 3.1. Cuerpos y validaciones basados en el DDL (pendientes de aprobación funcional)

- Campos obligatorios, opcionales y tipos por entidad: **ver tablas literales en `5_data_model.md`**. Las PK V2 son suministradas por cliente; `activo` es interno.
- FK nullable: `docente.linea_investigacion_principal` y `grupo_investigacion.universidsad` pueden ser `NULL`. Si contienen valor, debe existir el registro padre y quedar verificada la política de activo.
- No hay ningún `id` independiente en tablas puente; **POST suministra ambos componentes**.
- `participa_grupo` y `participa_semillero` incluyen `rol`, `fecha_inicio` y `fecha_fin` (nullable). Los otros cinco puentes solo tienen PK y `activo`, así que el comportamiento de PUT/PATCH está **pendiente de decisión**, no se declara implementado.
- Los nombres `universidsad` y `nacionalidaad` son **literales en el SQL suministrado** y deben mantenerse en el JSON hasta que haya un cambio de esquema aprobado.
- Una re-creación de asociación lógicamente retirada no puede tratarse como INSERT nuevo sin conflicto de PK: pendiente decisión explícita de reactivación.

## 4. Desplegables del frontend

La lista de universidades, grupos y líneas procede de los `GET` de sus respectivos recursos. Si el GET retorna `204`, la pantalla muestra «No hay opciones disponibles»; si la API no responde, muestra indisponibilidad y no finge opciones.

La selección no evita una validación de integridad al guardar: un registro pudo ser retirado entre leer el desplegable y hacer POST/PUT/PATCH.

## 5. Pruebas obligatorias por recurso

1. GET de la colección: 200 o 204.
2. POST válido: 201, luego GET individual: 200.
3. PUT completo: 200 y uno incompleto: 422.
4. PATCH parcial: 200; PATCH `{}`: 400.
5. FK inexistente o retirada: no persistir y obtener el error de integridad acordado.
6. POST con llave o asociación duplicada: 409.
7. DELETE lógico: 200, luego GET: 404, segundo DELETE: 404.
8. Comprobar sobres JSON, campos y mensajes contra el contrato.
9. Revisar que el frontend representa correctamente los casos de error y de lista vacía.

## 6. Contratos V1 invariables

Los endpoints y sobres de los seis recursos sin FK de V1 no cambian debido a la reorganización de carpetas. Una modificación de ruta de archivo PHP interna no altera la dirección HTTP de la API.
