# Tareas — v3: Seguridad (Investigación)

> **Actualización — 13-11-2026, fase 6 frontend (Ángel):** navegador y MariaDB confirmaron altas/edición/contraseña/baja lógica de usuarios, asignación/duplicado/retiro/restauración de roles, menú `consulta`, denegación de ruta administrativa y revocación de acceso con la sesión ya abierta. `ADMIN` es el único administrador activo según SQL; el frontend lo muestra protegido, **sin ensayar su eliminación real**. Se restauró el rol de Mariana después de las pruebas. Véase [REGISTRO_FASE6_FRONTEND.md](REGISTRO_FASE6_FRONTEND.md). Este registro **no cierra** CRUD `rol`, regresión, SDD ni la V3 global.

> **Actualización de evidencia — 12-11-2026, fase 5:** gestión principal de `rol_usuario` comprobada por **15/15** pruebas aisladas y HTTP real: asignación `201`, ficha `200`, duplicado `409`, retiro `200`, ficha posterior `404`. Un usuario `consulta` real obtuvo GET V1 `200`, POST V1 `403` y GET administrativo `403`; tras retirar el rol, **el mismo JWT** pasó a GET `403`. SQL confirmó usuario sin rol aún activo y `ADMIN` con `admin`. La protección del último administrador **queda pendiente de prueba final solo en base descartable**, sin arriesgar el volumen local. Ver [REGISTRO_FASE5_ROL_USUARIO.md](REGISTRO_FASE5_ROL_USUARIO.md).

> Orden sugerido: especificar → comprobar el esquema → implementar por capas → probar API y frontend → regresión → tag. Asignar responsables y commits del equipo; las casillas de **aceptación final** permanecen abiertas hasta revisión del equipo. Algunas tareas con evidencia ejecutada se marcaron como completadas de forma **acotada**; ello no equivale a cerrar una fase ni aprobar compuertas SDD. Ver registros de fases 1, 2, 3, 4 y 5.

## Fase 0 — Compuertas SDD y base v2

> **Estado heredado:** la V2 documenta diez entidades implementadas, pero aún tenía pendientes de cierre al 29-10-2026. Confirmado PR #15 en backend por información del usuario; las verificaciones restantes se comprueban con el equipo y no se marcan solas como aprobadas.


- [ ] Confirmar el cierre de v2: `tag v2` existente y contratos de v1/v2 funcionales.
- [ ] Identificar diferencias entre mapa de versiones antiguo y metodología; actualizar mapa del equipo con aprobación.
- [x] Examinar `init(4).sql` y registrar su DDL de seguridad en `5_data_model.md`. [ ] Contrastar el resultado con `SHOW CREATE TABLE` en MariaDB activa.
- [x] Registrar el acuerdo para roles semilla `admin` y `consulta`, y primer administrador mediante script PHP solo CLI (9-11-2026; diseño, no prueba).
- [x] Confirmación del estudiante: equipo aprobó matriz admin/consulta, roles múltiples, bootstrap manual, logout en sesión PHP y JWT de 60 minutos. [ ] Precisar diseño técnico de cookies/CSRF, claims opcionales y automatización Docker.
- [ ] Resolver C2–C12 de `2_spec.md` y comprobar en ejecución la concordancia del esquema; **C1 está resuelta para el SQL fuente**.
- [ ] Validar `3_plan.md` contra la constitución vigente del equipo.
- [ ] Revisar la lista de requisitos de `9_checklist.md` entre compañeros.
- [ ] Ajustar contratos con los campos, claves y rutas definitivas.

**Verificación:** especificación y contratos revisados, ningún `[NECESITA ACLARACIÓN]` bloqueante.

## Fase 1 — Infraestructura y secretos

