<?php

/**
 * index.php — Front Controller de la API de Investigación (V1 + V2 en desarrollo).
 *
 * Todas las peticiones HTTP de la API entran por este archivo.
 * Aquí solamente se decide qué controlador debe atender cada ruta.
 *
 * Recursos de V1:
 * - area_conocimiento
 * - objetivo_desarrollo_sostenible
 * - area_aplicacion
 * - termino_clave
 * - universidad
 * - linea_investigacion
 *
 * Recursos habilitados de V2: grupo_investigacion, semillero, docente, participa_grupo, participa_semillero, ac_linea, ods_linea, aa_linea, grupo_linea y semillero_linea.
 *
 * Este archivo NO contiene SQL ni reglas de negocio.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// ----------------------------------------------------------------------
// 1. Capturar la petición
// ----------------------------------------------------------------------

$metodo = $_SERVER['REQUEST_METHOD'];
$ruta = rtrim(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
    '/'
) ?: '/';

$contenido = file_get_contents('php://input');
$cuerpo = [];

if ($contenido !== '') {
    $decodificado = json_decode($contenido, true);

    if (!is_array($decodificado)) {
        http_response_code(422);

        echo json_encode(
            [
                'estado' => 422,
                'mensaje' => 'Datos inválidos.',
                'errores' => [
                    'El cuerpo debe contener un JSON válido.',
                ],
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        return;
    }

    $cuerpo = $decodificado;
}

// ----------------------------------------------------------------------
// 2. Diagnóstico
// ----------------------------------------------------------------------

if ($ruta === '/' && $metodo === 'GET') {
    echo json_encode(
        [
            'mensaje' => 'API de Investigación funcionando',
            'version' => 'v2-en-desarrollo',
            'recursos' => [
                'area_conocimiento',
                'objetivo_desarrollo_sostenible',
                'area_aplicacion',
                'termino_clave',
                'universidad',
                'linea_investigacion',
                'grupo_investigacion',
                'semillero',
                'docente',
                'participa_grupo',
                'participa_semillero',
                'ac_linea',
                'ods_linea',
                'aa_linea',
                'grupo_linea',
                'semillero_linea',
            ],
        ],
        JSON_UNESCAPED_UNICODE
    );

    return;
}

// ======================================================================
// AREA DE CONOCIMIENTO
// ======================================================================

if ($ruta === '/api/area_conocimiento') {

    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['area_conocimiento'];

    if ($metodo === 'GET') {
        $controlador->listar();
    } elseif ($metodo === 'POST') {
        $controlador->crear($cuerpo);
    } else {
        responderNoPermitido();
    }

    return;
}

if (
    preg_match(
        '#^/api/area_conocimiento/([^/]+)$#',
        $ruta,
        $coincidencias
    )
) {

    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['area_conocimiento'];

    $clave = urldecode($coincidencias[1]);

    enrutarFicha(
        $controlador,
        $metodo,
        $clave,
        $cuerpo
    );

    return;
}

// ======================================================================
// OBJETIVO DE DESARROLLO SOSTENIBLE
// ======================================================================

if ($ruta === '/api/objetivo_desarrollo_sostenible') {

    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['objetivo_desarrollo_sostenible'];

    if ($metodo === 'GET') {
        $controlador->listar();
    } elseif ($metodo === 'POST') {
        $controlador->crear($cuerpo);
    } else {
        responderNoPermitido();
    }

    return;
}

if (
    preg_match(
        '#^/api/objetivo_desarrollo_sostenible/([^/]+)$#',
        $ruta,
        $coincidencias
    )
) {

    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['objetivo_desarrollo_sostenible'];

    $clave = (int) urldecode($coincidencias[1]);

    enrutarFicha(
        $controlador,
        $metodo,
        $clave,
        $cuerpo
    );

    return;
}

// ======================================================================
// AREA DE APLICACION
// ======================================================================

if ($ruta === '/api/area_aplicacion') {

    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['area_aplicacion'];

    if ($metodo === 'GET') {
        $controlador->listar();
    } elseif ($metodo === 'POST') {
        $controlador->crear($cuerpo);
    } else {
        responderNoPermitido();
    }

    return;
}

if (
    preg_match(
        '#^/api/area_aplicacion/([^/]+)$#',
        $ruta,
        $coincidencias
    )
) {

    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['area_aplicacion'];

    $clave = (int) urldecode($coincidencias[1]);

    enrutarFicha(
        $controlador,
        $metodo,
        $clave,
        $cuerpo
    );

    return;
}

// ======================================================================
// TERMINO CLAVE
// ======================================================================

if ($ruta === '/api/termino_clave') {

    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['termino_clave'];

    if ($metodo === 'GET') {
        $controlador->listar();
    } elseif ($metodo === 'POST') {
        $controlador->crear($cuerpo);
    } else {
        responderNoPermitido();
    }

    return;
}

if (
    preg_match(
        '#^/api/termino_clave/([^/]+)$#',
        $ruta,
        $coincidencias
    )
) {

    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['termino_clave'];

    $clave = urldecode($coincidencias[1]);

    enrutarFicha(
        $controlador,
        $metodo,
        $clave,
        $cuerpo
    );

    return;
}

// ======================================================================
// UNIVERSIDAD
// ======================================================================

if ($ruta === '/api/universidad') {

    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['universidad'];

    if ($metodo === 'GET') {
        $controlador->listar();
    } elseif ($metodo === 'POST') {
        $controlador->crear($cuerpo);
    } else {
        responderNoPermitido();
    }

    return;
}

if (
    preg_match(
        '#^/api/universidad/(\d+)$#',
        $ruta,
        $coincidencias
    )
) {

    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['universidad'];

    $clave = (int) $coincidencias[1];

    enrutarFicha(
        $controlador,
        $metodo,
        $clave,
        $cuerpo
    );

    return;
}

// ======================================================================
// LINEA DE INVESTIGACION
// ======================================================================

if ($ruta === '/api/linea_investigacion') {

    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['linea_investigacion'];

    if ($metodo === 'GET') {
        $controlador->listar();
    } elseif ($metodo === 'POST') {
        $controlador->crear($cuerpo);
    } else {
        responderNoPermitido();
    }

    return;
}

if (
    preg_match(
        '#^/api/linea_investigacion/(\d+)$#',
        $ruta,
        $coincidencias
    )
) {

    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['linea_investigacion'];

    $clave = (int) $coincidencias[1];

    enrutarFicha(
        $controlador,
        $metodo,
        $clave,
        $cuerpo
    );

    return;
}

// ======================================================================
// GRUPO DE INVESTIGACION — V2
// ======================================================================

if ($ruta === '/api/grupo_investigacion') {

    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['grupo_investigacion'];

    if ($metodo === 'GET') {
        $controlador->listar();
    } elseif ($metodo === 'POST') {
        $controlador->crear($cuerpo);
    } else {
        responderNoPermitido();
    }

    return;
}

if (
    preg_match(
        '#^/api/grupo_investigacion/(\d+)$#',
        $ruta,
        $coincidencias
    )
) {

    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['grupo_investigacion'];

    $clave = (int) $coincidencias[1];

    enrutarFicha(
        $controlador,
        $metodo,
        $clave,
        $cuerpo
    );

    return;
}

// ======================================================================
// SEMILLERO — V2
// ======================================================================

if ($ruta === '/api/semillero') {

    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['semillero'];

    if ($metodo === 'GET') {
        $controlador->listar();
    } elseif ($metodo === 'POST') {
        $controlador->crear($cuerpo);
    } else {
        responderNoPermitido();
    }

    return;
}

if (
    preg_match(
        '#^/api/semillero/(\d+)$#',
        $ruta,
        $coincidencias
    )
) {

    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['semillero'];

    $clave = (int) $coincidencias[1];

    enrutarFicha(
        $controlador,
        $metodo,
        $clave,
        $cuerpo
    );

    return;
}

// ======================================================================
// DOCENTE — V2
// ======================================================================

if ($ruta === '/api/docente') {

    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['docente'];

    if ($metodo === 'GET') {
        $controlador->listar();
    } elseif ($metodo === 'POST') {
        $controlador->crear($cuerpo);
    } else {
        responderNoPermitido();
    }

    return;
}

if (
    preg_match(
        '#^/api/docente/(\d+)$#',
        $ruta,
        $coincidencias
    )
) {

    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['docente'];

    $cedula = (int) $coincidencias[1];

    enrutarFicha(
        $controlador,
        $metodo,
        $cedula,
        $cuerpo
    );

    return;
}

// ======================================================================
// PARTICIPA GRUPO — V2 (DOS IDENTIFICADORES)
// ======================================================================

// Colección: GET /api/participa_grupo y POST /api/participa_grupo.
if ($ruta === '/api/participa_grupo') {
    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['participa_grupo'];

    if ($metodo === 'GET') {
        $controlador->listar();
    } elseif ($metodo === 'POST') {
        $controlador->crear($cuerpo);
    } else {
        responderNoPermitido();
    }

    return;
}

// Ficha: /api/participa_grupo/{docente_cedula}/{grupo_investigacion_id}.
// Se requieren AMBAS claves, que son enteros positivos.
if (preg_match('#^/api/participa_grupo/([1-9]\d*)/([1-9]\d*)$#', $ruta, $coincidencias)) {
    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['participa_grupo'];
    $cedula = (int) $coincidencias[1];
    $grupoId = (int) $coincidencias[2];

    if ($metodo === 'GET') {
        $controlador->obtener($cedula, $grupoId);
    } elseif ($metodo === 'PUT') {
        $controlador->reemplazar($cedula, $grupoId, $cuerpo);
    } elseif ($metodo === 'PATCH') {
        $controlador->actualizar($cedula, $grupoId, $cuerpo);
    } elseif ($metodo === 'DELETE') {
        $controlador->retirar($cedula, $grupoId);
    } else {
        responderNoPermitido();
    }

    return;
}

// ======================================================================
// PARTICIPA SEMILLERO — V2 (DOS IDENTIFICADORES)
// ======================================================================

// Coleccion: GET y POST sobre /api/participa_semillero.
if ($ruta === '/api/participa_semillero') {
    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['participa_semillero'];

    if ($metodo === 'GET') {
        $controlador->listar();
    } elseif ($metodo === 'POST') {
        $controlador->crear($cuerpo);
    } else {
        responderNoPermitido();
    }

    return;
}

// Ficha: /api/participa_semillero/{docente}/{semillero}.
// Ambos identificadores son enteros positivos; no se editan mediante PUT/PATCH.
if (preg_match('#^/api/participa_semillero/([1-9]\d*)/([1-9]\d*)$#', $ruta, $coincidencias)) {
    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['participa_semillero'];
    $docenteId = (int) $coincidencias[1];
    $semilleroId = (int) $coincidencias[2];

    if ($metodo === 'GET') {
        $controlador->obtener($docenteId, $semilleroId);
    } elseif ($metodo === 'PUT') {
        $controlador->reemplazar($docenteId, $semilleroId, $cuerpo);
    } elseif ($metodo === 'PATCH') {
        $controlador->actualizar($docenteId, $semilleroId, $cuerpo);
    } elseif ($metodo === 'DELETE') {
        $controlador->retirar($docenteId, $semilleroId);
    } else {
        responderNoPermitido();
    }

    return;
}

// ======================================================================
// AC LINEA — V2 (CLAVE COMPUESTA INT + VARCHAR)
// ======================================================================

// Coleccion: GET y POST sobre /api/ac_linea.
if ($ruta === '/api/ac_linea') {
    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['ac_linea'];

    if ($metodo === 'GET') {
        $controlador->listar();
    } elseif ($metodo === 'POST') {
        $controlador->crear($cuerpo);
    } else {
        responderNoPermitido();
    }

    return;
}

// Ficha: /api/ac_linea/{linea_investigacion}/{area_conocimiento}.
// Ejemplo: /api/ac_linea/11/1A01 (area_conocimiento es texto).
if (preg_match('#^/api/ac_linea/([1-9]\d*)/([^/]+)$#', $ruta, $coincidencias)) {
    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['ac_linea'];

    $lineaId = (int) $coincidencias[1];
    $areaId = rawurldecode($coincidencias[2]);

    if ($metodo === 'GET') {
        $controlador->obtener($lineaId, $areaId);
    } elseif ($metodo === 'DELETE') {
        $controlador->retirar($lineaId, $areaId);
    } elseif ($metodo === 'PUT') {
        // Esta tabla no tiene columnas editables: el controlador responde 405.
        $controlador->reemplazar($lineaId, $areaId, $cuerpo);
    } elseif ($metodo === 'PATCH') {
        $controlador->actualizar($lineaId, $areaId, $cuerpo);
    } else {
        responderNoPermitido();
    }

    return;
}

// ======================================================================
// ODS LINEA — V2 (CLAVE COMPUESTA INT + INT)
// ======================================================================

// Coleccion: GET y POST sobre /api/ods_linea.
if ($ruta === '/api/ods_linea') {
    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['ods_linea'];

    if ($metodo === 'GET') {
        $controlador->listar();
    } elseif ($metodo === 'POST') {
        $controlador->crear($cuerpo);
    } else {
        responderNoPermitido();
    }

    return;
}

// Ficha: /api/ods_linea/{linea_investigacion}/{ods}.
// Los dos identificadores son enteros positivos e inmutables.
if (preg_match('#^/api/ods_linea/([1-9]\d*)/([1-9]\d*)$#', $ruta, $coincidencias)) {
    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['ods_linea'];
    $lineaId = (int) $coincidencias[1];
    $odsId = (int) $coincidencias[2];

    if ($metodo === 'GET') {
        $controlador->obtener($lineaId, $odsId);
    } elseif ($metodo === 'DELETE') {
        $controlador->retirar($lineaId, $odsId);
    } elseif ($metodo === 'PUT') {
        $controlador->reemplazar($lineaId, $odsId, $cuerpo);
    } elseif ($metodo === 'PATCH') {
        $controlador->actualizar($lineaId, $odsId, $cuerpo);
    } else {
        responderNoPermitido();
    }

    return;
}

// ======================================================================
// AA LINEA — V2 (CLAVE COMPUESTA INT + INT)
// ======================================================================

// Coleccion: GET y POST /api/aa_linea.
if ($ruta === '/api/aa_linea') {
    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['aa_linea'];

    if ($metodo === 'GET') {
        $controlador->listar();
    } elseif ($metodo === 'POST') {
        $controlador->crear($cuerpo);
    } else {
        responderNoPermitido();
    }

    return;
}

// Ficha: /api/aa_linea/{area_aplicacion}/{linea_investigacion}.
// Ambas claves son enteros positivos. No hay campos editables mediante PUT/PATCH.
if (preg_match('#^/api/aa_linea/([1-9]\d*)/([1-9]\d*)$#', $ruta, $coincidencias)) {
    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['aa_linea'];
    $areaAplicacionId = (int) $coincidencias[1];
    $lineaInvestigacionId = (int) $coincidencias[2];

    if ($metodo === 'GET') {
        $controlador->obtener($areaAplicacionId, $lineaInvestigacionId);
    } elseif ($metodo === 'DELETE') {
        $controlador->retirar($areaAplicacionId, $lineaInvestigacionId);
    } elseif ($metodo === 'PUT') {
        $controlador->reemplazar($areaAplicacionId, $lineaInvestigacionId, $cuerpo);
    } elseif ($metodo === 'PATCH') {
        $controlador->actualizar($areaAplicacionId, $lineaInvestigacionId, $cuerpo);
    } else {
        responderNoPermitido();
    }

    return;
}

// ======================================================================
// GRUPO LINEA — V2 (CLAVE COMPUESTA INT + INT)
// ======================================================================

// Coleccion: GET y POST /api/grupo_linea.
if ($ruta === '/api/grupo_linea') {
    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['grupo_linea'];

    if ($metodo === 'GET') {
        $controlador->listar();
    } elseif ($metodo === 'POST') {
        $controlador->crear($cuerpo);
    } else {
        responderNoPermitido();
    }

    return;
}

// Ficha: /api/grupo_linea/{grupo_investigacion}/{linea_investigacion}.
// Ambas claves deben ser enteros positivos; PUT y PATCH devuelven HTTP 405.
if (preg_match('#^/api/grupo_linea/([1-9]\d*)/([1-9]\d*)$#', $ruta, $coincidencias)) {
    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['grupo_linea'];
    $grupoInvestigacionId = (int) $coincidencias[1];
    $lineaInvestigacionId = (int) $coincidencias[2];

    if ($metodo === 'GET') {
        $controlador->obtener($grupoInvestigacionId, $lineaInvestigacionId);
    } elseif ($metodo === 'DELETE') {
        $controlador->retirar($grupoInvestigacionId, $lineaInvestigacionId);
    } elseif ($metodo === 'PUT') {
        $controlador->reemplazar($grupoInvestigacionId, $lineaInvestigacionId, $cuerpo);
    } elseif ($metodo === 'PATCH') {
        $controlador->actualizar($grupoInvestigacionId, $lineaInvestigacionId, $cuerpo);
    } else {
        responderNoPermitido();
    }

    return;
}

// ======================================================================
// SEMILLERO LINEA — V2 (CLAVE COMPUESTA INT + INT)
// ======================================================================

// Coleccion: GET y POST sobre /api/semillero_linea.
if ($ruta === '/api/semillero_linea') {
    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['semillero_linea'];

    if ($metodo === 'GET') {
        $controlador->listar();
    } elseif ($metodo === 'POST') {
        $controlador->crear($cuerpo);
    } else {
        responderNoPermitido();
    }

    return;
}

// Ficha: /api/semillero_linea/{semillero}/{linea_investigacion}.
// Los dos identificadores son enteros positivos y no se pueden modificar.
if (preg_match('#^/api/semillero_linea/([1-9]\d*)/([1-9]\d*)$#', $ruta, $coincidencias)) {
    require_once __DIR__ . '/servicios/ensamblador.php';

    $controladores = ensamblarControladores();
    $controlador = $controladores['semillero_linea'];
    $semilleroId = (int) $coincidencias[1];
    $lineaInvestigacionId = (int) $coincidencias[2];

    if ($metodo === 'GET') {
        $controlador->obtener($semilleroId, $lineaInvestigacionId);
    } elseif ($metodo === 'DELETE') {
        $controlador->retirar($semilleroId, $lineaInvestigacionId);
    } elseif ($metodo === 'PUT') {
        $controlador->reemplazar($semilleroId, $lineaInvestigacionId, $cuerpo);
    } elseif ($metodo === 'PATCH') {
        $controlador->actualizar($semilleroId, $lineaInvestigacionId, $cuerpo);
    } else {
        responderNoPermitido();
    }

    return;
}

// ----------------------------------------------------------------------
// Ninguna ruta coincidió
// ----------------------------------------------------------------------

http_response_code(404);

echo json_encode(
    [
        'estado' => 404,
        'mensaje' => 'Ruta no encontrada.',
        'detalle' => "$metodo $ruta",
    ],
    JSON_UNESCAPED_UNICODE
);

// ----------------------------------------------------------------------
// Funciones auxiliares del ENRUTADOR
// ----------------------------------------------------------------------

function enrutarFicha(
    object $controlador,
    string $metodo,
    string|int $clave,
    array $cuerpo
): void {
    if ($metodo === 'GET') {
        $controlador->obtener($clave);
    } elseif ($metodo === 'PUT') {
        $controlador->reemplazar($clave, $cuerpo);
    } elseif ($metodo === 'PATCH') {
        $controlador->actualizar($clave, $cuerpo);
    } elseif ($metodo === 'DELETE') {
        $controlador->retirar($clave);
    } else {
        responderNoPermitido();
    }
}

function responderNoPermitido(): void
{
    http_response_code(405);

    echo json_encode(
        [
            'estado' => 405,
            'mensaje' => 'Método no permitido para esta ruta.',
        ],
        JSON_UNESCAPED_UNICODE
    );
}