<?php

declare(strict_types=1);

/** Adaptador HTTP; la autorización admin ocurre antes en el middleware. */
final class ControladorRolUsuario
{
    public function __construct(private IServicioRolUsuario $servicio) {}

    public function listar(): void
    {
        $this->ejecutar(function (): void {
            $datos = $this->servicio->listar();
            if ($datos === []) { http_response_code(204); return; }
            $this->responder(200, 'Asignaciones encontradas.', $datos);
        });
    }

    public function obtener(int $usuarioId, int $rolId): void
    {
        $this->ejecutar(fn () => $this->responder(200, 'Asignación encontrada.', $this->servicio->obtener($usuarioId, $rolId)));
    }

    public function crear(array $datos): void
    {
        $this->ejecutar(fn () => $this->responder(201, 'Rol asignado correctamente.', $this->servicio->crear($datos)));
    }

    public function retirar(int $usuarioId, int $rolId): void
    {
        $this->ejecutar(function () use ($usuarioId, $rolId): void {
            $this->servicio->retirar($usuarioId, $rolId);
            $this->responder(200, 'Asignación retirada correctamente.');
        });
    }

    private function ejecutar(callable $operacion): void
    {
        header('Cache-Control: no-store, private');
        try {
            $operacion();
        } catch (InvalidArgumentException $e) {
            $this->error(422, 'Datos inválidos.', [$e->getMessage()]);
        } catch (ExcepcionRolUsuario $e) {
            $this->error($e->estadoHttp(), $e->getMessage());
        } catch (Throwable $e) {
            error_log('Error interno en rol_usuario.');
            $this->error(500, 'No fue posible procesar la asignación.');
        }
    }

    private function responder(int $estado, string $mensaje, ?array $datos = null): void
    {
        http_response_code($estado);
        $respuesta = ['estado' => $estado, 'mensaje' => $mensaje];
        if ($datos !== null) { $respuesta['datos'] = $datos; }
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private function error(int $estado, string $mensaje, array $errores = []): void
    {
        http_response_code($estado);
        $respuesta = ['estado' => $estado, 'mensaje' => $mensaje];
        if ($errores !== []) { $respuesta['errores'] = $errores; }
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
