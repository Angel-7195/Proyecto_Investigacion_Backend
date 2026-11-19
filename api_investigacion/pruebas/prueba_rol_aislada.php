<?php

declare(strict_types=1);

require_once __DIR__ . '/../modelos/Rol.php';
require_once __DIR__ . '/../excepciones/ExcepcionRol.php';
require_once __DIR__ . '/../repositorios/Repositorios_Rol/IRepositorioRol.php';
require_once __DIR__ . '/../servicios/Servicios_Rol/IServicioRol.php';
require_once __DIR__ . '/../servicios/Servicios_Rol/ServicioRol.php';
require_once __DIR__ . '/../controladores/ControladorRol.php';

final class RepositorioRolEnMemoria implements IRepositorioRol
{
    private array $roles = [];
    private array $retirados = [];

    public function listar(): array
    {
        return array_values(array_diff_key($this->roles, $this->retirados));
    }

    public function obtener(int $id): ?Rol
    {
        return isset($this->retirados[$id]) ? null : ($this->roles[$id] ?? null);
    }

    public function crear(string $nombre, ?string $descripcion): Rol
    {
        $id = count($this->roles) + 1;
        $this->comprobarDuplicado($id, $nombre);
        return $this->roles[$id] = new Rol($id, $nombre, $descripcion);
    }

    public function actualizar(int $id, array $cambios): ?Rol
    {
        $rol = $this->obtener($id);
        if ($rol === null) {
            return null;
        }

        $datos = $cambios + $rol->aArray();
        $this->comprobarDuplicado($id, $datos['nombre']);
        return $this->roles[$id] = new Rol($id, $datos['nombre'], $datos['descripcion']);
    }

    public function retirar(int $id): bool
    {
        if ($this->obtener($id) === null) {
            return false;
        }
        return $this->retirados[$id] = true;
    }

    private function comprobarDuplicado(int $id, string $nombre): void
    {
        foreach ($this->roles as $rol) {
            if ($rol->id !== $id && $rol->nombre === $nombre) {
                throw ExcepcionRol::conflicto('Ya existe un rol con ese nombre.');
            }
        }
    }
}

function comprobarRol(string $caso, mixed $esperado, mixed $real): void
{
    if ($esperado !== $real) {
        throw new RuntimeException($caso . ': esperado ' . var_export($esperado, true)
            . ', obtenido ' . var_export($real, true));
    }
}

function probarRespuestaRol(callable $operacion, int $estado): array
{
    ob_start();
    try {
        $operacion();
        $json = ob_get_contents();
    } finally {
        ob_end_clean();
    }
    comprobarRol('estado HTTP', $estado, http_response_code());
    if ($estado === 204) {
        comprobarRol('cuerpo vacío en 204', '', $json);
        return [];
    }
    $respuesta = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
    comprobarRol('estado JSON', $estado, $respuesta['estado']);
    return $respuesta['datos'] ?? [];
}

$controlador = new ControladorRol(new ServicioRol(new RepositorioRolEnMemoria()));
probarRespuestaRol($controlador->listar(...), 204);
$rol = probarRespuestaRol(fn () => $controlador->crear(['nombre' => ' edición ']), 201);
$id = $rol['id'];
comprobarRol('creación y forma pública', ['id' => $id, 'nombre' => 'edición', 'descripcion' => null], $rol);
comprobarRol('listado', [$rol], probarRespuestaRol($controlador->listar(...), 200));
comprobarRol('lectura', $rol, probarRespuestaRol(fn () => $controlador->obtener($id), 200));
probarRespuestaRol(fn () => $controlador->crear(['nombre' => 'edición']), 409);
$otro = probarRespuestaRol(fn () => $controlador->crear(['nombre' => str_repeat('á', 100)]), 201);
probarRespuestaRol(fn () => $controlador->crear(['nombre' => str_repeat('á', 101)]), 422);
probarRespuestaRol(fn () => $controlador->crear(['nombre' => ' ']), 422);
probarRespuestaRol(fn () => $controlador->crear(['nombre' => 42]), 422);
probarRespuestaRol(fn () => $controlador->crear(['nombre' => 'privado', 'activo' => 0]), 422);
probarRespuestaRol(fn () => $controlador->crear(['nombre' => 'privado', 'descripcion' => []]), 422);
probarRespuestaRol(fn () => $controlador->actualizar($id, []), 422);
probarRespuestaRol(fn () => $controlador->actualizar($id, ['nombre' => $otro['nombre']]), 409);
$editado = probarRespuestaRol(fn () => $controlador->actualizar($id, ['descripcion' => 'Detalle']), 200);
comprobarRol('PATCH conserva nombre', 'edición', $editado['nombre']);
comprobarRol('PATCH actualiza descripción', 'Detalle', $editado['descripcion']);
$reemplazado = probarRespuestaRol(fn () => $controlador->reemplazar($id, ['nombre' => 'nuevo']), 200);
comprobarRol('PUT repone descripción opcional a null', null, $reemplazado['descripcion']);
probarRespuestaRol(fn () => $controlador->reemplazar($id, ['descripcion' => 'sin nombre']), 422);
probarRespuestaRol(fn () => $controlador->retirar($id), 200);
comprobarRol('listado sin retirados', [$otro], probarRespuestaRol($controlador->listar(...), 200));
probarRespuestaRol(fn () => $controlador->obtener($id), 404);
probarRespuestaRol(fn () => $controlador->retirar($id), 404);
probarRespuestaRol(fn () => $controlador->actualizar($id, ['nombre' => 'otro']), 404);
probarRespuestaRol(fn () => $controlador->crear(['nombre' => 'nuevo']), 409);
echo "22 escenarios aislados de rol aprobados.\n";
