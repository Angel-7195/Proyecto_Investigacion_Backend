# Especificación — v3: Autenticación, sesiones y roles (Investigación)

> **Actualización 19-11-2026 — catálogo fijo de roles:** el recurso `rol` dejó de ser un CRUD y quedó definido como catálogo de solo lectura. Los únicos perfiles de acceso admitidos por esta versión son `admin` y `consulta`; sus IDs los genera MariaDB. La API permite únicamente `GET /api/rol` y `GET /api/rol/{id}` para administradores; `POST`, `PUT`, `PATCH` y `DELETE` responden `405` con `Allow: GET`. La pantalla de roles muestra los dos registros y no ofrece acciones de creación, edición ni retiro. Se comprobaron pruebas aisladas, HTTP real con token admin y visualización del frontend. `rol_usuario` conserva su contrato y no se modificó. La lectura de `/api/rol` con una cuenta `consulta` no se volvió a probar en esta actualización; la política continúa siendo `403` por tratarse de un recurso administrativo. Evidencia: [REGISTRO_FASE7_CATALOGO_ROL_20261119.md](REGISTRO_FASE7_CATALOGO_ROL_20261119.md). Esta actualización no declara cerrada la v3.

> **Actualización 16-11-2026 — bootstrap y acceso de demostración:** el arranque local normal con Docker Compose ya ejecuta `inicializar-admin` después de que MariaDB está saludable; en la base habitual reconoció el administrador existente y terminó con código 0. En una **base nueva aislada** creó `ADMIN`, lo vinculó al rol `admin`, y una segunda ejecución no duplicó cuentas ni cambió la contraseña. SQL mostró **una** cuenta, activa, con rol `admin` y un hash de longitud 60. Además, el frontend incorporó una tarjeta opcional de credenciales de demostración, cuya visibilidad se controla por entorno; el estudiante comprobó sintaxis PHP y acceso web con credenciales **realmente existentes**. Ver [REGISTRO_BOOTSTRAP_Y_LOGIN_DEMO_20261116.md](REGISTRO_BOOTSTRAP_Y_LOGIN_DEMO_20261116.md). Quedan pendientes pruebas negativas, catálogo `rol`, regresión completa y despliegue fuera de Docker.

> **Seguimiento de implementación — 13-11-2026:** los flujos principales del frontend de autenticación, `usuario` y `rol_usuario` ya fueron probados manualmente; se verificó denegación del acceso de `consulta`, retirada del único rol durante una sesión abierta y recuperación tras reasignarlo. Ver [REGISTRO_FASE6_FRONTEND.md](REGISTRO_FASE6_FRONTEND.md). La **especificación y sus criterios no se consideran aprobados automáticamente**: faltan catálogo `rol`, regresión general, pruebas de API apagada y cierre de compuertas.

> **Actualización de evidencia — 12-11-2026, fase 5:** gestión principal de `rol_usuario` comprobada por **15/15** pruebas aisladas y HTTP real: asignación `201`, ficha `200`, duplicado `409`, retiro `200`, ficha posterior `404`. Un usuario `consulta` real obtuvo GET V1 `200`, POST V1 `403` y GET administrativo `403`; tras retirar el rol, **el mismo JWT** pasó a GET `403`. SQL confirmó usuario sin rol aún activo y `ADMIN` con `admin`. La protección del último administrador **queda pendiente de prueba final solo en base descartable**, sin arriesgar el volumen local. Ver [REGISTRO_FASE5_ROL_USUARIO.md](REGISTRO_FASE5_ROL_USUARIO.md).

> **Estado (revisión con V2, SQL y v3 del profesor): BORRADOR PARA COMPUERTA 1.** El usuario informa V2 terminada y confirmó el merge del backend en `main` mediante PR #15, commit `9bcf84b`; los documentos V2 fechados 29-10-2026 seguían registrando pendientes de evidencia de regresión, aprobación y tag. No suponer que tales pendientes siguen abiertos ni que ya se cerraron: verificarlos. Además del `.rar` de V2, ahora se recibió `init(4).sql` con el DDL de seguridad; **sigue sin verificarse la estructura DDL de la base ya desplegada y el código completo actualizado del frontend; se recibieron archivos parciales de login/enrutamiento y salidas de pruebas del backend**.
>
> **Fuentes:** `0_METODOLOGIA.md`, `modulo_investigacion.md` y `2_spec.md`–`9_checklist.md` + `GUIA_IA2.md` del paquete V2 (29-10-2026). La constitución **vigente del equipo** requiere cotejo; el DDL fuente de seguridad ya fue cotejado con el SQL compartido, sujeto a verificar la base en ejecución. Véase `COMPARACION_V2_V3.md`. El mapa antiguo del ejemplo no fija el alcance del proyecto de aula.

