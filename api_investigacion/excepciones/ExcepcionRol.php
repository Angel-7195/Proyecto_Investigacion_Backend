<?php

declare(strict_types=1);

final class ExcepcionRol extends RuntimeException
{
    private function __construct(string $mensaje, private int $estadoHttp)
    {
        parent::__construct($mensaje);
    }

    public static function noEncontrado(): self
    {
        return new self('Rol no encontrado.', 404);
    }

    public static function conflicto(string $mensaje): self
    {
        return new self($mensaje, 409);
    }

    public function estadoHttp(): int
    {
        return $this->estadoHttp;
    }
}
