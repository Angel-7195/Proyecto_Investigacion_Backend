<?php

declare(strict_types=1);

require_once __DIR__ . '/../servicios/Servicios_OdsLinea/IServicioOdsLinea.php';
require_once __DIR__ . '/../excepciones/ConflictoExcepcion.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

/** Controlador HTTP de ods_linea. No consulta directamente la base de datos. */
class ControladorOdsLinea
{
    private IServicioOdsLinea $servicio;

    public function __construct(IServicioOdsLinea $servicio)
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
            $datos = array_map(static fn (OdsLinea $asociacion): array => $asociacion->toArray(), $registros);
            $this->responderJson(200, [
                'recurso' => 'ods_linea',
                'total' => count($datos),
                'datos' => $datos,
            ]);
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    public function obtener(int $lineaInvestigacion, int $ods): void
    {
        try {
            $asociacion = $this->servicio->obtenerPorClave($lineaInvestigacion, $ods);
            $this->responderJson(200, $asociacion->toArray());
        } catch (InvalidArgumentException $excepcion) {
            $this->responderValidacion([$excepcion->getMessage()]);
        } catch (NoEncontradoExcepcion $excepcion) {
            $this->responderNoEncontrado();
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    /** @param array<string,mixed> $cuerpo */
    public function crear(array $cuerpo): void
    {
        $errores = $this->validarCuerpoCreacion($cuerpo);
        if ($errores !== []) {
            $this->responderValidacion($errores);
            return;
        }

        try {
            $nueva = $this->servicio->crear(new OdsLinea(
                $cuerpo['linea_investigacion'],
                $cuerpo['ods']
            ));
            $this->responderJson(201, [
                'estado' => 201,
                'mensaje' => 'Asociación de línea y ODS creada.',
                'datos' => $nueva->toArray(),
            ]);
        } catch (ConflictoExcepcion $excepcion) {
            $this->responderConflicto();
        } catch (InvalidArgumentException $excepcion) {
            $this->responderValidacion([$excepcion->getMessage()]);
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    public function retirar(int $lineaInvestigacion, int $ods): void
    {
        try {
            $filas = $this->servicio->retirar($lineaInvestigacion, $ods);
            $this->responderJson(200, [
                'estado' => 200,
                'mensaje' => 'Asociación retirada.',
                'filasAfectadas' => $filas,
            ]);
        } catch (InvalidArgumentException $excepcion) {
            $this->responderValidacion([$excepcion->getMessage()]);
        } catch (NoEncontradoExcepcion $excepcion) {
            $this->responderNoEncontrado();
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    /**
     * No hay campos editables: las únicas columnas de datos son las dos claves primarias.
     * @param array<string,mixed> $cuerpo
     */
    public function reemplazar(int $lineaInvestigacion, int $ods, array $cuerpo): void
    {
        $this->responderNoPermitido();
    }

    /** @param array<string,mixed> $cuerpo */
    public function actualizar(int $lineaInvestigacion, int $ods, array $cuerpo): void
    {
        $this->responderNoPermitido();
    }

    /** @param array<string,mixed> $cuerpo
     *  @return string[]
     */
    private function validarCuerpoCreacion(array $cuerpo): array
    {
        $errores = [];
        foreach (array_keys($cuerpo) as $campo) {
            if (!in_array($campo, ['linea_investigacion', 'ods'], true)) {
                $errores[] = 'El campo ' . $campo . ' no está permitido.';
            }
        }
        if (!array_key_exists('linea_investigacion', $cuerpo)) {
            $errores[] = 'El campo linea_investigacion es obligatorio.';
        } elseif (!is_int($cuerpo['linea_investigacion']) || $cuerpo['linea_investigacion'] <= 0) {
            $errores[] = 'linea_investigacion debe ser un entero positivo.';
        }
        if (!array_key_exists('ods', $cuerpo)) {
            $errores[] = 'El campo ods es obligatorio.';
        } elseif (!is_int($cuerpo['ods']) || $cuerpo['ods'] <= 0) {
            $errores[] = 'ods debe ser un entero positivo.';
        }
        return $errores;
    }

    /** @param string[] $errores */
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
            'mensaje' => 'Asociación no encontrada.',
            'detalle' => 'No existe una asociación activa con esas dos claves.',
        ]);
    }

    private function responderConflicto(): void
    {
        $this->responderJson(409, [
            'estado' => 409,
            'mensaje' => 'Conflicto de datos.',
            'detalle' => 'La combinación ya existe o alguna referencia no está activa.',
        ]);
    }

    private function responderNoPermitido(): void
    {
        $this->responderJson(405, [
            'estado' => 405,
            'mensaje' => 'PUT y PATCH no están disponibles para ods_linea: no hay campos editables.',
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

    /** @param array<string,mixed> $datos */
    private function responderJson(int $codigo, array $datos): void
    {
        http_response_code($codigo);
        if (PHP_SAPI !== 'cli') {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
