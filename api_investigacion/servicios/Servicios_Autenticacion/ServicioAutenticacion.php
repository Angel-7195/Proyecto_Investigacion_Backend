<?php

declare(strict_types=1);

/** Reglas de negocio: valida credenciales y emite JWT solo si el usuario tiene rol activo. */
final class ServicioAutenticacion
{
    public function __construct(
        private IRepositorioAutenticacion $repositorio,
        private JwtHS256 $jwt
    ) {
    }

    public function iniciarSesion(array $cuerpo): array
    {
        $username = $cuerpo['username'] ?? null;
        $password = $cuerpo['password'] ?? null;

        if (
            !is_string($username) || !is_string($password)
            || trim($username) === '' || $password === ''
            || strlen(trim($username)) > 100
        ) {
            throw new InvalidArgumentException('Debe enviar username y password válidos.');
        }

        $usuario = $this->repositorio->obtenerUsuarioActivoPorUsername(trim($username));

        // El mismo mensaje para un usuario inexistente, inactivo o con contraseña incorrecta.
        if (
            $usuario === null
            || !is_string($usuario['password'] ?? null)
            || !password_verify($password, $usuario['password'])
        ) {
            throw new DomainException('Credenciales inválidas.');
        }

        $usuarioId = (int) $usuario['id'];
        $roles = $this->repositorio->listarRolesActivos($usuarioId);
        if ($roles === []) {
            throw new DomainException('Credenciales inválidas.');
        }

        return [
            'token' => $this->jwt->emitir($usuarioId, (string) $usuario['username']),
            'tipo' => 'Bearer',
            'expira_en' => JwtHS256::DURACION_SEGUNDOS,
            'usuario' => [
                'id' => $usuarioId,
                'username' => (string) $usuario['username'],
                'roles' => $roles,
            ],
        ];
    }
}