> **Evidencia de implementación al 12-11-2026 (comunicada por el estudiante):** fase 1: alta manual de roles y administrador local; fase 2: **15/15** pruebas de login/JWT, login HTTP `200` y vigencia `3600` s; fase 3: **28/28** pruebas de autorización y GET `/api/area_conocimiento` con `401` sin Bearer / `200` con Bearer válido. **Fase 4 (`usuario`): 28/28 pruebas aisladas y operaciones HTTP reales** `GET` listado/por ID (`200`), `POST` (`201`), `PUT` (`200`), `PATCH` de perfil (`200`), `PATCH` de contraseña (`200`), `DELETE` lógico (`200`), `GET` posterior (`404`), duplicados de `username` y `email` (`409`), y GET sin JWT (`401`). En MariaDB se observó `activo=0` tras DELETE, hashes con prefijo `$2y$` y longitud 60, y un administrador activo. **Quedan pendientes** la prueba real de la barrera del último administrador (solo probada con repositorio falso), una verificación directa `password_verify()` del cambio aleatorio de contraseña del usuario de prueba, `rol`, `rol_usuario`, frontend, instalación limpia/idempotencia, pruebas completas con `consulta` y regresión de los 16 recursos. Véanse [REGISTRO_FASE1_ADMIN.md](REGISTRO_FASE1_ADMIN.md), [REGISTRO_FASE2_LOGIN.md](REGISTRO_FASE2_LOGIN.md), [REGISTRO_FASE3_MIDDLEWARE.md](REGISTRO_FASE3_MIDDLEWARE.md) y [REGISTRO_FASE4_USUARIO.md](REGISTRO_FASE4_USUARIO.md).

**Guía de lectura:** este archivo fija el **QUÉ**. Las rutas y respuestas se detallan en [6_contracts.md](6_contracts.md); clases, fases y tecnologías en [3_plan.md](3_plan.md). Lo que exige el profesor se distingue de las decisiones acordadas por el equipo y de las propuestas aún sujetas a clarificación.

La v3 añade **autenticación y autorización** a los CRUD existentes del módulo de Investigación y habilita la gestión de las tres tablas de seguridad ya previstas en la base de datos, con operaciones específicas para cada recurso:

- `POST /api/login`: valida credenciales y entrega un JWT firmado mediante HS256.
- Comprobación de identidad y permisos **antes de ejecutar el controlador del recurso** desde el front controller de la API.
- **Sesión PHP en el frontend**: conservar el JWT del lado del servidor y enviarlo a la API mediante `Authorization: Bearer <token>`.
- **Menú por roles**: ocultar las acciones no autorizadas sin confiar en el menú como única protección.
- **Logout**: cerrar la sesión del frontend y dejar de enviar el JWT.
- CRUD de `usuario` y gestión de asignaciones `rol_usuario`, exclusivamente para administradores; `rol` es un catálogo fijo de consulta, sin operaciones de escritura desde la API ni desde la pantalla.
- **Inicialización de seguridad aprobada por el equipo para esta revisión:** sembrar los roles `admin` y `consulta` en `db/init.sql` para bases nuevas, y crear la primera cuenta administradora y su asociación al rol `admin` con un script PHP **solo CLI**, sin contraseña fija en SQL ni registro público.
- Pruebas de regresión de los **seis recursos v1** y los **diez recursos v2** bajo la nueva política de acceso, respetando las operaciones que cada uno admite.

El documento del módulo establece explícitamente que una persona con rol de consulta **no puede escribir** y que únicamente el administrador puede acceder a los recursos de gestión de usuarios y roles. Para esta implementación, el equipo concreta la gestión de `rol` como consulta de un catálogo fijo; la creación y edición de asignaciones se realiza mediante `rol_usuario`.

## 2. Qué NO incluye

- Las diez consultas multitabla, los gráficos, el dashboard, la PWA y la publicación: pertenecen a **v4**.
- Un framework PHP o Composer sin enmienda aprobada de constitución y decisión documentada.
- Un frontend con conexión PDO directa a MariaDB o una ruta genérica `/api/{tabla}`.
- Un endpoint de registro público, recuperación de contraseña por correo, OAuth externo, MFA o un sistema de revocación centralizada de JWT, salvo aprobación explícita del equipo y profesor.
- Modificaciones oportunistas a los contratos de v1/v2 que no estén justificadas por la nueva capa de seguridad.

