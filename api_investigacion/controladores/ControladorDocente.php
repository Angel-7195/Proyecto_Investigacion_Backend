<?php

declare(strict_types=1);

require_once __DIR__ . '/../servicios/Servicios_Docente/IServicioDocente.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../excepciones/ConflictoExcepcion.php';

/** Interpreta HTTP y valida cuerpos JSON sin acceder a SQL. */
class ControladorDocente
{
    private IServicioDocente $servicio;

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

    private const MAXIMOS = [
        'nombres' => 60,
        'apellidos' => 60,
        'genero' => 12,
        'cargo' => 30,
        'correo' => 70,
        'telefono' => 20,
        'url_cvlac' => 128,
        'escalafon' => 45,
        'cat_minciencia' => 45,
        'conv_minciencia' => 45,
        'nacionalidaad' => 45,
    ];

    private const CAMPOS_OPCIONALES = [
        'cat_minciencia', 'linea_investigacion_principal',
    ];

    public function __construct(IServicioDocente $servicio)
    {
        $this->servicio = $servicio;
    }

    public function listar(): void
    {
        try {
            $docentes = $this->servicio->listar();
            if ($docentes === []) {
                http_response_code(204);
                return;
            }
            $datos = [];
            foreach ($docentes as $docente) {
                $datos[] = $docente->toArray();
            }
            $this->responderJson(200, [
                'recurso' => 'docente',
                'total' => count($datos),
                'datos' => $datos,
            ]);
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    public function obtener(int $cedula): void
    {
        try {
            $docente = $this->servicio->obtenerPorCedula($cedula);
            $this->responderJson(200, $docente->toArray());
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
            $docente = new Docente(
                $cuerpo['cedula'],
                $cuerpo['nombres'],
                $cuerpo['apellidos'],
                $cuerpo['genero'],
                $cuerpo['cargo'],
                $cuerpo['fecha_nacimiento'],
                $cuerpo['correo'],
                $cuerpo['telefono'],
                $cuerpo['url_cvlac'],
                $cuerpo['fecha_actualizacion'],
                $cuerpo['escalafon'],
                $cuerpo['perfil'],
                $cuerpo['cat_minciencia'],
                $cuerpo['conv_minciencia'],
                $cuerpo['nacionalidaad'],
                $cuerpo['linea_investigacion_principal'],
            );
            $creado = $this->servicio->crear($docente);
            $this->responderJson(201, [
                'estado' => 201,
                'mensaje' => 'Docente creado exitosamente.',
                'datos' => $creado->toArray(),
            ]);
        } catch (ConflictoExcepcion $excepcion) {
            $this->responderConflicto();
        } catch (InvalidArgumentException $excepcion) {
            $this->responderArgumentosInvalidos($excepcion);
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    public function reemplazar(int $cedula, array $cuerpo): void
    {
        $errores = $this->validarCuerpo($cuerpo, false, true);
        if ($errores !== []) {
            $this->responderValidacion($errores);
            return;
        }
        try {
            $filas = $this->servicio->reemplazar($cedula, $cuerpo);
            $this->responderJson(200, [
                'estado' => 200,
                'mensaje' => 'Docente reemplazado.',
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

    public function actualizar(int $cedula, array $cuerpo): void
    {
        $errores = $this->validarCuerpo($cuerpo, false, false);
        if ($errores !== []) {
            $this->responderValidacion($errores);
            return;
        }
        try {
            $filas = $this->servicio->actualizar($cedula, $cuerpo);
            $this->responderJson(200, [
                'estado' => 200,
                'mensaje' => 'Docente actualizado.',
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

    public function retirar(int $cedula): void
    {
        try {
            $filas = $this->servicio->retirar($cedula);
            $this->responderJson(200, [
                'estado' => 200,
                'mensaje' => 'Docente retirado.',
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
     * POST: cédula + 15 campos editables (incluidos NULL explícitos).
     * PUT: los 15 campos editables. PATCH: mínimo uno.
     * @return string[]
     */
    private function validarCuerpo(array $cuerpo, bool $incluyeCedula, bool $completo): array
    {
        $permitidos = self::CAMPOS_EDITABLES;
        if ($incluyeCedula) {
            $permitidos[] = 'cedula';
        }
        $errores = [];
        foreach (array_keys($cuerpo) as $campo) {
            if (!in_array($campo, $permitidos, true)) {
                $errores[] = "El campo {$campo} no está permitido.";
            }
        }
        if (!$completo && $cuerpo === []) {
            // Se conserva el contrato de V1: error de forma HTTP 422.
            $errores[] = 'Debes enviar al menos un campo para actualizar.';
        }
        if ($incluyeCedula) {
            if (!array_key_exists('cedula', $cuerpo)) {
                $errores[] = 'El campo cedula es obligatorio.';
            } elseif (!is_int($cuerpo['cedula']) || $cuerpo['cedula'] <= 0) {
                $errores[] = 'cedula debe ser un entero positivo.';
            }
        }

        foreach (self::CAMPOS_EDITABLES as $campo) {
            if (!array_key_exists($campo, $cuerpo)) {
                if ($completo) {
                    $errores[] = "El campo {$campo} es obligatorio (puede ser NULL si es opcional).";
                }
                continue;
            }
            $valor = $cuerpo[$campo];
            if (in_array($campo, self::CAMPOS_OPCIONALES, true) && $valor === null) {
                continue;
            }
            if ($campo === 'linea_investigacion_principal') {
                if (!is_int($valor) || $valor <= 0) {
                    $errores[] = 'linea_investigacion_principal debe ser entero positivo o NULL.';
                }
                continue;
            }
            if ($campo === 'fecha_nacimiento' || $campo === 'fecha_actualizacion') {
                if (!$this->esFechaValida($valor)) {
                    $errores[] = "{$campo} debe ser una fecha válida YYYY-MM-DD.";
                }
                continue;
            }
            $limite = self::MAXIMOS[$campo] ?? null;
            if (!$this->esTextoValido($valor, $limite)) {
                $longitud = $limite === null ? 'no vacío' : "de 1 a {$limite} caracteres";
                $errores[] = "{$campo} debe ser texto {$longitud}.";
                continue;
            }
            if ($campo === 'correo' && filter_var($valor, FILTER_VALIDATE_EMAIL) === false) {
                $errores[] = 'correo debe contener una dirección de correo válida.';
            }
        }
        return $errores;
    }

    private function esTextoValido(mixed $valor, ?int $maximo): bool
    {
        if (!is_string($valor) || trim($valor) === '') {
            return false;
        }
        if ($maximo === null) {
            return true; // perfil es LONGTEXT; sin límite VARCHAR en el DDL.
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
            'mensaje' => 'Docente no encontrado.',
            'detalle' => 'No existe un docente activo con la cédula solicitada.',
        ]);
    }

    private function responderConflicto(): void
    {
        $this->responderJson(409, [
            'estado' => 409,
            'mensaje' => 'Conflicto de datos.',
            'detalle' => 'La cédula ya existe o la línea relacionada no existe o está inactiva.',
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
