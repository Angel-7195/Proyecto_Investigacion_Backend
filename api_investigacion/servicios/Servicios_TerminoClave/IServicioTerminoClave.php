<?php

declare(strict_types=1);

require_once __DIR__ . '/../../modelos/TerminoClave.php';

interface IServicioTerminoClave
{
    /**
     * @return TerminoClave[]
     */
    public function listar(): array;

    public function obtenerPorTermino(string $termino): TerminoClave;

    public function crear(TerminoClave $terminoClave): TerminoClave;

    public function reemplazar(
        string $termino,
        ?string $terminoIngles
    ): int;

    public function actualizar(
        string $termino,
        array $datos
    ): int;

    public function retirar(string $termino): int;
}