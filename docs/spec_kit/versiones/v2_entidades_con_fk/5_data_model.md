# 5. Modelo de datos — V2: diez tablas con claves foráneas

> **ESTADO (29-10-2026): MODELO IMPLEMENTADO Y CONTRASTADO CON EL SQL COMPARTIDO Y EL CÓDIGO; APROBACIÓN FORMAL PENDIENTE.**  
> Este documento actualiza el modelo de datos de V2 sin cambiar el esquema de MariaDB. Se conserva el inventario original de columnas, tipos, nulabilidad, claves y relaciones; las reglas de aplicación se contrastan con las carpetas finales `api_investigacion/` y `front_php/`, así como con las comprobaciones comunicadas durante el desarrollo. La referencia DDL examinada es la copia compartida `init(2).sql` (coincidente con `init(3).sql`); **aún se requiere cotejarla formalmente con `db/init.sql` de la rama final del equipo**. Implementación funcional no significa aprobación del merge ni cierre del Spec Kit.

## 1. Alcance del esquema

La V2 implementa **diez tablas con claves foráneas** sobre las seis entidades de V1, manteniendo **16 recursos de Investigación**. El script SQL compartido crea **19 tablas en total**: 16 de Investigación y 3 relacionadas con usuarios/roles que corresponden a V3 (`rol`, `usuario`, `rol_usuario`). La presencia de esas tres tablas en la base no implica que se haya implementado autenticación en V2.

- Los **16 recursos de Investigación** tienen `activo BOOLEAN NOT NULL DEFAULT TRUE` para retiro lógico. En MariaDB, `BOOLEAN` equivale a `TINYINT(1)`.
- En V2 hay **tres tablas con clave primaria simple** (`docente`, `grupo_investigacion`, `semillero`) y **siete con clave primaria compuesta**. Ninguna de las tres PK simples V2 usa `AUTO_INCREMENT`: el cliente proporciona sus identificadores. Esto no se confunde con `linea_investigacion.id`, entidad V1 que **sí** es `AUTO_INCREMENT` en el DDL compartido.
- La implementación respeta los nombres originales de la base, incluidos `docente.nacionalidaad` y `grupo_investigacion.universidsad`.
- No se efectuaron migraciones, renombramientos ni modificaciones de DDL como parte de esta actualización documental.

### Separación entre esquema y aplicación

La **base de datos** impone las PK, FK, tipos y condiciones `NOT NULL` definidas en SQL. Los **servicios y controladores PHP** aportan las reglas que no quedan garantizadas por ese DDL: comprobación de padres **activos**, validaciones de rol y fechas, prohibición de editar las claves, rechazo de campos `activo`, respuestas HTTP y decisiones operativas sobre el retiro lógico. No debe presentarse una validación de aplicación como si fuera un `CHECK` o una cascada declarada en MariaDB.

## 2. Resumen de claves y dependencias contrastadas

| Tabla V2 | Clave primaria (en orden) | Claves foráneas (columna → tabla.columna) | Particularidad confirmada |
|---|---|---|---|
| `docente` | `cedula` | `linea_investigacion_principal` → `linea_investigacion.id` | PK simple provista; FK nullable |
| `grupo_investigacion` | `id` | `universidsad` → `universidad.id` | PK simple provista; FK nullable; nombre SQL histórico |
| `semillero` | `id` | `grupo_investigacion` → `grupo_investigacion.id` | PK simple provista; FK obligatoria |
| `participa_grupo` | (`docente_cedula`, `grupo_investigacion_id`) | `docente_cedula` → `docente.cedula`; `grupo_investigacion_id` → `grupo_investigacion.id` | PK compuesta, rol y fechas editables |
| `participa_semillero` | (`docente`, `semillero`) | `docente` → `docente.cedula`; `semillero` → `semillero.id` | PK compuesta, rol y fechas editables |
| `grupo_linea` | (`grupo_investigacion`, `linea_investigacion`) | `grupo_investigacion` → `grupo_investigacion.id`; `linea_investigacion` → `linea_investigacion.id` | PK compuesta; solo asociación |
| `semillero_linea` | (`semillero`, `linea_investigacion`) | `semillero` → `semillero.id`; `linea_investigacion` → `linea_investigacion.id` | PK compuesta; solo asociación |
| `ac_linea` | (`linea_investigacion`, `area_conocimiento`) | `linea_investigacion` → `linea_investigacion.id`; `area_conocimiento` → `area_conocimiento.id` | `area_conocimiento` es **VARCHAR(6)**, no entero |
| `ods_linea` | (`linea_investigacion`, `ods`) | `linea_investigacion` → `linea_investigacion.id`; `ods` → `objetivo_desarrollo_sostenible.id` | La columna hija se llama literalmente `ods` |
| `aa_linea` | (`area_aplicacion`, `linea_investigacion`) | `area_aplicacion` → `area_aplicacion.id`; `linea_investigacion` → `linea_investigacion.id` | PK compuesta; solo asociación |

