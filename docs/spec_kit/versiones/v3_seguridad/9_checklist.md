# Checklist de requisitos y cierre — v3: Investigación

> **Actualización de evidencia — 19-11-2026, catálogo fijo `rol`:** backend con GET listado/ficha, escrituras bloqueadas con `405`/`Allow: GET`, prueba aislada con salida `6 escenarios aislados de rol aprobados` y frontend mostrando `admin`/`consulta` sin acciones. `rol_usuario` se conserva y su suite sigue en `15/15`. No se probó en esta actualización el GET de `rol` con `consulta` ni la regresión completa. Ver [REGISTRO_FASE7_CATALOGO_ROL_20261119.md](REGISTRO_FASE7_CATALOGO_ROL_20261119.md). Las firmas y compuertas humanas siguen abiertas.

> **Actualización de pruebas locales — 16-11-2026:** inicializador automático Docker con MariaDB saludable, cuenta única de ADMIN en volumen nuevo aislado y reintento sin duplicados; en el volumen habitual el inicializador finalizó sin modificar el admin existente. Se comprobó la tarjeta demo del frontend, sintaxis PHP y acceso real cuando coincidían las credenciales. Ver [REGISTRO_BOOTSTRAP_Y_LOGIN_DEMO_20261116.md](REGISTRO_BOOTSTRAP_Y_LOGIN_DEMO_20261116.md). **El checklist de aceptación y las firmas humanas siguen abiertos.**

> **Evidencia nueva — 13-11-2026:** pruebas manuales de frontend de `usuario` y `rol_usuario`, control de menú, restricción directa, revocación de sesión con rol retirado, SQL del único administrador activo y recuperación de acceso al restaurar `consulta`. Véase [REGISTRO_FASE6_FRONTEND.md](REGISTRO_FASE6_FRONTEND.md). **No se firman compuertas, ni se aprueba la regresión completa, ni se considera terminada la V3 por este registro.**

> **Actualización de evidencia — 12-11-2026, fase 5:** gestión principal de `rol_usuario` comprobada por **15/15** pruebas aisladas y HTTP real: asignación `201`, ficha `200`, duplicado `409`, retiro `200`, ficha posterior `404`. Un usuario `consulta` real obtuvo GET V1 `200`, POST V1 `403` y GET administrativo `403`; tras retirar el rol, **el mismo JWT** pasó a GET `403`. SQL confirmó usuario sin rol aún activo y `ADMIN` con `admin`. La protección del último administrador **queda pendiente de prueba final solo en base descartable**, sin arriesgar el volumen local. Ver [REGISTRO_FASE5_ROL_USUARIO.md](REGISTRO_FASE5_ROL_USUARIO.md).

> **No es autoaprobación de IA.** Lo revisa el equipo en tres momentos: (1) antes de programar, para verificar calidad de la spec; (2) durante pruebas; (3) antes del tag v3. Las casillas **de aprobación humana** no están marcadas; los resultados de pruebas ya ejecutadas se registran por separado.

> **Nota de evidencia actualizada al 12-11-2026:** Fase 1: bootstrap manual local; Fase 2: **15/15** pruebas de login/JWT y login real HTTP 200; Fase 3: **28/28** pruebas del middleware y HTTP real `401` sin token / `200` con Bearer para `area_conocimiento`; **Fase 4 (`usuario`): 28/28 pruebas aisladas, CRUD principal probado por HTTP y MariaDB**. La evidencia está en los registros de las cuatro fases. **No** se marcan por IA los criterios amplios ni las compuertas humanas; siguen pendientes `rol` de Miguel, pruebas negativas adicionales de `rol_usuario`, último administrador en BD descartable, frontend, regresión completa e instalación limpia; `consulta` real y el flujo principal de `rol_usuario` ya cuentan con evidencia.

## I. COMPUERTA PREVIA — calidad de requisitos (antes de escribir código)

Esta parte adapta `9_checklist.md` del ejemplo `v3_segundo_motor`: valida **documentos, no funcionalidad ejecutada**. Una persona del equipo debe firmarla. No se programa con una clarificación decisiva abierta. **Ninguna casilla está preaprobada por la IA.**

