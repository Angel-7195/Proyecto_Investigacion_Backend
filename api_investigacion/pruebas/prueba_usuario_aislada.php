<?php

declare(strict_types=1);

/** Prueba sin base de datos ni peticiones HTTP. No usar contraseñas reales aquí. */
require_once __DIR__ . '/../modelos/Usuario.php';
require_once __DIR__ . '/../excepciones/ExcepcionUsuario.php';
require_once __DIR__ . '/../repositorios/Repositorios_Usuario/IRepositorioUsuario.php';
require_once __DIR__ . '/../servicios/Servicios_Usuario/IServicioUsuario.php';
require_once __DIR__ . '/../servicios/Servicios_Usuario/ServicioUsuario.php';

final class RepositorioUsuarioFalso implements IRepositorioUsuario
{
    /** @var array<int,array<string,mixed>> */
    public array $filas = [];
    private int $siguiente = 1;
    public bool $ultimoAdmin = false;

    private function mapear(int $id): ?Usuario
    {
        $fila = $this->filas[$id] ?? null;
        if ($fila === null || !$fila['activo']) {
            return null;
        }
        return Usuario::desdeFila($fila);
    }

    public function listar(): array
    {
        return array_values(array_filter(array_map(
            fn (int $id): ?Usuario => $this->mapear($id),
            array_keys($this->filas)
        )));
    }

    public function obtener(int $id): ?Usuario { return $this->mapear($id); }

    public function crear(string $username, string $email, ?string $nombreCompleto, string $hash): Usuario
    {
        foreach ($this->filas as $fila) {
            if ($fila['username'] === $username || $fila['email'] === $email) {
                throw ExcepcionUsuario::conflicto('Usuario o correo ya registrados.');
            }
        }
        $id = $this->siguiente++;
        $this->filas[$id] = [
            'id' => $id, 'username' => $username, 'email' => $email,
            'nombre_completo' => $nombreCompleto, 'password' => $hash, 'activo' => true,
        ];
        return $this->mapear($id);
    }

    public function actualizarPerfil(int $id, array $datos): ?Usuario
    {
        if ($this->mapear($id) === null) { return null; }
        foreach ($datos as $clave => $valor) { $this->filas[$id][$clave] = $valor; }
        return $this->mapear($id);
    }

    public function retirar(int $id): bool
    {
        if ($this->mapear($id) === null) { return false; }
        if ($this->ultimoAdmin) {
            throw ExcepcionUsuario::conflicto('No puede retirar al último administrador activo.');
        }
        $this->filas[$id]['activo'] = false;
        return true;
    }

    public function actualizarHash(int $id, string $hash): bool
    {
        if ($this->mapear($id) === null) { return false; }
        $this->filas[$id]['password'] = $hash;
        return true;
    }
}

$ok = 0;
function verificar(bool $condicion, string $descripcion): void
{
    global $ok;
    if (!$condicion) { throw new RuntimeException("FALLÓ: $descripcion"); }
    $ok++;
    echo "OK: $descripcion\n";
}
function falla(callable $f, string $clase): bool
{
    try { $f(); } catch (Throwable $e) { return $e instanceof $clase; }
    return false;
}

$repo = new RepositorioUsuarioFalso();
$servicio = new ServicioUsuario($repo);
verificar($servicio->listar() === [], 'Listado vacío');
verificar(falla(fn () => $servicio->crear([]), InvalidArgumentException::class), 'POST requiere datos');
verificar(falla(fn () => $servicio->crear([
    'username' => 'a', 'email' => 'no-es-email', 'password' => 'contraseñaDemo123!'
]), InvalidArgumentException::class), 'Correo inválido -> 422');
verificar(falla(fn () => $servicio->crear([
    'username' => 'a', 'email' => 'a@example.com', 'password' => 'corta'
]), InvalidArgumentException::class), 'Contraseña corta -> 422');
verificar(falla(fn () => $servicio->crear([
    'username' => 'a', 'email' => 'a@example.com', 'password' => 'contraseñaDemo123!', 'activo' => 0
]), InvalidArgumentException::class), 'No admite activo desde cliente');

