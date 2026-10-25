<?php

declare(strict_types=1);

require_once __DIR__ . '/IServicioSemillero.php';
require_once __DIR__ . '/../../repositorios/Repositorios_Semillero/IRepositorioSemillero.php';
require_once __DIR__ . '/../../repositorios/Repositorios_GrupoInvestigacion/IRepositorioGrupoInvestigacion.php';
require_once __DIR__ . '/../../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../../excepciones/ConflictoExcepcion.php';

/**
 * Reglas de negocio de los semilleros.
 * La existencia y actividad del grupo se comprueban con su repositorio.
 */
class ServicioSemillero implements IServicioSemillero
{
    private IRepositorioSemillero $repositorio;
    private IRepositorioGrupoInvestigacion $repositorioGrupo;

    private const CAMPOS_EDITABLES = [
        'nombre', 'fecha_fundacion', 'grupo_investigacion',
    ];

    public function __construct(
        IRepositorioSemillero $repositorio,
        IRepositorioGrupoInvestigacion $repositorioGrupo
    ) {
        $this->repositorio = $repositorio;
        $this->repositorioGrupo = $repositorioGrupo;
    }

    /** @return Semillero[] */
    public function listar(): array
    {
        return $this->repositorio->obtenerTodos();
    }

    public function obtenerPorId(int $id): Semillero
    {
        $semillero = $this->repositorio->obtenerPorId($id);
        if ($semillero === null) {
            throw new NoEncontradoExcepcion(
                'No existe un semillero activo con el id solicitado.'
            );
        }
        return $semillero;
    }

    public function crear(Semillero $semillero): Semillero
    {
        $this->comprobarGrupoActivo($semillero->getGrupoInvestigacion());
        $this->repositorio->crear($semillero);
        return $semillero;
    }

    /** @param array<string, mixed> $datos */
    public function reemplazar(int $id, array $datos): int
    {
        $this->comprobarCampos($datos, true);
        $this->comprobarGrupoActivo($datos['grupo_investigacion']);
        $filas = $this->repositorio->reemplazar($id, $datos);
        if ($filas === 0) {
            throw new NoEncontradoExcepcion('No existe un semillero activo con ese id.');
        }
        return $filas;
    }

    /** @param array<string, mixed> $datos */
    public function actualizar(int $id, array $datos): int
    {
        $this->comprobarCampos($datos, false);
        if (array_key_exists('grupo_investigacion', $datos)) {
            $this->comprobarGrupoActivo($datos['grupo_investigacion']);
        }
        $filas = $this->repositorio->actualizar($id, $datos);
        if ($filas === 0) {
            throw new NoEncontradoExcepcion('No existe un semillero activo con ese id.');
        }
        return $filas;
    }

    public function retirar(int $id): int
    {
        // No borra físicamente la fila ni sus asociaciones.
        // La política con hijos activos se cerrará en el Spec Kit V2.
        $filas = $this->repositorio->retirar($id);
        if ($filas === 0) {
            throw new NoEncontradoExcepcion('No existe un semillero activo con ese id.');
        }
        return $filas;
    }

    private function comprobarGrupoActivo(int $idGrupo): void
    {
        // obtenerPorId() filtra activo = TRUE en RepositorioGrupoInvestigacionMariaDB.
        if ($this->repositorioGrupo->obtenerPorId($idGrupo) === null) {
            throw new ConflictoExcepcion(
                'El grupo de investigación indicado no existe o está inactivo.'
            );
        }
    }

    /** @param array<string, mixed> $datos */
    private function comprobarCampos(array $datos, bool $completo): void
    {
        if ($datos === []) {
            throw new InvalidArgumentException('No se enviaron campos para actualizar.');
        }
        foreach (array_keys($datos) as $campo) {
            if (!in_array($campo, self::CAMPOS_EDITABLES, true)) {
                throw new InvalidArgumentException('El campo ' . $campo . ' no es editable.');
            }
        }
        if ($completo) {
            foreach (self::CAMPOS_EDITABLES as $campo) {
                if (!array_key_exists($campo, $datos)) {
                    throw new InvalidArgumentException('Falta el campo ' . $campo . ' para PUT.');
                }
            }
        }
        // Defensa de tipo para llamadas que no provienen del controlador HTTP.
        if (array_key_exists('grupo_investigacion', $datos)
            && (!is_int($datos['grupo_investigacion']) || $datos['grupo_investigacion'] <= 0)) {
            throw new InvalidArgumentException('grupo_investigacion debe ser un entero positivo.');
        }
    }
}
