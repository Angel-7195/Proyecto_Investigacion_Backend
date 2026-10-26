<?php

declare(strict_types=1);

require_once __DIR__ . '/../../modelos/ParticipaSemillero.php';

/** Contrato de persistencia de participa_semillero (clave primaria compuesta). */
interface IRepositorioParticipaSemillero
{
    /** @return ParticipaSemillero[] */
    public function obtenerTodos(): array;

    public function obtenerPorClave(int $docenteId, int $semilleroId): ?ParticipaSemillero;

    public function crear(ParticipaSemillero $participacion): int;

    /** @param array<string, mixed> $datos */
    public function reemplazar(int $docenteId, int $semilleroId, array $datos): int;

    /** @param array<string, mixed> $datos */
    public function actualizar(int $docenteId, int $semilleroId, array $datos): int;

    public function retirar(int $docenteId, int $semilleroId): int;
}
