# 7. Quickstart — V2: Investigación (PHP 8.3, MariaDB y Docker)

> **ESTADO (29-10-2026): GUÍA ACTUALIZADA A LA IMPLEMENTACIÓN FUNCIONAL DE V1 + V2.**  
> Se contrastó con el `7_quickstart.md` provisional, los documentos `2_spec.md` a `6_contracts.md` actualizados, las carpetas finales compartidas `api_investigacion/` y `front_php/` y las salidas de pruebas comunicadas durante el desarrollo. Las diez entidades V2 se implementaron y probaron por recurso; **esto no demuestra todavía una regresión integral posterior al merge, la aprobación formal ni la publicación del tag `v2`**.

## 1. Precondiciones

- Tener **Docker Desktop** con Docker Compose disponible y el motor iniciado.
- Tener los **dos repositorios independientes**, backend (`Proyecto_Investigacion_Backend`) y frontend (`Proyecto_Investigacion_Frontend`), en sus ubicaciones configuradas por el equipo. La composición usada durante el desarrollo se ejecutó desde la **raíz del backend**, donde está `docker-compose.yml`, y levantó tanto `api-investigacion` como `front-php`.
- Contar con `db/init.sql` y los archivos de configuración que necesita `docker-compose.yml`. Comprobar los valores requeridos a partir de `.env.example` y del Compose vigente; **no copiar contraseñas ni incluir `.env` en Git**.
- Disponer de **PHP CLI** si se quiere ejecutar `php -l` desde la terminal de Windows. La ejecución dentro de Docker utiliza PHP 8.3.
- Conservar la base V1: seis recursos sin FK. V2 incorpora diez recursos con FK; el total implementado es de **16 recursos**.
- Mantener una rama de trabajo y el árbol de cambios bajo control de Git; los merges definitivos y el tag `v2` deben verificarse, no suponerse.

**Arquitectura en uso:** `front_php` → **HTTP/JSON** → `api_investigacion` → **PDO** → MariaDB. El frontend no utiliza PDO ni conexiones directas a la base de datos. `front_php/cliente_api.php` consume HTTP mediante `stream_context_create()` y `file_get_contents()`. La exigencia literal de **cURL** de la metodología académica requiere todavía decisión o adecuación; **no documentar que se usa cURL en PHP mientras el código conserve el cliente actual**.

## 2. Verificar configuración y levantar servicios

Abrir PowerShell en la raíz de **`Proyecto_Investigacion_Backend`**:

```powershell
docker compose config
docker compose config --services
docker compose up -d --build
docker compose ps
```

- `docker compose config` permite detectar errores de configuración y variables requeridas antes del arranque; evitar publicar su salida si contiene datos sensibles.
- En el entorno utilizado durante V2 figuran los servicios `api-investigacion`, `front-php` y MariaDB; comprobar los nombres exactos con `config --services` si cambia el Compose.
- Si cambió **solo el código del backend**:

  ```powershell
  docker compose up -d --build api-investigacion
  ```

- Si cambió **solo el código del frontend**:

  ```powershell
  docker compose up -d --build front-php
  ```

Los Dockerfile actuales copian el código al construir la imagen (`COPY . .`), por lo que debe reconstruirse el servicio afectado después de copiar archivos PHP nuevos o modificados.

### 2.1. Direcciones del entorno comprobado durante V2

| Servicio | Acceso desde el equipo anfitrión |
|---|---|
| Frontend PHP | `http://localhost:8110` |
| API PHP | `http://localhost:8111` |
| Diagnóstico de API | `http://localhost:8111/` |
| phpMyAdmin | `http://localhost:8105` |
| MariaDB | `localhost:13330` (puerto TCP publicado) |

Estas direcciones corresponden al entorno local utilizado durante las pruebas: verificar las asignaciones publicadas con `docker compose ps` si otro equipo usa puertos distintos.

**URL interna del frontend:** la función `apiUrl()` de `front_php/cliente_api.php` lee `getenv('URL_API')`. Desde el contenedor del frontend, la dirección debe apuntar al **nombre del servicio** de la API en la red de Docker (en la composición utilizada: `http://api-investigacion:8111`), no a `localhost:8111` del propio contenedor. Comprobar el valor definido por el Compose vigente.

### 2.2. Detener sin perder la base de datos

