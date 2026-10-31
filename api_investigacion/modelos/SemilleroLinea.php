<?php

declare(strict_types=1);

/** Asociación entre semillero y línea de investigación. */
class SemilleroLinea
{
    private int $semillero;
    private int $linea_investigacion;

    public function __construct(int $semillero_investigacion, int $linea_investigacion)
    {
        $this->semillero = $semillero_investigacion;
        $this->linea_investigacion = $linea_investigacion;
    }

    public function getSemillero(): int
    {
        return $this->semillero;
    }

    public function getLineaInvestigacion(): int
    {
        return $this->linea_investigacion;
    }

    /** @return array{semillero:int, linea_investigacion:int} */
    public function toArray(): array
    {
        return [
            'semillero' => $this->semillero,
            'linea_investigacion' => $this->linea_investigacion,
        ];
    }
}
