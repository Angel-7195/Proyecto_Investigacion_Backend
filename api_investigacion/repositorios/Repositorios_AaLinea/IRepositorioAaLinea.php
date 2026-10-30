<?php

declare(strict_types=1);

require_once __DIR__ . '/../../modelos/AaLinea.php';

/** Contrato de persistencia de aa_linea, con clave primaria compuesta. */
interface IRepositorioAaLinea
{
    /** @return AaLinea[] */
    public function obtenerTodos(): array;

    public function obtenerPorClave(int $areaAplicacion, int $lineaInvestigacion): ?AaLinea;

    public function crear(AaLinea $asociacion): int;

    public function retirar(int $areaAplicacion, int $lineaInvestigacion): int;
}
