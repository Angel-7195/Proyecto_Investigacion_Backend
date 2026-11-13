# Asignación de responsabilidades — v3 Investigación (propuesta)

**Proyecto:** Módulo de Investigación — PHP 8.3+ y MariaDB  
**Versión:** v3 — Autenticación JWT, sesiones y control de acceso por roles  
**Fecha:** 10 de noviembre de 2026  
**Equipo:** Ángel David Gutiérrez Ladino (integrador) y Miguel Angel Lotero Alvarez  
**Estado:** propuesta de reparto para ejecutar y reflejar en `8_tasks.md`. Las decisiones funcionales fueron aprobadas por el equipo; las verificaciones técnicas y pruebas siguen pendientes.

## Distribución de responsabilidades

Implementar la seguridad de la v3 sin romper los seis CRUD de v1 ni los diez de v2, manteniendo **API y frontend en repositorios independientes**. Se asigna a **Ángel David Gutiérrez Ladino** el trabajo más complejo y las integraciones de mayor riesgo; a **Miguel Angel Lotero Alvarez** se le asigna **un módulo concreto y relativamente independiente**, con entregables pequeños y verificables, teniendo en cuenta su menor disponibilidad de tiempo.

| Responsable | Carga orientativa | Enfoque |
|---|---:|---|
| **Ángel David Gutiérrez Ladino** | **75–80 %** | Seguridad JWT, login, autorización central, usuarios, asignaciones, Docker, frontend compartido, integración y regresión |
| **Miguel Angel Lotero Alvarez** | **20–25 %** | CRUD específico de `rol` (API y sus pantallas) y pruebas de ese módulo |

> Los porcentajes son una estimación para organizar el esfuerzo, **no una nota ni un conteo exacto de horas**. La responsabilidad por cada entrega debe evidenciarse en commits y Pull Requests propios.

### Distribución de funcionalidades

| Responsable | Funcionalidad | Dificultad estimada | Motivo principal |
|---|---|---|---|
| Ángel David Gutiérrez Ladino | Inicio de sesión y JWT | Alta | Firma HS256, expiración de 60 minutos y manejo seguro de credenciales. |
| Ángel David Gutiérrez Ladino | Autorización de los 16 recursos | Alta | Verificación central de permisos sin alterar los contratos de v1 y v2. |
| Ángel David Gutiérrez Ladino | CRUD de `usuario` | Alta | Hash de contraseñas, unicidad, validaciones y borrado lógico. |
| Ángel David Gutiérrez Ladino | Gestión de `rol_usuario` | Alta | Clave compuesta, relación con dos tablas y protección del último administrador. |
| Ángel David Gutiérrez Ladino | Administrador inicial y Docker | Media-alta | Inicialización segura para bases nuevas y existentes. |
| Ángel David Gutiérrez Ladino | Sesiones, menú y frontend compartido | Alta | Integración del token en todas las llamadas y pantallas. |
| Ángel David Gutiérrez Ladino | Integración y regresión | Alta | Rutas compartidas, Pull Requests y pruebas de los 16 CRUD anteriores. |
| Miguel Angel Lotero Alvarez | CRUD de `rol` (API y frontend) | Media-baja | Recurso delimitado, con pocos campos y sin claves foráneas. |
| Miguel Angel Lotero Alvarez | Pruebas del módulo de roles | Media-baja | Pruebas aisladas, validación de nombres duplicados y retiro lógico. |

**Nota:** La dificultad y los porcentajes son estimaciones para repartir responsabilidades, no una medición exacta del esfuerzo.

## Reglas técnicas comunes

