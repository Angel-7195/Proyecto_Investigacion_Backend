<?php

declare(strict_types=1);

require_once __DIR__ . '/../servicios/Servicios_TerminoClave/IServicioTerminoClave.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../excepciones/ConflictoExcepcion.php';

class ControladorTerminoClave
{
    private IServicioTerminoClave $servicio;

    public function __construct(IServicioTerminoClave $servicio)
    {
        $this->servicio = $servicio;
    }

    public function listar(): void
    {
        try {
            $terminos = $this->servicio->listar();

            if ($terminos === []) {
                http_response_code(204);
                return;
            }

            $datos = [];

            foreach ($terminos as $terminoClave) {
                $datos[] = $terminoClave->toArray();
            }

            $this->responderJson(200, [
                'recurso' => 'termino_clave',
                'total' => count($datos),
                'datos' => $datos,
            ]);
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    public function obtener(string $termino): void
    {
        try {
            $terminoClave = $this->servicio->obtenerPorTermino($termino);

            $this->responderJson(
                200,
                $terminoClave->toArray()
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
            $terminoClave = new TerminoClave(
                $cuerpo['termino'],
                $cuerpo['termino_ingles'] ?? null
            );

            $creado = $this->servicio->crear($terminoClave);

            $this->responderJson(201, [
                'estado' => 201,
                'mensaje' => 'Término clave creado exitosamente.',
                'datos' => $creado->toArray(),
            ]);
        } catch (ConflictoExcepcion $excepcion) {
            $this->responderJson(409, [
                'estado' => 409,
                'mensaje' => 'Conflicto de datos.',
                'detalle' => 'Ya existe un término clave con esa llave.',
            ]);
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    public function reemplazar(
        string $termino,
        array $cuerpo
    ): void {
        $errores = $this->validarEdicion($cuerpo, true);

        if ($errores !== []) {
            $this->responderValidacion($errores);
            return;
        }

        try {
            $filasAfectadas = $this->servicio->reemplazar(
                $termino,
                $cuerpo['termino_ingles']
            );

            $this->responderJson(200, [
                'estado' => 200,
                'mensaje' => 'Término clave reemplazado.',
                'filasAfectadas' => $filasAfectadas,
            ]);
        } catch (NoEncontradoExcepcion $excepcion) {
            $this->responderNoEncontrado();
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    public function actualizar(
        string $termino,
        array $cuerpo
    ): void {
        $errores = $this->validarEdicion($cuerpo, false);

        if ($errores !== []) {
            $this->responderValidacion($errores);
            return;
        }

        try {
            $filasAfectadas = $this->servicio->actualizar(
                $termino,
                $cuerpo
            );

            $this->responderJson(200, [
                'estado' => 200,
                'mensaje' => 'Término clave actualizado.',
                'filasAfectadas' => $filasAfectadas,
            ]);
        } catch (InvalidArgumentException $excepcion) {
            $this->responderJson(400, [
                'estado' => 400,
                'mensaje' => 'Parámetros inválidos.',
                'detalle' => $excepcion->getMessage(),
            ]);
        } catch (NoEncontradoExcepcion $excepcion) {
            $this->responderNoEncontrado();
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    public function retirar(string $termino): void
    {
        try {
            $filasAfectadas = $this->servicio->retirar($termino);

            $this->responderJson(200, [
                'estado' => 200,
                'mensaje' => 'Término clave retirado.',
                'filasAfectadas' => $filasAfectadas,
            ]);
        } catch (NoEncontradoExcepcion $excepcion) {
            $this->responderNoEncontrado();
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    private function validarCreacion(array $cuerpo): array
    {
        $errores = $this->validarCamposPermitidos(
            $cuerpo,
            ['termino', 'termino_ingles']
        );

        if (!array_key_exists('termino', $cuerpo)) {
            $errores[] = 'El campo termino es obligatorio.';
        } else {
            $termino = $cuerpo['termino'];

            if (
                !is_string($termino)
                || trim($termino) === ''
                || mb_strlen($termino) > 30
            ) {
                $errores[] =
                    'El campo termino debe ser un texto de 1 a 30 caracteres.';
            }
        }

        if (array_key_exists('termino_ingles', $cuerpo)) {
            $error = $this->validarTerminoIngles(
                $cuerpo['termino_ingles']
            );

            if ($error !== null) {
                $errores[] = $error;
            }
        }

        return $errores;
    }

    private function validarEdicion(
        array $cuerpo,
        bool $obligatorio
    ): array {
        $errores = $this->validarCamposPermitidos(
            $cuerpo,
            ['termino_ingles']
        );

        if (array_key_exists('termino_ingles', $cuerpo)) {
            $error = $this->validarTerminoIngles(
                $cuerpo['termino_ingles']
            );

            if ($error !== null) {
                $errores[] = $error;
            }
        } elseif ($obligatorio) {
            $errores[] = 'El campo termino_ingles es obligatorio.';
        }

        return $errores;
    }

    private function validarTerminoIngles(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        if (
            !is_string($valor)
            || trim($valor) === ''
            || mb_strlen($valor) > 30
        ) {
            return 'El campo termino_ingles debe ser null o un texto de 1 a 30 caracteres.';
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
                $errores[] = "El campo {$campo} no está permitido.";
            }
        }

        return $errores;
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
            'mensaje' => 'Término clave no encontrado.',
            'detalle' =>
                'No existe un término clave activo con la llave solicitada.',
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

    private function responderJson(
        int $estado,
        array $cuerpo
    ): void {
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