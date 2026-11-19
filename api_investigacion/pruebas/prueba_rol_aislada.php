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
    public function __construct(private array $roles = [])
    {
    }

    public function listar(): array
    {
        return array_values($this->roles);
    }

    public function obtener(int $id): ?Rol
    {
        return $this->roles[$id] ?? null;
    }
}

function comprobarRol(string $caso, mixed $esperado, mixed $real): void
{
    if ($esperado !== $real) {
        throw new RuntimeException(
            $caso . ': esperado ' . var_export($esperado, true)
            . ', obtenido ' . var_export($real, true)
        );
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

// Prueba de listado vacío.
$controladorVacio = new ControladorRol(
    new ServicioRol(new RepositorioRolEnMemoria())
);

probarRespuestaRol($controladorVacio->listar(...), 204);

// Repositorio con los dos roles permitidos.
$roles = [
    1 => new Rol(1, 'admin', null),
    2 => new Rol(2, 'consulta', null),
];

$controlador = new ControladorRol(
    new ServicioRol(new RepositorioRolEnMemoria($roles))
);

// Prueba del listado.
$lista = probarRespuestaRol($controlador->listar(...), 200);

comprobarRol('cantidad de roles', 2, count($lista));

comprobarRol(
    'primer rol',
    ['id' => 1, 'nombre' => 'admin', 'descripcion' => null],
    $lista[0]
);

comprobarRol(
    'segundo rol',
    ['id' => 2, 'nombre' => 'consulta', 'descripcion' => null],
    $lista[1]
);

// Prueba de consulta individual.
probarRespuestaRol(fn () => $controlador->obtener(1), 200);
probarRespuestaRol(fn () => $controlador->obtener(2), 200);

// Prueba de identificador inexistente.
probarRespuestaRol(fn () => $controlador->obtener(99), 404);

// Prueba de identificador inválido.
probarRespuestaRol(fn () => $controlador->obtener(0), 422);

echo "6 escenarios aislados de rol aprobados.\n";
