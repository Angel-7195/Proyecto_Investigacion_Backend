<?php

declare(strict_types=1);

/** SQL únicamente en repositorio; transacciones y bloqueo del rol admin para la integridad. */
final class RepositorioRolUsuarioMariaDB implements IRepositorioRolUsuario
{
    public function __construct(private PDO $pdo) {}

    public function listar(): array
    {
        $sql = 'SELECT ru.usuario_id, ru.rol_id FROM rol_usuario ru
                INNER JOIN usuario u ON u.id = ru.usuario_id AND u.activo = 1
                INNER JOIN rol r ON r.id = ru.rol_id AND r.activo = 1
                ORDER BY ru.usuario_id, ru.rol_id';
        return array_map(self::convertir(...), $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
    }

    public function obtener(int $usuarioId, int $rolId): ?RolUsuario
    {
        $consulta = $this->pdo->prepare(
            'SELECT ru.usuario_id, ru.rol_id FROM rol_usuario ru
             INNER JOIN usuario u ON u.id = ru.usuario_id AND u.activo = 1
             INNER JOIN rol r ON r.id = ru.rol_id AND r.activo = 1
             WHERE ru.usuario_id = :usuario AND ru.rol_id = :rol LIMIT 1'
        );
        $consulta->execute(['usuario' => $usuarioId, 'rol' => $rolId]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : self::convertir($fila);
    }

    public function crear(int $usuarioId, int $rolId): RolUsuario
    {
        $this->pdo->beginTransaction();
        try {
            // Mismo candado usado en RepositorioUsuarioMariaDB::retirar.
            $this->pdo->query("SELECT id FROM rol WHERE nombre = 'admin' LIMIT 1 FOR UPDATE")->fetch();

            $usuario = $this->pdo->prepare('SELECT id FROM usuario WHERE id = :id AND activo = 1 LIMIT 1');
            $usuario->execute(['id' => $usuarioId]);
            $rol = $this->pdo->prepare('SELECT id FROM rol WHERE id = :id AND activo = 1 LIMIT 1');
            $rol->execute(['id' => $rolId]);
            if ($usuario->fetchColumn() === false || $rol->fetchColumn() === false) {
                throw ExcepcionRolUsuario::referenciaInvalida();
            }

            $insertar = $this->pdo->prepare(
                'INSERT INTO rol_usuario (usuario_id, rol_id) VALUES (:usuario, :rol)'
            );
            $insertar->execute(['usuario' => $usuarioId, 'rol' => $rolId]);
            $this->pdo->commit();
            return new RolUsuario($usuarioId, $rolId);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            if ($e instanceof PDOException && (int) ($e->errorInfo[1] ?? 0) === 1062) {
                throw ExcepcionRolUsuario::conflicto('El usuario ya tiene asignado este rol.');
            }
            if ($e instanceof PDOException && (int) ($e->errorInfo[1] ?? 0) === 1452) {
                throw ExcepcionRolUsuario::referenciaInvalida();
            }
            throw $e;
        }
    }

    public function retirar(int $usuarioId, int $rolId): bool
    {
        $this->pdo->beginTransaction();
        try {
            // Orden de bloqueos compatible con el DELETE lógico de usuario.
            $admin = $this->pdo->query(
                "SELECT id, activo FROM rol WHERE nombre = 'admin' LIMIT 1 FOR UPDATE"
            )->fetch(PDO::FETCH_ASSOC);

            $relacion = $this->pdo->prepare(
                'SELECT usuario_id FROM rol_usuario
                 WHERE usuario_id = :usuario AND rol_id = :rol LIMIT 1 FOR UPDATE'
            );
            $relacion->execute(['usuario' => $usuarioId, 'rol' => $rolId]);
            if ($relacion->fetchColumn() === false) {
                $this->pdo->rollBack();
                return false;
            }

            if ($admin !== false && (int) $admin['activo'] === 1 && (int) $admin['id'] === $rolId) {
                $objetivo = $this->pdo->prepare(
                    'SELECT id FROM usuario WHERE id = :id AND activo = 1 LIMIT 1'
                );
                $objetivo->execute(['id' => $usuarioId]);
                if ($objetivo->fetchColumn() !== false) {
                    $activos = $this->pdo->prepare(
                        'SELECT u.id FROM usuario u
                         INNER JOIN rol_usuario ru ON ru.usuario_id = u.id
                         WHERE ru.rol_id = :rol AND u.activo = 1 FOR UPDATE'
                    );
                    $activos->execute(['rol' => $rolId]);
                    if (count($activos->fetchAll(PDO::FETCH_COLUMN)) <= 1) {
                        throw ExcepcionRolUsuario::conflicto(
                            'No puede retirar el rol al último administrador activo.'
                        );
                    }
                }
            }

            $borrar = $this->pdo->prepare(
                'DELETE FROM rol_usuario WHERE usuario_id = :usuario AND rol_id = :rol'
            );
            $borrar->execute(['usuario' => $usuarioId, 'rol' => $rolId]);
            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            throw $e;
        }
    }

    private static function convertir(array $fila): RolUsuario
    {
        return new RolUsuario((int) $fila['usuario_id'], (int) $fila['rol_id']);
    }
}
