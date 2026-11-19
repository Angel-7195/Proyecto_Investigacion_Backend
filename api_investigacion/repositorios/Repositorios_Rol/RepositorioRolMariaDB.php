<?php

declare(strict_types=1);

final class RepositorioRolMariaDB implements IRepositorioRol
{
    public function __construct(private PDO $pdo)
    {
    }

    public function listar(): array
    {
        $filas = $this->pdo->query(
            'SELECT id, nombre, descripcion
             FROM rol
             WHERE activo = 1
             ORDER BY id'
        )->fetchAll(PDO::FETCH_ASSOC);

        return array_map([Rol::class, 'desdeFila'], $filas);
    }

    public function obtener(int $id): ?Rol
    {
        $consulta = $this->pdo->prepare(
            'SELECT id, nombre, descripcion
             FROM rol
             WHERE id = :id AND activo = 1'
        );

        $consulta->execute(['id' => $id]);

        $fila = $consulta->fetch(PDO::FETCH_ASSOC);

        return $fila === false ? null : Rol::desdeFila($fila);
    }
}
