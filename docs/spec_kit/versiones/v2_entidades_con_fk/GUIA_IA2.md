# Guía de IA — V2: Investigación

> La IA sigue los archivos del proyecto. NO debe completar el esquema por intuición ni declarar aprobada una compuerta.

## 1. Antes de pedir código

Leer en este orden:

1. `docs/spec_kit/1_constitution.md` **del equipo**, no dar por hecho que es la constitución del ejemplo.
2. `docs/spec_kit/versiones/0_mapa_versiones.md` actualizado al módulo.
3. `docs/spec_kit/versiones/v2_entidades_con_fk/2_spec.md` y clarificaciones resueltas.
4. `docs/spec_kit/versiones/v2_entidades_con_fk/5_data_model.md` **verificado con `db/init.sql` real**.
5. `docs/spec_kit/versiones/v2_entidades_con_fk/6_contracts.md` ya sin marcadores pendientes.
6. `0_METODOLOGIA.md`, `modulo_investigacion.md`, `2_spec.md` y `6_contracts.md` de V1.

## 2. Prompt sugerido para reorganización, una entidad a la vez

```text
Actúa como colaborador en nuestro proyecto académico PHP 8.3 puro,
MariaDB, PDO y tres capas con interfaces. NO uses Composer ni frameworks.
Primero lee la constitución y el spec kit. Quiero mover, SIN cambiar lógica,
los archivos de repositorio y servicio de [ENTIDAD] a subcarpetas con el
mismo nombre de la entidad. Muestra qué archivos se mueven con git mv,
qué require_once deben actualizarse y qué tests de regresión correr.
No renombres clases, interfaces, rutas HTTP, campos JSON ni métodos públicos.
No muevas otra entidad hasta comprobar que la anterior sigue funcionando.
Los comentarios y mensajes deben estar en español.
```

## 3. Prompt sugerido para una entidad V2

```text
Implementa solamente [ENTIDAD] de la V2; NO implementes otros recursos.
Respeta `1_constitution.md`, `2_spec.md`, `5_data_model.md` y `6_contracts.md`.
Toma literalmente columnas, tipos, PK y FK del DDL confirmado y no inventes.
Crea modelo, interfaz repositorio, repositorio MariaDB parametrizado,
interfaz servicio, servicio, controlador, registro en ensamblador y router,
funciones específicas HTTP del frontend y vistas HTML/CSS de ese recurso.
No permitas `activo` en POST/PUT/PATCH ni rompas los contratos V1.
Traduce conflictos conocidos a 409, validaciones a 422, PATCH vacío a 400;
preserva 204 para listas vacías y 404 para retirados.
Explica por qué cada archivo pertenece a su capa y añade pruebas.
```

## 4. Señales de alarma

- Se supone una PK `id` para tablas puente sin mirar SQL.
- Se inventa el nombre de la columna FK (`grupo_id`, etc.) sin comprobarlo.
- Se crea un `/api/{tabla}` genérico.
- Se cambia el borrado lógico por `DELETE FROM`.
- Se permite que el frontend use PDO o importe clases del backend.
- Se usa Bootstrap por CDN.
- Se incluyen JWT, login, roles o dashboard antes de V3/V4.
- Se dice «terminado» sin checklist, regresión y tag.
