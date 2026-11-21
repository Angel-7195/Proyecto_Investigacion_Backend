# Proyecto de Investigación — Backend PHP

**Versión actual: V3 — Seguridad y administración de usuarios

- Pagina Desplegada con AlwaysData: https://proyectoinvestigacion.alwaysdata.net/
- Usuario: ADMIN
- Contraseña: administrator123

- **Backend:**
  https://github.com/Angel-7195/Proyecto_Investigacion_Backend.git
- **Frontend:**
  https://github.com/Angel-7195/Proyecto_Investigacion_Frontend.git

Proyecto académico del módulo de Investigación, desarrollado con **PHP
puro 8.3**, **MariaDB**, **PDO** y **Docker**. La API REST se organiza
por capas y es consumida mediante HTTP por una aplicación frontend PHP
alojada en un repositorio independiente.

## Integrantes

- **Ángel David Gutiérrez Ladino:** autenticación JWT, autorización,
  administración de `usuario` y `rol_usuario`, e integración de sus
  pantallas en el frontend.
- **Miguel Ángel Lotero Álvarez:** trabajo del módulo de roles y demás
  responsabilidades acordadas por el equipo.

> **Estado documentado al 19 de noviembre de 2026.** La V3 se encuentra
> en desarrollo avanzado, pero **todavía no está cerrada**. El catálogo
> fijo `rol` ya está integrado como recurso de solo consulta y cuenta
> con verificaciones aisladas, HTTP para `admin` y revisión visual del
> frontend. Siguen pendientes la comprobación HTTP de los permisos de
> `consulta` sobre ese catálogo, la regresión integral de V1/V2, otras
> pruebas de seguridad e instalación, la revisión conjunta y el proceso
> de PR/tag.

## 1. Evolución del proyecto

| Versión | Alcance                                                                                                                              | Estado conocido                                                                                                                                                                |
|---------|--------------------------------------------------------------------------------------------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **V1**  | Seis recursos de Investigación sin claves foráneas.                                                                                  | Implementados y comprobados previamente por el equipo.                                                                                                                         |
| **V2**  | Diez recursos adicionales con claves foráneas y asociaciones; conserva los seis de V1.                                               | Código integrado; queda pendiente registrar y verificar la regresión final de todos los contratos durante V3.                                                                  |
| **V3**  | Login JWT, sesiones PHP, permisos `admin`/`consulta`, administración de `usuario`, catálogo fijo `rol` y asignaciones `rol_usuario`. | **En desarrollo avanzado**. Login, middleware, `usuario`, `rol_usuario` y las operaciones de consulta de `rol` tienen verificaciones; faltan comprobaciones y cierre integral. |
| **V4**  | Consultas multitabla, dashboard, mejoras visuales y publicación.                                                                     | Pendiente; fuera del alcance de esta rama.                                                                                                                                     |

La base de datos contiene **19 tablas**: 16 del módulo de Investigación
y tres de seguridad (`usuario`, `rol`, `rol_usuario`). La V3 agrega
identidad y permisos sin sustituir los contratos de negocio de V1/V2.

## 2. Arquitectura

El sistema está dividido en dos repositorios:

- **`Proyecto_Investigacion_Backend` (este repositorio):** API REST en
  PHP, controladores, servicios, repositorios PDO, modelos, seguridad y
  pruebas.
- **`Proyecto_Investigacion_Frontend`:** vistas y formularios PHP;
  utiliza sesiones y consume la API por HTTP. **No debe conectarse
  directamente a MariaDB.**

El recorrido de una petición protegida es:

``` text
Navegador → Frontend PHP (sesión) → API (Bearer JWT)
                                   ↓
                         Middleware de autorización
                                   ↓
                       Controlador → Servicio → Repositorio PDO → MariaDB
```

La API valida el JWT y **vuelve a consultar los roles vigentes en
MariaDB** antes de permitir una operación. El menú del frontend refleja
los permisos, pero **no reemplaza** la protección del backend.

### Estructura principal

