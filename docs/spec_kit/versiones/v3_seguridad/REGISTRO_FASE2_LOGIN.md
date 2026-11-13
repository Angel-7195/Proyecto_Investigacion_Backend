# Registro de ejecución — Fase 2: login y JWT (v3)

**Fecha de documentación:** 12-11-2026. **Responsable de las pruebas comunicadas:** Ángel David Gutiérrez Ladino. **Alcance:** backend PHP + entorno Docker/MariaDB del estudiante.

> **Procedencia y limitación:** evidencias tomadas de la salida de PowerShell que compartió el estudiante. No se accedió directamente a su computador ni se inspeccionó la rama actual completa. La prueba automatizada se ejecutó dentro del contenedor y el login real mediante HTTP. No incluir contraseñas, secretos, tokens completos ni hashes en el repositorio.

## 1. Comportamiento implementado y observado

- Ruta pública `POST /api/login`; recibe `username` y `password` en JSON.
- `ServicioAutenticacion` comprueba credenciales con `password_verify()`, usuario activo y al menos un rol activo.
- `JwtHS256` emite HS256 con `iss`, `sub`, `username`, `iat`, `nbf`, `exp`, y duración `exp - iat = 3600` segundos (60 minutos).
- El login devuelve `datos.token`, `datos.tipo`, `datos.expira_en`, `datos.usuario.username` y `datos.usuario.roles`, sin exponer el hash de contraseña.
- El secreto `JWT_SECRET` se proporcionó mediante el entorno del contenedor; `strlen((string) getenv('JWT_SECRET'))` devolvió `64` (comprobación de **longitud**, no auditoría de secretos en Git).

## 2. Evidencia de pruebas

| Comprobación | Resultado comunicado | Conclusión limitada |
|---|---|---|
| `php -l` de `index.php`, `ensamblador.php`, `ControladorLogin.php`, `ServicioAutenticacion.php` y `JwtHS256.php` | Sin errores de sintaxis | Los archivos analizados son sintácticamente válidos en ese entorno |
| `docker compose exec api-investigacion php /app/pruebas/prueba_login_jwt.php` | **15/15 OK** | Las pruebas automatizadas incluidas en el proyecto pasan |
| Login real de usuario `ADMIN` con credenciales válidas | `estado: 200`, `expira_en: 3600`, `username: ADMIN`, `roles: admin` | Autenticación HTTP satisfactoria contra MariaDB local |
| Primer intento de login | HTTP `401` | La API rechazó un intento, pero no quedó documentada su causa exacta |
| Token alterado, firma ajena, algoritmo `none`, vencimiento | Casos OK en el archivo de pruebas | Comprobación automatizada; no se ha enviado cada uno como solicitud HTTP independiente |
| Usuario sin roles, datos incompletos o tipos incorrectos | Casos OK en archivo de pruebas | Comprobación automatizada |

Los 15 casos reportados fueron: login correcto y `3600` s; rol `admin`; ausencia de hash en respuesta; firma/identidad JWT; `exp = iat + 3600`; contraseña incorrecta; usuario inexistente; cuerpo incompleto; tipos incorrectos; usuario sin roles activos; token alterado; firma de otra clave; algoritmo `none`; secreto demasiado corto; token expirado con firma correcta.

## 3. Reproducción sin mostrar secretos

```powershell
docker compose up -d --build
docker compose exec api-investigacion php -l /app/index.php
docker compose exec api-investigacion php -l /app/servicios/ensamblador.php
docker compose exec api-investigacion php /app/pruebas/prueba_login_jwt.php
# Verificar solo la longitud, nunca imprimir el secreto:
docker compose exec api-investigacion php -r "echo strlen((string)getenv('JWT_SECRET')), PHP_EOL;"
```

Para repetir el login real, usar el bloque PowerShell interactivo documentado en `7_quickstart.md`, sin imprimir el JWT. **No copiar el secreto ni credenciales de producción a comandos o archivos versionados**.

## 4. Pendientes relacionados

- Auditoría de `.gitignore`, `.env.example` y ausencia de secretos en Git.
- Pruebas de integración HTTP adicionales ante token expirado, alterado y usuario desactivado (además de las unitarias).
- CRUD de `usuario` y cambio de contraseña, frontend con sesión/logout y regresión v1/v2.
- Firma de compuertas SDD y cierre de versión por el equipo.

**Conclusión:** login y emisión/verificación básica JWT **funcionales con evidencia automatizada y HTTP real**; no equivale a la aprobación de toda la seguridad de v3.
