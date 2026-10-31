<?php

declare(strict_types=1);

require_once __DIR__ . '/../../modelos/AaLinea.php';

/** Contrato de negocio de las relaciones entre áreas de aplicación y líneas. */
interface IServicioAaLinea
{
    /** @return AaLinea[] */
    public function listar(): array;

    public function obtenerPorClave(int $areaAplicacion, int $lineaInvestigacion): AaLinea;

    public function crear(AaLinea $asociacion): AaLinea;

    public function retirar(int $areaAplicacion, int $lineaInvestigacion): int;
}