- [x] Añadir `JWT_SECRET` al entorno de la API (no frontend). **Evidencia local:** longitud 64 en el contenedor; falta revisión de Git.
- [ ] Mantener `.env` en `.gitignore` y ejemplos seguros en `.env.example`.
- [ ] Verificar que no haya secretos reales en Git ni contraseñas de usuario en SQL de prueba versionado.
- [x] Confirmar arranque Docker con MariaDB saludable tras `docker compose up -d --build` en el volumen local. **Pendiente:** regresión de datos v1/v2 e instalación desde cero.
- [ ] Añadir a `db/init.sql` los INSERT de roles `admin` y `consulta` y numerar/justificar el cambio de semillas en la cabecera del script; **no** incluir usuarios ni contraseñas.
- [ ] Crear `api_investigacion/scripts/inicializar_admin.php` solo CLI; leer `ADMIN_INICIAL_USERNAME`, `ADMIN_INICIAL_EMAIL` y `ADMIN_INICIAL_PASSWORD` desde el entorno y crear el hash con `password_hash(PASSWORD_DEFAULT)`.
- [ ] Hacer la creación de cuenta y asignación en `rol_usuario` atómica, con SQL preparado y consulta de IDs reales (nunca asumir `rol_id = 1`).
- [ ] Garantizar idempotencia; si hay administrador existente no crear ni sobrescribir credenciales. Fallar sin privilegios ante datos preexistentes contradictorios.
- [ ] Para volúmenes existentes, asegurar roles idempotentemente por `nombre` sin ejecutar `down -v`; probar volumen con datos v2 conservados.
- [ ] Integrar el script con el arranque `docker compose up -d --build` tras disponibilidad de MariaDB y documentar su ejecución real; el secreto debe quedar fuera de Git, logs y capturas.
- [ ] Comprobar que no existen rutas HTTP de bootstrap o registro público; documentar recuperación inicial y retirada del secreto temporal tras la creación.

**Verificación:** el bootstrap **manual** ya creó un admin con rol en MariaDB; sigue pendiente repetirlo, probar instalación limpia, comprobar secreto fuera de Git y validar el arranque en un solo comando.

### Evidencia parcial de Fase 1 (no marcar todo como cerrado)

- **Realizado y observado:** bloque SQL con roles en `git diff`; cuatro archivos PHP sin errores de sintaxis; mensaje de alta `Administrador inicial creado y rol asignado correctamente`; SELECT de los dos roles y asociación del usuario `ADMIN` con `admin`.
- **Sin prueba todavía:** idempotencia por segunda ejecución, instalación limpia, prueba segura del hash con `password_verify`, comportamiento sin variables, recuperación ante estado ambiguo, commit/PR, integración automática al arranque y ausencia de secretos en Git.
- Evidencia detallada: [REGISTRO_FASE1_ADMIN.md](REGISTRO_FASE1_ADMIN.md).

## Fase 2 — Modelos, interfaces y repositorios

- [ ] Modelo `Usuario` sin exponer `password` en serialización.
- [ ] Modelos `Rol` y `RolUsuario` acordes al DDL real.
- [ ] Interfaces de servicios y repositorios para los tres recursos.
- [ ] Repositorio de usuarios con búsqueda por identificador de login y estado activo.
- [ ] Repositorio de roles con lectura de roles vigentes.
- [ ] Repositorio de asignaciones con manejo de FK, duplicados y retirada según C8.
- [ ] Borrado lógico en usuario/rol; validar coherencia con dependencias.
- [ ] Agregar dependencias al ensamblador.

**Verificación:** casos de repositorio mediante datos de prueba y errores de integridad traducidos.

## Fase 3 — Login y JWT

- [ ] `password_hash()` al crear/cambiar contraseña y `password_verify()` al login.
- [x] Codificación Base64URL, HS256 y verificación con `hash_equals` (archivo `JwtHS256.php` recibido y suite de login 15/15).
- [x] Rechazo de token vencido, alterado, algoritmo `none` y firma inválida **en suite automatizada 15/15**; HTTP end-to-end de cada variante pendiente.
- [ ] `POST /api/login`: **HTTP 200 real** comprobado, `401` en primer intento sin causa exacta; **15/15 suite**. Aún falta validar todos los errores 401/422/500 por HTTP.
- [ ] Sin contraseñas, hashes, JWT ni secretos completos en respuestas de error o logs.

**Verificación parcial realizada:** 15/15 suite y login HTTP real; faltan controles end-to-end de usuario inactivo, errores y secrecía en Git.

## Fase 4 — Autorización central

