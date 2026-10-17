<?php
declare(strict_types=1);
require_once __DIR__ . '/../servicios/Servicios_TerminoClave/ServicioTerminoClave.php';
require_once __DIR__ . '/../servicios/Servicios_Universidad/ServicioUniversidad.php';
require_once __DIR__ . '/../servicios/Servicios_LineaInvestigacion/ServicioLineaInvestigacion.php';
require_once __DIR__ . '/../excepciones/ConflictoExcepcion.php';

/**
 * Repositorio falso para probar ServicioTerminoClave
 * sin utilizar PDO ni MariaDB.
 */
class RepositorioTerminoClaveFalsoEnMemoria
    implements IRepositorioTerminoClave
{
    private array $registros = [];
    public function obtenerTodos(): array
    {
        $activos = [];
        foreach ($this->registros as $registro) {
            if ($registro['activo']) {
                $activos[] = $registro['modelo'];
            }
        }
        return $activos;
    }
    public function obtenerPorTermino(
        string $termino
    ): ?TerminoClave {
        if (!array_key_exists($termino, $this->registros)) {
            return null;
        }
        if (!$this->registros[$termino]['activo']) {
            return null;
        }
        return $this->registros[$termino]['modelo'];
    }
    public function crear(TerminoClave $terminoClave): int
    {
        $termino = $terminoClave->getTermino();
        /*
         * Una llave retirada sigue ocupada.
         * Por eso también se rechaza si el registro está inactivo.
         */
        if (array_key_exists($termino, $this->registros)) {
            throw new ConflictoExcepcion(
                'Ya existe un término clave con esa llave.'
            );
        }
        $this->registros[$termino] = [
            'modelo' => $terminoClave,
            'activo' => true,
        ];
        return 1;
    }
    public function reemplazar(
        string $termino,
        ?string $terminoIngles
    ): int {
        $terminoClave = $this->obtenerPorTermino($termino);
        if ($terminoClave === null) {
            return 0;
        }
        $terminoClave->setTerminoIngles($terminoIngles);
        return 1;
    }
    public function actualizar(
        string $termino,
        array $datos
    ): int {
        $terminoClave = $this->obtenerPorTermino($termino);
        if ($terminoClave === null) {
            return 0;
        }
        if (array_key_exists('termino_ingles', $datos)) {
            $terminoClave->setTerminoIngles(
                $datos['termino_ingles']
            );
            return 1;
        }
        return 0;
    }
    public function retirar(string $termino): int
    {
        if (!array_key_exists($termino, $this->registros)) {
            return 0;
        }
        if (!$this->registros[$termino]['activo']) {
            return 0;
        }
        $this->registros[$termino]['activo'] = false;
        return 1;
    }
}

/**
 * Repositorio falso para probar ServicioUniversidad
 * sin utilizar PDO ni MariaDB.
 */
class RepositorioUniversidadFalsoEnMemoria
    implements IRepositorioUniversidad
{
    private array $registros = [];
    public function obtenerTodos(): array
    {
        $activos = [];
        foreach ($this->registros as $registro) {
            if ($registro['activo']) {
                $activos[] = $registro['modelo'];
            }
        }
        return $activos;
    }
    public function obtenerPorId(int $id): ?Universidad
    {
        if (!array_key_exists($id, $this->registros)) {
            return null;
        }
        if (!$this->registros[$id]['activo']) {
            return null;
        }
        return $this->registros[$id]['modelo'];
    }
    public function crear(Universidad $universidad): int
    {
        $id = $universidad->getId();
        /*
         * Una llave retirada sigue ocupada.
         */
        if (array_key_exists($id, $this->registros)) {
            throw new ConflictoExcepcion(
                'Ya existe una universidad con esa llave.'
            );
        }
        $this->registros[$id] = [
            'modelo' => $universidad,
            'activo' => true,
        ];
        return 1;
    }
    public function reemplazar(
        int $id,
        array $datos
    ): int {
        $universidad = $this->obtenerPorId($id);
        if ($universidad === null) {
            return 0;
        }
        $universidad->setNombre($datos['nombre']);
        $universidad->setTipo($datos['tipo']);
        $universidad->setCiudad($datos['ciudad']);
        return 1;
    }
    public function actualizar(
        int $id,
        array $datos
    ): int {
        $universidad = $this->obtenerPorId($id);
        if ($universidad === null) {
            return 0;
        }
        if (array_key_exists('nombre', $datos)) {
            $universidad->setNombre($datos['nombre']);
        }
        if (array_key_exists('tipo', $datos)) {
            $universidad->setTipo($datos['tipo']);
        }
        if (array_key_exists('ciudad', $datos)) {
            $universidad->setCiudad($datos['ciudad']);
        }
        return 1;
    }
    public function retirar(int $id): int
    {
        if (!array_key_exists($id, $this->registros)) {
            return 0;
        }
        if (!$this->registros[$id]['activo']) {
            return 0;
        }
        $this->registros[$id]['activo'] = false;
        return 1;
    }
}

