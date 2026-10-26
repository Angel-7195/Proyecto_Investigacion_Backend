<?php

declare(strict_types=1);

require_once __DIR__ . '/IServicioLineaInvestigacion.php';
require_once __DIR__ . '/../../repositorios/Repositorios_LineaInvestigacion/IRepositorioLineaInvestigacion.php';
require_once __DIR__ . '/../../excepciones/NoEncontradoExcepcion.php';

class ServicioLineaInvestigacion
    implements IServicioLineaInvestigacion
{
    private IRepositorioLineaInvestigacion $repositorio;

    public function __construct(
        IRepositorioLineaInvestigacion $repositorio
    ) {
        $this->repositorio = $repositorio;
    }

    public function listar(): array
    {
        return $this->repositorio->obtenerTodos();
    }

    public function obtenerPorId(
        int $id
    ): LineaInvestigacion {
        $linea = $this->repositorio->obtenerPorId($id);

        if ($linea === null) {
            throw new NoEncontradoExcepcion(
                'No existe una línea de investigación activa con el id solicitado.'
            );
        }

        return $linea;
    }

    public function crear(
        LineaInvestigacion $lineaInvestigacion
    ): LineaInvestigacion {
        $id = $this->repositorio->crear(
            $lineaInvestigacion
        );

        return new LineaInvestigacion(
            $id,
            $lineaInvestigacion->getNombre(),
            $lineaInvestigacion->getDescripcion()
        );
    }

    public function reemplazar(
        int $id,
        array $datos
    ): int {
        $filas = $this->repositorio->reemplazar(
            $id,
            $datos
        );

        if ($filas === 0) {
            throw new NoEncontradoExcepcion(
                'No existe una línea de investigación activa con el id solicitado.'
            );
        }

        return $filas;
    }

    public function actualizar(
        int $id,
        array $datos
    ): int {
        if ($datos === []) {
            throw new InvalidArgumentException(
                'No se envió ningún campo para actualizar.'
            );
        }

        $filas = $this->repositorio->actualizar(
            $id,
            $datos
        );

        if ($filas === 0) {
            throw new NoEncontradoExcepcion(
                'No existe una línea de investigación activa con el id solicitado.'
            );
        }

        return $filas;
    }

    public function retirar(int $id): int
    {
        $filas = $this->repositorio->retirar($id);

        if ($filas === 0) {
            throw new NoEncontradoExcepcion(
                'No existe una línea de investigación activa con el id solicitado.'
            );
        }

        return $filas;
    }
}