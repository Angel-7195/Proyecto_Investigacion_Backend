<?php

declare(strict_types=1);

require_once __DIR__ . '/IRepositorioGrupoLinea.php';
require_once __DIR__ . '/../../excepciones/ConflictoExcepcion.php';

/** Consultas SQL preparadas para la tabla grupo_linea. */
class RepositorioGrupoLineaMariaDB implements IRepositorioGrupoLinea
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    /** @return GrupoLinea[] */
    public function obtenerTodos(): array
    {
        $sentencia = $this->conexion->prepare('
            SELECT grupo_investigacion, linea_investigacion
            FROM grupo_linea
            WHERE activo = TRUE
            ORDER BY grupo_investigacion, linea_investigacion
        ');
        $sentencia->execute();

        $resultado = [];
        foreach ($sentencia->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $resultado[] = $this->construirModelo($fila);
        }
        return $resultado;
    }

    public function obtenerPorClave(int $grupoInvestigacion, int $lineaInvestigacion): ?GrupoLinea
    {
        $sentencia = $this->conexion->prepare('
            SELECT grupo_investigacion, linea_investigacion
            FROM grupo_linea
            WHERE grupo_investigacion = :grupo_investigacion
              AND linea_investigacion = :linea_investigacion
              AND activo = TRUE
        ');
        $this->vincularClaves($sentencia, $grupoInvestigacion, $lineaInvestigacion);
        $sentencia->execute();

        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $this->construirModelo($fila);
    }

    public function crear(GrupoLinea $asociacion): int
    {
        $sentencia = $this->conexion->prepare('
            INSERT INTO grupo_linea (grupo_investigacion, linea_investigacion)
            VALUES (:grupo_investigacion, :linea_investigacion)
        ');
        $this->vincularClaves(
            $sentencia,
            $asociacion->getGrupoInvestigacion(),
            $asociacion->getLineaInvestigacion()
        );

        try {
            $sentencia->execute();
        } catch (PDOException $excepcion) {
            $codigo = (int) ($excepcion->errorInfo[1] ?? 0);
            if ($codigo === 1062) {
                // Una asociación retirada sigue ocupando su clave compuesta.
                throw new ConflictoExcepcion(
                    'Ya existe esa combinación de grupo de investigación y línea de investigación.',
                    0,
                    $excepcion
                );
            }
            if ($codigo === 1452) {
                throw new ConflictoExcepcion(
                    'El grupo de investigación o la línea de investigación no existe.',
                    0,
                    $excepcion
                );
            }
            throw $excepcion;
        }

        return $sentencia->rowCount();
    }

    public function retirar(int $grupoInvestigacion, int $lineaInvestigacion): int
    {
        $sentencia = $this->conexion->prepare('
            UPDATE grupo_linea
            SET activo = FALSE
            WHERE grupo_investigacion = :grupo_investigacion
              AND linea_investigacion = :linea_investigacion
              AND activo = TRUE
        ');
        $this->vincularClaves($sentencia, $grupoInvestigacion, $lineaInvestigacion);
        $sentencia->execute();
        return $sentencia->rowCount();
    }

    private function vincularClaves(PDOStatement $sentencia, int $grupoInvestigacion, int $lineaInvestigacion): void
    {
        $sentencia->bindValue(':grupo_investigacion', $grupoInvestigacion, PDO::PARAM_INT);
        $sentencia->bindValue(':linea_investigacion', $lineaInvestigacion, PDO::PARAM_INT);
    }

    /** @param array<string,mixed> $fila */
    private function construirModelo(array $fila): GrupoLinea
    {
        return new GrupoLinea(
            (int) $fila['grupo_investigacion'],
            (int) $fila['linea_investigacion']
        );
    }
}
