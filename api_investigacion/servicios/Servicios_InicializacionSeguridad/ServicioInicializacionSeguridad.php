<?php

declare(strict_types=1);

/** Reglas para el primer administrador; no conoce HTTP ni SQL. */
final class ServicioInicializacionSeguridad
{
    public function __construct(private IRepositorioInicializacionSeguridad $repositorio)
    {
    }

    public function inicializarAdministrador(string $username, string $email, string $contrasena): string
    {
        $username = trim($username);
        $email = trim($email);

        if (strlen($username) < 3 || strlen($username) > 100) {
            throw new InvalidArgumentException('El username debe tener entre 3 y 100 caracteres.');
        }
        if (strlen($email) > 150 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('El correo electrónico no es válido.');
        }
        if (strlen($contrasena) < 12) {
            throw new InvalidArgumentException('La contraseña inicial debe tener al menos 12 caracteres.');
        }

        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
        return $this->repositorio->inicializarAdministrador($username, $email, $hash);
    }
}
