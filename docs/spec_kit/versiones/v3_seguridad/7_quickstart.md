# Quickstart — v3: autenticación y roles

> **Estado:** GUÍA V3 PENDIENTE DE EJECUCIÓN. Se cuenta ahora con el Spec Kit de V2 (29-10-2026), que documenta puertos, servicios y rutas; **no** se recibió el `docker-compose.yml` ni el código de V2. No atribuir a esta guía pruebas nuevas ni afirmar que las variables o puertos son idénticos en la versión integrada.

## 0. Precondiciones

1. Verificar si existe tag `v2` y cierre formal; se confirmó merge del backend por PR #15 en `9bcf84b`, pero el Spec Kit V2 del 29-10 aún marcaba aceptación/regresión pendiente.
2. Los servicios Docker reales pueden levantarse desde la estructura de repositorios del equipo.
3. `JWT_SECRET` está definido como variable de entorno sin publicarse en Git.
4. La creación de los roles `admin`/`consulta` y del primer administrador se documentó como **decisión de diseño**, no se ha implementado todavía: revisar el procedimiento previsto a continuación. La cuenta `consulta` se crea después a través del CRUD administrativo, no mediante credenciales fijas en SQL. **No** crear usuarios con contraseñas de ejemplo versionadas.
5. El equipo ha completado C1–C12 de `2_spec.md` y ha reemplazado los ejemplos de contrato por los definitivos.

## 0.1. Preparación de roles y administrador inicial (PROCEDIMIENTO PREVISTO, NO EJECUTABLE TODAVÍA)

1. Preparar un `.env` local **ignorado por Git** con `ADMIN_INICIAL_USERNAME`, `ADMIN_INICIAL_EMAIL` y `ADMIN_INICIAL_PASSWORD` para el primer arranque, además de `JWT_SECRET` y las credenciales ya utilizadas por la BD. Mantener `.env.example` solo con nombres/valores ficticios; no copiar claves reales a documentación o capturas.
2. En una instalación con volumen vacío, el `init.sql` de v3 sembrará `admin` y `consulta`. En un volumen existente, el inicializador seguro los buscará/creará por `nombre` si faltan, sin eliminar la BD.
3. El proceso PHP `api_investigacion/scripts/inicializar_admin.php` se integrará en el arranque Docker, **únicamente por CLI**. Validará presencia y estado de cuentas, generará `password_hash(PASSWORD_DEFAULT)` y asignará el `rol_id` consultado, sin tener ruta web.
4. Reiniciar la aplicación y repetir el proceso **no** creará nuevos administradores ni cambiará contraseñas. Si ya hay cuentas ambiguas, se detendrá con instrucciones de recuperación autorizada. Tras el alta, retirar la contraseña de bootstrap de la configuración persistente y custodiar la cuenta.
5. **No ejecutar** `docker compose down -v`: destruiría el volumen de MariaDB. No hay comando definitivo de inicialización hasta inspeccionar `docker-compose.yml` y comprobar que el proceso cumple la regla de un solo arranque.

**Consultas de comprobación (de solo lectura, para ejecutar una vez implementado y con acceso autorizado):**

```sql
SELECT id, nombre, activo FROM rol WHERE nombre IN ('admin', 'consulta');
SELECT u.id, u.username, u.activo, r.nombre AS rol, r.activo AS rol_activo
FROM usuario u
JOIN rol_usuario ru ON ru.usuario_id = u.id
JOIN rol r ON r.id = ru.rol_id
WHERE r.nombre = 'admin';
```

Nunca mostrar `usuario.password` ni contraseñas en capturas. El `SELECT` no prueba la validez del hash: comprobar el login real y la idempotencia por separado.

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

**Importante:** las claves `username`, `password` y `datos.token` siguen siendo de la propuesta contractual; adaptarlas si el equipo aprueba otra forma. No mostrar `$tokenConsulta` en videos o capturas.

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
| Dar de baja relación puente | Según política C8, **no asumir DELETE físico** |

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
| Bootstrap de roles/primer administrador, idempotencia, volumen existente | Pendiente (decisión documentada, sin código) | — |
| Login correcto e incorrecto | Pendiente | — |
| 401/403 por HTTP | Pendiente | — |
| CRUD usuario/rol/rol_usuario | Pendiente | — |
| Hash de contraseñas | Pendiente | — |
| Frontend + logout | Pendiente | — |
| Regresión v1/v2 | Pendiente | — |
| API apagada / frontend activo | Pendiente | — |
| Ausencia de secretos versionados | Pendiente | — |

> No marcar ninguno como aprobado hasta ejecutar pruebas y reunir evidencias con credenciales/token ocultos.

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
| Base nueva con roles semilla y bootstrap | Diseñado; no probado | Logs sanitizados, SELECT de roles, login válido |
| Base existente con los mismos roles | Diseñado; no probado | Conteos antes/después, sin `down -v` |
| Repetir bootstrap | Diseñado; no probado | Sin usuario duplicado ni cambio de contraseña |
| Credenciales iniciales faltantes | Diseñado; no probado | Proceso falla cerrado sin valor por defecto |
| Token alterado/expirado/algoritmo inesperado | Diseñado; no probado | 401 y ningún efecto lateral |
| Permisos admin/consulta en 16 recursos | Diseñado; no probado | Matriz de métodos, 200/204 o 403 según caso |
| CRUD de las tres tablas de seguridad | Diseñado; no probado | 201, 404, 409, 422 y cambio del estado real |
| Logout y API apagada | Diseñado; no probado | Captura sin datos ni token expuesto |

> **Importante:** las rutas, puertos y comandos de los apartados previos siguen sujetos al Docker Compose real. El ZIP contiene únicamente documentos; **ningún test de V3 se ejecutó** durante su preparación. La mención del ejemplo `v3_segundo_motor` no implica probar PostgreSQL en nuestro proyecto.
