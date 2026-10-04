# Quickstart — v1: Investigación (PHP + MariaDB)

> **Alcance:** seis recursos con CRUD en API y frontend. Esta guía está escrita para **Windows PowerShell 5.1** y los puertos confirmados en el entorno de desarrollo. Para Git Bash, Linux o macOS deben adaptarse los comandos de PowerShell.
>
> **Estado (04/10/2026):** se han comprobado los seis CRUD desde el navegador; los tres recursos `area_conocimiento`, `objetivo_desarrollo_sostenible` y `area_aplicacion` también pasaron pruebas HTTP reales de CRUD y errores. Las pruebas de capas de los seis recursos se ejecutaron con MariaDB apagada. Las comprobaciones pendientes se indican expresamente: instalación desde cero, seis semillas de `universidad`, prueba final del frontend con la API apagada y revisión integral de los contratos.

## 0. Preparar los repositorios y el entorno

Se necesitan Docker Desktop, Git, ambos repositorios y PowerShell. El archivo `docker-compose.yml` se ejecuta **desde la raíz de `Proyecto_Investigacion_Backend`**. El código del frontend permanece en el repositorio separado `Proyecto_Investigacion_Frontend`; compruebe que los volúmenes definidos en Compose apuntan a la copia local correcta.

```powershell
cd C:\Users\angel\OneDrive\Desktop\ProgramacionEnPHP\Proyecto_Investigacion_Backend

git status
docker compose config --services
```

La lista de servicios del entorno probado es `api-investigacion`, `front-php`, `mariadb` y `phpmyadmin`. Confirme que Docker Desktop está encendido y que las variables de entorno necesarias están configuradas. Si necesita crear un `.env`, parta del `.env.example` de su repositorio, sin publicar contraseñas ni sobrescribir una configuración existente.

**No utilice `docker compose down -v` para estas pruebas:** elimina los volúmenes y podría borrar los registros de MariaDB. Tampoco ejecute pruebas de DELETE sobre las filas iniciales del curso.

## 1. Arrancar y comprobar los servicios

```powershell
docker compose up -d --build
docker compose ps
```

| Servicio | Dirección desde Windows | Comprobación |
|---|---|---|
| API | `http://localhost:8111/` | Diagnóstico JSON `200` |
| Frontend | `http://localhost:8110/` | Panel con seis tarjetas |
| phpMyAdmin | `http://localhost:8105/` | Acceso a las tablas |
| MariaDB | Puerto del host `13330` | Contenedor saludable |

Los nombres de servicio y puertos corresponden al entorno que funcionó durante las pruebas; una instalación desde cero todavía debe comprobarse siguiendo este documento.

```powershell
Invoke-WebRequest -UseBasicParsing http://localhost:8111/ |
    Select-Object StatusCode, Content

Invoke-WebRequest -UseBasicParsing http://localhost:8110/ |
    Select-Object StatusCode

# Debe devolver Content-Type: text/css, no text/html.
curl.exe -I http://localhost:8110/publico/bootstrap.min.css
```

El diagnóstico de la API devuelve el mensaje `API de Investigación funcionando`, la versión `v1` y los seis recursos.

## 2. Peticiones HTTP en PowerShell: función `pedir`

En PowerShell, `curl` puede ser un alias de `Invoke-WebRequest`. Se utiliza esta función para enviar JSON UTF-8 y leer tanto respuestas exitosas como errores HTTP, sin depender del formato de comillas de `curl.exe`. Defínala una vez por sesión:

