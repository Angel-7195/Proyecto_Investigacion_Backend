# Guía de IA — V2: Investigación (actualizada)

> **Estado al 29 de octubre de 2026:** las diez entidades de V2 están **implementadas en backend y frontend** y se probaron individualmente durante el desarrollo. **La V2 no se declara formalmente cerrada**: falta verificar la integración final de ambos repositorios, realizar regresión, resolver las decisiones abiertas y obtener aprobación del equipo.
>
> **Regla principal:** la IA debe seguir la constitución **del equipo**, el SQL y los archivos **reales** del proyecto. No debe inventar columnas ni marcar como aprobadas las compuertas, tareas o pruebas que no se hayan comprobado.

## 1. Antes de pedir código

### 1.1. Fuentes que debe consultar la IA

Leer y contrastar, según la tarea, en este orden:

1. `docs/spec_kit/1_constitution.md` **vigente del equipo**; no asumir que una constitución de ejemplo está aprobada sin revisión.
2. `docs/spec_kit/versiones/0_mapa_versiones.md`, verificando que **la versión utilizada por el equipo** distinga V1 (seis entidades sin FK) y V2 (diez entidades con FK). Si el mapa dice otra cosa, **registrar la discrepancia y pedir su revisión**; no corregirlo silenciosamente.
3. `docs/spec_kit/versiones/v2_entidades_con_fk/2_spec.md`, en particular requisitos, criterios y clarificaciones C1–C12.
4. `docs/spec_kit/versiones/v2_entidades_con_fk/5_data_model.md` **junto al `db/init.sql` vigente en la rama de trabajo**: este último confirma nombres, tipos, nulabilidad, PK y FK. No adivinar el esquema.
5. `docs/spec_kit/versiones/v2_entidades_con_fk/6_contracts.md`, para las rutas, cuerpos JSON, operaciones y códigos HTTP **efectivamente implementados**.
6. `3_plan.md` y `4_research.md` de V2, para arquitectura y decisiones técnicas.
7. `7_quickstart.md`, `8_tasks.md` y `9_checklist.md` de V2, para ejecutar verificaciones y distinguir tareas completadas de pendientes.
8. `0_METODOLOGIA.md`, `modulo_investigacion.md` y los documentos aplicables de V1, sin alterar contratos heredados.
9. **Código actual** de `api_investigacion/` y `front_php/`, especialmente archivos compartidos y clases de la entidad afectada; comprobar también `git status` y la rama activa antes de editar.

> **Prioridad ante diferencias:** no presentar una discrepancia entre constitución, especificación, SQL y código como si ya estuviera resuelta. Explicar qué afirma cada fuente, cuál es la implementación vigente y qué decisión o cambio hace falta. Nunca inventar aprobaciones del profesor o del equipo.

### 1.2. Contexto técnico confirmado

- **Dos repositorios independientes:** `Proyecto_Investigacion_Backend` y `Proyecto_Investigacion_Frontend`, con trabajo en ramas y Pull Requests.
- **Backend:** PHP 8.3 puro, sin Composer ni framework; arquitectura de controlador → servicio (interfaz) → repositorio (interfaz y MariaDB), con modelos y excepciones; acceso a MariaDB mediante PDO y sentencias preparadas.
- **Integración compartida del backend:** `api_investigacion/index.php` y `api_investigacion/servicios/ensamblador.php`.
- **Frontend:** PHP independiente (`front_php/`), con `index.php`, `cliente_api.php`, `rutas/`, `vistas/` y recursos locales. Se comunica con la API **solo por HTTP/JSON**, sin PDO ni modelos del backend.
- **Cliente HTTP actual del frontend:** `stream_context_create()` y `file_get_contents()`. **No afirmar que ya utiliza cURL**; la exigencia literal de cURL sigue pendiente de aclaración o adecuación (C11).
- **Entidades:** seis de V1 y diez de V2 = **16 recursos implementados**. La implementación por entidad está probada; la regresión integral posterior al merge sigue pendiente.

| Entidades V2 con PK simple | Asociaciones con PK compuesta y campos editables | Asociaciones con PK compuesta sin campos editables |
|---|---|---|
| `docente`, `grupo_investigacion`, `semillero` | `participa_grupo`, `participa_semillero` | `ac_linea`, `ods_linea`, `aa_linea`, `grupo_linea`, `semillero_linea` |

