# Especificación — v3: Autenticación, sesiones y roles (Investigación)

> **Seguimiento de implementación — 13-11-2026:** los flujos principales del frontend de autenticación, `usuario` y `rol_usuario` ya fueron probados manualmente; se verificó denegación del acceso de `consulta`, retirada del único rol durante una sesión abierta y recuperación tras reasignarlo. Ver [REGISTRO_FASE6_FRONTEND.md](REGISTRO_FASE6_FRONTEND.md). La **especificación y sus criterios no se consideran aprobados automáticamente**: faltan catálogo `rol`, regresión general, pruebas de API apagada y cierre de compuertas.

> **Actualización de evidencia — 12-11-2026, fase 5:** gestión principal de `rol_usuario` comprobada por **15/15** pruebas aisladas y HTTP real: asignación `201`, ficha `200`, duplicado `409`, retiro `200`, ficha posterior `404`. Un usuario `consulta` real obtuvo GET V1 `200`, POST V1 `403` y GET administrativo `403`; tras retirar el rol, **el mismo JWT** pasó a GET `403`. SQL confirmó usuario sin rol aún activo y `ADMIN` con `admin`. La protección del último administrador **queda pendiente de prueba final solo en base descartable**, sin arriesgar el volumen local. Ver [REGISTRO_FASE5_ROL_USUARIO.md](REGISTRO_FASE5_ROL_USUARIO.md).

> **Estado (revisión con V2, SQL y v3 del profesor): BORRADOR PARA COMPUERTA 1.** El usuario informa V2 terminada y confirmó el merge del backend en `main` mediante PR #15, commit `9bcf84b`; los documentos V2 fechados 29-10-2026 seguían registrando pendientes de evidencia de regresión, aprobación y tag. No suponer que tales pendientes siguen abiertos ni que ya se cerraron: verificarlos. Además del `.rar` de V2, ahora se recibió `init(4).sql` con el DDL de seguridad; **sigue sin verificarse la estructura DDL de la base ya desplegada y el código completo actualizado del frontend; se recibieron archivos parciales de login/enrutamiento y salidas de pruebas del backend**.
>
> **Fuentes:** `0_METODOLOGIA.md`, `modulo_investigacion.md` y `2_spec.md`–`9_checklist.md` + `GUIA_IA2.md` del paquete V2 (29-10-2026). La constitución **vigente del equipo** requiere cotejo; el DDL fuente de seguridad ya fue cotejado con el SQL compartido, sujeto a verificar la base en ejecución. Véase `COMPARACION_V2_V3.md`. El mapa antiguo del ejemplo no fija el alcance del proyecto de aula.

> **Evidencia de implementación al 12-11-2026 (comunicada por el estudiante):** fase 1: alta manual de roles y administrador local; fase 2: **15/15** pruebas de login/JWT, login HTTP `200` y vigencia `3600` s; fase 3: **28/28** pruebas de autorización y GET `/api/area_conocimiento` con `401` sin Bearer / `200` con Bearer válido. **Fase 4 (`usuario`): 28/28 pruebas aisladas y operaciones HTTP reales** `GET` listado/por ID (`200`), `POST` (`201`), `PUT` (`200`), `PATCH` de perfil (`200`), `PATCH` de contraseña (`200`), `DELETE` lógico (`200`), `GET` posterior (`404`), duplicados de `username` y `email` (`409`), y GET sin JWT (`401`). En MariaDB se observó `activo=0` tras DELETE, hashes con prefijo `$2y$` y longitud 60, y un administrador activo. **Quedan pendientes** la prueba real de la barrera del último administrador (solo probada con repositorio falso), una verificación directa `password_verify()` del cambio aleatorio de contraseña del usuario de prueba, `rol`, `rol_usuario`, frontend, instalación limpia/idempotencia, pruebas completas con `consulta` y regresión de los 16 recursos. Véanse [REGISTRO_FASE1_ADMIN.md](REGISTRO_FASE1_ADMIN.md), [REGISTRO_FASE2_LOGIN.md](REGISTRO_FASE2_LOGIN.md), [REGISTRO_FASE3_MIDDLEWARE.md](REGISTRO_FASE3_MIDDLEWARE.md) y [REGISTRO_FASE4_USUARIO.md](REGISTRO_FASE4_USUARIO.md).

