<?php

declare(strict_types=1);

require_once __DIR__ . '/../../modelos/Docente.php';

interface IServicioDocente
{
    /** @return Docente[] */
    public function listar(): array;

    public function obtenerPorCedula(int $cedula): Docente;

    public function crear(Docente $docente): Docente;

    /** @param array<string, mixed> $datos */
    public function reemplazar(int $cedula, array $datos): int;

    /** @param array<string, mixed> $datos */
    public function actualizar(int $cedula, array $datos): int;

    public function retirar(int $cedula): int;
}
