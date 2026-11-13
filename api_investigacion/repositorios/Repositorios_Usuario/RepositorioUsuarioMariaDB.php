<?php

declare(strict_types=1);

/** PDO + SQL parametrizado: única capa que conoce la tabla usuario. */
final class RepositorioUsuarioMariaDB implements IRepositorioUsuario
{
    private const CAMPOS_LECTURA =
        'id, username, email, nombre_completo, fecha_creacion, fecha_actualizacion';

    public function __construct(private PDO $pdo)
    {
    }

    public function listar(): array
    {
        $sql = 'SELECT ' . self::CAMPOS_LECTURA .
            ' FROM usuario WHERE activo = 1 ORDER BY id';
        $filas = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        return array_map(static fn (array $fila): Usuario => Usuario::desdeFila($fila), $filas);
    }

    public function obtener(int $id): ?Usuario
    {
        $sql = 'SELECT ' . self::CAMPOS_LECTURA .
            ' FROM usuario WHERE id = :id AND activo = 1 LIMIT 1';
        $sentencia = $this->pdo->prepare($sql);
        $sentencia->execute(['id' => $id]);
        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : Usuario::desdeFila($fila);
    }

    public function crear(string $username, string $email, ?string $nombreCompleto, string $hash): Usuario
    {
        try {
            $sentencia = $this->pdo->prepare(
                'INSERT INTO usuario (username, password, email, nombre_completo, activo)
                 VALUES (:username, :password, :email, :nombre_completo, 1)'
            );
            $sentencia->execute([
                'username' => $username,
                'password' => $hash,
                'email' => $email,
                'nombre_completo' => $nombreCompleto,
            ]);
        } catch (PDOException $e) {
            $this->traducirDuplicado($e);
            throw $e;
        }

        $usuario = $this->obtener((int) $this->pdo->lastInsertId());
        if ($usuario === null) {
            throw new RuntimeException('No se pudo recuperar el usuario creado.');
        }
        return $usuario;
    }

    public function actualizarPerfil(int $id, array $datos): ?Usuario
    {
        // La lista blanca evita que id, activo, password o columnas arbitrarias se editen.
        $permitidos = ['username', 'email', 'nombre_completo'];
        $asignaciones = [];
        $parametros = ['id' => $id];
        foreach ($datos as $campo => $valor) {
            if (!in_array($campo, $permitidos, true)) {
                throw new LogicException('Campo no admitido por el repositorio.');
            }
            $asignaciones[] = "$campo = :$campo";
            $parametros[$campo] = $valor;
        }
        if ($asignaciones === []) {
            throw new LogicException('No hay campos para actualizar.');
        }
        if ($this->obtener($id) === null) {
            return null;
        }

        try {
            $sql = 'UPDATE usuario SET ' . implode(', ', $asignaciones) .
                ' WHERE id = :id AND activo = 1';
            $sentencia = $this->pdo->prepare($sql);
            $sentencia->execute($parametros);
        } catch (PDOException $e) {
            $this->traducirDuplicado($e);
            throw $e;
        }
        return $this->obtener($id);
    }

    public function retirar(int $id): bool
    {
        // El bloqueo del rol admin serializa retiros concurrentes. La futura gestión
        // de rol_usuario deberá usar el MISMO bloqueo al retirar un admin.
        $this->pdo->beginTransaction();
        try {
            $bloqueoRol = $this->pdo->query(
                "SELECT id, activo FROM rol WHERE nombre = 'admin' LIMIT 1 FOR UPDATE"
            );
            $rolAdmin = $bloqueoRol->fetch(PDO::FETCH_ASSOC);

            $consulta = $this->pdo->prepare(
                'SELECT id FROM usuario WHERE id = :id AND activo = 1 FOR UPDATE'
            );
            $consulta->execute(['id' => $id]);
            if ($consulta->fetch(PDO::FETCH_ASSOC) === false) {
                $this->pdo->rollBack();
                return false;
            }

            if ($rolAdmin !== false && (int) $rolAdmin['activo'] === 1) {
                $adminId = (int) $rolAdmin['id'];
                $relacion = $this->pdo->prepare(
                    'SELECT usuario_id FROM rol_usuario
                     WHERE usuario_id = :uid AND rol_id = :rid LIMIT 1'
                );
                $relacion->execute(['uid' => $id, 'rid' => $adminId]);
                if ($relacion->fetch(PDO::FETCH_ASSOC) !== false) {
                    $todos = $this->pdo->prepare(
                        'SELECT u.id FROM usuario AS u
                         INNER JOIN rol_usuario AS ru ON ru.usuario_id = u.id
                         WHERE ru.rol_id = :rid AND u.activo = 1 FOR UPDATE'
                    );
                    $todos->execute(['rid' => $adminId]);
                    if (count($todos->fetchAll(PDO::FETCH_COLUMN)) <= 1) {
                        throw ExcepcionUsuario::conflicto(
                            'No puede retirar al último administrador activo.'
                        );
                    }
                }
            }

            $cambio = $this->pdo->prepare(
                'UPDATE usuario SET activo = 0 WHERE id = :id AND activo = 1'
            );
            $cambio->execute(['id' => $id]);
            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function actualizarHash(int $id, string $hash): bool
    {
        // COUNT FOUND_ROWS no es imprescindible: verificar existencia evita
        // depender de un cambio en bytes para saber si el usuario existe.
        if ($this->obtener($id) === null) {
            return false;
        }
        $sentencia = $this->pdo->prepare(
            'UPDATE usuario SET password = :password WHERE id = :id AND activo = 1'
        );
        $sentencia->execute(['id' => $id, 'password' => $hash]);
        return true;
    }

    private function traducirDuplicado(PDOException $e): void
    {
        // MariaDB/MySQL: error 1062 para UNIQUE (username o email).
        $codigoMotor = isset($e->errorInfo[1]) ? (int) $e->errorInfo[1] : 0;
        if ($codigoMotor === 1062) {
            throw ExcepcionUsuario::conflicto(
                'El nombre de usuario o el correo electrónico ya están registrados.'
            );
        }
    }
}
