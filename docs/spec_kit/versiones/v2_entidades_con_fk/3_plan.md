# 3. Plan técnico — V2: entidades con FK y reorganización

> **ESTADO (29-10-2026): IMPLEMENTACIÓN FUNCIONAL REALIZADA; CIERRE DE INTEGRACIÓN Y DOCUMENTACIÓN PENDIENTE.**  
> Este plan describe la **implementación real** de las diez entidades V2 como ampliación de los seis recursos V1. Se actualizó a partir del borrador de este `3_plan.md`, el `2_spec.md` actualizado, las carpetas finales `api_investigacion/` y `front_php/` compartidas por el equipo y los resultados de pruebas comunicados durante el desarrollo. **No implica aprobación formal, merge verificado ni etiqueta `v2` publicada.**

## 1. Arquitectura conservada

La V2 mantiene PHP 8.3 sin framework y la separación de responsabilidades de V1:

```text
Petición HTTP / JSON
        ↓
api_investigacion/index.php       (enrutador de recursos)
        ↓
Controlador<Entidad>              (forma del cuerpo, HTTP, errores)
        ↓ interfaz IServicio<Entidad>
Servicio<Entidad>                 (reglas de negocio y FK activas)
        ↓ interfaz IRepositorio<Entidad>
Repositorio<Entidad>MariaDB       (SQL parametrizado con PDO)
        ↓
MariaDB                            (PK, FK, activo)
```

- **Modelo:** representa los campos de la entidad y ofrece acceso a sus datos, incluido `toArray()` cuando corresponde.
- **Controlador:** valida la forma de entrada, delega al servicio y convierte resultados/excepciones en respuestas HTTP. No escribe SQL.
- **Servicio:** aplica reglas de negocio, valida referencias activas e impide cambios de identificadores que forman una PK. No responde directamente con códigos HTTP.
- **Interfaz de servicio y repositorio:** fijan contratos entre capas; las clases concretas se suministran por inyección de dependencias.
- **Repositorio MariaDB:** consulta y modifica registros con PDO y sentencias preparadas; implementa el filtrado de activos y el borrado lógico.
- **`servicios/ensamblador.php`:** concentra los `require_once`, crea una conexión PDO y construye los repositorios, servicios y controladores de los **16 recursos**. Los servicios de V2 reutilizan repositorios padre, en lugar de consultar directamente sus tablas desde un controlador.

`api_investigacion/index.php` mantiene **rutas explícitas por recurso**, no una ruta SQL genérica `/api/{tabla}`. Para las PK simples utiliza la función compartida `enrutarFicha()`; para las siete PK compuestas dispone de rutas con **dos segmentos**, en el orden de las columnas de la clave. Las respuestas utilizan JSON, salvo los listados vacíos (`204`, sin contenido).

La conexión definida en el ensamblador lee `DB_DSN`, `DB_USUARIO` y `DB_CLAVE` del entorno, habilita errores PDO mediante excepciones, desactiva la emulación de sentencias preparadas y establece `PDO::MYSQL_ATTR_FOUND_ROWS => true`. Esta última opción evita interpretar como inexistente una fila encontrada por un `UPDATE` que no cambió sus valores.

## 2. Organización REAL del BACKEND V1 + V2

La versión final compartida contiene las **seis entidades V1 y las diez V2**; las clases y carpetas que el borrador marcaba «pendientes» ahora existen:

```text
api_investigacion/
├── index.php
├── Dockerfile
├── modelos/
│   ├── [6 modelos V1]
│   ├── Docente.php
│   ├── GrupoInvestigacion.php
│   ├── Semillero.php
│   ├── ParticipaGrupo.php
│   ├── ParticipaSemillero.php
│   ├── AcLinea.php
│   ├── OdsLinea.php
│   ├── AaLinea.php
│   ├── GrupoLinea.php
│   └── SemilleroLinea.php
├── controladores/
│   ├── [6 controladores V1]
│   └── Controlador<EntidadV2>.php           (10 archivos)
├── repositorios/
│   ├── Repositorios_<EntidadV1>/           (6 carpetas)
│   │   ├── IRepositorio<EntidadV1>.php
│   │   └── Repositorio<EntidadV1>MariaDB.php
│   └── Repositorios_<EntidadV2>/           (10 carpetas, misma convención)
│       ├── IRepositorio<EntidadV2>.php
│       └── Repositorio<EntidadV2>MariaDB.php
├── servicios/
│   ├── ensamblador.php
│   ├── Servicios_<EntidadV1>/               (6 carpetas)
│   │   ├── IServicio<EntidadV1>.php
│   │   └── Servicio<EntidadV1>.php
│   └── Servicios_<EntidadV2>/               (10 carpetas)
│       ├── IServicio<EntidadV2>.php
│       └── Servicio<EntidadV2>.php
├── excepciones/
│   ├── NoEncontradoExcepcion.php
│   └── ConflictoExcepcion.php
└── pruebas/
    ├── prueba_capas.php
    ├── prueba_capas_restantes.php
    └── prueba_controladores_restantes.php
```

