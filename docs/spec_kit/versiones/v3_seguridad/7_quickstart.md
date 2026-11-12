# Quickstart — v3: autenticación y roles

> **Estado al 12-11-2026:** bootstrap manual local y roles en MariaDB comprobados; login JWT con **15/15** pruebas automatizadas y HTTP 200 real; middleware con **28/28** pruebas automatizadas y controles HTTP reales **401 sin token / 200 con Bearer** para `area_conocimiento`. La idempotencia del bootstrap, instalación limpia, auditoría de secretos, usuario real `consulta` por HTTP, CRUD administrativos, frontend y regresión total **siguen pendientes**. Detalles en los registros de fase 1, 2 y 3.

## 0. Requisitos y configuración real observada

- Repositorio backend en rama `feature/angel-investigacion-v3`, con `db/init.sql` modificado y 4 archivos PHP de inicialización incorporados.
- `docker-compose.yml` recibido: `mariadb` (imagen `mariadb:11`), `api-investigacion` (puerto 8111), `front-php` (8110), phpMyAdmin (8105), volumen `mariadbdata`; MariaDB expuesta en puerto 13330. Los nombres de contenedor pueden variar con el nombre del proyecto Docker.
- El `ensamblador.php` define `crearConexion()` con `DB_DSN`, `DB_USUARIO`, `DB_CLAVE`; la ampliación de fase 1 incorpora `crearServicioInicializacionSeguridad()`.
- `.env` de la infraestructura local debe contener la configuración existente de MariaDB y `FRONT_PATH`, pero **nunca se sube a Git**. `JWT_SECRET` se añadió y PHP confirmó su presencia en el contenedor mediante longitud `64`; **no** se ha auditado todavía su exclusión de Git.
- Antes del cierre del proyecto revisar compuertas SDD, estado de v2 y código real del frontend. Conservar el volumen: **no ejecutar `docker compose down -v`**.

## 0.1. Preparación de roles y primer administrador — CLI probado localmente

### Paso A. Levantar la base y los servicios

```powershell
docker compose up -d --build
docker compose ps
```

En un volumen vacío, `db/init.sql` con el bloque v3 siembra `admin` y `consulta` (esto sigue **pendiente de prueba independiente** en instalación limpia). En un volumen ya creado, el script de SQL inicial **no** se reejecuta; el repositorio del bootstrap asegura los dos roles sin destruir datos.

### Paso B. Revisar la sintaxis de los cuatro archivos

```powershell
docker compose exec api-investigacion php -l /app/scripts/inicializar_admin.php
docker compose exec api-investigacion php -l /app/repositorios/Repositorios_InicializacionSeguridad/RepositorioInicializacionSeguridadMariaDB.php
docker compose exec api-investigacion php -l /app/repositorios/Repositorios_InicializacionSeguridad/IRepositorioInicializacionSeguridad.php
docker compose exec api-investigacion php -l /app/servicios/Servicios_InicializacionSeguridad/ServicioInicializacionSeguridad.php
```

El estudiante obtuvo `No syntax errors detected` en los cuatro. La comprobación aislada de `function_exists('crearServicioInicializacionSeguridad')` **no imprimió** el `OK` esperado en su terminal; sin embargo, el script invocó con éxito el ensamblador. Registrar nueva prueba explícita si se modifica el ensamblador.

### Paso C. Crear el administrador sin registrar contraseñas

Abrir **PowerShell** en la raíz del repositorio backend. No pegar datos reales en el chat, archivos Markdown, Git ni capturas.

```powershell
$env:ADMIN_INICIAL_USERNAME = Read-Host 'Usuario del administrador inicial'
$env:ADMIN_INICIAL_EMAIL = Read-Host 'Correo del administrador inicial'
$claveSegura = Read-Host 'Contraseña inicial (mínimo 12 caracteres)' -AsSecureString
$env:ADMIN_INICIAL_PASSWORD = [System.Net.NetworkCredential]::new('', $claveSegura).Password

try {
    docker compose run --rm --no-deps -e ADMIN_INICIAL_USERNAME -e ADMIN_INICIAL_EMAIL -e ADMIN_INICIAL_PASSWORD api-investigacion php /app/scripts/inicializar_admin.php
} finally {
    Remove-Item Env:ADMIN_INICIAL_USERNAME -ErrorAction SilentlyContinue
    Remove-Item Env:ADMIN_INICIAL_EMAIL -ErrorAction SilentlyContinue
    Remove-Item Env:ADMIN_INICIAL_PASSWORD -ErrorAction SilentlyContinue
    Remove-Variable claveSegura -ErrorAction SilentlyContinue
}
```

