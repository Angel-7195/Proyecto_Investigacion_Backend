<?php

declare(strict_types=1);

require_once __DIR__ . '/../../modelos/ParticipaGrupo.php';

/** Contrato de persistencia de participa_grupo (clave primaria compuesta). */
interface IRepositorioParticipaGrupo
{
    /** @return ParticipaGrupo[] */
    public function obtenerTodos(): array;

    public function obtenerPorClave(int $docenteCedula, int $grupoId): ?ParticipaGrupo;

    public function crear(ParticipaGrupo $participacion): int;

    /** @param array<string, mixed> $datos */
    public function reemplazar(int $docenteCedula, int $grupoId, array $datos): int;

    /** @param array<string, mixed> $datos */
    public function actualizar(int $docenteCedula, int $grupoId, array $datos): int;

    public function retirar(int $docenteCedula, int $grupoId): int;
}
