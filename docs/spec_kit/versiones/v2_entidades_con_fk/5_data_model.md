# 5. Modelo de datos — V2: diez tablas con claves foráneas

> **Estado: revisión técnica cotejada con la copia `init(2).sql` compartida en el proyecto.** Las columnas, tipos, restricciones `NOT NULL`, claves primarias y referencias que siguen se transcribieron de ese SQL. Antes de aprobar la compuerta, el equipo debe comprobar que corresponde exactamente al `db/init.sql` de la rama backend vigente. **No se modificó la base de datos.**

## 1. Alcance del esquema

La V2 añade CRUD para 10 tablas con claves foráneas a los seis recursos V1 (`area_conocimiento`, `objetivo_desarrollo_sostenible`, `area_aplicacion`, `termino_clave`, `universidad`, `linea_investigacion`). Las 16 tablas del módulo tienen `activo BOOLEAN NOT NULL DEFAULT TRUE`; las tres de usuarios corresponden a V3. **Ninguna PK simple de estas diez tablas V2 tiene `AUTO_INCREMENT`:** los identificadores se suministran al crear, salvo que el equipo autorice y documente cambios de esquema (no asumidos).

## 2. Resumen de claves y dependencias comprobadas

| Tabla V2 | Clave primaria | Claves foráneas (columna → padre.columna) | Particularidad |
|---|---|---|---|
| `docente` | `cedula` | `linea_investigacion_principal` → `linea_investigacion.id` | **PK simple asignada**; FK línea principal nullable |
| `grupo_investigacion` | `id` | `universidsad` → `universidad.id` | **PK simple asignada**; FK universidad nullable y nombre literal `universidsad` |
| `semillero` | `id` | `grupo_investigacion` → `grupo_investigacion.id` | **PK simple asignada**; grupo obligatorio |
| `participa_grupo` | `docente_cedula` + `grupo_investigacion_id` | `docente_cedula` → `docente.cedula`; `grupo_investigacion_id` → `grupo_investigacion.id` | **PK compuesta** |
| `participa_semillero` | `docente` + `semillero` | `docente` → `docente.cedula`; `semillero` → `semillero.id` | **PK compuesta** |
| `grupo_linea` | `grupo_investigacion` + `linea_investigacion` | `grupo_investigacion` → `grupo_investigacion.id`; `linea_investigacion` → `linea_investigacion.id` | **PK compuesta** |
| `semillero_linea` | `semillero` + `linea_investigacion` | `semillero` → `semillero.id`; `linea_investigacion` → `linea_investigacion.id` | **PK compuesta** |
| `ac_linea` | `linea_investigacion` + `area_conocimiento` | `linea_investigacion` → `linea_investigacion.id`; `area_conocimiento` → `area_conocimiento.id` | **PK compuesta** |
| `ods_linea` | `linea_investigacion` + `ods` | `linea_investigacion` → `linea_investigacion.id`; `ods` → `objetivo_desarrollo_sostenible.id` | **PK compuesta** |
| `aa_linea` | `area_aplicacion` + `linea_investigacion` | `area_aplicacion` → `area_aplicacion.id`; `linea_investigacion` → `linea_investigacion.id` | **PK compuesta** |

## 3. Fichas de tablas y columnas

Los nombres se respetan **exactamente como aparecen en SQL**, incluidos errores de ortografía. `NULL` indica columna opcional según DDL; esto no elimina validaciones adicionales que el equipo pudiera aprobar más adelante.

### `docente`

**PK:** `cedula`. **FK:** `linea_investigacion_principal` → `linea_investigacion(id)`.

| Columna | Tipo SQL | Nulabilidad | Por defecto |
|---|---|---|---|
| `cedula` | `INT` | NOT NULL | — |
| `nombres` | `VARCHAR(60)` | NOT NULL | — |
| `apellidos` | `VARCHAR(60)` | NOT NULL | — |
| `genero` | `VARCHAR(12)` | NOT NULL | — |
| `cargo` | `VARCHAR(30)` | NOT NULL | — |
| `fecha_nacimiento` | `DATE` | NOT NULL | — |
| `correo` | `VARCHAR(70)` | NOT NULL | — |
| `telefono` | `VARCHAR(20)` | NOT NULL | — |
| `url_cvlac` | `VARCHAR(128)` | NOT NULL | — |
| `fecha_actualizacion` | `DATE` | NOT NULL | — |
| `escalafon` | `VARCHAR(45)` | NOT NULL | — |
| `perfil` | `LONGTEXT` | NOT NULL | — |
| `cat_minciencia` | `VARCHAR(45)` | NULL | — |
| `conv_minciencia` | `VARCHAR(45)` | NOT NULL | — |
| `nacionalidaad` | `VARCHAR(45)` | NOT NULL | — |
| `linea_investigacion_principal` | `INT` | NULL | — |
| `activo` | `BOOLEAN` | NOT NULL | DEFAULT TRUE |

