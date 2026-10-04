<?php

declare(strict_types=1);

require_once __DIR__ . '/../servicios/ServicioAreaConocimiento.php';
require_once __DIR__ . '/../servicios/ServicioObjetivoDesarrolloSostenible.php';
require_once __DIR__ . '/../servicios/ServicioAreaAplicacion.php';
require_once __DIR__ . '/../excepciones/ConflictoExcepcion.php';

$fallos = 0;
function probarR(string $descripcion, callable $f): void
{
    global $fallos;
    try {
        $f();
        echo "[OK] {$descripcion}" . PHP_EOL;
    } catch (Throwable $e) {
        $fallos++;
        echo "[ERROR] {$descripcion}: {$e->getMessage()}" . PHP_EOL;
    }
}
function verificarR(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}
function esperarR(string $excepcion, callable $f): void
{
    try {
        $f();
    } catch (Throwable $e) {
        if ($e instanceof $excepcion) {
            return;
        }
        throw $e;
    }
    throw new RuntimeException("No se lanzó {$excepcion}.");
}

class FalsoAreaConocimiento implements IRepositorioAreaConocimiento
{
    private array $registros = [];
    public function listar(): array
    {
        return array_values(array_map(
            static fn(array $r): AreaConocimiento => $r['modelo'],
            array_filter($this->registros, static fn(array $r): bool => $r['activo'])
        ));
    }
    public function obtenerPorId(string $id): ?AreaConocimiento
    {
        return ($this->registros[$id]['activo'] ?? false)
            ? $this->registros[$id]['modelo']
            : null;
    }
    public function crear(AreaConocimiento $registro): int
    {
        $id = $registro->getId();
        if (array_key_exists($id, $this->registros)) {
            throw new ConflictoExcepcion('Llave ya ocupada.');
        }
        $this->registros[$id] = ['modelo' => $registro, 'activo' => true];
        return 1;
    }
    public function reemplazar(string $id, array $datos): int
    {
        $registro = $this->obtenerPorId($id);
        if ($registro === null) {
            return 0;
        }
        $registro->setGranArea($datos['gran_area']);
        $registro->setArea($datos['area']);
        $registro->setDisciplina($datos['disciplina']);
        return 1;
    }
    public function actualizar(string $id, array $datos): int
    {
        $registro = $this->obtenerPorId($id);
        if ($registro === null) {
            return 0;
        }
        if (array_key_exists('gran_area', $datos)) {
            $registro->setGranArea($datos['gran_area']);
        }
        if (array_key_exists('area', $datos)) {
            $registro->setArea($datos['area']);
        }
        if (array_key_exists('disciplina', $datos)) {
            $registro->setDisciplina($datos['disciplina']);
        }
        return 1;
    }
    public function retirar(string $id): int
    {
        if ($this->obtenerPorId($id) === null) {
            return 0;
        }
        $this->registros[$id]['activo'] = false;
        return 1;
    }
}

$falsoarea_conocimiento = new FalsoAreaConocimiento();
$servicioarea_conocimiento = new ServicioAreaConocimiento($falsoarea_conocimiento);
$idarea_conocimiento = 'ZZ9001';