```powershell
docker compose down
```

**No usar `docker compose down -v`** como procedimiento normal: `-v` puede eliminar el volumen persistente de MariaDB. Reconstruir una imagen **no** requiere borrar volúmenes. Asimismo, el archivo `db/init.sql` puede estar configurado como inicialización de la base en un volumen nuevo; no suponer que se ejecuta nuevamente con cada `up --build`.

## 3. Configurar la URL de API y comprobar respuestas

### 3.1. Diagnóstico de la API

Desde **PowerShell del equipo anfitrión**:

```powershell
$API = 'http://localhost:8111'
$diagnostico = Invoke-RestMethod -Uri "$API/" -Method GET
$diagnostico | ConvertTo-Json -Depth 5
$diagnostico.recursos
```

La API actual anuncia **16 nombres de recursos**: los seis de V1 y los diez de V2. En el código compartido, el campo de diagnóstico `version` conserva el texto `v2-en-desarrollo`; **no interpretarlo como un tag ni como publicación oficial**.

### 3.2. Comprobar los 16 listados sin modificar datos

```powershell
$API = 'http://localhost:8111'
$recursos = @(
    'area_conocimiento',
    'objetivo_desarrollo_sostenible',
    'area_aplicacion',
    'termino_clave',
    'universidad',
    'linea_investigacion',
    'docente',
    'grupo_investigacion',
    'semillero',
    'participa_grupo',
    'participa_semillero',
    'ac_linea',
    'ods_linea',
    'aa_linea',
    'grupo_linea',
    'semillero_linea'
)

foreach ($recurso in $recursos) {
    try {
        $r = Invoke-WebRequest -Uri "$API/api/$recurso" -Method GET `
            -UseBasicParsing -ErrorAction Stop
        Write-Host "$recurso -> HTTP $($r.StatusCode)"
    }
    catch {
        $codigo = if ($_.Exception.Response) {
            [int]$_.Exception.Response.StatusCode
        } else { 'SIN RESPUESTA' }
        Write-Host "$recurso -> ERROR $codigo"
    }
}
```

**Resultado esperado:** para cada recurso, **HTTP `200`** cuando tiene registros activos o **HTTP `204`** si la lista está vacía. El `204` no tiene cuerpo JSON y **no es un error**. Un `404`, `500` o falta de respuesta requiere diagnóstico antes de proseguir.

### 3.3. Comprobar una ficha por su clave real

Las rutas individuales de V2 usan una PK simple o **dos segmentos** si tienen PK compuesta:

| Recurso V2 | Ruta individual |
|---|---|
| `docente` | `/api/docente/{cedula}` |
| `grupo_investigacion` | `/api/grupo_investigacion/{id}` |
| `semillero` | `/api/semillero/{id}` |
| `participa_grupo` | `/api/participa_grupo/{docente_cedula}/{grupo_investigacion_id}` |
| `participa_semillero` | `/api/participa_semillero/{docente}/{semillero}` |
| `ac_linea` | `/api/ac_linea/{linea_investigacion}/{area_conocimiento}` |
| `ods_linea` | `/api/ods_linea/{linea_investigacion}/{ods}` |
| `aa_linea` | `/api/aa_linea/{area_aplicacion}/{linea_investigacion}` |
| `grupo_linea` | `/api/grupo_linea/{grupo_investigacion}/{linea_investigacion}` |
| `semillero_linea` | `/api/semillero_linea/{semillero}/{linea_investigacion}` |

Sustituir los campos entre llaves por **identificadores activos obtenidos del listado**. En `ac_linea`, `area_conocimiento` es `VARCHAR(6)` (por ejemplo, `1A01`), no un entero. Una asociación retirada responde `404` cuando se consulta por su ficha.

## 4. Verificaciones por cada entidad

La validación por recurso sigue el mismo orden empleado en el desarrollo: comprobar sintaxis, reconstruir API, consultar listado, crear datos de prueba válidos, comprobar operaciones/errores, retirarlos lógicamente, verificar la BD y, finalmente, comprobar el frontend.

### 4.1. Revisar sintaxis antes de reconstruir

Desde la raíz del backend:

```powershell
Get-ChildItem -Path .\api_investigacion -Recurse -Filter *.php |
    ForEach-Object { php -l $_.FullName }
