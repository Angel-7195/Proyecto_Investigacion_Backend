# Validación cruzada — `init(4).sql` y Spec Kit v3

**Actualización de decisión (9-11-2026):** el equipo decidió documentar la siembra de `admin` y `consulta` en `init.sql` y el primer administrador mediante script PHP solo CLI con contraseña desde el entorno. **Aún no se ha aplicado** al SQL compartido ni a la base real. En volumen persistente, el script de inicio debe asegurar los roles idempotentemente sin perder datos.

## Verificado en el archivo recibido

| Fuente SQL | Hecho | Implicación para v3 |
|---|---|---|
| `CREATE TABLE rol` | `id INT AUTO_INCREMENT`, `nombre VARCHAR(100) NOT NULL UNIQUE` | ID generado; nombre único en altas; no enviar ID en POST |
| `CREATE TABLE usuario` | `id INT AUTO_INCREMENT`, `username VARCHAR(100) NOT NULL UNIQUE`, `email VARCHAR(150) NOT NULL UNIQUE`, `password VARCHAR(255) NOT NULL` | Validar unicidad y longitud; solo hash en BD |
| `usuario` y `rol` | `activo TINYINT(1) DEFAULT 1` (nullable) | Aplicar `activo = 1` para autorización; evitar NULL como estado activo |
| `rol_usuario` | PK `(usuario_id, rol_id)` | No inventar ID; rutas con dos llaves; un vínculo duplicado da conflicto |
| `rol_usuario` | FK a usuario y rol con `ON DELETE CASCADE` | Cascada solo ante DELETE físico de padre; no al cambiar `activo` |
| `rol_usuario` | **Sin** `activo` ni otros campos | Política de retiro de vínculo pendiente C8; PUT/PATCH 405 es propuesta |
| Script completo | Sin INSERT en tablas de seguridad | Definir bootstrap primer admin; consultar estado de BD ya persistida |

## Pendiente de aprobación

1. C3/C4: nombres exactos de roles, combinación de roles y política para GET públicos.
2. C5: ejecutar y comprobar la estrategia ya documentada para crear admin inicial por script PHP solo CLI, sin clave en Git ni registro público.
3. C6/C7: caducidad de JWT, roles vigentes y efecto real del logout.
4. C8: retiro de vínculo de `rol_usuario` sin `activo` y política para proteger último admin.
5. C9/C10: campo de login, actualización de password, sesiones y CSRF.
6. C11: verificación de cierre v2, flujo HTTP y cURL.
7. Ejecutar `SHOW CREATE TABLE`/`COUNT(*)` en MariaDB para comprobar el volumen real.

## Nota de seguridad

**No ejecutar `docker compose down -v` para «actualizar» el esquema sin copia de seguridad**: puede destruir datos del volumen. El archivo SQL de inicialización no aplica cambios automáticamente a una base ya existente.

## Cruce final contra el ejemplo del profesor y el esquema real

El RAR `v3_segundo_motor` sirve como patrón de **documentación y pruebas**, no como SQL para Investigación. El archivo recibido `init(4).sql` aporta DDL verificable de `usuario`, `rol` y `rol_usuario`: no se copiaron tablas de `bdfacturas`, no se agregó PostgreSQL y no se inventó una columna `activo` para el puente. El cambio [C6] de semillas mencionado en `5_data_model.md` es **propuesto y todavía no aplicado**.

### Verificaciones aún necesarias en la instalación del equipo

```sql
SHOW CREATE TABLE usuario;
SHOW CREATE TABLE rol;
SHOW CREATE TABLE rol_usuario;
SELECT nombre, activo FROM rol WHERE nombre IN ('admin', 'consulta');
```

**No incluir** `SELECT password` en las capturas o evidencias. No confundir los resultados que arroje el SQL en el repositorio con el estado real de una instancia que conserva su volumen Docker.