**Resultado observado (primera ejecución en el equipo del estudiante):** `Administrador inicial creado y rol asignado correctamente.` El código exige usuario entre 3 y 100 caracteres, correo válido hasta 150 y contraseña de al menos 12 caracteres. Se usa `password_hash(PASSWORD_DEFAULT)` y una transacción para registrar cuenta y vínculo. Esta regla de 12 caracteres queda adoptada como política del primer administrador de v3 y debe reflejarse también en la gestión posterior de contraseñas.

El comando **no es automático al hacer `docker compose up -d --build`**: en otro computador, una vez inicializada MariaDB, el profesor debe ejecutarlo una sola vez con credenciales elegidas por él. La BD/volumen del estudiante **no** se incluye al clonar GitHub. No hay endpoint web para crear el primer administrador.

### Paso D. Comprobar en phpMyAdmin sin exponer el hash

Abrir `http://localhost:8105` y seleccionar la base configurada en `MARIADB_DATABASE`.

```sql
SELECT id, nombre, activo FROM rol WHERE nombre IN ('admin', 'consulta');

SELECT u.username, u.email, u.activo, r.nombre AS rol
FROM usuario AS u
JOIN rol_usuario AS ru ON ru.usuario_id = u.id
JOIN rol AS r ON r.id = ru.rol_id
WHERE r.nombre = 'admin';
```

**Resultado observado:** dos roles activos `admin` y `consulta` y una cuenta administradora activa vinculada a `admin`. No copiar `usuario.password` ni datos reales en las evidencias públicas. La consulta por sí sola no demuestra la validez del hash; posteriormente un login HTTP real del administrador tuvo éxito mediante `password_verify()` (fase 2).

### Paso E. Pruebas adicionales pendientes de ejecutar

1. Volver a ejecutar el script usando la cuenta existente y confirmar mensaje de administrador previo; comparar conteos antes/después, sin imprimir hashes.
2. En otra instalación limpia y aislada, verificar los INSERT de roles de `init.sql` sin borrar el volumen del proyecto en uso.
3. Intentar inicializar sin variables, con un usuario duplicado y frente a un administrador inactivo; comprobar fallo seguro sin promociones ni alteraciones.
4. Confirmar que `.env` no está en Git y que no existe ruta pública de bootstrap. La automatización de inicialización al arrancar con Docker sigue pendiente de diseño y prueba.

Referencia de evidencia: [REGISTRO_FASE1_ADMIN.md](REGISTRO_FASE1_ADMIN.md).

## 1. Levantar entorno y ubicar URLs

Desde el directorio donde se encuentre el `docker-compose.yml` **real**:

```powershell
docker compose up -d --build
docker compose ps
```

Según `7_quickstart.md` V2, el entorno probado usó **frontend `http://localhost:8110`, API `http://localhost:8111`, phpMyAdmin `http://localhost:8105` y MariaDB `localhost:13330`**; los servicios reportados fueron `front-php` y `api-investigacion`. Verificar esos datos en el `docker-compose.yml` real con `docker compose config --services` y `docker compose ps` antes de usarlos. Registrar variaciones:

```text
URL_API_REAL:       __________________________
URL_FRONT_REAL:     __________________________
SERVICIO_API:       __________________________
SERVICIO_FRONT:     __________________________
SERVICIO_MARIADB:   __________________________
```

## 1.1. Comprobaciones reales realizadas de las fases 2 y 3

**A. Sintaxis y pruebas locales del backend (comunicadas por el estudiante):**

```powershell
docker compose up -d --build
docker compose exec api-investigacion php -l /app/index.php
docker compose exec api-investigacion php -l /app/servicios/ensamblador.php
docker compose exec api-investigacion php /app/pruebas/prueba_login_jwt.php
docker compose exec api-investigacion php /app/pruebas/prueba_middleware_autorizacion.php
```

**Resultados observados:** sin errores de sintaxis; **15/15** pruebas del login; **28/28** del middleware. En Docker, MariaDB se informó saludable. Además, la longitud de `JWT_SECRET` visible a PHP fue **64**, sin imprimir el secreto:

```powershell
docker compose exec api-investigacion php -r "echo strlen((string)getenv('JWT_SECRET')), PHP_EOL;"
```

**B. Solicitud HTTP sin token (ejecutada):**

