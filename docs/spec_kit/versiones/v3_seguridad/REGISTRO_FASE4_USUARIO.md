# Registro de ejecución — Fase 4: CRUD `usuario` (v3 Investigación)

**Fecha de la evidencia:** 12 de noviembre de 2026.  
**Responsable:** Ángel David Gutiérrez Ladino.  
**Entorno:** backend PHP 8.3 en Docker, MariaDB local persistente, PowerShell en Windows, API `http://localhost:8111`.  
**Estado:** operaciones principales **probadas** en instancia local; seguridad e integración **no completamente cerradas**.

> Este registro se basa en los resultados de terminal y capturas de phpMyAdmin compartidos por el estudiante. No es una ejecución independiente sobre su repositorio ni acredita aprobación docente o de las compuertas SDD. No incluye contraseñas, JWT, secreto de firma ni hashes completos.

## 1. Objetivo y arquitectura

Verificar la administración de usuarios solo para la cuenta con rol `admin`, conservando los 16 CRUD de V1 y V2 y la autorización JWT previamente implementada. Las piezas preparadas para esta fase son `Usuario.php`, `IRepositorioUsuario.php`, `RepositorioUsuarioMariaDB.php`, `IServicioUsuario.php`, `ServicioUsuario.php`, `ControladorUsuario.php`, `ExcepcionUsuario.php` y `prueba_usuario_aislada.php`. Se añadieron rutas explícitas y ensamblaje sin sustituir las capas antiguas.

## 2. Pruebas automatizadas y de ejecución

| Comprobación | Resultado aportado | Interpretación |
|---|---|---|
| `docker compose up -d --build` | Imágenes construidas; MariaDB healthy y API iniciada | Entorno local operativo |
| `php -l /app/index.php` y `php -l /app/servicios/ensamblador.php` | Sin errores de sintaxis | Solo sintaxis de los dos archivos |
| `php /app/pruebas/prueba_usuario_aislada.php` | **28/28** | Pruebas con repositorio falso, no contra MariaDB |
| `GET /api/usuario` con JWT de ADMIN | **200**, un activo inicialmente, sin campo `password` | Lectura HTTP real autorizada |
| `POST /api/usuario` con JWT de ADMIN | **201**, ID `2` en esa instancia | Alta real de usuario temporal |
| `GET /api/usuario/2` | **200** | Ficha recuperada |
| `PATCH /api/usuario/2` con `nombre_completo` | **200** + GET posterior actualizado | Cambios parciales persistidos |
| `PUT /api/usuario/2` con perfil | **200** + GET posterior actualizado | Reemplazo de perfil persistido |
| `PATCH /api/usuario/2` con `username=ADMIN` | **409**; username original conservado | Restricción de duplicidad observada |
| `POST /api/usuario` con correo ya usado por ADMIN | **409** | Unicidad de correo observada |
| `PATCH /api/usuario/2/password` | **200**, mensaje `Contraseña actualizada.` | Ruta de cambio de hash aceptada; verificación directa posterior pendiente |
| `DELETE /api/usuario/2` | **200** | Retiro lógico solicitado sobre cuenta de prueba |
| `GET /api/usuario/2` posterior, con JWT | **404** | No se muestra el usuario desactivado |
| Consulta SQL `usuario` para ID 2 | `activo = 0`; fila todavía presente | Borrado lógico comprobado en MariaDB |
| `GET /api/usuario/2` sin JWT | **401**, encabezado `WWW-Authenticate: Bearer` | Middleware exige autenticación |
| Consulta SQL `LEFT(password,4), LENGTH(password)` | En usuarios ID 1 y 2: `$2y$`, 60 caracteres | Hash con formato bcrypt observado; no revela el hash |
| SQL `COUNT(DISTINCT u.id)` sobre roles admin activos | **1** | Solo uno activo; **no verifica** el rechazo de desactivarlo |

**Datos de prueba de la BD del estudiante:** la cuenta temporal fue `prueba_v3_3ee170b3`, ID `2`. Este identificador **no debe fijarse en scripts generales**, pues dependerá de cada base nueva.

## 3. Seguridad observada

- Las solicitudes al CRUD que se probaron con éxito utilizaron JWT válido de `ADMIN`; el GET sin JWT dio `401`.
- El POST exitoso no devolvió un campo `password`; las 28 pruebas aisladas verificaron además que la serialización no expone hash y que la contraseña se hashea.
- Duplicar username o correo devolvió `409` sin modificar la identidad de prueba.
- La consulta SQL mostró hashes bcrypt por su prefijo `$2y$` y longitud 60, **no** confirma cuál era la clave ni equivale a una verificación directa de `password_verify()` para la contraseña nueva. El login HTTP de `ADMIN` ya había funcionado previamente.
- La protección del último administrador está prevista mediante transacción y bloqueo del repositorio; su simulación aislada pasó, pero **no** se intentó retirar al único administrador real.

## 4. Pruebas pendientes antes del cierre

1. En **base descartable**, intentar desactivar al único administrador y comprobar el `409` y la conservación de la cuenta/rol; añadir caso concurrente cuando se implemente `rol_usuario`. No tocar al administrador principal.
2. Crear una cuenta temporal con rol `consulta` y demostrar por HTTP **403** en rutas administrativas, además de lecturas permitidas y escrituras prohibidas en los 16 recursos.
3. Verificar de forma directa un cambio de contraseña con `password_verify()` o login posterior de cuenta temporal **con rol activo**, sin publicar la nueva clave.
4. Completar la batería HTTP de `422` de entrada, `404` y escenarios de base caida; revisar todas las respuestas para impedir exposición de hashes/secrets.
5. Implementar e integrar `rol_usuario` (Ángel) y `rol` (Miguel) respetando la excepción de DELETE físico para vínculos; probar consistencia con último administrador.
6. Ejecutar regresión integral de V1/V2, frontend con sesiones y cierre de Spec Kit por equipo.

## 5. Documentos y trazabilidad

- RF-v3-06: CRUD de usuarios y borrado lógico — `2_spec.md` y `6_contracts.md` §3.
- RNF-v3-03: hash seguro, sin secretos en respuestas — `5_data_model.md` y `9_checklist.md`.
- Pruebas reproducibles — `7_quickstart.md` §4.1.
- Tareas y pendientes — `8_tasks.md` Fase 5 y `9_checklist.md` D.1.
- Fase siguiente — `rol_usuario`, sin modificar el CRUD `rol` asignado a Miguel Angel Lotero Alvarez.
