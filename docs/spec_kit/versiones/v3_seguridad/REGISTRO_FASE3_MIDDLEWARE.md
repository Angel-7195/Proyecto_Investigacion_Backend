# Registro de ejecución — Fase 3: middleware de autorización JWT (v3)

**Fecha de documentación:** 12-11-2026. **Responsable de las pruebas comunicadas:** Ángel David Gutiérrez Ladino. **Alcance:** API en Docker del estudiante y pruebas automatizadas del middleware.

> Evidencia recibida como texto de terminal compartido por el estudiante. No equivale a acceder a su Docker ni a revisar exhaustivamente su repositorio. Los controles documentados para las rutas nuevas siguen pendientes de probar con sus respectivos controladores CRUD administrativos.

## 1. Integración y diseño observado

- `index.php` incorpora una guarda antes de los CRUD, en rutas `'/api'` o que comienzan con `'/api/'`, excepto `'/api/login'`.
- La guarda invoca `crearMiddlewareAutorizacion()` y `$middleware->autorizar($metodo, $ruta, $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null)`.
- Se valida JWT y se consultan en MariaDB los roles **vigentes** para cada solicitud; no se confían permisos persistentes a un claim de roles antiguo.
- Política: `admin` puede operar recursos de investigación y acceder a rutas administrativas; `consulta` solo puede leer recursos de investigación reconocidos; acciones sin permiso se rechazan. Rutas nuevas/desconocidas no reciben permisos implícitos de consulta.

## 2. Evidencia automatizada

Comandos reportados:

```powershell
docker compose up -d --build
docker compose exec api-investigacion php -l /app/index.php
docker compose exec api-investigacion php -l /app/servicios/ensamblador.php
docker compose exec api-investigacion php /app/pruebas/prueba_middleware_autorizacion.php
```

**Resultado:** Docker construyó e inició los servicios (`mariadb` saludable); ambos archivos pasaron `php -l`; **28/28 pruebas de autorización OK**.

Casos incluidos en esas 28 pruebas: sin Authorization, Bearer vacío, Basic, cabecera malformada y token alterado (`401`); permisos de admin en v1/v2 y rutas de administración; lectura de v1/v2 permitida a consulta; escritura y administración denegada a consulta (`403`); bloqueo de rutas desconocidas/HEAD; multirrol admin; retiro de rol admin sin conservar privilegio; usuario sin roles; rol desconocido; usuario desactivado; cambio de username y desacuerdo de identidad; token imposible de interpretar.

**Límite:** la suite comprueba la lógica de autorización; no demuestra por sí sola el estado final de los 16 CRUD por HTTP ni la ausencia de efectos en BD de todas las escrituras rechazadas.

## 3. Evidencia HTTP real — control positivo y negativo

| Caso | Solicitud ejecutada | Respuesta observada |
|---|---|---|
| Sin token | `GET http://localhost:8111/api/area_conocimiento` con `curl.exe -i --max-time 10`, **sin** cabecera Authorization | **HTTP 401 Unauthorized**, `WWW-Authenticate: Bearer realm="investigacion"`; JSON `estado: 401`, mensaje de autenticación requerida |
| Con token válido | Primero `POST /api/login` con `ADMIN`; luego `GET /api/area_conocimiento` con `-Headers @{ Authorization = "Bearer $token" }` | **HTTP 200** |

En intentos previos, algunos bloques copiados omitían `-Headers` y dieron resultados no concluyentes; también hubo una prueba con Docker apagado y valor `0` producido por PowerShell (no es código HTTP). Las verificaciones **definitivas**, con Docker activo y cabecera explícita donde corresponde, son `401` sin token y `200` con token.

## 4. Pendientes antes del cierre

- Crear un usuario con rol `consulta` en el flujo administrativo, y ejecutar **por HTTP real** GET permitido y POST/PUT/PATCH/DELETE denegados `403`, verificando que la BD no cambie.
- Ejecutar matriz HTTP de todos los 16 recursos con sus métodos particulares; conservar `405` de asociaciones V2 para admin.
- Implementar y probar rutas de `usuario`, `rol`, `rol_usuario` (el CRUD `rol` corresponde a Miguel Angel Lotero Alvarez).
- Verificar secretos en Git, token vencido por HTTP y fallo seguro cuando MariaDB esté inaccesible.
- Integrar frontend PHP con sesión y Bearer, logout, y regresión v1/v2 completa.
- Cierre documental humano, Pull Requests y tag `v3`.

**Conclusión:** middleware **funcional en pruebas automatizadas y en el flujo HTTP `area_conocimiento`**. No declarar completa la matriz de permisos end-to-end ni toda la versión v3.
