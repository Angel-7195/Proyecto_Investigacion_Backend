<?php

declare(strict_types=1);

require_once __DIR__ . '/../../modelos/OdsLinea.php';

/** Contrato de negocio de las asociaciones entre líneas de investigación y ODS. */
interface IServicioOdsLinea
{
    /** @return OdsLinea[] */
    public function listar(): array;

    public function obtenerPorClave(int $lineaInvestigacion, int $ods): OdsLinea;

    public function crear(OdsLinea $asociacion): OdsLinea;

    public function retirar(int $lineaInvestigacion, int $ods): int;
}
