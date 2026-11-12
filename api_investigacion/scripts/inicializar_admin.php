<?php

declare(strict_types=1);

// Solo se ejecuta desde la consola. Nunca se ofrece como endpoint HTTP.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../servicios/ensamblador.php';

$username = getenv('ADMIN_INICIAL_USERNAME');
$email = getenv('ADMIN_INICIAL_EMAIL');
$contrasena = getenv('ADMIN_INICIAL_PASSWORD');

if (
    !is_string($username) || trim($username) === '' ||
    !is_string($email) || trim($email) === '' ||
    !is_string($contrasena) || $contrasena === ''
) {
    fwrite(STDERR, "Faltan ADMIN_INICIAL_USERNAME, ADMIN_INICIAL_EMAIL o ADMIN_INICIAL_PASSWORD.\n");
    exit(1);
}

try {
    $servicio = crearServicioInicializacionSeguridad();
    $resultado = $servicio->inicializarAdministrador($username, $email, $contrasena);
    if ($resultado === 'creado') {
        fwrite(STDOUT, "Administrador inicial creado y rol asignado correctamente.\n");
    } else {
        fwrite(STDOUT, "Ya existe un administrador activo. No se duplicó la cuenta ni se cambió la contraseña.\n");
    }
} catch (InvalidArgumentException | RuntimeException $excepcion) {
    fwrite(STDERR, $excepcion->getMessage() . "\n");
    exit(1);
} catch (Throwable $excepcion) {
    // No imprimir el DSN ni las excepciones internas de PDO en la consola.
    fwrite(STDERR, "Error durante la inicialización. Comprueba la configuración y las tablas.\n");
    exit(1);
}
