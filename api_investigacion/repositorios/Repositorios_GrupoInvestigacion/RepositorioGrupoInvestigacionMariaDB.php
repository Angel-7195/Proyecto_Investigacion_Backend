<?php

declare(strict_types=1);

require_once __DIR__ . '/IRepositorioGrupoInvestigacion.php';
require_once __DIR__ . '/../../excepciones/ConflictoExcepcion.php';

/**
 * Acceso a grupo_investigacion mediante PDO y sentencias preparadas.
 * La columna universidsad se conserva con la grafía del esquema SQL.
 */
class RepositorioGrupoInvestigacionMariaDB implements IRepositorioGrupoInvestigacion
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    /** @return GrupoInvestigacion[] */
    public function obtenerTodos(): array
    {
        $sql = '
            SELECT
                id, nombre, url_gruplac, categoria, convocatoria,
                fecha_fundacion, universidsad, interno, ambito
            FROM grupo_investigacion
            WHERE activo = TRUE
            ORDER BY id
        ';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();

        $grupos = [];

        foreach ($sentencia->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $grupos[] = $this->armarGrupoInvestigacion($fila);
        }

        return $grupos;
    }

    public function obtenerPorId(int $id): ?GrupoInvestigacion
    {
        $sql = '
            SELECT
                id, nombre, url_gruplac, categoria, convocatoria,
                fecha_fundacion, universidsad, interno, ambito
            FROM grupo_investigacion
            WHERE id = :id
              AND activo = TRUE
        ';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        $sentencia->execute();

        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);

        return $fila === false ? null : $this->armarGrupoInvestigacion($fila);
    }

    public function crear(GrupoInvestigacion $grupoInvestigacion): int
    {
        // id se proporciona explícitamente; no tiene AUTO_INCREMENT.
        // activo toma el valor TRUE definido por defecto en MariaDB.
        $sql = '
            INSERT INTO grupo_investigacion (
                id, nombre, url_gruplac, categoria, convocatoria,
                fecha_fundacion, universidsad, interno, ambito
            ) VALUES (
                :id, :nombre, :url_gruplac, :categoria, :convocatoria,
                :fecha_fundacion, :universidsad, :interno, :ambito
            )
        ';

        $sentencia = $this->conexion->prepare($sql);
        $datos = $grupoInvestigacion->toArray();

        foreach ($datos as $campo => $valor) {
            $this->vincularCampo($sentencia, $campo, $valor);
        }

        $this->ejecutarEscritura($sentencia);

        return $sentencia->rowCount();
    }

    public function reemplazar(int $id, array $datos): int
    {
        // El controlador/servicio debe exigir todos los campos editables.
        $sql = '
            UPDATE grupo_investigacion
            SET nombre = :nombre,
                url_gruplac = :url_gruplac,
                categoria = :categoria,
                convocatoria = :convocatoria,
                fecha_fundacion = :fecha_fundacion,
                universidsad = :universidsad,
                interno = :interno,
                ambito = :ambito
            WHERE id = :id
              AND activo = TRUE
        ';

        $sentencia = $this->conexion->prepare($sql);

        foreach ($this->camposEditables() as $campo) {
            $this->vincularCampo($sentencia, $campo, $datos[$campo]);
        }

        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        $this->ejecutarEscritura($sentencia);

        return $sentencia->rowCount();
    }

    public function actualizar(int $id, array $datos): int
    {
        $asignaciones = [];
        $camposRecibidos = [];

        // Los nombres de columnas proceden de una lista fija, nunca del usuario.
        foreach ($this->camposEditables() as $campo) {
            if (array_key_exists($campo, $datos)) {
                $asignaciones[] = $campo . ' = :' . $campo;
                $camposRecibidos[$campo] = $datos[$campo];
            }
        }

        if ($asignaciones === []) {
            throw new InvalidArgumentException(
                'No se envió ningún campo editable para actualizar.'
            );
        }

        $sql = '
            UPDATE grupo_investigacion
            SET ' . implode(', ', $asignaciones) . '
            WHERE id = :id
              AND activo = TRUE
        ';

        $sentencia = $this->conexion->prepare($sql);

        foreach ($camposRecibidos as $campo => $valor) {
            $this->vincularCampo($sentencia, $campo, $valor);
        }

        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        $this->ejecutarEscritura($sentencia);

        return $sentencia->rowCount();
    }

    public function retirar(int $id): int
    {
        // El borrado lógico no borra físicamente las relaciones existentes.
        // La política para grupos con hijos activos se definirá en el servicio.
        $sql = '
            UPDATE grupo_investigacion
            SET activo = FALSE
            WHERE id = :id
              AND activo = TRUE
        ';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        $sentencia->execute();

        return $sentencia->rowCount();
    }

    /** @return string[] */
    private function camposEditables(): array
    {
        return [
            'nombre',
            'url_gruplac',
            'categoria',
            'convocatoria',
            'fecha_fundacion',
            'universidsad',
            'interno',
            'ambito',
        ];
    }

    /**
     * Vincula los campos opcionales como NULL, los numéricos como INT
     * y los restantes como STR.
     */
    private function vincularCampo(
        PDOStatement $sentencia,
        string $campo,
        mixed $valor
    ): void {
        if ($valor === null) {
            $tipo = PDO::PARAM_NULL;
        } elseif (in_array($campo, ['id', 'universidsad', 'interno'], true)) {
            $tipo = PDO::PARAM_INT;
        } else {
            $tipo = PDO::PARAM_STR;
        }

        $sentencia->bindValue(':' . $campo, $valor, $tipo);
    }

    /**
     * Convierte solo violaciones conocidas de integridad en un conflicto.
     * No expone el detalle del SQL a los clientes de la API.
     */
    private function ejecutarEscritura(PDOStatement $sentencia): void
    {
        try {
            $sentencia->execute();
        } catch (PDOException $excepcion) {
            $codigoMariaDB = (int) ($excepcion->errorInfo[1] ?? 0);

            if ($codigoMariaDB === 1062) {
                throw new ConflictoExcepcion(
                    'Ya existe un grupo de investigación con ese id.',
                    0,
                    $excepcion
                );
            }

            if ($codigoMariaDB === 1452) {
                throw new ConflictoExcepcion(
                    'La universidad indicada no existe en la base de datos.',
                    0,
                    $excepcion
                );
            }

            throw $excepcion;
        }
    }

    private function armarGrupoInvestigacion(array $fila): GrupoInvestigacion
    {
        return new GrupoInvestigacion(
            (int) $fila['id'],
            (string) $fila['nombre'],
            $fila['url_gruplac'] === null ? null : (string) $fila['url_gruplac'],
            $fila['categoria'] === null ? null : (string) $fila['categoria'],
            $fila['convocatoria'] === null ? null : (string) $fila['convocatoria'],
            (string) $fila['fecha_fundacion'],
            $fila['universidsad'] === null ? null : (int) $fila['universidsad'],
            (int) $fila['interno'],
            (string) $fila['ambito']
        );
    }
}
