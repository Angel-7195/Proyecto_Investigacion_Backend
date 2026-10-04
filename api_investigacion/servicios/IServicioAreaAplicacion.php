<?php

declare(strict_types=1);

require_once __DIR__ . '/../modelos/AreaAplicacion.php';

interface IServicioAreaAplicacion
{
    public function listar(): array;
    public function obtenerPorId(int $id): AreaAplicacion;
    public function crear(AreaAplicacion $registro): AreaAplicacion;
    public function reemplazar(int $id, array $datos): int;
    public function actualizar(int $id, array $datos): int;
    public function retirar(int $id): int;
}
