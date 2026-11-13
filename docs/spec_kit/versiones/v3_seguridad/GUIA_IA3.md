# Guía para trabajar con IA — v3: Seguridad del módulo de Investigación

> La IA apoya especificaciones, pruebas y revisión; **no decide vacíos** ni aprueba la compuerta de requisitos. Usar una instrucción por fase o archivo y comprobar el resultado antes de seguir.

<!-- Registro de decisión del 9-11-2026 (v3, documentación; no implementación). -->
**Inicialización de cuentas:** al implementar, agregar únicamente los roles públicos `admin` y `consulta` como semillas documentadas de `db/init.sql`. La primera cuenta administradora y su `rol_usuario` deben originarse de un script PHP **solo CLI** (`api_investigacion/scripts/inicializar_admin.php`, ruta propuesta), con `password_hash(PASSWORD_DEFAULT)` y variables `ADMIN_INICIAL_USERNAME`, `ADMIN_INICIAL_EMAIL`, `ADMIN_INICIAL_PASSWORD` no versionadas. No inventar IDs, no crear `POST /api/register` público, no incluir contraseñas en SQL, no ejecutar `down -v` sobre datos existentes y no afirmar que este procedimiento ya fue probado. Exigir idempotencia y proteger contra elevación accidental de privilegios.

## 1. Documentos que deben leerse primero

**Actualización por comparación con V2:** usar también el paquete `v2_entidades_con_fk` entregado por el equipo, especialmente `2_spec.md`, `3_plan.md`, `5_data_model.md`, `6_contracts.md` y `9_checklist.md`. Si existen discrepancias entre documentación V2 de 29-10 y el código actual en `main`, no inventar el resultado: registrar evidencia y acordar decisión.


1. `docs/spec_kit/1_constitution.md` **vigente del equipo**.
2. `docs/spec_kit/versiones/v3_seguridad/2_spec.md` (clarificaciones resueltas).
3. `docs/spec_kit/versiones/v3_seguridad/5_data_model.md` y `6_contracts.md`.
4. `docs/spec_kit/versiones/v2_*/6_contracts.md` y el código de v2 realmente integrado.
5. `ProyectosDeAula/docs/0_METODOLOGIA.md` (§6.4 y §7) y documento `modulo_investigacion.md`.

Si faltan archivos, la IA debe solicitar evidencia o marcar `[NECESITA ACLARACIÓN]`; no suponer la estructura de la BD ni los permisos.

## 2. Prompt de trabajo (para usar después de aprobar la spec)

```text
Vas a implementar la versión 3 de nuestro módulo de Investigación en PHP,
partiendo de una v2 cerrada. Antes de modificar código, lee la constitución
vigente, los documentos de spec_kit/versiones/v3_seguridad, el SQL real y
los contratos de v1 y v2. Cualquier diferencia se reporta y se resuelve
EN la documentación, no improvisando en PHP.

Alcance de v3: POST /api/login con JWT HS256, sesiones PHP en el frontend,
Authorization Bearer entre frontend y API, autorización central por rol antes
del controlador, menú por permisos, logout y gestión de usuario/rol/rol_usuario
solo admin. El rol consulta nunca puede escribir. Deben seguir funcionando
los 16 recursos anteriores con sus métodos individuales, respuestas, integridad y borrado lógico.

Reglas obligatorias:
- PHP 8.3+ puro, sin Composer ni framework no aprobado.
- declare(strict_types=1); nombres, errores y comentarios en español.
- controlador/servicio/repositorio separados por interfaces; SQL solo con PDO
  parametrizado y únicamente en repositorios.
- No construir una ruta genérica /api/{tabla} ni compartir código PHP de la
  API con el frontend.
- La API protege endpoints con 401 para falta de autenticación y 403 para
  token válido sin permiso, incluso cuando se invoca con Postman.
- Usar password_hash/password_verify; jamás guardar contraseñas en claro ni
  exponer hashes en JSON, logs o pantallas.
- JWT_SECRET se obtiene del entorno y NO queda en Git. Validar firma con
  hash_equals, expiración y algoritmo permitido.
- El frontend usa session_start y un cliente HTTP con Bearer; la V2 usa streams,
  y la exigencia de cURL debe resolverse (C11-V2). NO utiliza PDO ni
  credenciales de base.
- No implementar dashboard, consultas multitabla ni publicación: eso es v4.
- El DDL de `usuario`, `rol`, `rol_usuario` ya está especificado en `5_data_model.md`; nada de supuestos sobre el estado real de la BD, nombres de roles, datos iniciales,
  revocación o ruta de logout: detenerse y citar la clarificación pendiente.

Orden: 1) revisar contratos, 2) interfaces y modelos, 3) repositorios,
4) servicios, 5) seguridad/JWT y enrutador, 6) controladores,
7) frontend, 8) pruebas positivas/negativas y regresión completa.

Por cada archivo que generes explica en 2-4 frases qué responsabilidad tiene,
qué decisión de spec implementa y qué prueba demuestra que funciona.
No marques checklist aprobado ni afirmes pruebas ejecutadas sin evidencia.
```

## 3. Errores típicos que debe detectar el estudiante