### `grupo_investigacion`

**PK:** `id`. **FK:** `universidsad` → `universidad(id)`.

| Columna | Tipo SQL | Nulabilidad | Por defecto |
|---|---|---|---|
| `id` | `INT` | NOT NULL | — |
| `nombre` | `VARCHAR(60)` | NOT NULL | — |
| `url_gruplac` | `VARCHAR(128)` | NULL | — |
| `categoria` | `VARCHAR(10)` | NULL | — |
| `convocatoria` | `VARCHAR(10)` | NULL | — |
| `fecha_fundacion` | `DATE` | NOT NULL | — |
| `universidsad` | `INT` | NULL | — |
| `interno` | `TINYINT` | NOT NULL | — |
| `ambito` | `VARCHAR(45)` | NOT NULL | — |
| `activo` | `BOOLEAN` | NOT NULL | DEFAULT TRUE |

### `semillero`

**PK:** `id`. **FK:** `grupo_investigacion` → `grupo_investigacion(id)`.

| Columna | Tipo SQL | Nulabilidad | Por defecto |
|---|---|---|---|
| `id` | `INT` | NOT NULL | — |
| `nombre` | `VARCHAR(60)` | NOT NULL | — |
| `fecha_fundacion` | `DATE` | NOT NULL | — |
| `grupo_investigacion` | `INT` | NOT NULL | — |
| `activo` | `BOOLEAN` | NOT NULL | DEFAULT TRUE |

### `participa_grupo`

**PK:** `docente_cedula`, `grupo_investigacion_id`. **FK:** `docente_cedula` → `docente(cedula)`; `grupo_investigacion_id` → `grupo_investigacion(id)`.

| Columna | Tipo SQL | Nulabilidad | Por defecto |
|---|---|---|---|
| `docente_cedula` | `INT` | NOT NULL | — |
| `grupo_investigacion_id` | `INT` | NOT NULL | — |
| `rol` | `VARCHAR(15)` | NOT NULL | — |
| `fecha_inicio` | `DATE` | NOT NULL | — |
| `fecha_fin` | `DATE` | NULL | — |
| `activo` | `BOOLEAN` | NOT NULL | DEFAULT TRUE |

### `participa_semillero`

**PK:** `docente`, `semillero`. **FK:** `docente` → `docente(cedula)`; `semillero` → `semillero(id)`.

| Columna | Tipo SQL | Nulabilidad | Por defecto |
|---|---|---|---|
| `docente` | `INT` | NOT NULL | — |
| `semillero` | `INT` | NOT NULL | — |
| `rol` | `VARCHAR(15)` | NOT NULL | — |
| `fecha_inicio` | `DATE` | NOT NULL | — |
| `fecha_fin` | `DATE` | NULL | — |
| `activo` | `BOOLEAN` | NOT NULL | DEFAULT TRUE |

### `grupo_linea`

**PK:** `grupo_investigacion`, `linea_investigacion`. **FK:** `grupo_investigacion` → `grupo_investigacion(id)`; `linea_investigacion` → `linea_investigacion(id)`.

| Columna | Tipo SQL | Nulabilidad | Por defecto |
|---|---|---|---|
| `grupo_investigacion` | `INT` | NOT NULL | — |
| `linea_investigacion` | `INT` | NOT NULL | — |
| `activo` | `BOOLEAN` | NOT NULL | DEFAULT TRUE |

### `semillero_linea`

**PK:** `semillero`, `linea_investigacion`. **FK:** `semillero` → `semillero(id)`; `linea_investigacion` → `linea_investigacion(id)`.

| Columna | Tipo SQL | Nulabilidad | Por defecto |
|---|---|---|---|
| `semillero` | `INT` | NOT NULL | — |
| `linea_investigacion` | `INT` | NOT NULL | — |
| `activo` | `BOOLEAN` | NOT NULL | DEFAULT TRUE |