Los nombres entre `<...>` y `[... ]` en este árbol son **convenciones explicativas**, no nombres literales de archivos. Los archivos reales se encuentran en las carpetas del repositorio. Los scripts HTTP de PowerShell utilizados para validar entidades V2 se ejecutaron desde la raíz del backend; no se presentan en el árbol de `api_investigacion/` porque no forman parte de esa carpeta compartida.

**Entidades implementadas:**

| V1 (conservadas) | V2 — entidades con FK y asociaciones |
|---|---|
| `area_conocimiento` | `docente` |
| `objetivo_desarrollo_sostenible` | `grupo_investigacion` |
| `area_aplicacion` | `semillero` |
| `termino_clave` | `participa_grupo` |
| `universidad` | `participa_semillero` |
| `linea_investigacion` | `ac_linea`, `ods_linea`, `aa_linea`, `grupo_linea`, `semillero_linea` |

La V2 **amplía** clases y rutas sin sustituir los contratos de V1. No introduce las tablas de autenticación, roles o funcionalidades de V3/V4.

## 3. Organización REAL del FRONTEND V1 + V2

El frontend está en un repositorio independiente de la API. La estructura final es:

```text
front_php/
├── index.php                              (enrutador de pantallas)
├── cliente_api.php                        (cliente HTTP/JSON compartido)
├── Dockerfile
├── rutas/
│   ├── compartidas/
│   │   └── acciones_catalogo.php
│   ├── rutas_<entidad_v1>.php              (6 rutas)
│   └── rutas_<entidad_v2>.php              (10 rutas)
├── vistas/
│   ├── inicio.php
│   ├── compartidas/
│   │   ├── plantilla.php
│   │   ├── crud_catalogos.php
│   │   └── no_encontrada.php
│   ├── <entidad_v1>/                       (6 carpetas)
│   │   ├── lista.php
│   │   └── formulario.php
│   └── <entidad_v2>/                       (10 carpetas)
│       ├── lista.php
│       └── formulario.php
└── publico/
    ├── bootstrap.min.css
    ├── bootstrap.bundle.min.js
    └── css/
        └── estilos.css
```

En este árbol, `<entidad_v1>` y `<entidad_v2>` representan las carpetas concretas por recurso. En particular, existen `vistas/docente/`, `vistas/grupo_investigacion/`, `vistas/semillero/`, `vistas/participa_grupo/`, `vistas/participa_semillero/` y las cinco carpetas de asociaciones con líneas de investigación.

**Recorrido de un formulario:** navegador → `front_php/index.php` → `rutas/rutas_<entidad>.php` → `cliente_api.php` → API REST → respuesta JSON → `vistas/<entidad>/...`. Las rutas HTML para una tabla puente proporcionan listado, formulario de creación, consulta de detalle y retiro lógico. Las entidades con campos editables también presentan sus acciones de actualización.

- Los selectores FK obtienen registros **activos desde la API** y presentan nombres legibles, conservando los identificadores para la solicitud HTTP.
- `cliente_api.php` obtiene la base HTTP desde `URL_API`, construye rutas con `apiUrl()` y permite enviar ambas partes de una PK compuesta como segmentos separados.
- **Tecnología observada:** `stream_context_create()` y `file_get_contents()` con contexto HTTP, no cURL. No utiliza PDO, modelos ni repositorios del backend. La metodología académica menciona cURL; su exigencia queda **pendiente de revisión** (**C11** de `2_spec.md`).
- `204` significa «sin registros»; una conexión fallida se maneja como «servicio no disponible». La verificación integral con la API apagada todavía necesita evidencia de aceptación.
- Bootstrap y estilos se sirven localmente desde `publico/`; el enrutador deja que el servidor PHP entregue directamente los archivos estáticos.

