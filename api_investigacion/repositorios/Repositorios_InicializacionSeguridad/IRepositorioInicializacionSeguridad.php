<?php

declare(strict_types=1);

/** Contrato de persistencia para el alta inicial y única del administrador. */
interface IRepositorioInicializacionSeguridad
{
    /** Devuelve 'creado' o 'existente'. */
    public function inicializarAdministrador(string $username, string $email, string $hash): string;
}
