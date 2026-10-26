<?php

declare(strict_types=1);

require_once __DIR__ . '/../../modelos/GrupoInvestigacion.php';

/**
 * Contrato de las operaciones de negocio para grupos de investigación.
 */
interface IServicioGrupoInvestigacion
{
    /** @return GrupoInvestigacion[] */
    public function listar(): array;

    public function obtenerPorId(int $id): GrupoInvestigacion;

    public function crear(GrupoInvestigacion $grupoInvestigacion): GrupoInvestigacion;

    /** @param array<string, mixed> $datos */
    public function reemplazar(int $id, array $datos): int;

    /** @param array<string, mixed> $datos */
    public function actualizar(int $id, array $datos): int;

    public function retirar(int $id): int;
}
