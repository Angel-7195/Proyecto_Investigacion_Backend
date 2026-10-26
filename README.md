# Proyecto de Investigación — Versión 2

## Integrantes

- Ángel David Gutiérrez Ladino
- Miguel Ángel Lotero Álvarez

Proyecto académico desarrollado en **PHP puro**, **MariaDB** y **Docker** para el módulo de Investigación.

La **versión 2** amplía los seis CRUD de la versión 1 con **diez recursos que poseen claves foráneas**. Al completar esta versión, el módulo contará con **16 recursos CRUD** en la API y el frontend.

El sistema está dividido en dos aplicaciones independientes:

- **Backend:** API REST desarrollada en PHP que se comunica con MariaDB mediante PDO.
- **Frontend:** aplicación PHP con HTML, CSS y Bootstrap local, que consume la API mediante HTTP.

El frontend **no se conecta directamente a MariaDB** ni utiliza clases internas del backend.

---

## Estado de la versión

La **V1 ya cuenta con los seis CRUD implementados y comprobados por el equipo**. Las vistas y las rutas del frontend se reorganizaron por entidad y se incorporó un archivo CSS personalizado.

La **V2 se encuentra en preparación y documentación**. Los diez CRUD nuevos, sus formularios y sus pruebas de integración **todavía están pendientes de implementación**.

El entorno local utilizado incluye:

- MariaDB;
- API de Investigación;
- frontend PHP;
- phpMyAdmin.

La implementación de V2 seguirá el **Spec Kit**: especificar y aprobar los contratos antes de programar, implementar por capas y ejecutar pruebas de regresión de V1.

---

## Cómo levantar el proyecto

Desde la raíz del repositorio **backend**, donde está `docker-compose.yml`:

```powershell
docker compose up -d --build
```

Para comprobar los contenedores:

```powershell
docker compose ps
```

Para detener el entorno sin eliminar los datos del volumen:

```powershell
docker compose down
```

> **Importante:** no utilizar `docker compose down -v` para detener el proyecto normalmente: la opción `-v` elimina también los volúmenes y puede borrar los datos almacenados en MariaDB.

### Accesos locales

| Servicio | Dirección |
|---|---|
| Frontend | http://localhost:8110 |
| API | http://localhost:8111 |
| phpMyAdmin | http://localhost:8105 |
| MariaDB | `localhost:13330` |

El endpoint raíz de diagnóstico de la API es:

http://localhost:8111/

Los puertos corresponden al entorno local utilizado en V1; deben cotejarse con el `docker-compose.yml` vigente si se modifica su configuración.

---

## Qué incluye la V2

### Recursos de V1 — Se mantienen (6)

Estos recursos **ya están implementados** y deben seguir funcionando sin cambios de contrato:

| Recurso | Clave primaria |
|---|---|
| `area_conocimiento` | `id` (`VARCHAR(6)`) |
| `objetivo_desarrollo_sostenible` | `id` |
| `area_aplicacion` | `id` |
| `termino_clave` | `termino` |
| `universidad` | `id` |
| `linea_investigacion` | `id` autoincremental |

### Recursos nuevos de V2 — Pendientes (10)

Las **tres primeras** tablas poseen una clave primaria simple y al menos una clave foránea:

| Recurso | Clave primaria | Relación principal |
|---|---|---|
| `docente` | `cedula` | `linea_investigacion_principal` → `linea_investigacion.id` |
| `grupo_investigacion` | `id` | `universidsad` → `universidad.id` |
| `semillero` | `id` | `grupo_investigacion` → `grupo_investigacion.id` |

Las **siete restantes** son tablas de asociación con **clave primaria compuesta**:

| Recurso | Clave primaria compuesta | Relación |
|---|---|---|
| `participa_grupo` | (`docente_cedula`, `grupo_investigacion_id`) | Docente ↔ Grupo |
| `participa_semillero` | (`docente`, `semillero`) | Docente ↔ Semillero |
| `grupo_linea` | (`grupo_investigacion`, `linea_investigacion`) | Grupo ↔ Línea |
| `semillero_linea` | (`semillero`, `linea_investigacion`) | Semillero ↔ Línea |
| `ac_linea` | (`linea_investigacion`, `area_conocimiento`) | Línea ↔ Área de conocimiento |
| `ods_linea` | (`linea_investigacion`, `ods`) | Línea ↔ ODS |
| `aa_linea` | (`area_aplicacion`, `linea_investigacion`) | Área de aplicación ↔ Línea |

