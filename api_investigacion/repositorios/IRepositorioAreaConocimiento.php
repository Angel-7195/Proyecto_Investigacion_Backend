<?php

declare(strict_types=1);

require_once __DIR__ . '/../modelos/AreaConocimiento.php';

interface IRepositorioAreaConocimiento
{
    public function listar(): array;
    public function obtenerPorId(string $id): ?AreaConocimiento;
    public function crear(AreaConocimiento $registro): int;
    public function reemplazar(string $id, array $datos): int;
    public function actualizar(string $id, array $datos): int;
    public function retirar(string $id): int;
}