```powershell
curl.exe -i --max-time 10 http://localhost:8111/api/area_conocimiento
```

**Resultado real:** `HTTP/1.1 401 Unauthorized`, encabezado `WWW-Authenticate: Bearer realm="investigacion"`, cuerpo JSON `estado:401`.

**C. Login HTTP y GET con Bearer (ejecutados, sin imprimir el token):**

```powershell
$usuario = Read-Host 'Nombre de usuario'
$claveSegura = Read-Host 'Contraseña' -AsSecureString
$puntero = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($claveSegura)
try {
    $claveTemporal = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($puntero)
    $json = @{ username = $usuario; password = $claveTemporal } | ConvertTo-Json -Compress
    $login = Invoke-RestMethod -Uri 'http://localhost:8111/api/login' -Method POST -ContentType 'application/json' -Body $json -ErrorAction Stop
    $token = $login.datos.token
    $respuesta = Invoke-WebRequest -Uri 'http://localhost:8111/api/area_conocimiento' -Method GET -Headers @{ Authorization = "Bearer $token" } -UseBasicParsing -ErrorAction Stop
    Write-Output "HTTP con JWT: $([int]$respuesta.StatusCode)"
    Write-Output "Expira en: $($login.datos.expira_en) segundos"
    Write-Output "Rol(es): $($login.datos.usuario.roles -join ', ')"
} finally {
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($puntero)
    Remove-Variable claveTemporal, claveSegura, json, login, token, respuesta -ErrorAction SilentlyContinue
}
```

**Resultado real comunicado:** `ADMIN`, `admin`, `3600` segundos; GET con cabecera Bearer → **HTTP 200**. **No** confundir este GET autenticado con uno al que se omitió la cabecera: esa omisión no prueba autenticación. Hubo además una ejecución con Docker apagado que dio `0` en PowerShell; ese `0` no es una respuesta HTTP de la API.

**D. Pendientes:** prueba HTTP de `consulta` → 200/403, expiración/alteración por HTTP, regresión de 16 recursos, CRUD administrativo, sesiones frontend y auditoría Git. Consultar [REGISTRO_FASE2_LOGIN.md](REGISTRO_FASE2_LOGIN.md) y [REGISTRO_FASE3_MIDDLEWARE.md](REGISTRO_FASE3_MIDDLEWARE.md).

## 2. Comprobar credenciales sin publicarlas

En PowerShell, reemplazar la URL y cargar los datos de prueba con los valores que tenga el equipo. No incorporar contraseñas reales al documento ni capturas:

```powershell
$API = "http://localhost:8111"  # puerto documentado en V2; verificar con docker compose ps
$usuarioConsulta = Read-Host "Usuario de consulta"
$claveConsulta = Read-Host "Clave de consulta" -AsSecureString

# Para un comando de prueba Invoke-RestMethod hay que convertir temporalmente
# la clave a texto dentro del proceso actual; NO imprimirla ni registrarla.
$bstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($claveConsulta)
try {
    $claveTemporal = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($bstr)
    $cuerpo = @{ username = $usuarioConsulta; password = $claveTemporal } | ConvertTo-Json
    $respuesta = Invoke-RestMethod -Uri "$API/api/login" -Method POST -ContentType "application/json" -Body $cuerpo
} finally {
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr)
    Remove-Variable claveTemporal -ErrorAction SilentlyContinue
}

$tokenConsulta = $respuesta.datos.token
if (-not $tokenConsulta) { throw "No llegó token en la ruta de respuesta acordada." }
```

**Contrato utilizado y probado:** `username`, `password` y `datos.token` corresponden a la implementación actual del login. Nunca mostrar `$tokenConsulta` en capturas ni videos. No mostrar `$tokenConsulta` en videos o capturas.

## 3. Autenticación y 401/403

```powershell
# Sin token debe devolver 401 para una ruta protegida:
curl.exe -i "$API/api/universidad"

# Token alterado o inválido → 401:
curl.exe -i -H "Authorization: Bearer token.invalido.falso" "$API/api/universidad"

# Con consulta: GET permitido conforme a matriz aprobada:
Invoke-RestMethod -Uri "$API/api/universidad" -Headers @{ Authorization = "Bearer $tokenConsulta" }

# Con consulta: POST prohibido → 403 SIN INSERT:
$cuerpoBloqueado = '{"id":999999,"nombre":"No debe insertarse","tipo":"Prueba","ciudad":"Prueba"}'
curl.exe -i -X POST -H "Authorization: Bearer $tokenConsulta" -H "Content-Type: application/json" --data "$cuerpoBloqueado" "$API/api/universidad"
```

