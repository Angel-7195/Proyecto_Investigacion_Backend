<?php

declare(strict_types=1);

/** SQL del proceso de inicialización; no interviene en peticiones HTTP. */
final class RepositorioInicializacionSeguridadMariaDB implements IRepositorioInicializacionSeguridad
{
    public function __construct(private PDO $pdo)
    {
    }

    public function inicializarAdministrador(string $username, string $email, string $hash): string
    {
        $this->pdo->beginTransaction();

        try {
            // Las semillas del init.sql no se vuelven a ejecutar en un volumen existente.
            $insertarRol = $this->pdo->prepare(
                'INSERT INTO rol (nombre, descripcion, activo)
                 VALUES (:nombre, :descripcion, TRUE)
                 ON DUPLICATE KEY UPDATE nombre = nombre'
            );
            $insertarRol->execute([
                'nombre' => 'admin',
                'descripcion' => 'Administrador del sistema',
            ]);
            $insertarRol->execute([
                'nombre' => 'consulta',
                'descripcion' => 'Usuario con permisos de solo lectura',
            ]);

            // Bloquear la fila admin serializa dos inicializaciones concurrentes.
            $buscarRol = $this->pdo->prepare(
                'SELECT id, activo FROM rol WHERE nombre = :nombre FOR UPDATE'
            );
            $buscarRol->execute(['nombre' => 'admin']);
            $rolAdmin = $buscarRol->fetch(PDO::FETCH_ASSOC);
            if (!$rolAdmin || (int) $rolAdmin['activo'] !== 1) {
                throw new RuntimeException('El rol admin no existe o está inactivo.');
            }
            $buscarRol->execute(['nombre' => 'consulta']);
            $rolConsulta = $buscarRol->fetch(PDO::FETCH_ASSOC);
            if (!$rolConsulta || (int) $rolConsulta['activo'] !== 1) {
                throw new RuntimeException('El rol consulta no existe o está inactivo.');
            }

            // No crear otra cuenta ni reactivar una cuenta administrativa retirada.
            $buscarAdministrador = $this->pdo->prepare(
                'SELECT u.activo
                 FROM usuario AS u
                 INNER JOIN rol_usuario AS ru ON ru.usuario_id = u.id
                 WHERE ru.rol_id = :rol_id
                 LIMIT 1'
            );
            $buscarAdministrador->execute(['rol_id' => (int) $rolAdmin['id']]);
            $administrador = $buscarAdministrador->fetch(PDO::FETCH_ASSOC);
            if ($administrador !== false) {
                if ((int) $administrador['activo'] !== 1) {
                    throw new RuntimeException(
                        'Existe una asignación admin a una cuenta inactiva; se requiere revisión manual.'
                    );
                }
                $this->pdo->commit();
                return 'existente';
            }

            // No otorgar privilegios a un usuario previamente creado por coincidencia.
            $buscarDuplicado = $this->pdo->prepare(
                'SELECT id FROM usuario WHERE username = :username OR email = :email LIMIT 1'
            );
            $buscarDuplicado->execute(['username' => $username, 'email' => $email]);
            if ($buscarDuplicado->fetchColumn() !== false) {
                throw new RuntimeException(
                    'Ya existe el username o el correo; no se concedieron permisos de administrador.'
                );
            }

            $insertarUsuario = $this->pdo->prepare(
                'INSERT INTO usuario (username, password, email, activo)
                 VALUES (:username, :hash, :email, TRUE)'
            );
            $insertarUsuario->execute([
                'username' => $username,
                'hash' => $hash,
                'email' => $email,
            ]);
            $usuarioId = (int) $this->pdo->lastInsertId();

            $asignarRol = $this->pdo->prepare(
                'INSERT INTO rol_usuario (usuario_id, rol_id) VALUES (:usuario_id, :rol_id)'
            );
            $asignarRol->execute([
                'usuario_id' => $usuarioId,
                'rol_id' => (int) $rolAdmin['id'],
            ]);

            $this->pdo->commit();
            return 'creado';
        } catch (Throwable $excepcion) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $excepcion;
        }
    }
}
