<?php

declare(strict_types=1);

final class ExcepcionRolUsuario extends RuntimeException
{
    public function __construct(string $mensaje, private int $estadoHttp)
    {
        parent::__construct($mensaje);
    }

    public function estadoHttp(): int { return $this->estadoHttp; }
    public static function noEncontrado(): self { return new self('Asignación no encontrada.', 404); }
    public static function conflicto(string $mensaje): self { return new self($mensaje, 409); }
    public static function referenciaInvalida(): self { return new self('Usuario o rol inexistente o inactivo.', 422); }
}
