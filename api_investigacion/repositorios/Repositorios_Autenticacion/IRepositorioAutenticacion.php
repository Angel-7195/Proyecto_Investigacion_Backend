<?php

declare(strict_types=1);

/** Consultas necesarias para autenticar usuarios (sin HTTP ni JWT). */
interface IRepositorioAutenticacion
{
    /** Devuelve solo los campos necesarios, incluido el hash; nunca se envía al cliente. */
    public function obtenerUsuarioActivoPorUsername(string $username): ?array;

    /** Devuelve nombres de roles activos asociados al usuario. */
    public function listarRolesActivos(int $usuarioId): array;
}