probarR('area_conocimiento: comienza vacío', function () use ($servicioarea_conocimiento): void {
    verificarR($servicioarea_conocimiento->listar() === [], 'Listado no comienza vacío.');
});
probarR('area_conocimiento: crear', function () use ($servicioarea_conocimiento, $idarea_conocimiento): void {
    $creado = $servicioarea_conocimiento->crear(new AreaConocimiento('ZZ9001', 'Area temporal', 'Subarea temporal', 'Disciplina temporal'));
    verificarR($creado->getId() === $idarea_conocimiento, 'ID creado incorrecto.');
});
probarR('area_conocimiento: listar y consultar', function () use ($servicioarea_conocimiento, $idarea_conocimiento): void {
    verificarR(count($servicioarea_conocimiento->listar()) === 1, 'Lista no contiene un registro.');
    verificarR($servicioarea_conocimiento->obtenerPorId($idarea_conocimiento)->getId() === $idarea_conocimiento, 'GET falló.');
});
probarR('area_conocimiento: registro inexistente', function () use ($servicioarea_conocimiento): void {
    esperarR(NoEncontradoExcepcion::class, fn() => $servicioarea_conocimiento->obtenerPorId('XX999'));
});
probarR('area_conocimiento: duplicado', function () use ($servicioarea_conocimiento): void {
    esperarR(ConflictoExcepcion::class, fn() => $servicioarea_conocimiento->crear(new AreaConocimiento('ZZ9001', 'Area temporal', 'Subarea temporal', 'Disciplina temporal')));
});
probarR('area_conocimiento: PUT y PUT sin cambios', function () use ($servicioarea_conocimiento, $idarea_conocimiento): void {
    $datos = ['gran_area' => 'Area temporal editado', 'area' => 'Subarea temporal editado', 'disciplina' => 'Disciplina temporal editado'];
    verificarR($servicioarea_conocimiento->reemplazar($idarea_conocimiento, $datos) === 1, 'PUT no actualizó.');
    verificarR($servicioarea_conocimiento->obtenerPorId($idarea_conocimiento)->getGranArea() === $datos['gran_area'], 'PUT datos incorrectos.');
    verificarR($servicioarea_conocimiento->reemplazar($idarea_conocimiento, $datos) === 1, 'PUT sin cambios debe ser válido.');
});
probarR('area_conocimiento: PATCH parcial', function () use ($servicioarea_conocimiento, $idarea_conocimiento): void {
    verificarR($servicioarea_conocimiento->actualizar($idarea_conocimiento, ['gran_area' => 'PARCIAL']) === 1, 'PATCH no actualizó.');
    verificarR($servicioarea_conocimiento->obtenerPorId($idarea_conocimiento)->getGranArea() === 'PARCIAL', 'PATCH datos incorrectos.');
});
probarR('area_conocimiento: PATCH vacío', function () use ($servicioarea_conocimiento, $idarea_conocimiento): void {
    esperarR(InvalidArgumentException::class, fn() => $servicioarea_conocimiento->actualizar($idarea_conocimiento, []));
});
probarR('area_conocimiento: PUT y PATCH inexistente', function () use ($servicioarea_conocimiento): void {
    $id = 'XX999';
    esperarR(NoEncontradoExcepcion::class, fn() => $servicioarea_conocimiento->reemplazar($id, ['gran_area' => 'Area temporal editado', 'area' => 'Subarea temporal editado', 'disciplina' => 'Disciplina temporal editado']));
    esperarR(NoEncontradoExcepcion::class, fn() => $servicioarea_conocimiento->actualizar($id, ['gran_area' => 'X']));
});
probarR('area_conocimiento: retiro y segundo retiro', function () use ($servicioarea_conocimiento, $idarea_conocimiento): void {
    verificarR($servicioarea_conocimiento->retirar($idarea_conocimiento) === 1, 'Retiro fallido.');
    verificarR($servicioarea_conocimiento->listar() === [], 'Retirado en listado.');
    esperarR(NoEncontradoExcepcion::class, fn() => $servicioarea_conocimiento->obtenerPorId($idarea_conocimiento));
    esperarR(NoEncontradoExcepcion::class, fn() => $servicioarea_conocimiento->retirar($idarea_conocimiento));
    esperarR(ConflictoExcepcion::class, fn() => $servicioarea_conocimiento->crear(new AreaConocimiento('ZZ9001', 'Area temporal', 'Subarea temporal', 'Disciplina temporal')));
});

class FalsoObjetivoDesarrolloSostenible implements IRepositorioObjetivoDesarrolloSostenible
{
    private array $registros = [];
    public function listar(): array
    {
        return array_values(array_map(
            static fn(array $r): ObjetivoDesarrolloSostenible => $r['modelo'],
            array_filter($this->registros, static fn(array $r): bool => $r['activo'])
        ));
    }
    public function obtenerPorId(int $id): ?ObjetivoDesarrolloSostenible
    {
        return ($this->registros[$id]['activo'] ?? false)
            ? $this->registros[$id]['modelo']
            : null;
    }
    public function crear(ObjetivoDesarrolloSostenible $registro): int
    {
        $id = $registro->getId();
        if (array_key_exists($id, $this->registros)) {
            throw new ConflictoExcepcion('Llave ya ocupada.');
        }
        $this->registros[$id] = ['modelo' => $registro, 'activo' => true];
        return 1;
    }
    public function reemplazar(int $id, array $datos): int
    {
        $registro = $this->obtenerPorId($id);
        if ($registro === null) {
            return 0;
        }
        $registro->setNombre($datos['nombre']);
        $registro->setCategoria($datos['categoria']);
        return 1;
    }
    public function actualizar(int $id, array $datos): int
    {
        $registro = $this->obtenerPorId($id);
        if ($registro === null) {
            return 0;
        }
        if (array_key_exists('nombre', $datos)) {
            $registro->setNombre($datos['nombre']);
        }
        if (array_key_exists('categoria', $datos)) {
            $registro->setCategoria($datos['categoria']);
        }
        return 1;
    }
    public function retirar(int $id): int
    {
        if ($this->obtenerPorId($id) === null) {
            return 0;
        }
        $this->registros[$id]['activo'] = false;
        return 1;
    }
}

