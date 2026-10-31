<?php

declare(strict_types=1);

require_once __DIR__ . '/../../modelos/GrupoLinea.php';

/** Contrato de negocio de las relaciones entre grupos de investigación y líneas. */
interface IServicioGrupoLinea
{
    /** @return GrupoLinea[] */
    public function listar(): array;

    public function obtenerPorClave(int $grupoInvestigacion, int $lineaInvestigacion): GrupoLinea;

    public function crear(GrupoLinea $asociacion): GrupoLinea;

    public function retirar(int $grupoInvestigacion, int $lineaInvestigacion): int;
}
