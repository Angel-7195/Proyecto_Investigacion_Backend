<?php

declare(strict_types=1);

require_once __DIR__ . '/../servicios/Servicios_Universidad/IServicioUniversidad.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../excepciones/ConflictoExcepcion.php';

class ControladorUniversidad
{
    private IServicioUniversidad $servicio;

    public function __construct(IServicioUniversidad $servicio)
    {
        $this->servicio = $servicio;
    }

    public function listar(): void
    {
        try {
            $universidades = $this->servicio->listar();

            if ($universidades === []) {
                http_response_code(204);
                return;
            }

            $datos = [];

            foreach ($universidades as $universidad) {
                $datos[] = $universidad->toArray();
            }

            $this->responderJson(200, [
                'recurso' => 'universidad',
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
            $universidad = $this->servicio->obtenerPorId($id);

            $this->responderJson(
                200,
                $universidad->toArray()
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
            $universidad = new Universidad(
                $cuerpo['id'],
                $cuerpo['nombre'],
                $cuerpo['tipo'],
                $cuerpo['ciudad']
            );

            $creada = $this->servicio->crear($universidad);

            $this->responderJson(201, [
                'estado' => 201,
                'mensaje' => 'Universidad creada exitosamente.',
                'datos' => $creada->toArray(),
            ]);
        } catch (ConflictoExcepcion $excepcion) {
            $this->responderJson(409, [
                'estado' => 409,
                'mensaje' => 'Conflicto de datos.',
                'detalle' => 'Ya existe una universidad con esa llave.',
            ]);
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    public function reemplazar(
        int $id,
        array $cuerpo
    ): void {
        $errores = $this->validarEdicion($cuerpo, true);

        if ($errores !== []) {
            $this->responderValidacion($errores);
            return;
        }

        try {
            $filasAfectadas = $this->servicio->reemplazar(
                $id,
                $cuerpo
            );

            $this->responderJson(200, [
                'estado' => 200,
                'mensaje' => 'Universidad reemplazada.',
                'filasAfectadas' => $filasAfectadas,
            ]);
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
        $errores = $this->validarEdicion($cuerpo, false);

        if ($errores !== []) {
            $this->responderValidacion($errores);
            return;
        }

        try {
            $filasAfectadas = $this->servicio->actualizar(
                $id,
                $cuerpo
            );

            $this->responderJson(200, [
                'estado' => 200,
                'mensaje' => 'Universidad actualizada.',
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

    public function retirar(int $id): void
    {
        try {
            $filasAfectadas = $this->servicio->retirar($id);

            $this->responderJson(200, [
                'estado' => 200,
                'mensaje' => 'Universidad retirada.',
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
            ['id', 'nombre', 'tipo', 'ciudad']
        );

        if (!array_key_exists('id', $cuerpo)) {
            $errores[] = 'El campo id es obligatorio.';
        } elseif (!is_int($cuerpo['id'])) {
            $errores[] = 'El campo id debe ser un entero.';
        }

        $errores = array_merge(
            $errores,
            $this->validarCamposUniversidad($cuerpo, true)
        );

        return $errores;
    }

    private function validarEdicion(
        array $cuerpo,
        bool $obligatorios
    ): array {
        $errores = $this->validarCamposPermitidos(
            $cuerpo,
            ['nombre', 'tipo', 'ciudad']
        );

        $errores = array_merge(
            $errores,
            $this->validarCamposUniversidad(
                $cuerpo,
                $obligatorios
            )
        );

        return $errores;
    }

    private function validarCamposUniversidad(
        array $cuerpo,
        bool $obligatorios
    ): array {
        $errores = [];

        $errorNombre = $this->validarTexto(
            $cuerpo,
            'nombre',
            60,
            $obligatorios
        );

        if ($errorNombre !== null) {
            $errores[] = $errorNombre;
        }

        $errorTipo = $this->validarTexto(
            $cuerpo,
            'tipo',
            45,
            $obligatorios
        );

        if ($errorTipo !== null) {
            $errores[] = $errorTipo;
        }

        $errorCiudad = $this->validarTexto(
            $cuerpo,
            'ciudad',
            45,
            $obligatorios
        );

        if ($errorCiudad !== null) {
            $errores[] = $errorCiudad;
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
            'mensaje' => 'Universidad no encontrada.',
            'detalle' =>
                'No existe una universidad activa con el id solicitado.',
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