## A. Calidad de la especificación (COMPUERTA 3)

- [ ] Cada requisito de `2_spec.md` es único, medible y permite una prueba concreta.
- [ ] C1–C13 están resueltas o verificadas por escrito y se reflejan en `3_plan.md`, `5_data_model.md`, `6_contracts.md` (C3 precisa el catálogo fijo `admin`/`consulta`, solo GET; C13 afecta la interfaz, no el esquema).
- [ ] La política de permisos para las 16 rutas y los 3 recursos administrativos está aprobada y sin huecos.
- [ ] Los nombres y tipos del script `init(4).sql` están documentados en `5_data_model.md`.
- [ ] Está previsto cotejar el esquema de la BD en ejecución con ese script antes de implementar.
- [ ] El significado exacto de logout no induce a creer que `session_destroy()` revoca un JWT válido.
- [ ] La decisión de **sembrar roles y crear el primer admin por PHP solo CLI** está registrada coherentemente. **La ejecución manual, repetición, volumen nuevo aislado y arranque automático Docker ya fueron observados**; faltan auditorías de errores y revisión del equipo.
- [ ] El procedimiento del primer admin es seguro, idempotente y verificable; no existen secretos fijos en el código ni una ruta HTTP pública de bootstrap. **La demo opcional puede mostrar credenciales deliberadamente, solo con datos ficticios y configuración privada.**
- [ ] Se definieron los escenarios de prueba para roles existentes, cuenta administrativa previa/inactiva y un usuario del mismo nombre sin rol; su ejecución se reserva para la compuerta de cierre.
- [ ] Se mantiene el alcance de v4 fuera de esta versión.
- [ ] La constitución del equipo está vigente y es compatible con el plan.

## A.1. Comprobaciones de coherencia entre documentos (profesor V3)

- [ ] Cada RF tiene una prueba con resultado medible y un contrato concreto, o una razón documentada de por qué no es HTTP (por ejemplo, bootstrap CLI).
- [ ] Cada ruta y código de `6_contracts.md` está cubierto por una tarea de `8_tasks.md` y por evidencia en `7_quickstart.md`.
- [ ] Ninguna pantalla del frontend inventa una ruta administrativa inexistente.
- [ ] Los ejemplos de JSON usan los campos exactos del SQL y no incluyen `password` en respuestas.
- [ ] La base activa coincide con `5_data_model.md` o la divergencia tiene migración aprobada y reversible.
- [ ] El alcance no contiene PostgreSQL, `MOTOR`, dashboard, PWA, publicación ni otra versión futura.
- [ ] `4_research.md` consigna alternativas, decisión y consecuencias para cada tema importante, sin convertir propuestas pendientes en acuerdos.


## Evidencias parciales registradas (no constituyen firma de las compuertas)

| Tema | Evidencia comunicada | Qué NO demuestra aún |
|---|---|---|
| Bootstrap de `admin` y `consulta` | Alta CLI, Docker automático, volumen nuevo aislado y segundo intento, JOIN/COUNT en MariaDB | Fallo seguro ante configuración inválida, base V2 poblada y adaptación a AlwaysData |
| Tarjeta de demostración | Login PHP sin error de sintaxis, aviso visible y acceso ADMIN cuando las credenciales coincidieron con DB | Modo oculto, entorno incompleto, auditoría de Git y uso público seguro |
| Login / JWT | 15/15 suite, HTTP 200 de `ADMIN`, JWT de 3600 s | Todos los casos HTTP de errores y seguridad de secretos en Git |
| Middleware / roles | 28/28 suite; 401 sin Bearer y 200 con Bearer en `/api/area_conocimiento`; catálogo `rol` con GET 200 y escrituras 405 `Allow: GET` con token admin | Matriz real de 16 CRUD y GET `/api/rol` con usuario `consulta` → 403 |
| CRUD `usuario` | 28/28 suite; GET/POST/PUT/PATCH/PATCH password/DELETE por HTTP; 409 duplicados; 401 sin JWT; SQL `activo=0` y formato hash `$2y$` | Rechazo del último admin en base real, `password_verify()` directo de clave nueva, 403 con `consulta`, cobertura de 422 y regresión |
| Backend sintáctico | `php -l` sin errores en los archivos registrados | Ausencia de errores de lógica o regresiones |

