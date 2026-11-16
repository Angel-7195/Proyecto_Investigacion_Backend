# Registro complementario V3 — bootstrap automático y login de demostración

**Fecha del registro:** 16 de noviembre de 2026 (pruebas compartidas el 15 de noviembre, hora de Colombia).
**Responsable de las ejecuciones:** Ángel David Gutiérrez Ladino.
**Entorno:** Windows PowerShell, Docker Compose, PHP 8.3 y MariaDB 11; repositorios separados de backend y frontend.
**Alcance probatorio:** salidas de terminal, SQL de solo lectura y capturas del navegador comunicadas por el estudiante. Este documento **no significa que se haya ejecutado nuevamente el código en una instalación independiente**, ni certifica la V3 completa.

## 1. Qué cambió respecto al registro inicial de la fase 1

El archivo PHP `api_investigacion/scripts/inicializar_admin.php` continúa siendo una herramienta **exclusiva de línea de comandos**, sin endpoint web. La diferencia es su orquestación:

1. MariaDB se inicia y Docker comprueba su estado saludable.
2. El servicio `inicializar-admin` ejecuta el CLI con los valores `ADMIN_INICIAL_USERNAME`, `ADMIN_INICIAL_EMAIL` y `ADMIN_INICIAL_PASSWORD` de la configuración privada del backend.
3. El servicio termina con éxito (`Exited (0)`) si crea el administrador o detecta uno válido preexistente.
4. La API arranca cuando la inicialización ha finalizado correctamente; el frontend la consume por HTTP.

`db/init.sql` siembra los roles públicos `admin` y `consulta` en una base nueva. El CLI utiliza el servicio/repositorio de inicialización para generar un hash con `password_hash(PASSWORD_DEFAULT)` y vincular al usuario con `rol_usuario`, sin grabar ninguna contraseña fija en SQL.

**La cuenta no se crea al clonar Git, sino cuando se prepara la base y se ejecuta el proceso de inicialización.** La instalación normal en Docker ahora automatiza ese paso; subir archivos a AlwaysData, que no usa este `docker-compose.yml`, **no lo ejecutará automáticamente**.

## 2. Evidencia — volumen habitual existente

El estudiante ejecutó:

```powershell
docker compose up -d --build
docker compose logs inicializar-admin
docker compose ps -a
```

Observaciones reportadas:

- MariaDB: `Healthy`; API: `Started`; frontend: `Started`; phpMyAdmin: `Running`.
- Servicio `inicializar-admin`: `Exited (0)`.
- Mensaje: `Ya existe un administrador activo. No se duplicó la cuenta ni se cambió la contraseña.`
- Consulta SQL de `usuario` + `rol_usuario` + `rol` mostró `ADMIN`, `activo=1` y rol `admin`.
- El estudiante confirmó inicio de sesión en el frontend de la base habitual.

Esto respalda la conservación de la cuenta en el **volumen usado**. No demuestra que el operador pueda omitir las variables necesarias en el primer arranque ni que cualquier estado corrupto se repare solo.

## 3. Evidencia — base nueva y repetición en proyecto aislado

Se utilizó **otro nombre de proyecto Compose**, evitando reutilizar el volumen habitual:

```powershell
docker volume ls --filter "label=com.docker.compose.project=investigacion_admin_prueba"
docker compose -f docker-compose.prueba-admin.yml -p investigacion_admin_prueba config --quiet
docker compose -f docker-compose.prueba-admin.yml -p investigacion_admin_prueba up -d --build
docker compose -f docker-compose.prueba-admin.yml -p investigacion_admin_prueba logs inicializar-admin
```

El listado inicial de volúmenes filtrados no devolvió resultados. Compose creó la red aislada y el volumen `investigacion_admin_prueba_datos_admin_prueba`; MariaDB quedó saludable. El log del inicializador indicó:

```text
Administrador inicial creado y rol asignado correctamente.
```

Se repitió el proceso sobre **esa misma base**:

```powershell
docker compose -f docker-compose.prueba-admin.yml -p investigacion_admin_prueba run --rm --no-deps inicializar-admin
```

La respuesta fue:

```text
Ya existe un administrador activo. No se duplicó la cuenta ni se cambió la contraseña.
```

Dentro de MariaDB de prueba se ejecutó SQL **sin revelar el hash**:

```sql
SELECT u.id, u.username, u.activo, r.nombre AS rol,
       LENGTH(u.password) AS longitud_hash
FROM usuario AS u
INNER JOIN rol_usuario AS ru ON u.id = ru.usuario_id
INNER JOIN rol AS r ON ru.rol_id = r.id
WHERE u.username = 'ADMIN';

SELECT COUNT(*) AS total_usuarios FROM usuario;
```

Resultados observados: `id=1`, `username=ADMIN`, `activo=1`, `rol=admin`, `longitud_hash=60`; total de usuarios `1`. La longitud es **coherente con** el hash empleado por PHP, pero no reemplaza una validación `password_verify()` ni un login HTTP contra esa instancia aislada.

