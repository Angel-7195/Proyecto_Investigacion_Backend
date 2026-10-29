<?php

declare(strict_types=1);

/** Asociación de una línea de investigación con un objetivo de desarrollo sostenible. */
class OdsLinea
{
    private int $linea_investigacion;
    private int $ods;

    public function __construct(int $linea_investigacion, int $ods)
    {
        $this->linea_investigacion = $linea_investigacion;
        $this->ods = $ods;
    }

    public function getLineaInvestigacion(): int
    {
        return $this->linea_investigacion;
    }

    public function getOds(): int
    {
        return $this->ods;
    }

    /** @return array{linea_investigacion:int, ods:int} */
    public function toArray(): array
    {
        return [
            'linea_investigacion' => $this->linea_investigacion,
            'ods' => $this->ods,
        ];
    }
}