**Cuidado con las comillas de `curl.exe` en PowerShell:** el comando de POST anterior es ilustrativo; si el escape JSON falla, utilizar `Invoke-WebRequest` o enviar el cuerpo desde archivo de prueba temporal. Lo imprescindible es comprobar código `403` y ausencia de cambios en MariaDB.

## 4. Administración (cuenta `admin`)

Con un token administrativo obtenido con el mismo flujo del §2, validar los siguientes escenarios en Postman o HTTP:

| Prueba | Esperado |
|---|---|
| `GET /api/usuario` | 200 o 204, nunca retorna hashes |
| `POST /api/usuario` con datos válidos | 201; `password` almacenado como hash |
| Crear usuario con `username` duplicado, el esquema exige UNIQUE | 409 |
| `GET /api/rol` | 200 o 204 |
| `POST /api/rol` válido | 201 |
| Crear `rol_usuario` con ambas FK activas | Éxito contractual aprobado |
| Crear asignación de rol repetida | 409 si UNIQUE/PK lo prohíbe |
| Crear asignación con FK inexistente | Error coherente con contrato (propuesta 409) |
| Retirar usuario / rol | Borrado lógico y exclusión de SELECT |
| Dar de baja relación puente | Según C8 aprobada, DELETE físico **solo del vínculo** (pendiente implementación y prueba) |

## 5. Frontend: sesión y logout

1. Entrar desde la pantalla de login con usuario `consulta`.
2. Confirmar que aparece el menú de lectura y no las acciones de escritura o gestión administrativa.
3. Intentar una ruta protegida manualmente: la API debe seguir denegando con 403; una ruta web oculta no basta.
4. Salir desde la interfaz. Comprobar que no quedan datos de autenticación en la sesión y que al volver a una pantalla restringida se pide login.
5. Entrar con `admin`; comprobar menú y pantallas de las tres entidades administrativas.
6. Apagar **solo la API**; el frontend sigue respondiendo con aviso y sin datos.

## 6. Regresión v1 y v2

**Precaución:** conservar recursos de prueba y evidencias sin sobrescribir registros reales. En v3, repetir las pruebas autorizadas con `admin` y las denegadas con `consulta`.

- Ejecutar los casos existentes de `7_quickstart.md` / colección Postman de v1 y v2, agregando un token con permisos de cada operación.
- Comprobar GET 200/204, POST 201, PUT 200, PATCH parcial 200, inválido 422, inexistente 404, duplicado/FK 409, segundo retiro 404 **según método admitido**. En V2 documentada, PATCH `{}` es `422` en controladores revisados; en V1 existen contratos con `400` para PATCH vacío. Para `grupo_linea`, `semillero_linea`, `ac_linea`, `ods_linea`, `aa_linea`, PUT/PATCH de usuario autorizado deben seguir devolviendo `405`.
- Verificar `activo=TRUE` en SELECT y desplegables v2; la v3 no puede reactivar inadvertidamente registros retirados.
- Ensayar v1/v2 con `consulta` para garantizar que **cada escritura** devuelve 403.

## 7. Pruebas de capas y secretos

Una vez implementados los archivos de prueba definidos en `3_plan.md`, ejecutar el comando **real** de su contenedor o PHP local; no presuponer que existen todavía:

```text
php api_investigacion/pruebas/prueba_seguridad.php
php api_investigacion/pruebas/prueba_capas_seguridad.php
```

Pruebas mínimas: `password_verify` correcto/incorrecto; token expirado; token manipulado; algoritmo incorrecto; `consulta` sin escritura; `admin` con acceso; roles inactivos; respuestas sin `password`; funcionamiento con repositorios falsos sin MariaDB.

Comprobar configuración y versionado, sin imprimir valores de secretos:

```powershell
git status --short
git ls-files .env
git ls-files .env.example
```

`git ls-files .env` no debe listar el archivo. Revisar además que no existan contraseñas o JWT secretos incrustados en otros archivos versionados.

## 8. Registro de resultados de cierre