/**
 * Repositorio falso para probar ServicioLineaInvestigacion sin MariaDB.
 * Emula AUTO_INCREMENT: cada POST recibe un id nuevo, incluso cuando
 * existen registros retirados logicamente.
 */
class RepositorioLineaInvestigacionFalsoEnMemoria
    implements IRepositorioLineaInvestigacion
{
    private array $registros = [];
    private int $siguienteId = 1;

    public function obtenerTodos(): array
    {
        $activos = [];

        foreach ($this->registros as $registro) {
            if ($registro['activo']) {
                $activos[] = $registro['modelo'];
            }
        }

        return $activos;
    }

    public function obtenerPorId(int $id): ?LineaInvestigacion
    {
        if (!isset($this->registros[$id])) {
            return null;
        }

        if (!$this->registros[$id]['activo']) {
            return null;
        }

        return $this->registros[$id]['modelo'];
    }

    public function crear(LineaInvestigacion $lineaInvestigacion): int
    {
        $id = $this->siguienteId++;

        $modelo = new LineaInvestigacion(
            $id,
            $lineaInvestigacion->getNombre(),
            $lineaInvestigacion->getDescripcion()
        );

        $this->registros[$id] = [
            'modelo' => $modelo,
            'activo' => true,
        ];

        return $id;
    }

    public function reemplazar(int $id, array $datos): int
    {
        $linea = $this->obtenerPorId($id);

        if ($linea === null) {
            return 0;
        }

        $linea->setNombre($datos['nombre']);
        $linea->setDescripcion($datos['descripcion']);

        return 1;
    }

    public function actualizar(int $id, array $datos): int
    {
        $linea = $this->obtenerPorId($id);

        if ($linea === null) {
            return 0;
        }

        $camposActualizados = 0;

        if (array_key_exists('nombre', $datos)) {
            $linea->setNombre($datos['nombre']);
            $camposActualizados++;
        }

        if (array_key_exists('descripcion', $datos)) {
            $linea->setDescripcion($datos['descripcion']);
            $camposActualizados++;
        }

        return $camposActualizados > 0 ? 1 : 0;
    }

    public function retirar(int $id): int
    {
        if (!isset($this->registros[$id])) {
            return 0;
        }

        if (!$this->registros[$id]['activo']) {
            return 0;
        }

        $this->registros[$id]['activo'] = false;

        return 1;
    }
}

/**
 * Contador general de pruebas fallidas.
 */
$fallos = 0;
function afirmar(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}
function probar(string $nombre, callable $prueba): void
{
    global $fallos;
    try {
        $prueba();
        echo "[OK] {$nombre}" . PHP_EOL;
    } catch (Throwable $excepcion) {
        $fallos++;
        echo "[ERROR] {$nombre}: "
            . $excepcion->getMessage()
            . PHP_EOL;
    }
}
/*
 * Aquí conectamos el servicio a la interfaz falsa,
 * no a RepositorioTerminoClaveMariaDB.
 */
$repositorio = new RepositorioTerminoClaveFalsoEnMemoria();
$servicio = new ServicioTerminoClave($repositorio);

// ----------------------------------------------------------------------
// TERMINO CLAVE

// ----------------------------------------------------------------------

probar('crear termino clave', function () use ($servicio): void {
    $creado = $servicio->crear(
        new TerminoClave(
            'inteligencia artificial',
            'artificial intelligence'
        )
    );
    afirmar(
        $creado->getTermino() === 'inteligencia artificial',
        'El término creado no coincide.'
    );
});

probar('listar registros activos', function () use ($servicio): void {
    $registros = $servicio->listar();
    afirmar(
        count($registros) === 1,
        'Se esperaba exactamente un registro activo.'
    );
});

