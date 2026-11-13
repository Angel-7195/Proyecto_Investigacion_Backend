<?php

declare(strict_types=1);

interface IServicioUsuario
{
    public function listar(): array;
    public function obtener(int $id): array;
    public function crear(array $cuerpo): array;
    public function reemplazar(int $id, array $cuerpo): array;
    public function actualizar(int $id, array $cuerpo): array;
    public function retirar(int $id): void;
    public function cambiarContrasena(int $id, array $cuerpo): void;
}