| Comprobación | Resultado real | Evidencia |
|---|---|---|
| Primer bootstrap CLI en volumen local existente | **Comprobado parcialmente**: roles y admin registrados | Salida CLI y capturas phpMyAdmin facilitadas por estudiante |
| Idempotencia, instalación limpia y arranque automático Docker | **Pendiente** | No se ejecutaron estas pruebas |
| Login JWT / credenciales correctas | **Comprobado:** login HTTP `ADMIN` → 200 y 3600 s; suite 15/15 | `REGISTRO_FASE2_LOGIN.md` |
| HTTP 401 sin token y 200 con Bearer | **Comprobado** en `/api/area_conocimiento` | `REGISTRO_FASE3_MIDDLEWARE.md` |
| HTTP 403 de `consulta` real | **Pendiente**; solo suite automatizada de autorización | `REGISTRO_FASE3_MIDDLEWARE.md` |
| CRUD usuario/rol/rol_usuario | Pendiente | — |
| Verificación de contraseña con `password_verify()` | **Comprobado en login real y suite de login**; auditoría de almacenamiento/hash de todos los usuarios pendiente | `REGISTRO_FASE2_LOGIN.md` |
| Frontend + logout | Pendiente | — |
| Regresión v1/v2 | Pendiente | — |
| API apagada / frontend activo | Pendiente | — |
| Ausencia de secretos versionados | Pendiente | — |

> Ya hay evidencia reportada de login JWT y guarda HTTP, limitada a los casos descritos. No concluir que todos los 16 CRUD estén probados, que haya 403 HTTP de `consulta` real, ni que la v3 esté cerrada.

## 9. Pruebas negativas con evidencia HTTP y estado de base (pendientes de ejecutar)

Las pruebas no deben imprimir tokens, contraseñas ni columnas de hash. En PowerShell, obtener el **código** y parte no sensible de la respuesta sin asumir que cualquier 4xx siempre es una excepción:

```powershell
# En PowerShell 5.1, Invoke-WebRequest normalmente lanza excepción ante 4xx.
try {
    $r = Invoke-WebRequest -Uri "$API/api/usuario" -Method GET -UseBasicParsing
    Write-Output "Estado: $($r.StatusCode)"
} catch {
    if ($_.Exception.Response) {
        Write-Output "Estado: $([int]$_.Exception.Response.StatusCode)"
    } else { throw }
}
# Resultado esperado: 401 si /api/usuario está protegido y no hay Bearer.
```

**Registrar por caso:** actor, método/ruta, código real, antes/después de la fila en BD, evidencia sin credenciales, y referencia al RF/criterio del §6 de `2_spec.md`. El test de `403` necesita un usuario de `consulta` creado por admin con rol vigente; *no* inventar credenciales en un script versionado. Para probar el segundo arranque, detener y volver a levantar sin borrar volúmenes; comprobar que no cambia número de administradores ni `usuario.password` (comparación de hashes dentro de prueba segura, sin imprimirlos). Para roles revocados y usuarios inactivos, ejecutar el escenario decidido en C6, no asumir que el JWT solo es suficiente.

## 10. Evidencia de instalación y revisión humana

| Caso | Estado documental actual | Comprobante a aportar |
|---|---|---|
| Base nueva con roles semilla y bootstrap | Pendiente en instalación limpia | Logs sanitizados, SELECT de roles, login válido |
| Base persistente con roles y admin inicial | **Primera ejecución realizada**; falta comparar conteos en repetición | Salida CLI, SELECT de roles y asociación sin hash |
| Repetir bootstrap | Diseñado; no probado | Sin usuario duplicado ni cambio de contraseña |
| Credenciales iniciales faltantes | Diseñado; no probado | Proceso falla cerrado sin valor por defecto |
| Token alterado/expirado/algoritmo inesperado | **Suite de login 15/15 y middleware 28/28**; faltan solicitudes HTTP específicas | 401 y ningún efecto lateral |
| Permisos admin/consulta en 16 recursos | **Lógica probada automatizadamente** y un recurso con admin por HTTP; matriz HTTP completa pendiente | Matriz de métodos, 200/204 o 403 según caso |
| CRUD de las tres tablas de seguridad | Diseñado; no probado | 201, 404, 409, 422 y cambio del estado real |
| Logout y API apagada | Diseñado; no probado | Captura sin datos ni token expuesto |

> **Importante:** los puertos y servicios de la sección 0 se cotejaron contra el `docker-compose.yml` compartido. El documento incorpora evidencias locales reportadas de las fases 1, 2 y 3; el ZIP contiene únicamente Markdown y **no ejecuta pruebas nuevas**. El ejemplo `v3_segundo_motor` del profesor no implica usar PostgreSQL en el proyecto de investigación.
