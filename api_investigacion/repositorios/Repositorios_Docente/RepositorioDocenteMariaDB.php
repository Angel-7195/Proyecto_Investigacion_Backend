<?php

declare(strict_types=1);

require_once __DIR__ . '/IRepositorioDocente.php';
require_once __DIR__ . '/../../excepciones/ConflictoExcepcion.php';

/**
 * Acceso SQL a docente con PDO y sentencias preparadas.
 * Las consultas públicas filtran activo = TRUE y DELETE es lógico.
 */
class RepositorioDocenteMariaDB implements IRepositorioDocente
{
    private PDO $conexion;

    private const CAMPOS_EDITABLES = [
        'nombres',
        'apellidos',
        'genero',
        'cargo',
        'fecha_nacimiento',
        'correo',
        'telefono',
        'url_cvlac',
        'fecha_actualizacion',
        'escalafon',
        'perfil',
        'cat_minciencia',
        'conv_minciencia',
        'nacionalidaad',
        'linea_investigacion_principal',
    ];

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    /** @return Docente[] */
    public function obtenerTodos(): array
    {
        $sentencia = $this->conexion->prepare(
            'SELECT cedula, nombres, apellidos, genero, cargo, fecha_nacimiento, correo, telefono, url_cvlac, fecha_actualizacion, escalafon, perfil, cat_minciencia, conv_minciencia, nacionalidaad, linea_investigacion_principal FROM docente WHERE activo = TRUE ORDER BY cedula'
        );
        $sentencia->execute();
        $docentes = [];
        foreach ($sentencia->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $docentes[] = $this->armarDocente($fila);
        }
        return $docentes;
    }

    public function obtenerPorCedula(int $cedula): ?Docente
    {
        $sentencia = $this->conexion->prepare(
            'SELECT cedula, nombres, apellidos, genero, cargo, fecha_nacimiento, correo, telefono, url_cvlac, fecha_actualizacion, escalafon, perfil, cat_minciencia, conv_minciencia, nacionalidaad, linea_investigacion_principal FROM docente WHERE cedula = :cedula AND activo = TRUE'
        );
        $sentencia->bindValue(':cedula', $cedula, PDO::PARAM_INT);
        $sentencia->execute();
        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $this->armarDocente($fila);
    }

    public function crear(Docente $docente): int
    {
        // cedula no es AUTO_INCREMENT; activo usa DEFAULT TRUE.
        $sentencia = $this->conexion->prepare(
            'INSERT INTO docente (cedula, nombres, apellidos, genero, cargo, fecha_nacimiento, correo, telefono, url_cvlac, fecha_actualizacion, escalafon, perfil, cat_minciencia, conv_minciencia, nacionalidaad, linea_investigacion_principal) VALUES (:cedula, :nombres, :apellidos, :genero, :cargo, :fecha_nacimiento, :correo, :telefono, :url_cvlac, :fecha_actualizacion, :escalafon, :perfil, :cat_minciencia, :conv_minciencia, :nacionalidaad, :linea_investigacion_principal)'
        );
        foreach ($docente->toArray() as $campo => $valor) {
            $this->vincularCampo($sentencia, $campo, $valor);
        }
        $this->ejecutarEscritura($sentencia);
        return $sentencia->rowCount();
    }

    public function reemplazar(int $cedula, array $datos): int
    {
        $sentencia = $this->conexion->prepare(
            'UPDATE docente SET nombres = :nombres, apellidos = :apellidos, genero = :genero, cargo = :cargo, fecha_nacimiento = :fecha_nacimiento, correo = :correo, telefono = :telefono, url_cvlac = :url_cvlac, fecha_actualizacion = :fecha_actualizacion, escalafon = :escalafon, perfil = :perfil, cat_minciencia = :cat_minciencia, conv_minciencia = :conv_minciencia, nacionalidaad = :nacionalidaad, linea_investigacion_principal = :linea_investigacion_principal WHERE cedula = :cedula AND activo = TRUE'
        );
        foreach (self::CAMPOS_EDITABLES as $campo) {
            $this->vincularCampo($sentencia, $campo, $datos[$campo]);
        }
        $sentencia->bindValue(':cedula', $cedula, PDO::PARAM_INT);
        $this->ejecutarEscritura($sentencia);
        return $sentencia->rowCount();
    }

    public function actualizar(int $cedula, array $datos): int
    {
        $asignaciones = [];
        $recibidos = [];
        // Los nombres SQL salen solo de una lista blanca, nunca del JSON directamente.
        foreach (self::CAMPOS_EDITABLES as $campo) {
            if (array_key_exists($campo, $datos)) {
                $asignaciones[] = $campo . ' = :' . $campo;
                $recibidos[$campo] = $datos[$campo];
            }
        }
        if ($asignaciones === []) {
            throw new InvalidArgumentException('No hay campos editables para actualizar.');
        }
        $sentencia = $this->conexion->prepare(
            'UPDATE docente SET ' . implode(', ', $asignaciones)
            . ' WHERE cedula = :cedula AND activo = TRUE'
        );
        foreach ($recibidos as $campo => $valor) {
            $this->vincularCampo($sentencia, $campo, $valor);
        }
        $sentencia->bindValue(':cedula', $cedula, PDO::PARAM_INT);
        $this->ejecutarEscritura($sentencia);
        return $sentencia->rowCount();
    }

    public function retirar(int $cedula): int
    {
        $sentencia = $this->conexion->prepare(
            'UPDATE docente SET activo = FALSE WHERE cedula = :cedula AND activo = TRUE'
        );
        $sentencia->bindValue(':cedula', $cedula, PDO::PARAM_INT);
        $sentencia->execute();
        return $sentencia->rowCount();
    }

    private function vincularCampo(PDOStatement $sentencia, string $campo, mixed $valor): void
    {
        if ($valor === null) {
            $tipo = PDO::PARAM_NULL;
        } elseif (in_array($campo, ['cedula', 'linea_investigacion_principal'], true)) {
            $tipo = PDO::PARAM_INT;
        } else {
            $tipo = PDO::PARAM_STR;
        }
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
                    'Ya existe un docente con esa cédula.', 0, $excepcion
                );
            }
            if ($codigo === 1452) {
                throw new ConflictoExcepcion(
                    'La línea de investigación indicada no existe.', 0, $excepcion
                );
            }
            throw $excepcion;
        }
    }

    private function armarDocente(array $fila): Docente
    {
        return new Docente(
            (int) $fila['cedula'],
            (string) $fila['nombres'],
            (string) $fila['apellidos'],
            (string) $fila['genero'],
            (string) $fila['cargo'],
            (string) $fila['fecha_nacimiento'],
            (string) $fila['correo'],
            (string) $fila['telefono'],
            (string) $fila['url_cvlac'],
            (string) $fila['fecha_actualizacion'],
            (string) $fila['escalafon'],
            (string) $fila['perfil'],
            $fila['cat_minciencia'] === null ? null : (string) $fila['cat_minciencia'],
            (string) $fila['conv_minciencia'],
            (string) $fila['nacionalidaad'],
            $fila['linea_investigacion_principal'] === null ? null : (int) $fila['linea_investigacion_principal'],
        );
    }
}