## 3. Actores, datos y alcance funcional

| Actor | Capacidades exigidas por el módulo | Alcance aún por precisar |
|---|---|---|
| Usuario no autenticado | Acceso a la pantalla de login y petición `POST /api/login` | Si algunas vistas informativas o `GET /` permanecen públicas |
| Usuario con rol `consulta` | Lectura de los 16 recursos autorizados; **prohibidas todas las escrituras** y acceso a recursos administrativos | La lectura del catálogo administrativo `rol` también está prohibida |
| Usuario `admin` | CRUD de `usuario`; consulta del catálogo `rol`; gestión de asignaciones `rol_usuario` | No puede crear, editar ni retirar tipos de rol |

**Matriz de permisos aprobada por el equipo según confirmación del estudiante (10-11-2026):**

| Recurso/acción | Sin sesión | `consulta` | `admin` |
|---|---|---|---|
| `POST /api/login` | Sí | Sí | Sí |
| 16 recursos de investigación: `GET` | No | Sí | Sí |
| 16 recursos de investigación: `POST`, `PUT`, `PATCH`, `DELETE` | No | No | Sí |
| `usuario`: operaciones CRUD | No | No | Sí |
| `rol`: `GET` de catálogo y ficha | No | No (`403`) | Sí |
| `rol`: `POST`, `PUT`, `PATCH`, `DELETE` | No | No (`403` por falta de acceso administrativo) | No (`405`, `Allow: GET`) |
| `rol_usuario`: `GET`, `POST`, `DELETE` | No | No | Sí |
| `rol_usuario`: `PUT`, `PATCH` | No | No | No (`405`) |
| Salir de la sesión del frontend | No aplica | Sí | Sí |

La matriz es **acuerdo funcional del equipo**, no prueba de una API ya protegida. Un usuario con múltiples roles solo obtiene permisos administrativos si posee el rol `admin` activo y su cuenta está activa; los permisos deberán comprobarse en la base durante la autorización. No asignar privilegios implícitos a otros nombres de rol.

## 3.1 Compatibilidad vinculante con la V2 recibida

Los dieciséis recursos mantienen sus **nombres de rutas** (ver `6_contracts.md` y `COMPARACION_V2_V3.md`). En V2 se distinguen tres grupos:

1. **V1 (6 recursos):** mantener sus contratos de lectura, creación, reemplazo, actualización y retiro lógico.
2. **V2 con campos editables (5 recursos):** `docente`, `grupo_investigacion`, `semillero`, `participa_grupo`, `participa_semillero`. Conservan GET, POST, PUT, PATCH, DELETE y sus validaciones; las dos participaciones usan claves compuestas.
3. **V2 asociaciones simples (5 recursos):** `grupo_linea`, `semillero_linea`, `ac_linea`, `ods_linea`, `aa_linea`. Solo GET, POST y DELETE lógico; **PUT y PATCH continúan respondiendo 405** para un actor autorizado. Sus claves compuestas se mantienen inmutables.

**Detalle crítico de regresión:** la V2 documenta **PATCH `{}` → 422** en los controladores revisados, aunque V1 y el borrador temprano de V2 contemplaban **400**. En V3, las respuestas de las operaciones autorizadas deben conservar el comportamiento documentado por **cada recurso**, hasta que el equipo acuerde y registre cualquier corrección contractual. Añadir seguridad no autoriza una normalización silenciosa.

La columna `rol` de `participa_grupo` y `participa_semillero` es un **rol de participación investigativa**; no confundirla con los privilegios de la tabla `rol` de la V3.

## 4. Requisitos funcionales

