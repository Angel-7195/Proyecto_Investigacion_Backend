<?php

declare(strict_types=1);

/** Extrae Authorization: Bearer <JWT> y delega la autorización al servicio. */
final class MiddlewareAutorizacion
{
    public function __construct(private ServicioAutorizacion $servicio)
    {
    }

    /**
     * @return array{id:int,username:string,roles:array<int,string>}
     * @throws ExcepcionAcceso
     */
    public function autorizar(string $metodo, string $ruta, ?string $authorization): array
    {
        if ($authorization === null || strlen($authorization) > 4200) {
            throw ExcepcionAcceso::sinAutenticacion();
        }

        $authorization = trim($authorization);
        // No aceptar Basic, JWT en URL, cabeceras múltiples o tokens mal formados.
        if (preg_match('/\ABearer[ \t]+([A-Za-z0-9_.-]+)\z/i', $authorization, $coincidencias) !== 1) {
            throw ExcepcionAcceso::sinAutenticacion();
        }

        return $this->servicio->autorizar($coincidencias[1], $ruta, $metodo);
    }
}
