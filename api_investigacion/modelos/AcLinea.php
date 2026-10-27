<?php

declare(strict_types=1);

class AcLinea
{
    private int $lineaInvestigacion;
    private string $areaConocimiento;

    public function __construct(
        int $lineaInvestigacion,
        string $areaConocimiento
    ) {
        $this->lineaInvestigacion = $lineaInvestigacion;
        $this->areaConocimiento = $areaConocimiento;
    }

    public function getLineaInvestigacion(): int
    {
        return $this->lineaInvestigacion;
    }

    public function getAreaConocimiento(): string
    {
        return $this->areaConocimiento;
    }

    public function toArray(): array
    {
        return [
            'linea_investigacion' => $this->lineaInvestigacion,
            'area_conocimiento' => $this->areaConocimiento,
        ];
    }
}
