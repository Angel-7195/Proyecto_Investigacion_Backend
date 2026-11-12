# Contratos de la API — v3: autenticación y administración

> **Documento contractual en desarrollo.** El login y la guarda ya se ejecutaron parcialmente; CRUD administrativos siguen como propuestas donde se indica. Revisado contra `6_contracts.md` V2 (29-10-2026), que identifica **las rutas y operaciones reales documentadas** de los diez recursos con FK. El **DDL del script de seguridad ya fue comprobado con `init(4).sql`**; las reglas de negocio marcadas **PROPUESTA** siguen pendientes de aprobación y los contratos de CRUD administrativos aún no se han probado contra código. Los contratos antiguos se conservan **después** del control de acceso.

> **Actualización al 12-11-2026:** roles y administrador inicial creados localmente; login real `ADMIN` exitoso con JWT de 3600 segundos (**15/15** pruebas automatizadas); autorización central con **28/28** pruebas automatizadas, HTTP **401** sin token y **200** con Bearer válido para `area_conocimiento`. Persisten pendientes idempotencia del bootstrap, instalación limpia, matriz HTTP completa, CRUD administrativos, frontend y regresión. La evidencia proviene del estudiante, no de acceso directo a su Docker. Véanse [REGISTRO_FASE1_ADMIN.md](REGISTRO_FASE1_ADMIN.md), [REGISTRO_FASE2_LOGIN.md](REGISTRO_FASE2_LOGIN.md), [REGISTRO_FASE3_MIDDLEWARE.md](REGISTRO_FASE3_MIDDLEWARE.md) y [7_quickstart.md](7_quickstart.md).
## §0. Sobre común y autenticación

El sobre de errores conserva el estilo de v1:

```json
{
  "estado": 401,
  "mensaje": "Autenticación requerida.",
  "detalle": "Inicie sesión para continuar."
}
```

| Condición | HTTP | Responsable |
|---|---:|---|
| JSON/campos inválidos, incluido PATCH vacío en V2 documentada | `422` | Controlador |
| Login no válido, usuario inactivo, token ausente, inválido o vencido | `401` | Controlador login / comprobación central |
| Token válido pero rol sin permiso | `403` | Comprobación central de autorización |
| Operación de negocio inválida, en recursos que lo usan | `400` | Servicio → controlador; **no universalizar PATCH vacío** |
| Recurso no existente o retirado | `404` | Servicio → controlador |
| Duplicado / violación de integridad | `409` | Repositorio → controlador |
| Ruta existente con verbo no admitido | `405` | Router |
| Error interno, secreto no configurado o BD caída | `500` / error controlado | Manejo general |

**Requisitos transversales:** sin exponer hashes, tokens, secretos ni mensajes SQL; no devolver datos de un recurso protegido antes de comprobar la autorización; evitar distinguir «usuario inexistente» de «contraseña incorrecta» en login.

## §0.1. Inicialización del primer administrador: **sin endpoint HTTP**

La inicialización no crea `POST /api/register`, `POST /api/bootstrap` ni `POST /api/admin/inicializar`. Se realiza por script PHP **solo CLI**, después de asegurar que existen los roles `admin` y `consulta`. La creación de la **primera** cuenta administrativa utiliza `password_hash(PASSWORD_DEFAULT)`, las variables `ADMIN_INICIAL_USERNAME`, `ADMIN_INICIAL_EMAIL`, `ADMIN_INICIAL_PASSWORD` y una asignación válida en `rol_usuario`. No se acepta una contraseña fija dentro de `init.sql`, y tampoco se retorna en JSON.

Se probará que `POST /api/login` funciona con ese primer administrador. **Un usuario adicional** se crea desde `POST /api/usuario` solo una vez que la autenticación administrativa ya funciona. Los roles semilla **no tienen IDs fijos**; cualquier asociación consulta los IDs reales.

Esta sección es un **contrato de operación interna**, no una ruta HTTP. La ejecución manual CLI del bootstrap ya fue satisfactoria en el equipo del estudiante; la idempotencia por repetición, instalación limpia y automatización de Docker siguen pendientes. Véase `REGISTRO_FASE1_ADMIN.md`.

## §1. `POST /api/login` (ruta pública)

