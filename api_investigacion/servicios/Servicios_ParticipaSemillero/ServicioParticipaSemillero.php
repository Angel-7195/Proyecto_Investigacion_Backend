<?php

declare(strict_types=1);

require_once __DIR__ . '/IServicioParticipaSemillero.php';
require_once __DIR__ . '/../../repositorios/Repositorios_ParticipaSemillero/IRepositorioParticipaSemillero.php';
require_once __DIR__ . '/../../repositorios/Repositorios_Docente/IRepositorioDocente.php';
require_once __DIR__ . '/../../repositorios/Repositorios_Semillero/IRepositorioSemillero.php';
require_once __DIR__ . '/../../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../../excepciones/ConflictoExcepcion.php';

/** Reglas de negocio de la participación de un docente en un semillero. */
class ServicioParticipaSemillero implements IServicioParticipaSemillero
{
    private IRepositorioParticipaSemillero $repositorio;
    private IRepositorioDocente $repositorioDocente;
    private IRepositorioSemillero $repositorioSemillero;

    private const CAMPOS_EDITABLES = ['rol', 'fecha_inicio', 'fecha_fin'];

    public function __construct(
        IRepositorioParticipaSemillero $repositorio,
        IRepositorioDocente $repositorioDocente,
        IRepositorioSemillero $repositorioSemillero
    ) {
        $this->repositorio = $repositorio;
        $this->repositorioDocente = $repositorioDocente;
        $this->repositorioSemillero = $repositorioSemillero;
    }

    /** @return ParticipaSemillero[] */
    public function listar(): array
    {
        return $this->repositorio->obtenerTodos();
    }

    public function obtenerPorClave(int $docenteId, int $semilleroId): ParticipaSemillero
    {
        $participacion = $this->repositorio->obtenerPorClave($docenteId, $semilleroId);
        if ($participacion === null) {
            throw new NoEncontradoExcepcion('No existe una participación activa para ese docente y semillero.');
        }
        return $participacion;
    }

    public function crear(ParticipaSemillero $participacion): ParticipaSemillero
    {
        $this->comprobarDocenteYSemilleroActivos(
            $participacion->getDocente(),
            $participacion->getSemillero()
        );
        $this->validarDatos([
            'rol' => $participacion->getRol(),
            'fecha_inicio' => $participacion->getFechaInicio(),
            'fecha_fin' => $participacion->getFechaFin(),
        ], true);
        $this->repositorio->crear($participacion);
        return $participacion;
    }

    /** @param array<string, mixed> $datos */
    public function reemplazar(int $docenteId, int $semilleroId, array $datos): int
    {
        $this->validarDatos($datos, true);
        $this->obtenerPorClave($docenteId, $semilleroId);
        $this->comprobarDocenteYSemilleroActivos($docenteId, $semilleroId);
        $filas = $this->repositorio->reemplazar($docenteId, $semilleroId, $datos);
        if ($filas === 0) {
            throw new NoEncontradoExcepcion('No existe una participación activa con esa clave.');
        }
        return $filas;
    }

    /** @param array<string, mixed> $datos */
    public function actualizar(int $docenteId, int $semilleroId, array $datos): int
    {
        $this->validarDatos($datos, false);
        $actual = $this->obtenerPorClave($docenteId, $semilleroId);
        $this->comprobarDocenteYSemilleroActivos($docenteId, $semilleroId);
        $inicio = $datos['fecha_inicio'] ?? $actual->getFechaInicio();
        $fin = array_key_exists('fecha_fin', $datos)
            ? $datos['fecha_fin'] : $actual->getFechaFin();
        $this->validarOrdenFechas($inicio, $fin);
        $filas = $this->repositorio->actualizar($docenteId, $semilleroId, $datos);
        if ($filas === 0) {
            throw new NoEncontradoExcepcion('No existe una participación activa con esa clave.');
        }
        return $filas;
    }

    public function retirar(int $docenteId, int $semilleroId): int
    {
        $filas = $this->repositorio->retirar($docenteId, $semilleroId);
        if ($filas === 0) {
            throw new NoEncontradoExcepcion('No existe una participación activa con esa clave.');
        }
        return $filas;
    }

    private function comprobarDocenteYSemilleroActivos(int $docenteId, int $semilleroId): void
    {
        // Los repositorios de V2 devuelven null si el registro está inactivo.
        if ($this->repositorioDocente->obtenerPorCedula($docenteId) === null) {
            throw new ConflictoExcepcion('El docente no existe o está inactivo.');
        }
        if ($this->repositorioSemillero->obtenerPorId($semilleroId) === null) {
            throw new ConflictoExcepcion('El semillero no existe o está inactivo.');
        }
    }

    /** @param array<string, mixed> $datos */
    private function validarDatos(array $datos, bool $completo): void
    {
        if ($datos === []) {
            throw new InvalidArgumentException('Debes enviar campos para actualizar.');
        }
        foreach (array_keys($datos) as $campo) {
            if (!in_array($campo, self::CAMPOS_EDITABLES, true)) {
                throw new InvalidArgumentException('El campo ' . $campo . ' no es editable.');
            }
        }
        if ($completo) {
            foreach (self::CAMPOS_EDITABLES as $campo) {
                if (!array_key_exists($campo, $datos)) {
                    throw new InvalidArgumentException('Falta el campo ' . $campo . '.');
                }
            }
        }
        if (array_key_exists('rol', $datos)) {
            if (!is_string($datos['rol']) || trim($datos['rol']) === ''
                || $this->longitud($datos['rol']) > 15) {
                throw new InvalidArgumentException('rol debe contener entre 1 y 15 caracteres.');
            }
        }
        foreach (['fecha_inicio', 'fecha_fin'] as $campo) {
            if (!array_key_exists($campo, $datos)) {
                continue;
            }
            if ($campo === 'fecha_fin' && $datos[$campo] === null) {
                continue;
            }
            if (!$this->fechaValida($datos[$campo])) {
                throw new InvalidArgumentException($campo . ' debe ser una fecha válida YYYY-MM-DD.');
            }
        }
        if (array_key_exists('fecha_inicio', $datos)
            && array_key_exists('fecha_fin', $datos)) {
            $this->validarOrdenFechas($datos['fecha_inicio'], $datos['fecha_fin']);
        }
    }

    private function validarOrdenFechas(string $inicio, ?string $fin): void
    {
        if ($fin !== null && $fin < $inicio) {
            throw new InvalidArgumentException('fecha_fin no puede ser anterior a fecha_inicio.');
        }
    }

    private function fechaValida(mixed $valor): bool
    {
        if (!is_string($valor)) {
            return false;
        }
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
        return $fecha !== false && $fecha->format('Y-m-d') === $valor;
    }

    private function longitud(string $texto): int
    {
        return function_exists('mb_strlen')
            ? mb_strlen($texto, 'UTF-8')
            : (preg_match_all('/./us', $texto) ?: 0);
    }
}
