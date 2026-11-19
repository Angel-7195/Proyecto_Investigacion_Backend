<?php

declare(strict_types=1);

interface IRepositorioRol
{
    /** @return list<Rol> */
    public function listar(): array;

    public function obtener(int $id): ?Rol;
}