$falsoobjetivo_desarrollo_sostenible = new FalsoObjetivoDesarrolloSostenible();
$servicioobjetivo_desarrollo_sostenible = new ServicioObjetivoDesarrolloSostenible($falsoobjetivo_desarrollo_sostenible);
$idobjetivo_desarrollo_sostenible = 900001;

probarR('objetivo_desarrollo_sostenible: comienza vacío', function () use ($servicioobjetivo_desarrollo_sostenible): void {
    verificarR($servicioobjetivo_desarrollo_sostenible->listar() === [], 'Listado no comienza vacío.');
});
probarR('objetivo_desarrollo_sostenible: crear', function () use ($servicioobjetivo_desarrollo_sostenible, $idobjetivo_desarrollo_sostenible): void {
    $creado = $servicioobjetivo_desarrollo_sostenible->crear(new ObjetivoDesarrolloSostenible(900001, 'ODS de prueba', 'Social'));
    verificarR($creado->getId() === $idobjetivo_desarrollo_sostenible, 'ID creado incorrecto.');
});
probarR('objetivo_desarrollo_sostenible: listar y consultar', function () use ($servicioobjetivo_desarrollo_sostenible, $idobjetivo_desarrollo_sostenible): void {
    verificarR(count($servicioobjetivo_desarrollo_sostenible->listar()) === 1, 'Lista no contiene un registro.');
    verificarR($servicioobjetivo_desarrollo_sostenible->obtenerPorId($idobjetivo_desarrollo_sostenible)->getId() === $idobjetivo_desarrollo_sostenible, 'GET falló.');
});
probarR('objetivo_desarrollo_sostenible: registro inexistente', function () use ($servicioobjetivo_desarrollo_sostenible): void {
    esperarR(NoEncontradoExcepcion::class, fn() => $servicioobjetivo_desarrollo_sostenible->obtenerPorId(999999));
});
probarR('objetivo_desarrollo_sostenible: duplicado', function () use ($servicioobjetivo_desarrollo_sostenible): void {
    esperarR(ConflictoExcepcion::class, fn() => $servicioobjetivo_desarrollo_sostenible->crear(new ObjetivoDesarrolloSostenible(900001, 'ODS de prueba', 'Social')));
});
probarR('objetivo_desarrollo_sostenible: PUT y PUT sin cambios', function () use ($servicioobjetivo_desarrollo_sostenible, $idobjetivo_desarrollo_sostenible): void {
    $datos = ['nombre' => 'ODS de prueba editado', 'categoria' => 'Social editado'];
    verificarR($servicioobjetivo_desarrollo_sostenible->reemplazar($idobjetivo_desarrollo_sostenible, $datos) === 1, 'PUT no actualizó.');
    verificarR($servicioobjetivo_desarrollo_sostenible->obtenerPorId($idobjetivo_desarrollo_sostenible)->getNombre() === $datos['nombre'], 'PUT datos incorrectos.');
    verificarR($servicioobjetivo_desarrollo_sostenible->reemplazar($idobjetivo_desarrollo_sostenible, $datos) === 1, 'PUT sin cambios debe ser válido.');
});
probarR('objetivo_desarrollo_sostenible: PATCH parcial', function () use ($servicioobjetivo_desarrollo_sostenible, $idobjetivo_desarrollo_sostenible): void {
    verificarR($servicioobjetivo_desarrollo_sostenible->actualizar($idobjetivo_desarrollo_sostenible, ['nombre' => 'PARCIAL']) === 1, 'PATCH no actualizó.');
    verificarR($servicioobjetivo_desarrollo_sostenible->obtenerPorId($idobjetivo_desarrollo_sostenible)->getNombre() === 'PARCIAL', 'PATCH datos incorrectos.');
});
probarR('objetivo_desarrollo_sostenible: PATCH vacío', function () use ($servicioobjetivo_desarrollo_sostenible, $idobjetivo_desarrollo_sostenible): void {
    esperarR(InvalidArgumentException::class, fn() => $servicioobjetivo_desarrollo_sostenible->actualizar($idobjetivo_desarrollo_sostenible, []));
});
probarR('objetivo_desarrollo_sostenible: PUT y PATCH inexistente', function () use ($servicioobjetivo_desarrollo_sostenible): void {
    $id = 999999;
    esperarR(NoEncontradoExcepcion::class, fn() => $servicioobjetivo_desarrollo_sostenible->reemplazar($id, ['nombre' => 'ODS de prueba editado', 'categoria' => 'Social editado']));
    esperarR(NoEncontradoExcepcion::class, fn() => $servicioobjetivo_desarrollo_sostenible->actualizar($id, ['nombre' => 'X']));
});
probarR('objetivo_desarrollo_sostenible: retiro y segundo retiro', function () use ($servicioobjetivo_desarrollo_sostenible, $idobjetivo_desarrollo_sostenible): void {
    verificarR($servicioobjetivo_desarrollo_sostenible->retirar($idobjetivo_desarrollo_sostenible) === 1, 'Retiro fallido.');
    verificarR($servicioobjetivo_desarrollo_sostenible->listar() === [], 'Retirado en listado.');
    esperarR(NoEncontradoExcepcion::class, fn() => $servicioobjetivo_desarrollo_sostenible->obtenerPorId($idobjetivo_desarrollo_sostenible));
    esperarR(NoEncontradoExcepcion::class, fn() => $servicioobjetivo_desarrollo_sostenible->retirar($idobjetivo_desarrollo_sostenible));
    esperarR(ConflictoExcepcion::class, fn() => $servicioobjetivo_desarrollo_sostenible->crear(new ObjetivoDesarrolloSostenible(900001, 'ODS de prueba', 'Social')));
});

