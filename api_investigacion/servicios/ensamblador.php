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

// ================================================================
// PARTICIPA GRUPO — V2 (CLAVE PRIMARIA COMPUESTA)
// ================================================================

require_once __DIR__ . '/../modelos/ParticipaGrupo.php';
require_once __DIR__ . '/../repositorios/Repositorios_ParticipaGrupo/IRepositorioParticipaGrupo.php';
require_once __DIR__ . '/../repositorios/Repositorios_ParticipaGrupo/RepositorioParticipaGrupoMariaDB.php';
require_once __DIR__ . '/Servicios_ParticipaGrupo/IServicioParticipaGrupo.php';
require_once __DIR__ . '/Servicios_ParticipaGrupo/ServicioParticipaGrupo.php';
require_once __DIR__ . '/../controladores/ControladorParticipaGrupo.php';

// ================================================================
// PARTICIPA SEMILLERO — V2 (CLAVE PRIMARIA COMPUESTA)
// ================================================================

require_once __DIR__ . '/../modelos/ParticipaSemillero.php';
require_once __DIR__ . '/../repositorios/Repositorios_ParticipaSemillero/IRepositorioParticipaSemillero.php';
require_once __DIR__ . '/../repositorios/Repositorios_ParticipaSemillero/RepositorioParticipaSemilleroMariaDB.php';
require_once __DIR__ . '/Servicios_ParticipaSemillero/IServicioParticipaSemillero.php';
require_once __DIR__ . '/Servicios_ParticipaSemillero/ServicioParticipaSemillero.php';
require_once __DIR__ . '/../controladores/ControladorParticipaSemillero.php';

// ================================================================
// AC LINEA — V2 (CLAVE COMPUESTA)
// ================================================================

require_once __DIR__ . '/../modelos/AcLinea.php';
require_once __DIR__ . '/../repositorios/Repositorios_AcLinea/IRepositorioAcLinea.php';
require_once __DIR__ . '/../repositorios/Repositorios_AcLinea/RepositorioAcLineaMariaDB.php';
require_once __DIR__ . '/Servicios_AcLinea/IServicioAcLinea.php';
require_once __DIR__ . '/Servicios_AcLinea/ServicioAcLinea.php';
require_once __DIR__ . '/../controladores/ControladorAcLinea.php';

// ================================================================
// ODS LINEA — V2 (CLAVE COMPUESTA)
// ================================================================

require_once __DIR__ . '/../modelos/OdsLinea.php';
require_once __DIR__ . '/../repositorios/Repositorios_OdsLinea/IRepositorioOdsLinea.php';
require_once __DIR__ . '/../repositorios/Repositorios_OdsLinea/RepositorioOdsLineaMariaDB.php';
require_once __DIR__ . '/Servicios_OdsLinea/IServicioOdsLinea.php';
require_once __DIR__ . '/Servicios_OdsLinea/ServicioOdsLinea.php';
require_once __DIR__ . '/../controladores/ControladorOdsLinea.php';

// ================================================================
// AA LINEA — V2 (CLAVE COMPUESTA)
// ================================================================

require_once __DIR__ . '/../modelos/AaLinea.php';
require_once __DIR__ . '/../repositorios/Repositorios_AaLinea/IRepositorioAaLinea.php';
require_once __DIR__ . '/../repositorios/Repositorios_AaLinea/RepositorioAaLineaMariaDB.php';
require_once __DIR__ . '/Servicios_AaLinea/IServicioAaLinea.php';
require_once __DIR__ . '/Servicios_AaLinea/ServicioAaLinea.php';
require_once __DIR__ . '/../controladores/ControladorAaLinea.php';

// ================================================================
// GRUPO LINEA — V2 (CLAVE COMPUESTA)
// ================================================================

require_once __DIR__ . '/../modelos/GrupoLinea.php';
require_once __DIR__ . '/../repositorios/Repositorios_GrupoLinea/IRepositorioGrupoLinea.php';
require_once __DIR__ . '/../repositorios/Repositorios_GrupoLinea/RepositorioGrupoLineaMariaDB.php';
require_once __DIR__ . '/Servicios_GrupoLinea/IServicioGrupoLinea.php';
require_once __DIR__ . '/Servicios_GrupoLinea/ServicioGrupoLinea.php';
require_once __DIR__ . '/../controladores/ControladorGrupoLinea.php';

// ================================================================
// SEMILLERO LINEA — V2 (CLAVE COMPUESTA)
// ================================================================

