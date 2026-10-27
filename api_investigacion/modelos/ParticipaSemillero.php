<?php

declare(strict_types=1);

/**
 * Participación de un docente en un semillero.
 * La PK es compuesta: docente + semillero.
 * activo pertenece al mecanismo interno de borrado lógico.
 */
class ParticipaSemillero
{
    private int $docente;
    private int $semillero;
    private string $rol;
    private string $fecha_inicio;
    private ?string $fecha_fin;

    public function __construct(
        int $docente,
        int $semillero,
        string $rol,
        string $fecha_inicio,
        ?string $fecha_fin
    ) {
        $this->docente = $docente;
        $this->semillero = $semillero;
        $this->rol = $rol;
        $this->fecha_inicio = $fecha_inicio;
        $this->fecha_fin = $fecha_fin;
    }

    public function getDocente(): int
    {
        return $this->docente;
    }

    public function getSemillero(): int
    {
        return $this->semillero;
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
            'docente' => $this->docente,
            'semillero' => $this->semillero,
            'rol' => $this->rol,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
        ];
    }
}