```powershell
$API = 'http://localhost:8111'

function pedir {
    param(
        [Parameter(Mandatory = $true)][string]$Metodo,
        [Parameter(Mandatory = $true)][string]$Ruta,
        [object]$Cuerpo = $null
    )

    $parametros = @{
        Uri             = "$API$Ruta"
        Method          = $Metodo
        UseBasicParsing = $true
        ErrorAction     = 'Stop'
    }

    if ($null -ne $Cuerpo) {
        $json = ConvertTo-Json -InputObject $Cuerpo -Compress -Depth 10
        $parametros['ContentType'] = 'application/json; charset=utf-8'
        $parametros['Body'] = [System.Text.Encoding]::UTF8.GetBytes($json)
    }

    try {
        $respuesta = Invoke-WebRequest @parametros
        $codigo = [int] $respuesta.StatusCode
        $contenido = [string] $respuesta.Content
    }
    catch [System.Net.WebException] {
        $respuesta = $_.Exception.Response
        if ($null -eq $respuesta) {
            throw 'La API no respondió. Revise docker compose ps y la URL.'
        }
        $codigo = [int] $respuesta.StatusCode
        $flujo = $respuesta.GetResponseStream()
        $contenido = ''
        if ($null -ne $flujo) {
            $lector = New-Object System.IO.StreamReader($flujo)
            try { $contenido = $lector.ReadToEnd() }
            finally { $lector.Dispose() }
        }
    }

    [PSCustomObject]@{
        HTTP = $codigo
        JSON = $contenido
    }
}

# Ejemplo: mostrar la respuesta completa.
pedir GET '/' | Format-List
```

`HTTP` contiene el código de estado y `JSON` el cuerpo recibido. Una respuesta `204` es correcta y **no tiene cuerpo**. Los comandos siguientes están pensados para ejecutarse desde PowerShell, no desde Git Bash.

## 3. Los seis recursos y las semillas iniciales

| Recurso de la API | Llave | Registros de referencia esperados |
|---|---|---:|
| `/api/area_conocimiento` | `id` texto, hasta 6 caracteres | 218 |
| `/api/objetivo_desarrollo_sostenible` | `id` entero | 17 |
| `/api/area_aplicacion` | `id` entero | 21 |
| `/api/termino_clave` | `termino`, hasta 30 caracteres | Puede estar vacío |
| `/api/universidad` | `id` entero | **6 pendientes de verificar en `init.sql`** |
| `/api/linea_investigacion` | `id` generado por MariaDB | Puede estar vacío |

```powershell
$recursos = @(
    'area_conocimiento',
    'objetivo_desarrollo_sostenible',
    'area_aplicacion',
    'termino_clave',
    'universidad',
    'linea_investigacion'
)

foreach ($recurso in $recursos) {
    $respuesta = pedir GET "/api/$recurso"
    if ($respuesta.HTTP -eq 200) {
        $datos = $respuesta.JSON | ConvertFrom-Json
        "$recurso : $($datos.total) activos"
    }
    elseif ($respuesta.HTTP -eq 204) {
        "$recurso : 0 activos (HTTP 204, sin cuerpo)"
    }
    else {
        "$recurso : HTTP $($respuesta.HTTP) $($respuesta.JSON)"
    }
}
```

Los conteos del API se refieren a **registros activos**; no equivalen necesariamente al total físico de filas después de ejecutar pruebas de borrado lógico. Los catálogos de 218, 17 y 21 registros se observaron en los listados del entorno probado. Los seis registros iniciales de `universidad` siguen pendientes de localizar en la fuente oficial, incorporarse al `db/init.sql` y verificarse **en una instalación limpia**. No invente registros para cumplir esa cifra.

## 4. Ciclo completo HTTP: un área de aplicación temporal

**Advertencia:** este ejemplo crea y retira una fila real. Use únicamente una **llave nueva de prueba** en una base de desarrollo. Aquí se propone `992101`; si ya la utilizó o fue retirada anteriormente, cámbiela antes de ejecutar el POST. Un GET `404` **no garantiza** que esté libre: una clave retirada sigue ocupada.

