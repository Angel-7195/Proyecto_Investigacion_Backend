<?php

declare(strict_types=1);

require_once __DIR__ . '/IRepositorioObjetivoDesarrolloSostenible.php';
require_once __DIR__ . '/../excepciones/ConflictoExcepcion.php';

class RepositorioObjetivoDesarrolloSostenibleMariaDB implements IRepositorioObjetivoDesarrolloSostenible
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function listar(): array
    {
        $sentencia = $this->conexion->prepare(
            'SELECT id, nombre, categoria FROM objetivo_desarrollo_sostenible WHERE activo = TRUE ORDER BY id'
        );
        $sentencia->execute();
        $registros = [];
        foreach ($sentencia->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $registros[] = $this->construirModelo($fila);
        }
        return $registros;
    }

    public function obtenerPorId(int $id): ?ObjetivoDesarrolloSostenible
    {
        $sentencia = $this->conexion->prepare(
            'SELECT id, nombre, categoria FROM objetivo_desarrollo_sostenible WHERE id = :id AND activo = TRUE'
        );
        $sentencia->execute([':id' => $id]);
        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $this->construirModelo($fila);
    }

    public function crear(ObjetivoDesarrolloSostenible $registro): int
    {
        $sentencia = $this->conexion->prepare(
            'INSERT INTO objetivo_desarrollo_sostenible (id, nombre, categoria, activo) '
            . 'VALUES (:id, :nombre, :categoria, TRUE)'
        );
        try {
            $sentencia->execute([
                ':id' => $registro->getId(),
            ':nombre' => $registro->getNombre(),
            ':categoria' => $registro->getCategoria(),
            ]);
        } catch (PDOException $excepcion) {
            if ((int) ($excepcion->errorInfo[1] ?? 0) === 1062) {
                throw new ConflictoExcepcion(
                    'Ya existe un objetivo de desarrollo sostenible con esa llave.',
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
            'UPDATE objetivo_desarrollo_sostenible SET nombre = :nombre,
                categoria = :categoria '
            . 'WHERE id = :id AND activo = TRUE'
        );
        $sentencia->execute([
            ':id' => $id,
            ':nombre' => $datos['nombre'],
            ':categoria' => $datos['categoria'],
        ]);
        // ensamblador.php configura MYSQL_ATTR_FOUND_ROWS para reconocer
        // un PUT válido incluso cuando todos los valores son iguales.
        return $sentencia->rowCount();
    }

    public function actualizar(int $id, array $datos): int
    {
        $permitidos = ['nombre', 'categoria'];
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
            'UPDATE objetivo_desarrollo_sostenible SET ' . implode(', ', $asignaciones)
            . ' WHERE id = :id AND activo = TRUE'
        );
        $sentencia->execute($parametros);
        return $sentencia->rowCount();
    }

    public function retirar(int $id): int
    {
        $sentencia = $this->conexion->prepare(
            'UPDATE objetivo_desarrollo_sostenible SET activo = FALSE '
            . 'WHERE id = :id AND activo = TRUE'
        );
        $sentencia->execute([':id' => $id]);
        return $sentencia->rowCount();
    }

    private function construirModelo(array $fila): ObjetivoDesarrolloSostenible
    {
        return new ObjetivoDesarrolloSostenible(
            (int) $fila['id'],
            (string) $fila['nombre'],
            (string) $fila['categoria']
        );
    }
}
