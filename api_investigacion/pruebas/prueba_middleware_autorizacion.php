<?php

declare(strict_types=1);

// Pruebas aisladas: NO utilizan Docker, MariaDB ni credenciales reales.
require_once __DIR__ . '/../repositorios/Repositorios_Autenticacion/IRepositorioAutenticacion.php';
require_once __DIR__ . '/../servicios/Servicios_Autenticacion/JwtHS256.php';
require_once __DIR__ . '/../excepciones/ExcepcionAcceso.php';
require_once __DIR__ . '/../servicios/Servicios_Autorizacion/ServicioAutorizacion.php';
require_once __DIR__ . '/../middleware/MiddlewareAutorizacion.php';

final class RepositorioAutenticacionFalsoV3 implements IRepositorioAutenticacion
{
    public bool $usuarioActivo = true;
    public string $usernameActual = 'ADMIN';
    public int $idActual = 17;
    public array $rolesActuales = ['admin'];

    public function obtenerUsuarioActivoPorUsername(string $username): ?array
    {
        if (!$this->usuarioActivo || $username !== $this->usernameActual) {
            return null;
        }
        return [
            'id' => $this->idActual,
            'username' => $this->usernameActual,
            'password' => 'NO-ES-CONTRASEÑA-REAL',
        ];
    }

    public function listarRolesActivos(int $usuarioId): array
    {
        return $usuarioId === $this->idActual ? $this->rolesActuales : [];
    }
}

$repo = new RepositorioAutenticacionFalsoV3();
$jwt = new JwtHS256(str_repeat('x', 48)); // Secreto fijo SÓLO para pruebas locales.
$servicio = new ServicioAutorizacion($repo, $jwt);
$middleware = new MiddlewareAutorizacion($servicio);
$token = $jwt->emitir(17, 'ADMIN');
$ok = 0;
$total = 0;

/** @param Closure():mixed $f */
function pruebaV3(string $descripcion, Closure $f): void
{
    global $ok, $total;
    $total++;
    try {
        if ($f() !== true) {
            throw new RuntimeException('Resultado distinto al esperado.');
        }
        $ok++;
        echo "OK: {$descripcion}\n";
    } catch (Throwable $e) {
        echo "FALLO: {$descripcion} — {$e->getMessage()}\n";
    }
}

function estadoEsperado(Closure $f, int $esperado): bool
{
    try {
        $f();
        return false;
    } catch (ExcepcionAcceso $e) {
        return $e->estadoHttp() === $esperado;
    }
}

pruebaV3('Sin Authorization -> 401', fn() => estadoEsperado(
    fn() => $middleware->autorizar('GET', '/api/docente', null), 401
));
pruebaV3('Bearer vacío -> 401', fn() => estadoEsperado(
    fn() => $middleware->autorizar('GET', '/api/docente', 'Bearer '), 401
));
pruebaV3('Basic no permitido -> 401', fn() => estadoEsperado(
    fn() => $middleware->autorizar('GET', '/api/docente', 'Basic abc123'), 401
));
pruebaV3('Cabecera múltiple malformada -> 401', fn() => estadoEsperado(
    fn() => $middleware->autorizar('GET', '/api/docente', 'Bearer ' . $token . ', Bearer otro'), 401
));
pruebaV3('Token alterado -> 401', fn() => estadoEsperado(
    fn() => $middleware->autorizar('GET', '/api/docente', 'Bearer ' . substr($token, 0, -1) . (substr($token, -1) === 'x' ? 'a' : 'x')), 401
));
pruebaV3('Rol admin puede consultar v1', fn() => $middleware->autorizar('GET', '/api/area_conocimiento', 'Bearer ' . $token)['id'] === 17);
pruebaV3('Rol admin puede crear v2', fn() => $middleware->autorizar('POST', '/api/docente', 'Bearer ' . $token)['username'] === 'ADMIN');
pruebaV3('Rol admin puede cambiar v2', fn() => $middleware->autorizar('PATCH', '/api/participa_grupo/17/8', 'Bearer ' . $token)['id'] === 17);
pruebaV3('Rol admin puede consultar usuarios', fn() => $middleware->autorizar('GET', '/api/usuario', 'Bearer ' . $token)['id'] === 17);
pruebaV3('Rol admin puede administrar roles', fn() => $middleware->autorizar('DELETE', '/api/rol/3', 'Bearer ' . $token)['id'] === 17);
pruebaV3('Rol admin puede asignar roles', fn() => $middleware->autorizar('POST', '/api/rol_usuario', 'Bearer ' . $token)['id'] === 17);