## 2. Prompt sugerido para reorganización o refactorización (una entidad a la vez)

> **Uso actual:** la reorganización de V1 ya se realizó. Este prompt se conserva para **revisiones o refactorizaciones puntuales**, no para repetir movimientos de archivos que ya existen.

```text
Actúa como colaborador de nuestro proyecto académico PHP 8.3 puro,
MariaDB, PDO y arquitectura estricta por capas e interfaces.
No uses Composer ni frameworks.

Lee primero la constitución, el Spec Kit y la estructura ACTUAL
que tengo en mi rama. Necesito reorganizar o refactorizar únicamente
[ENTIDAD / CAMBIO], SIN alterar su comportamiento público.

Compara el árbol de carpetas actual con la estructura deseada.
Si realmente hay archivos que mover, indica los git mv necesarios;
si ya están organizados, no los muevas por segunda vez.
Identifica los require_once, ensamblador, rutas y pruebas afectados.
No renombres clases, interfaces, métodos públicos, rutas HTTP,
campos JSON ni columnas SQL sin una decisión y plan explícitos.

Propón cambios mínimos, muestra cómo verificar php -l,
la API, el frontend y la regresión de V1 y V2.
No reemplaces archivos compartidos sin revisar su versión actual.
Los comentarios, explicaciones y mensajes estarán en español.
```

## 3. Prompt sugerido para mantener o corregir una entidad V2

> **Uso actual:** las diez entidades ya existen. Pedir a la IA **corregir, verificar o extender un comportamiento concreto**, no generarlas de nuevo ni sobrescribir archivos compartidos sin inspección previa.

```text
Actúa como desarrollador y revisor de nuestro proyecto Investigación V2.
Necesito [CORRECCIÓN / AJUSTE / VERIFICACIÓN] en [ENTIDAD].
No modifiques otros recursos sin justificar una dependencia real.

1. Lee 1_constitution.md, 2_spec.md, 3_plan.md,
   5_data_model.md, 6_contracts.md y el DDL de db/init.sql.
2. Inspecciona los archivos ACTUALES de la entidad en modelos,
   repositorios, servicios y controladores, además de index.php,
   ensamblador.php, cliente_api.php y las rutas/vistas pertinentes.
3. Mantén PHP 8.3 puro, PDO parametrizado, interfaces,
   responsabilidades por capa y mensajes en español.
4. Usa exactamente los nombres y tipos SQL; no inventes PK id
   para tablas de asociación. En las siete asociaciones la clave
   es compuesta y los identificadores no se cambian por PUT/PATCH.
5. Respeta el borrado lógico: DELETE cambia activo a FALSE;
   no uses DELETE FROM ni permitas activo en POST, PUT o PATCH.
6. En las cinco asociaciones simples (ac_linea, ods_linea,
   aa_linea, grupo_linea y semillero_linea) PUT/PATCH devuelven 405.
   En participa_grupo y participa_semillero se pueden actualizar
   rol, fecha_inicio y fecha_fin, sin modificar su PK.
7. Verifica que las FK referencien registros activos al crear o
   cambiar asociaciones. Parejas duplicadas o ya retiradas:
   409, sin reactivación automática. Validaciones: normalmente 422.
   PATCH vacío de participaciones: 422 comprobado, no 400.
   No cambies códigos HTTP solo por intuición: contrástalos
   con el controlador y 6_contracts.md.
8. Respeta GET de colección 200/204, GET de retirado 404,
   creación 201 y retiro lógico exitoso 200, según el contrato.
9. Conserva la separación de procesos: el frontend solo usa HTTP,
   nunca PDO o require_once de las clases de backend.
10. Compara los archivos compartidos antes de reemplazarlos;
    entrega únicamente los cambios necesarios y una explicación
    breve de su efecto y de las comprobaciones por ejecutar.

Antes de dar una tarea por completada, distingue: cambios escritos,
sintaxis comprobada, pruebas ejecutadas y aceptación del equipo.
No declares aprobado el merge, la regresión o el tag v2 sin evidencia.
```