``` text
Proyecto_Investigacion_Backend/
├── api_investigacion/
│   ├── index.php                         # Enrutador principal de la API
│   ├── controladores/                    # Entradas HTTP por recurso
│   ├── servicios/                        # Reglas de negocio y ensamblador
│   ├── repositorios/                     # Interfaces e implementaciones PDO
│   ├── modelos/                          # Entidades
│   ├── middleware/                       # Comprobación de JWT y permisos
│   ├── excepciones/                      # Errores de negocio y acceso
│   ├── scripts/
│   │   └── inicializar_admin.php         # Primer ADMIN; ejecución solo por CLI
│   └── pruebas/                          # Pruebas aisladas de la API
├── db/
│   └── init.sql                          # Esquema y semillas del catálogo de roles
├── docs/
│   └── spec_kit/
│       └── versiones/
│           ├── v1_investigacion/
│           ├── v2_entidades_con_fk/
│           └── v3_seguridad/
├── docker-compose.yml
├── .env.example
└── README.md
```

**Separación de responsabilidades:** los controladores procesan HTTP;
los servicios aplican las reglas; los repositorios ejecutan SQL
preparado; el ensamblador construye las dependencias. El frontend
permanece en otro repositorio.

## 3. Requisitos y puesta en marcha

Se necesita **Docker Desktop / Docker Compose** y una copia local de
ambos repositorios cuando se utilice la interfaz web.

1.  Clonar el backend y el frontend en carpetas independientes.

2.  Desde la raíz del backend, crear `.env` a partir de `.env.example` y
    configurar las credenciales locales de MariaDB, `FRONT_PATH` con la
    ruta real del frontend y un `JWT_SECRET` aleatorio de al menos 32
    bytes. **No subir `.env` ni publicar secretos.**

3.  Levantar los servicios desde la raíz del backend:

    ``` powershell
    docker compose up -d --build
    docker compose ps
    ```

4.  Verificar los puertos efectivos en `docker-compose.yml`.

5.  Para generar una clave secreta JWT en PowerShell:

    ``` powershell
    $bytes = New-Object byte[] 32
    [System.Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($bytes)
    [Convert]::ToBase64String($bytes)
    ```

6.  Copiar el resultado en `.env` como valor de `JWT_SECRET`:

    ``` dotenv
    JWT_SECRET=CLAVE_GENERADA
    ```

### Servicios del entorno local utilizado

| Servicio                | Acceso habitual       |
|-------------------------|-----------------------|
| Frontend                | http://localhost:8110 |
| API                     | http://localhost:8111 |
| phpMyAdmin              | http://localhost:8105 |
| MariaDB desde el equipo | `localhost:13330`     |

Estos puertos corresponden al entorno probado y pueden cambiar según la
configuración Docker.

Para detener los contenedores **sin borrar el volumen de MariaDB**:

``` powershell
docker compose down
```

> **Precaución:** no usar `docker compose down -v` durante el trabajo
> normal; `-v` elimina los volúmenes y puede borrar la base de datos
> local.

### Inicializar el primer administrador

En una instalación nueva, los roles públicos `admin` y `consulta` se
preparan desde SQL/configuración. La primera cuenta administradora se
crea mediante el script **exclusivo de consola**, no mediante un
endpoint público. En la configuración probada, este paso **no se ejecuta
automáticamente** al levantar Docker.

Con MariaDB levantada, abrir PowerShell en la raíz del backend y
ejecutar **una sola vez cuando aún no exista un administrador válido**:

``` powershell
$env:ADMIN_INICIAL_USERNAME = Read-Host 'Usuario administrador inicial'
$env:ADMIN_INICIAL_EMAIL = Read-Host 'Correo del administrador'
$claveSegura = Read-Host 'Contraseña inicial (mínimo 12 caracteres)' -AsSecureString

try {
    $env:ADMIN_INICIAL_PASSWORD = [System.Net.NetworkCredential]::new('', $claveSegura).Password

    docker compose run --rm --no-deps `
        -e ADMIN_INICIAL_USERNAME `
        -e ADMIN_INICIAL_EMAIL `
        -e ADMIN_INICIAL_PASSWORD `
        api-investigacion php /app/scripts/inicializar_admin.php
} finally {
    Remove-Item Env:ADMIN_INICIAL_USERNAME -ErrorAction SilentlyContinue
    Remove-Item Env:ADMIN_INICIAL_EMAIL -ErrorAction SilentlyContinue
    Remove-Item Env:ADMIN_INICIAL_PASSWORD -ErrorAction SilentlyContinue
    Remove-Variable claveSegura -ErrorAction SilentlyContinue
}
```