- Respetar `docs/spec_kit/versiones/v3_seguridad/` y las decisiones aprobadas en la v3. **Antes de codificar, registrar formalmente la duración del JWT (60 minutos), la matriz de permisos y las decisiones recién ratificadas en `2_spec.md`, `4_research.md`, `6_contracts.md`, `8_tasks.md` y demás documentos afectados.**
- `admin`: lectura y escritura autorizadas; gestión exclusiva de `usuario`, `rol` y `rol_usuario`. `consulta`: solo lecturas permitidas. **La API, no solo el menú, debe hacer cumplir los permisos.**
- JWT firmado con HS256, expiración de **60 minutos**, clave `JWT_SECRET` fuera de Git, sin renovación automática en v3. `401` sin autenticación válida y `403` sin autorización.
- Frontend: sesiones PHP y llamadas HTTP a la API; nunca PDO ni credenciales MariaDB. El logout destruye el estado de sesión del frontend, pero **no revoca por sí solo un JWT previamente emitido**.
- `usuario` y `rol` se retiran lógicamente (`activo = FALSE`). La tabla `rol_usuario` **no** tiene columna `activo`; aplicar la política de retiro de asociaciones aprobada y preservar al menos un administrador activo.
- Mantener nombres de tablas/columnas del `db/init.sql`; en `rol`, `id` se genera con `AUTO_INCREMENT`, `nombre` es único y `descripcion` es opcional. No agregar tablas ni columnas por comodidad.
- Usar PHP puro, PDO con consultas preparadas, tres capas, interfaces, `declare(strict_types=1);`, código y mensajes en español. **Sin Composer ni frameworks** salvo autorización documentada.
- Se conserva la semántica v1/v2: los cinco puentes simples v2 siguen sin PUT/PATCH (`405` para `admin`); sus claves compuestas y la diferencia documentada de PATCH vacío no se cambian silenciosamente.

## Responsabilidades de Ángel David Gutiérrez Ladino — mayor complejidad

| ID | Entregable y actividades | Evidencia mínima |
|---|---|---|
| **A01** | **Cerrar especificaciones:** trasladar las decisiones aprobadas a los contratos; revisar 60 minutos de JWT, permisos, login, logout, cambio de contraseña, retiro de roles y protección del último admin. Actualizar trazabilidad de tareas y checklist. | Docs coherentes; ninguna regla contradictoria entre spec, contratos y plan |
| **A02** | **Bootstrap seguro:** semillas `admin`/`consulta` en `db/init.sql` (sin contraseñas); script PHP **solo CLI** para crear al primer administrador con `password_hash()` y asignar el ID real del rol; integración con Docker. | Nueva instalación y volumen existente; ejecución repetida sin duplicar ni sobrescribir; no se usa `down -v` |
| **A03** | **Login y JWT:** autenticación con `password_verify()`, emisión HS256 y verificación de firma, algoritmo, formato, claims y vencimiento de 60 minutos. | Login correcto; contraseña incorrecta, token falsificado y token expirado rechazados |
| **A04** | **Autorización central:** verificar Bearer, usuario activo y roles vigentes; comprobar permiso por ruta + método antes de invocar cualquier controlador. Cubrir los 16 recursos existentes y los 3 administrativos. | `401` sin token, `403` para escritura de `consulta`; una petición denegada no modifica la base |
| **A05** | **CRUD de `usuario`:** modelo, interfaces, repositorio, servicio, controlador; creación y cambio de contraseña con hash, unicidad de `username`/`email`, borrado lógico, jamás mostrar hash en JSON. | Rutas y respuestas de `usuario` probadas con admin; duplicados y validaciones; hashes ausentes de respuestas |
| **A06** | **Gestión de `rol_usuario`:** CRUD/operaciones acordadas para clave compuesta, validación de ambas FK, retiro de asignaciones, permisos vigentes y protección del último administrador activo. | Crear, consultar y retirar relaciones sin dejar acceso indebido ni eliminar usuario/rol |
| **A07** | **Archivos centrales del backend:** `api_investigacion/index.php`, `servicios/ensamblador.php`, configuración de entorno y utilidades comunes de seguridad; integrar los controladores entregados por Miguel. | Rutas reconocidas, dependencias ensambladas y sin conflictos entre módulos |
| **A08** | **Frontend compartido:** inicio de sesión, `$_SESSION`, regeneración del ID de sesión, Bearer en cliente HTTP, logout, menú por roles, mensajes `401`/`403`, pantallas de usuario y asignaciones; integrar las pantallas de rol entregadas por Miguel. | Login/consulta/admin/salida demostrables; API apagada → frontend en pie, sin datos; sin PDO |
| **A09** | **Pruebas integrales y cierre:** JWT, permisos, 16 recursos v1/v2, CRUD administrativos, secretos, Docker, regresión y `7_quickstart.md` con comandos realmente ejecutados. Responsable de integrar PR y gestionar el tag cuando todo esté aprobado. | Evidencias de regresión + checklist revisado; merge por PR, tag `v3` al cierre |

**Archivos compartidos que coordina y edita Ángel David Gutiérrez Ladino:** `api_investigacion/index.php`, `api_investigacion/servicios/ensamblador.php`, `db/init.sql`, archivos de Docker/configuración, `front_php/index.php`, `front_php/cliente_api.php`, plantilla/menú principal y documentos globales del Spec Kit. Esto reduce conflictos al integrar.

