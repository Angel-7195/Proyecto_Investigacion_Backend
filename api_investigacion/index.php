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
 * Recursos habilitados de V2: grupo_investigacion y semillero.
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