**Guía de lectura:** este archivo fija el **QUÉ**. Las rutas y respuestas se detallan en [6_contracts.md](6_contracts.md); clases, fases y tecnologías en [3_plan.md](3_plan.md). Lo que exige el profesor se distingue de las decisiones acordadas por el equipo y de las propuestas aún sujetas a clarificación.

La v3 añade **autenticación y autorización** a los CRUD existentes del módulo de Investigación y habilita la administración de las tres tablas de gestión de usuarios ya previstas en la base de datos:

- `POST /api/login`: valida credenciales y entrega un JWT firmado mediante HS256.
- Comprobación de identidad y permisos **antes de ejecutar el controlador del recurso** desde el front controller de la API.
- **Sesión PHP en el frontend**: conservar el JWT del lado del servidor y enviarlo a la API mediante `Authorization: Bearer <token>`.
- **Menú por roles**: ocultar las acciones no autorizadas sin confiar en el menú como única protección.
- **Logout**: cerrar la sesión del frontend y dejar de enviar el JWT.
- CRUD de `usuario`, `rol` y `rol_usuario`, exclusivamente para administradores, con endpoints específicos y pantallas PHP.
- **Inicialización de seguridad aprobada por el equipo para esta revisión:** sembrar los roles `admin` y `consulta` en `db/init.sql` para bases nuevas, y crear la primera cuenta administradora y su asociación al rol `admin` con un script PHP **solo CLI**, sin contraseña fija en SQL ni registro público.
- Pruebas de regresión de los **seis recursos v1** y los **diez recursos v2** bajo la nueva política de acceso, respetando las operaciones que cada uno admite.

El documento del módulo establece explícitamente que una persona con rol de consulta **no puede escribir** y que únicamente el administrador puede ver y gestionar usuarios y roles.

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
| Usuario con rol `consulta` | Lectura de información autorizada; **prohibidas todas las escrituras** | Lecturas exactas visibles, de acuerdo con la matriz del equipo |
| Usuario `admin` | Administración exclusiva de `usuario`, `rol`, `rol_usuario` | Identificador real del rol y eventual alcance de otros perfiles |

**Matriz de permisos aprobada por el equipo según confirmación del estudiante (10-11-2026):**

| Recurso/acción | Sin sesión | `consulta` | `admin` |
|---|---|---|---|
| `POST /api/login` | Sí | Sí | Sí |
| 16 recursos de investigación: `GET` | No | Sí | Sí |
| 16 recursos de investigación: `POST`, `PUT`, `PATCH`, `DELETE` | No | No | Sí |
| `usuario`, `rol`, `rol_usuario`: cualquier CRUD | No | No | Sí |
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