### `ac_linea`

**PK:** `linea_investigacion`, `area_conocimiento`. **FK:** `linea_investigacion` → `linea_investigacion(id)`; `area_conocimiento` → `area_conocimiento(id)`.

| Columna | Tipo SQL | Nulabilidad | Por defecto |
|---|---|---|---|
| `linea_investigacion` | `INT` | NOT NULL | — |
| `area_conocimiento` | `VARCHAR(6)` | NOT NULL | — |
| `activo` | `BOOLEAN` | NOT NULL | DEFAULT TRUE |

### `ods_linea`

**PK:** `linea_investigacion`, `ods`. **FK:** `linea_investigacion` → `linea_investigacion(id)`; `ods` → `objetivo_desarrollo_sostenible(id)`.

| Columna | Tipo SQL | Nulabilidad | Por defecto |
|---|---|---|---|
| `linea_investigacion` | `INT` | NOT NULL | — |
| `ods` | `INT` | NOT NULL | — |
| `activo` | `BOOLEAN` | NOT NULL | DEFAULT TRUE |

### `aa_linea`

**PK:** `area_aplicacion`, `linea_investigacion`. **FK:** `area_aplicacion` → `area_aplicacion(id)`; `linea_investigacion` → `linea_investigacion(id)`.

| Columna | Tipo SQL | Nulabilidad | Por defecto |
|---|---|---|---|
| `area_aplicacion` | `INT` | NOT NULL | — |
| `linea_investigacion` | `INT` | NOT NULL | — |
| `activo` | `BOOLEAN` | NOT NULL | DEFAULT TRUE |

## 4. Advertencias derivadas del esquema

- **Ortografía histórica del SQL:** `docente.nacionalidaad` y `grupo_investigacion.universidsad` tienen esas grafías. No renombrarlas silenciosamente desde PHP, el JSON o los contratos; si se decide arreglar el DDL, documentar el cambio de esquema y su impacto antes de implementarlo.
- **Tablas puente:** `participa_grupo`, `participa_semillero`, `grupo_linea`, `semillero_linea`, `ac_linea`, `ods_linea`, `aa_linea` poseen **PK compuesta de dos columnas**, no `id` independiente.
- **Padres opcionales:** `docente.linea_investigacion_principal` y `grupo_investigacion.universidsad` admiten `NULL` a nivel SQL. `semillero.grupo_investigacion` es obligatorio; las FK de las siete tablas puente también son obligatorias.
- **Reactivación:** la PK sigue ocupada al poner `activo=FALSE`. Insertar nuevamente una misma asociación fallará por duplicado salvo que se defina una operación explícita de reactivación. **[NECESITA ACLARACIÓN]** No programar un comportamiento implícito.
- **Retiro lógico de padres:** el SQL no impide que un hijo activo apunte a un padre con `activo=FALSE`. **[NECESITA ACLARACIÓN]** Documentar si se bloquea el retiro o se aplica una regla a las relaciones activas.
- **Restricciones adicionales:** el SQL no declara `CHECK` para roles, categorías, fechas o rango de valores. `VARCHAR` limita la longitud de caracteres almacenables, pero **las listas enumeradas de roles/categorías en `modulo_investigacion.md` son contexto de negocio, no restricciones SQL automáticamente verificadas**.

## 5. Política de borrado lógico heredada

- En los 10 recursos V2, las consultas ordinarias deben filtrar `activo = TRUE`.
- `DELETE` se traduce en un `UPDATE ... SET activo = FALSE`, sin borrado físico.
- `activo` no es editable mediante POST/PUT/PATCH.
- La existencia física de una referencia no equivale a una referencia *activa*; la validación funcional debe consultar el estado del padre según la política aprobada.

## 6. Verificación previa a la implementación

- [x] Identificadas las diez tablas, columnas, PK y FK de la **copia SQL compartida**.
- [x] Identificadas las siete PK compuestas y las tres PK simples de la **copia SQL compartida**.
- [ ] Cotejo de esos datos con `db/init.sql` de la rama actual del equipo.
- [ ] Decisión de identificadores compuestos en URL y operaciones PUT/PATCH sin campos editables.
- [ ] Política de borrado de padres con hijos, reactivación de asociaciones y FK opcionales.
- [ ] Revisión/aprobación humana de este modelo de datos y su relación con la spec.
