<?php

declare(strict_types=1);

require_once __DIR__ . '/IServicioDocente.php';
require_once __DIR__ . '/../../repositorios/Repositorios_Docente/IRepositorioDocente.php';
require_once __DIR__ . '/../../repositorios/Repositorios_LineaInvestigacion/IRepositorioLineaInvestigacion.php';
require_once __DIR__ . '/../../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../../excepciones/ConflictoExcepcion.php';

/** Reglas de negocio; valida que la línea principal exista y esté activa. */
class ServicioDocente implements IServicioDocente
{
    private IRepositorioDocente $repositorio;
    private IRepositorioLineaInvestigacion $repositorioLinea;

    private const CAMPOS_EDITABLES = [
        'nombres',
        'apellidos',
        'genero',
        'cargo',
        'fecha_nacimiento',
        'correo',
        'telefono',
        'url_cvlac',
        'fecha_actualizacion',
        'escalafon',
        'perfil',
        'cat_minciencia',
        'conv_minciencia',
        'nacionalidaad',
        'linea_investigacion_principal',
    ];

    public function __construct(
        IRepositorioDocente $repositorio,
        IRepositorioLineaInvestigacion $repositorioLinea
    ) {
        $this->repositorio = $repositorio;
        $this->repositorioLinea = $repositorioLinea;
    }

    /** @return Docente[] */
    public function listar(): array
    {
        return $this->repositorio->obtenerTodos();
    }

    public function obtenerPorCedula(int $cedula): Docente
    {
        $docente = $this->repositorio->obtenerPorCedula($cedula);
        if ($docente === null) {
            throw new NoEncontradoExcepcion('No existe un docente activo con esa cédula.');
        }
        return $docente;
    }

    public function crear(Docente $docente): Docente
    {
        $this->comprobarLineaActiva($docente->getLineaInvestigacionPrincipal());
        $this->repositorio->crear($docente);
        return $docente;
    }

    public function reemplazar(int $cedula, array $datos): int
    {
        $this->comprobarCampos($datos, true);
        $this->comprobarLineaActiva($datos['linea_investigacion_principal']);
        $filas = $this->repositorio->reemplazar($cedula, $datos);
        if ($filas === 0) {
            throw new NoEncontradoExcepcion('No existe un docente activo con esa cédula.');
        }
        return $filas;
    }

    public function actualizar(int $cedula, array $datos): int
    {
        $this->comprobarCampos($datos, false);
        if (array_key_exists('linea_investigacion_principal', $datos)) {
            $this->comprobarLineaActiva($datos['linea_investigacion_principal']);
        }
        $filas = $this->repositorio->actualizar($cedula, $datos);
        if ($filas === 0) {
            throw new NoEncontradoExcepcion('No existe un docente activo con esa cédula.');
        }
        return $filas;
    }

    public function retirar(int $cedula): int
    {
        // No elimina asociaciones físicamente. Política de dependientes:
        // pendiente de acuerdo en Spec Kit V2 antes de crear participa_*.
        $filas = $this->repositorio->retirar($cedula);
        if ($filas === 0) {
            throw new NoEncontradoExcepcion('No existe un docente activo con esa cédula.');
        }
        return $filas;
    }

    private function comprobarLineaActiva(?int $idLinea): void
    {
        if ($idLinea === null) {
            return;
        }
        if ($idLinea <= 0) {
            throw new InvalidArgumentException('La línea principal debe ser un entero positivo o NULL.');
        }
        if ($this->repositorioLinea->obtenerPorId($idLinea) === null) {
            throw new ConflictoExcepcion(
                'La línea de investigación indicada no existe o está inactiva.'
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
        if (array_key_exists('linea_investigacion_principal', $datos)
            && $datos['linea_investigacion_principal'] !== null
            && (!is_int($datos['linea_investigacion_principal'])
                || $datos['linea_investigacion_principal'] <= 0)) {
            throw new InvalidArgumentException(
                'linea_investigacion_principal debe ser entero positivo o NULL.'
            );
        }
    }
}
