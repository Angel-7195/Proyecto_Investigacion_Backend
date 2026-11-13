<?php

declare(strict_types=1);

$base = dirname(__DIR__);
require_once $base . '/modelos/RolUsuario.php';
require_once $base . '/excepciones/ExcepcionRolUsuario.php';
require_once $base . '/repositorios/Repositorios_RolUsuario/IRepositorioRolUsuario.php';
require_once $base . '/servicios/Servicios_RolUsuario/IServicioRolUsuario.php';
require_once $base . '/servicios/Servicios_RolUsuario/ServicioRolUsuario.php';

final class RepositorioFalsoRolUsuario implements IRepositorioRolUsuario
{
    /** @var array<string,RolUsuario> */
    public array $relaciones = [];
    public function listar(): array { return array_values($this->relaciones); }
    public function obtener(int $u, int $r): ?RolUsuario { return $this->relaciones["$u:$r"] ?? null; }
    public function crear(int $u, int $r): RolUsuario {
        if (isset($this->relaciones["$u:$r"])) { throw ExcepcionRolUsuario::conflicto('Duplicado.'); }
        return $this->relaciones["$u:$r"] = new RolUsuario($u, $r);
    }
    public function retirar(int $u, int $r): bool {
        if (!isset($this->relaciones["$u:$r"])) { return false; }
        unset($this->relaciones["$u:$r"]); return true;
    }
}

$repo = new RepositorioFalsoRolUsuario();
$servicio = new ServicioRolUsuario($repo);
$exitos = 0;
function probar(string $nombre, callable $prueba): void {
    global $exitos;
    try { $prueba(); $exitos++; echo "OK: $nombre\n"; }
    catch (Throwable $e) { echo "ERROR: $nombre: {$e->getMessage()}\n"; exit(1); }
}
function afirmar(bool $valor): void { if (!$valor) { throw new RuntimeException('Resultado inesperado.'); } }
function esperar(string $excepcion, callable $accion, ?int $http = null): void {
    try { $accion(); } catch (Throwable $e) {
        afirmar($e instanceof $excepcion);
        if ($http !== null) { afirmar($e->estadoHttp() === $http); }
        return;
    }
    throw new RuntimeException('No lanzó excepción.');
}
probar('Lista vacía', fn () => afirmar($servicio->listar() === []));
probar('POST requiere ambos IDs', fn () => esperar(InvalidArgumentException::class, fn () => $servicio->crear(['usuario_id'=>2])));
probar('POST no permite campos extra', fn () => esperar(InvalidArgumentException::class, fn () => $servicio->crear(['usuario_id'=>2,'rol_id'=>3,'activo'=>1])));
probar('POST exige enteros', fn () => esperar(InvalidArgumentException::class, fn () => $servicio->crear(['usuario_id'=>'2','rol_id'=>3])));
probar('POST exige positivos', fn () => esperar(InvalidArgumentException::class, fn () => $servicio->crear(['usuario_id'=>2,'rol_id'=>0])));
probar('Crear asignación', fn () => afirmar($servicio->crear(['usuario_id'=>2,'rol_id'=>3]) === ['usuario_id'=>2,'rol_id'=>3]));
probar('GET asignación', fn () => afirmar($servicio->obtener(2,3)['rol_id'] === 3));
probar('GET listado', fn () => afirmar(count($servicio->listar()) === 1));
probar('Duplicado 409', fn () => esperar(ExcepcionRolUsuario::class, fn () => $servicio->crear(['usuario_id'=>2,'rol_id'=>3]),409));
probar('GET inexistente 404', fn () => esperar(ExcepcionRolUsuario::class, fn () => $servicio->obtener(2,4),404));
probar('DELETE asignación', function () use ($servicio): void { $servicio->retirar(2,3); afirmar($servicio->listar() === []); });
probar('DELETE repetido 404', fn () => esperar(ExcepcionRolUsuario::class, fn () => $servicio->retirar(2,3),404));
probar('GET identificador cero inválido', fn () => esperar(InvalidArgumentException::class, fn () => $servicio->obtener(0,1)));
probar('Otro rol mismo usuario permitido', fn () => afirmar($servicio->crear(['usuario_id'=>2,'rol_id'=>4])['rol_id'] === 4));
probar('Otro usuario mismo rol permitido', fn () => afirmar($servicio->crear(['usuario_id'=>3,'rol_id'=>4])['usuario_id'] === 3));
echo "Pruebas aisladas de rol_usuario: $exitos / 15.\n";