- [ ] Inventariar todos los métodos y rutas de los 16 recursos previos.
- [x] Implementar guarda de autenticación y rol antes del controlador (`index.php` confirmado por `Select-String`; 28/28 suite; HTTP 401/200 en `area_conocimiento`).
- [ ] Proteger los 3 CRUD administrativos exclusivamente para admin.
- [ ] `401` **comprobado por HTTP** sin token; `403` y ausencia de escrituras **solo en suite automatizada**, pendientes pruebas reales con `consulta`.
- [ ] Bloquear escrituras de `consulta` en todos los CRUD de v1/v2.
- [x] Comprobar en suite automatizada que retirar el rol `admin` elimina privilegios y usuario desactivado pierde autenticación; falta prueba de integración HTTP con cambio real de BD.
- [ ] Mantener excepciones/rutas originales de v1 y v2 sin alteraciones no acordadas.

**Verificación parcial realizada:** 28/28 suite y 401/200 real en `area_conocimiento`; falta matriz HTTP completa por verbo/recurso y usuario `consulta` real.

### Registro parcial de la autorización (12-11-2026)

| Evidencia | Resultado | Límite |
|---|---|---|
| `prueba_login_jwt.php` | **15/15** | No sustituye pruebas HTTP negativas exhaustivas |
| `POST /api/login` con `ADMIN` | **200**, rol `admin`, **3600 s** | Solo un usuario real probado |
| `prueba_middleware_autorizacion.php` | **28/28** | No sustituye las 16 rutas end-to-end |
| `GET /api/area_conocimiento` sin Bearer | **401** | Solo una ruta comprobada por HTTP |
| Misma GET con JWT válido de `ADMIN` | **200** | Prueba positiva de admin, no de `consulta` |

**Decisión de seguimiento:** iniciar CRUD `usuario` y `rol_usuario` sin dar por terminadas las tareas de regresión y matriz HTTP. `rol` sigue asignado a Miguel Angel Lotero Alvarez. Evidencia detallada en `REGISTRO_FASE2_LOGIN.md` y `REGISTRO_FASE3_MIDDLEWARE.md`.

## Fase 5 — CRUD administrativo API

- [x] Rutas específicas `usuario`: GET listado/individual, POST, PUT, PATCH, DELETE **implementadas y probadas por HTTP en MariaDB local**; `PATCH /api/usuario/{id}/password` probado con 200. Casos negativos restantes pendientes.
- [ ] Rutas específicas `rol`: GET listado/individual, POST, PUT, PATCH, DELETE.
- [x] Flujo principal `rol_usuario` GET/POST/DELETE implementado y probado por HTTP (15/15 aisladas, 201/200/409/200/404); aceptación de último admin y casos negativos pendiente.
- [ ] Validación de JSON, campos, llaves y listas blancas según esquema.
- [ ] Tratamiento de 204/400/404/409/422/500 sin filtración de SQL.
- [ ] Pruebas contra el uso directo de las rutas por `consulta` y usuarios sin token.

**Verificación parcial realizada:** CRUD `usuario` probado con PowerShell + JWT real y MariaDB: 28/28 suite aislada, HTTP 200/201/404/409/401 y `activo=0` tras DELETE; POST observado sin `password` en JSON. **No** se probó Postman ni se completaron los otros dos CRUD ni la matriz completa de roles. Fuente: `REGISTRO_FASE4_USUARIO.md`.

### Evidencias concretas de Fase 5 — `usuario` (12-11-2026)

- [x] Consultar listado y ficha de usuarios con JWT admin (HTTP `200`).
- [x] Crear usuario de prueba con JWT admin (HTTP `201`, ID `2` solo local); sin hash en la respuesta observada.
- [x] Cambiar perfil por `PUT` y `PATCH` y comprobar persistencia con GET posterior (`200`).
- [x] Rechazar `username` y correo duplicados (`409` observado).
- [x] Cambiar contraseña por ruta específica (`200`); falta validación directa posterior con contraseña nueva.
- [x] Retirar lógicamente usuario de prueba (`200`), verificar `404` en GET posterior y `activo=0` en MariaDB.
- [x] Rechazar GET `/api/usuario/2` sin JWT (`401`).
- [x] Inspeccionar solo **formato** de hashes (`$2y$`, 60 caracteres) y contar un administrador activo mediante SQL de lectura.
- [ ] Probar en una base descartable que no se puede retirar al **último** administrador (la prueba aislada pasa, la base real no se puso en riesgo).
- [ ] Verificar `password_verify()` sobre la contraseña recién cambiada en una cuenta con rol activo de prueba.
- [x] Verificar parcialmente permisos `403` con usuario `consulta` real (POST V1, GET `/api/usuario`, y pérdida inmediata de rol). [ ] Extenderlo a todas las rutas/métodos.
- [ ] Cerrar casos negativos y protección transaccional del último admin de `rol_usuario` en BD descartable; altas/retiros principales ya comprobados.

