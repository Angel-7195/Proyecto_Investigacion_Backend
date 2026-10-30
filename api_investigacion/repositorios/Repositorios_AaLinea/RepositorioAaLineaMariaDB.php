<?php

declare(strict_types=1);

require_once __DIR__ . '/IRepositorioAaLinea.php';
require_once __DIR__ . '/../../excepciones/ConflictoExcepcion.php';

/** Consultas SQL preparadas para la tabla aa_linea. */
class RepositorioAaLineaMariaDB implements IRepositorioAaLinea
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    /** @return AaLinea[] */
    public function obtenerTodos(): array
    {
        $sentencia = $this->conexion->prepare('
            SELECT area_aplicacion, linea_investigacion
            FROM aa_linea
            WHERE activo = TRUE
            ORDER BY area_aplicacion, linea_investigacion
        ');
        $sentencia->execute();

        $resultado = [];
        foreach ($sentencia->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $resultado[] = $this->construirModelo($fila);
        }
        return $resultado;
    }

    public function obtenerPorClave(int $areaAplicacion, int $lineaInvestigacion): ?AaLinea
    {
        $sentencia = $this->conexion->prepare('
            SELECT area_aplicacion, linea_investigacion
            FROM aa_linea
            WHERE area_aplicacion = :area_aplicacion
              AND linea_investigacion = :linea_investigacion
              AND activo = TRUE
        ');
        $this->vincularClaves($sentencia, $areaAplicacion, $lineaInvestigacion);
        $sentencia->execute();

        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $this->construirModelo($fila);
    }

    public function crear(AaLinea $asociacion): int
    {
        $sentencia = $this->conexion->prepare('
            INSERT INTO aa_linea (area_aplicacion, linea_investigacion)
            VALUES (:area_aplicacion, :linea_investigacion)
        ');
        $this->vincularClaves(
            $sentencia,
            $asociacion->getAreaAplicacion(),
            $asociacion->getLineaInvestigacion()
        );

        try {
            $sentencia->execute();
        } catch (PDOException $excepcion) {
            $codigo = (int) ($excepcion->errorInfo[1] ?? 0);
            if ($codigo === 1062) {
                // Una asociación retirada sigue ocupando su clave compuesta.
                throw new ConflictoExcepcion(
                    'Ya existe esa combinación de área de aplicación y línea de investigación.',
                    0,
                    $excepcion
                );
            }
            if ($codigo === 1452) {
                throw new ConflictoExcepcion(
                    'El área de aplicación o la línea de investigación no existe.',
                    0,
                    $excepcion
                );
            }
            throw $excepcion;
        }

        return $sentencia->rowCount();
    }

    public function retirar(int $areaAplicacion, int $lineaInvestigacion): int
    {
        $sentencia = $this->conexion->prepare('
            UPDATE aa_linea
            SET activo = FALSE
            WHERE area_aplicacion = :area_aplicacion
              AND linea_investigacion = :linea_investigacion
              AND activo = TRUE
        ');
        $this->vincularClaves($sentencia, $areaAplicacion, $lineaInvestigacion);
        $sentencia->execute();
        return $sentencia->rowCount();
    }

    private function vincularClaves(PDOStatement $sentencia, int $areaAplicacion, int $lineaInvestigacion): void
    {
        $sentencia->bindValue(':area_aplicacion', $areaAplicacion, PDO::PARAM_INT);
        $sentencia->bindValue(':linea_investigacion', $lineaInvestigacion, PDO::PARAM_INT);
    }

    /** @param array<string,mixed> $fila */
    private function construirModelo(array $fila): AaLinea
    {
        return new AaLinea(
            (int) $fila['area_aplicacion'],
            (int) $fila['linea_investigacion']
        );
    }
}