**Fuentes:** [REGISTRO_FASE1_ADMIN.md](REGISTRO_FASE1_ADMIN.md), [REGISTRO_FASE2_LOGIN.md](REGISTRO_FASE2_LOGIN.md), [REGISTRO_FASE3_MIDDLEWARE.md](REGISTRO_FASE3_MIDDLEWARE.md) y [REGISTRO_FASE4_USUARIO.md](REGISTRO_FASE4_USUARIO.md). Los checks de B–G siguen abiertos cuando agrupan varios escenarios y no hay evidencia completa de todos ellos.

## II. COMPUERTA DE CIERRE — código y pruebas (solo después de implementar)

Las secciones B–G **no cuentan** para aprobar anticipadamente la calidad de la spec; se marcan después de ejecutar pruebas y guardar evidencia.

## B. Autenticación y seguridad de token

- [ ] `POST /api/login` verifica usuario activo y contraseña mediante `password_verify()`.
- [ ] Los hashes se generan mediante `password_hash(..., PASSWORD_DEFAULT)`.
- [ ] Login correcto devuelve el JWT definido en contratos, y errores no revelan la existencia de cuentas.
- [ ] No existen contraseñas ni hashes en ninguna respuesta JSON, lista o detalle de usuario.
- [ ] Token sin firma válida, malformado, algoritmo ajeno o expirado es rechazado con 401.
- [ ] Peticiones sin Bearer a rutas protegidas responden 401.
- [ ] `JWT_SECRET` no se expone ni queda en código o Git; `.env.example` usa valores ficticios.
- [ ] Ningún error 500 muestra contraseñas, SQL, firma o datos sensibles.
- [ ] Se ha probado comportamiento de usuario retirado y rol revocado conforme a la decisión C6.

## C. Autorización de API

- [ ] La comprobación de autenticación y rol se realiza **antes** de invocar al controlador.
- [ ] Una cuenta de consulta accede a los GET autorizados de v1 y v2.
- [ ] Una cuenta de consulta recibe 403 al intentar POST de cualquiera de los 16 recursos previos.
- [ ] Una cuenta de consulta recibe 403 en PUT, PATCH y DELETE de cualquiera de los 16 recursos.
- [ ] La denegación no causa escrituras parciales ni cambios en BD.
- [ ] Solo admin puede llamar a los endpoints de `usuario` y `rol_usuario`, y consultar por GET el catálogo `rol`; `consulta` debe recibir `403` al intentar acceder a `rol`.
- [ ] Un usuario sin permiso no obtiene éxito por escribir directamente la URL o fabricar una petición HTTP.
- [ ] El router distingue 401/403 de los errores preexistentes de negocio 400/404/409/422.

## D. Administración y modelo

- [ ] `usuario`: listar, consultar, crear, reemplazar, actualizar y retirar conforme a contrato real.
- [ ] `rol`: listar y consultar únicamente; `POST`, `PUT`, `PATCH` y `DELETE` responden `405` con `Allow: GET` para admin.
- [ ] `rol_usuario`: operaciones aprobadas cubren alta, consulta y retiro del vínculo sin violar FK; PUT/PATCH responden `405`.
- [ ] Se han probado llaves duplicadas y violaciones de integridad con respuesta contractual.
- [ ] Usuario retirado conserva fila con `activo=FALSE` y no sale en GET normal. El catálogo `rol` conserva `activo` en el DDL, pero no ofrece una operación API para editarlo o retirar roles.
- [ ] Ningún POST/PUT/PATCH modifica `activo` directamente si no está permitido por contrato.
- [ ] La retirada de una asignación `rol_usuario` o de un usuario no deja acceso no autorizado ni integridad incoherente.
- [ ] El `init.sql` conserva estructura original salvo ajustes mínimos justificados y numerados; los roles `admin` y `consulta` quedan agregados como **semillas no sensibles**.
- [ ] Los roles semilla se crean en una BD nueva y se aseguran de forma idempotente en un volumen existente, sin `down -v` ni duplicados.
- [ ] El primer administrador está creado con hash, asociación por ID real, transacción y datos suministrados fuera de Git.
- [ ] Repetir el arranque no reescribe su contraseña, no crea otro administrador y no conserva el secreto de bootstrap innecesariamente.
- [ ] Sin contraseña inicial configurada, no se crea cuenta insegura ni contraseña predecible; el mensaje de error no expone secretos.

