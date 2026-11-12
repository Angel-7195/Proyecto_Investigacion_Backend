# Comparación trazable: Spec Kit V2 recibido → Spec Kit V3 propuesto

> **Fuente directa:** comprimido `v2_entidades_con_fk(1).rar`, que contiene `2_spec.md`, `3_plan.md`, `4_research.md`, `5_data_model.md`, `6_contracts.md`, `7_quickstart.md`, `8_tasks.md`, `9_checklist.md` y `GUIA_IA2.md`. Documentos fechados **29-10-2026**. Este anexo contrasta **documentación**, no demuestra el estado del código ni de MariaDB. **Fuentes generales del curso:** `0_METODOLOGIA.md` y `modulo_investigacion.md`.

## 1. Resultado esencial

La V2 especifica e informa implementación de **10 recursos con FK** añadidos a **6 recursos V1** (16 en total). V3 **no modifica** su modelo de investigación: añade `POST /api/login`, JWT, sesiones frontend, permisos, menú por roles, logout y administración exclusiva para `admin` de las tres tablas `usuario`, `rol`, `rol_usuario`.

| Regla documentada de V2 | Evidencia documental V2 | Consecuencia para V3 |
|---|---|---|
| Backend PHP 8.3 + tres capas, repositorios PDO y ensamblador | `3_plan.md` §§1–2 | Añadir autenticación como dependencias nuevas, sin SQL en controladores ni cambiar las interfaces de 16 entidades por capricho |
| Organización por recursos `Repositorios_<Entidad>/`, `Servicios_<Entidad>/` | `3_plan.md` §2 | Seguir nombres reales al agregar usuario, rol y rol_usuario; el árbol V3 es provisional |
| Frontend independiente, rutas `rutas_<entidad>.php` y vistas por entidad | `3_plan.md` §3 | Login y administración deben tener páginas del frontend; no compartir código con la API |
| Cliente HTTP existente usa streams, no cURL | `2_spec.md` C11 y `3_plan.md` §3 | Resolver cumplimiento de cURL académica; Bearer se debe enviar por HTTP cualquiera que sea el cliente aprobado |
| Cinco tablas puente con solo dos FK+activo | `2_spec.md` RF-V2-04/05; `6_contracts.md` §1.1 | **PUT/PATCH → 405** para usuario autorizado; no convertirlas en CRUD editables |
| Dos participaciones con rol y fechas editables | `2_spec.md` RF-V2-09 | Preservar PUT/PATCH, validaciones de fechas y rol académico independiente de rol de seguridad |
| Siete PK compuestas identificadas por 2 segmentos | `5_data_model.md` §2; `6_contracts.md` §1 | No alterar rutas ni inventar IDs; `ac_linea.area_conocimiento` es `VARCHAR(6)` |
| `PATCH {}` V2 revisado → 422, pero V1 esperaba 400 | `2_spec.md` RF-V2-05; `6_contracts.md` §0 | Regresión separada por recurso; no uniformar sin aclaración y cambio aprobado |
| FK no activa y pareja duplicada → 409; retirado → 404 | `2_spec.md` RF-V2-03/06/07 | Estos resultados siguen vigentes si el actor tiene permiso; si no, preceden 401/403 |
| Borrado lógico `activo=FALSE` | `2_spec.md` RF-V2-06 | Se mantiene en Investigación y en `usuario`/`rol`; no inventar `activo` para `rol_usuario` sin revisar DDL |
| V2 no incluye JWT ni roles de acceso | `2_spec.md` RF-V2-12 | El nuevo control de acceso debe ser transversal, no debe esconderse dentro de los servicios investigativos |

## 2. Inventario V2 heredado (documentado)

### Seis recursos V1

`area_conocimiento`, `objetivo_desarrollo_sostenible`, `area_aplicacion`, `termino_clave`, `universidad`, `linea_investigacion`.

### Cinco recursos V2 con campos editables

| Recurso | Identificador individual | GET lista/ficha, POST, PUT, PATCH, DELETE |
|---|---|---|
| `docente` | `/api/docente/{cedula}` | Sí |
| `grupo_investigacion` | `/api/grupo_investigacion/{id}` | Sí |
| `semillero` | `/api/semillero/{id}` | Sí |
| `participa_grupo` | `/api/participa_grupo/{docente_cedula}/{grupo_investigacion_id}` | Sí |
| `participa_semillero` | `/api/participa_semillero/{docente}/{semillero}` | Sí |

### Cinco asociaciones V2 sin campos editables

| Recurso | Ruta individual | PUT/PATCH autorizados |
|---|---|---|
| `grupo_linea` | `/api/grupo_linea/{grupo_investigacion}/{linea_investigacion}` | `405` |
| `semillero_linea` | `/api/semillero_linea/{semillero}/{linea_investigacion}` | `405` |
| `ac_linea` | `/api/ac_linea/{linea_investigacion}/{area_conocimiento}` | `405` |
| `ods_linea` | `/api/ods_linea/{linea_investigacion}/{ods}` | `405` |
| `aa_linea` | `/api/aa_linea/{area_aplicacion}/{linea_investigacion}` | `405` |

