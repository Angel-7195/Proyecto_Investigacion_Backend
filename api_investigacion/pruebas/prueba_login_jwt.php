<?php

declare(strict_types=1);

require_once __DIR__ . '/../repositorios/Repositorios_Autenticacion/IRepositorioAutenticacion.php';
require_once __DIR__ . '/../servicios/Servicios_Autenticacion/JwtHS256.php';
require_once __DIR__ . '/../servicios/Servicios_Autenticacion/ServicioAutenticacion.php';

final class RepositorioAutenticacionFalso implements IRepositorioAutenticacion
{
    public bool $conRoles = true;

    public function __construct(private string $hash)
    {
    }

    public function obtenerUsuarioActivoPorUsername(string $username): ?array
    {
        return $username === 'ADMIN'
            ? ['id' => 5, 'username' => 'ADMIN', 'password' => $this->hash, 'email' => 'no-usar@ejemplo.invalid']
            : null;
    }

    public function listarRolesActivos(int $usuarioId): array
    {
        return $usuarioId === 5 && $this->conRoles ? ['admin'] : [];
    }
}

$correctas = 0;
function comprobar(string $caso, bool $resultado): void
{
    global $correctas;
    if (!$resultado) {
        fwrite(STDERR, "FALLÓ: $caso\n");
        exit(1);
    }
    $correctas++;
    echo "OK: $caso\n";
}

function debeFallar(string $caso, callable $accion, string $tipo): void
{
    try {
        $accion();
        comprobar($caso, false);
    } catch (Throwable $error) {
        comprobar($caso, $error instanceof $tipo);
    }
}

$clave = str_repeat('A', 64); // Dato ficticio solamente para pruebas, no producción.
$repo = new RepositorioAutenticacionFalso(password_hash('ContrasenaDePrueba1234', PASSWORD_DEFAULT));
$jwt = new JwtHS256($clave);
$servicio = new ServicioAutenticacion($repo, $jwt);
$resultado = $servicio->iniciarSesion(['username' => 'ADMIN', 'password' => 'ContrasenaDePrueba1234']);
comprobar('Login correcto y expira en 3600 segundos', $resultado['expira_en'] === 3600);
comprobar('Rol admin en la respuesta', $resultado['usuario']['roles'] === ['admin']);
comprobar('No expone el hash de contraseña', !str_contains(json_encode($resultado), $repo->obtenerUsuarioActivoPorUsername('ADMIN')['password']));
$datos = $jwt->verificar($resultado['token']);
comprobar('Firma válida e identidad del JWT', $datos['sub'] === '5' && $datos['username'] === 'ADMIN');
comprobar('La expiración es iat + 3600', $datos['exp'] - $datos['iat'] === 3600);

debeFallar('Contraseña incorrecta', fn () => $servicio->iniciarSesion(['username' => 'ADMIN', 'password' => 'incorrecta']), DomainException::class);
debeFallar('Usuario inexistente', fn () => $servicio->iniciarSesion(['username' => 'NADIE', 'password' => 'incorrecta']), DomainException::class);
debeFallar('Cuerpo incompleto', fn () => $servicio->iniciarSesion(['username' => 'ADMIN']), InvalidArgumentException::class);
debeFallar('Datos de tipos incorrectos', fn () => $servicio->iniciarSesion(['username' => 12, 'password' => ['x']]), InvalidArgumentException::class);
$repo->conRoles = false;
debeFallar('Usuario sin roles activos', fn () => $servicio->iniciarSesion(['username' => 'ADMIN', 'password' => 'ContrasenaDePrueba1234']), DomainException::class);
$repo->conRoles = true;
$partes = explode('.', $resultado['token']);
$parteFalsa = substr($partes[1], 0, -1) . ($partes[1][-1] === 'A' ? 'B' : 'A');
debeFallar('Token alterado', fn () => $jwt->verificar($partes[0] . '.' . $parteFalsa . '.' . $partes[2]), DomainException::class);
debeFallar('Firma de otra clave', fn () => (new JwtHS256(str_repeat('B', 64)))->verificar($resultado['token']), DomainException::class);
debeFallar('Token con algoritmo none', function () use ($jwt, $partes) {
    $cabeceraFalsa = rtrim(strtr(base64_encode('{"alg":"none","typ":"JWT"}'), '+/', '-_'), '=');
    $jwt->verificar($cabeceraFalsa . '.' . $partes[1] . '.' . $partes[2]);
}, DomainException::class);
debeFallar('JWT_SECRET demasiado corto', fn () => new JwtHS256('corto'), RuntimeException::class);

$payloadVencido = ['iss' => 'investigacion-api', 'sub' => '5', 'username' => 'ADMIN', 'iat' => time() - 7200, 'nbf' => time() - 7200, 'exp' => time() - 3600];
$h = rtrim(strtr(base64_encode('{"typ":"JWT","alg":"HS256"}'), '+/', '-_'), '=');
$p = rtrim(strtr(base64_encode(json_encode($payloadVencido, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
$f = rtrim(strtr(base64_encode(hash_hmac('sha256', "$h.$p", $clave, true)), '+/', '-_'), '=');
debeFallar('Token expirado con firma correcta', fn () => $jwt->verificar("$h.$p.$f"), DomainException::class);

echo "\nPruebas locales completadas: $correctas / $correctas.\n";
