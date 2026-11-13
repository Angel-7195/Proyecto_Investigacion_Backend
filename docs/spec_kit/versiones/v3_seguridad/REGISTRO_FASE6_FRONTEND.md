# Registro de evidencia — Fase 6: frontend de seguridad V3

- **Proyecto:** Investigación — V3, autenticación y autorización.
- **Responsable de esta implementación:** Ángel David Gutiérrez Ladino.
- **Fecha de las comprobaciones:** 13 de noviembre de 2026.
- **Repositorios:** frontend `feature/angel-frontend-v3`; backend `feature/angel-investigacion-v3`.
- **Entorno comunicado:** PHP 8.3, Docker, MariaDB, frontend `http://localhost:8110`, API `http://localhost:8111` y phpMyAdmin `http://localhost:8105` (confirmar puertos con `docker compose ps` en otras instalaciones).

> **Origen y alcance de la evidencia.** Este registro combina revisión de los archivos PHP compartidos y resultados de terminal, navegador y phpMyAdmin aportados por el estudiante. No es una ejecución independiente en su computador. Las capturas confirman los comportamientos descritos, pero no demuestran por sí solas todos los códigos HTTP de la API. No se incluyen contraseñas, tokens, hashes ni secretos. **No implica aprobación de la V3 completa**, ni cierre de las compuertas SDD.

## 1. Implementación efectuada

Se conservaron las pantallas y rutas de los **16 recursos** de V1/V2. La ampliación transversal de seguridad del frontend utiliza estos archivos:

| Archivo en `front_php/` | Función en V3 |
|---|---|
| `index.php` | Sesión, login, logout, comprobación web de autorización y registro de las rutas administrativas. |
| `cliente_api.php` | Cliente HTTP hacia la API con `Authorization: Bearer` desde `$_SESSION['jwt']`; sigue utilizando **streams HTTP**, no se declara migración a cURL. |
| `vistas/compartidas/plantilla.php` | Navegación y acciones visibles según sesión y rol. |
| `rutas/rutas_usuario.php` | Listado, alta, edición de perfil (`PUT` o `PATCH`), cambio de contraseña y desactivación lógica. |
| `vistas/usuario/lista.php` y `vistas/usuario/formulario.php` | Formularios, listados, confirmaciones y mensajes de usuarios. |
| `rutas/rutas_rol_usuario.php` | Consulta, asignación y retirada de vínculos usuario–rol. |
| `vistas/rol_usuario/gestion.php` | Formulario de asignación, tabla de vínculos y confirmación de retirada. |

**Límites del trabajo:** el CRUD del catálogo `rol` corresponde a Miguel Ángel Lotero Álvarez. El módulo aquí registrado **no crea ni edita tipos de rol**. En la base local se observaron `admin` con ID 1 y `consulta` con ID 2; el selector del frontend usa **temporalmente estos ID confirmados**. Debe integrarse con el catálogo dinámico cuando el CRUD `rol` esté disponible. Los ID no son una garantía válida para otros volúmenes.

## 2. Rutas web y operaciones de la API

Los formularios HTML envían `POST` al frontend; el servidor frontend traduce la operación al método HTTP correspondiente de la API.

| Ruta web del frontend | Método web | Funcionalidad y API consumida |
|---|---|---|
| `/login` | GET / POST | Mostrar formulario / `POST /api/login`. |
| `/logout` | POST | Destruir sesión PHP y limpiar cookie; **no** existe revocación centralizada del JWT. |
| `/usuarios` | GET | `GET /api/usuario`; solo admin. |
| `/usuarios/nuevo` y `/usuarios/crear` | GET / POST | Formulario / `POST /api/usuario`. |
| `/usuarios/editar?id=N` | GET | `GET /api/usuario/N`, formulario del perfil. |
| `/usuarios/actualizar` con `accion=completo` o `parcial` | POST | `PUT` o `PATCH /api/usuario/N` respectivamente. |
| `/usuarios/editar?id=N&seccion=password` | GET | Formulario de contraseña separado. |
| `/usuarios/actualizar` con `accion=password` | POST | `PATCH /api/usuario/N/password`. |
| `/usuarios/editar?id=N&seccion=eliminar` | GET | Confirmación sin cambiar datos. |
| `/usuarios/actualizar` con `accion=eliminar` | POST | `DELETE /api/usuario/N`, que establece `activo=0`. |
| `/roles-usuarios` | GET | `GET /api/rol_usuario` y usuarios activos. |
| `/roles-usuarios/asignar` | POST | `POST /api/rol_usuario`, con `{usuario_id, rol_id}`. |
| `/roles-usuarios/confirmar-retiro?usuario_id=N&rol_id=R` | GET | Verificación de la pareja por `GET /api/rol_usuario/N/R`, **sin retirarla**. |
| `/roles-usuarios/retirar` | POST | `DELETE /api/rol_usuario/N/R`, eliminación **física del vínculo**, no de las filas `usuario` ni `rol`. |

