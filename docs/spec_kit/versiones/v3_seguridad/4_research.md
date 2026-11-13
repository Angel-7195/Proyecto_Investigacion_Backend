# Investigación y decisiones — v3: Seguridad

> **Actualización de evidencia — 12-11-2026, fase 5:** gestión principal de `rol_usuario` comprobada por **15/15** pruebas aisladas y HTTP real: asignación `201`, ficha `200`, duplicado `409`, retiro `200`, ficha posterior `404`. Un usuario `consulta` real obtuvo GET V1 `200`, POST V1 `403` y GET administrativo `403`; tras retirar el rol, **el mismo JWT** pasó a GET `403`. SQL confirmó usuario sin rol aún activo y `ADMIN` con `admin`. La protección del último administrador **queda pendiente de prueba final solo en base descartable**, sin arriesgar el volumen local. Ver [REGISTRO_FASE5_ROL_USUARIO.md](REGISTRO_FASE5_ROL_USUARIO.md).

> Cada decisión indica contexto, alternativas, decisión y consecuencia. **Aprobada** significa exigida por las fuentes del proyecto; **propuesta** requiere aprobación del equipo en la compuerta 1. No confundir este documento con investigación externa de bibliotecas.

> **Evidencia al 12-11-2026:** el usuario aportó las pruebas de la inicialización CLI, **15/15** pruebas de login/JWT y **28/28** del middleware; HTTP real de login con `ADMIN` → 200; GET `/api/area_conocimiento` sin token → 401 y con Bearer válido → 200. Es evidencia de ejecución local comunicada, no auditoría independiente de todo el código. Ver registros de fases 1, 2 y 3. El CRUD `usuario` añadió 28/28 pruebas aisladas, operaciones HTTP reales y comprobaciones SQL locales (ver `REGISTRO_FASE4_USUARIO.md`). Persisten pendientes de `rol`, `rol_usuario`, frontend PHP, regresión y despliegue reproducible.

## D-v3-01 — JWT firmado en PHP puro

- **Contexto:** la metodología exige autenticación `POST /api/login` y JWT; §6.4 muestra la firma HS256 con `hash_hmac()`.
- **Alternativas:** (a) instalar una librería JWT por Composer; (b) usar funciones nativas PHP y prueba de firma y expiración.
- **Decisión:** (b), conforme al stack docente. Validar cabecera `alg=HS256`, `typ=JWT`, tres segmentos, Base64URL válido, estructura esperada, firma mediante `hash_equals`, `exp` y claims obligatorios.
- **Consecuencias:** mayor responsabilidad en pruebas de entradas malformadas; ninguna dependencia adicional. No aceptar algoritmos indicados libremente por el token.
- **Estado al 12-11:** se recibió `JwtHS256.php` y el estudiante ejecutó la suite 15/15 y login real; pendiente verificación externa de todos los escenarios y revisión de seguridad final.

## D-v3-02 — Hash de contraseñas

- **Contexto:** `usuario.password` almacena credenciales y jamás debe guardar texto plano.
- **Alternativas:** (a) texto plano o SHA256 manual; (b) `password_hash(..., PASSWORD_DEFAULT)` y `password_verify()`.
- **Decisión:** (b), exigida por `modulo_investigacion.md` y §6.4.
- **Consecuencias:** almacenar el hash con longitud suficiente según DDL; jamás mostrar campo de contraseña ni hash en respuestas.
- **Estado:** aprobada por fuentes.

## D-v3-03 — Bearer en la API y sesión en el frontend

- **Contexto:** el frontend PHP es otro proceso y consume la API por HTTP. La documentación V2 describe streams, mientras la metodología exige cURL.
- **Alternativas:** (a) exponer JWT al JavaScript o usar PHP del frontend para acceder a MariaDB; (b) conservar JWT en `$_SESSION` y enviar Bearer en un cliente HTTP autorizado, resolviendo la conformidad con cURL (C11-V2).
- **Decisión propuesta:** (b), con migración a cURL o excepción expresa por resolver. La separación sesión/Bearer se ajusta a la metodología.
- **Consecuencias:** necesita `session_start()`, cookie segura, recuperación ordenada de sesión expirada y cierre de sesión web; no exige CORS en navegador para llamadas a la API.
- **Estado:** aprobada por fuentes; configuración concreta de cookie pendiente C10.