- **RF-v3-00 — Inicializar la administración sin incrustar secretos en el código.** En una instalación nueva deben existir los roles `admin` y `consulta` y una cuenta administradora inicial activa con contraseña protegida y asociación válida. La inicialización repetida no duplica cuentas ni cambia contraseñas; una instalación con datos existentes no debe borrarlos ni promocionar usuarios por accidente. **Decisión técnica acordada:** roles sin secretos en `init.sql` y cuenta inicial mediante PHP solo CLI, orquestado por el servicio Docker `inicializar-admin`, según C5 y [DECISION_BOOTSTRAP_ADMIN.md](DECISION_BOOTSTRAP_ADMIN.md). **Probado localmente:** arranque automático, reconocimiento de un admin existente, creación en volumen nuevo aislado y repetición idempotente. Siguen pendientes escenarios adversos y adaptación a un hosting sin Docker; véase [REGISTRO_BOOTSTRAP_Y_LOGIN_DEMO_20261116.md](REGISTRO_BOOTSTRAP_Y_LOGIN_DEMO_20261116.md).
- **RF-v3-01 — Iniciar sesión.** La API recibe credenciales, comprueba que el usuario está activo y verifica la contraseña con `password_verify()`. No devuelve la contraseña ni su hash.
- **RF-v3-02 — Emitir JWT.** Tras credenciales correctas, la API emite un JWT HS256 con identificador de usuario, vencimiento y datos mínimos necesarios para la autorización. El JWT tendrá una vigencia acordada de **60 minutos (3600 segundos)** desde su emisión (`exp = iat + 3600`); firma y demás claims se especifican en `6_contracts.md`.
- **RF-v3-03 — Verificar autenticación.** La API deniega el acceso a rutas protegidas cuando falte el token, esté malformado, su firma no sea válida o haya expirado.
- **RF-v3-04 — Autorizar por rol.** La API decide el permiso por combinación de ruta y método antes de llegar al controlador del recurso; mostrar u ocultar botones no sustituye este control.
- **RF-v3-05 — Proteger escrituras.** El perfil `consulta` puede acceder a lecturas autorizadas, pero no crear, editar ni retirar registros de los 16 CRUD previos.
- **RF-v3-06 — Gestionar usuarios.** Solo administradores pueden listar, consultar, crear, reemplazar, modificar y retirar usuarios activos por rutas específicas. Las contraseñas se almacenan con `password_hash()`.
- **RF-v3-07 — Consultar el catálogo fijo de roles.** El sistema dispone únicamente de los perfiles `admin` y `consulta`, sembrados y asegurados por nombre sin IDs fijos. Solo administradores pueden consultar `GET /api/rol` y `GET /api/rol/{id}`. No se permite crear, reemplazar, actualizar ni retirar roles desde la aplicación: `POST`, `PUT`, `PATCH` y `DELETE` responden `405` y la cabecera `Allow: GET`. La columna `activo` se conserva por compatibilidad con el DDL y se usa como filtro interno, no como campo editable.
- **RF-v3-08 — Gestionar asignaciones de roles.** Solo administradores pueden consultar, crear y retirar relaciones en `rol_usuario`, conforme a la llave y restricciones reales. Como la PK compuesta no tiene atributos editables, `PUT` y `PATCH` responden `405`.
- **RF-v3-09 — Sesión PHP y menú.** El frontend inicia sesión mediante HTTP contra la API, almacena el token en `$_SESSION`, lo incorpora al encabezado Bearer de sus llamadas y ofrece exclusivamente las acciones correspondientes a los permisos.
- **RF-v3-10 — Cerrar sesión.** El frontend elimina la información de autenticación de su sesión. Un cierre de sesión **no equivale por sí mismo a revocar criptográficamente un JWT ya emitido**.
- **RF-v3-11 — Mantener separación.** El frontend sigue funcionando sin la API, presenta una advertencia sin exponer datos y jamás consulta MariaDB por su cuenta.
- **RF-v3-12 — Regresión.** Cada recurso de v1/v2 mantiene reglas de integridad, validaciones, métodos HTTP, PK simples/compuestas, códigos y borrado lógico; se agrega la comprobación de identidad y permisos.
- **RF-v3-13 — Respetar los métodos no admitidos.** Las cinco asociaciones simples V2 deben continuar devolviendo `405` a PUT/PATCH una vez autentificada y autorizada la identidad; una cuenta `consulta` sin permiso de escritura puede recibir antes `403` según precedencia de seguridad aprobada.
- **RF-v3-14 — No confundir roles funcionales.** El atributo `rol` de las participaciones V2 nunca concede permisos de autenticación de V3.
- **RF-v3-15 — Mostrar acceso académico opcional.** Cuando el operador habilite explícitamente `LOGIN_DEMO_ENABLED=1` y configure usuario y contraseña de demostración, el frontend mostrará los datos para facilitar el ingreso durante la evaluación; deshabilitado o incompleto, el aviso no aparecerá. El aviso **no crea cuentas, cambia contraseñas ni concede permisos**: el login normal de la API debe validar esas credenciales frente a una cuenta existente. Los valores no se incrustan en el repositorio, y la función solo se autoriza para un entorno de demostración con datos desechables.

## 5. Requisitos no funcionales

