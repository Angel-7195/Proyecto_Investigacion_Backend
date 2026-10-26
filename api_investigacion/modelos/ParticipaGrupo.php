<?php

declare(strict_types=1);

/**
 * Participación de un docente en un grupo de investigación.
 * La PK es compuesta: docente_cedula + grupo_investigacion_id.
 * activo pertenece al mecanismo interno de borrado lógico.
 */
class ParticipaGrupo
{
    private int $docente_cedula;
    private int $grupo_investigacion_id;
    private string $rol;
    private string $fecha_inicio;
    private ?string $fecha_fin;

    public function __construct(
        int $docente_cedula,
        int $grupo_investigacion_id,
        string $rol,
        string $fecha_inicio,
        ?string $fecha_fin
    ) {
        $this->docente_cedula = $docente_cedula;
        $this->grupo_investigacion_id = $grupo_investigacion_id;
        $this->rol = $rol;
        $this->fecha_inicio = $fecha_inicio;
        $this->fecha_fin = $fecha_fin;
    }

    public function getDocenteCedula(): int
    {
        return $this->docente_cedula;
    }

    public function getGrupoInvestigacionId(): int
    {
        return $this->grupo_investigacion_id;
    }

    public function getRol(): string
    {
        return $this->rol;
    }

    public function setRol(string $rol): void
    {
        $this->rol = $rol;
    }

    public function getFechaInicio(): string
    {
        return $this->fecha_inicio;
    }

    public function setFechaInicio(string $fecha_inicio): void
    {
        $this->fecha_inicio = $fecha_inicio;
    }

    public function getFechaFin(): ?string
    {
        return $this->fecha_fin;
    }

    public function setFechaFin(?string $fecha_fin): void
    {
        $this->fecha_fin = $fecha_fin;
    }

    /** @return array<string, int|string|null> */
    public function toArray(): array
    {
        return [
            'docente_cedula' => $this->docente_cedula,
            'grupo_investigacion_id' => $this->grupo_investigacion_id,
            'rol' => $this->rol,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
        ];
    }
}
