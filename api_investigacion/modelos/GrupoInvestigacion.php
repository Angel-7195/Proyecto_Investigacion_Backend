<?php

declare(strict_types=1);

class GrupoInvestigacion
{
    private int $id;
    private string $nombre;
    private ?string $url_gruplac;
    private ?string $categoria;
    private ?string $convocatoria;
    private string $fecha_fundacion;
    private ?int $universidsad;
    private int $interno;
    private string $ambito;

    public function __construct(
        int $id,
        string $nombre,
        ?string $url_gruplac,
        ?string $categoria,
        ?string $convocatoria,
        string $fecha_fundacion,
        ?int $universidsad,
        int $interno,
        string $ambito
    ) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->url_gruplac = $url_gruplac;
        $this->categoria = $categoria;
        $this->convocatoria = $convocatoria;
        $this->fecha_fundacion = $fecha_fundacion;
        $this->universidsad = $universidsad;
        $this->interno = $interno;
        $this->ambito = $ambito;
    }
    
    public function getId(): int
    {
        return $this->id;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getUrlGruplac(): ?string
    {
        return $this->url_gruplac;
    }

    public function getCategoria(): ?string
    {
        return $this->categoria;
    }

    public function getConvocatoria(): ?string
    {
        return $this->convocatoria;
    }

    public function getFechaFundacion(): string
    {
        return $this->fecha_fundacion;
    }

    public function getUniversidsad(): ?int
    {
        return $this->universidsad;
    }

    public function getInterno(): int
    {
        return $this->interno;
    }

    public function getAmbito(): string
    {
        return $this->ambito;
    }
    
    public function setNombre(string $nombre): void
    {
        $this->nombre = $nombre;
    }

    public function setUrlGruplac(?string $url_gruplac): void
    {
        $this->url_gruplac = $url_gruplac;
    }

    public function setCategoria(?string $categoria): void
    {
        $this->categoria = $categoria;
    }

    public function setConvocatoria(?string $convocatoria): void
    {
        $this->convocatoria = $convocatoria;
    }

    public function setFechaFundacion(string $fecha_fundacion): void
    {
        $this->fecha_fundacion = $fecha_fundacion;
    }

    public function setUniversidsad(?int $universidsad): void
    {
        $this->universidsad = $universidsad;
    }

    public function setInterno(int $interno): void
    {
        $this->interno = $interno;
    }

    public function setAmbito(string $ambito): void
    {
        $this->ambito = $ambito;
    }

    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'url_gruplac' => $this->url_gruplac,
            'categoria' => $this->categoria,
            'convocatoria' => $this->convocatoria,
            'fecha_fundacion' => $this->fecha_fundacion,
            'universidsad' => $this->universidsad,
            'interno' => $this->interno,
            'ambito' => $this->ambito
        ];
    }

}
