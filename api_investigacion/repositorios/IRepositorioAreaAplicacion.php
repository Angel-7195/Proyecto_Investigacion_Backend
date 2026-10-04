<?php

declare(strict_types=1);

require_once __DIR__ . '/../modelos/AreaAplicacion.php';

interface IRepositorioAreaAplicacion
{
    public function listar(): array;
    public function obtenerPorId(int $id): ?AreaAplicacion;
    public function crear(AreaAplicacion $registro): int;
    public function reemplazar(int $id, array $datos): int;
    public function actualizar(int $id, array $datos): int;
    public function retirar(int $id): int;
}