```

Desde la raíz del repositorio **frontend**:

```powershell
Get-ChildItem -Path .\front_php -Recurse -Filter *.php |
    ForEach-Object { php -l $_.FullName }
```

Las comprobaciones de sintaxis **no sustituyen** las pruebas HTTP ni la prueba de navegación en Docker.

### 4.2. Operaciones y respuestas esperadas

| Grupo | GET colección | POST colección | GET ficha | PUT/PATCH ficha | DELETE ficha |
|---|---|---|---|---|---|
| `docente`, `grupo_investigacion`, `semillero` | `200`/`204` | `201` | `200` | `200` para actualizaciones válidas | `200`, borrado lógico |
| `participa_grupo`, `participa_semillero` | `200`/`204` | `201` | `200` | `200` solo para rol/fechas editables | `200`, borrado lógico |
| `ac_linea`, `ods_linea`, `aa_linea`, `grupo_linea`, `semillero_linea` | `200`/`204` | `201` | `200` | **`405`** (no existen campos editables distintos de la PK) | `200`, borrado lógico |

Verificar también: `404` al consultar/retirar una ficha inexistente o inactiva, `409` por duplicado o referencia padre inexistente/inactiva, y `422` por cuerpos inválidos según el controlador. Los códigos para rutas mal formadas pueden variar (`400`, `404` o `422`) según el punto donde falle la solicitud; usar como referencia `6_contracts.md` y la respuesta efectiva. Las dos columnas de las PK compuestas **son inmutables**.

### 4.3. Ejecutar los scripts HTTP disponibles

En las pruebas de desarrollo se ejecutaron scripts de PowerShell desde la raíz del backend. **Comprobar primero qué scripts están realmente presentes en el checkout**; las carpetas `api_investigacion/` y `front_php/` por sí solas no incluyen todos los `.ps1`:

```powershell
Get-ChildItem -Path . -Filter 'pruebas_*.ps1' -File | Select-Object Name
```

Ejemplos de scripts utilizados durante el desarrollo, si se encuentran en esa carpeta:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\pruebas_participa_semillero_v2.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\pruebas_ods_linea_v2_CORREGIDO.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\pruebas_aa_linea_v2.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\pruebas_grupo_linea_v2.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\pruebas_semillero_linea_v2.ps1
```

Utilizar también los scripts de `docente`, `grupo_investigacion`, `semillero`, `participa_grupo` y los demás recursos **solo si existen en el repositorio o en los entregables del equipo**. No se debe afirmar que un archivo no localizado está disponible. En particular, el script original de `ods_linea` tuvo un problema al enviar contenido en un GET desde PowerShell; la versión **`_CORREGIDO.ps1`** resolvió ese problema durante la ejecución documentada.

**Advertencia sobre los datos:** los scripts crean datos temporales y los retiran mediante **borrado lógico**, por lo que pueden permanecer filas con `activo = 0`. Ejecutarlos sobre una base destinada a la entrega requiere que el equipo acepte esos registros de prueba. No reutilizar claves de asociaciones retiradas: el POST duplicado devuelve `409` y no las reactiva.

### 4.4. Consulta SQL manual en phpMyAdmin (fuera del script)

Abrir `http://localhost:8105`, seleccionar la base de investigación y consultar la fila creada por el script utilizando los **ID que imprime esa ejecución**. Ejemplo tomado de una ejecución documentada de `semillero_linea`:

```sql
SELECT semillero, linea_investigacion, activo
FROM semillero_linea
WHERE semillero = 1620578068
  AND linea_investigacion = 20;
```

En aquella ejecución se comprobó `activo = 0`. **No reutilizar estos ID como datos nuevos**: son una evidencia histórica, no datos fijos de la base. Para otro recurso, cambiar el nombre de la tabla y las columnas según `5_data_model.md`.

### 4.5. Verificación manual de pantallas

