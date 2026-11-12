# Checklist de requisitos y cierre — v3: Investigación

> **No es autoaprobación de IA.** Lo revisa el equipo en tres momentos: (1) antes de programar, para verificar calidad de la spec; (2) durante pruebas; (3) antes del tag v3. Las casillas **de aprobación humana** no están marcadas; los resultados de pruebas ya ejecutadas se registran por separado.

> **Nota de evidencia actualizada al 12-11-2026:** Fase 1: bootstrap manual local; Fase 2: **15/15** pruebas de login/JWT y login real HTTP 200; Fase 3: **28/28** pruebas de middleware y HTTP real `401` sin token / `200` con Bearer para `area_conocimiento`. La evidencia está en los registros de fases 1, 2 y 3. **No** se marcan por IA los criterios de aceptación amplios ni las compuertas humanas; hay pruebas pendientes de usuarios `consulta`, CRUD administrativos, regresión completa e instalación limpia.

## I. COMPUERTA PREVIA — calidad de requisitos (antes de escribir código)

Esta parte adapta `9_checklist.md` del ejemplo `v3_segundo_motor`: valida **documentos, no funcionalidad ejecutada**. Una persona del equipo debe firmarla. No se programa con una clarificación decisiva abierta. **Ninguna casilla está preaprobada por la IA.**

## A. Calidad de la especificación (COMPUERTA 3)

- [ ] Cada requisito de `2_spec.md` es único, medible y permite una prueba concreta.
- [ ] C1–C12 están resueltas o verificadas por escrito y se reflejan en `3_plan.md`, `5_data_model.md`, `6_contracts.md`.
- [ ] La política de permisos para las 16 rutas y los 3 recursos administrativos está aprobada y sin huecos.
- [ ] Los nombres y tipos del script `init(4).sql` están documentados en `5_data_model.md`.
- [ ] Está previsto cotejar el esquema de la BD en ejecución con ese script antes de implementar.
- [ ] El significado exacto de logout no induce a creer que `session_destroy()` revoca un JWT válido.
- [ ] La decisión de **sembrar roles y crear el primer admin por PHP solo CLI** está registrada coherentemente. **La primera ejecución manual sí se realizó**; faltan las pruebas de repetición, instalación nueva y arranque automático.
- [ ] El procedimiento del primer admin es seguro, idempotente y verificable; no existen credenciales fijas ni una ruta HTTP pública de bootstrap.
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
| Bootstrap de `admin` y `consulta` | Alta CLI y SELECT en phpMyAdmin | Idempotencia y base nueva |
| Login / JWT | 15/15 suite, HTTP 200 de `ADMIN`, JWT de 3600 s | Todos los casos HTTP de errores y seguridad de secretos en Git |
| Middleware / roles | 28/28 suite; 401 sin Bearer y 200 con Bearer en `/api/area_conocimiento` | Matriz real de 16 CRUD y 403 con usuario real `consulta` |
| Backend sintáctico | `php -l` sin errores en los archivos registrados | Ausencia de errores de lógica o regresiones |

**Fuentes:** [REGISTRO_FASE1_ADMIN.md](REGISTRO_FASE1_ADMIN.md), [REGISTRO_FASE2_LOGIN.md](REGISTRO_FASE2_LOGIN.md), [REGISTRO_FASE3_MIDDLEWARE.md](REGISTRO_FASE3_MIDDLEWARE.md). Los checks de B–G siguen abiertos cuando agrupan varios escenarios y no hay evidencia completa de todos ellos.

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
- [ ] Solo admin puede llamar a los endpoints de `usuario`, `rol` y `rol_usuario`.
- [ ] Un usuario sin permiso no obtiene éxito por escribir directamente la URL o fabricar una petición HTTP.
- [ ] El router distingue 401/403 de los errores preexistentes de negocio 400/404/409/422.

## D. CRUD de administración y modelo

- [ ] `usuario`: listar, consultar, crear, reemplazar, actualizar y retirar conforme a contrato real.
- [ ] `rol`: listar, consultar, crear, reemplazar, actualizar y retirar conforme a contrato real.
- [ ] `rol_usuario`: operaciones aprobadas cubren alta, consulta, cambio de asignación y retiro sin violar FK.
- [ ] Se han probado llaves duplicadas y violaciones de integridad con respuesta contractual.
- [ ] Usuario y rol retirados conservan fila con `activo=FALSE` y no salen en GET normal.
- [ ] Ningún POST/PUT/PATCH modifica `activo` directamente si no está permitido por contrato.
- [ ] La baja de un rol o usuario no deja acceso no autorizado ni integridad incoherente.
- [ ] El `init.sql` conserva estructura original salvo ajustes mínimos justificados y numerados; los roles `admin` y `consulta` quedan agregados como **semillas no sensibles**.
- [ ] Los roles semilla se crean en una BD nueva y se aseguran de forma idempotente en un volumen existente, sin `down -v` ni duplicados.
- [ ] El primer administrador está creado con hash, asociación por ID real, transacción y datos suministrados fuera de Git.
- [ ] Repetir el arranque no reescribe su contraseña, no crea otro administrador y no conserva el secreto de bootstrap innecesariamente.
- [ ] Sin contraseña inicial configurada, no se crea cuenta insegura ni contraseña predecible; el mensaje de error no expone secretos.

## E. Frontend y separación

- [ ] Login PHP consume la API por HTTP, nunca se conecta a MariaDB.
- [ ] Tras login, el token queda en `$_SESSION` y se añade como Bearer en el cliente HTTP aprobado; consta decisión C11-V2 sobre cURL.
- [ ] El identificador de sesión se regenera al iniciar sesión correctamente.
- [ ] Logout limpia la sesión y la interfaz ya no envía el token.
- [ ] El menú de `consulta` no ofrece escrituras ni acceso a gestión de usuarios/roles.
- [ ] El menú de admin ofrece acceso a los CRUD de la v3.
- [ ] Los errores 401 y 403 tienen mensajes amigables sin códigos técnicos como instrucciones normales.
- [ ] Ningún formulario vuelve a imprimir contraseñas después de error.
- [ ] No hay PDO, credenciales MariaDB ni `require` de código backend en frontend.
- [ ] Con la API caída, frontend continúa respondiendo sin datos y con aviso.

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
- [ ] Hay evidencias de todas las pruebas principales y no contienen credenciales ni tokens completos.
- [ ] El mapa de versiones coincide con el alcance autorizado para v1-v4.
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
