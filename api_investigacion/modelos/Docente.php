<?php

declare(strict_types=1);

/**
 * Modelo docente. Respeta las columnas literales de db/init.sql,
 * incluido nacionalidaad. La cédula no tiene setter; activo es interno.
 */
class Docente
{
    private int $cedula;
    private string $nombres;
    private string $apellidos;
    private string $genero;
    private string $cargo;
    private string $fecha_nacimiento;
    private string $correo;
    private string $telefono;
    private string $url_cvlac;
    private string $fecha_actualizacion;
    private string $escalafon;
    private string $perfil;
    private ?string $cat_minciencia;
    private string $conv_minciencia;
    private string $nacionalidaad;
    private ?int $linea_investigacion_principal;

    public function __construct(
        int $cedula,
        string $nombres,
        string $apellidos,
        string $genero,
        string $cargo,
        string $fecha_nacimiento,
        string $correo,
        string $telefono,
        string $url_cvlac,
        string $fecha_actualizacion,
        string $escalafon,
        string $perfil,
        ?string $cat_minciencia,
        string $conv_minciencia,
        string $nacionalidaad,
        ?int $linea_investigacion_principal
    ) {
        $this->cedula = $cedula;
        $this->nombres = $nombres;
        $this->apellidos = $apellidos;
        $this->genero = $genero;
        $this->cargo = $cargo;
        $this->fecha_nacimiento = $fecha_nacimiento;
        $this->correo = $correo;
        $this->telefono = $telefono;
        $this->url_cvlac = $url_cvlac;
        $this->fecha_actualizacion = $fecha_actualizacion;
        $this->escalafon = $escalafon;
        $this->perfil = $perfil;
        $this->cat_minciencia = $cat_minciencia;
        $this->conv_minciencia = $conv_minciencia;
        $this->nacionalidaad = $nacionalidaad;
        $this->linea_investigacion_principal = $linea_investigacion_principal;
    }

    public function getCedula(): int
    {
        return $this->cedula;
    }

    public function getNombres(): string
    {
        return $this->nombres;
    }

    public function setNombres(string $nombres): void
    {
        $this->nombres = $nombres;
    }

    public function getApellidos(): string
    {
        return $this->apellidos;
    }

    public function setApellidos(string $apellidos): void
    {
        $this->apellidos = $apellidos;
    }

    public function getGenero(): string
    {
        return $this->genero;
    }

    public function setGenero(string $genero): void
    {
        $this->genero = $genero;
    }

    public function getCargo(): string
    {
        return $this->cargo;
    }

    public function setCargo(string $cargo): void
    {
        $this->cargo = $cargo;
    }

    public function getFechaNacimiento(): string
    {
        return $this->fecha_nacimiento;
    }

    public function setFechaNacimiento(string $fecha_nacimiento): void
    {
        $this->fecha_nacimiento = $fecha_nacimiento;
    }

    public function getCorreo(): string
    {
        return $this->correo;
    }

    public function setCorreo(string $correo): void
    {
        $this->correo = $correo;
    }

    public function getTelefono(): string
    {
        return $this->telefono;
    }

    public function setTelefono(string $telefono): void
    {
        $this->telefono = $telefono;
    }

    public function getUrlCvlac(): string
    {
        return $this->url_cvlac;
    }

    public function setUrlCvlac(string $url_cvlac): void
    {
        $this->url_cvlac = $url_cvlac;
    }

    public function getFechaActualizacion(): string
    {
        return $this->fecha_actualizacion;
    }

    public function setFechaActualizacion(string $fecha_actualizacion): void
    {
        $this->fecha_actualizacion = $fecha_actualizacion;
    }

    public function getEscalafon(): string
    {
        return $this->escalafon;
    }

    public function setEscalafon(string $escalafon): void
    {
        $this->escalafon = $escalafon;
    }

    public function getPerfil(): string
    {
        return $this->perfil;
    }

    public function setPerfil(string $perfil): void
    {
        $this->perfil = $perfil;
    }

    public function getCatMinciencia(): ?string
    {
        return $this->cat_minciencia;
    }

    public function setCatMinciencia(?string $cat_minciencia): void
    {
        $this->cat_minciencia = $cat_minciencia;
    }

    public function getConvMinciencia(): string
    {
        return $this->conv_minciencia;
    }

    public function setConvMinciencia(string $conv_minciencia): void
    {
        $this->conv_minciencia = $conv_minciencia;
    }

    public function getNacionalidaad(): string
    {
        return $this->nacionalidaad;
    }

    public function setNacionalidaad(string $nacionalidaad): void
    {
        $this->nacionalidaad = $nacionalidaad;
    }

    public function getLineaInvestigacionPrincipal(): ?int
    {
        return $this->linea_investigacion_principal;
    }

    public function setLineaInvestigacionPrincipal(?int $linea_investigacion_principal): void
    {
        $this->linea_investigacion_principal = $linea_investigacion_principal;
    }

    public function toArray(): array
    {
        return [
            'cedula' => $this->cedula,
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'genero' => $this->genero,
            'cargo' => $this->cargo,
            'fecha_nacimiento' => $this->fecha_nacimiento,
            'correo' => $this->correo,
            'telefono' => $this->telefono,
            'url_cvlac' => $this->url_cvlac,
            'fecha_actualizacion' => $this->fecha_actualizacion,
            'escalafon' => $this->escalafon,
            'perfil' => $this->perfil,
            'cat_minciencia' => $this->cat_minciencia,
            'conv_minciencia' => $this->conv_minciencia,
            'nacionalidaad' => $this->nacionalidaad,
            'linea_investigacion_principal' => $this->linea_investigacion_principal,
        ];
    }
}