class FalsoAreaAplicacion implements IRepositorioAreaAplicacion
{
    private array $registros = [];
    public function listar(): array
    {
        return array_values(array_map(
            static fn(array $r): AreaAplicacion => $r['modelo'],
            array_filter($this->registros, static fn(array $r): bool => $r['activo'])
        ));
    }
    public function obtenerPorId(int $id): ?AreaAplicacion
    {
        return ($this->registros[$id]['activo'] ?? false)
            ? $this->registros[$id]['modelo']
            : null;
    }
    public function crear(AreaAplicacion $registro): int
    {
        $id = $registro->getId();
        if (array_key_exists($id, $this->registros)) {
            throw new ConflictoExcepcion('Llave ya ocupada.');
        }
        $this->registros[$id] = ['modelo' => $registro, 'activo' => true];
        return 1;
    }
    public function reemplazar(int $id, array $datos): int
    {
        $registro = $this->obtenerPorId($id);
        if ($registro === null) {
            return 0;
        }
        $registro->setNombre($datos['nombre']);
        return 1;
    }
    public function actualizar(int $id, array $datos): int
    {
        $registro = $this->obtenerPorId($id);
        if ($registro === null) {
            return 0;
        }
        if (array_key_exists('nombre', $datos)) {
            $registro->setNombre($datos['nombre']);
        }
        return 1;
    }
    public function retirar(int $id): int
    {
        if ($this->obtenerPorId($id) === null) {
            return 0;
        }
        $this->registros[$id]['activo'] = false;
        return 1;
    }
}

$falsoarea_aplicacion = new FalsoAreaAplicacion();
$servicioarea_aplicacion = new ServicioAreaAplicacion($falsoarea_aplicacion);
$idarea_aplicacion = 900001;

