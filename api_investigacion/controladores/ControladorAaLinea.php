<?php

declare(strict_types=1);

require_once __DIR__ . '/../servicios/Servicios_AaLinea/IServicioAaLinea.php';
require_once __DIR__ . '/../excepciones/ConflictoExcepcion.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

/** Controlador HTTP para aa_linea, sin acceso directo a MariaDB. */
class ControladorAaLinea
{
    private IServicioAaLinea $servicio;

    public function __construct(IServicioAaLinea $servicio)
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
            $datos = array_map(static fn (AaLinea $asociacion): array => $asociacion->toArray(), $registros);
            $this->responderJson(200, [
                'recurso' => 'aa_linea',
                'total' => count($datos),
                'datos' => $datos,
            ]);
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    public function obtener(int $areaAplicacion, int $lineaInvestigacion): void
    {
        try {
            $asociacion = $this->servicio->obtenerPorClave($areaAplicacion, $lineaInvestigacion);
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
            $nueva = $this->servicio->crear(new AaLinea(
                $cuerpo['area_aplicacion'],
                $cuerpo['linea_investigacion']
            ));
            $this->responderJson(201, [
                'estado' => 201,
                'mensaje' => 'Asociación de área de aplicación y línea creada.',
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

    public function retirar(int $areaAplicacion, int $lineaInvestigacion): void
    {
        try {
            $filas = $this->servicio->retirar($areaAplicacion, $lineaInvestigacion);
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
     * No hay campos editables: las dos columnas son claves primarias.
     * @param array<string,mixed> $cuerpo
     */
    public function reemplazar(int $areaAplicacion, int $lineaInvestigacion, array $cuerpo): void
    {
        $this->responderNoPermitido();
    }

    /** @param array<string,mixed> $cuerpo */
    public function actualizar(int $areaAplicacion, int $lineaInvestigacion, array $cuerpo): void
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
            if (!in_array($campo, ['area_aplicacion', 'linea_investigacion'], true)) {
                $errores[] = 'El campo ' . $campo . ' no está permitido.';
            }
        }
        if (!array_key_exists('area_aplicacion', $cuerpo)) {
            $errores[] = 'El campo area_aplicacion es obligatorio.';
        } elseif (!is_int($cuerpo['area_aplicacion']) || $cuerpo['area_aplicacion'] <= 0) {
            $errores[] = 'area_aplicacion debe ser un entero positivo.';
        }
        if (!array_key_exists('linea_investigacion', $cuerpo)) {
            $errores[] = 'El campo linea_investigacion es obligatorio.';
        } elseif (!is_int($cuerpo['linea_investigacion']) || $cuerpo['linea_investigacion'] <= 0) {
            $errores[] = 'linea_investigacion debe ser un entero positivo.';
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
            'mensaje' => 'PUT y PATCH no están disponibles para aa_linea: no hay campos editables.',
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
