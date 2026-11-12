<?php

declare(strict_types=1);

interface IRepositorioRolUsuario
{
    /** @return RolUsuario[] */
    public function listar(): array;
    public function obtener(int $usuarioId, int $rolId): ?RolUsuario;
    public function crear(int $usuarioId, int $rolId): RolUsuario;
    public function retirar(int $usuarioId, int $rolId): bool;
}
