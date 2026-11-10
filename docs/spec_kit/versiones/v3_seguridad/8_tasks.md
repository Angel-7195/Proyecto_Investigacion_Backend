# Tareas — v3: Seguridad (Investigación)

> Orden sugerido: especificar → comprobar el esquema → implementar por capas → probar API y frontend → regresión → tag. Asignar responsables y commits del equipo; las casillas están **sin marcar** porque no se ha implementado ni verificado la v3.

## Fase 0 — Compuertas SDD y base v2

> **Estado heredado:** la V2 documenta diez entidades implementadas, pero aún tenía pendientes de cierre al 29-10-2026. Confirmado PR #15 en backend por información del usuario; las verificaciones restantes se comprueban con el equipo y no se marcan solas como aprobadas.


- [ ] Confirmar el cierre de v2: `tag v2` existente y contratos de v1/v2 funcionales.
- [ ] Identificar diferencias entre mapa de versiones antiguo y metodología; actualizar mapa del equipo con aprobación.
- [x] Examinar `init(4).sql` y registrar su DDL de seguridad en `5_data_model.md`. [ ] Contrastar el resultado con `SHOW CREATE TABLE` en MariaDB activa.
- [x] Registrar el acuerdo para roles semilla `admin` y `consulta`, y primer administrador mediante script PHP solo CLI (9-11-2026; diseño, no prueba).
- [ ] Confirmar matriz de acceso, roles adicionales/múltiples, detalles operativos del bootstrap y semántica de logout.
- [ ] Resolver C2–C12 de `2_spec.md` y comprobar en ejecución la concordancia del esquema; **C1 está resuelta para el SQL fuente**.
- [ ] Validar `3_plan.md` contra la constitución vigente del equipo.
- [ ] Revisar la lista de requisitos de `9_checklist.md` entre compañeros.
- [ ] Ajustar contratos con los campos, claves y rutas definitivas.

**Verificación:** especificación y contratos revisados, ningún `[NECESITA ACLARACIÓN]` bloqueante.

## Fase 1 — Infraestructura y secretos

- [ ] Añadir `JWT_SECRET` al entorno de la API (no frontend).
- [ ] Mantener `.env` en `.gitignore` y ejemplos seguros en `.env.example`.
- [ ] Verificar que no haya secretos reales en Git ni contraseñas de usuario en SQL de prueba versionado.
- [ ] Confirmar arranque Docker sin alterar datos persistidos de la v2.
- [ ] Añadir a `db/init.sql` los INSERT de roles `admin` y `consulta` y numerar/justificar el cambio de semillas en la cabecera del script; **no** incluir usuarios ni contraseñas.
- [ ] Crear `api_investigacion/scripts/inicializar_admin.php` solo CLI; leer `ADMIN_INICIAL_USERNAME`, `ADMIN_INICIAL_EMAIL` y `ADMIN_INICIAL_PASSWORD` desde el entorno y crear el hash con `password_hash(PASSWORD_DEFAULT)`.
- [ ] Hacer la creación de cuenta y asignación en `rol_usuario` atómica, con SQL preparado y consulta de IDs reales (nunca asumir `rol_id = 1`).
- [ ] Garantizar idempotencia; si hay administrador existente no crear ni sobrescribir credenciales. Fallar sin privilegios ante datos preexistentes contradictorios.
- [ ] Para volúmenes existentes, asegurar roles idempotentemente por `nombre` sin ejecutar `down -v`; probar volumen con datos v2 conservados.
- [ ] Integrar el script con el arranque `docker compose up -d --build` tras disponibilidad de MariaDB y documentar su ejecución real; el secreto debe quedar fuera de Git, logs y capturas.
- [ ] Comprobar que no existen rutas HTTP de bootstrap o registro público; documentar recuperación inicial y retirada del secreto temporal tras la creación.

**Verificación:** entorno levanta con un comando; no se imprimen ni versionan secretos.

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
- [ ] Codificación Base64URL, HS256 y verificación robusta con `hash_equals`.
- [ ] Rechazo de token vencido, alterado, con algoritmo no permitido o claims inválidos.
- [ ] `POST /api/login` con respuestas de éxito/401/422/500 documentadas.
- [ ] Sin contraseñas, hashes, JWT ni secretos completos en respuestas de error o logs.

