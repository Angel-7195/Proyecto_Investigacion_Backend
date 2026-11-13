<?php

declare(strict_types=1);

/** Datos públicos de un usuario. Deliberadamente NO contiene contraseña ni hash. */
final readonly class Usuario
{
    public function __construct(
        public int $id,
        public string $username,
        public string $email,
        public ?string $nombreCompleto,
        public ?string $fechaCreacion = null,
        public ?string $fechaActualizacion = null
    ) {
    }

    public static function desdeFila(array $fila): self
    {
        return new self(
            (int) $fila['id'],
            (string) $fila['username'],
            (string) $fila['email'],
            isset($fila['nombre_completo']) ? (string) $fila['nombre_completo'] : null,
            isset($fila['fecha_creacion']) ? (string) $fila['fecha_creacion'] : null,
            isset($fila['fecha_actualizacion']) ? (string) $fila['fecha_actualizacion'] : null
        );
    }

    public function aArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'nombre_completo' => $this->nombreCompleto,
            'fecha_creacion' => $this->fechaCreacion,
            'fecha_actualizacion' => $this->fechaActualizacion,
        ];
    }
}
