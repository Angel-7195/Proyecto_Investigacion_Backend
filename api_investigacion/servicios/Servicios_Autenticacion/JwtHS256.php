<?php

declare(strict_types=1);

/** Firma y valida JWT HS256 sin dependencias externas.
 * El middleware de autorización reutilizará verificar() en la siguiente fase.
 */
final class JwtHS256
{
    public const DURACION_SEGUNDOS = 3600;
    private const EMISOR = 'investigacion-api';

    public function __construct(private string $secreto)
    {
        if (strlen($secreto) < 32) {
            throw new RuntimeException('JWT_SECRET no está configurado correctamente.');
        }
    }

    public function emitir(int $usuarioId, string $username): string
    {
        if ($usuarioId < 1 || $username === '') {
            throw new InvalidArgumentException('Identidad inválida para emitir el token.');
        }

        $ahora = time();
        $cabecera = ['typ' => 'JWT', 'alg' => 'HS256'];
        $datos = [
            'iss' => self::EMISOR,
            'sub' => (string) $usuarioId,
            'username' => $username,
            'iat' => $ahora,
            'nbf' => $ahora,
            'exp' => $ahora + self::DURACION_SEGUNDOS,
        ];

        $primero = self::base64UrlCodificar(json_encode($cabecera, JSON_THROW_ON_ERROR));
        $segundo = self::base64UrlCodificar(json_encode($datos, JSON_THROW_ON_ERROR));
        $mensaje = $primero . '.' . $segundo;
        $firma = self::base64UrlCodificar(hash_hmac('sha256', $mensaje, $this->secreto, true));

        return $mensaje . '.' . $firma;
    }

    /** Verifica firma, algoritmo, emisor, identidad y expiración; lanza DomainException si falla. */
    public function verificar(string $token): array
    {
        if ($token === '' || strlen($token) > 4096) {
            throw new DomainException('Token inválido o vencido.');
        }

        $segmentos = explode('.', $token);
        if (count($segmentos) !== 3) {
            throw new DomainException('Token inválido o vencido.');
        }

        try {
            $cabecera = json_decode(self::base64UrlDecodificar($segmentos[0]), true, 512, JSON_THROW_ON_ERROR);
            $datos = json_decode(self::base64UrlDecodificar($segmentos[1]), true, 512, JSON_THROW_ON_ERROR);
            self::base64UrlDecodificar($segmentos[2]);
        } catch (JsonException | DomainException $e) {
            throw new DomainException('Token inválido o vencido.', 0, $e);
        }

        if (!is_array($cabecera) || ($cabecera['alg'] ?? null) !== 'HS256') {
            // La cabecera suministrada por el cliente nunca decide el algoritmo.
            throw new DomainException('Token inválido o vencido.');
        }
        if (($cabecera['typ'] ?? null) !== 'JWT' || !is_array($datos)) {
            throw new DomainException('Token inválido o vencido.');
        }

        $mensaje = $segmentos[0] . '.' . $segmentos[1];
        $firmaEsperada = self::base64UrlCodificar(hash_hmac('sha256', $mensaje, $this->secreto, true));
        if (!hash_equals($firmaEsperada, $segmentos[2])) {
            throw new DomainException('Token inválido o vencido.');
        }

        $ahora = time();
        $sub = $datos['sub'] ?? null;
        $iat = $datos['iat'] ?? null;
        $nbf = $datos['nbf'] ?? null;
        $exp = $datos['exp'] ?? null;

        if (
            ($datos['iss'] ?? null) !== self::EMISOR
            || !is_string($sub)
            || !ctype_digit($sub)
            || (int) $sub < 1
            || !is_int($iat) || !is_int($nbf) || !is_int($exp)
            || $iat > $ahora + 30
            || $nbf > $ahora
            || $exp <= $ahora
            || $exp <= $iat
            || $exp - $iat > self::DURACION_SEGUNDOS
        ) {
            throw new DomainException('Token inválido o vencido.');
        }

        return $datos;
    }

    private static function base64UrlCodificar(string $texto): string
    {
        return rtrim(strtr(base64_encode($texto), '+/', '-_'), '=');
    }

    private static function base64UrlDecodificar(string $texto): string
    {
        if ($texto === '' || preg_match('/^[A-Za-z0-9_-]+$/D', $texto) !== 1) {
            throw new DomainException('Token inválido o vencido.');
        }

        $decodificado = base64_decode(strtr($texto, '-_', '+/'), true);
        if ($decodificado === false || self::base64UrlCodificar($decodificado) !== $texto) {
            throw new DomainException('Token inválido o vencido.');
        }

        return $decodificado;
    }
}
