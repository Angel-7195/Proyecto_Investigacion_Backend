<?php

declare(strict_types=1);

require_once __DIR__ . '/IServicioGrupoLinea.php';
require_once __DIR__ . '/../../repositorios/Repositorios_GrupoLinea/IRepositorioGrupoLinea.php';
require_once __DIR__ . '/../../repositorios/Repositorios_GrupoInvestigacion/IRepositorioGrupoInvestigacion.php';
require_once __DIR__ . '/../../repositorios/Repositorios_LineaInvestigacion/IRepositorioLineaInvestigacion.php';
require_once __DIR__ . '/../../excepciones/ConflictoExcepcion.php';
require_once __DIR__ . '/../../excepciones/NoEncontradoExcepcion.php';

/** Reglas de negocio y validación de claves foráneas activas. */
class ServicioGrupoLinea implements IServicioGrupoLinea
{
    private IRepositorioGrupoLinea $repositorio;
    private IRepositorioGrupoInvestigacion $repositorioGrupo;
    private IRepositorioLineaInvestigacion $repositorioLinea;

    public function __construct(
        IRepositorioGrupoLinea $repositorio,
        IRepositorioGrupoInvestigacion $repositorioGrupo,
        IRepositorioLineaInvestigacion $repositorioLinea
    ) {
        $this->repositorio = $repositorio;
        $this->repositorioGrupo = $repositorioGrupo;
        $this->repositorioLinea = $repositorioLinea;
    }

    /** @return GrupoLinea[] */
    public function listar(): array
    {
        return $this->repositorio->obtenerTodos();
    }

    public function obtenerPorClave(int $grupoInvestigacion, int $lineaInvestigacion): GrupoLinea
    {
        $this->validarClaves($grupoInvestigacion, $lineaInvestigacion);
        $asociacion = $this->repositorio->obtenerPorClave($grupoInvestigacion, $lineaInvestigacion);
        if ($asociacion === null) {
            throw new NoEncontradoExcepcion('No existe una asociación activa con esas claves.');
        }
        return $asociacion;
    }

    public function crear(GrupoLinea $asociacion): GrupoLinea
    {
        $grupo = $asociacion->getGrupoInvestigacion();
        $linea = $asociacion->getLineaInvestigacion();
        $this->validarClaves($grupo, $linea);

        // Los repositorios de grupo (V2) y línea (V1) obtienen solo registros activos.
        if ($this->repositorioGrupo->obtenerPorId($grupo) === null) {
            throw new ConflictoExcepcion('El grupo de investigación no existe o está inactivo.');
        }
        if ($this->repositorioLinea->obtenerPorId($linea) === null) {
            throw new ConflictoExcepcion('La línea de investigación no existe o está inactiva.');
        }

        // No se reactiva una asociación retirada: la PK permanece ocupada.
        $this->repositorio->crear($asociacion);
        return $asociacion;
    }

    public function retirar(int $grupoInvestigacion, int $lineaInvestigacion): int
    {
        $this->validarClaves($grupoInvestigacion, $lineaInvestigacion);
        $filas = $this->repositorio->retirar($grupoInvestigacion, $lineaInvestigacion);
        if ($filas === 0) {
            throw new NoEncontradoExcepcion('No existe una asociación activa con esas claves.');
        }
        return $filas;
    }

    private function validarClaves(int $grupoInvestigacion, int $lineaInvestigacion): void
    {
        if ($grupoInvestigacion <= 0) {
            throw new InvalidArgumentException('grupo_investigacion debe ser un entero positivo.');
        }
        if ($lineaInvestigacion <= 0) {
            throw new InvalidArgumentException('linea_investigacion debe ser un entero positivo.');
        }
    }
}