$repo->rolesActuales = ['consulta'];
pruebaV3('Consulta puede GET v1', fn() => $middleware->autorizar('GET', '/api/universidad', 'Bearer ' . $token)['roles'] === ['consulta']);
pruebaV3('Consulta puede GET v2', fn() => $middleware->autorizar('GET', '/api/semillero_linea/2/3', 'bearer ' . $token)['id'] === 17);
pruebaV3('Consulta no puede POST v1 -> 403', fn() => estadoEsperado(
    fn() => $middleware->autorizar('POST', '/api/area_conocimiento', 'Bearer ' . $token), 403
));
pruebaV3('Consulta no puede DELETE v2 -> 403', fn() => estadoEsperado(
    fn() => $middleware->autorizar('DELETE', '/api/docente/5', 'Bearer ' . $token), 403
));
pruebaV3('Consulta no puede leer usuario -> 403', fn() => estadoEsperado(
    fn() => $middleware->autorizar('GET', '/api/usuario', 'Bearer ' . $token), 403
));
pruebaV3('Consulta no puede leer rol -> 403', fn() => estadoEsperado(
    fn() => $middleware->autorizar('GET', '/api/rol/1', 'Bearer ' . $token), 403
));
pruebaV3('Consulta no puede leer rol_usuario -> 403', fn() => estadoEsperado(
    fn() => $middleware->autorizar('GET', '/api/rol_usuario', 'Bearer ' . $token), 403
));
pruebaV3('Consulta no puede leer ruta futura/desconocida -> 403', fn() => estadoEsperado(
    fn() => $middleware->autorizar('GET', '/api/auditorias', 'Bearer ' . $token), 403
));
pruebaV3('Consulta no puede método HEAD -> 403', fn() => estadoEsperado(
    fn() => $middleware->autorizar('HEAD', '/api/universidad', 'Bearer ' . $token), 403
));

$repo->rolesActuales = ['admin', 'consulta'];
pruebaV3('Usuario multirrol tiene permisos admin', fn() => $middleware->autorizar('PUT', '/api/docente/1', 'Bearer ' . $token)['roles'] === ['admin', 'consulta']);
$repo->rolesActuales = ['consulta'];
pruebaV3('Retirar admin tiene efecto inmediato -> 403', fn() => estadoEsperado(
    fn() => $middleware->autorizar('PUT', '/api/docente/1', 'Bearer ' . $token), 403
));
$repo->rolesActuales = [];
pruebaV3('Usuario sin roles activos -> 403', fn() => estadoEsperado(
    fn() => $middleware->autorizar('GET', '/api/docente', 'Bearer ' . $token), 403
));
$repo->rolesActuales = ['rol_desconocido'];
pruebaV3('Rol arbitrario no concede permisos -> 403', fn() => estadoEsperado(
    fn() => $middleware->autorizar('GET', '/api/docente', 'Bearer ' . $token), 403
));
$repo->rolesActuales = ['admin'];
$repo->usuarioActivo = false;
pruebaV3('Usuario desactivado -> 401', fn() => estadoEsperado(
    fn() => $middleware->autorizar('GET', '/api/docente', 'Bearer ' . $token), 401
));
$repo->usuarioActivo = true;
$repo->usernameActual = 'OTRO';
pruebaV3('Username cambiado requiere login nuevo -> 401', fn() => estadoEsperado(
    fn() => $middleware->autorizar('GET', '/api/docente', 'Bearer ' . $token), 401
));
$repo->usernameActual = 'ADMIN';
$repo->idActual = 91;
pruebaV3('Identificador no coincide -> 401', fn() => estadoEsperado(
    fn() => $middleware->autorizar('GET', '/api/docente', 'Bearer ' . $token), 401
));
$repo->idActual = 17;
pruebaV3('Token imposible de interpretar -> 401', fn() => estadoEsperado(
    fn() => $middleware->autorizar('GET', '/api/docente', 'Bearer a.b.c'), 401
));

printf("\nPruebas de autorización: %d / %d.\n", $ok, $total);
exit($ok === $total ? 0 : 1);
