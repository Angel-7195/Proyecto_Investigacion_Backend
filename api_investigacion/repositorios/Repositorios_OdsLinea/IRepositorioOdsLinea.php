<?php

declare(strict_types=1);

require_once __DIR__ . '/../../modelos/OdsLinea.php';

/** Contrato de persistencia de ods_linea (clave primaria compuesta). */
interface IRepositorioOdsLinea
{
    /** @return OdsLinea[] */
    public function obtenerTodos(): array;

    public function obtenerPorClave(int $lineaInvestigacion, int $ods): ?OdsLinea;

    public function crear(OdsLinea $asociacion): int;

    public function retirar(int $lineaInvestigacion, int $ods): int;
}
