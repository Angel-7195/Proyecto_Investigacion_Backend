<?php

declare(strict_types=1);

/** Contrato de persistencia. Las operaciones leen/afectan solo usuarios activos. */
interface IRepositorioUsuario
{
    /** @return list<Usuario> */
    public function listar(): array;

    public function obtener(int $id): ?Usuario;

    public function crear(string $username, string $email, ?string $nombreCompleto, string $hash): Usuario;

    /** @param array<string,string|null> $datos Campos de perfil validados, sin password. */
    public function actualizarPerfil(int $id, array $datos): ?Usuario;

    /** Desactiva al usuario, pero impide dejar al sistema sin admin activo. */
    public function retirar(int $id): bool;

    /** Actualiza solo el hash; nunca devuelve el hash al servicio. */
    public function actualizarHash(int $id, string $hash): bool;
}
