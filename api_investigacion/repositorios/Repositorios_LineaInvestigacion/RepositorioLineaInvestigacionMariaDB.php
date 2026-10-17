<?php

declare(strict_types=1);

require_once __DIR__ . '/IRepositorioLineaInvestigacion.php';
require_once __DIR__ . '/../../modelos/LineaInvestigacion.php';

class RepositorioLineaInvestigacionMariaDB
    implements IRepositorioLineaInvestigacion
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtenerTodos(): array
    {
        $sql = '
            SELECT id, nombre, descripcion
            FROM linea_investigacion
            WHERE activo = TRUE
            ORDER BY id
        ';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();

        $filas = $sentencia->fetchAll(PDO::FETCH_ASSOC);

        $lineas = [];

        foreach ($filas as $fila) {
            $lineas[] = $this->armarLineaInvestigacion($fila);
        }

        return $lineas;
    }

    public function obtenerPorId(int $id): ?LineaInvestigacion
    {
        $sql = '
            SELECT id, nombre, descripcion
            FROM linea_investigacion
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

        return $this->armarLineaInvestigacion($fila);
    }

    public function crear(
        LineaInvestigacion $lineaInvestigacion
    ): int {
        $sql = '
            INSERT INTO linea_investigacion (
                nombre,
                descripcion
            )
            VALUES (
                :nombre,
                :descripcion
            )
        ';

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindValue(
            ':nombre',
            $lineaInvestigacion->getNombre(),
            PDO::PARAM_STR
        );

        $sentencia->bindValue(
            ':descripcion',
            $lineaInvestigacion->getDescripcion(),
            PDO::PARAM_STR
        );

        $sentencia->execute();

        $id = (int) $this->conexion->lastInsertId();

        if ($id <= 0) {
            throw new RuntimeException(
                'No fue posible obtener el id generado.'
            );
        }

        return $id;
    }

    public function reemplazar(
        int $id,
        array $datos
    ): int {
        $sql = '
            UPDATE linea_investigacion
            SET nombre = :nombre,
                descripcion = :descripcion
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
            ':descripcion',
            $datos['descripcion'],
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
            'descripcion',
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
            UPDATE linea_investigacion
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
            UPDATE linea_investigacion
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

    private function armarLineaInvestigacion(
        array $fila
    ): LineaInvestigacion {
        return new LineaInvestigacion(
            (int) $fila['id'],
            (string) $fila['nombre'],
            (string) $fila['descripcion']
        );
    }
}