```text
Navegador → Frontend PHP (:8110) → HTTP/JSON → API PHP (:8111) → PDO → MariaDB
```

No debe existir el atajo `frontend → MariaDB`.

## 4. Secuencia de trabajo aplicada y cierre pendiente

El borrador proponía implementar V2 después de reorganizar V1. Durante el desarrollo se aplicó el siguiente flujo **por entidad**, que debe mantenerse si hay correcciones:

1. **Base V1:** conservar los seis recursos, los controladores y las carpetas ya reorganizadas; trabajar en ramas de funcionalidad, sin editar directamente `main`.
2. **Modelo y capas:** desarrollar modelo, interfaz/repositorio PDO, interfaz/servicio y controlador (seis archivos PHP por entidad) usando la convención existente.
3. **Sintaxis:** ejecutar `php -l` en cada uno de los seis archivos.
4. **Integración compartida:** incorporar el controlador y sus dependencias en `api_investigacion/servicios/ensamblador.php` y registrar las rutas en `api_investigacion/index.php`, **comparando primero** los archivos actuales para no perder otras entidades.
5. **API real:** reconstruir el servicio Docker, comprobar el GET y ejecutar pruebas HTTP de creación, consulta, validaciones, integridad FK, duplicados, métodos no permitidos y retiro lógico.
6. **Base de datos:** comprobar de manera manual en phpMyAdmin que la fila retirada permanece con `activo = 0`.
7. **Frontend:** incorporar `rutas/rutas_<entidad>.php`, `vistas/<entidad>/lista.php` y `formulario.php`; actualizar los archivos compartidos `index.php`, `cliente_api.php`, `vistas/inicio.php` y `vistas/compartidas/plantilla.php`; ejecutar `php -l` y reconstruir Docker.
8. **Interfaz:** registrar, consultar, actualizar **cuando la entidad lo admite**, y retirar desde el navegador; revisar mensajes y selectores de padres activos.
9. **Control de cambios:** utilizar commits y PR por repositorio y coordinar las modificaciones de archivos compartidos. **No se declara verificado** el merge final de ambos repositorios ni una etiqueta `v2`.

El orden funcional permitió reutilizar padres existentes. Las asociaciones de Miguel se desarrollaron después de disponer de los recursos de V1 y de los grupos/semilleros construidos en V2. Los archivos consolidados permiten revisar las 16 entidades en ambos proyectos, pero **no sustituyen una regresión final después del merge**.

### Ejecución empleada

Desde la raíz de `Proyecto_Investigacion_Backend`:

```powershell
# Incorporar cambios que se hayan copiado en la API
php -l .\api_investigacion\index.php
php -l .\api_investigacion\servicios\ensamblador.php
docker compose up -d --build api-investigacion

# Comprobar un listado de V2
Invoke-WebRequest -Uri "http://localhost:8111/api/semillero_linea" -UseBasicParsing |
    Select-Object StatusCode, Content

# Ejemplo de script HTTP ejecutado durante el desarrollo
powershell -NoProfile -ExecutionPolicy Bypass -File .\pruebas_semillero_linea_v2.ps1

# Actualizar únicamente el frontend cuando sus archivos cambian
docker compose up -d --build front-php
```

**Nota Docker:** los Dockerfile compartidos emplean `COPY . .`; por eso se reconstruyeron los servicios después de copiar los archivos. No ejecutar `docker compose down -v` para aplicar cambios: eliminaría volúmenes persistentes.

## 5. Integridad referencial y operaciones

### Claves y dependencias

- **PK simples V2:** `docente.cedula`, `grupo_investigacion.id` y `semillero.id`. Sus identificadores se reciben del cliente según el esquema revisado; `semillero.grupo_investigacion` es FK obligatoria.
- **FK opcionales:** `docente.linea_investigacion_principal` y `grupo_investigacion.universidsad` admiten `null`; si se proporcionan, se verifica que apunten a registros activos. Se respetan literalmente las columnas históricas `nacionalidaad` y `universidsad`.
- **Siete PK compuestas:** `participa_grupo(docente_cedula, grupo_investigacion_id)`, `participa_semillero(docente, semillero)`, `ac_linea(linea_investigacion, area_conocimiento)`, `ods_linea(linea_investigacion, ods)`, `aa_linea(area_aplicacion, linea_investigacion)`, `grupo_linea(grupo_investigacion, linea_investigacion)` y `semillero_linea(semillero, linea_investigacion)`.
- La columna `area_conocimiento` de `ac_linea` es texto `VARCHAR(6)`; las demás claves de esas asociaciones son enteras. **No intercambiar el orden de segmentos en la URL ni renombrar columnas por comodidad.**