```powershell
$ruta = '/api/area_aplicacion'
$id = 992101
$ficha = "$ruta/$id"

# Si existe, no la modifique: elija otro identificador.
pedir GET $ficha | Format-List                 # 404 si no está activa

pedir POST $ruta @{                         # 201
    id = $id
    nombre = 'Area temporal quickstart'
} | Format-List

pedir GET $ficha | Format-List               # 200

pedir PUT $ficha @{                          # 200: ficha completa
    nombre = 'Area modificada con PUT'
} | Format-List

pedir GET $ficha | Format-List               # Debe conservar el nuevo nombre

pedir PATCH $ficha @{                        # 200: solo campos enviados
    nombre = 'Area modificada con PATCH'
} | Format-List

pedir GET $ficha | Format-List               # Debe mostrar el cambio de PATCH

pedir PATCH $ficha @{} | Format-List          # 400: no hay cambios

pedir DELETE $ficha | Format-List             # 200: retiro lógico
pedir GET $ficha | Format-List                # 404: no está activa
pedir DELETE $ficha | Format-List             # 404: segundo retiro
```

**Diferencia PUT/PATCH:** PUT envía la ficha completa de los campos editables. PATCH permite enviar solo los campos que cambiaron. La llave primaria se identifica mediante la URL, no se envía en el cuerpo de PUT/PATCH.

### Verificar que DELETE es lógico

Desde `http://localhost:8105/`, seleccione la base del proyecto y ejecute en phpMyAdmin la siguiente consulta para **el ID temporal que realmente utilizó**:

```sql
SELECT id, nombre, activo
FROM area_aplicacion
WHERE id = 992101;
```

El registro retirado debería continuar en la tabla con `activo = 0`; el GET de la API, en cambio, debe devolver `404`. No use `DELETE FROM` ni borre los datos de referencia.

## 5. Probar los otros cinco CRUD con registros nuevos

Se conservaron los mismos seis endpoints de `6_contracts.md` para cada recurso: `GET` listado, `GET` individual, `POST`, `PUT`, `PATCH` y `DELETE`. Los siguientes cuerpos sirven para repetir manualmente el ciclo anterior. Los IDs son **ejemplos nuevos**, no semillas ni claves reservadas: compruebe que no están ocupados ni retirados y cámbielos si ya ejecutó esta guía.

| Recurso | Llave temporal | Campos de POST |
|---|---|---|
| `area_conocimiento` | `ZZQ701` | `id`, `gran_area`, `area`, `disciplina` |
| `objetivo_desarrollo_sostenible` | `992102` | `id`, `nombre`, `categoria` |
| `termino_clave` | `qs_prueba_261004` | `termino`, `termino_ingles` (opcional) |
| `universidad` | `992103` | `id`, `nombre`, `tipo`, `ciudad` |
| `linea_investigacion` | **La API devuelve el ID** | `nombre`, `descripcion` (sin `id`) |

Puede ejecutar esta batería en PowerShell. **Hace cambios reales y después retira únicamente los cinco registros de prueba que logre crear**; no la ejecute sobre producción:

