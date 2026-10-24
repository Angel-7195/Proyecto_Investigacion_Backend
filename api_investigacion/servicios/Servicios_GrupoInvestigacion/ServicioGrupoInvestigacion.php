<?php

declare(strict_types=1);

require_once __DIR__ . '/IServicioGrupoInvestigacion.php';
require_once __DIR__ . '/../../repositorios/Repositorios_GrupoInvestigacion/IRepositorioGrupoInvestigacion.php';
require_once __DIR__ . '/../../repositorios/Repositorios_Universidad/IRepositorioUniversidad.php';
require_once __DIR__ . '/../../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../../excepciones/ConflictoExcepcion.php';

/**
 * Reglas de negocio de grupo_investigacion.
 *
 * No contiene consultas SQL ni emite respuestas HTTP.
 * La clave foránea se llama universidsad en el SQL actual.
 */
class ServicioGrupoInvestigacion implements IServicioGrupoInvestigacion
{
    private IRepositorioGrupoInvestigacion $repositorio;
    private IRepositorioUniversidad $repositorioUniversidad;

    /** @var string[] Campos admitidos en PUT/PATCH; excluye id y activo. */
    private const CAMPOS_EDITABLES = [
        'nombre',
        'url_gruplac',
        'categoria',
        'convocatoria',
        'fecha_fundacion',
        'universidsad',
        'interno',
        'ambito',
    ];

    public function __construct(
        IRepositorioGrupoInvestigacion $repositorio,
        IRepositorioUniversidad $repositorioUniversidad
    ) {
        $this->repositorio = $repositorio;
        $this->repositorioUniversidad = $repositorioUniversidad;
    }

    /** @return GrupoInvestigacion[] */
    public function listar(): array
    {
        return $this->repositorio->obtenerTodos();
    }

    public function obtenerPorId(int $id): GrupoInvestigacion
    {
        $grupo = $this->repositorio->obtenerPorId($id);

        if ($grupo === null) {
            throw new NoEncontradoExcepcion(
                'No existe un grupo de investigación activo con el id solicitado.'
            );
        }

        return $grupo;
    }

    public function crear(GrupoInvestigacion $grupoInvestigacion): GrupoInvestigacion
    {
        $this->comprobarUniversidadActiva(
            $grupoInvestigacion->getUniversidsad()
        );

        // El identificador se recibe del cliente: no es AUTO_INCREMENT.
        $this->repositorio->crear($grupoInvestigacion);

        return $grupoInvestigacion;
    }

    /** @param array<string, mixed> $datos */
    public function reemplazar(int $id, array $datos): int
    {
        $this->comprobarCamposEditables($datos, true);
        $this->comprobarUniversidadDeDatos($datos);

        $filas = $this->repositorio->reemplazar($id, $datos);

        if ($filas === 0) {
            throw new NoEncontradoExcepcion(
                'No existe un grupo de investigación activo con el id solicitado.'
            );
        }

        return $filas;
    }

    /** @param array<string, mixed> $datos */
    public function actualizar(int $id, array $datos): int
    {
        $this->comprobarCamposEditables($datos, false);
        $this->comprobarUniversidadDeDatos($datos);

        $filas = $this->repositorio->actualizar($id, $datos);

        if ($filas === 0) {
            throw new NoEncontradoExcepcion(
                'No existe un grupo de investigación activo con el id solicitado.'
            );
        }

        return $filas;
    }

    public function retirar(int $id): int
    {
        // Borrado lógico como en V1. La política de retiro con hijos
        // activos debe cerrarse en el Spec Kit antes de finalizar V2.
        $filas = $this->repositorio->retirar($id);

        if ($filas === 0) {
            throw new NoEncontradoExcepcion(
                'No existe un grupo de investigación activo con el id solicitado.'
            );
        }

        return $filas;
    }

    /**
     * Una FK opcional puede ser NULL. Si tiene valor, debe identificar
     * una universidad activa (obtenerPorId de V1 filtra activo = TRUE).
     */
    private function comprobarUniversidadActiva(?int $universidsad): void
    {
        if ($universidsad === null) {
            return;
        }

        if ($this->repositorioUniversidad->obtenerPorId($universidsad) === null) {
            throw new ConflictoExcepcion(
                'La universidad indicada no existe o se encuentra inactiva.'
            );
        }
    }

    /** @param array<string, mixed> $datos */
    private function comprobarUniversidadDeDatos(array $datos): void
    {
        // En PATCH, no enviar la FK significa conservar la actual.
        if (!array_key_exists('universidsad', $datos)) {
            return;
        }

        $universidsad = $datos['universidsad'];

        if ($universidsad !== null && !is_int($universidsad)) {
            throw new InvalidArgumentException(
                'El campo universidsad debe ser un entero o null.'
            );
        }

        $this->comprobarUniversidadActiva($universidsad);
    }

    /**
     * Defensa adicional: id y activo no son editables.
     * El controlador seguirá siendo responsable de validar tipos,
     * longitudes y forma del cuerpo HTTP (422).
     *
     * @param array<string, mixed> $datos
     */
    private function comprobarCamposEditables(array $datos, bool $completo): void
    {
        if ($datos === []) {
            throw new InvalidArgumentException(
                'No se envió ningún campo para actualizar.'
            );
        }

        foreach (array_keys($datos) as $campo) {
            if (!in_array($campo, self::CAMPOS_EDITABLES, true)) {
                throw new InvalidArgumentException(
                    'El campo ' . $campo . ' no se puede modificar.'
                );
            }
        }

        if ($completo) {
            foreach (self::CAMPOS_EDITABLES as $campo) {
                if (!array_key_exists($campo, $datos)) {
                    throw new InvalidArgumentException(
                        'Falta el campo obligatorio en PUT: ' . $campo . '.'
                    );
                }
            }
        }
    }
}
