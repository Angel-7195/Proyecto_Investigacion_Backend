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
            'SELECT id, nombre, descripcion FROM rol WHERE activo = 1 ORDER BY id'
        )->fetchAll(PDO::FETCH_ASSOC);

        return array_map([Rol::class, 'desdeFila'], $filas);
    }

    public function obtener(int $id): ?Rol
    {
        $consulta = $this->pdo->prepare(
            'SELECT id, nombre, descripcion FROM rol WHERE id = :id AND activo = 1'
        );
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);

        return $fila === false ? null : Rol::desdeFila($fila);
    }

    public function crear(string $nombre, ?string $descripcion): Rol
    {
        $this->guardar(
            'INSERT INTO rol (nombre, descripcion, activo) VALUES (:nombre, :descripcion, 1)',
            ['nombre' => $nombre, 'descripcion' => $descripcion]
        );

        return new Rol((int) $this->pdo->lastInsertId(), $nombre, $descripcion);
    }

    public function actualizar(int $id, array $cambios): ?Rol
    {
        $asignaciones = [];
        foreach ($cambios as $campo => $valor) {
            $asignaciones[] = match ($campo) {
                'nombre' => 'nombre = :nombre',
                'descripcion' => 'descripcion = :descripcion',
                default => throw new LogicException('Campo no admitido por el repositorio.'),
            };
        }

        $this->pdo->beginTransaction();
        try {
            $fila = $this->bloquearActivo($id);
            if ($fila === false) {
                $this->pdo->commit();
                return null;
            }

            if ((bool) $fila['es_admin'] && ($cambios['nombre'] ?? $fila['nombre']) !== $fila['nombre']) {
                throw ExcepcionRol::conflicto('El nombre del rol admin está protegido.');
            }

            $this->guardar(
                'UPDATE rol SET ' . implode(', ', $asignaciones) . ' WHERE id = :id',
                $cambios + ['id' => $id]
            );
            $rol = $this->obtener($id);
            $this->pdo->commit();

            return $rol;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function retirar(int $id): bool
    {
        $this->pdo->beginTransaction();
        try {
            $fila = $this->bloquearActivo($id);
            if ($fila === false) {
                $this->pdo->commit();
                return false;
            }

            if ((bool) $fila['es_admin']) {
                throw ExcepcionRol::conflicto('El rol admin está protegido contra el retiro.');
            }

            $this->guardar('UPDATE rol SET activo = 0 WHERE id = :id', ['id' => $id]);
            $this->pdo->commit();

            return true;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    private function bloquearActivo(int $id): array|false
    {
        // Usa la comparación de MariaDB para coincidir con la búsqueda del rol del guard.
        $consulta = $this->pdo->prepare(
            "SELECT nombre, nombre = 'admin' AS es_admin FROM rol
             WHERE id = :id AND activo = 1 FOR UPDATE"
        );
        $consulta->execute(['id' => $id]);

        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    private function guardar(string $sql, array $parametros): void
    {
        try {
            $this->pdo->prepare($sql)->execute($parametros);
        } catch (PDOException $error) {
            if (($error->errorInfo[0] ?? '') === '23000' && (int) ($error->errorInfo[1] ?? 0) === 1062) {
                throw ExcepcionRol::conflicto('Ya existe un rol con ese nombre.');
            }
            throw $error;
        }
    }
}