```powershell
$casos = @(
    @{
        Recurso = 'area_conocimiento'; Clave = 'ZZQ701'
        Crear = @{ id='ZZQ701'; gran_area='Prueba'; area='Area temporal'; disciplina='Disciplina temporal' }
        Completo = @{ gran_area='Prueba editada'; area='Area PUT'; disciplina='Disciplina PUT' }
        Parcial = @{ disciplina='Disciplina PATCH' }
    },
    @{
        Recurso = 'objetivo_desarrollo_sostenible'; Clave = '992102'
        Crear = @{ id=992102; nombre='ODS temporal'; categoria='Prueba' }
        Completo = @{ nombre='ODS editado PUT'; categoria='Prueba modificada' }
        Parcial = @{ categoria='Prueba PATCH' }
    },
    @{
        Recurso = 'termino_clave'; Clave = 'qs_prueba_261004'
        Crear = @{ termino='qs_prueba_261004'; termino_ingles='quickstart test' }
        Completo = @{ termino_ingles='updated test' }
        Parcial = @{ termino_ingles='partial test' }
    },
    @{
        Recurso = 'universidad'; Clave = '992103'
        Crear = @{ id=992103; nombre='Universidad temporal'; tipo='Prueba'; ciudad='Medellin' }
        Completo = @{ nombre='Universidad PUT'; tipo='Prueba'; ciudad='Bogota' }
        Parcial = @{ ciudad='Medellin' }
    },
    @{
        Recurso = 'linea_investigacion'; Clave = $null
        Crear = @{ nombre='Linea temporal QS'; descripcion='Creada para probar la API' }
        Completo = @{ nombre='Linea temporal PUT'; descripcion='Descripcion reemplazada' }
        Parcial = @{ descripcion='Descripcion PATCH' }
    }
)

foreach ($caso in $casos) {
    $ruta = "/api/$($caso.Recurso)"
    $clave = $caso.Clave

    if ($null -ne $clave) {
        $ficha = "$ruta/$([uri]::EscapeDataString([string]$clave))"
        $previo = pedir GET $ficha
        if ($previo.HTTP -ne 404) {
            Write-Warning "$ruta : el ID de prueba está activo o hubo otro error; se omite."
            continue
        }
        # Si una clave ya fue retirada, POST devolverá 409: elija otra.
    }

    $creacion = pedir POST $ruta $caso.Crear
    if ($creacion.HTTP -ne 201) {
        Write-Warning "$ruta : POST devolvió $($creacion.HTTP). $($creacion.JSON)"
        continue
    }

    if ($null -eq $clave) {
        $clave = ($creacion.JSON | ConvertFrom-Json).datos.id
    }
    $ficha = "$ruta/$([uri]::EscapeDataString([string]$clave))"

    "$ruta  GET inicial: $((pedir GET $ficha).HTTP) (esperado 200)"
    "$ruta  PUT:         $((pedir PUT $ficha $caso.Completo).HTTP) (esperado 200)"
    "$ruta  PATCH:       $((pedir PATCH $ficha $caso.Parcial).HTTP) (esperado 200)"
    "$ruta  GET final:    $((pedir GET $ficha).HTTP) (esperado 200)"
    "$ruta  DELETE:       $((pedir DELETE $ficha).HTTP) (esperado 200)"
    "$ruta  GET retirado: $((pedir GET $ficha).HTTP) (esperado 404)"
    "$ruta  2do DELETE:   $((pedir DELETE $ficha).HTTP) (esperado 404)"
}
```

Compruebe también el contenido del GET posterior a PUT/PATCH, no solo el código HTTP. Si una modificación falla, **detenga esa prueba** y revise el registro temporal antes de intentar retirarlo. Esta batería se incluye para futuras repeticiones; las pruebas HTTP de los tres recursos restantes y los CRUD de navegador de los seis recursos sí se realizaron durante la integración, pero **no se ha afirmado que esta batería completa haya sido ejecutada tal cual**.

## 6. Validaciones y conflictos

Un POST válido devuelve `201`; un GET de ficha activa `200`; PUT y PATCH válidos `200`; un registro inexistente o retirado `404`; llave duplicada `409`; campos inválidos `422`; PATCH `{}` devuelve `400`. Una ruta existente con un método no permitido debe devolver `405`.

Los siguientes ejemplos pueden ejecutarse después de definir `pedir`. El registro de prueba `992101` del apartado 4 ya estaría retirado:

```powershell
# 409: una llave retirada sigue ocupada (incluya todos los campos).
pedir POST '/api/area_aplicacion' @{
    id = 992101
    nombre = 'Intento de reutilizar ID retirado'
} | Format-List

# 422: falta el nombre; el ID propuesto no se crea.
pedir POST '/api/area_aplicacion' @{
    id = 992104
} | Format-List

# 400: sin campos para modificar. La API valida PATCH vacío.
pedir PATCH '/api/area_aplicacion/992101' @{} | Format-List

# 405: el listado no permite DELETE sin especificar la llave.
pedir DELETE '/api/area_aplicacion' | Format-List
```

