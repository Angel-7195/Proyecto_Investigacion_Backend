# 2. Especificación — V2: entidades con claves foráneas

> ESTADO: BORRADOR para revisión del equipo. Es un DELTA sobre la V1 de este proyecto, que especifica seis recursos sin FK. No autoriza implementar hasta superar las compuertas de clarificación y checklist.

## 1. Objetivo y alcance

Construir en la API REST de Investigación y su frontend PHP el CRUD completo de las diez entidades con clave foránea del módulo, manteniendo funcionando los seis CRUD de la V1. Cada recurso tiene sus propias rutas, reglas y pantalla, sin CRUD SQL genérico. Los campos FK se seleccionan desde listas obtenidas por HTTP de recursos activos de la API.

| Recurso V2 | Relación de negocio descrita en el módulo |
|---|---|
| `docente` | Perfil investigador; referencia su línea principal de investigación |
| `grupo_investigacion` | Grupo; referencia una universidad |
| `semillero` | Semillero; referencia un grupo de investigación |
| `participa_grupo` | Participación de un docente en un grupo, con rol y fechas |
| `participa_semillero` | Participación de un docente en un semillero, con rol y fechas |
| `grupo_linea` | Asociación de grupo con línea de investigación |
| `semillero_linea` | Asociación de semillero con línea de investigación |
| `ac_linea` | Asociación de línea con área de conocimiento |
| `ods_linea` | Asociación de línea con objetivo de desarrollo sostenible |
| `aa_linea` | Asociación de línea con área de aplicación |

**Nota:** las relaciones anteriores están fundamentadas en `modulo_investigacion.md`. La copia `init(2).sql` aportada permitió completar el inventario de columnas, PK/FK y nulabilidad en `5_data_model.md`. Falta cotejarlo con el archivo `db/init.sql` de la rama real y **aprobar las decisiones de negocio pendientes**. No inventar estructura.

## 2. Requisitos funcionales verificables

- **RF-V2-01 — Listar:** cada recurso permite listar registros activos; un listado sin activos devuelve `204` y el frontend lo reconoce como vacío, no como error.
- **RF-V2-02 — Consultar:** consultar cada registro mediante su identificador real, definido en `5_data_model.md`; uno inexistente o inactivo devuelve `404`.
- **RF-V2-03 — Crear:** crear registros válidos respetando las PK y campos `NOT NULL` del SQL; si la FK es obligatoria, debe apuntar a una fila existente y activa. En `docente` y `grupo_investigacion`, las FK indicadas como `NULL` en el SQL pueden omitirse o ser nulas; al informarse un valor, se valida la referencia.
- **RF-V2-04 — Reemplazar:** en los recursos con campos editables, `PUT` exige una ficha completa editable sin cambio de PK ni `activo`; en las cinco tablas puente sin atributos editables además de PK/`activo`, definir por decisión explícita si PUT/PATCH serán `405` o tendrá sentido una operación de modificación. [NECESITA ACLARACIÓN].
- **RF-V2-05 — Actualizar:** en recursos con campos editables, `PATCH` solo altera los campos enviados; `{}` devuelve `400`, campos desconocidos o inválidos devuelven `422`. No imponer esta operación a puentes sin campos editables hasta resolver la clarificación correspondiente.
- **RF-V2-06 — Retirar:** `DELETE` marca `activo = FALSE` donde el esquema y las reglas del módulo lo soportan; no elimina físicamente la fila. Un segundo retiro devuelve `404`.
- **RF-V2-07 — Integridad:** una FK incompatible o inexistente no se guarda; se traduce a `409 Conflict` según el contrato. No exponer errores SQL al cliente.
- **RF-V2-08 — Selectores FK:** formularios obtienen por HTTP opciones actuales de entidades relacionadas, muestran etiquetas comprensibles y envían la llave verdadera. No incorporan valores FK inventados en el HTML.
- **RF-V2-09 — Tablas puente:** las siete asociaciones usan PK compuesta **de dos columnas** según el SQL compartido; se crean, consultan y retiran según ella. Una asociación activa duplicada responde `409`; decidir expresamente el tratamiento del duplicado lógico retirado.
- **RF-V2-10 — Separación:** backend PHP 8.3+ por capas; frontend PHP con HTML y CSS/Bootstrap local consume solo HTTP, nunca PDO. La metodología pide **cURL**: revisar la implementación V1 actual, que venía utilizando `file_get_contents` con `stream_context_create`, y acordar su adaptación sin alterar el contrato.
- **RF-V2-11 — Regresión:** los seis recursos V1, rutas, sobres JSON, borrado lógico, errores, pantalla y prueba de desacoplamiento continúan funcionando.
- **RF-V2-12 — Identidad del usuario:** no se adelantan JWT, roles, menú por permisos, dashboard, PWA ni publicación, reservados para V3/V4.
- **RF-V2-13 — Reorganización sin cambios externos:** agrupar archivos de `repositorios/` y `servicios/` por recurso; agrupar `vistas/` por recurso; conservar nombres de clases, métodos públicos, rutas y cuerpos HTTP ya publicados.