**Verificación:** conjunto de casos positivos y negativos de login/token, incluido usuario inactivo.

## Fase 4 — Autorización central

- [ ] Inventariar todos los métodos y rutas de los 16 recursos previos.
- [ ] Implementar guardas de autenticación y rol antes del controlador.
- [ ] Proteger los 3 CRUD administrativos exclusivamente para admin.
- [ ] `401` si falta identidad válida, `403` si falta permiso, sin tocar BD de negocio.
- [ ] Bloquear escrituras de `consulta` en todos los CRUD de v1/v2.
- [ ] Asegurar que los permisos de roles retirados no se mantienen de forma indebida según C6.
- [ ] Mantener excepciones/rutas originales de v1 y v2 sin alteraciones no acordadas.

**Verificación:** prueba de matriz por verbo y recurso con ambos perfiles.

## Fase 5 — CRUD administrativo API

- [ ] Rutas específicas `usuario`: GET listado/individual, POST, PUT, PATCH, DELETE.
- [ ] Rutas específicas `rol`: GET listado/individual, POST, PUT, PATCH, DELETE.
- [ ] Rutas específicas `rol_usuario`: operaciones aprobadas en C8.
- [ ] Validación de JSON, campos, llaves y listas blancas según esquema.
- [ ] Tratamiento de 204/400/404/409/422/500 sin filtración de SQL.
- [ ] Pruebas contra el uso directo de las rutas por `consulta` y usuarios sin token.

**Verificación:** Postman demuestra tres recursos administrativos y que ninguna respuesta incluye `password`.

## Fase 6 — Frontend PHP

- [ ] Formulario de login y mensajes comprensibles en español.
- [ ] `session_start()`, regeneración de ID tras login y conservación del JWT en sesión.
- [ ] El cliente HTTP aprobado incorpora Bearer; resolver C11-V2 (streams actuales frente a cURL metodológico) antes de marcar esta tarea.
- [ ] Menú por roles, sin confiar en él como único control de acceso.
- [ ] Pantallas de lista, ficha, creación, edición, retiro de usuarios y roles.
- [ ] Pantalla de asignación/retiro de roles de usuarios conforme a contrato.
- [ ] Logout y retorno a login; no continuar enviando token.
- [ ] Gestión de 401, 403, API caída y formularios sin exponer claves.
- [ ] Confirmar frontend sin PDO, sin claves BD y sin importar clases backend.

**Verificación:** uso desde navegador con perfiles consulta/admin y API apagada.

## Fase 7 — Pruebas y regresión

- [ ] Regresión diferenciada: los cinco recursos V2 con campos editables mantienen PUT/PATCH; las cinco asociaciones simples (`grupo_linea`, `semillero_linea`, `ac_linea`, `ods_linea`, `aa_linea`) devuelven **405** para PUT/PATCH de admin; el rol `consulta` devuelve **403** al intentar cualquier escritura.
- [ ] Comprobar la divergencia documentada: PATCH `{}` en controladores V2 revisados → **422**; casos V1 según sus contratos → **400** donde corresponde. No homogeneizar sin decisión documentada.
- [ ] Registrar qué ocurrió con C4 (retiro de padres), C11 (cURL), tag/regresión de V2 después del 29-10.


- [ ] Pruebas unitarias con repositorios falsos, sin MariaDB, de autenticación y reglas de usuario/rol.
- [ ] Pruebas HTTP de token correcto, incorrecto, caducado y sin Bearer.
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

La preparación de JWT/middleware y la creación de pantallas/cliente HTTP pueden realizarse en ramas separadas, pero **ambas partes deben acordar primero el contrato de `POST /api/login`** y la matriz de permisos. Cada estudiante realiza commits propios y el encargado de `main` integra por Pull Request. Las decisiones C3–C11 deben tener responsable y fecha de aprobación antes de empezar sus tareas dependientes.
