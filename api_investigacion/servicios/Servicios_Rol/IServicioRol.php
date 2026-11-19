<?php

declare(strict_types=1);

interface IServicioRol
{
    public function listar(): array;
    public function obtener(int $id): array;
    public function crear(array $cuerpo): array;
    public function reemplazar(int $id, array $cuerpo): array;
    public function actualizar(int $id, array $cuerpo): array;
    public function retirar(int $id): void;
}