probar('obtener registro activo', function () use ($servicio): void {
    $registro = $servicio->obtenerPorTermino(
        'inteligencia artificial'
    );
    afirmar(
        $registro->getTerminoIngles()
            === 'artificial intelligence',
        'La traducción no coincide.'
    );
});

probar('recurso inexistente', function () use ($servicio): void {
    try {
        $servicio->obtenerPorTermino('no existe');
        throw new RuntimeException(
            'No se lanzó NoEncontradoExcepcion.'
        );
    } catch (NoEncontradoExcepcion) {
        // Resultado esperado.
    }
});

probar('PUT completo', function () use ($servicio): void {
    $filas = $servicio->reemplazar(
        'inteligencia artificial',
        'artificial intelligence updated'
    );
    afirmar(
        $filas === 1,
        'PUT no reportó una fila afectada.'
    );
    $registro = $servicio->obtenerPorTermino(
        'inteligencia artificial'
    );
    afirmar(
        $registro->getTerminoIngles()
            === 'artificial intelligence updated',
        'PUT no reemplazó el valor.'
    );
});

probar('PATCH parcial', function () use ($servicio): void {
    $filas = $servicio->actualizar(
        'inteligencia artificial',
        [
            'termino_ingles' => null,
        ]
    );
    afirmar(
        $filas === 1,
        'PATCH no reportó una fila afectada.'
    );
    $registro = $servicio->obtenerPorTermino(
        'inteligencia artificial'
    );
    afirmar(
        $registro->getTerminoIngles() === null,
        'PATCH no permitió establecer null.'
    );
});

probar('PATCH vacío', function () use ($servicio): void {
    try {
        $servicio->actualizar(
            'inteligencia artificial',
            []
        );
        throw new RuntimeException(
            'PATCH vacío fue aceptado.'
        );
    } catch (InvalidArgumentException) {
        // Resultado esperado.
    }
});

probar('llave duplicada', function () use ($servicio): void {
    try {
        $servicio->crear(
            new TerminoClave(
                'inteligencia artificial',
                'duplicado'
            )
        );
        throw new RuntimeException(
            'La llave duplicada fue aceptada.'
        );
    } catch (ConflictoExcepcion) {
        // Resultado esperado.
    }
});

probar('retirar registro', function () use ($servicio): void {
    $filas = $servicio->retirar(
        'inteligencia artificial'
    );
    afirmar(
        $filas === 1,
        'El retiro no afectó una fila.'
    );
});

probar(
    'registro retirado no se puede consultar',
    function () use ($servicio): void {
        try {
            $servicio->obtenerPorTermino(
                'inteligencia artificial'
            );
            throw new RuntimeException(
                'El registro retirado todavía aparece activo.'
            );
        } catch (NoEncontradoExcepcion) {
            // Resultado esperado.
        }
    }
);

probar(
    'registro retirado no aparece en listado',
    function () use ($servicio): void {
        afirmar(
            $servicio->listar() === [],
            'El registro retirado todavía aparece en el listado.'
        );
    }
);

probar(
    'segundo retiro produce NoEncontradoExcepcion',
    function () use ($servicio): void {
        try {
            $servicio->retirar(
                'inteligencia artificial'
            );
            throw new RuntimeException(
                'El segundo retiro fue aceptado.'
            );
        } catch (NoEncontradoExcepcion) {
            // Resultado esperado.
        }
    }
);

probar(
    'llave retirada sigue ocupada',
    function () use ($servicio): void {
        try {
            $servicio->crear(
                new TerminoClave(
                    'inteligencia artificial',
                    'new value'
                )
            );
            throw new RuntimeException(
                'Se reutilizó una llave retirada.'
            );
        } catch (ConflictoExcepcion) {
            // Resultado esperado.
        }
    }
);

// ----------------------------------------------------------------------
// UNIVERSIDAD

// ----------------------------------------------------------------------
$repositorioUniversidad =
    new RepositorioUniversidadFalsoEnMemoria();
$servicioUniversidad =
    new ServicioUniversidad($repositorioUniversidad);

probar(
    'universidad - crear',
    function () use ($servicioUniversidad): void {
        $creada = $servicioUniversidad->crear(
            new Universidad(
                1,
                'Universidad Inicial',
                'Privada',
                'Medellin'
            )
        );
        afirmar(
            $creada->getId() === 1,
            'El id de la universidad creada no coincide.'
        );
        afirmar(
            $creada->getNombre() === 'Universidad Inicial',
            'El nombre de la universidad creada no coincide.'
        );
    }
);

