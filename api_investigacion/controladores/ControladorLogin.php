<?php

declare(strict_types=1);

/** Adaptador HTTP del servicio de autenticación; no conoce SQL ni contraseñas almacenadas. */
final class ControladorLogin
{
    public function __construct(private ServicioAutenticacion $servicio)
    {
    }

    public function iniciarSesion(array $cuerpo): void
    {
        header('Cache-Control: no-store, private');
        header('Pragma: no-cache');

        try {
            $resultado = $this->servicio->iniciarSesion($cuerpo);
            http_response_code(200);
            echo json_encode([
                'estado' => 200,
                'mensaje' => 'Inicio de sesión correcto.',
                'datos' => $resultado,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode([
                'estado' => 422,
                'mensaje' => 'Datos inválidos.',
                'errores' => [$e->getMessage()],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (DomainException $e) {
            http_response_code(401);
            echo json_encode([
                'estado' => 401,
                'mensaje' => 'Credenciales inválidas.',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            // Sin credenciales, SQL, detalles de PDO ni claves en la respuesta.
            http_response_code(500);
            echo json_encode([
                'estado' => 500,
                'mensaje' => 'No fue posible procesar el inicio de sesión.',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }
}