- **RF-v3-00 — Inicializar la administración sin credenciales públicas.** En una instalación nueva deben existir los roles `admin` y `consulta` y una cuenta administradora inicial activa con contraseña protegida y asociación válida. La inicialización repetida no duplica cuentas ni cambia contraseñas; una instalación con datos existentes no debe borrarlos ni promocionar usuarios por accidente. **Decisión técnica acordada:** roles sin secretos en `init.sql` y cuenta inicial mediante PHP solo CLI, según C5 y [DECISION_BOOTSTRAP_ADMIN.md](DECISION_BOOTSTRAP_ADMIN.md). La **ejecución manual CLI sobre el volumen persistente** ya resultó satisfactoria en el entorno del estudiante. Están pendientes las pruebas de idempotencia, instalación limpia y automatización de arranque Docker; véase `REGISTRO_FASE1_ADMIN.md`.
- **RF-v3-01 — Iniciar sesión.** La API recibe credenciales, comprueba que el usuario está activo y verifica la contraseña con `password_verify()`. No devuelve la contraseña ni su hash.
- **RF-v3-02 — Emitir JWT.** Tras credenciales correctas, la API emite un JWT HS256 con identificador de usuario, vencimiento y datos mínimos necesarios para la autorización. El JWT tendrá una vigencia acordada de **60 minutos (3600 segundos)** desde su emisión (`exp = iat + 3600`); firma y demás claims se especifican en `6_contracts.md`.
- **RF-v3-03 — Verificar autenticación.** La API deniega el acceso a rutas protegidas cuando falte el token, esté malformado, su firma no sea válida o haya expirado.
- **RF-v3-04 — Autorizar por rol.** La API decide el permiso por combinación de ruta y método antes de llegar al controlador del recurso; mostrar u ocultar botones no sustituye este control.
- **RF-v3-05 — Proteger escrituras.** El perfil `consulta` puede acceder a lecturas autorizadas, pero no crear, editar ni retirar registros de los 16 CRUD previos.
- **RF-v3-06 — Gestionar usuarios.** Solo administradores pueden listar, consultar, crear, reemplazar, modificar y retirar usuarios activos por rutas específicas. Las contraseñas se almacenan con `password_hash()`.
- **RF-v3-07 — Gestionar roles.** Solo administradores pueden realizar CRUD de roles, respetando el borrado lógico de `rol`.
- **RF-v3-08 — Gestionar asignaciones de roles.** Solo administradores pueden consultar, crear, modificar cuando corresponda y retirar relaciones en `rol_usuario`, conforme a la llave y restricciones reales.
- **RF-v3-09 — Sesión PHP y menú.** El frontend inicia sesión mediante HTTP contra la API, almacena el token en `$_SESSION`, lo incorpora al encabezado Bearer de sus llamadas y ofrece exclusivamente las acciones correspondientes a los permisos.
- **RF-v3-10 — Cerrar sesión.** El frontend elimina la información de autenticación de su sesión. Un cierre de sesión **no equivale por sí mismo a revocar criptográficamente un JWT ya emitido**.
- **RF-v3-11 — Mantener separación.** El frontend sigue funcionando sin la API, presenta una advertencia sin exponer datos y jamás consulta MariaDB por su cuenta.
- **RF-v3-12 — Regresión.** Cada recurso de v1/v2 mantiene reglas de integridad, validaciones, métodos HTTP, PK simples/compuestas, códigos y borrado lógico; se agrega la comprobación de identidad y permisos.
- **RF-v3-13 — Respetar los métodos no admitidos.** Las cinco asociaciones simples V2 deben continuar devolviendo `405` a PUT/PATCH una vez autentificada y autorizada la identidad; una cuenta `consulta` sin permiso de escritura puede recibir antes `403` según precedencia de seguridad aprobada.
- **RF-v3-14 — No confundir roles funcionales.** El atributo `rol` de las participaciones V2 nunca concede permisos de autenticación de V3.

## 5. Requisitos no funcionales

- **RNF-v3-01 — Stack.** PHP 8.3+, `declare(strict_types=1);`, PDO/prepared statements, sesiones nativas; sin framework ni Composer salvo aprobación formal. **Situación V2 documentada:** el cliente HTTP del frontend utiliza `stream_context_create()` y `file_get_contents()`, no cURL; la metodología exige cURL y el equipo debe resolver C11-V2 antes de afirmar cumplimiento.
- **RNF-v3-02 — Secretos.** `JWT_SECRET` y credenciales reales solo en variables de entorno, `.env` ignorado por Git y `.env.example` sin valores reales. Si el artículo 9 de la constitución de ejemplo difiere de la metodología de este proyecto, prevalece la exigencia **del proyecto de aula**: cero secretos publicados.
- **RNF-v3-03 — Contraseñas.** Hash con `password_hash()`; validación con `password_verify()`. Nunca contraseñas ni hashes en respuestas, logs de depuración o mensajes visibles.
- **RNF-v3-04 — Firma y vigencia.** El verificador comprueba formato, algoritmo HS256, firma con `hash_equals`, claims exigidos y expiración; rechaza tokens alterados.
- **RNF-v3-05 — Capas.** HTTP en enrutador/controladores, reglas en servicios, SQL solo en repositorios; servicios dependen de interfaces y el ensamblador crea las implementaciones.
- **RNF-v3-06 — Interfaz.** Mensajes comprensibles en español, formularios conservan los datos no sensibles tras errores y nunca vuelven a mostrar contraseñas.
- **RNF-v3-07 — Aislamiento.** Los permisos son exigibles con Postman o cualquier cliente HTTP, incluso cuando alguien construya manualmente una petición que la pantalla no ofrece.
- **RNF-v3-08 — Pruebas.** Hay pruebas de autenticación, autorización, CRUD administrativo, regresión v1/v2, separación de procesos y manejo de base caída.

## 6. Criterios de aceptación (demostrables)

