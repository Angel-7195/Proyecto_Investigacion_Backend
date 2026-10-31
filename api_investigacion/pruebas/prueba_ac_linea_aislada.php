<?php

declare(strict_types=1);

require_once __DIR__ . '/../servicios/Servicios_AcLinea/ServicioAcLinea.php';
require_once __DIR__ . '/../controladores/ControladorAcLinea.php';

// Mantener los encabezados HTTP disponibles durante las pruebas CLI.
ob_start();

final class RepositorioAcLineaFalso implements IRepositorioAcLinea
{
    /** @var array<string,AcLinea> */
    private array $filas = [];

    private function clave(int $linea, string $area): string
    {
        return $linea . ':' . $area;
    }

    public function obtenerTodos(): array
    {
        return array_values($this->filas);
    }

    public function obtenerPorClave(int $lineaInvestigacion, string $areaConocimiento): ?AcLinea
    {
        return $this->filas[$this->clave($lineaInvestigacion, $areaConocimiento)] ?? null;
    }

    public function crear(AcLinea $asociacion): int
    {
        $clave = $this->clave($asociacion->getLineaInvestigacion(), $asociacion->getAreaConocimiento());
        if (isset($this->filas[$clave])) {
            throw new ConflictoExcepcion('Duplicado.');
        }
        $this->filas[$clave] = $asociacion;
        return 1;
    }

    public function retirar(int $lineaInvestigacion, string $areaConocimiento): int
    {
        $clave = $this->clave($lineaInvestigacion, $areaConocimiento);
        if (!isset($this->filas[$clave])) {
            return 0;
        }
        unset($this->filas[$clave]);
        return 1;
    }
}

final class RepositorioLineaFalso implements IRepositorioLineaInvestigacion
{
    public function obtenerTodos(): array { return []; }
    public function obtenerPorId(int $id): ?LineaInvestigacion
    {
        return $id === 7 ? new LineaInvestigacion(7, 'Software', 'Descripción') : null;
    }
    public function crear(LineaInvestigacion $lineaInvestigacion): int { return 0; }
    public function reemplazar(int $id, array $datos): int { return 0; }
    public function actualizar(int $id, array $datos): int { return 0; }
    public function retirar(int $id): int { return 0; }
}

final class RepositorioAreaFalso implements IRepositorioAreaConocimiento
{
    public function listar(): array { return []; }
    public function obtenerPorId(string $id): ?AreaConocimiento
    {
        return $id === '1A01'
            ? new AreaConocimiento('1A01', 'Ciencias Naturales', 'Matemáticas', 'Aplicadas')
            : null;
    }
    public function crear(AreaConocimiento $registro): int { return 0; }
    public function reemplazar(string $id, array $datos): int { return 0; }
    public function actualizar(string $id, array $datos): int { return 0; }
    public function retirar(string $id): int { return 0; }
}

function comprobar(bool $condicion, string $descripcion): void
{
    if (!$condicion) {
        throw new RuntimeException('FALLO: ' . $descripcion);
    }
    echo "APROBADO: $descripcion\n";
}

$repositorio = new RepositorioAcLineaFalso();
$servicio = new ServicioAcLinea($repositorio, new RepositorioLineaFalso(), new RepositorioAreaFalso());
$controlador = new ControladorAcLinea($servicio);

comprobar($servicio->listar() === [], 'Listado inicial vacío');
$asociacion = $servicio->crear(new AcLinea(7, '1A01'));
comprobar($asociacion->toArray() === [
    'linea_investigacion' => 7, 'area_conocimiento' => '1A01',
], 'Creación y serialización');
comprobar(count($servicio->listar()) === 1, 'Listado con un elemento');
comprobar($servicio->obtenerPorClave(7, '1A01')->getAreaConocimiento() === '1A01', 'Consulta por PK compuesta');

try {
    $servicio->crear(new AcLinea(7, '1A01'));
    throw new RuntimeException('FALLO: debía rechazar duplicado');
} catch (ConflictoExcepcion $esperada) {
    echo "APROBADO: Rechazo de duplicados\n";
}
try {
    $servicio->crear(new AcLinea(99, '1A01'));
    throw new RuntimeException('FALLO: debía rechazar línea inexistente');
} catch (ConflictoExcepcion $esperada) {
    echo "APROBADO: Línea inexistente\n";
}
try {
    $servicio->crear(new AcLinea(7, '9999'));
    throw new RuntimeException('FALLO: debía rechazar área inexistente');
} catch (ConflictoExcepcion $esperada) {
    echo "APROBADO: Área inexistente\n";
}
ob_start();
$controlador->actualizar(7, '1A01', []);
$salida = (string) ob_get_clean();
comprobar(http_response_code() === 405 && str_contains($salida, '405'), 'PATCH no permitido');
ob_start();
$controlador->reemplazar(7, '1A01', []);
$salida = (string) ob_get_clean();
comprobar(http_response_code() === 405 && str_contains($salida, '405'), 'PUT no permitido');
comprobar($servicio->retirar(7, '1A01') === 1, 'Retiro lógico solicitado');
try {
    $servicio->obtenerPorClave(7, '1A01');
    throw new RuntimeException('FALLO: debía rechazar registro retirado');
} catch (NoEncontradoExcepcion $esperada) {
    echo "APROBADO: Retirado no disponible\n";
}

echo "Pruebas aisladas de ac_linea finalizadas correctamente.\n";
