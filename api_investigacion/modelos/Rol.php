<?php

declare(strict_types=1);

final class Rol
{
    public function __construct(
        public readonly int $id,
        public readonly string $nombre,
        public readonly ?string $descripcion
    ) {
    }

    public static function desdeFila(array $fila): self
    {
        return new self((int) $fila['id'], $fila['nombre'], $fila['descripcion']);
    }

    public function aArray(): array
    {
        return ['id' => $this->id, 'nombre' => $this->nombre, 'descripcion' => $this->descripcion];
    }
}