### Evidencia parcial de fase 5 — `rol_usuario` (12-11-2026)

- [x] Docker y `php -l` del router/ensamblador sin errores.
- [x] Suite aislada de asociaciones **15/15**.
- [x] GET `/api/rol_usuario` sin token **401**.
- [x] POST asignación `201`, GET ficha `200`, POST duplicado `409`.
- [x] Usuario real `consulta`: GET V1 `200`, POST V1 `403`, GET usuario `403`.
- [x] DELETE asociación `200`, GET ficha posterior `404` y mismo JWT sin acceso `403`.
- [x] SQL posterior: usuario consulta activo sin rol y admin activo con `admin`.
- [ ] Último administrador, FK inválida, PUT/PATCH 405 y segundo DELETE por HTTP en entorno descartable.
- [ ] Integración frontend, CRUD `rol` de Miguel, regresión completa y revisión humana.

Fuente: `REGISTRO_FASE5_ROL_USUARIO.md`. Las marcas corresponden solo a hechos reportados; no aprueban toda la fase.


## Fase 6 — Frontend PHP

- [x] Formulario de login y mensajes comprensibles en español; inicio real con `ADMIN` y `consulta` comprobado en navegador.
- [x] `session_start()`, regeneración de ID tras login y conservación del JWT en sesión: **implementación revisada en `front_php/index.php`**; auditoría independiente de cookies pendiente.
- [x] Cliente HTTP incorpora Bearer desde la sesión: **código revisado**. **Pendiente:** resolver C11-V2, porque todavía usa streams HTTP y no cURL.
- [x] Menú por roles y acceso protegido también en la API: `consulta` vio solo lectura y recibió denegación al entrar directamente en gestión de roles.
- [x] Pantallas administrativas de `usuario` para alta, listado, edición PUT/PATCH, contraseña y baja lógica; vistas y persistencia comprobadas parcialmente desde el navegador y SQL.
- [ ] Pantallas de CRUD del **catálogo `rol`**: asignadas a Miguel; no atribuir su implementación a la gestión de `rol_usuario`.
- [x] Pantalla de consulta, asignación y retirada de `rol_usuario` (incluida confirmación POST + CSRF y duplicado); restauración de vínculo probada.
- [x] Logout por POST con limpieza de sesión y cookie **revisado en código**; pendiente prueba independiente de cookie/token posterior.
- [ ] Completar la matriz de 401, 403 y API caída en **todos** los formularios; la denegación de `consulta` y del usuario sin rol sí fue comprobada.
- [ ] Confirmar frontend sin PDO, sin claves BD y sin importar clases backend.

**Verificación parcial realizada:** pruebas desde navegador con `ADMIN` y Mariana (`consulta`), más SQL de solo lectura. **Pendientes:** API apagada, auditoría de separación, integración de `rol` y matriz completa. Evidencia: `REGISTRO_FASE6_FRONTEND.md`.

## Fase 7 — Pruebas y regresión

- [x] Prueba de revocación inmediata en navegador: Mariana permaneció con sesión abierta, ADMIN retiró su único rol y la página protegida dejó de mostrar registros; después de restaurar `consulta` e iniciar una sesión nueva, cargaron **218** áreas de conocimiento.
- [x] SQL de lectura: solo `ADMIN` (ID 1) figuraba con rol `admin` activo y el vínculo de Mariana (ID 6) desapareció durante el retiro; posteriormente fue restaurado. **No** se ejecutó DELETE del administrador real.