## D-v3-04 — Autorización antes del controlador

- **Contexto:** las 16 rutas existentes requieren una barrera común y diferenciación 401/403.
- **Alternativas:** (a) verificar permisos dentro de cada vista; (b) validar JWT y autorización en el front controller de API antes de ejecutar controladores.
- **Decisión propuesta:** (b): verificar token y autorización en una guarda transversal del front controller, antes de invocar los controladores específicos.
- **Consecuencias:** un menú oculto nunca se considera seguridad; permite pruebas de acceso directo mediante Postman.
- **Estado al 12-11:** integrada una guarda previa a controladores; 28/28 pruebas locales y HTTP real 401/200 en `area_conocimiento`; falta matriz HTTP completa.

## D-v3-05 — Política mínima de privilegios

- **Contexto:** el requisito explícito dice que `consulta` no puede escribir y solo admin gestiona usuarios/roles.
- **Alternativas:** (a) conceder permisos por defecto y bloquear excepciones; (b) denegar acciones no asignadas explícitamente.
- **Decisión propuesta:** (b). `consulta` lee; `admin` gestiona; ningún rol desconocido concede permisos automáticamente.
- **Consecuencias:** la matriz de rutas será fácil de auditar, pero exige acordar perfiles y permisos de lectura.
- **Estado al 12-11:** matriz aplicada en pruebas automatizadas del middleware (28/28); faltan pruebas HTTP con cuenta real `consulta` y comprobación de la ausencia de escrituras.

## D-v3-06 — Roles vigentes y cambios posteriores al login

- **Contexto:** el JWT puede conservar roles antiguos si se retira un rol o desactiva el usuario.
- **Alternativas:** (a) confiar en claims `roles` hasta `exp`; (b) usar token para identidad y consultar estado/roles vigentes en cada petición protegida.
- **Decisión recomendada:** (b) para evitar que un usuario retirado conserve privilegios hasta vencimiento.
- **Consecuencias:** una consulta más a MariaDB en solicitudes protegidas y necesidad de fallar de forma segura si la base no responde. No afecta separación de capas si la verificación consulta repositorio por interfaz.
- **Estado al 12-11:** la suite del middleware comprobó revocación de rol, usuario desactivado y multirrol con repositorio simulado; falta integración HTTP específica con cambios reales de roles en MariaDB.

## D-v3-07 — Qué significa logout

- **Contexto:** un JWT es autocontenido; borrar `$_SESSION` no invalida una copia previa del token.
- **Alternativas:** (a) logout de sesión frontend, token expira después; (b) revocación inmediata usando lista de denegación y estado del servidor.
- **Decisión aprobada por el equipo:** logout del frontend eliminando la sesión PHP y dejando de enviar el token; sin sistema de revocación central de JWT en v3. El token copiado podría seguir válido hasta expirar.
- **Consecuencias:** documentar semántica real sin prometer revocación inexistente. Si se elige (b), diseñar almacenamiento y pruebas adicionales.
- **Estado:** aprobado por el equipo: logout de sesión PHP sin revocación centralizada; implementación pendiente.

## D-v3-08 — CRUD de tabla puente `rol_usuario`

- **Contexto verificado con `init(4).sql`:** tabla con PK compuesta `(usuario_id, rol_id)`, dos FKs `ON DELETE CASCADE`, sin `activo` ni otras columnas. El borrado lógico de `usuario`/`rol` no dispara cascadas.
- **Alternativas:** (a) desasignar con DELETE físico **solo de la asociación** sin eliminar usuario/rol; (b) solicitar y justificar adición de `activo` al puente y consultar solo vínculos activos.
- **Decisión:** **C8 aprobada** por el equipo. Para PUT/PATCH se propone `405` porque no hay atributos editables aparte de la PK, como en las cinco asociaciones simples de V2.
- **Consecuencias:** (a) elimina historial de asignaciones; (b) cambia el esquema dado y requiere autorización y registro del cambio. Las cascadas del SQL solo aplican a borrado **físico** de las tablas principales.
- **Estado actualizado 12-11-2026:** DDL confirmado, DELETE físico del vínculo implementado (HTTP 200/404 y usuario conservado); PUT/PATCH 405 contractual pendiente de comprobación HTTP, igual que el último admin en BD descartable.