El script usa `password_hash()` y registra la asociación `admin` en
`rol_usuario`. La primera ejecución manual se comprobó en la base local;
siguen pendientes las pruebas completas de idempotencia, instalación
limpia y recuperación de estados inconsistentes. No publicar contraseñas
ni hashes en logs, capturas o Git.

## 4. Recursos de Investigación — V1 y V2

La API conserva rutas específicas por entidad. No existe un controlador
genérico de `/api/{tabla}`.

**Seis recursos de V1:**

``` text
/api/area_conocimiento
/api/objetivo_desarrollo_sostenible
/api/area_aplicacion
/api/termino_clave
/api/universidad
/api/linea_investigacion
```

**Diez recursos añadidos en V2:**

``` text
/api/docente
/api/grupo_investigacion
/api/semillero
/api/participa_grupo
/api/participa_semillero
/api/grupo_linea
/api/semillero_linea
/api/ac_linea
/api/ods_linea
/api/aa_linea
```

Los recursos con clave primaria compuesta se identifican mediante sus
**dos segmentos** en las rutas de detalle. Los cinco vínculos simples
`grupo_linea`, `semillero_linea`, `ac_linea`, `ods_linea` y `aa_linea`
permiten GET/POST/DELETE, pero **no PUT/PATCH** (`405` para usuarios
autorizados). Las entidades editables y las dos participaciones siguen
sus contratos propios de V2. Consultar [contratos
V2](docs/spec_kit/versiones/v2_entidades_con_fk/6_contracts.md) para
campos, métodos y códigos exactos.

En V3, todas las rutas de estos recursos requieren autenticación. El
permiso se aplica **por método**: `consulta` puede leer los 16 recursos
de Investigación; `admin` puede efectuar las operaciones permitidas por
cada contrato.

## 5. Seguridad — V3

### Autenticación y JWT

- `POST /api/login` recibe `username` y `password`; es el único inicio
  de sesión público.
- Las contraseñas se validan con `password_verify()` y se almacenan
  mediante `password_hash(PASSWORD_DEFAULT)`.
- El token es **JWT HS256**, firmado con `JWT_SECRET` privado y válido
  durante **3600 segundos (60 minutos)**.
- Las solicitudes a la API protegida envían
  `Authorization: Bearer <token>`.
- Los tokens ausentes, inválidos o vencidos se rechazan con **401**; las
  cuentas autenticadas sin permisos para la operación reciben **403**.
- El sistema no expone hashes ni contraseñas en el JSON de usuario.
- Una cuenta creada **sin un rol activo no puede iniciar sesión**. El
  alta de `usuario` no asigna roles automáticamente.

### Roles y autorización

El sistema utiliza dos tipos de rol fijos:

| Rol        | Permisos                                                                                                                                 |
|------------|------------------------------------------------------------------------------------------------------------------------------------------|
| `admin`    | Consultar y modificar los recursos V1/V2 según sus contratos; administrar usuarios y asignaciones de roles; consultar el catálogo `rol`. |
| `consulta` | Consultar los 16 recursos V1/V2. No puede escribir ni acceder a las funciones administrativas.                                           |

Se puede asociar más de un rol a un usuario. **La autorización
administrativa depende del nombre `admin` vigente en la base de datos**,
no de un ID numérico universal. Los identificadores de los roles los
genera MariaDB; los valores observados en una instalación local no deben
asumirse como universales.

Cuando se desactiva una cuenta o se retira su último rol, la
autorización se deniega aun si el JWT anterior no ha expirado.

### Administración de `usuario`

**Acceso exclusivo para `admin`:**

| Método y ruta                      | Operación                                             |
|------------------------------------|-------------------------------------------------------|
| `GET /api/usuario`                 | Listar usuarios activos.                              |
| `GET /api/usuario/{id}`            | Consultar uno activo.                                 |
| `POST /api/usuario`                | Registrar una cuenta; contraseña hasheada.            |
| `PUT /api/usuario/{id}`            | Reemplazar datos editables del perfil.                |
| `PATCH /api/usuario/{id}`          | Modificar solo los campos enviados.                   |
| `PATCH /api/usuario/{id}/password` | Actualizar contraseña, independientemente del perfil. |
| `DELETE /api/usuario/{id}`         | **Borrado lógico:** establecer `activo=0`.            |

