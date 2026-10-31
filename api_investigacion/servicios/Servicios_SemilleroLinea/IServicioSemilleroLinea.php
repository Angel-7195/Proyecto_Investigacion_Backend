<?php

declare(strict_types=1);

require_once __DIR__ . '/../../modelos/SemilleroLinea.php';

/** Contrato de negocio de las relaciones entre semilleros y líneas. */
interface IServicioSemilleroLinea
{
    /** @return SemilleroLinea[] */
    public function listar(): array;

    public function obtenerPorClave(int $semilleroId, int $lineaInvestigacion): SemilleroLinea;

    public function crear(SemilleroLinea $asociacion): SemilleroLinea;

    public function retirar(int $semilleroId, int $lineaInvestigacion): int;
}