1. Abrir `http://localhost:8110` y confirmar que el panel muestra las seis entidades V1 y las diez V2.
2. Abrir el listado de cada recurso y comprobar que representa los registros activos y un estado vacío sin datos inventados.
3. Comprobar que los desplegables muestran **registros padres activos** obtenidos mediante la API.
4. Crear una entidad o asociación con claves válidas; volver al listado y consultar su detalle.
5. En las entidades editables, comprobar PUT/PATCH y la permanencia de las claves primarias. En las cinco tablas puente simples, **no esperar un botón de edición**.
6. Retirar un registro de prueba, verificar que desaparece de las vistas activas y comprobar `activo = 0` en phpMyAdmin.
7. Verificar que los avisos de duplicados, validación y API desconectada se muestran sin mensajes SQL internos al usuario.

Rutas de pantalla de las cinco asociaciones simples:

| Recurso | Pantalla de listado | Formulario de alta |
|---|---|---|
| `ac_linea` | `/asociaciones-ac-linea` | `/asociaciones-ac-linea/nuevo` |
| `ods_linea` | `/asociaciones-ods-linea` | `/asociaciones-ods-linea/nuevo` |
| `aa_linea` | `/asociaciones-aa-linea` | `/asociaciones-aa-linea/nuevo` |
| `grupo_linea` | `/asociaciones-grupo-linea` | `/asociaciones-grupo-linea/nuevo` |
| `semillero_linea` | `/asociaciones-semillero-linea` | `/asociaciones-semillero-linea/nuevo` |

Las rutas restantes de V1 y V2 están declaradas explícitamente en `front_php/index.php` y en `front_php/rutas/`.

## 5. Regresión V1 y separación frontend–backend

Esta sección es parte de la **compuerta de cierre** de V2: las pruebas funcionales por entidad ya se reportaron, pero **la regresión consolidada después del merge sigue pendiente de evidencia**.

1. **Regresión de V1:** ejecutar los escenarios de `7_quickstart.md` y `9_checklist.md` de V1 sobre las seis entidades originales, comprobando que la ampliación de V2 no alteró sus contratos.
2. **Regresión de V2:** repetir los GET y el flujo CRUD permitido para los diez recursos en la versión que se vaya a entregar. Registrar resultado, fecha, rama o commit.
3. **Separación de capas:** revisar que `front_php/cliente_api.php` usa HTTP y no importa PDO, repositorios o modelos del backend.
4. **Frontend con la API apagada:** detener únicamente la API y comprobar desde el navegador que el frontend sigue sirviendo su estructura y comunica indisponibilidad, sin mostrar datos ficticios ni acceder directamente a la BD:

   ```powershell
   docker compose stop api-investigacion
   # Verificar http://localhost:8110 en el navegador
   docker compose start api-investigacion
   ```

   No afirmar que la prueba pasó sin realizarla y registrar su resultado. No detener MariaDB ni eliminar volúmenes para este caso.
