<?php

declare(strict_types=1);

interface IServicioRol
{
    public function listar(): array;

    public function obtener(int $id): array;
}