**Resultados ya comprobados durante el desarrollo:** en `area_conocimiento`, `objetivo_desarrollo_sostenible` y `area_aplicacion`, las pruebas HTTP reales devolvieron `409` para claves retiradas, `422` para campos obligatorios ausentes y `400` ante PATCH vacío. Los formularios de las seis entidades también se probaron con códigos duplicados. La comprobación integral de todos los casos negativos de los seis recursos contra `6_contracts.md` sigue siendo tarea de cierre.

## 7. Pruebas de capas sin MariaDB

Estas pruebas utilizan repositorios falsos; permiten detectar fallos de reglas sin una base de datos disponible. Desde el repositorio **backend**:

```powershell
# Comprobación de sintaxis de los archivos integrados.
docker compose exec api-investigacion php -l controladores/ControladorAreaConocimiento.php
docker compose exec api-investigacion php -l controladores/ControladorObjetivoDesarrolloSostenible.php
docker compose exec api-investigacion php -l controladores/ControladorAreaAplicacion.php

docker compose stop mariadb

try {
    docker compose exec api-investigacion php pruebas/prueba_capas_restantes.php
    docker compose exec api-investigacion php pruebas/prueba_controladores_restantes.php
    docker compose exec api-investigacion php pruebas/prueba_capas.php
}
finally {
    docker compose start mariadb
}

docker compose ps
```

**Evidencia obtenida:** 30 casos de servicios para las tres entidades restantes, 51 comprobaciones simuladas de sus controladores y todas las pruebas de término clave, universidad y línea de investigación terminaron `[OK]` con MariaDB apagada. Reiniciar la base después es obligatorio. Si alguna prueba falla, consulte el mensaje y no dé la fase por aprobada.

## 8. La pantalla: pruebas manuales de los seis recursos

El frontend está en `http://localhost:8110/`. Las rutas actuales son:

| Pantalla | URL relativa |
|---|---|
| Áreas de conocimiento | `/areas-conocimiento` |
| ODS | `/objetivos-desarrollo-sostenible` |
| Áreas de aplicación | `/areas-aplicacion` |
| Términos clave | `/terminos-clave` |
| Universidades | `/universidades` |
| Líneas de investigación | `/lineas-investigacion` |

Para cada recurso, compruebe el listado, el acceso al formulario de registro, la creación de un ID temporal, las dos opciones de edición (**Guardar ficha completa** y **Guardar solo los cambios**) y **Retirar**. Intente también registrar una clave duplicada y compruebe que aparece un mensaje comprensible sin sobrescribir el registro original. Si el listado queda vacío, la respuesta `204` debe mostrarse como un estado válido que permita registrar el primer elemento.

La navegación integrada utiliza `front_php/index.php` como punto de entrada y delega tres recursos a `front_php/rutas_restantes.php`. Sus pantallas se encuentran en `front_php/vistas/crud_restantes.php`; las otras tres conservan `lista.php` y `formulario.php`. **Solo `cliente_api.php`** se comunica con la API mediante HTTP; el frontend no utiliza PDO ni credenciales de MariaDB.

Para revisar sintaxis desde el repositorio backend:

```powershell
docker compose exec front-php php -l index.php
docker compose exec front-php php -l rutas_restantes.php
docker compose exec front-php php -l vistas/crud_restantes.php
docker compose exec front-php php -l vistas/inicio.php
docker compose exec front-php php -l vistas/plantilla.php
```

### Prueba pendiente: frontend con la API apagada

Realícela **solo después de terminar cualquier prueba HTTP en curso**. Detenga únicamente `api-investigacion` y deje MariaDB y el frontend encendidos:

