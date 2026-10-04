<?php

declare(strict_types=1);

require_once __DIR__ . '/../servicios/IServicioAreaAplicacion.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../excepciones/ConflictoExcepcion.php';

class ControladorAreaAplicacion
{
    private IServicioAreaAplicacion $servicio;
    private const CAMPOS_TEXTO = ['nombre' => 150];

    public function __construct(IServicioAreaAplicacion $servicio)
    {
        $this->servicio = $servicio;
    }

    public function listar(): void
    {
        try {
            $registros = $this->servicio->listar();
            if ($registros === []) {
                http_response_code(204);
                return;
            }
            $datos = array_map(
                static fn(AreaAplicacion $registro): array => $registro->toArray(),
                $registros
            );
            $this->responder(200, [
                'recurso' => 'area_aplicacion',
                'total' => count($datos),
                'datos' => $datos,
            ]);
        } catch (Throwable $excepcion) {
            $this->errorInterno();
        }
    }

    public function obtener(int $id): void
    {
        try {
            $registro = $this->servicio->obtenerPorId($id);
            $this->responder(200, $registro->toArray());
        } catch (NoEncontradoExcepcion $excepcion) {
            $this->noEncontrado();
        } catch (Throwable $excepcion) {
            $this->errorInterno();
        }
    }

    public function crear(array $cuerpo): void
    {
        $errores = $this->validarCuerpo($cuerpo, true, true);
        if ($errores !== []) {
            $this->errorValidacion($errores);
            return;
        }
        try {
            $registro = new AreaAplicacion(
                $cuerpo['id'],
                $cuerpo['nombre']
            );
            $creado = $this->servicio->crear($registro);
            $this->responder(201, [
                'estado' => 201,
                'mensaje' => 'Área de aplicación creada exitosamente.',
                'datos' => $creado->toArray(),
            ]);
        } catch (ConflictoExcepcion $excepcion) {
            $this->responder(409, [
                'estado' => 409,
                'mensaje' => 'Conflicto de datos.',
                'detalle' => 'Ya existe un área de aplicación con esa llave.',
            ]);
        } catch (Throwable $excepcion) {
            $this->errorInterno();
        }
    }

    public function reemplazar(int $id, array $cuerpo): void
    {
        $errores = $this->validarCuerpo($cuerpo, false, true);
        if ($errores !== []) {
            $this->errorValidacion($errores);
            return;
        }
        try {
            $filas = $this->servicio->reemplazar($id, $cuerpo);
            $this->responder(200, [
                'estado' => 200,
                'mensaje' => 'Área de aplicación reemplazada.',
                'filasAfectadas' => $filas,
            ]);
        } catch (NoEncontradoExcepcion $excepcion) {
            $this->noEncontrado();
        } catch (Throwable $excepcion) {
            $this->errorInterno();
        }
    }

    public function actualizar(int $id, array $cuerpo): void
    {
        $errores = $this->validarCuerpo($cuerpo, false, false);
        if ($errores !== []) {
            $this->errorValidacion($errores);
            return;
        }
        try {
            $filas = $this->servicio->actualizar($id, $cuerpo);
            $this->responder(200, [
                'estado' => 200,
                'mensaje' => 'Área de aplicación actualizada.',
                'filasAfectadas' => $filas,
            ]);
        } catch (InvalidArgumentException $excepcion) {
            $this->responder(400, [
                'estado' => 400,
                'mensaje' => 'Parámetros inválidos.',
                'detalle' => $excepcion->getMessage(),
            ]);
        } catch (NoEncontradoExcepcion $excepcion) {
            $this->noEncontrado();
        } catch (Throwable $excepcion) {
            $this->errorInterno();
        }
    }

    public function retirar(int $id): void
    {
        try {
            $filas = $this->servicio->retirar($id);
            $this->responder(200, [
                'estado' => 200,
                'mensaje' => 'Área de aplicación retirada.',
                'filasAfectadas' => $filas,
            ]);
        } catch (NoEncontradoExcepcion $excepcion) {
            $this->noEncontrado();
        } catch (Throwable $excepcion) {
            $this->errorInterno();
        }
    }

    private function validarCuerpo(
        array $cuerpo,
        bool $incluyeId,
        bool $obligatorios
    ): array {
        $permitidos = ['nombre'];
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

        foreach (self::CAMPOS_TEXTO as $campo => $maximo) {
            if (!array_key_exists($campo, $cuerpo)) {
                if ($obligatorios) {
                    $errores[] = "El campo {$campo} es obligatorio.";
                }
                continue;
            }
            $valor = $cuerpo[$campo];
            if (!is_string($valor) || trim($valor) === '' || $this->largo($valor) > $maximo) {
                $errores[] = "El campo {$campo} debe ser un texto de 1 a {$maximo} caracteres.";
            }
        }
        return $errores;
    }

    private function largo(string $texto): int
    {
        return function_exists('mb_strlen')
            ? mb_strlen($texto, 'UTF-8')
            : iconv_strlen($texto, 'UTF-8');
    }

    private function errorValidacion(array $errores): void
    {
        $this->responder(422, [
            'estado' => 422,
            'mensaje' => 'Datos inválidos.',
            'errores' => $errores,
        ]);
    }

    private function noEncontrado(): void
    {
        $this->responder(404, [
            'estado' => 404,
            'mensaje' => 'Área de aplicación no encontrada.',
            'detalle' => 'No existe un área de aplicación activa con el id solicitado.',
        ]);
    }

    private function errorInterno(): void
    {
        $this->responder(500, [
            'estado' => 500,
            'mensaje' => 'Error interno del servidor.',
            'detalle' => 'Ocurrió un problema al procesar la solicitud.',
        ]);
    }

    private function responder(int $estado, array $contenido): void
    {
        http_response_code($estado);
        if (PHP_SAPI !== 'cli') {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(
            $contenido,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
}
