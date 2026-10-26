<?php

declare(strict_types=1);

require_once __DIR__ . '/IServicioTerminoClave.php';
require_once __DIR__ . '/../../repositorios/Repositorios_TerminoClave/IRepositorioTerminoClave.php';
require_once __DIR__ . '/../../excepciones/NoEncontradoExcepcion.php';

class ServicioTerminoClave implements IServicioTerminoClave
{
    private IRepositorioTerminoClave $repositorio;

    public function __construct(IRepositorioTerminoClave $repositorio)
    {
        $this->repositorio = $repositorio;
    }

    /**
     * @return TerminoClave[]
     */
    public function listar(): array
    {
        return $this->repositorio->obtenerTodos();
    }

    public function obtenerPorTermino(string $termino): TerminoClave
    {
        $terminoClave = $this->repositorio->obtenerPorTermino($termino);

        if ($terminoClave === null) {
            throw new NoEncontradoExcepcion(
                'Término clave no encontrado.'
            );
        }

        return $terminoClave;
    }

    public function crear(TerminoClave $terminoClave): TerminoClave
    {
        $this->repositorio->crear($terminoClave);

        return $terminoClave;
    }

    public function reemplazar(
        string $termino,
        ?string $terminoIngles
    ): int {
        $filasAfectadas = $this->repositorio->reemplazar(
            $termino,
            $terminoIngles
        );

        if ($filasAfectadas === 0) {
            throw new NoEncontradoExcepcion(
                'Término clave no encontrado.'
            );
        }

        return $filasAfectadas;
    }

    public function actualizar(
        string $termino,
        array $datos
    ): int {
        if ($datos === []) {
            throw new InvalidArgumentException(
                'No se envió ningún campo para actualizar.'
            );
        }

        $filasAfectadas = $this->repositorio->actualizar(
            $termino,
            $datos
        );

        if ($filasAfectadas === 0) {
            throw new NoEncontradoExcepcion(
                'Término clave no encontrado.'
            );
        }

        return $filasAfectadas;
    }

    public function retirar(string $termino): int
    {
        $filasAfectadas = $this->repositorio->retirar($termino);

        if ($filasAfectadas === 0) {
            throw new NoEncontradoExcepcion(
                'Término clave no encontrado.'
            );
        }

        return $filasAfectadas;
    }
}