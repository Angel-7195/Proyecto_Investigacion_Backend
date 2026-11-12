<?php

declare(strict_types=1);

/** Excepciones funcionales del CRUD, sin datos de SQL ni secretos. */
final class ExcepcionUsuario extends RuntimeException
{
    private function __construct(string $mensaje, private int $estadoHttp)
    {
        parent::__construct($mensaje);
    }

    public static function noEncontrado(): self
    {
        return new self('Usuario no encontrado.', 404);
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
