# REGISTRO DE EVIDENCIA — Fase 7: catálogo fijo `rol`

**Proyecto:** Investigación — v3 Seguridad  
**Fecha:** 19 de noviembre de 2026  
**Fuente:** salidas de PowerShell y comprobación visual compartidas por el estudiante. No representa acceso autónomo al Docker ni una auditoría independiente del repositorio.

## 1. Alcance y decisión aplicada

El recurso `rol` deja de ofrecer operaciones CRUD y queda como **catálogo fijo de solo lectura**. Los únicos perfiles de acceso de esta versión son:

- `admin` — Administrador del sistema.
- `consulta` — Usuario con permisos de solo lectura.

MariaDB genera los identificadores; los valores `1` y `2` observados en la instancia local no son IDs portables. La API permite únicamente `GET /api/rol` y `GET /api/rol/{id}` para administradores. `POST`, `PUT`, `PATCH` y `DELETE` no están permitidos y deben responder `405 Method Not Allowed`, con `Allow: GET`.

La interfaz web muestra el catálogo sin botones ni formularios para crear, editar o retirar roles. La gestión de asignaciones sigue perteneciendo a `rol_usuario`, cuyo contrato no se modifica en esta fase.

## 2. Archivos y capas actualizados

En el backend se limitaron las responsabilidades de rol a consulta:

- `index.php`: las rutas de colección y ficha aceptan únicamente GET.
- `controladores/ControladorRol.php`: conserva las operaciones de listar y obtener.
- `servicios/Servicios_Rol/IServicioRol.php` y `ServicioRol.php`: solo declaran/ejecutan `listar` y `obtener`.
- `repositorios/Repositorios_Rol/IRepositorioRol.php` y `RepositorioRolMariaDB.php`: solo declaran/ejecutan consultas de roles activos.

En el frontend:

- `rutas_rol.php`: conserva la ruta de consulta del catálogo y elimina las rutas de crear, editar y retirar.
- `lista.php`: presenta ID, nombre y descripción, sin acciones de escritura.
- La vista/formulario anterior queda sin uso y no se enlaza desde el catálogo.
- No se modificaron las rutas ni las pantallas de `rol_usuario`.

## 3. Pruebas aisladas

El estudiante ejecutó:

```text
docker compose exec api-investigacion php -l /app/pruebas/prueba_rol_aislada.php
No syntax errors detected in /app/pruebas/prueba_rol_aislada.php

docker compose exec api-investigacion php /app/pruebas/prueba_rol_aislada.php
6 escenarios aislados de rol aprobados.
```

La regresión aislada de `rol_usuario` se volvió a ejecutar sin modificar ese módulo:

```text
docker compose exec api-investigacion php /app/pruebas/prueba_rol_usuario_aislada.php
Pruebas aisladas de rol_usuario: 15 / 15.
```

## 4. Pruebas HTTP reales con token de administrador

El estudiante inició sesión como `ADMIN` y guardó el JWT en variables de PowerShell. El token no se incluyó en las salidas compartidas ni en este registro.

| Petición | Resultado observado |
|---|---|
| `GET /api/rol` | HTTP `200` |
| `GET /api/rol/1` | HTTP `200`; `id=1`, `nombre=admin`, descripción «Administrador del sistema» |
| `GET /api/rol/2` | HTTP `200`; `id=2`, `nombre=consulta`, descripción «Usuario con permisos de solo lectura» |
| `POST /api/rol` | HTTP `405`; cabecera `Allow: GET` |
| `PUT /api/rol/999999` | HTTP `405`; cabecera `Allow: GET` |
| `PATCH /api/rol/999999` | HTTP `405`; cabecera `Allow: GET` |
| `DELETE /api/rol/999999` | HTTP `405`; cabecera `Allow: GET`; se repitió expresamente con el encabezado Bearer |

Para POST se envió un cuerpo de prueba con `nombre` y `descripcion`. Para PUT/PATCH se envió un cuerpo de prueba a la ruta de ID `999999`. El router rechazó los métodos no permitidos y no llegó a ejecutar las operaciones del controlador. No se utilizó un ID de rol real para probar los métodos de escritura.

## 5. Comprobación visual del frontend

El estudiante comprobó la página del catálogo. Se observaron únicamente dos registros:

| ID local | Nombre | Descripción |
|---:|---|---|
| 1 | `admin` | Administrador del sistema |
| 2 | `consulta` | Usuario con permisos de solo lectura |

La tabla presenta las columnas ID, Nombre y Descripción. No aparecen botones para crear, editar o retirar registros. Los IDs reflejan la instancia local y no deben codificarse como constantes para otros entornos.

## 6. Límites de la evidencia

- No se ejecutó en esta fase `GET /api/rol` con un JWT de `consulta`. La matriz C4 establece que el recurso es administrativo y que `consulta` debe recibir `403`; esa prueba concreta permanece pendiente.
- No se repitió la matriz completa de permisos de los 16 recursos de v1/v2.
- No se modificó ni se volvió a probar funcionalmente el selector de roles de la pantalla `/roles-usuarios`; este registro no afirma que consuma dinámicamente el catálogo.
- Las pruebas aisladas y HTTP de esta fase no equivalen a la aceptación global ni al cierre de las compuertas SDD de v3.

## 7. Documentos relacionados

Esta evidencia actualiza la decisión C3 y el requisito RF-v3-07 de `2_spec.md`; el plan de capas en `3_plan.md`; la descripción de `rol` en `5_data_model.md`; los endpoints en `6_contracts.md`; y las tareas/checklist en `8_tasks.md` y `9_checklist.md`.

**Estado de fase:** catálogo fijo de roles implementado y comprobado localmente en los escenarios descritos. La v3 permanece abierta a regresión y revisión humana.