## D-v3-09 — Primer administrador

- **Contexto:** sin una cuenta administradora inicial nadie podría crear usuarios y asignar roles.
- **Alternativas:** (a) `POST /api/register` abierto a cualquiera o contraseña fija versionada; (b) un procedimiento controlado de bootstrap para sembrar el primer admin con hash y secreto proporcionado fuera de Git.
- **Decisión acordada en esta revisión (9-11-2026):** (b). El primer administrador se creará con un script PHP `scripts/inicializar_admin.php`, **solo CLI**, sin ruta pública, obteniendo los datos sensibles del entorno y hasheando con `password_hash(PASSWORD_DEFAULT)` antes de guardar. Insertará la relación por IDs reales de `usuario` y `rol` en una transacción. Antes verificará administradores existentes para no generar duplicados ni asumir que el volumen está vacío.
- **Consecuencias:** procedimiento reproducible y auditable; el proceso no imprime la clave, no deja una ruta de registro abierto, no modifica una contraseña existente y falla de forma segura ante datos ambiguos. La ejecución/orquestación Docker y el manejo del secreto tras el alta deben cerrarse y probarse.
- **Estado a 10-11-2026:** implementación manual CLI y alta inicial **comprobadas por el estudiante** con SELECT de roles/vínculo. Pendientes idempotencia, volumen limpio y arranque automático; ver `REGISTRO_FASE1_ADMIN.md`.

## D-v3-10 — Conservación de compatibilidad v1/v2

- **Contexto:** las versiones son acumulativas; la nueva autenticación cambia el acceso a rutas previas.
- **Alternativas:** (a) reescribir CRUD anteriores al mismo tiempo; (b) agregar una barrera transversal y preservar contratos de datos existentes en llamadas autorizadas.
- **Decisión:** (b), conforme a la regresión exigida en metodología.
- **Consecuencias:** actualizar pruebas con token válido; conservar respuestas de negocio como 204, 400, 404, 409 y 422 cuando el usuario está autorizado.
- **Estado:** aprobada por fuentes.

## D-v3-11 — Configuración de secretos

- **Contexto:** las aplicaciones serán publicadas en v4, por lo que el curso exige no versionar secretos desde v1.
- **Alternativas:** (a) clave fija en código o `docker-compose.yml`; (b) variable de entorno `JWT_SECRET`, `.env` ignorado y `.env.example` ejemplar.
- **Decisión:** (b), exigida por metodología del proyecto de aula.
- **Consecuencias:** el arranque valida que exista un secreto apropiado; jamás se registra el token completo ni el secreto.
- **Estado:** aprobada por fuentes. La excepción didáctica de la constitución de **ejemplo** no aplica automáticamente al proyecto de aula.

## D-v3-12 — Conservar métodos y errores específicos de V2

- **Contexto y fuente.** `2_spec.md` y `6_contracts.md` de V2 registran cinco asociaciones simples solo GET/POST/DELETE (PUT/PATCH `405`); en V2 controladores revisados PATCH `{}` da `422`, mientras que V1 definía `400` para su PATCH vacío.
- **Alternativas:** (a) unificar silenciosamente todos los CRUD y respuestas al agregar middleware; (b) preservar la matriz de métodos y códigos por recurso hasta decidir una corrección contractual explícita.
- **Decisión propuesta: (b).** Seguridad se añade antes del controlador, sin reinterpretar la lógica de V2. Para una identidad autorizada, se conservan los `405` y los errores documentados; para `consulta` denegada, la comprobación de autorización puede contestar `403` antes de llegar al 405.
- **Consecuencias:** las pruebas de regresión deben ser **por clase de recurso**, no un único CRUD genérico. **Estado:** fundamento confirmado documentalmente; prueba real pendiente.

