<?php

declare(strict_types=1);

require_once __DIR__ . '/IServicioAaLinea.php';
require_once __DIR__ . '/../../repositorios/Repositorios_AaLinea/IRepositorioAaLinea.php';
require_once __DIR__ . '/../../repositorios/Repositorios_AreaAplicacion/IRepositorioAreaAplicacion.php';
require_once __DIR__ . '/../../repositorios/Repositorios_LineaInvestigacion/IRepositorioLineaInvestigacion.php';
require_once __DIR__ . '/../../excepciones/ConflictoExcepcion.php';
require_once __DIR__ . '/../../excepciones/NoEncontradoExcepcion.php';

/** Reglas de negocio y validación de claves foráneas activas. */
class ServicioAaLinea implements IServicioAaLinea
{
    private IRepositorioAaLinea $repositorio;
    private IRepositorioAreaAplicacion $repositorioArea;
    private IRepositorioLineaInvestigacion $repositorioLinea;

    public function __construct(
        IRepositorioAaLinea $repositorio,
        IRepositorioAreaAplicacion $repositorioArea,
        IRepositorioLineaInvestigacion $repositorioLinea
    ) {
        $this->repositorio = $repositorio;
        $this->repositorioArea = $repositorioArea;
        $this->repositorioLinea = $repositorioLinea;
    }

    /** @return AaLinea[] */
    public function listar(): array
    {
        return $this->repositorio->obtenerTodos();
    }

    public function obtenerPorClave(int $areaAplicacion, int $lineaInvestigacion): AaLinea
    {
        $this->validarClaves($areaAplicacion, $lineaInvestigacion);
        $asociacion = $this->repositorio->obtenerPorClave($areaAplicacion, $lineaInvestigacion);
        if ($asociacion === null) {
            throw new NoEncontradoExcepcion('No existe una asociación activa con esas claves.');
        }
        return $asociacion;
    }

    public function crear(AaLinea $asociacion): AaLinea
    {
        $area = $asociacion->getAreaAplicacion();
        $linea = $asociacion->getLineaInvestigacion();
        $this->validarClaves($area, $linea);

        // Los repositorios de V1 obtienen únicamente registros activos.
        if ($this->repositorioArea->obtenerPorId($area) === null) {
            throw new ConflictoExcepcion('El área de aplicación no existe o está inactiva.');
        }
        if ($this->repositorioLinea->obtenerPorId($linea) === null) {
            throw new ConflictoExcepcion('La línea de investigación no existe o está inactiva.');
        }

        // No se reactiva una asociación retirada: la PK permanece ocupada.
        $this->repositorio->crear($asociacion);
        return $asociacion;
    }

    public function retirar(int $areaAplicacion, int $lineaInvestigacion): int
    {
        $this->validarClaves($areaAplicacion, $lineaInvestigacion);
        $filas = $this->repositorio->retirar($areaAplicacion, $lineaInvestigacion);
        if ($filas === 0) {
            throw new NoEncontradoExcepcion('No existe una asociación activa con esas claves.');
        }
        return $filas;
    }

    private function validarClaves(int $areaAplicacion, int $lineaInvestigacion): void
    {
        if ($areaAplicacion <= 0) {
            throw new InvalidArgumentException('area_aplicacion debe ser un entero positivo.');
        }
        if ($lineaInvestigacion <= 0) {
            throw new InvalidArgumentException('linea_investigacion debe ser un entero positivo.');
        }
    }
}