> **Nota sobre el SQL:** los campos `nacionalidaad` (en `docente`) y `universidsad` (en `grupo_investigacion`) están escritos así en el script recibido. Se deben respetar sus nombres reales al consultar la base de datos; cualquier corrección del esquema requiere una decisión documentada.

### Tablas fuera del alcance de V2

La base de datos tiene **19 tablas en total**: 16 corresponden al módulo de Investigación y tres a la gestión de usuarios.

Las tablas `usuario`, `rol` y `rol_usuario` se utilizarán en **V3** para autenticación, autorización y administración de usuarios. **No forman parte de los diez CRUD nuevos de V2**.

---

## Estructura principal del backend

```text
api_investigacion/
├── index.php
├── Dockerfile
├── controladores/
├── excepciones/
├── modelos/
├── pruebas/
├── repositorios/
│   └── ... carpetas organizadas por recurso
└── servicios/
    ├── ensamblador.php
    └── ... carpetas organizadas por recurso

db/
└── init.sql

docs/
└── spec_kit/
    └── versiones/
        └── ... documentación de V1 y V2

docker-compose.yml
.env.example
.gitignore
README.md
```

La distribución muestra la estructura de referencia y la organización por recurso. **Los archivos específicos de las diez entidades se agregarán progresivamente durante V2.** La ubicación exacta de los documentos puede ajustarse a la rama de trabajo.

### `api_investigacion/index.php`

Es el **Front Controller** de la API: recibe las peticiones HTTP y las dirige al controlador correspondiente. No contiene consultas SQL ni reglas de negocio.

### `controladores/`

Reciben las solicitudes, validan su estructura, llaman a los servicios y producen respuestas HTTP en formato JSON. **No acceden directamente a MariaDB**.

### `servicios/`

Contienen las reglas de negocio: campos obligatorios, duplicados, validaciones de claves foráneas, estado de los recursos y operaciones permitidas. No contienen SQL ni códigos de respuesta HTTP.

### `servicios/ensamblador.php`

Centraliza la construcción y conexión de repositorios, servicios y sus dependencias.

### `repositorios/`

Contienen las interfaces y las implementaciones MariaDB con **PDO y consultas preparadas**. Son la capa encargada de ejecutar SQL.

### `modelos/`, `excepciones/` y `pruebas/`

Representan las entidades del dominio, los errores de negocio y las pruebas para verificar cada capa de manera independiente.

### `db/init.sql`

Contiene el esquema de las **19 tablas** y los registros iniciales. Las tablas existen desde el arranque de la base de datos; lo que se desarrolla por versiones son los recursos de la aplicación.

### `docs/spec_kit/`

Conserva las especificaciones, decisiones técnicas, modelo de datos, contratos HTTP, guía de pruebas, tareas y lista de verificación. Los contratos nuevos de V2 deben aprobarse antes de implementarse.

---

## Endpoints de la API

Cada recurso tiene rutas específicas; **no se utiliza una ruta genérica que acepte cualquier nombre de tabla**, como `/api/{tabla}`.

### Endpoints de V1 — Disponibles

```text
/api/area_conocimiento
/api/objetivo_desarrollo_sostenible
/api/area_aplicacion
/api/termino_clave
/api/universidad
/api/linea_investigacion
```

Las operaciones implementadas de V1 incluyen `GET`, `POST`, `PUT`, `PATCH` y `DELETE`, con rutas de colección y de registro individual según la clave primaria del recurso.

### Endpoints de V2 — Propuestos, todavía no implementados

```text
/api/docente
/api/grupo_investigacion
/api/semillero
/api/participa_grupo
/api/participa_semillero
/api/grupo_linea
/api/semillero_linea
/api/ac_linea
/api/ods_linea
/api/aa_linea
```

