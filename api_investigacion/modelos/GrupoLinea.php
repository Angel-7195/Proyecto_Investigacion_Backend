<?php

declare(strict_types=1);

/** Asociación entre grupo de investigación y línea de investigación. */
class GrupoLinea
{
    private int $grupo_investigacion;
    private int $linea_investigacion;

    public function __construct(int $grupo_investigacion, int $linea_investigacion)
    {
        $this->grupo_investigacion = $grupo_investigacion;
        $this->linea_investigacion = $linea_investigacion;
    }

    public function getGrupoInvestigacion(): int
    {
        return $this->grupo_investigacion;
    }

    public function getLineaInvestigacion(): int
    {
        return $this->linea_investigacion;
    }

    /** @return array{grupo_investigacion:int, linea_investigacion:int} */
    public function toArray(): array
    {
        return [
            'grupo_investigacion' => $this->grupo_investigacion,
            'linea_investigacion' => $this->linea_investigacion,
        ];
    }
}
