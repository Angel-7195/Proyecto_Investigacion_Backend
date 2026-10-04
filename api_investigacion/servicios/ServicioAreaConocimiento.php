<?php

declare(strict_types=1);

require_once __DIR__ . '/IServicioAreaConocimiento.php';
require_once __DIR__ . '/../repositorios/IRepositorioAreaConocimiento.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

class ServicioAreaConocimiento implements IServicioAreaConocimiento
{
    private IRepositorioAreaConocimiento $repositorio;

    public function __construct(IRepositorioAreaConocimiento $repositorio)
    {
        $this->repositorio = $repositorio;
    }

    public function listar(): array
    {
        return $this->repositorio->listar();
    }

    public function obtenerPorId(string $id): AreaConocimiento
    {
        $registro = $this->repositorio->obtenerPorId($id);
        if ($registro === null) {
            throw new NoEncontradoExcepcion('No existe un área de conocimiento activa con el id solicitado.');
        }
        return $registro;
    }

    public function crear(AreaConocimiento $registro): AreaConocimiento
    {
        $this->repositorio->crear($registro);
        return $registro;
    }

    public function reemplazar(string $id, array $datos): int
    {
        $filas = $this->repositorio->reemplazar($id, $datos);
        if ($filas === 0) {
            throw new NoEncontradoExcepcion('No existe un área de conocimiento activa con el id solicitado.');
        }
        return $filas;
    }

    public function actualizar(string $id, array $datos): int
    {
        if ($datos === []) {
            throw new InvalidArgumentException(
                'No se envió ningún campo para actualizar.'
            );
        }
        $filas = $this->repositorio->actualizar($id, $datos);
        if ($filas === 0) {
            throw new NoEncontradoExcepcion('No existe un área de conocimiento activa con el id solicitado.');
        }
        return $filas;
    }

    public function retirar(string $id): int
    {
        $filas = $this->repositorio->retirar($id);
        if ($filas === 0) {
            throw new NoEncontradoExcepcion('No existe un área de conocimiento activa con el id solicitado.');
        }
        return $filas;
    }
}