0. En una instalación limpia, tras configurar las variables locales y ejecutar la inicialización aprobada, existen los roles activos `admin` y `consulta`, una sola cuenta inicial administradora activa con hash y una asignación válida en `rol_usuario`; el login de ese administrador funciona. Repetir la inicialización no crea duplicados ni sobrescribe contraseñas. Con credenciales iniciales ausentes o inválidas, **no se crea** un admin inseguro. En una base persistente no se borran datos ni se requiere reiniciar volúmenes.
1. Credenciales correctas de usuario activo entregan JWT firmado; credenciales incorrectas no permiten iniciar sesión.
2. Contraseñas guardadas en `usuario.password` son hashes verificables y no texto plano.
3. Petición a recurso protegido **sin Bearer** → `401`; con token alterado o vencido → `401`.
4. Petición de un usuario autenticado con rol `consulta` a una lectura autorizada → conserva el contrato de v1/v2.
5. El mismo usuario `consulta` intenta `POST`, `PUT`, `PATCH` o `DELETE` sobre un recurso de investigación → `403`, sin modificar la base.
6. Usuario `consulta` no accede a CRUD administrativos ni a su menú; las peticiones directas también dan `403`.
7. Un administrador puede utilizar los CRUD de `usuario`, `rol` y `rol_usuario` por API y frontend (las reglas particulares se aprueban en `6_contracts.md`).
8. `usuario` y `rol` retirados permanecen físicamente con `activo = FALSE` y no aparecen en consultas normales.
9. Al cerrar sesión, el frontend deja de conservar/enviar el token y las pantallas protegidas requieren nuevo login.
10. El frontend transmite el JWT como `Authorization: Bearer` al consumir la API y no comparte modelos ni credenciales de la BD.
11. Todas las pruebas v1/v2 siguen pasando **con autenticación adecuada**: se conserva `409` por integridad, `204` de listas vacías, `404` tras retiro, `405` para PUT/PATCH de cinco asociaciones simples y `422` para PATCH vacío de los controladores V2 documentados; las diferencias con V1 se registran por recurso.
12. Con la API apagada, el frontend sirve sus páginas y un aviso de indisponibilidad sin mostrar registros de MariaDB.
13. `JWT_SECRET` no aparece en código versionado; el valor de ejemplo no funciona como secreto real.
14. Se documentan rutas, respuestas 401/403, esquema real, quickstart reproducible y pruebas ejecutadas; al cierre se crea `tag v3` en `main` mediante el flujo de PR.

## 7. Clarificaciones — COMPUERTA 1 (resolver ANTES del plan definitivo)

