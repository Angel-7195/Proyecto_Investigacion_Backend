<?php

declare(strict_types=1);

require_once __DIR__ . '/../../modelos/LineaInvestigacion.php';

interface IRepositorioLineaInvestigacion
{
    /** @return LineaInvestigacion[] */
    public function obtenerTodos(): array;

    public function obtenerPorId(int $id): ?LineaInvestigacion;

    /**
     * Devuelve el id generado por AUTO_INCREMENT.
     */
    public function crear(
        LineaInvestigacion $lineaInvestigacion
    ): int;

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