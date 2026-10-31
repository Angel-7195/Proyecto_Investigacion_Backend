<?php

declare(strict_types=1);

require_once __DIR__ . '/../../modelos/AcLinea.php';

/** Contrato de persistencia de ac_linea (clave primaria compuesta). */
interface IRepositorioAcLinea
{
    /** @return AcLinea[] */
    public function obtenerTodos(): array;

    public function obtenerPorClave(int $lineaInvestigacion, string $areaConocimiento): ?AcLinea;

    public function crear(AcLinea $asociacion): int;

    public function retirar(int $lineaInvestigacion, string $areaConocimiento): int;
}
