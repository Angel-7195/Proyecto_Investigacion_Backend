<?php

declare(strict_types=1);

require_once __DIR__ . '/IRepositorioSemillero.php';
require_once __DIR__ . '/../../excepciones/ConflictoExcepcion.php';

/**
 * Persistencia de semilleros mediante PDO y consultas preparadas.
 * Solo devuelve registros activos. No ejecuta eliminaciones físicas.
 */
class RepositorioSemilleroMariaDB implements IRepositorioSemillero
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    /** @return Semillero[] */
    public function obtenerTodos(): array
    {
        $sentencia = $this->conexion->prepare('
            SELECT id, nombre, fecha_fundacion, grupo_investigacion
            FROM semillero
            WHERE activo = TRUE
            ORDER BY id
        ');
        $sentencia->execute();

        $semilleros = [];
        foreach ($sentencia->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $semilleros[] = $this->armarSemillero($fila);
        }
        return $semilleros;
    }

    public function obtenerPorId(int $id): ?Semillero
    {
        $sentencia = $this->conexion->prepare('
            SELECT id, nombre, fecha_fundacion, grupo_investigacion
            FROM semillero
            WHERE id = :id AND activo = TRUE
        ');
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        $sentencia->execute();

        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $this->armarSemillero($fila);
    }

    public function crear(Semillero $semillero): int
    {
        // id no es AUTO_INCREMENT. El campo activo utiliza el DEFAULT TRUE.
        $sentencia = $this->conexion->prepare('
            INSERT INTO semillero (id, nombre, fecha_fundacion, grupo_investigacion)
            VALUES (:id, :nombre, :fecha_fundacion, :grupo_investigacion)
        ');
        foreach ($semillero->toArray() as $campo => $valor) {
            $this->vincularCampo($sentencia, $campo, $valor);
        }
        $this->ejecutarEscritura($sentencia);
        return $sentencia->rowCount();
    }

    public function reemplazar(int $id, array $datos): int
    {
        $sentencia = $this->conexion->prepare('
            UPDATE semillero
            SET nombre = :nombre,
                fecha_fundacion = :fecha_fundacion,
                grupo_investigacion = :grupo_investigacion
            WHERE id = :id AND activo = TRUE
        ');
        foreach (['nombre', 'fecha_fundacion', 'grupo_investigacion'] as $campo) {
            $this->vincularCampo($sentencia, $campo, $datos[$campo]);
        }
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        $this->ejecutarEscritura($sentencia);
        return $sentencia->rowCount();
    }

    public function actualizar(int $id, array $datos): int
    {
        $asignaciones = [];
        $recibidos = [];

        // Lista blanca: el usuario nunca controla directamente nombres de columnas.
        foreach (['nombre', 'fecha_fundacion', 'grupo_investigacion'] as $campo) {
            if (array_key_exists($campo, $datos)) {
                $asignaciones[] = $campo . ' = :' . $campo;
                $recibidos[$campo] = $datos[$campo];
            }
        }

        if ($asignaciones === []) {
            throw new InvalidArgumentException('No hay campos editables para actualizar.');
        }

        $sentencia = $this->conexion->prepare(
            'UPDATE semillero SET ' . implode(', ', $asignaciones)
            . ' WHERE id = :id AND activo = TRUE'
        );
        foreach ($recibidos as $campo => $valor) {
            $this->vincularCampo($sentencia, $campo, $valor);
        }
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        $this->ejecutarEscritura($sentencia);
        return $sentencia->rowCount();
    }

    public function retirar(int $id): int
    {
        $sentencia = $this->conexion->prepare('
            UPDATE semillero
            SET activo = FALSE
            WHERE id = :id AND activo = TRUE
        ');
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        $sentencia->execute();
        return $sentencia->rowCount();
    }

    private function vincularCampo(PDOStatement $sentencia, string $campo, mixed $valor): void
    {
        $tipo = in_array($campo, ['id', 'grupo_investigacion'], true)
            ? PDO::PARAM_INT
            : PDO::PARAM_STR;
        $sentencia->bindValue(':' . $campo, $valor, $tipo);
    }

    private function ejecutarEscritura(PDOStatement $sentencia): void
    {
        try {
            $sentencia->execute();
        } catch (PDOException $excepcion) {
            $codigo = (int) ($excepcion->errorInfo[1] ?? 0);
            if ($codigo === 1062) {
                throw new ConflictoExcepcion(
                    'Ya existe un semillero con ese identificador.', 0, $excepcion
                );
            }
            if ($codigo === 1452) {
                throw new ConflictoExcepcion(
                    'El grupo de investigación indicado no existe.', 0, $excepcion
                );
            }
            throw $excepcion;
        }
    }

    private function armarSemillero(array $fila): Semillero
    {
        return new Semillero(
            (int) $fila['id'],
            (string) $fila['nombre'],
            (string) $fila['fecha_fundacion'],
            (int) $fila['grupo_investigacion']
        );
    }
}
