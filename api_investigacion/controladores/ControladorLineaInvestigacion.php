<?php

declare(strict_types=1);

require_once __DIR__ . '/../servicios/Servicios_LineaInvestigacion/IServicioLineaInvestigacion.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

class ControladorLineaInvestigacion
{
    private IServicioLineaInvestigacion $servicio;

    public function __construct(
        IServicioLineaInvestigacion $servicio
    ) {
        $this->servicio = $servicio;
    }

    public function listar(): void
    {
        try {
            $lineas = $this->servicio->listar();

            if ($lineas === []) {
                http_response_code(204);
                return;
            }

            $datos = [];

            foreach ($lineas as $linea) {
                $datos[] = $linea->toArray();
            }

            $this->responderJson(
                200,
                [
                    'recurso' => 'linea_investigacion',
                    'total' => count($datos),
                    'datos' => $datos,
                ]
            );
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    public function obtener(int $id): void
    {
        try {
            $linea = $this->servicio->obtenerPorId($id);

            $this->responderJson(
                200,
                $linea->toArray()
            );
        } catch (NoEncontradoExcepcion $excepcion) {
            $this->responderNoEncontrado();
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    public function crear(array $cuerpo): void
    {
        $errores = $this->validarCreacion($cuerpo);

        if ($errores !== []) {
            $this->responderValidacion($errores);
            return;
        }

        try {
            $linea = new LineaInvestigacion(
                null,
                $cuerpo['nombre'],
                $cuerpo['descripcion']
            );

            $creada = $this->servicio->crear($linea);

            $this->responderJson(
                201,
                [
                    'estado' => 201,
                    'mensaje' => 'Línea de investigación creada exitosamente.',
                    'datos' => $creada->toArray(),
                ]
            );
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    public function reemplazar(
        int $id,
        array $cuerpo
    ): void {
        $errores = $this->validarEdicion(
            $cuerpo,
            true
        );

        if ($errores !== []) {
            $this->responderValidacion($errores);
            return;
        }

        try {
            $filas = $this->servicio->reemplazar(
                $id,
                $cuerpo
            );

            $this->responderJson(
                200,
                [
                    'estado' => 200,
                    'mensaje' => 'Línea de investigación reemplazada.',
                    'filasAfectadas' => $filas,
                ]
            );
        } catch (NoEncontradoExcepcion $excepcion) {
            $this->responderNoEncontrado();
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    public function actualizar(
        int $id,
        array $cuerpo
    ): void {
        $errores = $this->validarEdicion(
            $cuerpo,
            false
        );

        if ($errores !== []) {
            $this->responderValidacion($errores);
            return;
        }

        try {
            $filas = $this->servicio->actualizar(
                $id,
                $cuerpo
            );

            $this->responderJson(
                200,
                [
                    'estado' => 200,
                    'mensaje' => 'Línea de investigación actualizada.',
                    'filasAfectadas' => $filas,
                ]
            );
        } catch (InvalidArgumentException $excepcion) {
            $this->responderJson(
                400,
                [
                    'estado' => 400,
                    'mensaje' => 'Parámetros inválidos.',
                    'detalle' => $excepcion->getMessage(),
                ]
            );
        } catch (NoEncontradoExcepcion $excepcion) {
            $this->responderNoEncontrado();
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    public function retirar(int $id): void
    {
        try {
            $filas = $this->servicio->retirar($id);

            $this->responderJson(
                200,
                [
                    'estado' => 200,
                    'mensaje' => 'Línea de investigación retirada.',
                    'filasAfectadas' => $filas,
                ]
            );
        } catch (NoEncontradoExcepcion $excepcion) {
            $this->responderNoEncontrado();
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    private function validarCreacion(array $cuerpo): array
    {
        /*
         * POST no acepta id porque MariaDB lo genera.
         * activo tampoco es un campo editable.
         */
        $errores = $this->validarCamposPermitidos(
            $cuerpo,
            [
                'nombre',
                'descripcion',
            ]
        );

        return array_merge(
            $errores,
            $this->validarCamposLinea(
                $cuerpo,
                true
            )
        );
    }

    private function validarEdicion(
        array $cuerpo,
        bool $obligatorios
    ): array {
        $errores = $this->validarCamposPermitidos(
            $cuerpo,
            [
                'nombre',
                'descripcion',
            ]
        );

        return array_merge(
            $errores,
            $this->validarCamposLinea(
                $cuerpo,
                $obligatorios
            )
        );
    }

    private function validarCamposLinea(
        array $cuerpo,
        bool $obligatorios
    ): array {
        $errores = [];

        $errorNombre = $this->validarTexto(
            $cuerpo,
            'nombre',
            45,
            $obligatorios
        );

        if ($errorNombre !== null) {
            $errores[] = $errorNombre;
        }

        $errorDescripcion = $this->validarTexto(
            $cuerpo,
            'descripcion',
            256,
            $obligatorios
        );

        if ($errorDescripcion !== null) {
            $errores[] = $errorDescripcion;
        }

        return $errores;
    }

    private function validarTexto(
        array $cuerpo,
        string $campo,
        int $maximo,
        bool $obligatorio
    ): ?string {
        if (!array_key_exists($campo, $cuerpo)) {
            if ($obligatorio) {
                return "El campo {$campo} es obligatorio.";
            }

            return null;
        }

        $valor = $cuerpo[$campo];

        if (
            !is_string($valor)
            || trim($valor) === ''
            || mb_strlen($valor) > $maximo
        ) {
            return "El campo {$campo} debe ser un texto de 1 a {$maximo} caracteres.";
        }

        return null;
    }

    private function validarCamposPermitidos(
        array $cuerpo,
        array $permitidos
    ): array {
        $errores = [];

        foreach (array_keys($cuerpo) as $campo) {
            if (!in_array($campo, $permitidos, true)) {
                $errores[] =
                    "El campo {$campo} no está permitido.";
            }
        }

        return $errores;
    }

    private function responderValidacion(
        array $errores
    ): void {
        $this->responderJson(
            422,
            [
                'estado' => 422,
                'mensaje' => 'Datos inválidos.',
                'errores' => $errores,
            ]
        );
    }

    private function responderNoEncontrado(): void
    {
        $this->responderJson(
            404,
            [
                'estado' => 404,
                'mensaje' => 'Línea de investigación no encontrada.',
                'detalle' => 'No existe una línea de investigación activa con el id solicitado.',
            ]
        );
    }

    private function responderErrorInterno(): void
    {
        $this->responderJson(
            500,
            [
                'estado' => 500,
                'mensaje' => 'Error interno del servidor.',
                'detalle' => 'Ocurrió un problema al procesar la solicitud.',
            ]
        );
    }

    private function responderJson(
        int $estado,
        array $cuerpo
    ): void {
        http_response_code($estado);

        if (PHP_SAPI !== 'cli') {
            header(
                'Content-Type: application/json; charset=utf-8'
            );
        }

        echo json_encode(
            $cuerpo,
            JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
        );
    }
}