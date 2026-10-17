<?php

declare(strict_types=1);

require_once __DIR__ . '/IRepositorioTerminoClave.php';
require_once __DIR__ . '/../../excepciones/ConflictoExcepcion.php';

class RepositorioTerminoClaveMariaDB implements IRepositorioTerminoClave
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return TerminoClave[]
     */
    public function obtenerTodos(): array
    {
        $sql = '
            SELECT termino, termino_ingles
            FROM termino_clave
            WHERE activo = TRUE
            ORDER BY termino
        ';

        $sentencia = $this->pdo->prepare($sql);
        $sentencia->execute();

        $filas = $sentencia->fetchAll(PDO::FETCH_ASSOC);

        $terminos = [];

        foreach ($filas as $fila) {
            $terminos[] = new TerminoClave(
                $fila['termino'],
                $fila['termino_ingles']
            );
        }

        return $terminos;
    }

    public function obtenerPorTermino(string $termino): ?TerminoClave
    {
        $sql = '
            SELECT termino, termino_ingles
            FROM termino_clave
            WHERE termino = :termino
            AND activo = TRUE
        ';

        $sentencia = $this->pdo->prepare($sql);

        $sentencia->execute([
            'termino' => $termino,
        ]);

        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);

        if ($fila === false) {
            return null;
        }

        return new TerminoClave(
            $fila['termino'],
            $fila['termino_ingles']
        );
    }

    public function crear(TerminoClave $terminoClave): int
    {
        $sql = '
            INSERT INTO termino_clave (
                termino,
                termino_ingles
            )
            VALUES (
                :termino,
                :termino_ingles
            )
        ';

        $sentencia = $this->pdo->prepare($sql);

        try {
            $sentencia->execute([
                'termino' => $terminoClave->getTermino(),
                'termino_ingles' => $terminoClave->getTerminoIngles(),
            ]);
        } catch (PDOException $excepcion) {
            $codigoMariaDB = $excepcion->errorInfo[1] ?? null;

            if ($codigoMariaDB === 1062) {
                throw new ConflictoExcepcion(
                    'Ya existe un término clave con esa llave.'
                );
            }

            throw $excepcion;
        }

        return $sentencia->rowCount();
    }

    public function reemplazar(
        string $termino,
        ?string $terminoIngles
    ): int {
        $sql = '
            UPDATE termino_clave
            SET termino_ingles = :termino_ingles
            WHERE termino = :termino
            AND activo = TRUE
        ';

        $sentencia = $this->pdo->prepare($sql);

        $sentencia->execute([
            'termino_ingles' => $terminoIngles,
            'termino' => $termino,
        ]);

        return $sentencia->rowCount();
    }

    public function actualizar(
        string $termino,
        array $datos
    ): int {
        $campos = [];
        $parametros = [
            'termino' => $termino,
        ];

        if (array_key_exists('termino_ingles', $datos)) {
            $campos[] = 'termino_ingles = :termino_ingles';
            $parametros['termino_ingles'] = $datos['termino_ingles'];
        }

        if ($campos === []) {
            return 0;
        }

        $sql = '
            UPDATE termino_clave
            SET ' . implode(', ', $campos) . '
            WHERE termino = :termino
            AND activo = TRUE
        ';

        $sentencia = $this->pdo->prepare($sql);
        $sentencia->execute($parametros);

        return $sentencia->rowCount();
    }

    public function retirar(string $termino): int
    {
        $sql = '
            UPDATE termino_clave
            SET activo = FALSE
            WHERE termino = :termino
            AND activo = TRUE
        ';

        $sentencia = $this->pdo->prepare($sql);

        $sentencia->execute([
            'termino' => $termino,
        ]);

        return $sentencia->rowCount();
    }
}