Finalmente se ejecutó:

```powershell
docker compose -f docker-compose.prueba-admin.yml -p investigacion_admin_prueba down
docker compose ps
```

Los contenedores y la red de prueba se retiraron. **No se usó `down -v`**, por lo que el volumen aislado no fue borrado. El `docker compose ps` normal mostró API, frontend, MariaDB y phpMyAdmin en funcionamiento. No se destruyó ni reinicializó la base habitual.

## 4. Tarjeta opcional de acceso de demostración

El archivo `front_php/vistas/autenticacion/login.php` muestra una tarjeta de «Acceso de demostración» si, y solo si, se cumplen estas condiciones:

- `LOGIN_DEMO_ENABLED=1`.
- `LOGIN_DEMO_ADMIN_USERNAME` es un texto no vacío.
- `LOGIN_DEMO_ADMIN_PASSWORD` es una cadena no vacía.

`docker-compose.demo.yml` propaga los valores configurados **en el `.env` privado** al contenedor `front-php` cuando se activa el archivo override. El aviso se limita a **mostrar** esos datos; el formulario utiliza el mismo login HTTP normal y el backend sigue validando `usuario`, hash y roles.

Comprobaciones comunicadas:

```powershell
docker compose -f docker-compose.yml -f docker-compose.demo.yml up -d --build front-php
docker compose -f docker-compose.yml -f docker-compose.demo.yml exec front-php php -l /app/vistas/autenticacion/login.php
```

- El archivo PHP informó `No syntax errors detected`.
- La tarjeta apareció en el navegador.
- Una prueba con un **username inexistente en MariaDB**, aunque estuviera escrito en la tarjeta, produjo `Usuario o contraseña incorrectos`.
- Posteriormente, al usar credenciales de la cuenta real existente, el estudiante inició sesión y vio el panel de investigación y la navegación administrativa.

**Conclusión técnica:** `LOGIN_DEMO_ADMIN_*` **no crea usuarios ni cambia contraseñas**. Para un equipo nuevo, primero debe existir el administrador de bootstrap y después se muestran **las mismas credenciales** en la tarjeta, únicamente si el operador decide habilitarla. No guardar contraseñas ni tokens literales en este documento o repositorio.

## 5. Riesgos y límites para publicación académica

La cuenta de demostración con rol `admin` puede crear, cambiar o desactivar datos. **Cualquier persona que vea usuario y contraseña tendrá esos permisos.** Es una elección explícita de uso académico, no una configuración segura para manejar datos reales.

- Usar **base separada, desechable y con datos ficticios**; nunca reutilizar una cuenta administrativa personal o productiva.
- Guardar la configuración en `.env` excluido del control de versiones, aunque sus valores puedan mostrarse deliberadamente en la pantalla demo.
- En desarrollo normal, fijar `LOGIN_DEMO_ENABLED=0`. Comprobar con navegador que la tarjeta se oculta.
- No afirmar que AlwaysData publica automáticamente los cambios de Git ni que ejecuta el inicializador Docker. Allí se tendrá que configurar PHP/MariaDB, ejecutar el CLI de bootstrap con los secretos del hosting y planear un proceso específico de publicación.
- Coordinar con Miguel la integración del CRUD de catálogo `rol` antes de declarar lista la V3 o decidir publicarla.

## 6. Pendientes documentados de esta ampliación

| Verificación | Estado | Próxima acción segura |
|---|---|---|
| Arranque automático normal de Docker | **Probado** | Registrar commit y revisar configuración final |
| Creación en base nueva **aislada** | **Probado** | Login HTTP contra esa instancia, si se repite la prueba |
| Segunda ejecución sin duplicado | **Probado** | Comparar hash antes/después sin imprimirlo |
| Conservación de cuenta en volumen habitual | **Probado** | Comprobar datos previos adicionales de V2 |
| Falta de variables, rol inactivo o username/email ambiguos | **Pendiente** | Ejecutar en otra BD descartable |
| Ausencia de secretos en Git / logs | **Pendiente** | `git ls-files .env`; inspección humana de diff/logs |
| Tarjeta demo visible y sintaxis PHP | **Probado** | Probar modo deshabilitado y variables incompletas |
| Login web con cuenta administrativa existente | **Probado en BD habitual** | No extrapolar a hosting ni a base aislada |
| CRUD catálogo `rol` de Miguel | **Pendiente de integración y pruebas** | Coordinar con compañero |
| Matriz 16 recursos, API caída, CSRF, cookies, último admin | **Pendiente** | Seguir `7_quickstart.md` y `9_checklist.md` |
| Despliegue en AlwaysData | **No realizado** | Esperar integración y diseñar bootstrap sin Docker |

**No se da por cerrado el Spec Kit V3 ni se firman las compuertas SDD** por estas pruebas adicionales.
