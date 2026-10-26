<?php

declare(strict_types=1);

require_once __DIR__ . '/../servicios/Servicios_ParticipaGrupo/IServicioParticipaGrupo.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../excepciones/ConflictoExcepcion.php';

/** Controlador HTTP de participa_grupo: no ejecuta consultas SQL. */
class ControladorParticipaGrupo
{
    private IServicioParticipaGrupo $servicio;

    private const CAMPOS_EDITABLES = ['rol', 'fecha_inicio', 'fecha_fin'];

    public function __construct(IServicioParticipaGrupo $servicio)
    {
        $this->servicio = $servicio;
    }

    public function listar(): void
    {
        try {
            $participaciones = $this->servicio->listar();
            if ($participaciones === []) {
                http_response_code(204);
                return;
            }
            $datos = array_map(
                static fn (ParticipaGrupo $p): array => $p->toArray(),
                $participaciones
            );
            $this->responderJson(200, [
                'recurso' => 'participa_grupo',
                'total' => count($datos),
                'datos' => $datos,
            ]);
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    public function obtener(int $docenteCedula, int $grupoId): void
    {
        try {
            $p = $this->servicio->obtenerPorClave($docenteCedula, $grupoId);
            $this->responderJson(200, $p->toArray());
        } catch (NoEncontradoExcepcion $excepcion) {
            $this->responderNoEncontrado();
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    /** @param array<string, mixed> $cuerpo */
    public function crear(array $cuerpo): void
    {
        $errores = $this->validarCuerpo($cuerpo, true, true);
        if ($errores !== []) {
            $this->responderValidacion($errores);
            return;
        }
        try {
            $participacion = new ParticipaGrupo(
                $cuerpo['docente_cedula'],
                $cuerpo['grupo_investigacion_id'],
                $cuerpo['rol'],
                $cuerpo['fecha_inicio'],
                $cuerpo['fecha_fin']
            );
            $creada = $this->servicio->crear($participacion);
            $this->responderJson(201, [
                'estado' => 201,
                'mensaje' => 'Participación en grupo creada exitosamente.',
                'datos' => $creada->toArray(),
            ]);
        } catch (ConflictoExcepcion $excepcion) {
            $this->responderConflicto();
        } catch (InvalidArgumentException $excepcion) {
            $this->responderValidacion([$excepcion->getMessage()]);
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    /** @param array<string, mixed> $cuerpo */
    public function reemplazar(int $docenteCedula, int $grupoId, array $cuerpo): void
    {
        $errores = $this->validarCuerpo($cuerpo, false, true);
        if ($errores !== []) {
            $this->responderValidacion($errores);
            return;
        }
        try {
            $filas = $this->servicio->reemplazar($docenteCedula, $grupoId, $cuerpo);
            $this->responderJson(200, [
                'estado' => 200,
                'mensaje' => 'Participación en grupo reemplazada.',
                'filasAfectadas' => $filas,
            ]);
        } catch (NoEncontradoExcepcion $excepcion) {
            $this->responderNoEncontrado();
        } catch (ConflictoExcepcion $excepcion) {
            $this->responderConflicto();
        } catch (InvalidArgumentException $excepcion) {
            $this->responderValidacion([$excepcion->getMessage()]);
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    /** @param array<string, mixed> $cuerpo */
    public function actualizar(int $docenteCedula, int $grupoId, array $cuerpo): void
    {
        $errores = $this->validarCuerpo($cuerpo, false, false);
        if ($errores !== []) {
            $this->responderValidacion($errores);
            return;
        }
        try {
            $filas = $this->servicio->actualizar($docenteCedula, $grupoId, $cuerpo);
            $this->responderJson(200, [
                'estado' => 200,
                'mensaje' => 'Participación en grupo actualizada.',
                'filasAfectadas' => $filas,
            ]);
        } catch (NoEncontradoExcepcion $excepcion) {
            $this->responderNoEncontrado();
        } catch (ConflictoExcepcion $excepcion) {
            $this->responderConflicto();
        } catch (InvalidArgumentException $excepcion) {
            $this->responderValidacion([$excepcion->getMessage()]);
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    public function retirar(int $docenteCedula, int $grupoId): void
    {
        try {
            $filas = $this->servicio->retirar($docenteCedula, $grupoId);
            $this->responderJson(200, [
                'estado' => 200,
                'mensaje' => 'Participación en grupo retirada.',
                'filasAfectadas' => $filas,
            ]);
        } catch (NoEncontradoExcepcion $excepcion) {
            $this->responderNoEncontrado();
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    /**
     * POST: ambas claves + rol + fecha_inicio + fecha_fin (nullable).
     * PUT: solo rol + fecha_inicio + fecha_fin.
     * PATCH: un subconjunto no vacío de esos tres campos.
     * Nunca permite modificar las claves ni activo mediante PUT/PATCH.
     * @param array<string, mixed> $cuerpo
     * @return string[]
     */
    private function validarCuerpo(array $cuerpo, bool $incluyeClaves, bool $completo): array
    {
        $permitidos = self::CAMPOS_EDITABLES;
        if ($incluyeClaves) {
            $permitidos[] = 'docente_cedula';
            $permitidos[] = 'grupo_investigacion_id';
        }
        $errores = [];
        foreach (array_keys($cuerpo) as $campo) {
            if (!in_array($campo, $permitidos, true)) {
                $errores[] = 'El campo ' . $campo . ' no está permitido.';
            }
        }
        if (!$completo && $cuerpo === []) {
            $errores[] = 'Debes enviar al menos un campo para actualizar.';
        }
        if ($incluyeClaves) {
            foreach (['docente_cedula', 'grupo_investigacion_id'] as $campo) {
                if (!array_key_exists($campo, $cuerpo)) {
                    $errores[] = 'El campo ' . $campo . ' es obligatorio.';
                } elseif (!is_int($cuerpo[$campo]) || $cuerpo[$campo] <= 0) {
                    $errores[] = $campo . ' debe ser un entero positivo.';
                }
            }
        }
        foreach (self::CAMPOS_EDITABLES as $campo) {
            if (!array_key_exists($campo, $cuerpo)) {
                if ($completo) {
                    $errores[] = 'El campo ' . $campo . ' es obligatorio (acepta NULL solo para fecha_fin).';
                }
                continue;
            }
            $valor = $cuerpo[$campo];
            if ($campo === 'rol' && (!is_string($valor) || trim($valor) === ''
                || $this->longitud($valor) > 15)) {
                $errores[] = 'rol debe tener entre 1 y 15 caracteres.';
            }
            if ($campo === 'fecha_inicio' && !$this->fechaValida($valor)) {
                $errores[] = 'fecha_inicio debe ser una fecha válida YYYY-MM-DD.';
            }
            if ($campo === 'fecha_fin' && $valor !== null && !$this->fechaValida($valor)) {
                $errores[] = 'fecha_fin debe ser NULL o una fecha válida YYYY-MM-DD.';
            }
        }
        if (array_key_exists('fecha_inicio', $cuerpo)
            && array_key_exists('fecha_fin', $cuerpo)
            && $this->fechaValida($cuerpo['fecha_inicio'])
            && ($cuerpo['fecha_fin'] === null || $this->fechaValida($cuerpo['fecha_fin']))
            && $cuerpo['fecha_fin'] !== null
            && $cuerpo['fecha_fin'] < $cuerpo['fecha_inicio']) {
            $errores[] = 'fecha_fin no puede ser anterior a fecha_inicio.';
        }
        return $errores;
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
            'mensaje' => 'Participación en grupo no encontrada.',
            'detalle' => 'No existe una participación activa con esa clave compuesta.',
        ]);
    }

    private function responderConflicto(): void
    {
        $this->responderJson(409, [
            'estado' => 409,
            'mensaje' => 'Conflicto de datos.',
            'detalle' => 'La pareja docente-grupo ya está registrada o una de las referencias no está activa.',
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
        echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
