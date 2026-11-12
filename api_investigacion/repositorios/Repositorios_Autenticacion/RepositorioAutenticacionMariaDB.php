<?php

declare(strict_types=1);

/** Consultas parametrizadas a las tablas ya existentes de la versión 3. */
final class RepositorioAutenticacionMariaDB implements IRepositorioAutenticacion
{
    public function __construct(private PDO $pdo)
    {
    }

    public function obtenerUsuarioActivoPorUsername(string $username): ?array
    {
        $consulta = $this->pdo->prepare(
            'SELECT id, username, password, email
             FROM usuario
             WHERE username = :username AND activo = 1
             LIMIT 1'
        );
        $consulta->execute(['username' => $username]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);

        return $fila === false ? null : $fila;
    }

    public function listarRolesActivos(int $usuarioId): array
    {
        $consulta = $this->pdo->prepare(
            'SELECT DISTINCT r.nombre
             FROM rol_usuario AS ru
             INNER JOIN rol AS r ON r.id = ru.rol_id
             WHERE ru.usuario_id = :usuario_id AND r.activo = 1
             ORDER BY r.nombre'
        );
        $consulta->execute(['usuario_id' => $usuarioId]);

        return array_values(array_map('strval', $consulta->fetchAll(PDO::FETCH_COLUMN)));
    }
}