## Responsabilidades de Miguel Angel Lotero Alvarez — módulo delimitado

**Encargo principal:** realizar el **CRUD de roles** (`rol`) con la misma estructura de capas utilizada en v2. Es un recurso autocontenido, con menos campos y sin JWT ni relaciones complejas que programar en su parte.

| ID | Entregable y actividades | Evidencia mínima |
|---|---|---|
| **B01** | **Modelo e interfaces de `rol`:** campos `id`, `nombre`, `descripcion`; reflejar `activo` como estado interno de persistencia y no como dato editable. | Clases e interfaces con las operaciones previstas en `6_contracts.md` |
| **B02** | **Repositorio MariaDB de `rol`:** listar/obtener activos, crear, PUT, PATCH, retiro lógico; consultas preparadas; distinguir duplicado de `nombre` (`409` mediante la capa HTTP). | Lecturas filtran inactivos, segundo retiro no es exitoso, nombre duplicado controlado |
| **B03** | **Servicio y controlador de `rol`:** validar entradas, respetar límites (`nombre` hasta 100 caracteres, `descripcion` opcional), manejo de excepciones, respuestas acordadas. El controlador **no implementa su propio middleware JWT**. | GET/POST/PUT/PATCH/DELETE conforme al contrato, sin SQL en servicio/controlador |
| **B04** | **Pantallas específicas de roles:** listado, formulario de crear/editar y acción de retirar; archivos y funciones **propios del recurso** acordados con Ángel. No modificar el menú, el router general ni el cliente HTTP compartido. | Vistas reutilizables que Ángel pueda conectar al flujo de sesiones/permiso admin |
| **B05** | **Pruebas aisladas y entrega:** pruebas del servicio con repositorio falso; pruebas HTTP del CRUD de rol **una vez integrado** con el guard central; registrar comandos, resultados y abrir PR. | Prueba positiva, duplicados, retiros, inactivos; `401`/`403` probados en integración |

**Archivos orientativos a cargo de Miguel Angel Lotero Alvarez** (ajustar exactamente a la estructura actual del repositorio):

```text
Backend — api_investigacion/
  modelos/Rol.php
  repositorios/Repositorios_Rol/IRepositorioRol.php
  repositorios/Repositorios_Rol/RepositorioRolMariaDB.php
  servicios/Servicios_Rol/IServicioRol.php
  servicios/Servicios_Rol/ServicioRol.php
  controladores/ControladorRol.php
  pruebas/prueba_rol_aislada.php

Frontend — front_php/
  vistas/rol/                 # vistas específicas del rol
  rutas/rutas_rol.php        # solo si coincide con el patrón real de v2
```

> Miguel **no modifica** `index.php`, `ensamblador.php`, `cliente_api.php`, `docker-compose.yml`, `db/init.sql` ni los archivos de seguridad. En lugar de tocar esos archivos, entrega las instrucciones de registro/integración para que Ángel las aplique. Si un archivo propuesto ya existe con otro nombre, se conserva la convención real de v2.

## Dependencias y orden sugerido

| Etapa | Ángel David Gutiérrez Ladino | Miguel Angel Lotero Alvarez | Condición de salida |
|---|---|---|---|
| **0. Acordar contratos** | Actualiza la spec/contratos y comunica firma del modelo `rol` y cuerpos JSON | Revisa específicamente el contrato de `rol` y responde dudas | Contrato común definido antes de implementar |
| **1. Trabajo independiente** | Bootstrap, login/JWT y guard central | Modelo, repositorio, servicio y controlador de `rol` con prueba aislada | Ambos bloques ejecutan sus pruebas propias |
| **2. Integración backend** | Implementa `usuario`/`rol_usuario` y registra `rol` en enrutador/ensamblador | Entrega PR del módulo y corrige observaciones | Admin puede operar `rol`; `consulta` no puede escribir |
| **3. Frontend** | Sesión, cliente Bearer, menú y demás pantallas | Vistas y formularios de `rol`, sin editar archivos compartidos | Rol visible únicamente para admin, integrado por Ángel |
| **4. Aceptación** | Regresión completa, Docker, quickstart, revisión de seguridad y merge | Casos de prueba de rol, revisión cruzada del checklist | Evidencias y aprobación antes del tag `v3` |

**Dependencia importante:** Miguel **sí puede comenzar** los modelos, capas y pruebas de `rol` sin esperar a que JWT funcione. La validación HTTP de `401`/`403` y las vistas funcionando con sesión solo pueden comprobarse después de la integración central de Ángel.

