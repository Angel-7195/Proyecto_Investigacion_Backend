<?php

declare(strict_types=1);

// ================================================================
// AREA DE CONOCIMIENTO
// ================================================================

require_once __DIR__ . '/../modelos/AreaConocimiento.php';
require_once __DIR__ . '/../repositorios/Repositorios_AreaConocimiento/IRepositorioAreaConocimiento.php';
require_once __DIR__ . '/../repositorios/Repositorios_AreaConocimiento/RepositorioAreaConocimientoMariaDB.php';
require_once __DIR__ . '/Servicios_AreaConocimiento/IServicioAreaConocimiento.php';
require_once __DIR__ . '/Servicios_AreaConocimiento/ServicioAreaConocimiento.php';
require_once __DIR__ . '/../controladores/ControladorAreaConocimiento.php';

// ================================================================
// OBJETIVO DE DESARROLLO SOSTENIBLE
// ================================================================

require_once __DIR__ . '/../modelos/ObjetivoDesarrolloSostenible.php';
require_once __DIR__ . '/../repositorios/Repositorios_ObjetivoDesarrolloSostenible/IRepositorioObjetivoDesarrolloSostenible.php';
require_once __DIR__ . '/../repositorios/Repositorios_ObjetivoDesarrolloSostenible/RepositorioObjetivoDesarrolloSostenibleMariaDB.php';
require_once __DIR__ . '/Servicios_ObjetivoDesarrolloSostenible/IServicioObjetivoDesarrolloSostenible.php';
require_once __DIR__ . '/Servicios_ObjetivoDesarrolloSostenible/ServicioObjetivoDesarrolloSostenible.php';
require_once __DIR__ . '/../controladores/ControladorObjetivoDesarrolloSostenible.php';

// ================================================================
// AREA DE APLICACION
// ================================================================

require_once __DIR__ . '/../modelos/AreaAplicacion.php';
require_once __DIR__ . '/../repositorios/Repositorios_AreaAplicacion/IRepositorioAreaAplicacion.php';
require_once __DIR__ . '/../repositorios/Repositorios_AreaAplicacion/RepositorioAreaAplicacionMariaDB.php';
require_once __DIR__ . '/Servicios_AreaAplicacion/IServicioAreaAplicacion.php';
require_once __DIR__ . '/Servicios_AreaAplicacion/ServicioAreaAplicacion.php';
require_once __DIR__ . '/../controladores/ControladorAreaAplicacion.php';

// ================================================================
// RECURSOS DE ANGEL
// ================================================================

require_once __DIR__ . '/../repositorios/Repositorios_TerminoClave/RepositorioTerminoClaveMariaDB.php';
require_once __DIR__ . '/Servicios_TerminoClave/ServicioTerminoClave.php';
require_once __DIR__ . '/../controladores/ControladorTerminoClave.php';

require_once __DIR__ . '/../repositorios/Repositorios_Universidad/RepositorioUniversidadMariaDB.php';
require_once __DIR__ . '/Servicios_Universidad/ServicioUniversidad.php';
require_once __DIR__ . '/../controladores/ControladorUniversidad.php';

require_once __DIR__ . '/../repositorios/Repositorios_LineaInvestigacion/RepositorioLineaInvestigacionMariaDB.php';
require_once __DIR__ . '/Servicios_LineaInvestigacion/ServicioLineaInvestigacion.php';
require_once __DIR__ . '/../controladores/ControladorLineaInvestigacion.php';

// ================================================================
// GRUPO DE INVESTIGACION — V2
// ================================================================

require_once __DIR__ . '/../modelos/GrupoInvestigacion.php';
require_once __DIR__ . '/../repositorios/Repositorios_GrupoInvestigacion/IRepositorioGrupoInvestigacion.php';
require_once __DIR__ . '/../repositorios/Repositorios_GrupoInvestigacion/RepositorioGrupoInvestigacionMariaDB.php';
require_once __DIR__ . '/Servicios_GrupoInvestigacion/IServicioGrupoInvestigacion.php';
require_once __DIR__ . '/Servicios_GrupoInvestigacion/ServicioGrupoInvestigacion.php';
require_once __DIR__ . '/../controladores/ControladorGrupoInvestigacion.php';

// ================================================================
// SEMILLERO — V2
// ================================================================

require_once __DIR__ . '/../modelos/Semillero.php';
require_once __DIR__ . '/../repositorios/Repositorios_Semillero/IRepositorioSemillero.php';
require_once __DIR__ . '/../repositorios/Repositorios_Semillero/RepositorioSemilleroMariaDB.php';
require_once __DIR__ . '/Servicios_Semillero/IServicioSemillero.php';
require_once __DIR__ . '/Servicios_Semillero/ServicioSemillero.php';
require_once __DIR__ . '/../controladores/ControladorSemillero.php';

// ================================================================
// DOCENTE — V2
// ================================================================

