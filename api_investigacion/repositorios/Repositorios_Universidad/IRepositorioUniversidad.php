<?php

declare(strict_types=1);

require_once __DIR__ . '/../../modelos/Universidad.php';

interface IRepositorioUniversidad
{
    /**
     * @return Universidad[]
     */
    public function obtenerTodos(): array;

    public function obtenerPorId(int $id): ?Universidad;

    public function crear(Universidad $universidad): int;

    public function reemplazar(
        int $id,
        array $datos
    ): int;

    public function actualizar(
        int $id,
        array $datos
    ): int;

    public function retirar(int $id): int;
}