require_once __DIR__ . '/../modelos/SemilleroLinea.php';
require_once __DIR__ . '/../repositorios/Repositorios_SemilleroLinea/IRepositorioSemilleroLinea.php';
require_once __DIR__ . '/../repositorios/Repositorios_SemilleroLinea/RepositorioSemilleroLineaMariaDB.php';
require_once __DIR__ . '/Servicios_SemilleroLinea/IServicioSemilleroLinea.php';
require_once __DIR__ . '/Servicios_SemilleroLinea/ServicioSemilleroLinea.php';
require_once __DIR__ . '/../controladores/ControladorSemilleroLinea.php';

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

    // PARTICIPA GRUPO — V2
    // Reutilizamos los repositorios ya instanciados de Docente y Grupo.
    $repositorioParticipaGrupo = new RepositorioParticipaGrupoMariaDB($pdo);

    $servicioParticipaGrupo = new ServicioParticipaGrupo(
        $repositorioParticipaGrupo,
        $repositorioDocente,
        $repositorioGrupoInvestigacion
    );

    $controladorParticipaGrupo = new ControladorParticipaGrupo($servicioParticipaGrupo);

    // PARTICIPA SEMILLERO — V2
    // Se reutilizan los repositorios de Docente y Semillero ya instanciados.
    $repositorioParticipaSemillero = new RepositorioParticipaSemilleroMariaDB($pdo);

    $servicioParticipaSemillero = new ServicioParticipaSemillero(
        $repositorioParticipaSemillero,
        $repositorioDocente,
        $repositorioSemillero
    );

    $controladorParticipaSemillero = new ControladorParticipaSemillero($servicioParticipaSemillero);

    // AC LINEA — V2
    // Los dos repositorios de V1 ya están creados arriba y filtran activos.
    $repositorioAcLinea = new RepositorioAcLineaMariaDB($pdo);

    $servicioAcLinea = new ServicioAcLinea(
        $repositorioAcLinea,
        $repositorioLineaInvestigacion,
        $repositorioAreaConocimiento
    );

    $controladorAcLinea = new ControladorAcLinea($servicioAcLinea);

    // ODS LINEA — V2
    // Reutiliza los repositorios de LineaInvestigacion y ODS de V1.
    $repositorioOdsLinea = new RepositorioOdsLineaMariaDB($pdo);

    $servicioOdsLinea = new ServicioOdsLinea(
        $repositorioOdsLinea,
        $repositorioLineaInvestigacion,
        $repositorioObjetivo
    );

    $controladorOdsLinea = new ControladorOdsLinea($servicioOdsLinea);

    // AA LINEA — V2
    // Reutiliza AreaAplicacion y LineaInvestigacion, ambos de V1.
    $repositorioAaLinea = new RepositorioAaLineaMariaDB($pdo);

    $servicioAaLinea = new ServicioAaLinea(
        $repositorioAaLinea,
        $repositorioAreaAplicacion,
        $repositorioLineaInvestigacion
    );

    $controladorAaLinea = new ControladorAaLinea($servicioAaLinea);

    // GRUPO LINEA — V2
    // Reutiliza los repositorios de GrupoInvestigacion (V2) y LineaInvestigacion (V1).
    $repositorioGrupoLinea = new RepositorioGrupoLineaMariaDB($pdo);

    $servicioGrupoLinea = new ServicioGrupoLinea(
        $repositorioGrupoLinea,
        $repositorioGrupoInvestigacion,
        $repositorioLineaInvestigacion
    );

    $controladorGrupoLinea = new ControladorGrupoLinea($servicioGrupoLinea);

    // SEMILLERO LINEA — V2
    // Reutiliza los repositorios de Semillero (V2) y LineaInvestigacion (V1).
    $repositorioSemilleroLinea = new RepositorioSemilleroLineaMariaDB($pdo);

    $servicioSemilleroLinea = new ServicioSemilleroLinea(
        $repositorioSemilleroLinea,
        $repositorioSemillero,
        $repositorioLineaInvestigacion
    );

    $controladorSemilleroLinea = new ControladorSemilleroLinea($servicioSemilleroLinea);

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
        'participa_grupo' => $controladorParticipaGrupo,
        'participa_semillero' => $controladorParticipaSemillero,
        'ac_linea' => $controladorAcLinea,
        'ods_linea' => $controladorOdsLinea,
        'aa_linea' => $controladorAaLinea,
        'grupo_linea' => $controladorGrupoLinea,
        'semillero_linea' => $controladorSemilleroLinea,
    ];
}