require_once __DIR__ . '/../modelos/Docente.php';
require_once __DIR__ . '/../repositorios/Repositorios_Docente/IRepositorioDocente.php';
require_once __DIR__ . '/../repositorios/Repositorios_Docente/RepositorioDocenteMariaDB.php';
require_once __DIR__ . '/Servicios_Docente/IServicioDocente.php';
require_once __DIR__ . '/Servicios_Docente/ServicioDocente.php';
require_once __DIR__ . '/../controladores/ControladorDocente.php';

/**
 * Construye la conexión PDO utilizando las variables de entorno.
 */
function crearConexion(): PDO
{
    $dsn = getenv('DB_DSN');
    $usuario = getenv('DB_USUARIO');
    $clave = getenv('DB_CLAVE');

    if (
        $dsn === false
        || $usuario === false
        || $clave === false
    ) {
        throw new RuntimeException(
            'Falta la configuración de conexión a la base de datos.'
        );
    }

    return new PDO(
        $dsn,
        $usuario,
        $clave,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_FOUND_ROWS => true,
        ]
    );
}

/**
 * Construye todos los controladores de la API.
 */
function ensamblarControladores(): array
{
    $pdo = crearConexion();

    // AREA DE CONOCIMIENTO
    $repositorioAreaConocimiento =
        new RepositorioAreaConocimientoMariaDB($pdo);

    $servicioAreaConocimiento =
        new ServicioAreaConocimiento($repositorioAreaConocimiento);

    $controladorAreaConocimiento =
        new ControladorAreaConocimiento($servicioAreaConocimiento);

    // OBJETIVO DE DESARROLLO SOSTENIBLE
    $repositorioObjetivo =
        new RepositorioObjetivoDesarrolloSostenibleMariaDB($pdo);

    $servicioObjetivo =
        new ServicioObjetivoDesarrolloSostenible($repositorioObjetivo);

    $controladorObjetivo =
        new ControladorObjetivoDesarrolloSostenible($servicioObjetivo);

    // AREA DE APLICACION
    $repositorioAreaAplicacion =
        new RepositorioAreaAplicacionMariaDB($pdo);

    $servicioAreaAplicacion =
        new ServicioAreaAplicacion($repositorioAreaAplicacion);

    $controladorAreaAplicacion =
        new ControladorAreaAplicacion($servicioAreaAplicacion);

    // TERMINO CLAVE
    $repositorioTerminoClave =
        new RepositorioTerminoClaveMariaDB($pdo);

    $servicioTerminoClave =
        new ServicioTerminoClave($repositorioTerminoClave);

    $controladorTerminoClave =
        new ControladorTerminoClave($servicioTerminoClave);

    // UNIVERSIDAD
    $repositorioUniversidad =
        new RepositorioUniversidadMariaDB($pdo);

    $servicioUniversidad =
        new ServicioUniversidad($repositorioUniversidad);

    $controladorUniversidad =
        new ControladorUniversidad($servicioUniversidad);

    // LINEA DE INVESTIGACION
    $repositorioLineaInvestigacion =
        new RepositorioLineaInvestigacionMariaDB($pdo);

    $servicioLineaInvestigacion =
        new ServicioLineaInvestigacion($repositorioLineaInvestigacion);

    $controladorLineaInvestigacion =
        new ControladorLineaInvestigacion($servicioLineaInvestigacion);

    // GRUPO DE INVESTIGACION — V2
    $repositorioGrupoInvestigacion =
        new RepositorioGrupoInvestigacionMariaDB($pdo);

    // Reutilizamos el repositorio de Universidad creado en la sección de V1
    // para validar que el identificador de universidad exista y esté activo.
    $servicioGrupoInvestigacion = new ServicioGrupoInvestigacion(
        $repositorioGrupoInvestigacion,
        $repositorioUniversidad
    );

    $controladorGrupoInvestigacion =
        new ControladorGrupoInvestigacion($servicioGrupoInvestigacion);

    // SEMILLERO — V2
    // El repositorio de Grupo de Investigación ya está disponible arriba.
    $repositorioSemillero = new RepositorioSemilleroMariaDB($pdo);

    $servicioSemillero = new ServicioSemillero(
        $repositorioSemillero,
        $repositorioGrupoInvestigacion
    );

    $controladorSemillero = new ControladorSemillero($servicioSemillero);

    // DOCENTE — V2
    // Utiliza el repositorio de LineaInvestigacion de V1 (solo lineas activas).
    $repositorioDocente = new RepositorioDocenteMariaDB($pdo);

    $servicioDocente = new ServicioDocente(
        $repositorioDocente,
        $repositorioLineaInvestigacion
    );

    $controladorDocente = new ControladorDocente($servicioDocente);

    // CONTROLADORES DISPONIBLES
    return [
        'area_conocimiento' => $controladorAreaConocimiento,
        'objetivo_desarrollo_sostenible' => $controladorObjetivo,
        'area_aplicacion' => $controladorAreaAplicacion,
        'termino_clave' => $controladorTerminoClave,
        'universidad' => $controladorUniversidad,
        'linea_investigacion' => $controladorLineaInvestigacion,
        'grupo_investigacion' => $controladorGrupoInvestigacion,
        'semillero' => $controladorSemillero,
        'docente' => $controladorDocente,
    ];
}
