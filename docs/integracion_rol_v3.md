# Integración del catálogo de roles V3

Entrega de Miguel Angel Lotero Alvarez para B01–B05. El catálogo administra el recurso `rol`. La relación `rol_usuario` mantiene su flujo de asignación independiente.

## Backend

En `api_investigacion/servicios/ensamblador.php`, añadir las dependencias siguiendo el orden de carga existente y registrar la fábrica.

```php
require_once __DIR__ . '/../modelos/Rol.php';
require_once __DIR__ . '/../excepciones/ExcepcionRol.php';
require_once __DIR__ . '/../repositorios/Repositorios_Rol/IRepositorioRol.php';
require_once __DIR__ . '/../repositorios/Repositorios_Rol/RepositorioRolMariaDB.php';
require_once __DIR__ . '/Servicios_Rol/IServicioRol.php';
require_once __DIR__ . '/Servicios_Rol/ServicioRol.php';
require_once __DIR__ . '/../controladores/ControladorRol.php';

function crearControladorRol(): ControladorRol
{
    return new ControladorRol(new ServicioRol(new RepositorioRolMariaDB(crearConexion())));
}
```

En `api_investigacion/index.php`, agregar este bloque después de autorizar la solicitud y decodificar el JSON, antes de la respuesta de ruta desconocida. El guard existente habilita para `consulta` solo los GET de su lista permitida. Mantener `rol` fuera de esa lista.

```php
if ($ruta === '/api/rol') {
    if (!in_array($metodo, ['GET', 'POST'], true)) {
        responderNoPermitido();
        return;
    }
    $controladorRol = crearControladorRol();
    if ($metodo === 'GET') {
        $controladorRol->listar();
    } else {
        $controladorRol->crear($cuerpo);
    }
    return;
}

if (preg_match('#^/api/rol/([1-9]\d*)$#', $ruta, $coincidenciaRol)) {
    if (!in_array($metodo, ['GET', 'PUT', 'PATCH', 'DELETE'], true)) {
        responderNoPermitido();
        return;
    }
    $idRol = (int) $coincidenciaRol[1];
    $controladorRol = crearControladorRol();
    match ($metodo) {
        'GET' => $controladorRol->obtener($idRol),
        'PUT' => $controladorRol->reemplazar($idRol, $cuerpo),
        'PATCH' => $controladorRol->actualizar($idRol, $cuerpo),
        'DELETE' => $controladorRol->retirar($idRol),
    };
    return;
}
```

El API devuelve `id`, `nombre` y `descripcion`. PUT exige nombre y reemplaza la descripción, con `null` si se omite. PATCH modifica los campos enviados, acepta `null` para limpiar la descripción y devuelve `422` si no recibe cambios solicitados. El retiro conserva la fila con `activo=0`. La restricción UNIQUE de MariaDB conserva el nombre ocupado después del retiro.

Se usó una política conservadora para `admin` pendiente de ratificación del equipo. El catálogo admite actualizar su descripción. Renombrarlo o retirarlo devuelve `409`, ya que sus permisos se resuelven por nombre. El repositorio comprueba el nombre en MariaDB bajo bloqueo de fila y no usa IDs fijos.

## Frontend

En `front_php/index.php`, después de iniciar sesión y cargar `cliente_api.php`, registrar `require __DIR__ . '/rutas/rutas_rol.php';` junto con las rutas administrativas. Añadir un enlace «Catálogo de roles» a `/roles` en el menú de administradores.

En la integración del selector de roles por usuario, reemplazar los IDs fijos con los registros activos devueltos por `adaptarRespuestaApi(llamarApi('GET', 'rol'))`. Los registros están en `$respuesta['datos']['datos']`; con `204` la lista está vacía. Usar `id` como valor y `nombre` como etiqueta, conservando el manejo de errores y el escape HTML de la vista.

## Evidencia de pruebas

```sh
php api_investigacion/pruebas/prueba_rol_aislada.php
php api_investigacion/pruebas/prueba_login_jwt.php
php api_investigacion/pruebas/prueba_middleware_autorizacion.php
php api_investigacion/pruebas/prueba_usuario_aislada.php
php api_investigacion/pruebas/prueba_rol_usuario_aislada.php
```

Resultados con PHP 8.2.12. Sintaxis correcta en los 11 archivos PHP nuevos. Catálogo aislado 22/22, login 15/15, autorización 28/28, usuario 28/28 y asignación 15/15.

En una copia temporal con los registros de rutas anteriores, PHP y MariaDB 10.4.32 con el SQL del repositorio, se ejecutaron 21 escenarios HTTP del catálogo. Se verificaron `401` sin JWT, `403` para consulta, operaciones de admin, duplicados, validación, protección del nombre `admin`, retiro, segundo retiro y lecturas posteriores. SQL confirmó `activo=0` después del retiro. En el frontend se comprobaron alta, listado, escape HTML, edición, confirmación y retiro, rechazo de CSRF inválido y rechazo de acceso con sesión consulta.

La prueba HTTP usó los servidores locales de PHP con `php -S 127.0.0.1:48317 -t api_investigacion api_investigacion/index.php` para la API y `php -S 127.0.0.1:48318 -t front_php front_php/index.php` para el frontend, desde los respectivos repositorios. El frontend apuntó con `URL_API` al puerto de la API. La base y los usuarios de prueba fueron descartables.

Después del registro definitivo en las ramas del equipo, repetir los casos protegidos y las pruebas de regresión de V1/V2 previstas por el Spec Kit.