> **Contrato de fase 2 verificado parcialmente en ejecución (12-11-2026):** `POST /api/login` entregó en HTTP real `estado=200`, `datos.expira_en=3600`, `datos.usuario.username=ADMIN`, `datos.usuario.roles=["admin"]`. La suite automatizada reportó 15/15. No se comparó exhaustivamente el JSON de todos los errores por HTTP.



El módulo **exige** la ruta y la entrega de JWT. **Cuerpo acordado por el equipo (`username` y `password`):**

```json
{
  "username": "usuario_ejemplo",
  "password": "clave-enviada-solo-por-HTTPS"
}
```

**Respuesta 200: estructura observada en el login y contrastada con los archivos PHP recibidos; duración `expira_en` aprobada:**

```json
{
  "estado": 200,
  "mensaje": "Inicio de sesión correcto.",
  "datos": {
    "token": "<JWT_HS256>",
    "tipo": "Bearer",
    "expira_en": 3600,
    "usuario": {
      "id": 12,
      "username": "usuario_ejemplo",
      "roles": ["consulta"]
    }
  }
}
```

`expira_en: 3600` es la duración **aprobada por el equipo** (60 minutos). La fecha de expiración del JWT será `exp = iat + 3600`; el token sin `exp` válido se rechazará. El DDL confirma `usuario.id` como entero autoincremental (ejemplo de token `sub` como cadena de ese entero). **Confirmado en `ServicioAutenticacion.php` recibido:** `roles` se incluye en la respuesta JSON, mientras que `JwtHS256.php` emite identidad (`sub` y `username`) sin lista de roles; el middleware consulta los roles vigentes en MariaDB.

- `200`: autenticación correcta, usuario activo.
- `401`: credenciales no válidas; mensaje genérico sin diferenciar usuario o clave.
- `422`: cuerpo incompleto/tipos incorrectos.
- `500`: configuración o base no disponible, sin fugas de secretos.

## §2. Comprobación Bearer en rutas protegidas

> **Contrato de fase 3 verificado parcialmente en ejecución (12-11-2026):** `GET /api/area_conocimiento` sin Authorization → **HTTP 401**, `WWW-Authenticate: Bearer realm="investigacion"`; la misma ruta con `Authorization: Bearer <JWT>` válido de `ADMIN` → **HTTP 200**. Se reportaron 28/28 pruebas automatizadas del middleware. **Falta** probar por HTTP con un usuario real `consulta`, rutas administrativas ya implementadas y todos los métodos de los 16 recursos.



```text
Authorization: Bearer <JWT_HS256>
```

**Comportamiento acordado e implementado en la guarda:** token ausente o inválido → `401`; token válido sin permiso → `403`. Se validan firma, `exp`, identidad y HS256 y se consultan los roles vigentes en MariaDB en cada petición. La matriz HTTP completa aún debe probarse con datos reales. No aceptar el token en parámetro de URL.

Ejemplo 403:

```json
{
  "estado": 403,
  "mensaje": "Acceso denegado.",
  "detalle": "No tiene permiso para realizar esta acción."
}
```

## §3. CRUD de `usuario` — **solo admin**

| Ruta específica | Operación | Respuestas esperadas (además de 401/403/500) |
|---|---|---|
| `GET /api/usuario` | Listar activos, sin hash | `200`, `204` |
| `GET /api/usuario/{id}` | Obtener sin hash | `200`, `404` |
| `POST /api/usuario` | Crear, hash de contraseña | `201`, `409`, `422` |
| `PUT /api/usuario/{id}` | Reemplazar campos admitidos | `200`, `404`, `409`, `422` |
| `PATCH /api/usuario/{id}` | Modificar campos admitidos | `200`, `404`, `409`, `422`; `400` solo si se acuerda regla concreta |
| `DELETE /api/usuario/{id}` | Retirar lógicamente | `200`, `404`, `409` si la regla aprobada impide retiro |

**Propuesta de POST**, con longitudes verificadas en SQL (`username` 100, `email` 150, `nombre_completo` 200, hash 255); las reglas de contraseñas siguen pendientes de aprobar:

```json
{
  "username": "usuario_ejemplo",
  "password": "clave-solo-de-entrada",
  "email": "ejemplo@dominio.invalid",
  "nombre_completo": "Persona de ejemplo"
}
```

**Nunca** devolver el campo `password`, ni el hash, en `datos` del POST, GET o actualizaciones. No permitir que el frontend escriba `activo` o el hash almacenado. Definir si la contraseña es obligatoria en PUT y cómo cambiarla sin sobrescribirla accidentalmente (**contrato de cambio de contraseña pendiente en C9/clarificación específica**).