probar(
    'universidad - listar registros activos',
    function () use ($servicioUniversidad): void {
        $registros = $servicioUniversidad->listar();
        afirmar(
            count($registros) === 1,
            'Se esperaba exactamente una universidad activa.'
        );
    }
);

probar(
    'universidad - obtener registro activo',
    function () use ($servicioUniversidad): void {
        $universidad = $servicioUniversidad->obtenerPorId(1);
        afirmar(
            $universidad->getCiudad() === 'Medellin',
            'La ciudad de la universidad no coincide.'
        );
    }
);

probar(
    'universidad - recurso inexistente',
    function () use ($servicioUniversidad): void {
        try {
            $servicioUniversidad->obtenerPorId(999);
            throw new RuntimeException(
                'No se lanzó NoEncontradoExcepcion.'
            );
        } catch (NoEncontradoExcepcion) {
            // Resultado esperado.
        }
    }
);

probar(
    'universidad - PUT completo',
    function () use ($servicioUniversidad): void {
        $filas = $servicioUniversidad->reemplazar(
            1,
            [
                'nombre' => 'Universidad Actualizada',
                'tipo' => 'Publica',
                'ciudad' => 'Bogota',
            ]
        );
        afirmar(
            $filas === 1,
            'PUT no reportó una fila afectada.'
        );
        $universidad = $servicioUniversidad->obtenerPorId(1);
        afirmar(
            $universidad->getNombre()
                === 'Universidad Actualizada',
            'PUT no reemplazó el nombre.'
        );
        afirmar(
            $universidad->getTipo() === 'Publica',
            'PUT no reemplazó el tipo.'
        );
        afirmar(
            $universidad->getCiudad() === 'Bogota',
            'PUT no reemplazó la ciudad.'
        );
    }
);

probar(
    'universidad - PATCH parcial',
    function () use ($servicioUniversidad): void {
        $filas = $servicioUniversidad->actualizar(
            1,
            [
                'ciudad' => 'Cali',
            ]
        );
        afirmar(
            $filas === 1,
            'PATCH no reportó una fila afectada.'
        );
        $universidad = $servicioUniversidad->obtenerPorId(1);
        afirmar(
            $universidad->getCiudad() === 'Cali',
            'PATCH no modificó la ciudad.'
        );
        afirmar(
            $universidad->getNombre()
                === 'Universidad Actualizada',
            'PATCH modificó un campo que no debía.'
        );
        afirmar(
            $universidad->getTipo() === 'Publica',
            'PATCH modificó el tipo sin recibirlo.'
        );
    }
);

probar(
    'universidad - PATCH vacío',
    function () use ($servicioUniversidad): void {
        try {
            $servicioUniversidad->actualizar(1, []);
            throw new RuntimeException(
                'PATCH vacío fue aceptado.'
            );
        } catch (InvalidArgumentException) {
            // Resultado esperado.
        }
    }
);

probar(
    'universidad - llave duplicada',
    function () use ($servicioUniversidad): void {
        try {
            $servicioUniversidad->crear(
                new Universidad(
                    1,
                    'Universidad Duplicada',
                    'Privada',
                    'Pereira'
                )
            );
            throw new RuntimeException(
                'La llave duplicada fue aceptada.'
            );
        } catch (ConflictoExcepcion) {
            // Resultado esperado.
        }
    }
);

probar(
    'universidad - retirar registro',
    function () use ($servicioUniversidad): void {
        $filas = $servicioUniversidad->retirar(1);
        afirmar(
            $filas === 1,
            'El retiro no afectó una fila.'
        );
    }
);

probar(
    'universidad - registro retirado no se puede consultar',
    function () use ($servicioUniversidad): void {
        try {
            $servicioUniversidad->obtenerPorId(1);
            throw new RuntimeException(
                'La universidad retirada todavía aparece activa.'
            );
        } catch (NoEncontradoExcepcion) {
            // Resultado esperado.
        }
    }
);

probar(
    'universidad - registro retirado no aparece en listado',
    function () use ($servicioUniversidad): void {
        afirmar(
            $servicioUniversidad->listar() === [],
            'La universidad retirada todavía aparece en el listado.'
        );
    }
);