### D.3. Evidencia nueva del primer administrador (15-11-2026; no firma la sección D)

- [x] `docker compose up -d --build`: MariaDB saludable, servicio `inicializar-admin` completado y API/frontend iniciados.
- [x] Volumen habitual: mensaje de administrador existente sin duplicación ni cambio intencional de contraseña.
- [x] Proyecto aislado sin volumen anterior: mensaje de creación exitosa, `ADMIN` activo con rol `admin`, contraseña almacenada en columna de hash de longitud 60 y **una sola** fila de usuario.
- [x] Repetición aislada con `run --rm --no-deps`: mensaje de administrador existente; no creó otra fila.
- [x] `down` del proyecto aislado **sin `-v`**: comprobado que los servicios habituales seguían activos.
- [ ] Probar el primer arranque sin las variables requeridas, roles/usuarios contradictorios y bases V2 con datos previos; comparar el hash antes/después sin imprimirlo.
- [ ] Verificar el login HTTP contra la **nueva base aislada**, no solamente contra la instalación habitual.

### D.1. Resultados parciales verificados de `usuario` (no aprueban automáticamente la sección D)

- [x] HTTP 200 en listado y ficha mediante JWT admin, sin `password` en respuesta de listado observada.
- [x] POST 201 de usuario temporal, POST con correo duplicado 409 y PATCH con username duplicado 409.
- [x] PUT y PATCH 200 con GET posterior que confirma persistencia.
- [x] PATCH de contraseña 200 (sin validar directamente el nuevo hash con la clave aleatoria).
- [x] DELETE 200, GET posterior 404, fila conservada con `activo=0`.
- [x] GET individual sin JWT devuelve 401.
- [x] Hash con prefijo `$2y$` y longitud 60; JOIN con un administrador activo (consultas de solo lectura).
- [ ] Validar rechazo **real** de retirar al último administrador en una BD descartable.
- [x] Validar parcialmente HTTP 403 para usuario `consulta` real (POST V1 y GET usuario). [ ] Completar otros métodos/rutas y casos 422.
- [ ] Comprobar directamente el hash de una contraseña recién cambiada con `password_verify()`.

### D.2. Evidencia parcial de `rol_usuario` (NO constituye cierre de sección D)

- [x] Suite aislada 15/15 y sintaxis del router/ensamblador correcta.
- [x] GET sin token 401; POST asignación 201; GET ficha 200; duplicado 409.
- [x] Cuenta `consulta` real: GET V1 200, POST V1 403 y GET `/api/usuario` 403.
- [x] DELETE relación 200, GET posterior 404, mismo JWT ya sin permiso 403.
- [x] SQL de lectura: `ADMIN` activo con rol `admin`, usuario 4 activo sin rol.
- [ ] Proteger último administrador en BD descartable; no tocar la cuenta real `ADMIN`.
- [ ] Verificar por HTTP FK inexistentes/inactivas, PUT/PATCH 405, segundo DELETE y datos inválidos.
- [ ] Probar la matriz completa, incluida la denegación de `consulta` sobre `GET /api/rol`, y la regresión V1/V2. El **flujo principal del frontend** de `usuario` y `rol_usuario` cuenta con evidencia específica de 13-11-2026, y el catálogo `rol` tiene evidencia acotada del 19-11-2026; ninguna de estas pruebas cubre toda esta sección.

**Fuente:** `REGISTRO_FASE5_ROL_USUARIO.md`; no se marcan por IA las compuertas de aprobación humanas.


### D.4. Evidencia del catálogo fijo `rol` (19-11-2026; no firma la sección D)

