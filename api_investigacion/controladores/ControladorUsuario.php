<?php

declare(strict_types=1);

/** Adaptador HTTP: serializa usuarios sin contraseñas. La seguridad la aplica el middleware. */
final class ControladorUsuario
{
    public function __construct(private IServicioUsuario $servicio)
    {
    }

    public function listar(): void
    {
        $this->ejecutar(function (): void {
            $datos = $this->servicio->listar();
            if ($datos === []) {
                http_response_code(204);
                return;
            }
            $this->responder(200, 'Usuarios encontrados.', $datos);
        });
    }

    public function obtener(int $id): void
    {
        $this->ejecutar(fn () => $this->responder(200, 'Usuario encontrado.', $this->servicio->obtener($id)));
    }

    public function crear(array $cuerpo): void
    {
        $this->ejecutar(fn () => $this->responder(201, 'Usuario creado exitosamente.', $this->servicio->crear($cuerpo)));
    }

    public function reemplazar(int $id, array $cuerpo): void
    {
        $this->ejecutar(fn () => $this->responder(200, 'Usuario actualizado.', $this->servicio->reemplazar($id, $cuerpo)));
    }

    public function actualizar(int $id, array $cuerpo): void
    {
        $this->ejecutar(fn () => $this->responder(200, 'Usuario actualizado.', $this->servicio->actualizar($id, $cuerpo)));
    }

    public function retirar(int $id): void
    {
        $this->ejecutar(function () use ($id): void {
            $this->servicio->retirar($id);
            $this->responder(200, 'Usuario retirado correctamente.');
        });
    }

    public function cambiarContrasena(int $id, array $cuerpo): void
    {
        $this->ejecutar(function () use ($id, $cuerpo): void {
            $this->servicio->cambiarContrasena($id, $cuerpo);
            $this->responder(200, 'Contraseña actualizada.');
        });
    }

    private function ejecutar(callable $operacion): void
    {
        header('Cache-Control: no-store, private');
        try {
            $operacion();
        } catch (InvalidArgumentException $e) {
            $this->error(422, 'Datos inválidos.', [$e->getMessage()]);
        } catch (ExcepcionUsuario $e) {
            $this->error($e->estadoHttp(), $e->getMessage());
        } catch (Throwable $e) {
            // No enviar SQL, hash, contraseña ni stack trace al navegador.
            error_log('Error interno del CRUD de usuarios.');
            $this->error(500, 'No fue posible procesar la operación de usuario.');
        }
    }

    private function responder(int $estado, string $mensaje, ?array $datos = null): void
    {
        http_response_code($estado);
        $respuesta = ['estado' => $estado, 'mensaje' => $mensaje];
        if ($datos !== null) {
            $respuesta['datos'] = $datos;
        }
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function error(int $estado, string $mensaje, array $errores = []): void
    {
        http_response_code($estado);
        $respuesta = ['estado' => $estado, 'mensaje' => $mensaje];
        if ($errores !== []) {
            $respuesta['errores'] = $errores;
        }
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
