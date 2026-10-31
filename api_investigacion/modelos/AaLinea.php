<?php

declare(strict_types=1);

/** Asociación entre área de aplicación y línea de investigación. */
class AaLinea
{
    private int $area_aplicacion;
    private int $linea_investigacion;

    public function __construct(int $area_aplicacion, int $linea_investigacion)
    {
        $this->area_aplicacion = $area_aplicacion;
        $this->linea_investigacion = $linea_investigacion;
    }

    public function getAreaAplicacion(): int
    {
        return $this->area_aplicacion;
    }

    public function getLineaInvestigacion(): int
    {
        return $this->linea_investigacion;
    }

    /** @return array{area_aplicacion:int, linea_investigacion:int} */
    public function toArray(): array
    {
        return [
            'area_aplicacion' => $this->area_aplicacion,
            'linea_investigacion' => $this->linea_investigacion,
        ];
    }
}
