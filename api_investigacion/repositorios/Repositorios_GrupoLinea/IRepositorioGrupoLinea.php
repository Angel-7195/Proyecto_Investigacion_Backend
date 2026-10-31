<?php

declare(strict_types=1);

require_once __DIR__ . '/../../modelos/GrupoLinea.php';

/** Contrato de persistencia de grupo_linea, con clave primaria compuesta. */
interface IRepositorioGrupoLinea
{
    /** @return GrupoLinea[] */
    public function obtenerTodos(): array;

    public function obtenerPorClave(int $grupoInvestigacion, int $lineaInvestigacion): ?GrupoLinea;

    public function crear(GrupoLinea $asociacion): int;

    public function retirar(int $grupoInvestigacion, int $lineaInvestigacion): int;
}