Los formularios administrativos incorporan validación de campos y tokens CSRF; la confirmación del retiro de rol tiene un CSRF asociado a la pareja usuario–rol. El frontend solicita confirmación adicional antes de asignar `admin`. Se usan `session_start()`, `session_regenerate_id(true)` al autenticarse, cookie `HttpOnly`/`SameSite=Lax` y `Secure` si la conexión es HTTPS. Estas propiedades fueron **revisadas en código**, pero no se efectuó una auditoría independiente de cookies/CSRF en navegador.

## 3. Pruebas observadas desde el frontend

| Caso | Evidencia comunicada | Estado acotado |
|---|---|---|
| Sintaxis y arranque | `php -l` sin errores en `index.php`, `cliente_api.php`, plantilla y dos archivos de roles; `docker compose up -d --build` completado, MariaDB saludable y frontend iniciado. | **Comprobado** en los archivos citados, no es suite total del frontend. |
| Inicio de sesión de ADMIN | Se mostró navegación administrativa y se gestionaron usuarios/roles. | **Comprobado** en navegador. |
| Alta de usuarios | Se crearon cuentas de prueba, entre ellas `mario` (ID 5), `mariana` (ID 6) y `prueba_delete_v3` (ID 7). | **Comprobado** en la instalación local. |
| Contraseña y perfil | Se cambió una contraseña de prueba y se logró iniciar sesión después de asignar un rol; también se modificó el nombre completo por edición parcial (`PATCH`) de una cuenta de prueba. | **Comprobado** en el flujo comunicado; no guardar la contraseña en la documentación. |
| Borrado lógico de usuario | Se desactivó `prueba_delete_v3`; desapareció del listado activo y SQL confirmó la fila con `activo=0`. | **Comprobado** en frontend y MariaDB; `DELETE` no borró físicamente la cuenta. |
| Registro sin rol | Mariana inicialmente no pudo iniciar sesión; su cuenta existía, pero no había vínculo `rol_usuario`. | **Comprobado**. El alta de usuario **no** asigna roles automáticamente. |
| Asignación de consulta | ADMIN vinculó a Mariana (usuario 6) con `consulta` (rol 2); apareció en el listado. | **Comprobado**. |
| Duplicado de rol | Al intentar añadir el mismo rol a Mariana, la interfaz informó que ya estaba asignado. | **Comprobado**; el backend ya tenía evidencia HTTP `409` de este caso. |
| Restricción de consulta | Mariana accedió a `Áreas de conocimiento` con **218 registros** sin acciones CRUD, y la entrada directa a `/roles-usuarios` mostró «Acceso denegado». | **Comprobado** para las pantallas observadas; no valida por sí solo los 16 recursos. |
| Retiro y reasignación | Se retiró `consulta`, se volvió a asignar y se retiró nuevamente; `SELECT * FROM rol_usuario WHERE usuario_id = 6` no devolvió filas. | **Comprobado** por interfaz y SQL después del retiro. |
| Login sin rol | Sin roles, un nuevo inicio de sesión de Mariana fue rechazado con el mensaje genérico de credenciales. | **Comprobado**; no se registró aquí captura del status HTTP exacto. |
| Revocación con sesión abierta | En ventana independiente, Mariana mantuvo su sesión mientras ADMIN retiraba el rol; al actualizar `Áreas de conocimiento`, el listado dejó de cargar y apareció «No tiene permiso para realizar esta acción». | **Comprobado visualmente**. Coincide con la evidencia de API `403` con JWT anterior recogida en fase 5. |
| Recuperación tras reasignar | ADMIN asignó otra vez `consulta`; Mariana cerró e inició sesión de nuevo y pudo consultar los **218** registros. | **Comprobado**. |
| Protección del último administrador | El listado mostró «Último admin protegido» para `ADMIN`, sin botón de retiro. SQL con JOIN mostró **solo un admin activo**, `ADMIN` ID 1. | **Interfaz y conteo SQL comprobados**. No se ejecutó un DELETE real contra ese administrador; falta prueba de integración en BD descartable. |