Ejemplo GET **sin hash**:

```json
{
  "id": "<ID_REAL>",
  "username": "usuario_ejemplo",
  "email": "ejemplo@dominio.invalid",
  "nombre_completo": "Persona de ejemplo"
}
```

## §4. CRUD de `rol` — **solo admin**

| Ruta específica | Operación | Respuestas esperadas (además de 401/403/500) |
|---|---|---|
| `GET /api/rol` | Listar activos | `200`, `204` |
| `GET /api/rol/{id}` | Obtener activo | `200`, `404` |
| `POST /api/rol` | Crear | `201`, `409`, `422` |
| `PUT /api/rol/{id}` | Reemplazar | `200`, `404`, `409`, `422` |
| `PATCH /api/rol/{id}` | Actualizar parcialmente | `200`, `404`, `409`, `422`; `400` solo con regla definida |
| `DELETE /api/rol/{id}` | Retirar lógicamente | `200`, `404`, `409` si existe restricción aprobada |

**Propuesta de POST (DDL de `rol` verificado: `nombre VARCHAR(100) UNIQUE NOT NULL`, `descripcion TEXT NULL`):**

```json
{
  "nombre": "consulta",
  "descripcion": "Puede consultar los recursos autorizados"
}
```

`id` es `INT AUTO_INCREMENT` y `fecha_creacion` tiene `DEFAULT CURRENT_TIMESTAMP`: no se solicitan al cliente en POST. No aceptar `activo` como entrada editable salvo decisión expresa, respetando el borrado lógico.

## §5. Gestión de `rol_usuario` — **solo admin**

El módulo exige gestión de asignaciones. El SQL confirma que **`usuario_id` y `rol_id` son INT, FK y PK compuesta**, y que no hay `id` propio ni `activo`. **La política de retiro y los métodos ya fueron acordados; falta implementarlos**, sin añadir campos inexistentes.

| Ruta **PROPUESTA** | Operación | Observación |
|---|---|---|
| `GET /api/rol_usuario` | Listar asignaciones vigentes | Datos solo para admin |
| `GET /api/rol_usuario/{usuario_id}/{rol_id}` | Obtener relación | PK compuesta confirmada en SQL |
| `POST /api/rol_usuario` | Crear relación | Comprobar ambas FK activas; duplicado → 409 |
| `DELETE /api/rol_usuario/{usuario_id}/{rol_id}` | Retirar asignación | DELETE físico solo del vínculo; protegido contra retiro del último admin activo; usuario/rol conservan borrado lógico |
| `PUT` / `PATCH` | Actualizar asignación | **Acordado C8:** responder `405`; las dos FK constituyen la PK y no hay campos adicionales editables. |

**Propuesta de cuerpo de creación:**

```json
{
  "usuario_id": 1,
  "rol_id": 2
}
```

Los valores `1` y `2` son **IDs ilustrativos** de tipo `INT`, no claves garantizadas en los datos reales. Si una FK no existe o está retirada → resolver si responde `409` por integridad de asociación (propuesta) o `404` por no encontrado, dejando una sola decisión coherente con v2.

## §6. Los 16 recursos anteriores: delta contractual de seguridad

Se mantienen las rutas reales documentadas de v1/v2. La siguiente tabla señala el alcance, **no crea rutas nuevas**:

| Grupo | Recursos | Lecturas con rol `consulta` | Escrituras con rol `consulta` |
|---|---|---|---|
| v1 | `area_conocimiento`, `objetivo_desarrollo_sostenible`, `area_aplicacion`, `termino_clave`, `universidad`, `linea_investigacion` | Permitidas en matriz propuesta | `403` |
| v2 | `docente`, `grupo_investigacion`, `semillero`, `participa_grupo`, `participa_semillero`, `grupo_linea`, `semillero_linea`, `ac_linea`, `ods_linea`, `aa_linea` | Permitidas en matriz propuesta | `403` |

Las solicitudes **autenticadas y autorizadas** conservan los desenlaces por recurso (`204` lista vacía, `201` creación, `404` retirado, `409` FK/duplicado, `422` validación; `400` en los casos V1 que lo especifican). **Importante:** en la V2 documentada, los controladores revisados usan `422` para PATCH `{}`; y las cinco asociaciones simples devuelven `405` para PUT/PATCH, no `200`. El control previo de seguridad hace que un usuario sin permiso obtenga `401`/`403` antes de llegar a los errores del controlador.

