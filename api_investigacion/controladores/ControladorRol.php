<?php

declare(strict_types=1);

final class ControladorRol
{
    public function __construct(private IServicioRol $servicio)
    {
    }

    public function listar(): void
    {
        $this->ejecutar(function (): void {
            $roles = $this->servicio->listar();
            if ($roles === []) {
                http_response_code(204);
                return;
            }
            $this->responder(200, 'Roles encontrados.', ['datos' => $roles]);
        });
    }

    public function obtener(int $id): void
    {
        $this->ejecutar(fn () => $this->responder(
            200, 'Rol encontrado.', ['datos' => $this->servicio->obtener($id)]
        ));
    }

    public function crear(array $cuerpo): void
    {
        $this->ejecutar(fn () => $this->responder(
            201, 'Rol creado.', ['datos' => $this->servicio->crear($cuerpo)]
        ));
    }

    public function reemplazar(int $id, array $cuerpo): void
    {
        $this->ejecutar(fn () => $this->responder(
            200, 'Rol actualizado.', ['datos' => $this->servicio->reemplazar($id, $cuerpo)]
        ));
    }

    public function actualizar(int $id, array $cuerpo): void
    {
        $this->ejecutar(fn () => $this->responder(
            200, 'Rol actualizado.', ['datos' => $this->servicio->actualizar($id, $cuerpo)]
        ));
    }

    public function retirar(int $id): void
    {
        $this->ejecutar(function () use ($id): void {
            $this->servicio->retirar($id);
            $this->responder(200, 'Rol retirado.');
        });
    }

    private function ejecutar(callable $operacion): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, private');

        try {
            $operacion();
        } catch (InvalidArgumentException $error) {
            $this->responder(422, 'Datos inválidos.', ['errores' => [$error->getMessage()]]);
        } catch (ExcepcionRol $error) {
            $this->responder($error->estadoHttp(), $error->getMessage());
        } catch (Throwable $error) {
            error_log('Error interno del CRUD de roles.');
            $this->responder(500, 'No fue posible procesar la operación de rol.');
        }
    }

    private function responder(int $estado, string $mensaje, array $contenido = []): void
    {
        $json = json_encode(
            ['estado' => $estado, 'mensaje' => $mensaje] + $contenido,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
        http_response_code($estado);
        echo $json;
    }
}