### Reglas aplicadas

| Grupo de recursos | Métodos habilitados | Regla clave |
|---|---|---|
| `docente`, `grupo_investigacion`, `semillero` | GET, POST, PUT, PATCH, DELETE | Validaciones de campos, claves y referencias activas |
| `participa_grupo`, `participa_semillero` | GET, POST, PUT, PATCH, DELETE | PK compuesta inmutable; solo se editan `rol`, `fecha_inicio`, `fecha_fin` |
| `ac_linea`, `ods_linea`, `aa_linea`, `grupo_linea`, `semillero_linea` | GET, POST, DELETE | No hay campos editables aparte de las claves; PUT/PATCH devuelven `405` |

- **Participaciones:** `rol` no vacío (máximo 15 caracteres), fechas válidas en formato `YYYY-MM-DD`, `fecha_fin` opcional y no anterior a `fecha_inicio`.
- **FK activas:** la existencia física en MariaDB no basta; el servicio comprueba que la referencia no haya sido retirada. Las FK faltantes o inactivas se rechazan con `409` en los casos probados.
- **Sin reactivación implícita:** una PK compuesta retirada sigue ocupada; un nuevo POST con la misma combinación devuelve `409`. No se hace `UPDATE activo = TRUE` automáticamente.
- **Borrado lógico:** `DELETE` ejecuta un `UPDATE` de `activo` a falso, los listados filtran activos y la consulta posterior/segundo DELETE del mismo registro devuelve `404`.
- **Campos de persistencia:** `activo` no es modificable por el cliente en POST, PUT o PATCH.
- **Errores previstos:** `200`/`201`/`204` para respuestas satisfactorias; `404` para recurso no disponible; `405` para método no permitido; `409` para conflictos de FK o duplicados; `422` para datos inválidos; `500` para errores internos. **No se declara un `400` universal para PATCH vacío**: los controladores de las participaciones probadas devuelven `422`, según `2_spec.md`.

**Decisión aún abierta (C4):** no se ha formalizado una política general sobre retirar una entidad padre mientras mantiene asociaciones hijas activas. El borrado físico protegido por FK no resuelve por sí solo la coherencia de un **retiro lógico**. No introducir cascadas, bloqueos ni reactivaciones nuevas sin acuerdo del equipo y actualización de contratos y pruebas.

## 6. Pruebas y verificación

La evidencia reunida corresponde a comprobaciones **por entidad durante el desarrollo**, no a un proceso integral de regresión posterior al merge:

| Recurso V2 | Evidencia de API comunicada | Evidencia de frontend comunicada |
|---|---|---|
| `grupo_investigacion` | 19 comprobaciones HTTP aprobadas | CRUD probado |
| `semillero` | 25 comprobaciones HTTP aprobadas | CRUD probado |
| `docente` | 30 comprobaciones HTTP aprobadas | CRUD probado |
| `participa_grupo` | 41 comprobaciones HTTP aprobadas | CRUD probado |
| `participa_semillero` | 46 comprobaciones HTTP aprobadas | CRUD probado |
| `ac_linea` | Pruebas manuales HTTP y consulta SQL (`activo = 0`) | Registro, consulta y retiro probados |
| `ods_linea` | 50 comprobaciones HTTP aprobadas | Registro, consulta y retiro probados |
| `aa_linea` | 50 comprobaciones HTTP aprobadas | Registro, consulta y retiro probados |
| `grupo_linea` | 50 comprobaciones HTTP aprobadas | Registro, consulta y retiro probados |
| `semillero_linea` | 54 comprobaciones HTTP aprobadas **tras corregir** el modelo en PHP 8.3 | Registro, consulta y retiro probados |