- **RNF-v3-01 — Stack.** PHP 8.3+, `declare(strict_types=1);`, PDO/prepared statements, sesiones nativas; sin framework ni Composer salvo aprobación formal. **Situación V2 documentada:** el cliente HTTP del frontend utiliza `stream_context_create()` y `file_get_contents()`, no cURL; la metodología exige cURL y el equipo debe resolver C11-V2 antes de afirmar cumplimiento.
- **RNF-v3-02 — Secretos.** `JWT_SECRET` y credenciales de operación solo en variables de entorno, `.env` ignorado por Git y `.env.example` sin valores reales. No publicar secretos en Git. **Excepción explícita para una cuenta académica desechable:** RF-v3-15 permite visualizar su usuario y contraseña en la pantalla de login si el operador habilita el modo demo, con pleno conocimiento de que dejan de ser secretos para los visitantes. Esto nunca incluye `JWT_SECRET` ni claves de base de datos.
- **RNF-v3-03 — Contraseñas.** Hash con `password_hash()`; validación con `password_verify()`. Nunca contraseñas ni hashes en respuestas de la **API**, logs de depuración ni mensajes de error. La tarjeta demo de RF-v3-15 es una exposición **deliberada y exclusiva** de credenciales de prueba configuradas para ese fin, no de contraseñas de cuentas reales de producción.
- **RNF-v3-04 — Firma y vigencia.** El verificador comprueba formato, algoritmo HS256, firma con `hash_equals`, claims exigidos y expiración; rechaza tokens alterados.
- **RNF-v3-05 — Capas.** HTTP en enrutador/controladores, reglas en servicios, SQL solo en repositorios; servicios dependen de interfaces y el ensamblador crea las implementaciones.
- **RNF-v3-06 — Interfaz.** Mensajes comprensibles en español; los formularios conservan los datos no sensibles tras errores y nunca reimprimen contraseñas introducidas por el usuario. La tarjeta informativa opcional de RF-v3-15 es distinta de un formulario con error.
- **RNF-v3-07 — Aislamiento.** Los permisos son exigibles con Postman o cualquier cliente HTTP, incluso cuando alguien construya manualmente una petición que la pantalla no ofrece.
- **RNF-v3-08 — Pruebas.** Hay pruebas de autenticación, autorización, CRUD de `usuario`, catálogo `rol` de solo lectura, operaciones de `rol_usuario`, regresión v1/v2, separación de procesos y manejo de base caída.

## 6. Criterios de aceptación (demostrables)

0. En una instalación limpia, tras configurar las variables locales y ejecutar la inicialización aprobada, existen los roles activos `admin` y `consulta`, una sola cuenta inicial administradora activa con hash y una asignación válida en `rol_usuario`; el login de ese administrador funciona. Repetir la inicialización no crea duplicados ni sobrescribe contraseñas. Con credenciales iniciales ausentes o inválidas, **no se crea** un admin inseguro. En una base persistente no se borran datos ni se requiere reiniciar volúmenes.
1. Credenciales correctas de usuario activo entregan JWT firmado; credenciales incorrectas no permiten iniciar sesión.
2. Contraseñas guardadas en `usuario.password` son hashes verificables y no texto plano.
3. Petición a recurso protegido **sin Bearer** → `401`; con token alterado o vencido → `401`.
4. Petición de un usuario autenticado con rol `consulta` a una lectura autorizada → conserva el contrato de v1/v2.
5. El mismo usuario `consulta` intenta `POST`, `PUT`, `PATCH` o `DELETE` sobre un recurso de investigación → `403`, sin modificar la base.
6. Usuario `consulta` no accede a los recursos administrativos (`usuario`, catálogo `rol` y `rol_usuario`) ni a sus pantallas; las peticiones directas también dan `403`.
7. Un administrador puede utilizar el CRUD de `usuario`, consultar el catálogo fijo `rol` y gestionar las asignaciones `rol_usuario` por API y frontend. `GET /api/rol` y `GET /api/rol/{id}` funcionan; los métodos de escritura sobre `rol` responden `405` con `Allow: GET`.
8. Los usuarios retirados permanecen físicamente con `activo = FALSE` y no aparecen en consultas normales. Los roles semilla `admin` y `consulta` se mantienen activos; la API no ofrece una operación para retirar o editar roles.
9. Al cerrar sesión, el frontend deja de conservar/enviar el token y las pantallas protegidas requieren nuevo login.
10. El frontend transmite el JWT como `Authorization: Bearer` al consumir la API y no comparte modelos ni credenciales de la BD.
11. Todas las pruebas v1/v2 siguen pasando **con autenticación adecuada**: se conserva `409` por integridad, `204` de listas vacías, `404` tras retiro, `405` para PUT/PATCH de cinco asociaciones simples y `422` para PATCH vacío de los controladores V2 documentados; las diferencias con V1 se registran por recurso.
12. Con la API apagada, el frontend sirve sus páginas y un aviso de indisponibilidad sin mostrar registros de MariaDB.
13. `JWT_SECRET` no aparece en código versionado; el valor de ejemplo no funciona como secreto real.
14. Se documentan rutas, respuestas 401/403, esquema real, quickstart reproducible y pruebas ejecutadas; al cierre se crea `tag v3` en `main` mediante el flujo de PR.
15. En modo demo habilitado, `/login` presenta las credenciales configuradas, y el acceso real depende de que coincidan con un usuario de la base. Con `LOGIN_DEMO_ENABLED=0` o credenciales incompletas, no se presentan. La cuenta exhibida solo se utiliza con datos ficticios y sin acceso a información sensible.