$usuario = $servicio->crear([
    'username' => 'ADMIN', 'email' => 'admin@example.com',
    'nombre_completo' => 'Persona Demo', 'password' => 'ClaveFicticia123!'
]);
verificar($usuario['id'] === 1 && $usuario['username'] === 'ADMIN', 'POST crea usuario');
verificar(!array_key_exists('password', $usuario), 'POST nunca entrega hash');
verificar($repo->filas[1]['password'] !== 'ClaveFicticia123!', 'Contraseña no se almacena en claro');
verificar(password_verify('ClaveFicticia123!', $repo->filas[1]['password']), 'Hash compatible con login');
verificar(count($servicio->listar()) === 1, 'GET lista activos');
verificar($servicio->obtener(1)['email'] === 'admin@example.com', 'GET individual');
verificar(falla(fn () => $servicio->crear([
    'username' => 'ADMIN', 'email' => 'nuevo@example.com', 'password' => 'ClaveFicticia123!'
]), ExcepcionUsuario::class), 'Duplicado -> conflicto');
verificar(falla(fn () => $servicio->actualizar(1, []), InvalidArgumentException::class), 'PATCH vacío -> 422');
verificar(falla(fn () => $servicio->actualizar(1, ['password' => 'ClaveFicticia123!']), InvalidArgumentException::class), 'PATCH perfil no cambia clave');

$hashAnterior = $repo->filas[1]['password'];
verificar($servicio->actualizar(1, ['nombre_completo' => 'Nuevo Nombre'])['nombre_completo'] === 'Nuevo Nombre', 'PATCH modifica campo');
verificar($repo->filas[1]['password'] === $hashAnterior, 'PATCH preserva hash');
verificar($servicio->reemplazar(1, ['username' => 'ADMIN2', 'email' => 'nuevo@example.com'])['nombre_completo'] === null, 'PUT reemplaza perfil sin nombre');
verificar($repo->filas[1]['password'] === $hashAnterior, 'PUT preserva hash');
verificar(falla(fn () => $servicio->reemplazar(1, ['username' => 'ADMIN3']), InvalidArgumentException::class), 'PUT exige username y email');

$servicio->cambiarContrasena(1, ['password' => 'ClaveNuevaFicticia123!']);
verificar(password_verify('ClaveNuevaFicticia123!', $repo->filas[1]['password']), 'Cambio explícito genera hash nuevo');
verificar(!password_verify('ClaveFicticia123!', $repo->filas[1]['password']), 'Contraseña anterior deja de autenticar');
verificar(falla(fn () => $servicio->cambiarContrasena(1, ['password' => 'pequeña']), InvalidArgumentException::class), 'Cambio inválido -> 422');

$repo->ultimoAdmin = true;
verificar(falla(fn () => $servicio->retirar(1), ExcepcionUsuario::class), 'Protección último admin (repositorio falso)');
$repo->ultimoAdmin = false;
$servicio->retirar(1);
verificar(count($servicio->listar()) === 0, 'DELETE lógico oculta en GET');
verificar($repo->filas[1]['activo'] === false, 'DELETE conserva la fila');
verificar(falla(fn () => $servicio->obtener(1), ExcepcionUsuario::class), 'GET retirado -> 404');
verificar(falla(fn () => $servicio->retirar(1), ExcepcionUsuario::class), 'Segundo DELETE -> 404');
verificar(falla(fn () => $servicio->cambiarContrasena(1, ['password' => 'ClaveNuevaFicticia123!']), ExcepcionUsuario::class), 'Usuario retirado no cambia hash');

echo "\nPruebas aisladas de usuario: $ok / $ok.\n";