| Error propuesto por IA | Cómo detectarlo |
|---|---|
| Contraseña con MD5/SHA256 o guardada en claro | No se usó `password_hash()` y `password_verify()` |
| `JWT_SECRET` como string dentro de una clase | Buscar valor incrustado; debe venir de `getenv()` |
| Confiar en `alg` del JWT o aceptar `none` | Test de algoritmo inesperado debe fallar con 401 |
| Omitir verificación de expiración | Token vencido sigue abriendo rutas |
| Proteger solo los botones del menú | Postman permite POST de usuario consulta |
| No comprobar estado del usuario o rol | Usuario retirado mantiene permisos según política definida |
| `password` se serializa desde la tabla | GET listado/individual contiene hash |
| Cliente del front hace `new PDO` | Separación de frontend/API rota |
| Inventar un `id` o `activo` en `rol_usuario` | DDL real no contiene esa columna |
| Confundir logout de sesión con revocación JWT | Una copia del token sigue válida antes de su expiración |
| Reescribir CRUD v2 para agregar seguridad | Se rompen contratos de anteriores versiones |

## 4. Cómo comprobar lo producido

1. Pedir **casos de prueba** antes de más código: login correcto/incorrecto, expiración, 401, 403, CRUD admin, base apagada, regresión.
2. Probar desde Postman contra la API, no solo mediante clicks del frontend.
3. Inspeccionar `usuario.password` como hash **sin imprimir valores reales** en la sustentación.
4. Intentar una escritura con rol consulta y comprobar que no cambió ninguna fila.
5. Apagar la API mientras el frontend corre; no debe mostrar filas ni conectarse a MariaDB.
6. Hacer revisión humana del checklist y de la diferencia entre docs y código.

## 5. Preguntas para sustentación

- ¿Cuál es la diferencia entre autenticación (401) y autorización (403)?
- ¿Por qué el frontend guarda el JWT en una sesión PHP y lo envía como Bearer?
- ¿Qué garantiza `hash_equals()` al verificar la firma del token?
- ¿Por qué `password_hash()` es preferible a cifrar/hashear una contraseña manualmente?
- ¿Por qué ocultar un botón de editar no impide modificar un recurso?
- ¿Qué pasa con un JWT que alguien copió antes del logout? ¿Qué implementaron realmente?
- ¿Cómo se diferencia un 409 por FK de un 403 por falta de permisos?
- ¿Cómo comprobaron que los 16 CRUD de v1-v2 no se rompieron?

## 6. Restricciones de compatibilidad verificadas en la documentación V2

- **16 recursos** (6 V1 + 10 V2); V3 solo añade los tres de gestión y la capa de identidad.
- **Cinco asociaciones simples V2** (`grupo_linea`, `semillero_linea`, `ac_linea`, `ods_linea`, `aa_linea`) admiten GET/POST/DELETE lógico y responden **405 a PUT/PATCH** si la persona está autorizada; no generar edición para ellas.
- **Controladores V2 revisados** responden **422** a PATCH `{}`; el contrato V1 anterior contemplaba **400**. Cada recurso debe preservar su contrato, a menos que un cambio se apruebe expresamente.
- **Frontend V2** utiliza streams (`stream_context_create` + `file_get_contents`), no cURL: no escribir pruebas que den por hecha una migración no realizada.
- `participa_grupo.rol` y `participa_semillero.rol` describen el papel de un docente, no son roles de acceso.
- El comprimido V2 no contenía el DDL, **pero ahora se recibió `init(4).sql`**: las PK y tipos están verificados y `rol_usuario` no tiene `activo`. Pendiente comprobar si la base en ejecución coincide con el script y decidir la desasignación.
- **Estado documental al 29-10-2026:** pruebas individuales sí; aprobación final, regresión posmerge, decisión C4/C11 y tag aún pendientes. El usuario confirmó más tarde un merge backend; comprobar los demás hechos.

No generar ni modificar archivos PHP hasta que el equipo haya aprobado `2_spec.md` y pasado las tres compuertas SDD.

## 7. Dos formas de utilizar IA (tomado del enfoque metodológico del profesor)

| Camino | Cómo usarlo | Quién comprueba el resultado |
|---|---|---|
| **Chat, archivo por archivo** | Compartir la spec aprobada, pedir un solo archivo y su prueba, revisar antes de avanzar | Estudiante/pareja con Git diff + HTTP/tests |
| **IDE con asistente** | Limitar al conjunto de archivos de una fase y exigir un reporte de cambios y pruebas; no permitir modificaciones masivas a 16 CRUD | Estudiante/pareja revisa diff, rutas y regresión |

**Secuencia mínima en ambos caminos:** (1) leer constitución y specs aprobadas, (2) generar pruebas de caso feliz y rechazo, (3) implementar un componente, (4) ejecutar y registrar las pruebas, (5) revisar cambios antes del commit, (6) pasar a la siguiente fase. Un agente no debe aprobar C3–C11 por deducción. El profesor entrega un ejemplo de **segundo motor**, que se usa para enseñar supervisión y trazabilidad, NO para generar PostgreSQL en esta versión.

### Instrucción de control antes de aceptar código de IA

```text
Compara tu propuesta con 2_spec.md, 5_data_model.md, 6_contracts.md y los contratos V1/V2.
Indica qué RF satisface, qué archivos existentes modifica, qué pruebas ejecutaste
realmente y cuáles son solo propuestas. No agregues rutas, columnas, permisos,
roles ni dependencias sin acuerdo escrito. Si falta la aprobación de C3–C11,
reporta el bloqueo en lugar de rellenarlo. No expongas contraseñas, JWT ni secretos.
```

**Señales de rechazo inmediato:** `POST /api/register` público no aprobado, contraseña admin fija, un `DELETE FROM usuario`, contraseña/hash en respuestas, `SELECT` desde el frontend, permisos decididos únicamente en menús, bloqueo de métodos heredados sin contrato, modificación automática del esquema del puente, `docker compose down -v` para aplicar V3, o claims JWT aceptados sin verificar su firma.
