<?php

declare(strict_types=1);

require_once __DIR__ . '/../../modelos/Universidad.php';

interface IServicioUniversidad
{
    /**
     * @return Universidad[]
     */
    public function listar(): array;

    public function obtenerPorId(int $id): Universidad;

    public function crear(Universidad $universidad): Universidad;

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