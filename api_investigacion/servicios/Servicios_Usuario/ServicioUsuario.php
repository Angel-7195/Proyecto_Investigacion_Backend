<?php

declare(strict_types=1);

/** Reglas de negocio del recurso usuario; NO contiene SQL ni conoce HTTP. */
final class ServicioUsuario implements IServicioUsuario
{
    public function __construct(private IRepositorioUsuario $repositorio)
    {
    }

    public function listar(): array
    {
        return array_map(
            static fn (Usuario $usuario): array => $usuario->aArray(),
            $this->repositorio->listar()
        );
    }

    public function obtener(int $id): array
    {
        $this->validarId($id);
        $usuario = $this->repositorio->obtener($id);
        if ($usuario === null) {
            throw ExcepcionUsuario::noEncontrado();
        }
        return $usuario->aArray();
    }

    public function crear(array $cuerpo): array
    {
        $this->validarCamposPermitidos($cuerpo, [
            'username', 'email', 'nombre_completo', 'password',
        ]);
        $username = $this->validarUsername($cuerpo['username'] ?? null);
        $email = $this->validarEmail($cuerpo['email'] ?? null);
        $nombre = $this->validarNombre($cuerpo['nombre_completo'] ?? null);
        $hash = password_hash($this->validarContrasena($cuerpo['password'] ?? null), PASSWORD_DEFAULT);

        return $this->repositorio->crear($username, $email, $nombre, $hash)->aArray();
    }

    public function reemplazar(int $id, array $cuerpo): array
    {
        $this->validarId($id);
        $this->validarCamposPermitidos($cuerpo, ['username', 'email', 'nombre_completo']);
        // PUT exige perfil completo pero NO acepta password: la contraseña tiene ruta propia.
        $datos = [
            'username' => $this->validarUsername($cuerpo['username'] ?? null),
            'email' => $this->validarEmail($cuerpo['email'] ?? null),
            'nombre_completo' => $this->validarNombre($cuerpo['nombre_completo'] ?? null),
        ];
        $usuario = $this->repositorio->actualizarPerfil($id, $datos);
        if ($usuario === null) {
            throw ExcepcionUsuario::noEncontrado();
        }
        return $usuario->aArray();
    }

    public function actualizar(int $id, array $cuerpo): array
    {
        $this->validarId($id);
        $this->validarCamposPermitidos($cuerpo, ['username', 'email', 'nombre_completo']);
        if ($cuerpo === []) {
            throw new InvalidArgumentException('Debe enviar al menos un campo para actualizar.');
        }
        $datos = [];
        foreach ($cuerpo as $campo => $valor) {
            $datos[$campo] = match ($campo) {
                'username' => $this->validarUsername($valor),
                'email' => $this->validarEmail($valor),
                'nombre_completo' => $this->validarNombre($valor),
            };
        }
        $usuario = $this->repositorio->actualizarPerfil($id, $datos);
        if ($usuario === null) {
            throw ExcepcionUsuario::noEncontrado();
        }
        return $usuario->aArray();
    }

    public function retirar(int $id): void
    {
        $this->validarId($id);
        if (!$this->repositorio->retirar($id)) {
            throw ExcepcionUsuario::noEncontrado();
        }
    }

    public function cambiarContrasena(int $id, array $cuerpo): void
    {
        $this->validarId($id);
        $this->validarCamposPermitidos($cuerpo, ['password']);
        $hash = password_hash($this->validarContrasena($cuerpo['password'] ?? null), PASSWORD_DEFAULT);
        if (!$this->repositorio->actualizarHash($id, $hash)) {
            throw ExcepcionUsuario::noEncontrado();
        }
    }

    private function validarId(int $id): void
    {
        if ($id < 1) {
            throw new InvalidArgumentException('El identificador debe ser positivo.');
        }
    }

    private function validarCamposPermitidos(array $datos, array $permitidos): void
    {
        foreach (array_keys($datos) as $campo) {
            if (!is_string($campo) || !in_array($campo, $permitidos, true)) {
                throw new InvalidArgumentException('La solicitud contiene campos no permitidos.');
            }
        }
    }

    private function validarUsername(mixed $valor): string
    {
        if (!is_string($valor) || trim($valor) === '') {
            throw new InvalidArgumentException('El username es obligatorio.');
        }
        $texto = trim($valor);
        if ($this->longitud($texto) > 100) {
            throw new InvalidArgumentException('El username no debe superar 100 caracteres.');
        }
        return $texto;
    }

    private function validarEmail(mixed $valor): string
    {
        if (!is_string($valor) || trim($valor) === '') {
            throw new InvalidArgumentException('El correo electrónico es obligatorio.');
        }
        $texto = trim($valor);
        if ($this->longitud($texto) > 150 || filter_var($texto, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Debe indicar un correo electrónico válido (máximo 150 caracteres).');
        }
        return $texto;
    }

    private function validarNombre(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }
        if (!is_string($valor) || $this->longitud(trim($valor)) > 200) {
            throw new InvalidArgumentException('El nombre completo debe ser texto de máximo 200 caracteres.');
        }
        return trim($valor) === '' ? null : trim($valor);
    }

    private function validarContrasena(mixed $valor): string
    {
        // Máximo 72 bytes: PHP 8.3 utiliza bcrypt por defecto, que trunca más allá de 72.
        // Mínimo 12 por coherencia con el bootstrap de la fase 1.
        if (!is_string($valor) || strlen($valor) < 12 || strlen($valor) > 72) {
            throw new InvalidArgumentException('La contraseña debe tener entre 12 y 72 bytes.');
        }
        return $valor;
    }

    private function longitud(string $texto): int
    {
        $resultado = preg_match_all('/./us', $texto);
        if ($resultado === false) {
            throw new InvalidArgumentException('El texto debe estar codificado en UTF-8.');
        }
        return $resultado;
    }
}