5. **Recursos estáticos:** comprobar la hoja de estilos local y el contenido CSS:

   ```powershell
   Invoke-WebRequest -Uri 'http://localhost:8110/publico/css/estilos.css' `
       -UseBasicParsing | Select-Object StatusCode, Headers
   ```

   Confirmar HTTP `200` y tipo `text/css`; Bootstrap debe servirse desde `front_php/publico/`, sin requerir CDN.
6. **Errores seguros:** verificar de forma controlada que un problema de conexión/SQL no revele contraseñas, trazas o consultas internas en la respuesta HTTP. **Pendiente de evidencia de cierre**.

### 5.1. Evidencias observadas durante el desarrollo

| Recurso o grupo | Resultado comunicado | Alcance de la evidencia |
|---|---|---|
| V1 (6 entidades) | CRUD del frontend y `php -l` comprobados durante la reorganización | No sustituye regresión final integrada |
| `grupo_investigacion` | 19 comprobaciones HTTP aprobadas | Backend por recurso y frontend probado |
| `semillero` | 25 comprobaciones HTTP aprobadas | Backend por recurso y frontend probado |
| `docente` | 30 comprobaciones HTTP aprobadas | Backend por recurso y frontend probado |
| `participa_grupo` | 41 comprobaciones HTTP aprobadas | Backend por recurso y frontend probado |
| `participa_semillero` | 46 comprobaciones HTTP aprobadas | Backend por recurso y frontend probado |
| `ac_linea` | Comprobaciones manuales de GET, POST, duplicados, retiro y `activo = 0` | Backend por recurso y frontend probado |
| `ods_linea` | 50 comprobaciones HTTP aprobadas con script corregido; SQL de retiro | Backend por recurso y frontend probado |
| `aa_linea` | 50 comprobaciones HTTP aprobadas; SQL de retiro | Backend por recurso y frontend probado |
| `grupo_linea` | 50 comprobaciones HTTP aprobadas; frontend probado | La consulta SQL manual final no tiene evidencia explícita conservada en este cierre |
| `semillero_linea` | 54 comprobaciones HTTP aprobadas; SQL `activo = 0` | Backend por recurso y frontend probado, tras corregir `SemilleroLinea::$semillero` para PHP 8.3 |

En el primer intento de `semillero_linea`, una propiedad dinámica no declarada produjo una advertencia de PHP 8.3 y afectó la respuesta HTTP. Se corrigió el modelo y la nueva ejecución aprobó las 54 comprobaciones. Esta incidencia **no debe confundirse con una falla persistente del endpoint**.

## 6. Registro de ejecución, solución de problemas y pendientes de cierre

### 6.1. Registro para completar en la versión integrada

| Comprobación | Acción o ruta | Estado según evidencia disponible | Responsable / fecha / commit |
|---|---|---|---|
| Arranque de API y MariaDB | `docker compose up -d --build api-investigacion` | Ejecutado durante desarrollo V2 | Completar en cierre |
| Arranque de frontend | `docker compose up -d --build front-php` | Ejecutado durante desarrollo V2 | Completar en cierre |
| Diagnóstico y listados | `GET /`, GET de los recursos V1 y V2 | V2 comprobada por recurso; regresión 16/16 posterior al merge pendiente | Completar |
| Asociaciones y FK | Scripts/manuales indicados en §4 | Comprobaciones por recurso aprobadas | Completar |
| Retiro lógico | `DELETE` y consulta `activo = 0` | Verificado en varias tablas; cerrar evidencia integral | Completar |
| Regresión de V1 + V2 | Secciones 3–5 | **Pendiente después del merge** | Completar |
| Frontend sin API | §5, paso 4 | **Pendiente de comprobación formal** | Completar |
| Verificación de cURL académico | Comparar cliente HTTP con `0_METODOLOGIA.md` | **Pendiente de decisión/adecuación** | Completar |
| Política de retiro de padres | Revisar dependencias activas | **Pendiente de decisión del equipo** | Completar |
| PR, merge y tag | GitHub, backend y frontend | **No verificados en esta guía** | Completar |

### 6.2. Diagnóstico de fallos frecuentes

| Síntoma | Qué revisar |
|---|---|
| `Invoke-WebRequest` no conecta | `docker compose ps`, puerto `8111`, contenedor `api-investigacion`, logs con `docker compose logs --tail 100 api-investigacion` |
| Frontend muestra API no disponible | Variable `URL_API` dentro del servicio `front-php`, nombre de host de Docker, estado de la API |
| HTTP `204` en GET colección | No existen filas activas; es una respuesta normal sin cuerpo |
| HTTP `404` en ficha | Identificadores o su orden, ruta con dos segmentos, fila inexistente o retirada |
| HTTP `409` al crear | Clave compuesta repetida (también si se retiró antes), o FK inexistente/inactiva |
| HTTP `422` | Campos requeridos, tipos, JSON mal formado o intento de enviar `activo` |
| HTTP `405` en una asociación simple | PUT/PATCH no aplican a tablas que solo tienen las dos PK y `activo` |
| Cambios PHP no se reflejan | Reconstruir **solo** el servicio editado con `docker compose up -d --build ...` |
| Advertencia `Creation of dynamic property` | Declarar en el modelo la propiedad PHP con el mismo nombre que usa el constructor, como se corrigió en `SemilleroLinea.php` |
| Fallo PowerShell al enviar contenido en GET | Utilizar el script `pruebas_ods_linea_v2_CORREGIDO.ps1`; no enviar cuerpo HTTP para peticiones GET |
| Necesidad de revisar los logs | `docker compose logs --tail 100 api-investigacion` o `docker compose logs --tail 100 front-php` |

**Criterio de cierre de la V2:** conservar pruebas de la versión consolidada en ambos repositorios, resolver o registrar formalmente las decisiones pendientes y actualizar `8_tasks.md` y `9_checklist.md`. El éxito de las pruebas por entidad no autoriza por sí solo marcar estos pasos de cierre como completos.
