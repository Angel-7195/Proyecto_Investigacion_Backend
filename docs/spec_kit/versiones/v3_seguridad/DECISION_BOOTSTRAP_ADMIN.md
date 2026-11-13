# Registro de decisión — roles iniciales y primer administrador (v3)

**Fecha:** 9 de noviembre de 2026.  
**Estado:** acuerdo de diseño documentado con el estudiante; **sin implementación, sin ejecución de SQL y sin validación del profesor/equipo completo**.  
**Fuentes:** `init(4).sql`, `0_METODOLOGIA.md`, `modulo_investigacion.md`, Spec Kit v2 y los documentos del Spec Kit v3.

> **Estado a 10-11-2026:** acuerdo del equipo **implementado parcialmente y ejecutado** en el equipo del estudiante por comando CLI, sobre volumen existente. El resultado de alta y la asociación en MariaDB se confirmaron mediante salida de terminal y capturas; **idempotencia por repetición, base nueva y orquestación automática no se han probado**. Véase `REGISTRO_FASE1_ADMIN.md`. La política de contraseña inicial de esta implementación es mínimo **12 caracteres**, mediante variables efímeras en PowerShell.

## Necesidad

La v3 exige `POST /api/login`, control de roles y CRUD de usuarios/roles exclusivo para administradores. El `init.sql` recibido crea `rol`, `usuario` y `rol_usuario`, pero **no contiene cuentas ni roles iniciales**. No se puede entrar a administrar sin al menos una cuenta que tenga el rol `admin`.

## Decisión acordada

1. **Solo datos públicos en SQL:** añadir a `db/init.sql` dos registros `rol.nombre`: `admin` y `consulta`, con descripción. No fijar IDs ni incluir hash/contraseña. Anotar el cambio de semillas en la cabecera con un nuevo número.
2. **Primer administrador en PHP:** crear un comando local y exclusivo de consola (`api_investigacion/scripts/inicializar_admin.php`), no expuesto por HTTP. Leer `ADMIN_INICIAL_USERNAME`, `ADMIN_INICIAL_EMAIL`, `ADMIN_INICIAL_PASSWORD` de un entorno no versionado; validar y crear el hash con `password_hash(PASSWORD_DEFAULT)`. Crear usuario y vínculo `rol_usuario` de manera transaccional usando los IDs reales de la base.
3. **Repetición segura:** comprobar antes si ya existe un administrador válido; no crear otro ni sobrescribir su contraseña. Ante identificadores existentes incompatibles o cuentas administrativas inactivas, detenerse en lugar de asignar privilegios por sorpresa.
4. **Compatibilidad Docker:** para volumen nuevo, los roles se siembran desde SQL; para volumen existente (donde `init.sql` ya no se ejecuta), la inicialización debe asegurar los roles faltantes por nombre **sin destruir datos**. La implementación **actual** es por ejecución manual `docker compose run --rm --no-deps ...`; la eventual integración en el arranque normal del compose continúa pendiente de diseño/prueba.
5. **Sin puerta trasera:** no agregar registro público, no registrar credenciales en logs, no guardar el secreto inicial en Git y retirar los valores temporales del entorno después del alta. Un primer arranque sin secreto configurado debe fallar cerrado, nunca crear un usuario de contraseña conocida.

## Fuera de esta decisión

La duración JWT está acordada en **60 minutos**; se aprobaron `admin`/`consulta`, varios roles por usuario, retiro físico del vínculo `rol_usuario` y protección del último administrador. Continúan pendientes de desarrollo y pruebas las rutas y claims concretos, los casos de recuperación administrativa, la orquestación Docker y la protección del frontend. Estos puntos siguen en las clarificaciones de `2_spec.md` y **no se deben resolver improvisando en código**.

## Evidencia exigida antes de darlo por hecho

- Instalación limpia: existen `admin` y `consulta`, con claves autogeneradas y un administrador con hash válido y asociación activa; login correcto.
- Segundo arranque: no hay duplicados de roles, administradores ni asociaciones; la contraseña inicial no se reemplaza.
- Instalación con volumen v2: se conservan las 16 tablas/datos del módulo y se incorporan roles faltantes sin `docker compose down -v`.
- Configuración incompleta: ningún usuario admin se crea con secreto por defecto; no quedan secretos en Git, logs, SQL o respuestas HTTP.

**Los documentos que reciben esta decisión:** `2_spec.md`, `3_plan.md`, `4_research.md` (D-v3-09 y D-v3-16), `5_data_model.md`, `6_contracts.md`, `7_quickstart.md`, `8_tasks.md`, `9_checklist.md`, `GUIA_IA3.md` y `VALIDACION_SQL_V3.md`. El `init.sql` **original no fue modificado**.

## Restricciones y pruebas de aceptación de la decisión

| Escenario | Conducta exigida por el acuerdo |
|---|---|
| BD nueva, roles ausentes, credenciales iniciales presentes | Sembrar roles públicos por nombre, crear un solo usuario admin con hash y una asignación, sin exponer clave |
| BD existente, roles ya creados | No duplicar roles ni asumir `id = 1` o `id = 2` |
| Repetición del bootstrap con admin válido | No cambiar contraseña ni crear otra cuenta |
| Username/email coincide con otra cuenta sin rol | No promoverla automáticamente: detenerse y pedir decisión humana |
| Falta contraseña inicial en primer arranque | Fallar cerrado, sin contraseña de ejemplo |
| Volumen de MariaDB persistente | No borrar volumen ni exigir `docker compose down -v` |

**Separación del acuerdo:** la decisión y el **comando manual Docker** ya tuvieron una ejecución satisfactoria reportada; **todavía no** se consideran probadas la repetición, la instalación limpia, recuperación de cuentas ambiguas ni la automatización. El equipo acordó el tratamiento de `rol_usuario.DELETE`, pero no está implementado ni probado. Esos aspectos se documentan como C5/C8/C9 antes de implementar. La inicialización no tendrá endpoint HTTP público.
