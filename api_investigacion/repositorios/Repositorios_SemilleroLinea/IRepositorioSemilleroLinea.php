<?php

declare(strict_types=1);

require_once __DIR__ . '/../../modelos/SemilleroLinea.php';

/** Contrato de persistencia de semillero_linea, con clave primaria compuesta. */
interface IRepositorioSemilleroLinea
{
    /** @return SemilleroLinea[] */
    public function obtenerTodos(): array;

    public function obtenerPorClave(int $semilleroId, int $lineaInvestigacion): ?SemilleroLinea;

    public function crear(SemilleroLinea $asociacion): int;

    public function retirar(int $semilleroId, int $lineaInvestigacion): int;
}
