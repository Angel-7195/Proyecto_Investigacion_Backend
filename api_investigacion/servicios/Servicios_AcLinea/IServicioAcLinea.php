<?php

declare(strict_types=1);

require_once __DIR__ . '/../../modelos/AcLinea.php';

/** Contrato de negocio para relaciones de líneas y áreas de conocimiento. */
interface IServicioAcLinea
{
    /** @return AcLinea[] */
    public function listar(): array;

    public function obtenerPorClave(int $lineaInvestigacion, string $areaConocimiento): AcLinea;

    public function crear(AcLinea $asociacion): AcLinea;

    public function retirar(int $lineaInvestigacion, string $areaConocimiento): int;
}