- **C1 — DDL del script: RESUELTO CON `init(4).sql`.** `usuario.id` y `rol.id` son `INT AUTO_INCREMENT`; `username`, `email` y `rol.nombre` son únicos; `rol_usuario` tiene PK compuesta `(usuario_id, rol_id)` y FK `ON DELETE CASCADE` en ambas columnas; `rol_usuario` NO tiene `activo`. El detalle íntegro está en `5_data_model.md`. **Verificación pendiente en entorno:** comprobar con `SHOW CREATE TABLE` si el volumen MariaDB usado por el equipo coincide con este script.
- **C2 — Rutas y métodos de v2:** **RESUELTO EN DOCUMENTACIÓN V2**: inventario de 16 recursos, rutas simples y compuestas, cinco asociaciones GET/POST/DELETE con 405 en PUT/PATCH, y PATCH vacío 422 en V2 revisada. **Pendiente de corroboración técnica:** comparar `api_investigacion/index.php` del commit integrado y ejecutar regresión; los documentos solos no prueban el estado actual del código.
- **C3 — Roles: APROBADO POR EL EQUIPO (10-11-2026).** Se utilizan `admin` y `consulta` identificados por su **nombre**; sus `id` los genera MariaDB. Se permiten varias asignaciones por usuario, pero solo un `admin` activo concede permisos de administración. La gestión de otros nombres de rol mediante CRUD no les otorga permisos privilegiados por defecto.
- **C4 — Protección de recursos: APROBADA (10-11-2026).** `POST /api/login` es público; los 16 recursos previos y los tres administrativos exigen JWT. `consulta` solo puede leer los 16 anteriores, `admin` puede operar en ellos y es el único autorizado para administrar `usuario`, `rol` y `rol_usuario`. El diagnóstico básico puede seguir público sin revelar información sensible; definir su ruta exacta según el enrutador existente.
- **C5 — Primer administrador: IMPLEMENTACIÓN MANUAL PROBADA LOCALMENTE (10-11-2026).** Se agregó al `db/init.sql` local un `INSERT` de los roles `admin` y `consulta`; los 4 archivos PHP del bootstrap pasan sintaxis y `docker compose run --rm --no-deps ... php /app/scripts/inicializar_admin.php` informó «Administrador inicial creado y rol asignado correctamente». phpMyAdmin mostró ambos roles activos y la asociación del usuario `ADMIN` con `admin`. Las credenciales se introdujeron temporalmente por PowerShell y no forman parte del SQL. **No se ha probado** segunda ejecución ni un volumen nuevo; `docker compose up -d --build` todavía no dispara el bootstrap de manera automática. El script y el volumen del estudiante no se transfieren al docente excepto por código versionado y un nuevo proceso de bootstrap. Véase `REGISTRO_FASE1_ADMIN.md`.
- **C6 — JWT: DURACIÓN APROBADA Y PROBADA (12-11-2026).** Vida exacta **60 minutos / 3600 segundos**, con `iat` y `exp`. El login JWT y el middleware están implementados y pasaron las suites 15/15 y 28/28, más peticiones HTTP reales 401/200 para un recurso. La fase 5 ya registró **HTTP 403 al reutilizar el mismo JWT después del retiro del rol**; el frontend reprodujo la denegación visual durante sesión abierta el **13-11-2026**. Faltan otros roles, recursos y la matriz completa de integración.
- **C7 — Logout: APROBADO.** El frontend elimina el JWT almacenado en `$_SESSION`, destruye la sesión PHP y redirige a login. No habrá mecanismo de revocación centralizada ni refresh token en esta versión; un JWT copiado antes del logout podrá seguir siendo válido hasta su `exp`, salvo desactivación de cuenta/rol.
- **C8 — Relación `rol_usuario`:** **DDL aclarado**: PK compuesta `(usuario_id, rol_id)`, solo esas dos columnas, ambas FK con `ON DELETE CASCADE`, sin `activo` ni atributos editables adicionales. **Decisión del equipo:** `DELETE` físico únicamente del vínculo `rol_usuario`, excepción documentada al borrado lógico (porque el puente no tiene `activo`); `PUT/PATCH` responden `405`; impedir quitar el último administrador activo. `usuario` y `rol` conservan borrado lógico. Pendiente de pruebas y revisión de carreras transaccionales.
- **C9 — Login y gestión de contraseñas: IMPLEMENTADOS EN `usuario` (12-11-2026).** `POST /api/login` recibe `username` y `password`. El CRUD restringe `PUT` y `PATCH` de perfil a `username`, `email` y `nombre_completo`; el cambio de contraseña es exclusivo de `PATCH /api/usuario/{id}/password` con `{ "password": "..." }`. El servicio exige de 12 a 72 bytes (ratificar el máximo como decisión del equipo), usa `password_hash(PASSWORD_DEFAULT)` y no retorna hashes. El backend obtuvo HTTP 200 para el cambio; el **13-11-2026** el estudiante comunicó login exitoso de una cuenta de prueba con contraseña actualizada **después de asignarle `consulta`**. No se divulgó la contraseña y sigue pendiente una auditoría automatizada de casos negativos.
- **C10 — Sesiones web: IMPLEMENTACIÓN PARCIAL COMPROBADA (13-11-2026).** El código del frontend usa sesión PHP con JWT del lado servidor, regeneración del ID al autenticarse, logout local y controles de cookie (`HttpOnly`, `SameSite`, `Secure` cuando exista HTTPS). Las rutas `/login`, `/logout`, `/usuarios` y `/roles-usuarios` y la protección CSRF de formularios administrativos constan en `6_contracts.md` §9.4 y [REGISTRO_FASE6_FRONTEND.md](REGISTRO_FASE6_FRONTEND.md). Se probó denegación por revocación en navegador; **falta** prueba independiente del cierre/cookie, CSRF malformado y API apagada.
- **C11 — Cierre heredado de V2:** [NECESITA VERIFICACIÓN: la documentación del 29-10 registra C4 (retiro de padres con hijos), C11 (cliente HTTP sin cURL), regresión final, PR/tag; el usuario confirmó integración backend PR #15. Revisar qué se resolvió posteriormente y no reabrir versiones cerradas sin acuerdo].
- **C12 — Separación entre rol de seguridad y rol investigativo:** **ACLARADO**: `rol_usuario` asigna permisos del sistema; `participa_grupo.rol` y `participa_semillero.rol` describen participación académica y no autorizan acciones por sí mismos.

**Registro de acuerdo y ejecución:** el equipo aprobó el alcance y ya se modificó SQL/código de la fase 1 en la rama local; existe evidencia de ejecución manual. Esto **no equivale** a validación del profesor, prueba total de seguridad ni aprobación formal de todas las compuertas SDD. Véanse D-v3-09 y D-v3-16 en `4_research.md`.

> **Regla de la compuerta:** el equipo decide y escribe cada respuesta aquí, después armoniza `3_plan.md`, `5_data_model.md` y `6_contracts.md`. Las alternativas que aparecen en borradores NO autorizan implementación automática.

## 8. Flujos verificables de usuario (inspirados en el formato del ejemplo del profesor)

| Flujo | Actor | Situación inicial | Resultado esperado | RF |
|---|---|---|---|---|
| F1. Inicio de sesión | Administrador/consulta | Cuenta activa con contraseña válida | Respuesta 200 y JWT; menú del rol al iniciar sesión web | RF-v3-01/02/09 |
| F2. Acceso anónimo | Sin sesión | Petición a recurso protegido | 401 antes de consultar el recurso | RF-v3-03/04 |
| F3. Lectura permitida | Consulta | JWT válido y rol vigente | GET respeta formato y códigos heredados | RF-v3-04/05/12 |
| F4. Escritura denegada | Consulta | JWT válido | 403 sin alteración de BD | RF-v3-05 |
| F5. Administración | Admin | JWT válido y rol vigente | CRUD permitido de las tres tablas de seguridad | RF-v3-06/07/08 |
| F6. Cierre de sesión | Admin/consulta | Sesión web iniciada | Frontend descarta la sesión; no asegura revocación del JWT copiado | RF-v3-10 |
| F7. Primer arranque y reintento | Operador autorizado | Base nueva o volumen preexistente | Roles + administrador creados de forma idempotente, sin contraseña predeterminada | RF-v3-00 |

Los flujos F3/F4 necesitan la aprobación expresa de la matriz C4; F5 requiere definir C8 (relación sin `activo`) y C9 (contraseñas).

## 9. Glosario mínimo

- **Autenticación:** prueba de identidad; token ausente o inválido corresponde a `401`.
- **Autorización:** permiso del usuario autenticado para una ruta y método; falta de permiso corresponde a `403`.
- **JWT:** token firmado; el frontend lo transporta como Bearer, nunca como contraseña persistida en base.
- **Rol de acceso:** valor de `rol.nombre`, asociado por `rol_usuario` y consultado solo si usuario y rol están activos.
- **Rol investigativo:** texto de `participa_grupo.rol` o `participa_semillero.rol`; no concede permisos web.
- **Regresión:** ejecutar los criterios de v1/v2 con credenciales válidas de la v3, sin cambiar sus contratos de negocio.

## 10. Definición de TERMINADA

La v3 NO se declara terminada por tener el login funcionando. Deben existir en los repositorios separados **API y pantallas**; superarse las tres compuertas del SDD; ejecutarse la matriz de permisos con pruebas directas HTTP y de frontend; verificarse idempotencia del administrador inicial; comprobarse la regresión de los 16 recursos previos y la ausencia de secretos en Git; cerrarse mediante PR revisado y tag `v3` en `main`. Los criterios 0–14 del §6 deben tener evidencia real en [7_quickstart.md](7_quickstart.md) y revisión humana de [9_checklist.md](9_checklist.md).

**Pendientes que bloquean aprobación global:** cierre de C5 (orquestación, repetición e instalación limpia), pruebas negativas/integración restantes de C6/C8/C9/C10, clarificación heredada C11-V2, catálogo `rol` de Miguel, regresión HTTP completa de los 16 recursos y verificación formal de compuertas. **C3/C4 y el alcance de C7 ya tienen acuerdo registrado**, pero su aplicación integral debe revisarse al cierre. La existencia de este documento no aprueba la V3.
