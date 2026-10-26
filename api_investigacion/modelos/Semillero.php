<?php

declare(strict_types=1);

/**
 * Entidad de un semillero de investigación.
 * La clave primaria id no se modifica luego de construir el objeto.
 * activo se gestiona exclusivamente mediante borrado lógico.
 */
class Semillero
{
    private int $id;
    private string $nombre;
    private string $fecha_fundacion;
    private int $grupo_investigacion;

    public function __construct(
        int $id,
        string $nombre,
        string $fecha_fundacion,
        int $grupo_investigacion
    ) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->fecha_fundacion = $fecha_fundacion;
        $this->grupo_investigacion = $grupo_investigacion;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): void
    {
        $this->nombre = $nombre;
    }

    public function getFechaFundacion(): string
    {
        return $this->fecha_fundacion;
    }

    public function setFechaFundacion(string $fecha_fundacion): void
    {
        $this->fecha_fundacion = $fecha_fundacion;
    }

    public function getGrupoInvestigacion(): int
    {
        return $this->grupo_investigacion;
    }

    public function setGrupoInvestigacion(int $grupo_investigacion): void
    {
        $this->grupo_investigacion = $grupo_investigacion;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'fecha_fundacion' => $this->fecha_fundacion,
            'grupo_investigacion' => $this->grupo_investigacion,
        ];
    }
}