El cambio de contraseña aplica también al usuario ADMIN cuando se
realiza mediante una cuenta autorizada. No se permite desactivar al
**último administrador activo**. Esta protección se probó con
repositorios falsos; todavía falta verificarla mediante HTTP sobre una
base de datos descartable.

### Catálogo fijo `rol`

`rol` es un **catálogo de consulta**, no un CRUD administrativo. Solo se
contemplan los nombres `admin` y `consulta`; no se crean, editan ni
retiran tipos de rol desde la API.

**Acceso exclusivo para `admin`:**

| Método y ruta       | Operación                                     |
|---------------------|-----------------------------------------------|
| `GET /api/rol`      | Listar los roles activos del catálogo.        |
| `GET /api/rol/{id}` | Consultar un rol activo por su identificador. |

Para un usuario autenticado con `admin`, los métodos `POST`, `PUT`,
`PATCH` y `DELETE` sobre las rutas del catálogo responden **405 Method
Not Allowed** e incluyen `Allow: GET`.

El campo `activo` permanece en la tabla como dato interno de
persistencia y filtro de consulta; no es editable mediante la API. La
base también conserva `fecha_creacion`. Los identificadores se generan
en la base de datos y no se fijan en el contrato.

En la interfaz del frontend, la pantalla `/roles` presenta el catálogo
con las columnas **ID, Nombre y Descripción**, sin botones para crear,
editar o retirar roles.

La lectura del catálogo por parte de `consulta` debe rechazarse con
**403**, porque se trata de una función administrativa. Esta respuesta
está definida como comportamiento esperado, pero su comprobación HTTP
directa sigue pendiente.

### Administración de `rol_usuario`

**Acceso exclusivo para `admin`:**

| Método y ruta                                   | Operación                    |
|-------------------------------------------------|------------------------------|
| `GET /api/rol_usuario`                          | Listar asignaciones.         |
| `GET /api/rol_usuario/{usuario_id}/{rol_id}`    | Consultar una asignación.    |
| `POST /api/rol_usuario`                         | Asignar un rol a un usuario. |
| `DELETE /api/rol_usuario/{usuario_id}/{rol_id}` | Retirar una asignación.      |

`rol_usuario` tiene clave primaria compuesta `(usuario_id, rol_id)` y
**no tiene columna `activo`**. Por eso se elimina físicamente **solo el
vínculo**, sin borrar el usuario ni el catálogo de roles. Las
asignaciones repetidas se rechazan con `409`; no se usan PUT/PATCH
(`405`). El backend incorpora protección del último administrador; queda
pendiente su prueba HTTP sobre una base descartable.

## 6. Pruebas realizadas y pendientes

### Evidencia de pruebas del backend

| Componente                 | Evidencia obtenida                                                                                                                         |
|----------------------------|--------------------------------------------------------------------------------------------------------------------------------------------|
| Login y JWT                | **15/15** pruebas aisladas; login HTTP real de ADMIN.                                                                                      |
| Middleware de autorización | **28/28** pruebas aisladas; GET sin token `401` y GET con JWT permitido `200`.                                                             |
| CRUD de `usuario`          | **28/28** pruebas aisladas; alta, GET, PUT/PATCH, cambio de contraseña, duplicados y borrado lógico contra API/MariaDB.                    |
| `rol_usuario`              | **15/15** pruebas aisladas; POST `201`, duplicado `409`, GET `200`, DELETE `200` y GET posterior `404`.                                    |
| Catálogo `rol`             | **6 escenarios aislados** aprobados; GET de colección y detalle con `admin` `200`; POST/PUT/PATCH/DELETE con `admin` `405` y `Allow: GET`. |
| Permisos revocados         | Con el **mismo JWT**, la API rechazó con `403` una consulta después de retirar el rol.                                                     |
| Interfaz del catálogo      | Se comprobó visualmente que `/roles` muestra únicamente `admin` y `consulta`, con ID, nombre y descripción, sin acciones de edición.       |

Para ejecutar las pruebas aisladas relevantes desde Docker:

``` powershell
docker compose exec api-investigacion php /app/pruebas/prueba_login_jwt.php
docker compose exec api-investigacion php /app/pruebas/prueba_middleware_autorizacion.php
docker compose exec api-investigacion php /app/pruebas/prueba_usuario_aislada.php
docker compose exec api-investigacion php /app/pruebas/prueba_rol_aislada.php
docker compose exec api-investigacion php /app/pruebas/prueba_rol_usuario_aislada.php
```

