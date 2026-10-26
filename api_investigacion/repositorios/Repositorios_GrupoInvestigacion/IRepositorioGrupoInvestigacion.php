<?php

declare(strict_types=1);

require_once __DIR__ . '/../../modelos/GrupoInvestigacion.php';

/**
 * Contrato de persistencia para los grupos de investigación.
 * Las reglas de negocio pertenecen a la capa de servicios.
 */
interface IRepositorioGrupoInvestigacion
{
    /** @return GrupoInvestigacion[] */
    public function obtenerTodos(): array;

    public function obtenerPorId(int $id): ?GrupoInvestigacion;

    /** Devuelve el número de filas insertadas (id no es AUTO_INCREMENT). */
    public function crear(GrupoInvestigacion $grupoInvestigacion): int;

    /** Reemplaza todos los campos editables, sin cambiar id ni activo. */
    public function reemplazar(int $id, array $datos): int;

    /** Actualiza únicamente los campos editables recibidos. */
    public function actualizar(int $id, array $datos): int;

    /** Borrado lógico: marca la fila como inactiva. */
    public function retirar(int $id): int;
}
