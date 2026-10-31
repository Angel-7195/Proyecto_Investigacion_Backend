<?php

declare(strict_types=1);

require_once __DIR__ . '/IServicioAcLinea.php';
require_once __DIR__ . '/../../repositorios/Repositorios_AcLinea/IRepositorioAcLinea.php';
require_once __DIR__ . '/../../repositorios/Repositorios_LineaInvestigacion/IRepositorioLineaInvestigacion.php';
require_once __DIR__ . '/../../repositorios/Repositorios_AreaConocimiento/IRepositorioAreaConocimiento.php';
require_once __DIR__ . '/../../excepciones/ConflictoExcepcion.php';
require_once __DIR__ . '/../../excepciones/NoEncontradoExcepcion.php';

/** Valida las relaciones antes de registrar la asociación en ac_linea. */
class ServicioAcLinea implements IServicioAcLinea
{
    private IRepositorioAcLinea $repositorio;
    private IRepositorioLineaInvestigacion $repositorioLinea;
    private IRepositorioAreaConocimiento $repositorioArea;

    public function __construct(
        IRepositorioAcLinea $repositorio,
        IRepositorioLineaInvestigacion $repositorioLinea,
        IRepositorioAreaConocimiento $repositorioArea
    ) {
        $this->repositorio = $repositorio;
        $this->repositorioLinea = $repositorioLinea;
        $this->repositorioArea = $repositorioArea;
    }

    /** @return AcLinea[] */
    public function listar(): array
    {
        return $this->repositorio->obtenerTodos();
    }

    public function obtenerPorClave(int $lineaInvestigacion, string $areaConocimiento): AcLinea
    {
        $this->validarClaves($lineaInvestigacion, $areaConocimiento);
        $asociacion = $this->repositorio->obtenerPorClave($lineaInvestigacion, $areaConocimiento);
        if ($asociacion === null) {
            throw new NoEncontradoExcepcion('No existe una asociación activa con esas claves.');
        }
        return $asociacion;
    }

    public function crear(AcLinea $asociacion): AcLinea
    {
        $linea = $asociacion->getLineaInvestigacion();
        $area = $asociacion->getAreaConocimiento();
        $this->validarClaves($linea, $area);

        // Los repositorios de V1 filtran activo = TRUE.
        if ($this->repositorioLinea->obtenerPorId($linea) === null) {
            throw new ConflictoExcepcion('La línea de investigación no existe o está inactiva.');
        }
        if ($this->repositorioArea->obtenerPorId($area) === null) {
            throw new ConflictoExcepcion('El área de conocimiento no existe o está inactiva.');
        }
        // La PK SQL evita duplicados, incluso cuando la pareja tiene activo = FALSE.
        $this->repositorio->crear($asociacion);
        return $asociacion;
    }

    public function retirar(int $lineaInvestigacion, string $areaConocimiento): int
    {
        $this->validarClaves($lineaInvestigacion, $areaConocimiento);
        $filas = $this->repositorio->retirar($lineaInvestigacion, $areaConocimiento);
        if ($filas === 0) {
            throw new NoEncontradoExcepcion('No existe una asociación activa con esas claves.');
        }
        return $filas;
    }

    private function validarClaves(int $lineaInvestigacion, string $areaConocimiento): void
    {
        if ($lineaInvestigacion <= 0) {
            throw new InvalidArgumentException('linea_investigacion debe ser un entero positivo.');
        }
        $longitud = function_exists('mb_strlen')
            ? mb_strlen($areaConocimiento, 'UTF-8')
            : (preg_match_all('/./us', $areaConocimiento) ?: 0);
        if (trim($areaConocimiento) === '' || $longitud > 6) {
            throw new InvalidArgumentException('area_conocimiento debe contener entre 1 y 6 caracteres.');
        }
    }
}
