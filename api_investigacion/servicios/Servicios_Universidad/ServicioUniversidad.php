<?php

declare(strict_types=1);

require_once __DIR__ . '/IServicioUniversidad.php';
require_once __DIR__ . '/../../repositorios/Repositorios_Universidad/IRepositorioUniversidad.php';
require_once __DIR__ . '/../../excepciones/NoEncontradoExcepcion.php';

class ServicioUniversidad implements IServicioUniversidad
{
    private IRepositorioUniversidad $repositorio;

    public function __construct(IRepositorioUniversidad $repositorio)
    {
        $this->repositorio = $repositorio;
    }

    /**
     * @return Universidad[]
     */
    public function listar(): array
    {
        return $this->repositorio->obtenerTodos();
    }

    public function obtenerPorId(int $id): Universidad
    {
        $universidad = $this->repositorio->obtenerPorId($id);

        if ($universidad === null) {
            throw new NoEncontradoExcepcion(
                'No existe una universidad activa con el id solicitado.'
            );
        }

        return $universidad;
    }

    public function crear(Universidad $universidad): Universidad
    {
        $this->repositorio->crear($universidad);

        return $universidad;
    }

    public function reemplazar(
        int $id,
        array $datos
    ): int {
        $filasAfectadas = $this->repositorio->reemplazar(
            $id,
            $datos
        );

        if ($filasAfectadas === 0) {
            throw new NoEncontradoExcepcion(
                'No existe una universidad activa con el id solicitado.'
            );
        }

        return $filasAfectadas;
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

        $filasAfectadas = $this->repositorio->actualizar(
            $id,
            $datos
        );

        if ($filasAfectadas === 0) {
            throw new NoEncontradoExcepcion(
                'No existe una universidad activa con el id solicitado.'
            );
        }

        return $filasAfectadas;
    }

    public function retirar(int $id): int
    {
        $filasAfectadas = $this->repositorio->retirar($id);

        if ($filasAfectadas === 0) {
            throw new NoEncontradoExcepcion(
                'No existe una universidad activa con el id solicitado.'
            );
        }

        return $filasAfectadas;
    }
}