probar(
    'universidad - segundo retiro produce NoEncontradoExcepcion',
    function () use ($servicioUniversidad): void {
        try {
            $servicioUniversidad->retirar(1);
            throw new RuntimeException(
                'El segundo retiro fue aceptado.'
            );
        } catch (NoEncontradoExcepcion) {
            // Resultado esperado.
        }
    }
);

probar(
    'universidad - llave retirada sigue ocupada',
    function () use ($servicioUniversidad): void {
        try {
            $servicioUniversidad->crear(
                new Universidad(
                    1,
                    'Universidad Nueva',
                    'Privada',
                    'Medellin'
                )
            );
            throw new RuntimeException(
                'Se reutilizó una llave retirada.'
            );
        } catch (ConflictoExcepcion) {
            // Resultado esperado.
        }
    }
);

// ----------------------------------------------------------------------
// LINEA DE INVESTIGACION

// ----------------------------------------------------------------------

$repositorioLineaInvestigacion =
    new RepositorioLineaInvestigacionFalsoEnMemoria();

$servicioLineaInvestigacion =
    new ServicioLineaInvestigacion($repositorioLineaInvestigacion);

// Este identificador lo genera el repositorio falso al crear el registro.
$idLineaInvestigacion = null;

probar(
    'linea_investigacion - crear con id generado',
    function () use ($servicioLineaInvestigacion, &$idLineaInvestigacion): void {
        $creada = $servicioLineaInvestigacion->crear(
            new LineaInvestigacion(
                null,
                'Ingenieria de software',
                'Linea creada en memoria'
            )
        );

        $idLineaInvestigacion = $creada->getId();

        afirmar(
            is_int($idLineaInvestigacion) && $idLineaInvestigacion > 0,
            'Crear no devolvio el id autogenerado.'
        );
        afirmar(
            $creada->getNombre() === 'Ingenieria de software',
            'El nombre del registro creado no coincide.'
        );
        afirmar(
            $creada->getDescripcion() === 'Linea creada en memoria',
            'La descripcion del registro creado no coincide.'
        );
    }
);

probar(
    'linea_investigacion - listar registros activos',
    function () use ($servicioLineaInvestigacion): void {
        $lineas = $servicioLineaInvestigacion->listar();

        afirmar(
            count($lineas) === 1,
            'Se esperaba una linea activa.'
        );
    }
);

probar(
    'linea_investigacion - obtener registro activo',
    function () use ($servicioLineaInvestigacion, &$idLineaInvestigacion): void {
        $linea = $servicioLineaInvestigacion->obtenerPorId(
            $idLineaInvestigacion
        );

        afirmar(
            $linea->getDescripcion() === 'Linea creada en memoria',
            'La descripcion consultada no coincide.'
        );
    }
);

probar(
    'linea_investigacion - recurso inexistente',
    function () use ($servicioLineaInvestigacion): void {
        try {
            $servicioLineaInvestigacion->obtenerPorId(999999);
            throw new RuntimeException('No se lanzo NoEncontradoExcepcion.');
        } catch (NoEncontradoExcepcion) {
            // Resultado esperado.
        }
    }
);

probar(
    'linea_investigacion - PUT completo',
    function () use ($servicioLineaInvestigacion, &$idLineaInvestigacion): void {
        $filas = $servicioLineaInvestigacion->reemplazar(
            $idLineaInvestigacion,
            [
                'nombre' => 'Desarrollo de software',
                'descripcion' => 'Descripcion mediante PUT',
            ]
        );

        afirmar($filas === 1, 'PUT no afecto una fila.');

        $linea = $servicioLineaInvestigacion->obtenerPorId(
            $idLineaInvestigacion
        );

        afirmar(
            $linea->getNombre() === 'Desarrollo de software',
            'PUT no reemplazo el nombre.'
        );
        afirmar(
            $linea->getDescripcion() === 'Descripcion mediante PUT',
            'PUT no reemplazo la descripcion.'
        );
    }
);

probar(
    'linea_investigacion - PATCH parcial',
    function () use ($servicioLineaInvestigacion, &$idLineaInvestigacion): void {
        $filas = $servicioLineaInvestigacion->actualizar(
            $idLineaInvestigacion,
            ['descripcion' => 'Descripcion mediante PATCH']
        );

        afirmar($filas === 1, 'PATCH no afecto una fila.');

        $linea = $servicioLineaInvestigacion->obtenerPorId(
            $idLineaInvestigacion
        );

        afirmar(
            $linea->getDescripcion() === 'Descripcion mediante PATCH',
            'PATCH no actualizo la descripcion.'
        );
        afirmar(
            $linea->getNombre() === 'Desarrollo de software',
            'PATCH modifico un campo no enviado.'
        );
    }
);