- [x] Suite aislada de `/app/pruebas/prueba_rol_aislada.php`: sintaxis correcta y salida `6 escenarios aislados de rol aprobados`.
- [x] Suite de regresión aislada de `rol_usuario`: `15 / 15` escenarios; no se modificó el módulo.
- [x] `GET /api/rol` con token admin: `200`.
- [x] `GET /api/rol/1`: `200`, nombre `admin`; `GET /api/rol/2`: `200`, nombre `consulta` (IDs observados solo en la instancia local).
- [x] `POST /api/rol`: `405`, cabecera `Allow: GET`.
- [x] `PUT`, `PATCH` y `DELETE /api/rol/999999`: `405`, cabecera `Allow: GET`; DELETE se repitió con el encabezado Bearer.
- [x] Pantalla del catálogo: dos filas (`admin` y `consulta`), columnas ID/Nombre/Descripción y sin botones de escritura.
- [ ] Probar por HTTP `GET /api/rol` con una cuenta `consulta` y confirmar `403`; la política está definida por C4, pero no se ejecutó en esta actualización.
- [ ] Completar la regresión global de v1/v2 y las demás comprobaciones de cierre.

**Fuente:** [REGISTRO_FASE7_CATALOGO_ROL_20261119.md](REGISTRO_FASE7_CATALOGO_ROL_20261119.md). Los IDs 1/2 y 999999 son valores de la prueba local, no constantes para otras instalaciones.

## E. Frontend y separación

- [x] Login PHP llama a la API por HTTP, según código inspeccionado y autenticación real de `ADMIN`/`consulta`; la **revisión integral** de conexiones del frontend permanece pendiente.
- [x] El JWT queda en `$_SESSION` y se incluye como Bearer: **código inspeccionado**; C11-V2 sobre streams/cURL **sigue sin cerrarse**.
- [x] Se llama a `session_regenerate_id(true)` después del login correcto: **revisado en código**, falta inspección de cookie en navegador.
- [ ] Logout limpia sesión y cookie en código; **pendiente verificar en navegador** que ninguna llamada posterior envía el token antiguo.
- [x] El menú `consulta` no presenta escritura ni acceso a gestión; visita directa a `/roles-usuarios` fue rechazada.
- [ ] El menú admin muestra usuarios, catálogo `Roles` de solo lectura y `Roles por usuario`; la pantalla de catálogo no debe mostrar acciones de escritura.
- [x] Error de permiso visible en español tras revocar un rol y mensaje genérico de login sin rol: **casos concretos probados**, no toda la matriz 401/403.
- [x] Los formularios de contraseña revisados no reimprimen los valores tras un error: **revisión de código**, no auditoría de todas las vistas.
- [ ] No hay PDO, credenciales MariaDB ni `require` de código backend en frontend.
- [ ] Con la API caída, frontend continúa respondiendo sin datos y con aviso.

### E.1. Pruebas manuales de frontend registradas el 13-11-2026

- [x] Alta de usuarios (`mario`, `mariana`, `prueba_delete_v3`) y necesidad de asignar un rol antes del login.
- [x] Edición parcial de perfil y cambio de contraseña en cuenta de prueba, sin guardar la clave en evidencias.
- [x] Desactivación de `prueba_delete_v3`: no aparece en listado activo y MariaDB conserva la fila con `activo=0`.
- [x] Alta de `rol_usuario` de Mariana con `consulta`, rechazo visual de duplicado y posterior retirada/reasignación.
- [x] SQL `rol_usuario` sin filas para Mariana justo después del retiro; cuenta sin rol no puede iniciar una sesión nueva.
- [x] Sesión abierta de Mariana pierde acceso tras retirar `consulta`; recuperación después de reasignación y nuevo login con **218** registros visibles.
- [x] Protección visual del último administrador y SQL que muestra a `ADMIN` como único admin activo.
- [ ] Rechazo HTTP real a retiro del último admin en **BD descartable**; no poner en riesgo `ADMIN` del volumen habitual.
- [ ] API caída, validación adversarial de CSRF, comprobación del selector dentro de `/roles-usuarios` y regresión general V1/V2.

**Evidencia y límites:** [REGISTRO_FASE6_FRONTEND.md](REGISTRO_FASE6_FRONTEND.md). Las casillas E.1 certifican solo pruebas acotadas o inspección de código, **no** la aceptación de toda la sección E.

