<?php

declare(strict_types=1);

require_once __DIR__ . '/IRepositorioAreaAplicacion.php';
require_once __DIR__ . '/../excepciones/ConflictoExcepcion.php';

class RepositorioAreaAplicacionMariaDB implements IRepositorioAreaAplicacion
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function listar(): array
    {
        $sentencia = $this->conexion->prepare(
            'SELECT id, nombre FROM area_aplicacion WHERE activo = TRUE ORDER BY id'
        );
        $sentencia->execute();
        $registros = [];
        foreach ($sentencia->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $registros[] = $this->construirModelo($fila);
        }
        return $registros;
    }

    public function obtenerPorId(int $id): ?AreaAplicacion
    {
        $sentencia = $this->conexion->prepare(
            'SELECT id, nombre FROM area_aplicacion WHERE id = :id AND activo = TRUE'
        );
        $sentencia->execute([':id' => $id]);
        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $this->construirModelo($fila);
    }

    public function crear(AreaAplicacion $registro): int
    {
        $sentencia = $this->conexion->prepare(
            'INSERT INTO area_aplicacion (id, nombre, activo) '
            . 'VALUES (:id, :nombre, TRUE)'
        );
        try {
            $sentencia->execute([
                ':id' => $registro->getId(),
            ':nombre' => $registro->getNombre(),
            ]);
        } catch (PDOException $excepcion) {
            if ((int) ($excepcion->errorInfo[1] ?? 0) === 1062) {
                throw new ConflictoExcepcion(
                    'Ya existe un área de aplicación con esa llave.',
                    0,
                    $excepcion
                );
            }
            throw $excepcion;
        }
        return $sentencia->rowCount();
    }

    public function reemplazar(int $id, array $datos): int
    {
        $sentencia = $this->conexion->prepare(
            'UPDATE area_aplicacion SET nombre = :nombre '
            . 'WHERE id = :id AND activo = TRUE'
        );
        $sentencia->execute([
            ':id' => $id,
            ':nombre' => $datos['nombre'],
        ]);
        // ensamblador.php configura MYSQL_ATTR_FOUND_ROWS para reconocer
        // un PUT válido incluso cuando todos los valores son iguales.
        return $sentencia->rowCount();
    }

    public function actualizar(int $id, array $datos): int
    {
        $permitidos = ['nombre'];
        $asignaciones = [];
        $parametros = [':id' => $id];

        foreach ($permitidos as $campo) {
            if (array_key_exists($campo, $datos)) {
                $asignaciones[] = $campo . ' = :' . $campo;
                $parametros[':' . $campo] = $datos[$campo];
            }
        }
        if ($asignaciones === []) {
            throw new InvalidArgumentException(
                'No se envió ningún campo para actualizar.'
            );
        }
        $sentencia = $this->conexion->prepare(
            'UPDATE area_aplicacion SET ' . implode(', ', $asignaciones)
            . ' WHERE id = :id AND activo = TRUE'
        );
        $sentencia->execute($parametros);
        return $sentencia->rowCount();
    }

    public function retirar(int $id): int
    {
        $sentencia = $this->conexion->prepare(
            'UPDATE area_aplicacion SET activo = FALSE '
            . 'WHERE id = :id AND activo = TRUE'
        );
        $sentencia->execute([':id' => $id]);
        return $sentencia->rowCount();
    }

    private function construirModelo(array $fila): AreaAplicacion
    {
        return new AreaAplicacion(
            (int) $fila['id'],
            (string) $fila['nombre']
        );
    }
}
