<?php

declare(strict_types=1);

require_once __DIR__ . '/IRepositorioParticipaGrupo.php';
require_once __DIR__ . '/../../excepciones/ConflictoExcepcion.php';

/**
 * Persistencia de participa_grupo con PDO y consultas preparadas.
 * Nunca permite modificar los componentes de la PK mediante PUT o PATCH.
 */
class RepositorioParticipaGrupoMariaDB implements IRepositorioParticipaGrupo
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    /** @return ParticipaGrupo[] */
    public function obtenerTodos(): array
    {
        $sentencia = $this->conexion->prepare('
            SELECT docente_cedula, grupo_investigacion_id, rol, fecha_inicio, fecha_fin
            FROM participa_grupo
            WHERE activo = TRUE
            ORDER BY docente_cedula, grupo_investigacion_id
        ');
        $sentencia->execute();

        $participaciones = [];
        foreach ($sentencia->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $participaciones[] = $this->armarParticipacion($fila);
        }
        return $participaciones;
    }

    public function obtenerPorClave(int $docenteCedula, int $grupoId): ?ParticipaGrupo
    {
        $sentencia = $this->conexion->prepare('
            SELECT docente_cedula, grupo_investigacion_id, rol, fecha_inicio, fecha_fin
            FROM participa_grupo
            WHERE docente_cedula = :docente_cedula
              AND grupo_investigacion_id = :grupo_investigacion_id
              AND activo = TRUE
        ');
        $this->vincularClave($sentencia, $docenteCedula, $grupoId);
        $sentencia->execute();

        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $this->armarParticipacion($fila);
    }

    public function crear(ParticipaGrupo $participacion): int
    {
        $sentencia = $this->conexion->prepare('
            INSERT INTO participa_grupo
                (docente_cedula, grupo_investigacion_id, rol, fecha_inicio, fecha_fin)
            VALUES
                (:docente_cedula, :grupo_investigacion_id, :rol, :fecha_inicio, :fecha_fin)
        ');
        foreach ($participacion->toArray() as $campo => $valor) {
            $this->vincularCampo($sentencia, $campo, $valor);
        }
        $this->ejecutarEscritura($sentencia);
        return $sentencia->rowCount();
    }

    /** @param array<string, mixed> $datos */
    public function reemplazar(int $docenteCedula, int $grupoId, array $datos): int
    {
        $sentencia = $this->conexion->prepare('
            UPDATE participa_grupo
            SET rol = :rol,
                fecha_inicio = :fecha_inicio,
                fecha_fin = :fecha_fin
            WHERE docente_cedula = :docente_cedula
              AND grupo_investigacion_id = :grupo_investigacion_id
              AND activo = TRUE
        ');
        foreach (['rol', 'fecha_inicio', 'fecha_fin'] as $campo) {
            $this->vincularCampo($sentencia, $campo, $datos[$campo]);
        }
        $this->vincularClave($sentencia, $docenteCedula, $grupoId);
        $this->ejecutarEscritura($sentencia);
        return $sentencia->rowCount();
    }

    /** @param array<string, mixed> $datos */
    public function actualizar(int $docenteCedula, int $grupoId, array $datos): int
    {
        $asignaciones = [];
        $recibidos = [];
        // Lista blanca: los nombres SQL no provienen de la solicitud.
        foreach (['rol', 'fecha_inicio', 'fecha_fin'] as $campo) {
            if (array_key_exists($campo, $datos)) {
                $asignaciones[] = $campo . ' = :' . $campo;
                $recibidos[$campo] = $datos[$campo];
            }
        }
        if ($asignaciones === []) {
            throw new InvalidArgumentException('No hay campos editables para actualizar.');
        }

        $sentencia = $this->conexion->prepare(
            'UPDATE participa_grupo SET ' . implode(', ', $asignaciones)
            . ' WHERE docente_cedula = :docente_cedula'
            . ' AND grupo_investigacion_id = :grupo_investigacion_id'
            . ' AND activo = TRUE'
        );
        foreach ($recibidos as $campo => $valor) {
            $this->vincularCampo($sentencia, $campo, $valor);
        }
        $this->vincularClave($sentencia, $docenteCedula, $grupoId);
        $this->ejecutarEscritura($sentencia);
        return $sentencia->rowCount();
    }

    public function retirar(int $docenteCedula, int $grupoId): int
    {
        $sentencia = $this->conexion->prepare('
            UPDATE participa_grupo
            SET activo = FALSE
            WHERE docente_cedula = :docente_cedula
              AND grupo_investigacion_id = :grupo_investigacion_id
              AND activo = TRUE
        ');
        $this->vincularClave($sentencia, $docenteCedula, $grupoId);
        $sentencia->execute();
        return $sentencia->rowCount();
    }

    private function vincularClave(PDOStatement $sentencia, int $docenteCedula, int $grupoId): void
    {
        $sentencia->bindValue(':docente_cedula', $docenteCedula, PDO::PARAM_INT);
        $sentencia->bindValue(':grupo_investigacion_id', $grupoId, PDO::PARAM_INT);
    }

    private function vincularCampo(PDOStatement $sentencia, string $campo, mixed $valor): void
    {
        $tipo = $valor === null ? PDO::PARAM_NULL
            : (in_array($campo, ['docente_cedula', 'grupo_investigacion_id'], true)
                ? PDO::PARAM_INT : PDO::PARAM_STR);
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
                    'Ya existe una participación con esa pareja docente-grupo.',
                    0,
                    $excepcion
                );
            }
            if ($codigo === 1452) {
                throw new ConflictoExcepcion(
                    'El docente o el grupo de investigación no existe.',
                    0,
                    $excepcion
                );
            }
            throw $excepcion;
        }
    }

    /** @param array<string, mixed> $fila */
    private function armarParticipacion(array $fila): ParticipaGrupo
    {
        return new ParticipaGrupo(
            (int) $fila['docente_cedula'],
            (int) $fila['grupo_investigacion_id'],
            (string) $fila['rol'],
            (string) $fila['fecha_inicio'],
            $fila['fecha_fin'] === null ? null : (string) $fila['fecha_fin']
        );
    }
}