## 7. Clarificaciones — COMPUERTA 1 (resolver ANTES del plan definitivo)

- **C1 — DDL del script: RESUELTO CON `init(4).sql`.** `usuario.id` y `rol.id` son `INT AUTO_INCREMENT`; `username`, `email` y `rol.nombre` son únicos; `rol_usuario` tiene PK compuesta `(usuario_id, rol_id)` y FK `ON DELETE CASCADE` en ambas columnas; `rol_usuario` NO tiene `activo`. El detalle íntegro está en `5_data_model.md`. **Verificación pendiente en entorno:** comprobar con `SHOW CREATE TABLE` si el volumen MariaDB usado por el equipo coincide con este script.
- **C2 — Rutas y métodos de v2:** **RESUELTO EN DOCUMENTACIÓN V2**: inventario de 16 recursos, rutas simples y compuestas, cinco asociaciones GET/POST/DELETE con 405 en PUT/PATCH, y PATCH vacío 422 en V2 revisada. **Pendiente de corroboración técnica:** comparar `api_investigacion/index.php` del commit integrado y ejecutar regresión; los documentos solos no prueban el estado actual del código.
- **C3 — Catálogo de roles: APROBADO Y PRECISADO (acuerdo inicial 10-11-2026; alcance definitivo 19-11-2026).** Los únicos perfiles de acceso de la v3 son `admin` y `consulta`, identificados por su **nombre**; sus `id` los genera MariaDB. Se permiten varias asignaciones por usuario, pero solo un `admin` activo concede permisos de administración. `rol` es un catálogo fijo: la API y el frontend no permiten crear, editar ni retirar roles. Solo se permiten GET administrativos para listar y consultar; los métodos de escritura responden `405` con `Allow: GET`. No se admiten nombres adicionales como perfiles de acceso en esta versión.
- **C4 — Protección de recursos: APROBADA (10-11-2026), con precisión del catálogo 19-11-2026.** `POST /api/login` es público; los 16 recursos previos y los recursos administrativos exigen JWT. `consulta` solo puede leer los 16 recursos de investigación y no puede acceder a `usuario`, `rol` ni `rol_usuario`; `admin` puede operar en los 16 recursos y administrar `usuario`/`rol_usuario`, además de consultar `rol`. Para `admin`, los métodos de escritura sobre `rol` responden `405`; para `consulta`, el acceso al recurso `rol` debe ser denegado con `403`. El diagnóstico básico puede seguir público sin revelar información sensible; definir su ruta exacta según el enrutador existente.
- **C5 — Primer administrador: ARRANQUE AUTOMÁTICO LOCAL E IDEMPOTENCIA PROBADOS (15-11-2026).** Además del alta manual histórica, el `docker-compose.yml` local incorpora `inicializar-admin`: se ejecuta tras MariaDB saludable y la API espera su finalización correcta. En el volumen habitual respondió «Ya existe un administrador activo. No se duplicó la cuenta ni se cambió la contraseña». En proyecto Docker aislado con volumen nuevo respondió «Administrador inicial creado y rol asignado correctamente»; su segunda ejecución conservó la cuenta. SQL de la base nueva verificó `ADMIN`, `activo=1`, rol `admin`, hash de longitud 60 y `COUNT(*)=1`. **No se ha demostrado** aún fallo cerrado con variables ausentes, escenarios ambiguos ni login HTTP contra esa base aislada. Los volúmenes no viajan con Git; para AlwaysData se necesitará un procedimiento CLI propio porque allí no se ejecutará Compose. Ver [REGISTRO_BOOTSTRAP_Y_LOGIN_DEMO_20261116.md](REGISTRO_BOOTSTRAP_Y_LOGIN_DEMO_20261116.md).
- **C6 — JWT: DURACIÓN APROBADA Y PROBADA (12-11-2026).** Vida exacta **60 minutos / 3600 segundos**, con `iat` y `exp`. El login JWT y el middleware están implementados y pasaron las suites 15/15 y 28/28, más peticiones HTTP reales 401/200 para un recurso. La fase 5 ya registró **HTTP 403 al reutilizar el mismo JWT después del retiro del rol**; el frontend reprodujo la denegación visual durante sesión abierta el **13-11-2026**. Faltan otros roles, recursos y la matriz completa de integración.
- **C7 — Logout: APROBADO.** El frontend elimina el JWT almacenado en `$_SESSION`, destruye la sesión PHP y redirige a login. No habrá mecanismo de revocación centralizada ni refresh token en esta versión; un JWT copiado antes del logout podrá seguir siendo válido hasta su `exp`, salvo desactivación de cuenta/rol.
- **C8 — Relación `rol_usuario`:** **DDL aclarado**: PK compuesta `(usuario_id, rol_id)`, solo esas dos columnas, ambas FK con `ON DELETE CASCADE`, sin `activo` ni atributos editables adicionales. **Decisión del equipo:** `DELETE` físico únicamente del vínculo `rol_usuario`, excepción documentada al borrado lógico (porque el puente no tiene `activo`); `PUT/PATCH` responden `405`; impedir quitar el último administrador activo. `usuario` conserva su borrado lógico. `rol` mantiene la columna `activo` del DDL, pero su catálogo es fijo y la API no ofrece operaciones para editarlo ni retirarlo. Pendiente de pruebas y revisión de carreras transaccionales.
- **C9 — Login y gestión de contraseñas: IMPLEMENTADOS EN `usuario` (12-11-2026).** `POST /api/login` recibe `username` y `password`. El CRUD restringe `PUT` y `PATCH` de perfil a `username`, `email` y `nombre_completo`; el cambio de contraseña es exclusivo de `PATCH /api/usuario/{id}/password` con `{ "password": "..." }`. El servicio exige de 12 a 72 bytes (ratificar el máximo como decisión del equipo), usa `password_hash(PASSWORD_DEFAULT)` y no retorna hashes. El backend obtuvo HTTP 200 para el cambio; el **13-11-2026** el estudiante comunicó login exitoso de una cuenta de prueba con contraseña actualizada **después de asignarle `consulta`**. No se divulgó la contraseña y sigue pendiente una auditoría automatizada de casos negativos.
- **C10 — Sesiones web: IMPLEMENTACIÓN PARCIAL COMPROBADA (13-11-2026).** El código del frontend usa sesión PHP con JWT del lado servidor, regeneración del ID al autenticarse, logout local y controles de cookie (`HttpOnly`, `SameSite`, `Secure` cuando exista HTTPS). Las rutas `/login`, `/logout`, `/usuarios` y `/roles-usuarios` y la protección CSRF de formularios administrativos constan en `6_contracts.md` §9.4 y [REGISTRO_FASE6_FRONTEND.md](REGISTRO_FASE6_FRONTEND.md). Se probó denegación por revocación en navegador; **falta** prueba independiente del cierre/cookie, CSRF malformado y API apagada.
- **C11 — Cierre heredado de V2:** [NECESITA VERIFICACIÓN: la documentación del 29-10 registra C4 (retiro de padres con hijos), C11 (cliente HTTP sin cURL), regresión final, PR/tag; el usuario confirmó integración backend PR #15. Revisar qué se resolvió posteriormente y no reabrir versiones cerradas sin acuerdo].
- **C12 — Separación entre rol de seguridad y rol investigativo:** **ACLARADO**: `rol_usuario` asigna permisos del sistema; `participa_grupo.rol` y `participa_semillero.rol` describen participación académica y no autorizan acciones por sí mismos.
- **C13 — Acceso demo (16-11-2026):** la tarjeta de credenciales solo pertenece a la **vista de login** y se habilita con las variables de entorno `LOGIN_DEMO_ENABLED`, `LOGIN_DEMO_ADMIN_USERNAME` y `LOGIN_DEMO_ADMIN_PASSWORD`, pasadas a `front-php` mediante `docker-compose.demo.yml`. No forma parte del contrato JSON de `/api/login`, ni modifica JWT, permisos o persistencia. Al cambiar solo estas variables, la contraseña de `usuario` **no cambia**. El inicio de sesión real fue observado después de alinear usuario/contraseña con la cuenta existente. La exposición deliberada de una cuenta admin supone acceso administrativo para cualquier visitante que vea el sitio: limitarlo a **demo pública de datos ficticios**, con credenciales exclusivas y posibilidad de regenerar el entorno.

**Registro de acuerdo y ejecución:** el equipo aprobó el alcance y ya se modificó SQL/código de la fase 1 en la rama local; existe evidencia de ejecución manual. Esto **no equivale** a validación del profesor, prueba total de seguridad ni aprobación formal de todas las compuertas SDD. Véanse D-v3-09 y D-v3-16 en `4_research.md`.

> **Regla de la compuerta:** el equipo decide y escribe cada respuesta aquí, después armoniza `3_plan.md`, `5_data_model.md` y `6_contracts.md`. Las alternativas que aparecen en borradores NO autorizan implementación automática.

## 8. Flujos verificables de usuario (inspirados en el formato del ejemplo del profesor)

| Flujo | Actor | Situación inicial | Resultado esperado | RF |
|---|---|---|---|---|
| F1. Inicio de sesión | Administrador/consulta | Cuenta activa con contraseña válida | Respuesta 200 y JWT; menú del rol al iniciar sesión web | RF-v3-01/02/09 |
| F2. Acceso anónimo | Sin sesión | Petición a recurso protegido | 401 antes de consultar el recurso | RF-v3-03/04 |
| F3. Lectura permitida | Consulta | JWT válido y rol vigente | GET respeta formato y códigos heredados | RF-v3-04/05/12 |
| F4. Escritura denegada | Consulta | JWT válido | 403 sin alteración de BD | RF-v3-05 |
| F5. Administración | Admin | JWT válido y rol vigente | CRUD de `usuario`, GET del catálogo `rol` y operaciones permitidas de `rol_usuario` | RF-v3-06/07/08 |
| F6. Cierre de sesión | Admin/consulta | Sesión web iniciada | Frontend descarta la sesión; no asegura revocación del JWT copiado | RF-v3-10 |
| F7. Primer arranque y reintento | Operador autorizado | Base nueva o volumen preexistente | Roles + administrador creados de forma idempotente, sin contraseña incrustada en el código | RF-v3-00 |
| F8. Inicio de sesión académico | Visitante en entorno de demostración | `LOGIN_DEMO_ENABLED=1` y una cuenta real configurada | `/login` muestra las credenciales de demo; la API valida la cuenta normalmente | RF-v3-15 |

La matriz C4 está aprobada. La lectura del catálogo `rol` con `consulta` sigue siendo una comprobación HTTP pendiente, aunque el contrato exige `403`; las pruebas del 19-11-2026 cubrieron el acceso administrativo y los métodos de escritura con token admin.

## 9. Glosario mínimo

- **Autenticación:** prueba de identidad; token ausente o inválido corresponde a `401`.
- **Autorización:** permiso del usuario autenticado para una ruta y método; falta de permiso corresponde a `403`.
- **JWT:** token firmado; el frontend lo transporta como Bearer, nunca como contraseña persistida en base.
- **Rol de acceso:** valor de `rol.nombre`, asociado por `rol_usuario` y consultado solo si usuario y rol están activos.
- **Rol investigativo:** texto de `participa_grupo.rol` o `participa_semillero.rol`; no concede permisos web.
- **Regresión:** ejecutar los criterios de v1/v2 con credenciales válidas de la v3, sin cambiar sus contratos de negocio.

## 10. Definición de TERMINADA

La v3 NO se declara terminada por tener el login funcionando. Deben existir en los repositorios separados **API y pantallas**; superarse las tres compuertas del SDD; ejecutarse la matriz de permisos con pruebas directas HTTP y de frontend; verificarse idempotencia del administrador inicial; comprobarse la regresión de los 16 recursos previos y la ausencia de secretos en Git; cerrarse mediante PR revisado y tag `v3` en `main`. Los criterios 0–15 del §6 deben tener evidencia real en [7_quickstart.md](7_quickstart.md) y revisión humana de [9_checklist.md](9_checklist.md).

**Pendientes que bloquean aprobación global:** escenarios negativos y adaptación de C5 a AlwaysData (la orquestación Docker, el volumen aislado y la repetición ya tienen evidencia local), pruebas negativas/integración restantes de C6/C8/C9/C10/C13, clarificación heredada C11-V2, prueba específica de `consulta` contra `GET /api/rol`, regresión HTTP completa de los 16 recursos y verificación formal de compuertas. El catálogo fijo `rol` ya está integrado y cuenta con pruebas acotadas; esto no equivale a aprobar toda la v3. **C3/C4 y el alcance de C7 ya tienen acuerdo registrado**, pero su aplicación integral debe revisarse al cierre. La existencia de este documento no aprueba la V3.