probar(
    'linea_investigacion - PATCH vacio',
    function () use ($servicioLineaInvestigacion, &$idLineaInvestigacion): void {
        try {
            $servicioLineaInvestigacion->actualizar(
                $idLineaInvestigacion,
                []
            );
            throw new RuntimeException('PATCH vacio fue aceptado.');
        } catch (InvalidArgumentException) {
            // Resultado esperado.
        }
    }
);

probar(
    'linea_investigacion - PUT de recurso inexistente',
    function () use ($servicioLineaInvestigacion): void {
        try {
            $servicioLineaInvestigacion->reemplazar(
                999999,
                [
                    'nombre' => 'No existe',
                    'descripcion' => 'Prueba de inexistencia',
                ]
            );
            throw new RuntimeException('PUT acepto un recurso inexistente.');
        } catch (NoEncontradoExcepcion) {
            // Resultado esperado.
        }
    }
);

probar(
    'linea_investigacion - PATCH de recurso inexistente',
    function () use ($servicioLineaInvestigacion): void {
        try {
            $servicioLineaInvestigacion->actualizar(
                999999,
                ['descripcion' => 'No existe']
            );
            throw new RuntimeException('PATCH acepto un recurso inexistente.');
        } catch (NoEncontradoExcepcion) {
            // Resultado esperado.
        }
    }
);

probar(
    'linea_investigacion - retirar registro',
    function () use ($servicioLineaInvestigacion, &$idLineaInvestigacion): void {
        $filas = $servicioLineaInvestigacion->retirar(
            $idLineaInvestigacion
        );

        afirmar($filas === 1, 'El retiro no afecto una fila.');
    }
);

probar(
    'linea_investigacion - registro retirado no se consulta',
    function () use ($servicioLineaInvestigacion, &$idLineaInvestigacion): void {
        try {
            $servicioLineaInvestigacion->obtenerPorId(
                $idLineaInvestigacion
            );
            throw new RuntimeException('El registro retirado sigue activo.');
        } catch (NoEncontradoExcepcion) {
            // Resultado esperado.
        }
    }
);

probar(
    'linea_investigacion - registro retirado no aparece en listado',
    function () use ($servicioLineaInvestigacion): void {
        afirmar(
            $servicioLineaInvestigacion->listar() === [],
            'El registro retirado todavia aparece en el listado.'
        );
    }
);

probar(
    'linea_investigacion - segundo retiro produce NoEncontradoExcepcion',
    function () use ($servicioLineaInvestigacion, &$idLineaInvestigacion): void {
        try {
            $servicioLineaInvestigacion->retirar(
                $idLineaInvestigacion
            );
            throw new RuntimeException('El segundo retiro fue aceptado.');
        } catch (NoEncontradoExcepcion) {
            // Resultado esperado.
        }
    }
);

probar(
    'linea_investigacion - id retirado no se reutiliza',
    function () use ($servicioLineaInvestigacion, &$idLineaInvestigacion): void {
        $nueva = $servicioLineaInvestigacion->crear(
            new LineaInvestigacion(
                null,
                'Inteligencia artificial',
                'Segunda linea creada en memoria'
            )
        );

        afirmar(
            $nueva->getId() !== $idLineaInvestigacion,
            'AUTO_INCREMENT reutilizo un id retirado.'
        );
        afirmar(
            $nueva->getId() > $idLineaInvestigacion,
            'AUTO_INCREMENT no avanzo.'
        );
        afirmar(
            count($servicioLineaInvestigacion->listar()) === 1,
            'La lista debe tener la nueva linea y no la retirada.'
        );
    }
);

// ----------------------------------------------------------------------
// RESULTADO FINAL

// ----------------------------------------------------------------------
echo PHP_EOL;
if ($fallos === 0) {
    echo '[OK] Todas las pruebas de termino_clave, universidad y linea_investigacion pasaron.'
        . PHP_EOL;
    exit(0);
}
echo "[ERROR] Total de pruebas fallidas: {$fallos}"
    . PHP_EOL;
exit(1);