Para cada recurso se planifican las siguientes operaciones:

| Método | Operación prevista |
|---|---|
| `GET` | Listar registros u obtener uno específico |
| `POST` | Crear un registro o una asociación |
| `PUT` | Reemplazar los campos editables de un registro |
| `PATCH` | Actualizar los campos enviados |
| `DELETE` | Retirar lógicamente el registro |

**Pendiente de aprobación:** las rutas individuales de las siete tablas con clave primaria compuesta deberán identificar **ambos valores** de su clave. Su formato definitivo se establecerá en `6_contracts.md` de V2; no debe suponerse que basta con un único `{id}`.

---

## Claves foráneas e integridad referencial

En V2, las claves foráneas conectan los recursos entre sí. Esto requiere:

1. **Validar relaciones:** no permitir, por ejemplo, registrar un `semillero` asociado a un `grupo_investigacion` inexistente.
2. **Validar el estado lógico:** comprobar también que los recursos relacionados estén activos, según las reglas aprobadas en el Spec Kit.
3. **Prevenir asociaciones duplicadas:** respetar las claves primarias compuestas de las siete tablas puente.
4. **Mostrar opciones en el frontend:** cargar los desplegables de selección de relaciones desde la API, sin consultar MariaDB directamente.
5. **Traducir errores:** devolver respuestas HTTP consistentes, sin exponer errores internos de SQL; se propone `409` para conflictos de integridad y `422` para datos inválidos, sujetos al contrato aprobado.

La base de datos garantiza las restricciones físicas de FK; la aplicación debe validar además las reglas de negocio, incluido el uso de registros inactivos.

---

## Borrado lógico

El módulo de Investigación utiliza el campo:

```sql
activo BOOLEAN NOT NULL DEFAULT TRUE
```

El `DELETE` de la API **no elimina físicamente** la fila. Marca el recurso como inactivo:

```sql
UPDATE nombre_tabla
SET activo = FALSE
WHERE ...
  AND activo = TRUE;
```

Los listados normales devuelven únicamente los registros activos. El campo `activo` no debe ser editable directamente mediante `POST`, `PUT` o `PATCH`.

En V2 también se deben definir las reglas de retiro y de nueva creación de asociaciones con clave primaria compuesta, para evitar inconsistencias con relaciones históricas inactivas.

---

## Pruebas y criterios de aceptación de V2

Para considerar finalizada la V2, será necesario comprobar:

- Los **seis CRUD de V1** siguen funcionando sin regresiones.
- Los **diez CRUD de V2** funcionan tanto en la API como en el frontend.
- No se aceptan claves foráneas que incumplan las reglas de integridad y estado activo.
- Las siete asociaciones con clave compuesta identifican correctamente cada registro y rechazan duplicados.
- `PUT` y `PATCH` cumplen comportamientos diferentes según los contratos.
- `DELETE` realiza borrado lógico y los listados ocultan los registros retirados.
- Los formularios cargan las opciones de relaciones mediante peticiones HTTP a la API.
- El frontend sigue mostrando un mensaje adecuado cuando la API no está disponible, sin conectarse a la base de datos.
- Las pruebas de capas y los escenarios de integración quedan documentados en el Spec Kit.

**Estado de estas pruebas:** las comprobaciones de V1 ya fueron realizadas por el equipo; las pruebas nuevas de V2 permanecen pendientes hasta su implementación.

---

## Trabajo por versiones

| Versión | Alcance | Estado |
|---|---|---|
| **V1** | Seis CRUD sin claves foráneas | Implementados y comprobados por el equipo |
| **V2** | Diez CRUD con claves foráneas, manteniendo V1 | Documentación y desarrollo pendientes |
| **V3** | Usuarios, roles, JWT y autorización | Futura |
| **V4** | Consultas multitabla, dashboard y despliegue | Futura |

**Este README describe el alcance previsto de V2.** Conforme avance la implementación, deberán actualizarse el estado, las rutas aprobadas, los comandos de prueba y los resultados reales antes del cierre y la entrega de la versión.
