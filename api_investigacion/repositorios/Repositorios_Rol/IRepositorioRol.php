<?php

declare(strict_types=1);

interface IRepositorioRol
{
    /** @return list<Rol> */
    public function listar(): array;
    public function obtener(int $id): ?Rol;
    public function crear(string $nombre, ?string $descripcion): Rol;

    /** @param array{nombre?: string, descripcion?: ?string} $cambios */
    public function actualizar(int $id, array $cambios): ?Rol;

    public function retirar(int $id): bool;
}
