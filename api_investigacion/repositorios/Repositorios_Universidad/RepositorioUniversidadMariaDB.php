<?php

declare(strict_types=1);

require_once __DIR__ . '/IRepositorioUniversidad.php';
require_once __DIR__ . '/../../modelos/Universidad.php';
require_once __DIR__ . '/../../excepciones/ConflictoExcepcion.php';

class RepositorioUniversidadMariaDB implements IRepositorioUniversidad
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    /**
     * @return Universidad[]
     */
    public function obtenerTodos(): array
    {
        $sql = '
            SELECT
                id,
                nombre,
                tipo,
                ciudad
            FROM universidad
            WHERE activo = TRUE
            ORDER BY id
        ';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();

        $filas = $sentencia->fetchAll(PDO::FETCH_ASSOC);

        $universidades = [];

        foreach ($filas as $fila) {
            $universidades[] = $this->armarUniversidad($fila);
        }

        return $universidades;
    }

    public function obtenerPorId(int $id): ?Universidad
    {
        $sql = '
            SELECT
                id,
                nombre,
                tipo,
                ciudad
            FROM universidad
            WHERE id = :id
              AND activo = TRUE
        ';

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindValue(
            ':id',
            $id,
            PDO::PARAM_INT
        );

        $sentencia->execute();

        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);

        if ($fila === false) {
            return null;
        }

        return $this->armarUniversidad($fila);
    }

    public function crear(Universidad $universidad): int
    {
        $sql = '
            INSERT INTO universidad (
                id,
                nombre,
                tipo,
                ciudad
            )
            VALUES (
                :id,
                :nombre,
                :tipo,
                :ciudad
            )
        ';

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindValue(
            ':id',
            $universidad->getId(),
            PDO::PARAM_INT
        );

        $sentencia->bindValue(
            ':nombre',
            $universidad->getNombre(),
            PDO::PARAM_STR
        );

        $sentencia->bindValue(
            ':tipo',
            $universidad->getTipo(),
            PDO::PARAM_STR
        );

        $sentencia->bindValue(
            ':ciudad',
            $universidad->getCiudad(),
            PDO::PARAM_STR
        );

        try {
            $sentencia->execute();
        } catch (PDOException $excepcion) {
            $codigoMariaDB = $excepcion->errorInfo[1] ?? null;

            if ($codigoMariaDB === 1062) {
                throw new ConflictoExcepcion(
                    'Ya existe una universidad con esa llave.',
                    0,
                    $excepcion
                );
            }

            throw $excepcion;
        }

        return $sentencia->rowCount();
    }

    public function reemplazar(
        int $id,
        array $datos
    ): int {
        $sql = '
            UPDATE universidad
            SET
                nombre = :nombre,
                tipo = :tipo,
                ciudad = :ciudad
            WHERE id = :id
              AND activo = TRUE
        ';

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindValue(
            ':nombre',
            $datos['nombre'],
            PDO::PARAM_STR
        );

        $sentencia->bindValue(
            ':tipo',
            $datos['tipo'],
            PDO::PARAM_STR
        );

        $sentencia->bindValue(
            ':ciudad',
            $datos['ciudad'],
            PDO::PARAM_STR
        );

        $sentencia->bindValue(
            ':id',
            $id,
            PDO::PARAM_INT
        );

        $sentencia->execute();

        return $sentencia->rowCount();
    }

    public function actualizar(
        int $id,
        array $datos
    ): int {
        $camposPermitidos = [
            'nombre',
            'tipo',
            'ciudad',
        ];

        $asignaciones = [];
        $parametros = [];

        foreach ($camposPermitidos as $campo) {
            if (array_key_exists($campo, $datos)) {
                $asignaciones[] = "{$campo} = :{$campo}";
                $parametros[$campo] = $datos[$campo];
            }
        }

        if ($asignaciones === []) {
            return 0;
        }

        $sql = '
            UPDATE universidad
            SET ' . implode(', ', $asignaciones) . '
            WHERE id = :id
              AND activo = TRUE
        ';

        $sentencia = $this->conexion->prepare($sql);

        foreach ($parametros as $campo => $valor) {
            $sentencia->bindValue(
                ':' . $campo,
                $valor,
                PDO::PARAM_STR
            );
        }

        $sentencia->bindValue(
            ':id',
            $id,
            PDO::PARAM_INT
        );

        $sentencia->execute();

        return $sentencia->rowCount();
    }

    public function retirar(int $id): int
    {
        $sql = '
            UPDATE universidad
            SET activo = FALSE
            WHERE id = :id
              AND activo = TRUE
        ';

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindValue(
            ':id',
            $id,
            PDO::PARAM_INT
        );

        $sentencia->execute();

        return $sentencia->rowCount();
    }

    private function armarUniversidad(array $fila): Universidad
    {
        return new Universidad(
            (int) $fila['id'],
            (string) $fila['nombre'],
            (string) $fila['tipo'],
            (string) $fila['ciudad']
        );
    }
}