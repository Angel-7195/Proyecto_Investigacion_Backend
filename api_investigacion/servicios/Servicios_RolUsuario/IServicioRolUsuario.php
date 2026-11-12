<?php

declare(strict_types=1);

interface IServicioRolUsuario
{
    public function listar(): array;
    public function obtener(int $usuarioId, int $rolId): array;
    public function crear(array $datos): array;
    public function retirar(int $usuarioId, int $rolId): void;
}
