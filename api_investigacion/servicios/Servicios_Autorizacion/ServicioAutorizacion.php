<?php

declare(strict_types=1);

/** Comprueba identidad y permisos vigentes sin lógica HTTP ni consultas SQL propias. */
final class ServicioAutorizacion
{
    /** Lista positiva: los demás recursos son exclusivos de admin por defecto. */
    private const RECURSOS_CONSULTA = [
        'area_conocimiento', 'objetivo_desarrollo_sostenible',
        'area_aplicacion', 'termino_clave', 'universidad',
        'linea_investigacion', 'grupo_investigacion', 'semillero',
        'docente', 'participa_grupo', 'participa_semillero',
        'ac_linea', 'ods_linea', 'aa_linea',
        'grupo_linea', 'semillero_linea',
    ];

    public function __construct(
        private IRepositorioAutenticacion $repositorio,
        private JwtHS256 $jwt
    ) {
    }

    /**
     * @return array{id:int,username:string,roles:array<int,string>}
     * @throws ExcepcionAcceso 401 si token o identidad inválidos; 403 si faltan permisos.
     */
    public function autorizar(string $token, string $ruta, string $metodo): array
    {
        try {
            $datos = $this->jwt->verificar($token);
        } catch (DomainException $excepcion) {
            throw ExcepcionAcceso::sinAutenticacion();
        }

        $idToken = $datos['sub'];
        $usernameToken = $datos['username'] ?? null;
        if (!is_string($usernameToken) || $usernameToken === '' || !ctype_digit($idToken)) {
            throw ExcepcionAcceso::sinAutenticacion();
        }

        // Reutiliza el repositorio del LOGIN: solo obtiene usuarios activos.
        // No confiar en los roles de la respuesta anterior ni en atributos del cliente.
        $usuario = $this->repositorio->obtenerUsuarioActivoPorUsername($usernameToken);
        if (
            $usuario === null
            || (string) ($usuario['id'] ?? '') !== $idToken
            || ($usuario['username'] ?? null) !== $usernameToken
        ) {
            throw ExcepcionAcceso::sinAutenticacion();
        }

        $roles = $this->repositorio->listarRolesActivos((int) $idToken);
        $esAdmin = in_array('admin', $roles, true);
        $esConsulta = in_array('consulta', $roles, true);

        if ($esAdmin) {
            return ['id' => (int) $idToken, 'username' => $usernameToken, 'roles' => $roles];
        }

        $segmentos = explode('/', trim($ruta, '/'));
        $recurso = $segmentos[1] ?? '';
        // Sólo los 16 recursos explícitos admiten GET de consulta.
        // Una futura ruta administrativa será denegada por defecto.
        if ($esConsulta && $metodo === 'GET' && in_array($recurso, self::RECURSOS_CONSULTA, true)) {
            return ['id' => (int) $idToken, 'username' => $usernameToken, 'roles' => $roles];
        }

        throw ExcepcionAcceso::sinPermisos();
    }
}