### 3.1. Reglas que la IA no puede pasar por alto

- **Siete claves compuestas, con orden literal de la ruta:**
  - `participa_grupo`: (`docente_cedula`, `grupo_investigacion_id`).
  - `participa_semillero`: (`docente`, `semillero`).
  - `ac_linea`: (`linea_investigacion`, `area_conocimiento`), con `area_conocimiento` de tipo **VARCHAR(6)**.
  - `ods_linea`: (`linea_investigacion`, `ods`).
  - `aa_linea`: (`area_aplicacion`, `linea_investigacion`).
  - `grupo_linea`: (`grupo_investigacion`, `linea_investigacion`).
  - `semillero_linea`: (`semillero`, `linea_investigacion`).
- **Tres PK simples de V2:** `docente.cedula`, `grupo_investigacion.id` y `semillero.id` se proporcionan al crear los registros; no asumir autoincremento.
- **Participaciones:** `rol` no vacío de hasta 15 caracteres; `fecha_inicio` obligatoria; `fecha_fin` opcional (`null`) y nunca anterior al inicio.
- **Nombres históricos:** conservar `docente.nacionalidaad` y `grupo_investigacion.universidsad` mientras el SQL y los contratos no se modifiquen formalmente.
- **Conflictos y errores:** los `409` son conflictos conocidos, no una respuesta universal para toda excepción de base de datos; no exponer errores SQL internos en HTTP.

## 4. Señales de alarma

- Suponer una PK `id` para una tabla puente sin mirar el SQL, o intercambiar el orden de los componentes en la URL.
- Inventar nombres de FK (`grupo_id`, `semillero_id`, etc.) en lugar de usar las columnas literales del esquema.
- Crear un enrutador SQL genérico `/api/{tabla}` en vez de rutas específicas.
- Reemplazar el borrado lógico con `DELETE FROM`, permitir editar `activo` o reactivar silenciosamente una PK retirada.
- Ofrecer PUT/PATCH en las cinco asociaciones simples sin atributos editables.
- Obligar a un PATCH vacío de las participaciones a devolver `400` aunque el contrato implementado y comprobado indique `422`.
- Aceptar asociaciones con una FK inexistente o inactiva sin la validación de servicio correspondiente.
- Declarar una propiedad en el modelo con un nombre distinto al usado por su constructor o getters: en PHP 8.3 puede generar avisos por **propiedades dinámicas** que alteran la respuesta HTTP (caso corregido en `SemilleroLinea.php`).
- Enviar un cuerpo JSON con peticiones GET desde scripts PowerShell, o interpretar un aviso PHP que ensució el JSON como éxito válido.
- Permitir que el frontend use PDO, importe clases del backend o declare que usa cURL sin comprobar el código.
- Usar Bootstrap por CDN o introducir dependencias/frameworks no aprobados.
- Agregar JWT, login, roles o dashboards de V3/V4 dentro del alcance de V2.
- Sobrescribir `index.php`, `ensamblador.php`, `cliente_api.php` o `plantilla.php` sin comparar cambios de las dos ramas.
- Ejecutar `docker compose down -v`, borrar registros de prueba físicamente o hacer `git reset --hard`/`git push --force` como soluciones rutinarias.
- Afirmar que V2 está «cerrada», que las Pull Requests ya se fusionaron o que existe un tag `v2` sin registro verificable.

## 5. Mantenimiento, pruebas y cierre de V2

### 5.1. Flujo recomendado para cualquier corrección

