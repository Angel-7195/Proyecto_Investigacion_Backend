<?php

declare(strict_types=1);

require_once __DIR__ . '/IServicioAreaAplicacion.php';
require_once __DIR__ . '/../repositorios/IRepositorioAreaAplicacion.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

class ServicioAreaAplicacion implements IServicioAreaAplicacion
{
    private IRepositorioAreaAplicacion $repositorio;

    public function __construct(IRepositorioAreaAplicacion $repositorio)
    {
        $this->repositorio = $repositorio;
    }

    public function listar(): array
    {
        return $this->repositorio->listar();
    }

    public function obtenerPorId(int $id): AreaAplicacion
    {
        $registro = $this->repositorio->obtenerPorId($id);
        if ($registro === null) {
            throw new NoEncontradoExcepcion('No existe un área de aplicación activa con el id solicitado.');
        }
        return $registro;
    }

    public function crear(AreaAplicacion $registro): AreaAplicacion
    {
        $this->repositorio->crear($registro);
        return $registro;
    }

    public function reemplazar(int $id, array $datos): int
    {
        $filas = $this->repositorio->reemplazar($id, $datos);
        if ($filas === 0) {
            throw new NoEncontradoExcepcion('No existe un área de aplicación activa con el id solicitado.');
        }
        return $filas;
    }

    public function actualizar(int $id, array $datos): int
    {
        if ($datos === []) {
            throw new InvalidArgumentException(
                'No se envió ningún campo para actualizar.'
            );
        }
        $filas = $this->repositorio->actualizar($id, $datos);
        if ($filas === 0) {
            throw new NoEncontradoExcepcion('No existe un área de aplicación activa con el id solicitado.');
        }
        return $filas;
    }

    public function retirar(int $id): int
    {
        $filas = $this->repositorio->retirar($id);
        if ($filas === 0) {
            throw new NoEncontradoExcepcion('No existe un área de aplicación activa con el id solicitado.');
        }
        return $filas;
    }
}
