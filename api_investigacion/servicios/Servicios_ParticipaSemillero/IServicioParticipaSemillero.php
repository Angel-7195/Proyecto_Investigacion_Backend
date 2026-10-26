<?php

declare(strict_types=1);

require_once __DIR__ . '/../../modelos/ParticipaSemillero.php';

/** Contrato de negocio para participaciones de docentes en semilleros. */
interface IServicioParticipaSemillero
{
    /** @return ParticipaSemillero[] */
    public function listar(): array;

    public function obtenerPorClave(int $docenteId, int $semilleroId): ParticipaSemillero;

    public function crear(ParticipaSemillero $participacion): ParticipaSemillero;

    /** @param array<string, mixed> $datos */
    public function reemplazar(int $docenteId, int $semilleroId, array $datos): int;

    /** @param array<string, mixed> $datos */
    public function actualizar(int $docenteId, int $semilleroId, array $datos): int;

    public function retirar(int $docenteId, int $semilleroId): int;
}