```powershell
docker compose stop api-investigacion

try {
    # El panel debe seguir respondiendo; los listados mostrarán un aviso.
    Invoke-WebRequest -UseBasicParsing http://localhost:8110/ |
        Select-Object StatusCode

    # Compruébelo también manualmente desde el navegador, en las seis rutas.
}
finally {
    docker compose start api-investigacion
}

docker compose ps
```

Compruebe que el frontend no muestre registros obtenidos directamente de MariaDB cuando la API está apagada. **Esta prueba todavía no se ha confirmado para la versión final integrada.**

### Guion automático del frontend

El esquema anterior de este quickstart menciona `pruebas_humo/humo_front.py`, pero no se ha aportado ni verificado ese archivo en los repositorios revisados. **No se incluye como un comando ejecutable de esta guía.** Si se desarrolla y comprueba posteriormente, documente aquí su ubicación, requisitos y forma de ejecución. Mientras tanto, utilice el recorrido manual de los seis recursos.

## 9. Si algo sale mal

| Problema | Causa probable | Qué comprobar |
|---|---|---|
| `no configuration file provided` | La terminal está fuera del repositorio backend | `pwd` y ubicación de `docker-compose.yml` |
| MariaDB no aparece saludable | Inicio o configuración pendiente | `docker compose ps` y `docker compose logs mariadb` |
| API devuelve `500` | Error de configuración, conexión o excepción | `docker compose logs api-investigacion` (no publique secretos) |
| El frontend no conecta con la API | `URL_API` incorrecta en Docker | Variable del servicio frontend y nombre DNS interno de la API; `localhost` del contenedor no es el host |
| Bootstrap devuelve HTML en vez de CSS | El enrutador intercepta archivos estáticos | `curl.exe -I .../publico/bootstrap.min.css` debe mostrar `text/css` |
| GET devuelve `404` y POST `409` | La llave está retirada, pero sigue siendo única | Use una llave nueva de prueba; no reactive ni borre registros de referencia |
| PATCH `{}` devuelve `400` | No hay campos para actualizar | Es el comportamiento esperado |
| Tras copiar el frontend sigue apareciendo la versión anterior | Montaje de volúmenes o caché del navegador | Verifique la ruta local montada por Compose y recargue con `Ctrl + F5` |
| El frontend funciona, pero faltan las seis semillas de `universidad` | `init.sql` pendiente de completar con la fuente oficial | No invente los seis registros; compruebe el script y una base nueva |

## 10. Antes de cerrar y etiquetar la v1

- [x] Los seis CRUD se probaron en el navegador con registros temporales.
- [x] Los tres recursos integrados de áreas/ODS superaron las pruebas HTTP reales de CRUD, duplicados, campos obligatorios y PATCH vacío.
- [x] Las pruebas de capas de los seis recursos se ejecutaron sin MariaDB; se ejecutaron además 51 comprobaciones simuladas de controladores.
- [x] Ejecutar esta guía completa **tal como está escrita** en el entorno del equipo y, después, desde una instalación nueva.
- [x] Incorporar y verificar los **seis registros oficiales iniciales de `universidad`** en `db/init.sql`.
- [x] Confirmar `204` en un recurso realmente vacío y la correspondiente pantalla de estado vacío.
- [x] Comprobar el frontend final con la API apagada.
- [x] Verificar los casos restantes de `2_spec.md` y `6_contracts.md`, completar la colección de Postman y revisar secretos y documentación.
- [ ] Marcar y firmar `9_checklist.md` antes de crear el tag `v1`.

**Referencias relacionadas:** `2_spec.md` (requisitos), `3_plan.md` (estructura, incluidos los archivos nuevos del frontend), `4_research.md` (decisión D-v1-11), `5_data_model.md` (tablas), `6_contracts.md` (rutas HTTP), `8_tasks.md` (tareas) y `9_checklist.md` (verificación final).
