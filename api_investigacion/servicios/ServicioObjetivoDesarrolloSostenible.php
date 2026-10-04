<?php

declare(strict_types=1);

require_once __DIR__ . '/IServicioObjetivoDesarrolloSostenible.php';
require_once __DIR__ . '/../repositorios/IRepositorioObjetivoDesarrolloSostenible.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

class ServicioObjetivoDesarrolloSostenible implements IServicioObjetivoDesarrolloSostenible
{
    private IRepositorioObjetivoDesarrolloSostenible $repositorio;

    public function __construct(IRepositorioObjetivoDesarrolloSostenible $repositorio)
    {
        $this->repositorio = $repositorio;
    }

    public function listar(): array
    {
        return $this->repositorio->listar();
    }

    public function obtenerPorId(int $id): ObjetivoDesarrolloSostenible
    {
        $registro = $this->repositorio->obtenerPorId($id);
        if ($registro === null) {
            throw new NoEncontradoExcepcion('No existe un objetivo activo con el id solicitado.');
        }
        return $registro;
    }

    public function crear(ObjetivoDesarrolloSostenible $registro): ObjetivoDesarrolloSostenible
    {
        $this->repositorio->crear($registro);
        return $registro;
    }

    public function reemplazar(int $id, array $datos): int
    {
        $filas = $this->repositorio->reemplazar($id, $datos);
        if ($filas === 0) {
            throw new NoEncontradoExcepcion('No existe un objetivo activo con el id solicitado.');
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
            throw new NoEncontradoExcepcion('No existe un objetivo activo con el id solicitado.');
        }
        return $filas;
    }

    public function retirar(int $id): int
    {
        $filas = $this->repositorio->retirar($id);
        if ($filas === 0) {
            throw new NoEncontradoExcepcion('No existe un objetivo activo con el id solicitado.');
        }
        return $filas;
    }
}
