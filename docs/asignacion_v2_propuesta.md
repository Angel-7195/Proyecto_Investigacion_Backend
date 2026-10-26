# Asignación de responsabilidades — v2 Investigación (propuesta)

La versión 2 comprende los **diez recursos con claves foráneas** del módulo de
Investigación. Se conservan y deben continuar funcionando los seis recursos de
la versión 1.

La implementación se divide entre los integrantes del equipo para permitir
trabajo independiente mediante ramas y Pull Requests. Esta asignación es una
**propuesta para revisión del equipo**, no una decisión aprobada automáticamente.

## Distribución de recursos

| Responsable | Recurso | Dificultad estimada | Motivo principal |
|---|---|---|---|
| Ángel David Gutiérrez Ladino | `docente` | Alta | Muchos campos, distintos tipos de datos y una clave foránea opcional hacia `linea_investigacion`. |
| Ángel David Gutiérrez Ladino | `grupo_investigacion` | Media-alta | Varias validaciones y clave foránea opcional hacia `universidad`. |
| Ángel David Gutiérrez Ladino | `semillero` | Media | Clave foránea obligatoria hacia `grupo_investigacion`; depende de ese recurso. |
| Ángel David Gutiérrez Ladino | `participa_semillero` | Alta | Clave primaria compuesta, dos claves foráneas, rol y fechas de participación. |
| Ángel David Gutiérrez Ladino | `participa_grupo` | Alta | Clave primaria compuesta, dos claves foráneas, rol y fechas de participación. |
| Miguel Angel Lotero Alvarez | `semillero_linea` | Media-baja | Tabla de asociación con dos claves foráneas y clave primaria compuesta. |
| Miguel Angel Lotero Alvarez | `grupo_linea` | Media-baja | Tabla de asociación con dos claves foráneas y clave primaria compuesta. |
| Miguel Angel Lotero Alvarez | `ac_linea` | Media-baja | Asociación de líneas de investigación con áreas de conocimiento. |
| Miguel Angel Lotero Alvarez | `ods_linea` | Media-baja | Asociación de líneas de investigación con ODS. |
| Miguel Angel Lotero Alvarez | `aa_linea` | Media-baja | Asociación de líneas de investigación con áreas de aplicación. |

**Nota:** Las dificultades son estimaciones basadas en el esquema de `db/init.sql`,
no en una medición objetiva del tiempo de implementación. Las cinco asociaciones
asignadas a Miguel no son CRUD triviales: todas requieren identificar registros
mediante **dos campos de clave primaria** y validar ambas relaciones.

## Alcance de la responsabilidad

Cada responsable implementa para sus recursos:

- modelo;
- interfaz del repositorio;
- repositorio MariaDB con PDO y consultas parametrizadas;
- interfaz del servicio;
- servicio y validaciones de negocio;
- controlador y respuestas HTTP/JSON;
- pruebas correspondientes, incluyendo errores y borrado lógico;
- vistas, formularios y rutas del frontend, que consumirán la API por HTTP;
- actualización de la documentación del recurso cuando corresponda.

La implementación debe respetar `1_constitution.md`, `2_spec.md`,
`3_plan.md`, `5_data_model.md` y `6_contracts.md` vigentes. Antes de implementar,
el equipo debe acordar los contratos de recursos con clave primaria compuesta,
el tratamiento de relaciones inactivas y la posible reactivación de asociaciones.

## Dependencias y orden sugerido

1. **Ángel:** empezar por `grupo_investigacion` y después `semillero` para
   habilitar las asociaciones que dependen de ellos. `docente` puede desarrollarse
   en paralelo, pues depende de `linea_investigacion`, que ya existe en V1.
2. **Miguel:** empezar por `ac_linea`, `ods_linea` y `aa_linea`, pues sus tablas
   relacionadas pertenecen a V1. Esto permite definir y probar temprano el
   manejo de claves primarias compuestas.
3. **Ambos:** acordar el diseño de rutas HTTP con dos identificadores, los
   formatos de formularios con selectores de claves foráneas, y las validaciones
   de duplicados, registros inactivos e integridad referencial.
4. **Ángel:** implementar `participa_grupo` y `participa_semillero` una vez
   disponibles `docente`, `grupo_investigacion` y `semillero`.
5. **Miguel:** completar e integrar `grupo_linea` y `semillero_linea` cuando
   estén disponibles los recursos base de Ángel. Se pueden adelantar modelos,
   repositorios y pruebas con datos controlados.

## Particularidades del esquema SQL

- `docente` referencia opcionalmente a `linea_investigacion` mediante
  `linea_investigacion_principal`.
- `grupo_investigacion` referencia opcionalmente a `universidad` mediante la
  columna SQL **`universidsad`** (grafía existente en el script).
- `docente` contiene la columna SQL **`nacionalidaad`** (grafía existente).
- `semillero` requiere la clave foránea `grupo_investigacion`.
- Las siete tablas de asociación (`participa_semillero`, `participa_grupo`,
  `semillero_linea`, `grupo_linea`, `ac_linea`, `ods_linea` y `aa_linea`) tienen
  claves primarias compuestas.
- Las tablas de la V2 utilizan el campo `activo` para borrado lógico, de acuerdo
  con el script actual. No deben borrarse físicamente registros por defecto.
- Las tablas `rol`, `usuario` y `rol_usuario` quedan fuera de esta asignación;
  pertenecen a la etapa de gestión de usuarios prevista para V3.

## Archivos compartidos

Para reducir conflictos entre ramas, los archivos que integran varios recursos
serán administrados principalmente por **Ángel David Gutiérrez Ladino**, tal
como se hizo en V1:

- `docker-compose.yml`;
- `api_investigacion/index.php`;
- `api_investigacion/servicios/ensamblador.php`;
- documentación general del Spec Kit;
- archivos generales de configuración.

El frontend se mantiene en su repositorio independiente. Los cambios a archivos
compartidos del frontend se coordinan entre ambos integrantes para evitar
conflictos y conservar los seis CRUD de V1.

Cuando un recurso desarrollado por otro integrante necesite integrarse en uno
de estos archivos, el cambio se incorporará de manera coordinada mediante
Pull Request.

## Flujo de Git

- Cada integrante trabaja en su propia rama de trabajo para V2, tanto en el
  backend como en el frontend cuando corresponda.
- No se realizan `push` directos a `main`.
- Todo cambio que llegue a `main` debe hacerlo mediante Pull Request.
- Cada Pull Request debe contener cambios relacionados con la responsabilidad
  asignada al integrante.
- Cada integrante prueba el funcionamiento de sus recursos y verifica que los
  CRUD de V1 no se hayan afectado antes de solicitar la integración.

## Decisiones por cerrar antes de implementar

- Forma definitiva de los endpoints para buscar, actualizar y retirar relaciones
  identificadas por **dos columnas**.
- Comportamiento del POST cuando ya existe una asociación con `activo = FALSE`.
- Reglas de validación de fechas para `participa_semillero` y `participa_grupo`.
- Política sobre uso de claves foráneas que apuntan a registros inactivos.

Estas decisiones deben quedar en los contratos y especificaciones de V2 antes
que se implementen de manera distinta en cada rama.
