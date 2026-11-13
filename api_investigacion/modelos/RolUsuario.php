<?php

declare(strict_types=1);

/** La relación solo posee dos columnas: clave primaria compuesta. */
final class RolUsuario
{
    public function __construct(public readonly int $usuarioId, public readonly int $rolId)
    {
    }

    public function serializar(): array
    {
        return ['usuario_id' => $this->usuarioId, 'rol_id' => $this->rolId];
    }
}
