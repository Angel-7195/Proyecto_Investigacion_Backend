<?php

declare(strict_types=1);

/** Asociación entre una línea de investigación y un área de conocimiento. */
class AcLinea
{
    private int $linea_investigacion;
    private string $area_conocimiento;

    public function __construct(int $linea_investigacion, string $area_conocimiento)
    {
        $this->linea_investigacion = $linea_investigacion;
        $this->area_conocimiento = $area_conocimiento;
    }

    public function getLineaInvestigacion(): int
    {
        return $this->linea_investigacion;
    }

    public function getAreaConocimiento(): string
    {
        return $this->area_conocimiento;
    }

    /** @return array{linea_investigacion:int, area_conocimiento:string} */
    public function toArray(): array
    {
        return [
            'linea_investigacion' => $this->linea_investigacion,
            'area_conocimiento' => $this->area_conocimiento,
        ];
    }
}