**Relaciones funcionales:** los padres de las tablas puente pueden participar en múltiples asociaciones. Cada fila de una tabla puente referencia exactamente un registro de cada padre; un padre puede tener cero o muchas filas asociadas. La FK opcional permite que `docente` no tenga línea principal y que `grupo_investigacion` no tenga universidad, pero `semillero` siempre requiere grupo.

**Identificación por API:** las siete asociaciones se consultan mediante dos segmentos de ruta en **el mismo orden** de su PK SQL: `/api/<recurso>/<clave1>/<clave2>`. No se introducen identificadores independientes en las tablas puente; los contratos HTTP detallados se registrarán en `6_contracts.md`.

## 3. Fichas de tablas y columnas

Se conservan los nombres **literales del DDL compartido**, incluidas las grafías históricas. `NULL` y `NOT NULL` son restricciones **del esquema**; las validaciones adicionales de negocio se indican en las secciones posteriores. Todas las tablas de esta sección utilizan InnoDB en el SQL revisado.

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

## 4. Reglas y advertencias de integridad referencial

### 4.1. Reglas que impone el DDL

- **Unicidad:** las tres PK simples identifican una fila; las siete PK compuestas impiden que se repita una misma pareja, **aunque su fila tenga `activo = FALSE`**.
- **FK existentes:** las referencias apuntan a las tablas y columnas indicadas en §2. Las FK opcionales se aceptan con `NULL`, no con identificadores inexistentes; las de `semillero` y las siete tablas puente son obligatorias.
- **Longitudes y tipos:** `ac_linea.area_conocimiento` utiliza `VARCHAR(6)` porque los códigos del catálogo V1 son alfanuméricos (por ejemplo, `1A01`). `participa_grupo.rol` y `participa_semillero.rol` son `VARCHAR(15)`; `fecha_fin` es `DATE NULL`.
- **Sin cascada lógica declarada:** el DDL no define una política que modifique `activo` en hijos cuando se retira lógicamente un padre. Tampoco contiene un `CHECK` de `fecha_fin >= fecha_inicio`, catálogos enumerados de rol o reglas automáticas de reactivación.
- **Ortografía histórica:** `nacionalidaad` y `universidsad` no son errores que deban corregirse silenciosamente en las consultas. Un cambio futuro requiere modificación de DDL y coordinación de API, frontend, contratos y pruebas.

### 4.2. Reglas implementadas en el backend de V2

| Tema | Comportamiento verificado o contrastado con código | Nivel |
|---|---|---|
| Validación de referencias | Los servicios verifican existencia **y estado activo** de los padres al crear asociaciones y al asignar FK cuando aplica | Aplicación (servicios) |
| Rol de participaciones | Cadena no vacía, máximo 15 caracteres | Aplicación; tipo/longitud en SQL |
| Fechas de participaciones | Formato válido `YYYY-MM-DD`; `fecha_fin` puede ser `null` y no puede preceder `fecha_inicio` | Aplicación; columnas DATE en SQL |
| PK compuesta inmutable | No se modifican los componentes de la clave por PUT/PATCH | Controlador/servicio/repositorio |
| Participaciones | PUT/PATCH solo para `rol`, `fecha_inicio` y `fecha_fin` | Aplicación |
| Cinco puentes sin otros atributos | `ac_linea`, `ods_linea`, `aa_linea`, `grupo_linea` y `semillero_linea` no admiten PUT/PATCH (`405`) | Contrato HTTP |
| Duplicados o reinserción de retirados | POST con una pareja ya existente, incluso inactiva, devuelve `409`; **no se reactiva implícitamente** | PK SQL y manejo de conflictos en PHP |
| Campo `activo` | No puede suministrarse para editar el estado por POST/PUT/PATCH | Controlador/servicio |
| Retiro lógico | DELETE cambia `activo` a falso; GET normal y listados solo muestran filas activas | Repositorio/contrato HTTP |

Estas reglas se desarrollaron y probaron **por recurso** durante V2. No son evidencia por sí mismas de una regresión completa de `main` después de los merges.

### 4.3. Decisión abierta sobre retiros de padres