### Inventario de rutas y métodos heredados de V2 (verificado documentalmente)

| Recurso | Ruta individual | Operaciones permitidas |
|---|---|---|
| `docente` | `/api/docente/{cedula}` | GET, POST en colección; GET, PUT, PATCH, DELETE en ficha |
| `grupo_investigacion` | `/api/grupo_investigacion/{id}` | Idem |
| `semillero` | `/api/semillero/{id}` | Idem |
| `participa_grupo` | `/api/participa_grupo/{docente_cedula}/{grupo_investigacion_id}` | Idem |
| `participa_semillero` | `/api/participa_semillero/{docente}/{semillero}` | Idem |
| `grupo_linea` | `/api/grupo_linea/{grupo_investigacion}/{linea_investigacion}` | GET, POST en colección; GET, DELETE en ficha; **PUT/PATCH 405** |
| `semillero_linea` | `/api/semillero_linea/{semillero}/{linea_investigacion}` | Idem (405) |
| `ac_linea` | `/api/ac_linea/{linea_investigacion}/{area_conocimiento}` | Idem (405); `area_conocimiento` es VARCHAR(6) |
| `ods_linea` | `/api/ods_linea/{linea_investigacion}/{ods}` | Idem (405) |
| `aa_linea` | `/api/aa_linea/{area_aplicacion}/{linea_investigacion}` | Idem (405) |

En la columna «Idem» se mantiene la primera fila del mismo grupo. `POST` aplica a la colección, nunca a la ruta de ficha. Los seis recursos V1 mantienen `/api/area_conocimiento`, `/api/objetivo_desarrollo_sostenible`, `/api/area_aplicacion`, `/api/termino_clave`, `/api/universidad`, `/api/linea_investigacion` y sus operaciones definidas en `6_contracts.md` V1. Las condiciones de seguridad modifican **quién puede invocarlas**, no los identificadores ni campos.

## §7. Logout: contrato FRONTEND, no suponer endpoint de API

La documentación del curso exige **logout** y precisa que el frontend guarda el token en una sesión PHP. **Decisión aprobada:** una ruta web de salida (nombre a definir con rutas reales) limpia la información de autenticación de `$_SESSION`, invalida la sesión y redirige a login. No inventar `POST /api/logout` a menos que C7 exija revocación en la API.

**Comprobación:** después de salir, el frontend no envía Authorization y las vistas protegidas piden ingreso nuevo. Si una copia del JWT no ha expirado, seguirá siendo criptográficamente válida a menos que exista un mecanismo de revocación explícito; dejarlo escrito en la especificación definitiva.

## §8. Comprobaciones antes de congelar contratos

- [x] DDL fuente, PK, UNIQUE y FK de usuario/rol/rol_usuario comprobados con `init(4).sql`; [ ] cotejar con la BD activa.
- [x] Login por `username` + `password` y vigencia 3600 segundos aprobados. [ ] Confirmar campos opcionales del sobre de respuesta y claims definitivos.
- [x] Política de puente: DELETE físico del vínculo; PUT/PATCH 405; proteger último admin. [ ] Validar rutas finales contra router.
- [x] Equipo aprobó matriz `admin`/`consulta` y comprobar usuario/rol activos en base. [ ] Implementar y probar.
- [x] Logout solo frontend por sesión PHP; no endpoint de revocación API en v3. [ ] Probar.
- [ ] Códigos para FK, usuario desactivado y reglas de retiro de admin actual.
- [x] Decisión sobre roles semilla `admin`/`consulta` y creación del administrador por PHP exclusivo CLI (sin registro público). [ ] Verificar proceso idempotente en base nueva y volumen existente sin revelar ni sobrescribir contraseñas.
- [x] Rutas y operaciones V2 levantadas de `6_contracts.md` V2. [ ] Cotejo final con código integrado y ejecución de regresión.

## §9. Cuadros de respuesta y reglas de entrada que deben congelarse antes de programar

### 9.1 Prioridad de seguridad (propuesta C4)

