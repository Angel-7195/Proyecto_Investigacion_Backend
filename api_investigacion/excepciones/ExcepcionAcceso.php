<?php

declare(strict_types=1);

/** Error de acceso HTTP conocido: 401 (autenticación) o 403 (autorización). */
final class ExcepcionAcceso extends RuntimeException
{
    private function __construct(private int $estadoHttp, string $mensaje)
    {
        parent::__construct($mensaje, $estadoHttp);
    }

    public static function sinAutenticacion(): self
    {
        return new self(401, 'Autenticación requerida o token inválido.');
    }

    public static function sinPermisos(): self
    {
        return new self(403, 'No tiene permiso para realizar esta acción.');
    }

    public function estadoHttp(): int
    {
        return $this->estadoHttp;
    }
}