## D-v3-13 — Cliente HTTP de V2 y exigencia de cURL

- **Contexto y fuente.** `3_plan.md` §3 y C11 de `2_spec.md` V2 documentan `stream_context_create()` y `file_get_contents()` en `front_php/cliente_api.php`, mientras la metodología menciona cURL.
- **Alternativas:** (a) añadir Bearer con el cliente existente y registrar excepción aprobada; (b) migrar el cliente a cURL, conservar contratos y volver a probar 16 recursos.
- **Decisión:** [NECESITA ACLARACIÓN: validación del profesor/equipo]. **No afirmar que ya está en cURL**. Cualquiera de las dos vías debe mantener HTTP/JSON y frontend sin PDO.
- **Consecuencias:** la migración o decisión documentada se efectúa antes de dar por cerrado el RNF de conformidad con el stack.

## D-v3-14 — Diferenciar roles investigativos de roles de acceso

- **Contexto.** `participa_grupo.rol` y `participa_semillero.rol` son texto con un cargo dentro de la actividad investigativa; `rol` y `rol_usuario` son las tablas de permisos de V3.
- **Alternativas:** (a) permitir que el rol académico conceda permisos; (b) autorizar solo mediante los roles de gestión de usuarios.
- **Decisión propuesta: (b).** Evita elevar privilegios cuando se registra a alguien como «líder» o «investigador».
- **Consecuencias:** los servicios de participación permanecen independientes de las reglas de autenticación. La autorización central consulta roles de seguridad, no roles de participación.

## D-v3-15 — Prioridad de controles de seguridad frente a errores antiguos

- **Contexto.** Los endpoints V1/V2 se llamaban sin JWT. Un GET o PUT protegido no puede revelar su contenido ni validar un cuerpo para una persona no autorizada.
- **Alternativas:** (a) invocar el controlador y después revisar permiso; (b) revisar token y autorización antes del controlador.
- **Decisión propuesta: (b).** Token ausente/inválido → `401`, token válido sin permiso → `403`; tras permiso, conservar `404`, `405`, `409`, `422` propios del recurso.
- **Consecuencias:** la matriz de regresión distingue respuestas sin permiso y respuestas con permiso. Las rutas inexistentes y métodos no admitidos no deben revelar información sensible sobre recursos administrativos.

## D-v3-16 — Semillas públicas de roles frente a credenciales embebidas

- **Contexto:** el SQL compartido crea `rol`, `usuario` y `rol_usuario` sin INSERT de seguridad; `rol.nombre` es `VARCHAR(100) UNIQUE` y su ID es autoincremental. Se necesita conocer al menos dos perfiles para probar los permisos de v3.
- **Alternativas:** (a) poner usuario y contraseña iniciales con INSERT en `init.sql`; (b) incluir en el SQL **solo los roles públicos** `admin` y `consulta` y delegar la creación de la cuenta a un bootstrap PHP seguro; (c) dejar los roles para crearse manualmente cada vez.
- **Decisión acordada en esta revisión (9-11-2026):** (b). Añadir los dos nombres de rol al `init.sql` **como cambio de semillas explícito y numerado**, no como modificación de DDL. Los IDs no se fijan: se consultan por nombre. Para volúmenes ya existentes, el inicializador debe asegurar idempotentemente la presencia de ambos roles sin borrar ni recrear la BD.
- **Consecuencias:** el `init.sql` del curso sigue siendo el esquema de referencia; el cambio en los datos semilla debe anotarse en la cabecera. Un reinicio no duplica los roles; un volumen previo no ejecuta automáticamente SQL nuevo. No aparece ninguna contraseña en Git. Pendiente probar dos escenarios (base nueva y existente) y la orquestación del arranque.
- **Estado a 10-11-2026:** el estudiante comunica aprobación del equipo y exhibió `git diff` con ambas semillas; la consulta posterior mostró ambos roles activos en volumen persistente. No está comprobado su uso en una instalación limpia.