## Contrato de integración entre ambos integrantes

1. La API ofrecerá rutas específicas de roles: `GET /api/rol`, `GET /api/rol/{id}`, `POST /api/rol`, `PUT /api/rol/{id}`, `PATCH /api/rol/{id}` y `DELETE /api/rol/{id}`, según `6_contracts.md` ratificado.
2. Todos estos endpoints serán **solo para `admin`**. Ángel aplicará el control de acceso **antes** de instanciar/ejecutar el controlador; Miguel no duplicará lógica JWT dentro del CRUD.
3. Los códigos de éxito y error seguirán el contrato v3 aprobado. Si hay una ambigüedad (por ejemplo, PATCH `{}` o impedir el retiro del rol `admin`), **se resuelve en la especificación antes de hacer una implementación distinta**.
4. La protección contra dejar al sistema sin un administrador será responsabilidad transversal de **Ángel David Gutiérrez Ladino**, con pruebas conjuntas. Miguel no debe asumir que un `DELETE` lógico de `rol` está permitido en todos los casos.
5. Los dos repositorios deben conservar separación API/front; las vistas de Miguel reciben datos por el cliente HTTP del frontend, no por SQL ni importando clases del backend.

## Flujo de Git

- **Backend:** Ángel ya trabaja en `feature/angel-investigacion-v3`; debe comprobar su estado y mantenerla actualizada. Miguel crea su propia rama desde `main` actualizado, por ejemplo `feature/miguel-roles-v3`.
- **Frontend:** cada integrante crea su rama equivalente en el repositorio del frontend. Una rama creada en el backend no existe automáticamente en el frontend.
- Nadie trabaja directamente en `main`; commits pequeños con mensajes descriptivos y autor identificable. No copiar archivos editados del otro integrante encima de `main`.
- Miguel abre un PR para el módulo `rol` (y otro para sus pantallas en el frontend). El integrador revisa pruebas, integra el módulo y registra las rutas en los archivos centrales.
- Solo el encargado autorizado de `main` efectúa el merge. El tag `v3` se crea **después** de la revisión manual de `9_checklist.md` y de las pruebas de regresión v1-v2.
- El respaldo Git `stash@{0}` que se conservó previamente no se restaura automáticamente sobre la nueva rama; revisarlo únicamente si hace falta recuperar algún trabajo no integrado.

## Checklist individual de entrega

### Ángel David Gutiérrez Ladino

- [ ] Los acuerdos de seguridad quedaron actualizados en el Spec Kit (60 minutos, permisos, logout, roles y bootstrap).
- [ ] Inicialización segura e idempotente probada con base nueva y existente.
- [ ] Login y JWT válidos/inválidos/expirados probados.
- [ ] Guard central probado: `401` sin token y `403` sin permiso.
- [ ] CRUD de `usuario` y `rol_usuario` probados, incluido el último administrador.
- [ ] Frontend de login, sesión, menú, logout y pantallas integradas.
- [ ] El módulo `rol` quedó integrado sin perder sus pruebas ni contratos.
- [ ] Regresión de los 16 recursos y fallo de API comprobados.
- [ ] Secretos ausentes de Git; checklist, quickstart y PR revisados.

### Miguel Angel Lotero Alvarez

- [ ] Clases y archivos de `rol` entregados respetando tres capas e interfaces.
- [ ] La API de `rol` filtra registros inactivos y maneja duplicados y validaciones.
- [ ] Pruebas aisladas añadidas con repositorio falso y resultados documentados.
- [ ] Pantallas de roles entregadas sin modificar archivos compartidos ni conectarse a MariaDB.
- [ ] Resultados HTTP de `rol` comprobados después de integrar JWT y permisos.
- [ ] Cambios documentados y Pull Request abierto con commits propios.

## Definición de finalizado

**El reparto queda cumplido cuando** ambos integrantes entregan su parte mediante PR, el sistema permite login JWT de 60 minutos, los permisos están protegidos en la API, el administrador puede gestionar usuarios, roles y asociaciones desde el frontend, el usuario `consulta` no puede escribir, y continúan pasando las comprobaciones de v1/v2. La documentación final debe corresponder al código y las pruebas ejecutadas.

**Nota:** esta asignación distribuye responsabilidades. **No sustituye** `2_spec.md`, `6_contracts.md`, `8_tasks.md` ni `9_checklist.md`, y no declara ninguna funcionalidad implementada ni ninguna prueba aprobada.
