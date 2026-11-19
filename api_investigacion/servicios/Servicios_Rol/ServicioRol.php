<?php

declare(strict_types=1);

final class ServicioRol implements IServicioRol
{
    public function __construct(private IRepositorioRol $repositorio)
    {
    }

    public function listar(): array
    {
        return array_map(
            static fn (Rol $rol): array => $rol->aArray(),
            $this->repositorio->listar()
        );
    }

    public function obtener(int $id): array
    {
        $this->validarId($id);

        return $this->serializar(
            $this->repositorio->obtener($id)
        );
    }

    private function serializar(?Rol $rol): array
    {
        if ($rol === null) {
            throw ExcepcionRol::noEncontrado();
        }

        return $rol->aArray();
    }

    private function validarId(int $id): void
    {
        if ($id < 1) {
            throw new InvalidArgumentException(
                'El identificador debe ser positivo.'
            );
        }
    }
}