| Situación | Propuesta de respuesta | Condición previa |
|---|---|---|
| Petición a ruta protegida sin Bearer o con JWT inválido | `401` con `{"estado":401,"mensaje":"No autenticado.","detalle":"Debe iniciar sesión."}` | Ruta protegida reconocida |
| Autenticado sin permiso sobre ruta protegida | `403` con el sobre de §2 | Rol vigente comprobado |
| Admin intenta PUT sobre puente simple V2 | `405` como V2 | Autenticación y autorización ya superadas |
| Admin envía cuerpo inválido a recurso antiguo | `422` o `400` específico de V1/V2 | Respetar contrato heredado por recurso |
| Ruta inexistente o método no previsto | **Pendiente de precedencia:** diferenciar enrutamiento de seguridad sin filtrar datos | C4/C10 |

Los errores nunca deben incluir el contenido del Bearer, hash de contraseña, secreto JWT ni traza SQL. Para el login inválido no revelar si falló `username` o `password`.

### 9.2 Tablas administrativas: campos exactos del contrato propuesto

| Recurso | POST de creación | PUT de reemplazo | PATCH | Campos no editables |
|---|---|---|---|---|
| `usuario` | `username`, `email`, `password`, `nombre_completo` opcional | `username`, `email`, `nombre_completo`; **definir explícitamente contraseña** en C9 | Subconjunto de campos aprobados | `id`, `activo`, fechas, hash emitido desde BD |
| `rol` | `nombre`, `descripcion` opcional | `nombre`, `descripcion` (admite NULL si se aprueba) | Subconjunto de campos aprobados | `id`, `activo`, `fecha_creacion` |
| `rol_usuario` | `usuario_id`, `rol_id` | `405` | `405` | `id` no existe; tampoco `activo` |

**No presentar la fila de PUT de `usuario` como contrato definitivo:** el requisito docente exige CRUD, pero no dice si editar perfil debe obligar a cambiar contraseña. Es preferible preservar el hash actual en una edición sin `password` y diseñar cambio de clave explícito, pendiente de acuerdo C9. Para PATCH `{}` en los nuevos recursos, el equipo deberá fijar un único código; **no heredar automáticamente** los distintos criterios V1/V2.

### 9.3 Respuestas tipo (propuestas consistentes con los sobres heredados)

**Login válido — 200** (la expiración concreta se aprueba en C6):

```json
{
  "estado": 200,
  "mensaje": "Inicio de sesión correcto.",
  "datos": {
    "token": "<TOKEN_DE_PRUEBA_NO_REAL>",
    "tipo": "Bearer",
    "expira_en": 3600,
    "usuario": {"id": 12, "username": "usuario_ejemplo", "roles": ["consulta"]}
  }
}
```

**Crear usuario — 201** (el hash nunca aparece):

```json
{
  "estado": 201,
  "mensaje": "Usuario creado exitosamente.",
  "datos": {"id": 12, "username": "usuario_ejemplo", "email": "ejemplo@dominio.invalid", "nombre_completo": null}
}
```

**Duplicado — 409:**

```json
{
  "estado": 409,
  "mensaje": "Conflicto de datos.",
  "detalle": "Ya existe un registro con ese identificador único."
}
```

**Campo inválido — 422:**

```json
{
  "estado": 422,
  "mensaje": "Datos inválidos.",
  "errores": ["El campo username es obligatorio."]
}
```

El texto de estos ejemplos es **propuesta nueva de v3**, no un contrato ejecutado; revisarlo con el equipo para conservar mensajes consistentes en todos los controladores.

### 9.4 Pantallas PHP de esta versión (rutas web sujetas a C10)

| Pantalla del frontend | Acceso | Llamadas HTTP a la API |
|---|---|---|
| Formulario de inicio de sesión | Público | `POST /api/login` |
| Menú de investigación | Consulta y admin | GET de los recursos permitidos con Bearer |
| Administración de usuarios | Solo admin | `/api/usuario` según operación |
| Administración de roles | Solo admin | `/api/rol` según operación |
| Asignaciones de rol | Solo admin | `/api/rol_usuario` según operación C8 |
| Acción de salir | Sesión activa | Cierra sesión **del frontend**, no necesita `POST /api/logout` salvo decisión C7 |

Las rutas **web** (`/login`, `/salir` u otras) deben definirse tras leer `front_php/index.php` actual; no se afirma que ya existan. La pantalla también debe distinguir lista vacía (`204`) de API caída y preservar formularios sin recuperar contraseñas.
