<?php

declare(strict_types=1);

require_once __DIR__ . '/IServicioSemilleroLinea.php';
require_once __DIR__ . '/../../repositorios/Repositorios_SemilleroLinea/IRepositorioSemilleroLinea.php';
require_once __DIR__ . '/../../repositorios/Repositorios_Semillero/IRepositorioSemillero.php';
require_once __DIR__ . '/../../repositorios/Repositorios_LineaInvestigacion/IRepositorioLineaInvestigacion.php';
require_once __DIR__ . '/../../excepciones/ConflictoExcepcion.php';
require_once __DIR__ . '/../../excepciones/NoEncontradoExcepcion.php';

/** Reglas de negocio y validación de claves foráneas activas. */
class ServicioSemilleroLinea implements IServicioSemilleroLinea
{
    private IRepositorioSemilleroLinea $repositorio;
    private IRepositorioSemillero $repositorioSemillero;
    private IRepositorioLineaInvestigacion $repositorioLinea;

    public function __construct(
        IRepositorioSemilleroLinea $repositorio,
        IRepositorioSemillero $repositorioSemillero,
        IRepositorioLineaInvestigacion $repositorioLinea
    ) {
        $this->repositorio = $repositorio;
        $this->repositorioSemillero = $repositorioSemillero;
        $this->repositorioLinea = $repositorioLinea;
    }

    /** @return SemilleroLinea[] */
    public function listar(): array
    {
        return $this->repositorio->obtenerTodos();
    }

    public function obtenerPorClave(int $semilleroId, int $lineaInvestigacion): SemilleroLinea
    {
        $this->validarClaves($semilleroId, $lineaInvestigacion);
        $asociacion = $this->repositorio->obtenerPorClave($semilleroId, $lineaInvestigacion);
        if ($asociacion === null) {
            throw new NoEncontradoExcepcion('No existe una asociación activa con esas claves.');
        }
        return $asociacion;
    }

    public function crear(SemilleroLinea $asociacion): SemilleroLinea
    {
        $semillero = $asociacion->getSemillero();
        $linea = $asociacion->getLineaInvestigacion();
        $this->validarClaves($semillero, $linea);

        // Los repositorios de semillero (V2) y línea (V1) obtienen solo registros activos.
        if ($this->repositorioSemillero->obtenerPorId($semillero) === null) {
            throw new ConflictoExcepcion('El semillero no existe o está inactivo.');
        }
        if ($this->repositorioLinea->obtenerPorId($linea) === null) {
            throw new ConflictoExcepcion('La línea de investigación no existe o está inactiva.');
        }

        // No se reactiva una asociación retirada: la PK permanece ocupada.
        $this->repositorio->crear($asociacion);
        return $asociacion;
    }

    public function retirar(int $semilleroId, int $lineaInvestigacion): int
    {
        $this->validarClaves($semilleroId, $lineaInvestigacion);
        $filas = $this->repositorio->retirar($semilleroId, $lineaInvestigacion);
        if ($filas === 0) {
            throw new NoEncontradoExcepcion('No existe una asociación activa con esas claves.');
        }
        return $filas;
    }

    private function validarClaves(int $semilleroId, int $lineaInvestigacion): void
    {
        if ($semilleroId <= 0) {
            throw new InvalidArgumentException('semillero debe ser un entero positivo.');
        }
        if ($lineaInvestigacion <= 0) {
            throw new InvalidArgumentException('linea_investigacion debe ser un entero positivo.');
        }
    }
}
