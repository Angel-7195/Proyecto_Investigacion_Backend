# REGISTRO DE EVIDENCIA — Fase 5: gestión de `rol_usuario`

**Proyecto:** Investigación — v3 Seguridad  
**Responsable:** Ángel David Gutiérrez Ladino  
**Fecha:** 12 de noviembre de 2026  
**Fuente:** resultados PowerShell y capturas de phpMyAdmin proporcionados por el estudiante, no una ejecución autónoma sobre su computadora.

## 1. Alcance del submódulo

La tabla `rol_usuario` tiene clave primaria compuesta `(usuario_id, rol_id)` y dos claves foráneas; **no** tiene `id`, `activo` ni otros campos editables. Se respeta la decisión aprobada C8: `DELETE` físico únicamente del **vínculo**, sin desactivar/eliminar usuario ni rol. El contrato indica `405` para PUT/PATCH después de autenticación y autorización.

Se integraron las rutas `GET /api/rol_usuario`, `GET /api/rol_usuario/{usuario_id}/{rol_id}`, `POST /api/rol_usuario`, `DELETE /api/rol_usuario/{usuario_id}/{rol_id}` en el router y ensamblador existentes. El código sigue capas (modelo, interfaz/repositorio PDO, servicio, controlador, excepción) y el módulo **`rol` queda asignado a Miguel**, sin atribuirle un resultado no comprobado.

## 2. Construcción, sintaxis y suite aislada

Comandos ejecutados por el estudiante:

```powershell
docker compose up -d --build
docker compose exec api-investigacion php -l /app/index.php
docker compose exec api-investigacion php -l /app/servicios/ensamblador.php
docker compose exec api-investigacion php /app/pruebas/prueba_rol_usuario_aislada.php
curl.exe -i http://localhost:8111/api/rol_usuario
```

Resultados: contenedores iniciados y MariaDB `Healthy`; dos `php -l` sin errores; **15/15** pruebas aisladas satisfactorias (validación de IDs, GET/POST/DELETE, listado, inexistente, segundo DELETE, asociaciones distintas); `GET /api/rol_usuario` **sin JWT** respondió **401** con esquema Bearer.

## 3. Evidencias HTTP reales con MariaDB

Se usó JWT de administrador para crear/modificar asociaciones y JWT de usuario de consulta para verificar permisos. **Los IDs corresponden solamente al volumen local**; no deben copiarse como constantes de producción.

| Prueba | Resultado comunicado |
|---|---|
| Roles en SQL: `admin` y `consulta` | IDs 1 y 2, ambos activos |
| Crear nuevo usuario `consulta_v3_15ce9ef7` | POST `/api/usuario` → **201**, usuario ID 4 |
| Asignar `consulta` al usuario 4 | POST `/api/rol_usuario` `{ "usuario_id":4, "rol_id":2 }` → **201** |
| Consultar vínculo `(4,2)` | GET → **200** |
| Intentar asignación duplicada `(4,2)` | POST → **409** |
| Verificar vínculo tras duplicado | GET → **200**, vínculo original conservado |
| Login con cuenta `consulta` | **200**; rol reportado `consulta` |
| GET recurso `area_conocimiento` con ese JWT | **200** |
| POST `area_conocimiento` con ese JWT y cuerpo `{}` | **403** (rechazo de autorización) |
| GET `/api/usuario` con ese JWT | **403** |
| GET de recurso V1 **antes** de retirar el rol | **200** |
| DELETE `/api/rol_usuario/4/2` con admin | **200** |
| GET de recurso V1 **con el mismo JWT anterior** después del retiro | **403** inmediato |
| GET de vínculo retirado `(4,2)` | **404** |

**Conclusión acotada:** la comprobación de permisos se realiza con el estado vigente de roles en MariaDB; el token de consulta previamente emitido ya no permite consultar una vez retirado el vínculo. No se declara por ello comprobada toda la matriz de 16 recursos.

## 4. Integridad por SQL — solo lectura

El estudiante ejecutó:

```sql
SELECT u.id, u.username, u.activo, r.nombre AS rol
FROM usuario u
LEFT JOIN rol_usuario ru ON u.id = ru.usuario_id
LEFT JOIN rol r ON ru.rol_id = r.id
WHERE u.id IN (1, 4)
ORDER BY u.id;
```

| id | username | activo | rol |
|---:|---|---:|---|
| 1 | `ADMIN` | 1 | `admin` |
| 4 | `consulta_v3_15ce9ef7` | 1 | `NULL` |

Se confirmó que **el usuario 4 sigue activo pero sin rol**, y que `ADMIN` conserva su asignación `admin`. El GET de vínculo retirado devolvió 404; no se aportó un SELECT directo independiente de la tabla puente tras el retiro.

## 5. Pendientes, no aprobados

- [ ] Probar la protección de **último administrador** y cualquier retiro concurrente en **base de datos descartable**. No ejecutar DELETE sobre el único `ADMIN` del volumen habitual.
- [ ] Validar por HTTP real las claves foráneas inexistentes/inactivas, segundo DELETE y PUT/PATCH `405`; las pruebas aisladas no equivalen a todas estas pruebas de integración.
- [ ] Probar todas las rutas protegidas v1/v2 y la integración con el CRUD `rol` de Miguel.
- [ ] Verificar si el usuario sin rol ya no puede iniciar una **nueva** sesión (queda pendiente esta prueba específica).
- [ ] Restaurar el vínculo `(4,2)` mediante admin cuando se necesite el usuario `consulta` para probar el frontend.
- [ ] Pruebas frontend, sesión/logout, instalación limpia, bootstrap idempotente, secretos, regresión, compuertas SDD y PR/tag.

**Estado:** operaciones principales de `rol_usuario` verificadas por HTTP contra MariaDB; fase 5 funcional, pero **no cerrada en seguridad y regresión**. La prueba del último administrador se aplaza expresamente al final de la v3 y solo en un entorno descartable.
