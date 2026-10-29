<?php

declare(strict_types=1);

require_once __DIR__ . '/IRepositorioOdsLinea.php';
require_once __DIR__ . '/../../excepciones/ConflictoExcepcion.php';

/** Acceso a la tabla ods_linea mediante PDO y consultas preparadas. */
class RepositorioOdsLineaMariaDB implements IRepositorioOdsLinea
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    /** @return OdsLinea[] */
    public function obtenerTodos(): array
    {
        $sentencia = $this->conexion->prepare('
            SELECT linea_investigacion, ods
            FROM ods_linea
            WHERE activo = TRUE
            ORDER BY linea_investigacion, ods
        ');
        $sentencia->execute();

        $resultado = [];
        foreach ($sentencia->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $resultado[] = $this->construirModelo($fila);
        }
        return $resultado;
    }

    public function obtenerPorClave(int $lineaInvestigacion, int $ods): ?OdsLinea
    {
        $sentencia = $this->conexion->prepare('
            SELECT linea_investigacion, ods
            FROM ods_linea
            WHERE linea_investigacion = :linea_investigacion
              AND ods = :ods
              AND activo = TRUE
        ');
        $this->vincularClaves($sentencia, $lineaInvestigacion, $ods);
        $sentencia->execute();

        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $this->construirModelo($fila);
    }

    public function crear(OdsLinea $asociacion): int
    {
        $sentencia = $this->conexion->prepare('
            INSERT INTO ods_linea (linea_investigacion, ods)
            VALUES (:linea_investigacion, :ods)
        ');
        $this->vincularClaves(
            $sentencia,
            $asociacion->getLineaInvestigacion(),
            $asociacion->getOds()
        );

        try {
            $sentencia->execute();
        } catch (PDOException $excepcion) {
            $codigo = (int) ($excepcion->errorInfo[1] ?? 0);
            if ($codigo === 1062) {
                // La PK permanece ocupada incluso cuando activo = FALSE.
                throw new ConflictoExcepcion(
                    'Ya existe esa combinación de línea de investigación y ODS.', 0, $excepcion
                );
            }
            if ($codigo === 1452) {
                throw new ConflictoExcepcion(
                    'La línea de investigación o el ODS no existe.', 0, $excepcion
                );
            }
            throw $excepcion;
        }

        return $sentencia->rowCount();
    }

    public function retirar(int $lineaInvestigacion, int $ods): int
    {
        $sentencia = $this->conexion->prepare('
            UPDATE ods_linea
            SET activo = FALSE
            WHERE linea_investigacion = :linea_investigacion
              AND ods = :ods
              AND activo = TRUE
        ');
        $this->vincularClaves($sentencia, $lineaInvestigacion, $ods);
        $sentencia->execute();
        return $sentencia->rowCount();
    }

    private function vincularClaves(PDOStatement $sentencia, int $lineaInvestigacion, int $ods): void
    {
        $sentencia->bindValue(':linea_investigacion', $lineaInvestigacion, PDO::PARAM_INT);
        $sentencia->bindValue(':ods', $ods, PDO::PARAM_INT);
    }

    /** @param array<string,mixed> $fila */
    private function construirModelo(array $fila): OdsLinea
    {
        return new OdsLinea(
            (int) $fila['linea_investigacion'],
            (int) $fila['ods']
        );
    }
}
