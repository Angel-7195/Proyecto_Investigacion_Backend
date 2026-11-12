<?php

declare(strict_types=1);

final class ServicioRolUsuario implements IServicioRolUsuario
{
    public function __construct(private IRepositorioRolUsuario $repositorio) {}

    public function listar(): array
    {
        return array_map(static fn (RolUsuario $relacion): array => $relacion->serializar(), $this->repositorio->listar());
    }

    public function obtener(int $usuarioId, int $rolId): array
    {
        self::comprobarId($usuarioId);
        self::comprobarId($rolId);
        $relacion = $this->repositorio->obtener($usuarioId, $rolId);
        if ($relacion === null) { throw ExcepcionRolUsuario::noEncontrado(); }
        return $relacion->serializar();
    }

    public function crear(array $datos): array
    {
        if (array_keys($datos) !== ['usuario_id', 'rol_id'] &&
            (count($datos) !== 2 || !array_key_exists('usuario_id', $datos) || !array_key_exists('rol_id', $datos))) {
            throw new InvalidArgumentException('Debe enviar únicamente usuario_id y rol_id.');
        }
        foreach (['usuario_id', 'rol_id'] as $campo) {
            if (!is_int($datos[$campo]) || $datos[$campo] < 1) {
                throw new InvalidArgumentException("$campo debe ser un entero positivo.");
            }
        }
        return $this->repositorio->crear($datos['usuario_id'], $datos['rol_id'])->serializar();
    }

    public function retirar(int $usuarioId, int $rolId): void
    {
        self::comprobarId($usuarioId);
        self::comprobarId($rolId);
        if (!$this->repositorio->retirar($usuarioId, $rolId)) {
            throw ExcepcionRolUsuario::noEncontrado();
        }
    }

    private static function comprobarId(int $id): void
    {
        if ($id < 1) { throw new InvalidArgumentException('Identificador inválido.'); }
    }
}