1. **Inspeccionar antes de editar:** revisar la rama con `git branch --show-current` y `git status`; comprobar especificación, contrato, DDL y archivos actuales.
2. **Cambiar solo lo necesario:** respetar separación por capas y proteger los archivos compartidos; trabajar por entidad o por incidencia concreta.
3. **Comprobar sintaxis:** ejecutar `php -l` sobre los archivos modificados. Esto valida sintaxis, **no** el comportamiento del CRUD.
4. **Reconstruir el servicio modificado:** desde la raíz del backend usar `docker compose up -d --build api-investigacion` para cambios de API o `docker compose up -d --build front-php` para cambios de frontend.
5. **Probar la API:** comprobar el endpoint del recurso, sus estados HTTP y las validaciones pertinentes. Reutilizar scripts `.ps1` existentes; no generar uno nuevo si el equipo ya dispone de pruebas adecuadas.
6. **Verificar borrado lógico cuando aplique:** consultar manualmente en phpMyAdmin que `activo = 0`; no sustituir esta evidencia por una simple respuesta HTTP si la tarea exige verificación SQL.
7. **Probar el frontend:** registrar, consultar y retirar desde el navegador; comprobar selectores, nombres legibles, errores y conservación de los demás CRUD.
8. **Registrar evidencia:** anotar comandos, resultados, incidencias y fecha en la documentación apropiada. No afirmar que una prueba fue ejecutada cuando solo se preparó un script.

### 5.2. Comandos y direcciones de referencia

Desde `Proyecto_Investigacion_Backend`:

```powershell
docker compose config --services
docker compose up -d --build
docker compose ps

# Reconstruir únicamente el servicio modificado:
docker compose up -d --build api-investigacion
docker compose up -d --build front-php

# Ejemplo de diagnóstico de la API:
Invoke-WebRequest -Uri 'http://localhost:8111/api/semillero_linea' `
    -UseBasicParsing | Select-Object StatusCode, Content

# Ejemplo de script existente, si está presente en la raíz:
powershell -NoProfile -ExecutionPolicy Bypass -File .\pruebas_semillero_linea_v2.ps1
```

Servicios locales del entorno de desarrollo: frontend `http://localhost:8110`, API `http://localhost:8111`, phpMyAdmin `http://localhost:8105` y MariaDB en el puerto publicado `13330`. **Comprobar el Compose actual antes de suponer esos puertos en otra máquina.** El cliente HTTP del frontend utiliza la variable de entorno `URL_API`, que dentro de Docker debe apuntar al servicio de API, no al `localhost` del contenedor del frontend.

> **Precaución:** `docker compose down` detiene los servicios sin eliminar volúmenes; `docker compose down -v` puede borrar los datos persistentes de MariaDB y no es parte del flujo normal de pruebas.

### 5.3. Trabajo en los dos repositorios y cierre formal

- Verificar las ramas del equipo antes de cambiar código. Por ejemplo, Miguel trabaja en `feature/miguel-investigacion-v2`; no hacer commits directamente en `main` si el repositorio exige revisión mediante PR.
- Antes de fusionar cambios, revisar diffs de `index.php`, `ensamblador.php`, `cliente_api.php`, `inicio.php` y `plantilla.php`, porque pueden recibir modificaciones de ambos integrantes.
- Después del merge final de **ambos** repositorios, repetir una **regresión de los 16 recursos**: API y pantallas de V1/V2, validaciones, integridad y borrado lógico. Las pruebas individuales hechas durante el desarrollo **no sustituyen** esta regresión.
- Probar formalmente el frontend **con la API detenida** y confirmar que la pantalla continúa disponible mostrando indisponibilidad, sin recurrir a datos falsos o acceso directo a MariaDB.
- Resolver y documentar las decisiones aún abiertas de `2_spec.md`/`9_checklist.md`: **C4** (retiro de padres con hijos activos), **C7/C8** (revisión y cierre heredado/constitución y mapa de versiones) y **C11** (cURL exigido por la metodología frente al cliente HTTP implementado).
- Completar `9_checklist.md`, aprobar las decisiones pendientes con el equipo y verificar PR, merges y eventual tag `v2`. **No afirmar que estas acciones ocurrieron hasta disponer de evidencia.**

### 5.4. Prompt sugerido para la revisión final

```text
Revisa el cierre de Investigación V2 sin modificar código todavía.
Lee el Spec Kit actualizado, 1_constitution.md, el SQL y las
versiones finales de api_investigacion y front_php de ambos repositorios.

Compara los 16 recursos y sus pantallas con los contratos REST,
propón una matriz de regresión reproducible e identifica
incidencias y decisiones sin resolver (C4, C7/C8, C11).
Distingue evidencia existente, pruebas por repetir y
aprobaciones formales pendientes.
No declares aprobada la compuerta, el merge ni el tag v2
hasta que el equipo lo confirme y exista evidencia verificable.
```
