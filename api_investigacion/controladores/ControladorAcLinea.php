<?php

declare(strict_types=1);

require_once __DIR__ . '/../servicios/Servicios_AcLinea/IServicioAcLinea.php';
require_once __DIR__ . '/../excepciones/ConflictoExcepcion.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

/** Controlador HTTP de ac_linea. Sin SQL ni lógica de persistencia. */
class ControladorAcLinea
{
    private IServicioAcLinea $servicio;

    public function __construct(IServicioAcLinea $servicio)
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
            $datos = array_map(static fn (AcLinea $relacion): array => $relacion->toArray(), $registros);
            $this->responderJson(200, [
                'recurso' => 'ac_linea',
                'total' => count($datos),
                'datos' => $datos,
            ]);
        } catch (Throwable $excepcion) {
            $this->responderErrorInterno();
        }
    }

    public function obtener(int $lineaInvestigacion, string $areaConocimiento): void
    {
        try {
            $asociacion = $this->servicio->obtenerPorClave($lineaInvestigacion, $areaConocimiento);
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
            $nueva = $this->servicio->crear(new AcLinea(
                $cuerpo['linea_investigacion'],
                $cuerpo['area_conocimiento']
            ));
            $this->responderJson(201, [
                'estado' => 201,
                'mensaje' => 'Asociación de línea y área de conocimiento creada.',
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

    public function retirar(int $lineaInvestigacion, string $areaConocimiento): void
    {
        try {
            $filas = $this->servicio->retirar($lineaInvestigacion, $areaConocimiento);
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
     * La tabla no tiene atributos editables: sus dos columnas de datos son la PK.
     * Se conservan estos manejadores para responder 405 si llegan PUT/PATCH.
     * @param array<string,mixed> $cuerpo
     */
    public function reemplazar(int $lineaInvestigacion, string $areaConocimiento, array $cuerpo): void
    {
        $this->responderNoPermitido();
    }

    /** @param array<string,mixed> $cuerpo */
    public function actualizar(int $lineaInvestigacion, string $areaConocimiento, array $cuerpo): void
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
            if (!in_array($campo, ['linea_investigacion', 'area_conocimiento'], true)) {
                $errores[] = 'El campo ' . $campo . ' no está permitido.';
            }
        }
        if (!array_key_exists('linea_investigacion', $cuerpo)) {
            $errores[] = 'El campo linea_investigacion es obligatorio.';
        } elseif (!is_int($cuerpo['linea_investigacion']) || $cuerpo['linea_investigacion'] <= 0) {
            $errores[] = 'linea_investigacion debe ser un entero positivo.';
        }
        if (!array_key_exists('area_conocimiento', $cuerpo)) {
            $errores[] = 'El campo area_conocimiento es obligatorio.';
        } elseif (!is_string($cuerpo['area_conocimiento'])) {
            $errores[] = 'area_conocimiento debe ser una cadena de texto.';
        } else {
            $area = $cuerpo['area_conocimiento'];
            $longitud = function_exists('mb_strlen')
                ? mb_strlen($area, 'UTF-8')
                : (preg_match_all('/./us', $area) ?: 0);
            if (trim($area) === '' || $longitud > 6) {
                $errores[] = 'area_conocimiento debe contener entre 1 y 6 caracteres.';
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
            'mensaje' => 'PUT y PATCH no están disponibles para ac_linea: no hay campos editables.',
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
