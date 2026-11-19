<?php

declare(strict_types=1);

final class ServicioRol implements IServicioRol
{
    public function __construct(private IRepositorioRol $repositorio)
    {
    }

    public function listar(): array
    {
        return array_map(static fn (Rol $rol): array => $rol->aArray(), $this->repositorio->listar());
    }

    public function obtener(int $id): array
    {
        $this->validarId($id);
        return $this->serializar($this->repositorio->obtener($id));
    }

    public function crear(array $cuerpo): array
    {
        $datos = $this->validar($cuerpo, true);
        return $this->repositorio->crear($datos['nombre'], $datos['descripcion'])->aArray();
    }

    public function reemplazar(int $id, array $cuerpo): array
    {
        $this->validarId($id);
        return $this->serializar($this->repositorio->actualizar($id, $this->validar($cuerpo, true)));
    }

    public function actualizar(int $id, array $cuerpo): array
    {
        $this->validarId($id);
        return $this->serializar($this->repositorio->actualizar($id, $this->validar($cuerpo, false)));
    }

    public function retirar(int $id): void
    {
        $this->validarId($id);
        if (!$this->repositorio->retirar($id)) {
            throw ExcepcionRol::noEncontrado();
        }
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
            throw new InvalidArgumentException('El identificador debe ser positivo.');
        }
    }

    private function validar(array $cuerpo, bool $completo): array
    {
        if (array_diff(array_keys($cuerpo), ['nombre', 'descripcion']) !== []) {
            throw new InvalidArgumentException('La solicitud contiene campos no permitidos.');
        }
        if (!$completo && $cuerpo === []) {
            throw new InvalidArgumentException('Debe enviar al menos un campo para actualizar.');
        }

        $datos = [];
        if ($completo || array_key_exists('nombre', $cuerpo)) {
            $nombre = is_string($cuerpo['nombre'] ?? null) ? trim($cuerpo['nombre']) : '';
            $longitud = preg_match_all('/./us', $nombre);
            if ($longitud === false || $longitud === 0 || $longitud > 100) {
                throw new InvalidArgumentException('El nombre debe tener entre 1 y 100 caracteres.');
            }
            $datos['nombre'] = $nombre;
        }
        if ($completo || array_key_exists('descripcion', $cuerpo)) {
            $descripcion = $cuerpo['descripcion'] ?? null;
            if ($descripcion !== null && (!is_string($descripcion) || strlen($descripcion) > 65535
                || preg_match('//u', $descripcion) !== 1)) {
                throw new InvalidArgumentException('La descripción debe ser texto válido de máximo 65.535 bytes o nula.');
            }
            $datos['descripcion'] = $descripcion;
        }
        return $datos;
    }
}