### E.2. Tarjeta de acceso académico (15-11-2026)

- [x] `front_php/vistas/autenticacion/login.php` no presenta errores sintácticos según `php -l` dentro de Docker.
- [x] Aviso de «Acceso de demostración» visible cuando `LOGIN_DEMO_ENABLED=1` con usuario y clave informados por el entorno.
- [x] Credenciales correspondientes a una cuenta administrativa real permiten iniciar sesión y ver el panel.
- [x] Se observó un rechazo de login al mostrar un nombre que no existía en la BD: el aviso no crea ni modifica cuentas.
- [ ] Comprobar tarjeta oculta con `LOGIN_DEMO_ENABLED=0` o datos incompletos y revisar que ninguna credencial haya sido commiteada.
- [ ] Validar en un entorno **aislado, sin información real** el acceso administrativo público previsto para la evaluación académica.

## F. Regresión v1-v2

- [ ] Siguen disponibles los seis CRUD de v1 y los diez de v2, con sus distintos métodos permitidos.
- [ ] Las peticiones autorizadas conservan 200/201/204, PUT/PATCH y borrado lógico.
- [ ] Los duplicados y FK inválidas conservan 409 según contratos previos.
- [ ] Los cuerpos inválidos conservan 422; PATCH `{}` en controladores V2 documentados conserva **422**, mientras V1 conserva **400** donde su contrato lo exige.
- [ ] Los listados y desplegables solo ofrecen filas activas.
- [ ] Las pruebas de capas siguen funcionando sin MariaDB donde corresponda.

- [ ] Las cinco asociaciones simples V2 (`grupo_linea`, `semillero_linea`, `ac_linea`, `ods_linea`, `aa_linea`) devuelven **405** a PUT/PATCH de admin; las rutas con claves compuestas siguen tomando ambos segmentos en orden.
- [ ] Las dos participaciones V2 conservan sus campos `rol`, `fecha_inicio` y `fecha_fin`; `rol` investigativo no concede acceso administrativo.

## G. Documentación, Git y cierre

- [ ] Se resolvió o dejó debidamente aprobada la aclaración C11-V2 sobre uso de streams frente a cURL obligatorio del curso.
- [ ] Se verificaron las compuertas V2 marcadas pendientes al 29-10, sin asumir que siguen abiertas si el equipo ya las cerró.


- [ ] `7_quickstart.md` tiene URLs, puertos, nombres de servicios y comandos ejecutados realmente.
- [ ] La documentación distingue el arranque automático Docker de la ejecución CLI manual necesaria en hosting sin Docker, y el modo demo expone credenciales **solo** en una instalación desechable.
- [ ] Hay evidencias de todas las pruebas principales y no contienen credenciales ni tokens completos.
- [ ] El mapa de versiones coincide con el alcance autorizado para v1-v4.
- [ ] `2_spec.md`, `3_plan.md`, `5_data_model.md` y `6_contracts.md` describen `rol` como catálogo fijo de solo lectura, sin restos de requisitos CRUD.
- [ ] `8_tasks.md` refleja los trabajos realizados y responsables reales.
- [ ] PR revisados, commits individuales claros, y solo el integrador integró a `main`.
- [ ] Se creó tag `v3` en `main` únicamente después de pasar criterios y regresión.

---

**Revisó:** ____________________  **Fecha:** ______________

**Clarificaciones pendientes:** ___________________________________________

**Evidencia de pruebas:** _________________________________________________

**Observaciones para v4 (no implementar aquí):** __________________________

**Registro de revisión humana**

| Revisión | Responsable | Fecha | Evidencia / observaciones |
|---|---|---|---|
| Compuerta 1: clarificaciones de `2_spec.md` | Por asignar | — | — |
| Compuerta 2: constitución vigente artículo por artículo | Por asignar | — | — |
| Compuerta 3: requisitos antes de programar | Por asignar | — | — |
| Aceptación funcional y regresión V1/V2 | Por asignar | — | — |
| PR + tag `v3` en `main` | Por asignar | — | — |

> **No firmada / no cerrada a fecha de esta revisión.** Mantener el estado en blanco hasta obtener evidencias reales.
