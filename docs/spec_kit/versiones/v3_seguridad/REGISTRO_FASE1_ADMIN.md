# Registro de ejecución — Fase 1 de seguridad v3

**Fecha del registro:** 10 de noviembre de 2026.  
**Responsable de ejecución:** Ángel David Gutiérrez Ladino.  
**Entorno:** backend local del proyecto de Investigación, rama `feature/angel-investigacion-v3`, Windows PowerShell y Docker Compose / MariaDB 11.  
**Fuente de evidencia:** salidas de PowerShell y dos capturas de phpMyAdmin compartidas por el estudiante; no se accedió directamente a su equipo ni a su base de datos.

## 1. Implementación realizada por el estudiante

- `db/init.sql`: añadido bloque `INSERT INTO rol (nombre, descripcion, activo)` con los nombres `admin` y `consulta`. El `git --no-pager diff -- db/init.sql` compartido mostraba el bloque completo y sintaxis SQL coherente. **Esto no acredita aún una inicialización desde un volumen vacío.**
- `api_investigacion/repositorios/Repositorios_InicializacionSeguridad/IRepositorioInicializacionSeguridad.php`: interfaz del proceso administrativo.
- `api_investigacion/repositorios/Repositorios_InicializacionSeguridad/RepositorioInicializacionSeguridadMariaDB.php`: sentencias SQL preparadas y transacción; si existen roles con el mismo nombre, su inserción no duplica las claves UNIQUE. Lee los IDs reales, verifica estado y no promueve automáticamente un usuario con identificadores coincidentes.
- `api_investigacion/servicios/Servicios_InicializacionSeguridad/ServicioInicializacionSeguridad.php`: valida username (3–100), email válido (máximo 150) y clave inicial de mínimo 12 caracteres; produce `password_hash(PASSWORD_DEFAULT)`.
- `api_investigacion/scripts/inicializar_admin.php`: solo CLI; lee variables de entorno y solicita al ensamblador el servicio. El código no incluye contraseñas fijas ni crea ruta HTTP de registro.
- `api_investigacion/servicios/ensamblador.php`: ampliado localmente para ensamblar el servicio. No se cambiaron los 16 controladores anteriores por esta funcionalidad.

## 2. Evidencia observada

| Caso | Resultado comunicado | Alcance de la evidencia |
|---|---|---|
| Comprobación `php -l` de los cuatro archivos PHP nuevos | `No syntax errors detected` en los cuatro | **Aprobado en sintaxis**, no en comportamiento integral |
| Primera ejecución manual con `docker compose run --rm --no-deps ... php /app/scripts/inicializar_admin.php` | `Administrador inicial creado y rol asignado correctamente.` | **Aprobada la ejecución feliz inicial** en volumen existente |
| Consulta `SELECT id,nombre,activo FROM rol` | Se ven dos roles: `admin` y `consulta`, `activo=1` | **Comprobada presencia de los roles** en esa BD |
| Consulta JOIN entre `usuario`, `rol_usuario`, `rol` | Cuenta `ADMIN` aparece activa con rol `admin` | **Comprobada la asociación** en esa BD |
| Comprobación independiente `function_exists('crearServicioInicializacionSeguridad')` | El comando no imprimió `OK` ni `FALTA` en la salida mostrada | **No concluyente**; la ejecución posterior del script sí demuestra que el ensamblaje funciona para ese flujo |
| `password_verify` sobre clave real | No realizada | **Pendiente**; no debe mostrar la clave ni el hash |
| Segunda ejecución con cuenta ya existente | No realizada | **Pendiente** (idempotencia aún no verificada en ejecución) |
| Volumen vacío / instalación en otro PC | No realizada | **Pendiente**; git no transfiere los registros MariaDB del estudiante |
| `POST /api/login`, JWT, roles por HTTP, logout | No implementados al cerrar este registro | **Pendiente** |
| Regresión v1/v2 con autenticación | No realizada | **Pendiente** |

No incluir contraseñas, hashes, tokens JWT ni correo real del administrador en capturas destinadas al repositorio.

## 3. Persistencia y despliegue

El `docker-compose.yml` real define `volumes: mariadbdata:` y monta `db/init.sql` bajo `/docker-entrypoint-initdb.d/init.sql:ro`. El usuario creado persiste **en el volumen Docker de la instalación local**, no como archivo SQL del repositorio; se conserva normalmente con `docker compose stop/start`, `docker compose down` y `docker compose up -d --build` cuando se reutiliza el mismo volumen. `docker compose down -v` **borra el volumen** y puede eliminar datos.

Para el profesor u otro integrante, `git clone` incluye `init.sql` y los archivos PHP **una vez se haga commit, push y merge correspondiente**, pero no lleva los registros existentes de `mariadbdata`. Tras preparar `.env` y levantar MariaDB, se ejecuta el bootstrap por CLI con **credenciales nuevas elegidas por el operador**, tal como se documenta en [7_quickstart.md](7_quickstart.md), apartado 0.1. La ejecución no es automática actualmente.

## 4. Pruebas mínimas que aún deben programarse y ejecutarse

1. Repetir bootstrap sin duplicar cuenta o modificar contraseña; medir conteos antes y después.
2. Probar base limpia en entorno aislado, sin eliminar el volumen actual.
3. Probar error sin variables, usuario/email duplicados, admin inactivo y roles inactivos.
4. Validar el hash por una operación segura que no lo imprima; el login real lo comprobará mediante `password_verify`.
5. Confirmar que secretos temporales no quedaron en archivos versionados y el frontend no recibe configuración PDO.
6. Validar con el compañero las compuertas SDD y la correspondencia de `5_data_model.md` con `SHOW CREATE TABLE` del volumen real.

**Conclusión de fase:** alta manual básica **funcional**, pero la fase 1 **no se declara cerrada** hasta completar idempotencia, instalación limpia, seguridad de errores, documentación de configuración y verificaciones del equipo. El token JWT acordado para la fase siguiente durará **60 minutos (3600 segundos)**; esto es una decisión de alcance, no una prueba de código.

## Actualización posterior — 12-11-2026

Después de cerrar el primer registro, se implementó y probó el login: la API autenticó al administrador real mediante `password_verify()`, emitió JWT de 3600 segundos y devolvió HTTP 200; se ejecutaron **15/15** pruebas automatizadas (véase `REGISTRO_FASE2_LOGIN.md`). Posteriormente, el middleware pasó **28/28** pruebas automatizadas y respondió HTTP 401 sin JWT / HTTP 200 con JWT sobre `area_conocimiento` (véase `REGISTRO_FASE3_MIDDLEWARE.md`). Las filas de este registro que indican «login pendiente» son **históricas del cierre de fase 1**, no describen el estado actual de v3. Siguen pendientes las pruebas de idempotencia e instalación limpia del primer administrador.
