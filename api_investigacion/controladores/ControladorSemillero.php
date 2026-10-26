<?php

declare(strict_types=1);

require_once __DIR__ . '/../servicios/Servicios_Semillero/IServicioSemillero.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../excepciones/ConflictoExcepcion.php';

/**
 * Valida las solicitudes HTTP de semillero y delega en el servicio.
 * No realiza operaciones SQL.
 */
class ControladorSemillero
{
    private IServicioSemillero $servicio;

    private const CAMPOS_EDITABLES = [
        'nombre', 'fecha_fundacion', 'grupo_investigacion',
    ];

    public function __construct(IServicioSemillero $servicio)
    {
        $this->servicio = $servicio;
    }

    public function listar(): void
    {
        try {
            $semilleros = $this->servicio->listar();
            if ($semilleros === []) {
                http_response_code(204);
                return;
            }

            $datos = [];
            foreach ($semilleros as $semillero) {
                $datos[] = $semillero->toArray();
            }
            $this->responderJson(200, [
                'recurso' => 'semillero',
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
            $semillero = $this->servicio->obtenerPorId($id);
            $this->responderJson(200, $semillero->toArray());
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
            $semillero = new Semillero(
                $cuerpo['id'],
                $cuerpo['nombre'],
                $cuerpo['fecha_fundacion'],
                $cuerpo['grupo_investigacion']
            );
            $creado = $this->servicio->crear($semillero);
            $this->responderJson(201, [
                'estado' => 201,
                'mensaje' => 'Semillero creado exitosamente.',
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
                'mensaje' => 'Semillero reemplazado.',
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
                'mensaje' => 'Semillero actualizado.',
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

    public function retirar(int $id): void
    {
        try {
            $filas = $this->servicio->retirar($id);
            $this->responderJson(200, [
                'estado' => 200,
                'mensaje' => 'Semillero retirado.',
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
     * POST: id y tres campos obligatorios.
     * PUT: los tres campos editables.
     * PATCH: solamente los campos enviados (al menos uno).
     * Ni id ni activo son editables.
     * @return string[]
     */
    private function validarCuerpo(array $cuerpo, bool $incluyeId, bool $completo): array
    {
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

        if (!$completo && $cuerpo === []) {
            $errores[] = 'Debes enviar al menos un campo para actualizar.';
        }

        if ($incluyeId) {
            if (!array_key_exists('id', $cuerpo)) {
                $errores[] = 'El campo id es obligatorio.';
            } elseif (!is_int($cuerpo['id']) || $cuerpo['id'] <= 0) {
                $errores[] = 'El campo id debe ser un entero positivo.';
            }
        }

        foreach (self::CAMPOS_EDITABLES as $campo) {
            if (!array_key_exists($campo, $cuerpo)) {
                if ($completo) {
                    $errores[] = "El campo {$campo} es obligatorio.";
                }
                continue;
            }

            $valor = $cuerpo[$campo];
            if ($campo === 'nombre' && !$this->esTextoValido($valor, 60)) {
                $errores[] = 'El nombre debe tener entre 1 y 60 caracteres.';
            } elseif ($campo === 'fecha_fundacion' && !$this->esFechaValida($valor)) {
                $errores[] = 'fecha_fundacion debe tener formato YYYY-MM-DD y ser válida.';
            } elseif ($campo === 'grupo_investigacion'
                && (!is_int($valor) || $valor <= 0)) {
                $errores[] = 'grupo_investigacion debe ser un entero positivo.';
            }
        }
        return $errores;
    }

    private function esTextoValido(mixed $valor, int $maximo): bool
    {
        if (!is_string($valor) || trim($valor) === '') {
            return false;
        }
        $longitud = function_exists('mb_strlen')
            ? mb_strlen($valor, 'UTF-8')
            : preg_match_all('/./us', $valor);
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
            'mensaje' => 'Semillero no encontrado.',
            'detalle' => 'No existe un semillero activo con el id solicitado.',
        ]);
    }

    private function responderConflicto(): void
    {
        $this->responderJson(409, [
            'estado' => 409,
            'mensaje' => 'Conflicto de datos.',
            'detalle' => 'El identificador ya existe o el grupo relacionado no existe o está inactivo.',
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