**PENDIENTE (C4 en `2_spec.md`):** el equipo aún debe decidir qué ocurrirá si se intenta retirar un `docente`, `grupo_investigacion`, `semillero`, `linea_investigacion` u otro padre con asociaciones hijas activas. Un `UPDATE padre SET activo = FALSE` no viola por sí solo una FK de InnoDB, aunque deje relaciones activas hacia un registro inactivo. No se declara implementada una cascada lógica ni un bloqueo general de retiro. Resolverlo exigirá actualizar la especificación, los servicios y las pruebas si cambia el comportamiento actual.

## 5. Política de borrado lógico implementada

1. **Persistencia:** `activo BOOLEAN NOT NULL DEFAULT TRUE` se conserva en las 16 tablas de Investigación; no se introducen columnas de auditoría o fechas de retiro por esta V2.
2. **Lectura:** los repositorios de V2 filtran `activo = TRUE` para listados y consultas de ficha.
3. **Retiro:** DELETE ejecuta un `UPDATE ... SET activo = FALSE` de la fila activa, sin borrarla físicamente. Si ya estaba retirada, el segundo DELETE devuelve `404` según las pruebas por entidad.
4. **Reinserción:** se mantiene la fila con su PK; un nuevo POST con la misma pareja devuelve `409`. La decisión de **no reactivar automáticamente** quedó implementada (C10 de `2_spec.md`).
5. **FK funcional:** un registro referenciado debe estar **activo** al crear una asociación, aunque físicamente exista y satisfaga una restricción FK en SQL.
6. **Verificación SQL manual:** las pruebas usaron consultas de la forma siguiente, sin alterar el DDL:

```sql
SELECT semillero, linea_investigacion, activo
FROM semillero_linea
WHERE semillero = 1620578068
  AND linea_investigacion = 20;
```

La comprobación comunicada en phpMyAdmin devolvió `activo = 0` para esa asociación de prueba. Se reportaron comprobaciones análogas en las otras tablas puente, además de pruebas HTTP de la API.

## 6. Verificación de consistencia del modelo y pendientes de cierre

- [x] Identificadas las **10 tablas V2**, columnas, nulabilidad, PK y FK de la copia `init(2).sql`.
- [x] Verificada en el SQL compartido la distribución **3 PK simples asignadas / 7 PK compuestas**, y que `linea_investigacion.id` de V1 sí es `AUTO_INCREMENT`.
- [x] Contrastados nombres de las claves y relaciones con los modelos, servicios y repositorios de las diez entidades presentes en el backend final compartido.
- [x] Confirmado que el código no añade un `id` artificial a las asociaciones y conserva las columnas `nacionalidaad`, `universidsad`, `ods` y `area_conocimiento VARCHAR(6)`.
- [x] Definido e implementado el rechazo de POST sobre parejas retiradas (`409`) y PUT/PATCH sobre las cinco asociaciones simples (`405`).
- [x] Comprobado el borrado lógico mediante pruebas HTTP por entidad y consultas de phpMyAdmin comunicadas durante el desarrollo.
- [ ] **Cotejar formalmente** la copia SQL usada con `db/init.sql` de la rama backend final y registrar la versión/revisión confirmada por el equipo.
- [ ] **Resolver C4:** retiro de padre con hijos activos y su efecto sobre las consultas y asociaciones vigentes.
- [ ] **Ejecutar y registrar regresión integral** de los seis recursos V1 y diez de V2 tras integrar las ramas.
- [ ] **Obtener aprobación humana** del equipo para el modelo, los contratos y el cierre documental.

## 7. Fuentes internas y trazabilidad

- **DDL de referencia:** `init(2).sql` / `init(3).sql` compartidos, con las 19 tablas; es una **copia** y no sustituye la verificación del archivo `db/init.sql` definitivo.
- **Código revisado:** carpetas consolidadas `api_investigacion/modelos/`, `api_investigacion/repositorios/`, `api_investigacion/servicios/`, `api_investigacion/controladores/` y rutas `api_investigacion/index.php`.
- **Documentos coherentes:** `2_spec.md` (RF-V2 y clarificaciones C1, C4, C5, C6, C9, C10, C12), `3_plan.md` (§§2, 5 y 6), `4_research.md` (decisiones sobre PK/FK, nombres SQL, retiro lógico y validaciones).
- **Evidencia funcional:** resultados HTTP de las diez entidades V2, así como consultas en phpMyAdmin que verifican `activo = 0`. El detalle de endpoints y códigos HTTP se consolida en `6_contracts.md`.

**Resultado:** el modelo previsto por V2 coincide con la implementación compartida de los diez recursos. El documento describe **lo implementado y lo pendiente**, sin atribuir al esquema reglas que pertenecen a PHP y sin afirmar validación formal de la rama final.
