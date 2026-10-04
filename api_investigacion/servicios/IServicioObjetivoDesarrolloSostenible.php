<?php

declare(strict_types=1);

require_once __DIR__ . '/../modelos/ObjetivoDesarrolloSostenible.php';

interface IServicioObjetivoDesarrolloSostenible
{
    public function listar(): array;
    public function obtenerPorId(int $id): ObjetivoDesarrolloSostenible;
    public function crear(ObjetivoDesarrolloSostenible $registro): ObjetivoDesarrolloSostenible;
    public function reemplazar(int $id, array $datos): int;
    public function actualizar(int $id, array $datos): int;
    public function retirar(int $id): int;
}
