<?php

declare(strict_types=1);

require_once __DIR__ . '/IRepositorioAcLinea.php';
require_once __DIR__ . '/../../excepciones/ConflictoExcepcion.php';

/** Única capa de ac_linea que accede a MariaDB mediante PDO. */
class RepositorioAcLineaMariaDB implements IRepositorioAcLinea
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    /** @return AcLinea[] */
    public function obtenerTodos(): array
    {
        $sentencia = $this->conexion->prepare('
            SELECT linea_investigacion, area_conocimiento
            FROM ac_linea
            WHERE activo = TRUE
            ORDER BY linea_investigacion, area_conocimiento
        ');
        $sentencia->execute();

        $resultado = [];
        foreach ($sentencia->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $resultado[] = $this->construirModelo($fila);
        }
        return $resultado;
    }

    public function obtenerPorClave(int $lineaInvestigacion, string $areaConocimiento): ?AcLinea
    {
        $sentencia = $this->conexion->prepare('
            SELECT linea_investigacion, area_conocimiento
            FROM ac_linea
            WHERE linea_investigacion = :linea_investigacion
              AND area_conocimiento = :area_conocimiento
              AND activo = TRUE
        ');
        $this->vincularClaves($sentencia, $lineaInvestigacion, $areaConocimiento);
        $sentencia->execute();

        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $this->construirModelo($fila);
    }

    public function crear(AcLinea $asociacion): int
    {
        $sentencia = $this->conexion->prepare('
            INSERT INTO ac_linea (linea_investigacion, area_conocimiento)
            VALUES (:linea_investigacion, :area_conocimiento)
        ');
        $this->vincularClaves(
            $sentencia,
            $asociacion->getLineaInvestigacion(),
            $asociacion->getAreaConocimiento()
        );

        try {
            $sentencia->execute();
        } catch (PDOException $excepcion) {
            $codigo = (int) ($excepcion->errorInfo[1] ?? 0);
            if ($codigo === 1062) {
                // Incluye las parejas retiradas que conservan la PK física.
                throw new ConflictoExcepcion(
                    'Ya existe esa combinación de línea y área de conocimiento.', 0, $excepcion
                );
            }
            if ($codigo === 1452) {
                throw new ConflictoExcepcion(
                    'La línea de investigación o el área de conocimiento no existe.', 0, $excepcion
                );
            }
            throw $excepcion;
        }

        return $sentencia->rowCount();
    }

    public function retirar(int $lineaInvestigacion, string $areaConocimiento): int
    {
        $sentencia = $this->conexion->prepare('
            UPDATE ac_linea
            SET activo = FALSE
            WHERE linea_investigacion = :linea_investigacion
              AND area_conocimiento = :area_conocimiento
              AND activo = TRUE
        ');
        $this->vincularClaves($sentencia, $lineaInvestigacion, $areaConocimiento);
        $sentencia->execute();
        return $sentencia->rowCount();
    }

    private function vincularClaves(
        PDOStatement $sentencia,
        int $lineaInvestigacion,
        string $areaConocimiento
    ): void {
        $sentencia->bindValue(':linea_investigacion', $lineaInvestigacion, PDO::PARAM_INT);
        $sentencia->bindValue(':area_conocimiento', $areaConocimiento, PDO::PARAM_STR);
    }

    /** @param array<string,mixed> $fila */
    private function construirModelo(array $fila): AcLinea
    {
        return new AcLinea(
            (int) $fila['linea_investigacion'],
            (string) $fila['area_conocimiento']
        );
    }
}