En las cinco, GET de lista/ficha y POST de colección y DELETE de ficha están admitidos. En todas las V2, una clave foránea inexistente o retirada debe manejarse de acuerdo con su contrato `409`, y las PK compuestas no pueden editarse.

## 3. Qué cambia en V3 (propuesta para compuerta 1)

| Tipo de actor | Login | GET Investigación | Escrituras Investigación | Gestión `usuario`/`rol`/`rol_usuario` |
|---|---|---|---|---|
| Sin sesión/token | Público, si credenciales válidas | `401` | `401` | `401` |
| `consulta` | Permitido | Acceso según matriz | `403` | `403` |
| `admin` | Permitido | Acceso | Acceso según métodos ya permitidos | Solo admin, sujeto al contrato de gestión |

**Importante:** la matriz de permisos propuesta debe aprobarse antes de implementarse; la norma sí exige que `consulta` no escriba y que solo `admin` gestione usuarios/roles. Si la identidad es `consulta` y envía PUT a `grupo_linea`, la seguridad propuesta devuelve `403` **antes** de llegar al `405`. Con `admin` el mismo PUT llega a la ruta y devuelve `405`.

El campo `rol` de `participa_grupo`/`participa_semillero` **no equivale** a los roles de autorización; esos campos son descriptores de participación en la actividad de investigación.

## 4. Pendientes que no se deben ocultar

1. **DDL de seguridad recibido después del comprimido V2:** `init(4).sql` confirma `id INT AUTO_INCREMENT` en usuario/rol, UNIQUE para `username`, `email`, `rol.nombre`, PK compuesta y FK ON DELETE CASCADE en `rol_usuario`; esta última tabla no tiene `activo`. El DDL ya está registrado en `5_data_model.md`, pero la **BD en ejecución** debe compararse con el script antes de implementar.
2. **Decisiones de seguridad V3 (actualización 10-11-2026):** el equipo aprobó login por `username`, roles `admin`/`consulta`, JWT de 60 minutos, logout de sesión PHP, primera cuenta por CLI y DELETE físico solo de asignaciones; el bootstrap manual ya produjo una cuenta `admin` activa. Los detalles de implementación/seguridad y pruebas continúan pendientes según `REGISTRO_FASE1_ADMIN.md`.
3. **Pendientes V2 fechados:** según `2_spec.md` C4/C11 y `9_checklist.md` V2, faltaban al 29-10 política de retiro de padres, pronunciamiento sobre cURL, revisión de DDL final, regresión integrada, prueba API apagada, cierre humano y tag. Esto **no contradice** que después se haya integrado o cerrado: el usuario mostró PR #15 y `main` actualizado a `9bcf84b`. Confirmar estado actual; no reabrir la versión automáticamente.
4. **Front y API reales no recibidos:** el `.rar` contiene exclusivamente documentos, por lo que las afirmaciones de implementación se atribuyen al **Spec Kit V2**, no a inspección independiente del código.
5. **Constitución y mapa:** la constitución antigua del repositorio guía contiene una excepción sobre contraseñas locales y su mapa de versiones no coincide con el alcance del módulo. Para este proyecto académico prevalece la metodología que exige credenciales y `JWT_SECRET` por entorno.

## 5. Protocolo de revisión antes de programar

- Cotejar en la base MariaDB activa las tres tablas con `SHOW CREATE TABLE`, partiendo del DDL comprobado de `init(4).sql`, y revisar la constitución del equipo.
- Confirmar C3–C11 en `2_spec.md` con el compañero y registrar la resolución por escrito.
- Validar la política de roles por ruta y método; demostrar que un menú oculto no es un control de autorización.
- Ejecutar al final regresión de 16 recursos por grupos y probar 401/403, 405 de cinco asociaciones, validaciones y caída de API.
- Aprobación humana del checklist, PR a `main` y tag `v3` cuando las pruebas estén verdes.

## 6. Correspondencia con la v3 del profesor (`v3_segundo_motor`)

| Patrón del ejemplo del profesor | Aplicación a nuestra v3 | No copiar |
|---|---|---|
| Especificar el **delta** sobre v2, sin romper contratos | Añadir `401/403` a las rutas de los 16 CRUD previos y conservar códigos de negocio para admin | PostgreSQL, repositorios para otro motor |
| Planificar por capas con cambios localizados | Guardas antes de controladores, servicios de seguridad, datos por PDO | Variable `MOTOR`, fábrica de conexión Postgres |
| Investigar alternativas antes de decidir | JWT en PHP puro, roles actuales vs claims fijos, bootstrap CLI vs credenciales en SQL | Traductores de errores por motor |
| Comprobar el mismo comportamiento previo con nuevas condiciones | Regresión de 16 CRUD con Bearer y prueba de `consulta` denegada | Comparar los datos entre MariaDB y PostgreSQL |
| `9_checklist.md` revisa requisitos **antes** de programar | Compuerta previa y lista de cierre funcional separadas | Autoaprobar criterios solo porque existen documentos |

**Fuente:** RAR entregado por el usuario del ejemplo del profesor; `0_METODOLOGIA.md` §2.2 y `modulo_investigacion.md` fijan el alcance distinto de nuestra versión. Esta tabla evita interpretar «v3» como el mismo conjunto de funcionalidades en ambos proyectos.