En pruebas de navegador se crearon y editaron cuentas temporales, se
verificó `activo=0` tras desactivar un usuario y se
asignó/retiró/restauró `consulta` a una cuenta de prueba. Con su sesión
todavía abierta, el frontend dejó de mostrar los registros al retirarle
el rol y volvió a permitir consultas después de reasignarlo e iniciar
sesión nuevamente. MariaDB mostró que **ADMIN era el único administrador
activo**. Estas pruebas se registran en [evidencias del frontend
V3](docs/spec_kit/versiones/v3_seguridad/REGISTRO_FASE6_FRONTEND.md).

### Pendientes antes del cierre de V3

1.  Comprobar mediante HTTP que `consulta` recibe `403` al intentar
    consultar `/api/rol`.
2.  Revisar nuevamente el selector de roles de la pantalla de
    asignaciones `/roles-usuarios`; la última verificación del catálogo
    no incluyó esa pantalla.
3.  Completar la matriz HTTP de los 16 recursos V1/V2 con `admin` y
    `consulta`, y ejecutar la regresión de las versiones anteriores.
4.  Probar en una **base de datos descartable** la protección del último
    administrador y escenarios negativos adicionales de integridad/HTTP.
5.  Comprobar instalación limpia, idempotencia del bootstrap y ausencia
    de secretos versionados.
6.  Verificar frontend con API apagada, logout/cookies y formularios
    adversariales; resolver con el equipo la diferencia entre streams
    HTTP y cURL indicada por la metodología.
7.  Realizar revisión cruzada, cerrar checklist y compuertas SDD y
    aprobar PR antes de generar el tag `v3` en `main`.

La finalización de las pruebas de un componente no representa la
aprobación automática de toda la versión.

## 7. Documentación Spec Kit

La especificación y el seguimiento de V3 están en
[`docs/spec_kit/versiones/v3_seguridad/`](docs/spec_kit/versiones/v3_seguridad/):

- [Especificación —
  2_spec.md](docs/spec_kit/versiones/v3_seguridad/2_spec.md)
- [Plan técnico —
  3_plan.md](docs/spec_kit/versiones/v3_seguridad/3_plan.md)
- [Investigación y decisiones —
  4_research.md](docs/spec_kit/versiones/v3_seguridad/4_research.md)
- [Modelo de datos —
  5_data_model.md](docs/spec_kit/versiones/v3_seguridad/5_data_model.md)
- [Contratos API y web —
  6_contracts.md](docs/spec_kit/versiones/v3_seguridad/6_contracts.md)
- [Quickstart y pruebas —
  7_quickstart.md](docs/spec_kit/versiones/v3_seguridad/7_quickstart.md)
- [Tareas — 8_tasks.md](docs/spec_kit/versiones/v3_seguridad/8_tasks.md)
- [Checklist —
  9_checklist.md](docs/spec_kit/versiones/v3_seguridad/9_checklist.md)
- [Evidencia de
  usuarios](docs/spec_kit/versiones/v3_seguridad/REGISTRO_FASE4_USUARIO.md)
- [Evidencia de
  `rol_usuario`](docs/spec_kit/versiones/v3_seguridad/REGISTRO_FASE5_ROL_USUARIO.md)
- [Evidencia del
  frontend](docs/spec_kit/versiones/v3_seguridad/REGISTRO_FASE6_FRONTEND.md)
- [Evidencia del catálogo fijo
  `rol`](docs/spec_kit/versiones/v3_seguridad/REGISTRO_FASE7_CATALOGO_ROL_20261119.md)

## 8. Flujo de trabajo con Git

Los desarrollos individuales se realizan en ramas de funcionalidad. Las
ramas utilizadas para el trabajo de Ángel son:

- Backend: `feature/angel-investigacion-v3`.
- Frontend: `feature/angel-frontend-v3`.

Cada repositorio se confirma y publica **por separado**. Los cambios
llegan a `main` mediante Pull Request y revisión del integrador. El
**tag `v3`** se crea solamente después de completar las pruebas, aprobar
el checklist y validar la versión con el equipo.
