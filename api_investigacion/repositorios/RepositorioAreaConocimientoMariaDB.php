<?php

declare(strict_types=1);

require_once __DIR__ . '/IRepositorioAreaConocimiento.php';
require_once __DIR__ . '/../excepciones/ConflictoExcepcion.php';

class RepositorioAreaConocimientoMariaDB implements IRepositorioAreaConocimiento
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function listar(): array
    {
        $sentencia = $this->conexion->prepare(
            'SELECT id, gran_area, area, disciplina FROM area_conocimiento WHERE activo = TRUE ORDER BY id'
        );
        $sentencia->execute();
        $registros = [];
        foreach ($sentencia->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $registros[] = $this->construirModelo($fila);
        }
        return $registros;
    }

    public function obtenerPorId(string $id): ?AreaConocimiento
    {
        $sentencia = $this->conexion->prepare(
            'SELECT id, gran_area, area, disciplina FROM area_conocimiento WHERE id = :id AND activo = TRUE'
        );
        $sentencia->execute([':id' => $id]);
        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $this->construirModelo($fila);
    }

    public function crear(AreaConocimiento $registro): int
    {
        $sentencia = $this->conexion->prepare(
            'INSERT INTO area_conocimiento (id, gran_area, area, disciplina, activo) '
            . 'VALUES (:id, :gran_area, :area, :disciplina, TRUE)'
        );
        try {
            $sentencia->execute([
                ':id' => $registro->getId(),
            ':gran_area' => $registro->getGranArea(),
            ':area' => $registro->getArea(),
            ':disciplina' => $registro->getDisciplina(),
            ]);
        } catch (PDOException $excepcion) {
            if ((int) ($excepcion->errorInfo[1] ?? 0) === 1062) {
                throw new ConflictoExcepcion(
                    'Ya existe un área de conocimiento con esa llave.',
                    0,
                    $excepcion
                );
            }
            throw $excepcion;
        }
        return $sentencia->rowCount();
    }

    public function reemplazar(string $id, array $datos): int
    {
        $sentencia = $this->conexion->prepare(
            'UPDATE area_conocimiento SET gran_area = :gran_area,
                area = :area,
                disciplina = :disciplina '
            . 'WHERE id = :id AND activo = TRUE'
        );
        $sentencia->execute([
            ':id' => $id,
            ':gran_area' => $datos['gran_area'],
            ':area' => $datos['area'],
            ':disciplina' => $datos['disciplina'],
        ]);
        // ensamblador.php configura MYSQL_ATTR_FOUND_ROWS para reconocer
        // un PUT válido incluso cuando todos los valores son iguales.
        return $sentencia->rowCount();
    }

    public function actualizar(string $id, array $datos): int
    {
        $permitidos = ['gran_area', 'area', 'disciplina'];
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
            'UPDATE area_conocimiento SET ' . implode(', ', $asignaciones)
            . ' WHERE id = :id AND activo = TRUE'
        );
        $sentencia->execute($parametros);
        return $sentencia->rowCount();
    }

    public function retirar(string $id): int
    {
        $sentencia = $this->conexion->prepare(
            'UPDATE area_conocimiento SET activo = FALSE '
            . 'WHERE id = :id AND activo = TRUE'
        );
        $sentencia->execute([':id' => $id]);
        return $sentencia->rowCount();
    }

    private function construirModelo(array $fila): AreaConocimiento
    {
        return new AreaConocimiento(
            $fila['id'],
            (string) $fila['gran_area'],
            (string) $fila['area'],
            (string) $fila['disciplina']
        );
    }
}
