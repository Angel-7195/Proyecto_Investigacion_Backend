<?php

declare(strict_types=1);

require_once __DIR__ . '/../../modelos/Semillero.php';

/** Contrato de persistencia para la tabla semillero. */
interface IRepositorioSemillero
{
    /** @return Semillero[] */
    public function obtenerTodos(): array;

    public function obtenerPorId(int $id): ?Semillero;

    public function crear(Semillero $semillero): int;

    /** @param array<string, mixed> $datos */
    public function reemplazar(int $id, array $datos): int;

    /** @param array<string, mixed> $datos */
    public function actualizar(int $id, array $datos): int;

    public function retirar(int $id): int;
}