## D-v3-17 — Tomar la metodología de la v3 del profesor, no su contenido

- **Contexto:** el paquete `v3_segundo_motor` entregado por el profesor corresponde al ejemplo del curso. Su objetivo es agregar PostgreSQL sin cambiar los contratos anteriores; la v3 del módulo de Investigación exige JWT, roles y gestión de usuarios según `0_METODOLOGIA.md` y `modulo_investigacion.md`.
- **Alternativas:** (a) copiar `MOTOR`, repositorios PostgreSQL y diagnóstico del ejemplo; (b) copiar **solo el método**: delta explícito, plan por capas, decisiones, pruebas de regresión, tres compuertas y cierre por tag.
- **Decisión:** (b). No se agregará segundo motor en la versión de seguridad.
- **Consecuencias:** `3_plan.md` debe identificar qué archivos cambian por autenticación y qué contratos siguen invariantes. La v3 del profesor es **referencia metodológica**, no requisitos funcionales de este módulo.
- **Estado:** documentado; sin cambios de implementación.

## D-v3-18 — Comprobaciones de autorización sobre roles activos

- **Contexto:** `usuario` y `rol` pueden tener `activo` en 0 o NULL, y el puente no tiene `activo`. Un JWT vigente puede permanecer en poder de un usuario retirado.
- **Alternativas:** (a) confiar únicamente en los roles del JWT emitido; (b) buscar usuario/rol/asignación vigentes en la base al autorizar cada operación.
- **Recomendación:** (b) por mínimo privilegio y efecto inmediato de retiros lógicos, siempre mediante servicios y repositorios por interfaz.
- **Consecuencias:** mayor carga de consulta; si el almacenamiento no está disponible la operación protegida falla cerrada, sin conceder acceso. **El equipo aprobó varios roles por usuario y la comprobación de permisos vivos en MariaDB; faltan pruebas de implementación.**

## D-v3-19 — Protección del último administrador y cambios de credenciales

- **Contexto:** los CRUD administrativos podrían eliminar acceso a la única cuenta admin o permitir sobreescrituras involuntarias de `password`.
- **Alternativas:** (a) aceptar cualquier operación administrativa, aun si deja al sistema sin admin; (b) impedir la retirada de la última cuenta/rol admin activo y definir un cambio de contraseña separado de la edición parcial normal.
- **Recomendación:** (b), con verificación transaccional para evitar carreras. La prohibición de retirar al último admin y el cambio explícito de contraseña fueron aprobados; faltan detalles de validación, implementación y pruebas.
- **Consecuencias:** evita bloqueo administrativo accidental, pero requiere pruebas de concurrencia e integridad; no declarar esta regla implementada.

**Evidencia parcial agregada el 12-11-2026:** el código del repositorio de `usuario` contiene una transacción y control de último administrador mediante bloqueo (`FOR UPDATE`); la suite aislada de `usuario` pasó 28/28, incluyendo la simulación de este caso. **No se ejecutó un intento real de desactivar al único administrador de la BD principal** para no perder el acceso. La base real mostró un administrador activo, lo cual no prueba la barrera de rechazo. `PATCH /api/usuario/{id}/password` devolvió HTTP 200; la nueva contraseña aleatoria no se conservó para verificar su hash con `password_verify()` directamente. El perfil se modificó por PUT/PATCH sin incluir `password`. La regla de 12–72 bytes proviene de la implementación y debe ratificarse en la documentación de requisitos.

## D-v3-20 — Dos revisiones independientes del checklist

- **Contexto:** `9_checklist.md` del ejemplo del profesor es una **compuerta previa al código**, mientras nuestro archivo también evaluaba ejecución y cierre.
- **Alternativas:** (a) mezclar validación documental y resultados ejecutados en una sola aprobación; (b) conservar ambas revisiones en secciones distintas, sin casillas premarcadas.
- **Decisión documental:** (b). La revisión de requisitos sucede antes de programar; el cierre funcional solo se marca después de pruebas reales.
- **Consecuencias:** no se confunde «el contrato está especificado» con «el software está probado».
