<?php

declare(strict_types=1);

require_once __DIR__ . '/IServicioOdsLinea.php';
require_once __DIR__ . '/../../repositorios/Repositorios_OdsLinea/IRepositorioOdsLinea.php';
require_once __DIR__ . '/../../repositorios/Repositorios_LineaInvestigacion/IRepositorioLineaInvestigacion.php';
require_once __DIR__ . '/../../repositorios/Repositorios_ObjetivoDesarrolloSostenible/IRepositorioObjetivoDesarrolloSostenible.php';
require_once __DIR__ . '/../../excepciones/ConflictoExcepcion.php';
require_once __DIR__ . '/../../excepciones/NoEncontradoExcepcion.php';

/** Reglas de negocio de ods_linea y validación de las entidades relacionadas. */
class ServicioOdsLinea implements IServicioOdsLinea
{
    private IRepositorioOdsLinea $repositorio;
    private IRepositorioLineaInvestigacion $repositorioLinea;
    private IRepositorioObjetivoDesarrolloSostenible $repositorioOds;

    public function __construct(
        IRepositorioOdsLinea $repositorio,
        IRepositorioLineaInvestigacion $repositorioLinea,
        IRepositorioObjetivoDesarrolloSostenible $repositorioOds
    ) {
        $this->repositorio = $repositorio;
        $this->repositorioLinea = $repositorioLinea;
        $this->repositorioOds = $repositorioOds;
    }

    /** @return OdsLinea[] */
    public function listar(): array
    {
        return $this->repositorio->obtenerTodos();
    }

    public function obtenerPorClave(int $lineaInvestigacion, int $ods): OdsLinea
    {
        $this->validarClaves($lineaInvestigacion, $ods);
        $asociacion = $this->repositorio->obtenerPorClave($lineaInvestigacion, $ods);
        if ($asociacion === null) {
            throw new NoEncontradoExcepcion('No existe una asociación activa con esas claves.');
        }
        return $asociacion;
    }

    public function crear(OdsLinea $asociacion): OdsLinea
    {
        $linea = $asociacion->getLineaInvestigacion();
        $ods = $asociacion->getOds();
        $this->validarClaves($linea, $ods);

        // Los repositorios de V1 solo devuelven entidades activas.
        if ($this->repositorioLinea->obtenerPorId($linea) === null) {
            throw new ConflictoExcepcion('La línea de investigación no existe o está inactiva.');
        }
        if ($this->repositorioOds->obtenerPorId($ods) === null) {
            throw new ConflictoExcepcion('El objetivo de desarrollo sostenible no existe o está inactivo.');
        }

        // La PK de MariaDB impide crear una combinación duplicada o retirada.
        $this->repositorio->crear($asociacion);
        return $asociacion;
    }

    public function retirar(int $lineaInvestigacion, int $ods): int
    {
        $this->validarClaves($lineaInvestigacion, $ods);
        $filas = $this->repositorio->retirar($lineaInvestigacion, $ods);
        if ($filas === 0) {
            throw new NoEncontradoExcepcion('No existe una asociación activa con esas claves.');
        }
        return $filas;
    }

    private function validarClaves(int $lineaInvestigacion, int $ods): void
    {
        if ($lineaInvestigacion <= 0) {
            throw new InvalidArgumentException('linea_investigacion debe ser un entero positivo.');
        }
        if ($ods <= 0) {
            throw new InvalidArgumentException('ods debe ser un entero positivo.');
        }
    }
}