SQL usado en comprobaciones **de solo lectura**:

```sql
SELECT * FROM rol_usuario WHERE usuario_id = 6;

SELECT u.id, u.username, u.activo, r.id AS rol_id, r.nombre AS rol
FROM usuario AS u
JOIN rol_usuario AS ru ON u.id = ru.usuario_id
JOIN rol AS r ON ru.rol_id = r.id
WHERE r.id = 1 AND u.activo = 1;
```

El primer SELECT estuvo vacío **durante la prueba de retiro**. El segundo devolvió únicamente a `ADMIN` con rol ID 1, cuyo nombre era `admin` en la base local. Después se restauró la relación de Mariana con `consulta`. Los IDs 1/2/5/6/7 pertenecen solamente a esta instancia de prueba.

## 4. Aspectos pendientes y criterios que no se dan por aprobados

- [ ] CRUD independiente del catálogo `rol` e integración de su lista dinámica con el selector: responsable Miguel; mantener los permisos admin solo para el nombre autorizado por la API.
- [ ] Prueba automatizada y HTTP de la protección del **último administrador** en una **base descartable**, sin arriesgar la cuenta real.
- [ ] Matriz completa de permisos y regresión HTTP de los **16 recursos V1/V2**, incluidos métodos no admitidos y sus claves compuestas.
- [ ] Probar específicamente frontend con la **API apagada**; revisar errores 401/403/503 en todos los formularios, además de casos CSRF malformados.
- [ ] Verificación final de logout, cookie y ausencia del JWT en peticiones posteriores, así como revisión integral de separación del frontend (sin PDO ni secretos de BD).
- [ ] Acordar C11-V2: el cliente compartido continúa usando `stream_context_create()`/`file_get_contents()`, mientras la metodología del curso menciona cURL.
- [ ] Pruebas de entrada inválida, FK, segundo DELETE y permisos con todos los verbos de los recursos administrativos; instalación limpia e idempotencia del bootstrap.
- [ ] Revisión humana de compuertas SDD, commits/PR, integración, regresión y `tag v3` en `main`.

**Observación de experiencia de usuario:** si se revoca un rol durante la sesión, la barra de navegación puede seguir mostrando el rol que estaba guardado en `$_SESSION` hasta un nuevo inicio de sesión; sin embargo, la API deniega los datos protegidos porque vuelve a comprobar los permisos vigentes. Es una posible mejora futura de interfaz, no evidencia de bypass de la API.

## 5. Trazabilidad

| Requisito / criterio de `2_spec.md` | Evidencia de esta fase | Restricción |
|---|---|---|
| RF-v3-01/02/09, CA1/10 | Login real, sesión PHP y Bearer revisados en código | Pruebas exhaustivas de sesión/cookies pendientes. |
| RF-v3-04/05, CA4–6 | Menú `consulta`, lectura permitida y ruta admin denegada | Muestra representativa, no los 16 módulos. |
| RF-v3-06, CA7/8 | Alta, perfil PUT/PATCH, contraseña y borrado lógico de usuarios | Reglas globales/último admin pendientes. |
| RF-v3-08, CA7 | Asignar, duplicado, retirar y restaurar `rol_usuario` | Catálogo `rol` y casos negativos pendientes. |
| RF-v3-03/04, CA3/6 | Revocación de acceso con sesión JWT previamente emitida | Código HTTP exacto no capturado en esta prueba web; fase 5 sí tiene API `403`. |

**Conclusión acotada:** el flujo principal del frontend desarrollado por Ángel está implementado y probado manualmente en el entorno local, con controles relevantes de seguridad y persistencia. **La aceptación global de V3 permanece abierta** hasta integración de `rol`, regresión completa y revisión del equipo.
