<?php

declare(strict_types=1);

require_once __DIR__ . '/../servicios/Servicios_GrupoInvestigacion/IServicioGrupoInvestigacion.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../excepciones/ConflictoExcepcion.php';

/**
 * Recibe las peticiones HTTP del recurso grupo_investigacion.
 * Valida el cuerpo JSON y delega las reglas de negocio al servicio.
 * No contiene consultas SQL.
 */
class ControladorGrupoInvestigacion
{
    private IServicioGrupoInvestigacion $servicio;

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

    public function __construct(IServicioGrupoInvestigacion $servicio)
    {
        $this->servicio = $servicio;
    }

    public function listar(): void
    {
        try {
            $grupos = $this->servicio->listar();

            if ($grupos === []) {
                http_response_code(204);
                return;
            }

            $datos = [];

            foreach ($grupos as $grupo) {
                $datos[] = $grupo->toArray();
            }

            $this->responderJson(200, [
                'recurso' => 'grupo_investigacion',
                'total' => count($datos),
                'datos' => $datos,
            ]);
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    public function obtener(int $id): void
    {
        try {
            $grupo = $this->servicio->obtenerPorId($id);
            $this->responderJson(200, $grupo->toArray());
        } catch (NoEncontradoExcepcion $excepcion) {
            $this->responderNoEncontrado();
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    public function crear(array $cuerpo): void
    {
        $errores = $this->validarCuerpo($cuerpo, true, true);

        if ($errores !== []) {
            $this->responderValidacion($errores);
            return;
        }

        try {
            $grupo = new GrupoInvestigacion(
                $cuerpo['id'],
                $cuerpo['nombre'],
                $cuerpo['url_gruplac'] ?? null,
                $cuerpo['categoria'] ?? null,
                $cuerpo['convocatoria'] ?? null,
                $cuerpo['fecha_fundacion'],
                $cuerpo['universidsad'] ?? null,
                $cuerpo['interno'],
                $cuerpo['ambito']
            );

            $creado = $this->servicio->crear($grupo);

            $this->responderJson(201, [
                'estado' => 201,
                'mensaje' => 'Grupo de investigación creado exitosamente.',
                'datos' => $creado->toArray(),
            ]);
        } catch (ConflictoExcepcion $excepcion) {
            $this->responderConflicto();
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    public function reemplazar(int $id, array $cuerpo): void
    {
        $errores = $this->validarCuerpo($cuerpo, false, true);

        if ($errores !== []) {
            $this->responderValidacion($errores);
            return;
        }

        try {
            $filas = $this->servicio->reemplazar($id, $cuerpo);

            $this->responderJson(200, [
                'estado' => 200,
                'mensaje' => 'Grupo de investigación reemplazado.',
                'filasAfectadas' => $filas,
            ]);
        } catch (NoEncontradoExcepcion $excepcion) {
            $this->responderNoEncontrado();
        } catch (ConflictoExcepcion $excepcion) {
            $this->responderConflicto();
        } catch (InvalidArgumentException $excepcion) {
            $this->responderArgumentosInvalidos($excepcion);
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    public function actualizar(int $id, array $cuerpo): void
    {
        $errores = $this->validarCuerpo($cuerpo, false, false);

        if ($errores !== []) {
            $this->responderValidacion($errores);
            return;
        }

        try {
            $filas = $this->servicio->actualizar($id, $cuerpo);

            $this->responderJson(200, [
                'estado' => 200,
                'mensaje' => 'Grupo de investigación actualizado.',
                'filasAfectadas' => $filas,
            ]);
        } catch (InvalidArgumentException $excepcion) {
            $this->responderArgumentosInvalidos($excepcion);
        } catch (NoEncontradoExcepcion $excepcion) {
            $this->responderNoEncontrado();
        } catch (ConflictoExcepcion $excepcion) {
            $this->responderConflicto();
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    public function retirar(int $id): void
    {
        try {
            $filas = $this->servicio->retirar($id);

            $this->responderJson(200, [
                'estado' => 200,
                'mensaje' => 'Grupo de investigación retirado.',
                'filasAfectadas' => $filas,
            ]);
        } catch (NoEncontradoExcepcion $excepcion) {
            $this->responderNoEncontrado();
        } catch (ConflictoExcepcion $excepcion) {
            $this->responderConflicto();
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    /**
     * POST: id y los campos obligatorios. Los opcionales pueden omitirse.
     * PUT: todos los campos editables, incluidos los que admiten null.
     * PATCH: solo los campos enviados.
     * El campo activo es interno y nunca se acepta en el cuerpo.
     *
     * @return string[]
     */
    private function validarCuerpo(
        array $cuerpo,
        bool $incluyeId,
        bool $obligatorios
    ): array {
        $permitidos = self::CAMPOS_EDITABLES;
        if ($incluyeId) {
            $permitidos[] = 'id';
        }

        $errores = [];

        foreach (array_keys($cuerpo) as $campo) {
            if (!in_array($campo, $permitidos, true)) {
                $errores[] = "El campo {$campo} no está permitido.";
            }
        }

        if ($incluyeId) {
            if (!array_key_exists('id', $cuerpo)) {
                $errores[] = 'El campo id es obligatorio.';
            } elseif (!is_int($cuerpo['id'])) {
                $errores[] = 'El campo id debe ser un entero.';
            }
        }

        // Los campos opcionales pueden omitirse en POST, pero en PUT
        // deben figurar explícitamente aunque su valor sea null.
        foreach (self::CAMPOS_EDITABLES as $campo) {
            if (!array_key_exists($campo, $cuerpo)) {
                $requerido = $obligatorios && (
                    !$incluyeId || in_array(
                        $campo,
                        ['nombre', 'fecha_fundacion', 'interno', 'ambito'],
                        true
                    )
                );

                if ($requerido) {
                    $errores[] = "El campo {$campo} es obligatorio.";
                }
                continue;
            }

            $valor = $cuerpo[$campo];

            if (in_array($campo, ['nombre', 'ambito'], true)) {
                $limite = $campo === 'nombre' ? 60 : 45;
                if (!$this->esTextoValido($valor, $limite)) {
                    $errores[] = "El campo {$campo} debe ser un texto de 1 a {$limite} caracteres.";
                }
                continue;
            }

            if (in_array($campo, ['url_gruplac', 'categoria', 'convocatoria'], true)) {
                $limites = ['url_gruplac' => 128, 'categoria' => 10, 'convocatoria' => 10];
                $limite = $limites[$campo];
                if ($valor !== null && !$this->esTextoValido($valor, $limite)) {
                    $errores[] = "El campo {$campo} debe ser null o un texto de 1 a {$limite} caracteres.";
                }
                continue;
            }

            if ($campo === 'universidsad') {
                if ($valor !== null && !is_int($valor)) {
                    $errores[] = 'El campo universidsad debe ser un entero o null.';
                }
                continue;
            }

            if ($campo === 'interno') {
                if (!is_int($valor) || !in_array($valor, [0, 1], true)) {
                    $errores[] = 'El campo interno debe ser 0 o 1.';
                }
                continue;
            }

            if ($campo === 'fecha_fundacion' && !$this->esFechaValida($valor)) {
                $errores[] = 'El campo fecha_fundacion debe ser una fecha válida con formato YYYY-MM-DD.';
            }
        }

        return $errores;
    }

    private function esTextoValido(mixed $valor, int $maximo): bool
    {
        if (!is_string($valor) || trim($valor) === '') {
            return false;
        }

        if (function_exists('mb_strlen')) {
            $longitud = mb_strlen($valor, 'UTF-8');
        } else {
            $longitud = preg_match_all('/./us', $valor);
        }

        return $longitud !== false && $longitud <= $maximo;
    }

    private function esFechaValida(mixed $valor): bool
    {
        if (!is_string($valor)) {
            return false;
        }

        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
        return $fecha !== false && $fecha->format('Y-m-d') === $valor;
    }

    private function responderValidacion(array $errores): void
    {
        $this->responderJson(422, [
            'estado' => 422,
            'mensaje' => 'Datos inválidos.',
            'errores' => $errores,
        ]);
    }

    private function responderNoEncontrado(): void
    {
        $this->responderJson(404, [
            'estado' => 404,
            'mensaje' => 'Grupo de investigación no encontrado.',
            'detalle' => 'No existe un grupo de investigación activo con el id solicitado.',
        ]);
    }

    private function responderConflicto(): void
    {
        $this->responderJson(409, [
            'estado' => 409,
            'mensaje' => 'Conflicto de datos.',
            'detalle' => 'El identificador ya existe o la universidad relacionada no existe o está inactiva.',
        ]);
    }

    private function responderArgumentosInvalidos(InvalidArgumentException $excepcion): void
    {
        $this->responderJson(400, [
            'estado' => 400,
            'mensaje' => 'Parámetros inválidos.',
            'detalle' => $excepcion->getMessage(),
        ]);
    }

    private function responderErrorInterno(): void
    {
        $this->responderJson(500, [
            'estado' => 500,
            'mensaje' => 'Error interno del servidor.',
            'detalle' => 'Ocurrió un problema al procesar la solicitud.',
        ]);
    }

    private function responderJson(int $estado, array $cuerpo): void
    {
        http_response_code($estado);

        if (PHP_SAPI !== 'cli') {
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode(
            $cuerpo,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
}