- [ ] Regresión diferenciada: los cinco recursos V2 con campos editables mantienen PUT/PATCH; las cinco asociaciones simples (`grupo_linea`, `semillero_linea`, `ac_linea`, `ods_linea`, `aa_linea`) devuelven **405** para PUT/PATCH de admin; el rol `consulta` devuelve **403** al intentar cualquier escritura.
- [ ] Comprobar la divergencia documentada: PATCH `{}` en controladores V2 revisados → **422**; casos V1 según sus contratos → **400** donde corresponde. No homogeneizar sin decisión documentada.
- [ ] Registrar qué ocurrió con C4 (retiro de padres), C11 (cURL), tag/regresión de V2 después del 29-10.


- [ ] Pruebas unitarias con repositorios falsos, sin MariaDB, de autenticación y reglas de usuario/rol.
- [ ] Pruebas HTTP: **correcto 200 / sin Bearer 401 comprobados** en `area_conocimiento`; alterado/caducado por HTTP pendientes (sí probados en suite).
- [ ] Verificar denegación real de cada escritura `consulta` sin efectos secundarios.
- [ ] Revisar que `rol_usuario` cumpla relaciones y estrategia de retiro acordada.
- [ ] Comprobar datos iniciales y borrado lógico de v1/v2.
- [ ] Ejecutar todos los tests de regresión de las versiones previas, ahora autenticados.
- [ ] Completar `7_quickstart.md` con URLs, servicios y comandos reales.
- [ ] Confirmar secretos ignorados por Git y no expuestos en respuestas.

**Verificación:** evidencia suficiente para los 14 criterios de aceptación de `2_spec.md`.

## Fase 8 — Cierre y sustentación

- [ ] Cada estudiante acredita commits propios y explica su rama.
- [ ] Revisar cruzadamente documentación, contratos y código; resolver diferencias.
- [ ] Completar `9_checklist.md`, revisado manualmente por integrante distinto.
- [ ] PR desde ramas individuales; solo el integrador hace merge a `main`.
- [ ] Crear `tag v3` sobre `main` después de todas las verificaciones.
- [ ] Actualizar mapa de versiones a «v3 hecha; v4 pendiente» tras aprobación.

**Verificación:** código y pantallas sustentables, tag en main, regresión completa.

## Matriz de trazabilidad requisito → tarea → prueba (estilo del ejemplo del profesor)

No marcar tareas hechas porque exista el archivo Markdown. Las fases de arriba solo se cierran con resultados ejecutados.

| Requisito de `2_spec.md` | Tarea/área del plan | Evidencia que debe entregarse |
|---|---|---|
| RF-v3-00 | Fase 1 — semillas, bootstrap CLI, Docker | CA0: roles y admin, repetición sin duplicados, volumen persistente |
| RF-v3-01/02/03 | Fases 2–4 — login, JWT, verificador | CA1–3: 200/401, firma, expiración, ausencia de hash |
| RF-v3-04/05/13/14 | Fase 4 — middleware de ruta y método | CA4–6/11: GET permitido, POST/PUT/PATCH/DELETE 403, 405 heredado |
| RF-v3-06/07/08 | Fase 5 — recursos admin | CA7–8: CRUD, borrado lógico y tratamiento del puente C8 |
| RF-v3-09/10/11 | Fase 6 — sesión y pantallas | CA9–10/12: Bearer, menú, logout y API caída |
| RF-v3-12 | Fase 7 — regresión | CA11: los 16 recursos según contratos v1/v2 |
| RNF-v3-02/03/04 | Fases 1/3/7 — seguridad | CA2/3/13: secreto fuera de Git, hashes, tokens inválidos |
| Metodología SDD | Fases 0/8 — compuertas y cierre | CA14: checklist firmado, PR revisado, tag v3 |

### Distribución recomendada en pareja (no asignación automática)

El login y el middleware ya están implementados parcialmente y probados en el backend local. Para las siguientes ramas se mantiene el **contrato acordado de `POST /api/login`** y la matriz de permisos; el CRUD `rol` y el frontend deben integrarse; el flujo principal de `rol_usuario` ya se integró sin reabrir arbitrariamente los contratos del login, middleware ni `usuario`. Cada estudiante realiza commits propios y el encargado de `main` integra por Pull Request. Las decisiones C3–C11 deben tener responsable y fecha de aprobación antes de empezar sus tareas dependientes.