Se ejecutaron pruebas de sintaxis con `php -l`, pruebas de API sobre Docker/MariaDB y comprobaciones de retiro lógico en phpMyAdmin durante el desarrollo. En `semillero_linea` se corrigió la declaración de la propiedad `$semillero` en `modelos/SemilleroLinea.php` para evitar la advertencia de propiedad dinámica en PHP 8.3; las 54 pruebas posteriores aprobaron.

**Pendiente para la compuerta de cierre:**

1. Regresión documentada de los **seis CRUD V1 y los diez recursos V2** sobre la versión consolidada de las ramas después de integrarlas.
2. Prueba de aceptación de **frontend operativo con API detenida**: debe mostrar indisponibilidad y no acceder a la BD.
3. Comprobación controlada de respuestas `500` sin filtración de información SQL.
4. Resolver la exigencia de **cURL** frente a la implementación HTTP actual; comprobar también los requisitos de marca si se exige en V2.
5. Cotejar formalmente el DDL utilizado con el `db/init.sql` vigente y cerrar la política de retiros de padres con hijos activos.
6. Verificar PR, merge y, si corresponde al proceso de entrega, la etiqueta `v2`.

Estas tareas deben reflejarse después en `7_quickstart.md`, `8_tasks.md` y `9_checklist.md`; su actualización documental todavía no se da por realizada.

## 7. Chequeo de constitución — COMPUERTA 2

Se contrasta el plan con `1_constitution.md` y las decisiones V2 registradas en `2_spec.md`. **Esta tabla no equivale a la aprobación formal del equipo.**

| Regla | Evidencia/implementación | Estado |
|---|---|---|
| PHP puro 8.3, sin framework ni Composer | Dockerfile de API y frontend basados en `php:8.3-cli` | **Verificado en archivos** |
| Capas estrictas e interfaces | Modelos, controladores, servicios e interfaces/repositorios por 16 recursos | **Verificado en estructura** |
| Ensamblador único | `api_investigacion/servicios/ensamblador.php` crea los controladores y una conexión PDO | **Verificado en código** |
| SQL en repositorios y sentencias preparadas | Repositorios concretos MariaDB con PDO; validación de reglas en servicios | **Implementado; revisar en regresión** |
| Front separado y sin PDO | `cliente_api.php` usa HTTP y el frontend tiene su propio repositorio | **Verificado en código** |
| Uso obligatorio de cURL, si aplica | El cliente actual utiliza streams HTTP, no cURL | **Pendiente de decisión C11** |
| Borrado lógico y filtro de activos | DELETE lógico y comprobaciones HTTP/SQL por entidad | **Comprobado por entidad** |
| API específica por recurso | Rutas explícitas para los 16 recursos | **Verificado en código** |
| Español y `strict_types` | Convenciones de nombres y declaraciones presentes en los archivos revisados | **Verificado en archivos** |
| Configuración y secretos | PDO lee `DB_DSN`, `DB_USUARIO`, `DB_CLAVE` del entorno; revisar tratamiento del `docker-compose.yml` local conforme a la constitución del equipo | **Revisión de configuración pendiente** |
| Esquema existente y nombres SQL conservados | PK/FK del módulo, incluso `universidsad` y `nacionalidaad` | **Cotejo formal con rama final pendiente** |
| Cada versión incluye API y frontend | Diez entidades V2 presentes en ambas carpetas, más las seis de V1 | **Implementado** |
| No anticipar V3/V4 | Sin JWT, roles ni dashboard en los recursos entregados de V2 | **Verificado en alcance revisado** |
| Identidad visual / manual de marca | Bootstrap local y `estilos.css`; no se constató `marca.css` ni `MANUAL_DE_MARCA.md` en las carpetas recibidas | **Revisión de alcance y marca pendiente** |
| Pruebas y control de versiones de cierre | Pruebas por entidad; falta evidencia de regresión posmerge y aprobación | **Pendiente** |

**Resultado de la compuerta:** arquitectura y funcionalidades principales de V2 verificadas contra las carpetas recibidas. **Cierre formal condicionado** a las comprobaciones y decisiones pendientes, sin asumir que ya hubo aprobación del equipo, merge final o publicación del tag `v2`.

---

**Alcance de este archivo:** actualiza únicamente `3_plan.md`. El `2_spec.md` ya fue revisado por separado. `4_research.md`, `5_data_model.md`, `6_contracts.md`, `7_quickstart.md`, `8_tasks.md` y `9_checklist.md` siguen pendientes de actualización individual.