probarR('area_aplicacion: comienza vacío', function () use ($servicioarea_aplicacion): void {
    verificarR($servicioarea_aplicacion->listar() === [], 'Listado no comienza vacío.');
});
probarR('area_aplicacion: crear', function () use ($servicioarea_aplicacion, $idarea_aplicacion): void {
    $creado = $servicioarea_aplicacion->crear(new AreaAplicacion(900001, 'Área de aplicación de prueba'));
    verificarR($creado->getId() === $idarea_aplicacion, 'ID creado incorrecto.');
});
probarR('area_aplicacion: listar y consultar', function () use ($servicioarea_aplicacion, $idarea_aplicacion): void {
    verificarR(count($servicioarea_aplicacion->listar()) === 1, 'Lista no contiene un registro.');
    verificarR($servicioarea_aplicacion->obtenerPorId($idarea_aplicacion)->getId() === $idarea_aplicacion, 'GET falló.');
});
probarR('area_aplicacion: registro inexistente', function () use ($servicioarea_aplicacion): void {
    esperarR(NoEncontradoExcepcion::class, fn() => $servicioarea_aplicacion->obtenerPorId(999999));
});
probarR('area_aplicacion: duplicado', function () use ($servicioarea_aplicacion): void {
    esperarR(ConflictoExcepcion::class, fn() => $servicioarea_aplicacion->crear(new AreaAplicacion(900001, 'Área de aplicación de prueba')));
});
probarR('area_aplicacion: PUT y PUT sin cambios', function () use ($servicioarea_aplicacion, $idarea_aplicacion): void {
    $datos = ['nombre' => 'Área de aplicación de prueba editado'];
    verificarR($servicioarea_aplicacion->reemplazar($idarea_aplicacion, $datos) === 1, 'PUT no actualizó.');
    verificarR($servicioarea_aplicacion->obtenerPorId($idarea_aplicacion)->getNombre() === $datos['nombre'], 'PUT datos incorrectos.');
    verificarR($servicioarea_aplicacion->reemplazar($idarea_aplicacion, $datos) === 1, 'PUT sin cambios debe ser válido.');
});
probarR('area_aplicacion: PATCH parcial', function () use ($servicioarea_aplicacion, $idarea_aplicacion): void {
    verificarR($servicioarea_aplicacion->actualizar($idarea_aplicacion, ['nombre' => 'PARCIAL']) === 1, 'PATCH no actualizó.');
    verificarR($servicioarea_aplicacion->obtenerPorId($idarea_aplicacion)->getNombre() === 'PARCIAL', 'PATCH datos incorrectos.');
});
probarR('area_aplicacion: PATCH vacío', function () use ($servicioarea_aplicacion, $idarea_aplicacion): void {
    esperarR(InvalidArgumentException::class, fn() => $servicioarea_aplicacion->actualizar($idarea_aplicacion, []));
});
probarR('area_aplicacion: PUT y PATCH inexistente', function () use ($servicioarea_aplicacion): void {
    $id = 999999;
    esperarR(NoEncontradoExcepcion::class, fn() => $servicioarea_aplicacion->reemplazar($id, ['nombre' => 'Área de aplicación de prueba editado']));
    esperarR(NoEncontradoExcepcion::class, fn() => $servicioarea_aplicacion->actualizar($id, ['nombre' => 'X']));
});
probarR('area_aplicacion: retiro y segundo retiro', function () use ($servicioarea_aplicacion, $idarea_aplicacion): void {
    verificarR($servicioarea_aplicacion->retirar($idarea_aplicacion) === 1, 'Retiro fallido.');
    verificarR($servicioarea_aplicacion->listar() === [], 'Retirado en listado.');
    esperarR(NoEncontradoExcepcion::class, fn() => $servicioarea_aplicacion->obtenerPorId($idarea_aplicacion));
    esperarR(NoEncontradoExcepcion::class, fn() => $servicioarea_aplicacion->retirar($idarea_aplicacion));
    esperarR(ConflictoExcepcion::class, fn() => $servicioarea_aplicacion->crear(new AreaAplicacion(900001, 'Área de aplicación de prueba')));
});

echo PHP_EOL;
if ($fallos !== 0) {
    echo "[ERROR] {$fallos} pruebas fallidas." . PHP_EOL;
    exit(1);
}
echo '[OK] Todas las pruebas de los tres recursos restantes pasaron sin MariaDB.' . PHP_EOL;
exit(0);
