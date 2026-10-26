<?php

declare(strict_types=1);

require_once __DIR__ . '/IRepositorioParticipaSemillero.php';
require_once __DIR__ . '/../../excepciones/ConflictoExcepcion.php';

/**
 * Persistencia de participa_semillero con PDO y consultas preparadas.
 * Nunca permite modificar los componentes de la PK mediante PUT o PATCH.
 */
class RepositorioParticipaSemilleroMariaDB implements IRepositorioParticipaSemillero
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    /** @return ParticipaSemillero[] */
    public function obtenerTodos(): array
    {
        $sentencia = $this->conexion->prepare('
            SELECT docente, semillero, rol, fecha_inicio, fecha_fin
            FROM participa_semillero
            WHERE activo = TRUE
            ORDER BY docente, semillero
        ');
        $sentencia->execute();

        $participaciones = [];
        foreach ($sentencia->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $participaciones[] = $this->armarParticipacion($fila);
        }
        return $participaciones;
    }

    public function obtenerPorClave(int $docenteId, int $semilleroId): ?ParticipaSemillero
    {
        $sentencia = $this->conexion->prepare('
            SELECT docente, semillero, rol, fecha_inicio, fecha_fin
            FROM participa_semillero
            WHERE docente = :docente
              AND semillero = :semillero
              AND activo = TRUE
        ');
        $this->vincularClave($sentencia, $docenteId, $semilleroId);
        $sentencia->execute();

        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $this->armarParticipacion($fila);
    }

    public function crear(ParticipaSemillero $participacion): int
    {
        $sentencia = $this->conexion->prepare('
            INSERT INTO participa_semillero
                (docente, semillero, rol, fecha_inicio, fecha_fin)
            VALUES
                (:docente, :semillero, :rol, :fecha_inicio, :fecha_fin)
        ');
        foreach ($participacion->toArray() as $campo => $valor) {
            $this->vincularCampo($sentencia, $campo, $valor);
        }
        $this->ejecutarEscritura($sentencia);
        return $sentencia->rowCount();
    }

    /** @param array<string, mixed> $datos */
    public function reemplazar(int $docenteId, int $semilleroId, array $datos): int
    {
        $sentencia = $this->conexion->prepare('
            UPDATE participa_semillero
            SET rol = :rol,
                fecha_inicio = :fecha_inicio,
                fecha_fin = :fecha_fin
            WHERE docente = :docente
              AND semillero = :semillero
              AND activo = TRUE
        ');
        foreach (['rol', 'fecha_inicio', 'fecha_fin'] as $campo) {
            $this->vincularCampo($sentencia, $campo, $datos[$campo]);
        }
        $this->vincularClave($sentencia, $docenteId, $semilleroId);
        $this->ejecutarEscritura($sentencia);
        return $sentencia->rowCount();
    }

    /** @param array<string, mixed> $datos */
    public function actualizar(int $docenteId, int $semilleroId, array $datos): int
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
            'UPDATE participa_semillero SET ' . implode(', ', $asignaciones)
            . ' WHERE docente = :docente'
            . ' AND semillero = :semillero'
            . ' AND activo = TRUE'
        );
        foreach ($recibidos as $campo => $valor) {
            $this->vincularCampo($sentencia, $campo, $valor);
        }
        $this->vincularClave($sentencia, $docenteId, $semilleroId);
        $this->ejecutarEscritura($sentencia);
        return $sentencia->rowCount();
    }

    public function retirar(int $docenteId, int $semilleroId): int
    {
        $sentencia = $this->conexion->prepare('
            UPDATE participa_semillero
            SET activo = FALSE
            WHERE docente = :docente
              AND semillero = :semillero
              AND activo = TRUE
        ');
        $this->vincularClave($sentencia, $docenteId, $semilleroId);
        $sentencia->execute();
        return $sentencia->rowCount();
    }

    private function vincularClave(PDOStatement $sentencia, int $docenteId, int $semilleroId): void
    {
        $sentencia->bindValue(':docente', $docenteId, PDO::PARAM_INT);
        $sentencia->bindValue(':semillero', $semilleroId, PDO::PARAM_INT);
    }

    private function vincularCampo(PDOStatement $sentencia, string $campo, mixed $valor): void
    {
        $tipo = $valor === null ? PDO::PARAM_NULL
            : (in_array($campo, ['docente', 'semillero'], true)
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
                    'Ya existe una participación con esa pareja docente-semillero.',
                    0,
                    $excepcion
                );
            }
            if ($codigo === 1452) {
                throw new ConflictoExcepcion(
                    'El docente o el semillero no existe.',
                    0,
                    $excepcion
                );
            }
            throw $excepcion;
        }
    }

    /** @param array<string, mixed> $fila */
    private function armarParticipacion(array $fila): ParticipaSemillero
    {
        return new ParticipaSemillero(
            (int) $fila['docente'],
            (int) $fila['semillero'],
            (string) $fila['rol'],
            (string) $fila['fecha_inicio'],
            $fila['fecha_fin'] === null ? null : (string) $fila['fecha_fin']
        );
    }
}