## 3. Flujos de usuario

1. Un usuario abre «Semilleros», crea uno y selecciona un grupo activo en un desplegable cargado por la API. Se guarda y aparece en el listado.
2. Si selecciona un grupo que ya no existe o se encuentra retirado cuando se procesa la operación, el sistema rechaza el guardado sin perder el formulario; el frontend explica el conflicto.
3. Un usuario asocia una línea activa a un grupo mediante `grupo_linea`, y puede consultar o retirar esa asociación conforme al esquema y las reglas de V2.
4. Al apagar la API y mantener encendida la base, el frontend conserva la navegación y muestra indisponibilidad, sin acceder a datos.

## 4. Fuera del alcance

- Implementar autenticación JWT o control por roles de V3.
- Agregar tablas, columnas o modificar claves del DDL para simplificar el CRUD.
- Generalizar una ruta `/api/{tabla}` o compartir clases entre frontend y API.
- Consultas analíticas de cuatro o más tablas, dashboard, PWA o despliegue de V4.

## 5. Criterios de aceptación

1. CRUD API + interfaz para las diez entidades con FK (total acumulado: 16 CRUD).
2. Los desplegables se alimentan de la API, no de listas fijas.
3. Un semillero no puede referenciar un grupo inexistente o inactivo; el estado de conflicto es comprensible para la pantalla.
4. Las asociaciones de tablas puente respetan sus claves reales y no se duplican.
5. Se prueba `201`, `200`, `204`, `400`, `404`, `405`, `409`, `422`, `500` donde correspondan, sin cambiar los contratos de V1.
6. El borrado sigue siendo lógico y los registros retirados no aparecen en consultas normales.
7. La reestructuración no rompe los `require_once`, el ensamblador, el router ni las pruebas.
8. Las pruebas de las seis entidades V1 pasan completas (regresión).
9. El frontend responde sin la API, sin recuperar datos ni usar PDO.
10. Quickstart, checklist, colección de pruebas y contratos reflejan rutas y comandos **realmente comprobados** antes de cerrar V2.

## 6. Clarificaciones — COMPUERTA 1

No resolver estos puntos por suposición en el código.

- [x] **C1 / copia del esquema:** transcritas columnas, tipos, longitudes, nulabilidad y claves de `init(2).sql` a `5_data_model.md`. **Pendiente:** cotejo final con el `db/init.sql` del repositorio y revisión de reglas de negocio no expresadas en SQL.
- [x] **C2 / llaves en SQL:** verificadas tres PK simples (`docente.cedula`, `grupo_investigacion.id`, `semillero.id`) y siete PK compuestas. **Pendiente:** aprobar el diseño de rutas individuales de dos componentes.
- [x] **C3 / identificadores de SQL:** las tres PK simples V2 no usan `AUTO_INCREMENT`, igual que los componentes de las PK compuestas. **Pendiente:** decidir operativamente si una asociación retirada se reactiva o se rechaza y cómo identificar sus operaciones.
- [ ] **C4 / integridad lógica:** definir si se permite retirar un padre (`grupo_investigacion`, `universidad`, etc.) con hijos activos; dado que DELETE es lógico, la FK física por sí sola no impide inconsistencias funcionales.
- [ ] **C5 / validación FK:** determinar si todas las relaciones deben apuntar a registros **activos** aun si la columna es nullable; documentar excepciones, si existen.
- [ ] **C6 / fechas y roles:** contrastar contra SQL formatos, nulabilidad y reglas de participación (`rol`, fechas, categorías), sin inventar validaciones.
- [ ] **C7 / V1 cerrada:** confirmar que pasaron 9_checklist, quickstart actualizado, PR en main y tag `v1`; la documentación disponible aún tenía tareas/checklist sin marcar y quickstart resumido.
- [ ] **C8 / constitución y mapa:** sustituir la distribución del *mapa guía* por el mapa específico del módulo, y resolver diferencias entre constitución del ejemplo y reglas del proyecto (especialmente secretos).

- [ ] **C9 / actualizaciones en tablas puente:** definir PUT/PATCH cuando la asociación solo tiene componentes de PK y `activo` (por ejemplo `grupo_linea`).
- [ ] **C10 / reactivación:** decidir qué sucede cuando se vuelve a crear una pareja de PK que existe físicamente con `activo=FALSE`.
- [ ] **C11 / cURL:** cotejar la implementación de `cliente_api.php` con la metodología que exige cURL y registrar si se adaptará antes del cierre de V2.
- [ ] **C12 / nombres SQL:** conservar `nacionalidaad` y `universidsad` o documentar una corrección de esquema explícita y aprobada. El borrador **no modifica el SQL**.

**Responsables / decisiones tomadas:** __________________________  **Fecha:** __________
