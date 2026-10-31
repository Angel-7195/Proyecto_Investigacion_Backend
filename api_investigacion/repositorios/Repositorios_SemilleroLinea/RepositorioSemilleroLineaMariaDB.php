<?php

declare(strict_types=1);

require_once __DIR__ . '/IRepositorioSemilleroLinea.php';
require_once __DIR__ . '/../../excepciones/ConflictoExcepcion.php';

/** Consultas SQL preparadas para la tabla semillero_linea. */
class RepositorioSemilleroLineaMariaDB implements IRepositorioSemilleroLinea
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    /** @return SemilleroLinea[] */
    public function obtenerTodos(): array
    {
        $sentencia = $this->conexion->prepare('
            SELECT semillero, linea_investigacion
            FROM semillero_linea
            WHERE activo = TRUE
            ORDER BY semillero, linea_investigacion
        ');
        $sentencia->execute();

        $resultado = [];
        foreach ($sentencia->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $resultado[] = $this->construirModelo($fila);
        }
        return $resultado;
    }

    public function obtenerPorClave(int $semilleroId, int $lineaInvestigacion): ?SemilleroLinea
    {
        $sentencia = $this->conexion->prepare('
            SELECT semillero, linea_investigacion
            FROM semillero_linea
            WHERE semillero = :semillero
              AND linea_investigacion = :linea_investigacion
              AND activo = TRUE
        ');
        $this->vincularClaves($sentencia, $semilleroId, $lineaInvestigacion);
        $sentencia->execute();

        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $this->construirModelo($fila);
    }

    public function crear(SemilleroLinea $asociacion): int
    {
        $sentencia = $this->conexion->prepare('
            INSERT INTO semillero_linea (semillero, linea_investigacion)
            VALUES (:semillero, :linea_investigacion)
        ');
        $this->vincularClaves(
            $sentencia,
            $asociacion->getSemillero(),
            $asociacion->getLineaInvestigacion()
        );

        try {
            $sentencia->execute();
        } catch (PDOException $excepcion) {
            $codigo = (int) ($excepcion->errorInfo[1] ?? 0);
            if ($codigo === 1062) {
                // Una asociación retirada sigue ocupando su clave compuesta.
                throw new ConflictoExcepcion(
                    'Ya existe esa combinación de semillero y línea de investigación.',
                    0,
                    $excepcion
                );
            }
            if ($codigo === 1452) {
                throw new ConflictoExcepcion(
                    'El semillero o la línea de investigación no existe.',
                    0,
                    $excepcion
                );
            }
            throw $excepcion;
        }

        return $sentencia->rowCount();
    }

    public function retirar(int $semilleroId, int $lineaInvestigacion): int
    {
        $sentencia = $this->conexion->prepare('
            UPDATE semillero_linea
            SET activo = FALSE
            WHERE semillero = :semillero
              AND linea_investigacion = :linea_investigacion
              AND activo = TRUE
        ');
        $this->vincularClaves($sentencia, $semilleroId, $lineaInvestigacion);
        $sentencia->execute();
        return $sentencia->rowCount();
    }

    private function vincularClaves(PDOStatement $sentencia, int $semilleroId, int $lineaInvestigacion): void
    {
        $sentencia->bindValue(':semillero', $semilleroId, PDO::PARAM_INT);
        $sentencia->bindValue(':linea_investigacion', $lineaInvestigacion, PDO::PARAM_INT);
    }

    /** @param array<string,mixed> $fila */
    private function construirModelo(array $fila): SemilleroLinea
    {
        return new SemilleroLinea(
            (int) $fila['semillero'],
            (int) $fila['linea_investigacion']
        );
    }
}
