# Tareas — v1: Investigación (PHP + MariaDB)

El orden importa: **de adentro hacia afuera**. Cada fase termina con algo que
se puede comprobar, no con «ya quedó».

La v1 se completa solamente cuando los seis recursos funcionan de extremo a
extremo, tanto en la API como en el frontend.

**Estado de seguimiento (04/10/2026).** `[x]` indica implementación constatada
en el código compartido o comprobada durante las pruebas realizadas. `[ ]`
indica una tarea pendiente o una verificación aún no demostrada; no significa
necesariamente que falte escribir el código. El funcionamiento observado en
el Docker actual no sustituye la prueba de instalación desde cero.


## Fase 0 — El compose y la base

- [x] Definir `docker-compose.yml` con los servicios necesarios para MariaDB,
      la API, el frontend y phpMyAdmin.
- [x] Definir y probar los puertos reales de la API, el frontend y phpMyAdmin.
- [x] Configurar las variables de entorno de MariaDB y la URL de la API.
- [x] Mantener las credenciales reales fuera de Git.
- [x] Mantener `.env` ignorado y `.env.example` versionado.
- [x] `db/init.sql` derivado del script del curso, con los cambios documentados
      en su cabecera.
- [x] Verificar que `activo` está disponible para los seis recursos de la v1.
- [x] Agregar al `init.sql` los 6 registros de `universidad` provenientes de la
      fuente de datos entregada.
- [x] El frontend queda **sin** credenciales de MariaDB y sin dependencia
      directa de la base de datos.

**Verificación:**

Levantar primero la base y comprobar que las semillas esperadas sean:

```text
area_conocimiento                 218
objetivo_desarrollo_sostenible     17
area_aplicacion                    21
universidad                         6
```

`termino_clave` y `linea_investigacion` pueden iniciar vacías.

Los comandos exactos, nombres de servicios y puertos se escribirán en
`7_quickstart.md` después de que el `docker-compose.yml` haya sido probado.


## Fase 1 — Los modelos

- [x] `modelos/AreaConocimiento.php`
- [x] `modelos/ObjetivoDesarrolloSostenible.php`
- [x] `modelos/AreaAplicacion.php`
- [x] `modelos/TerminoClave.php`
- [x] `modelos/Universidad.php`
- [x] `modelos/LineaInvestigacion.php`
- [x] Propiedades privadas y métodos de acceso según corresponda.
- [x] Las llaves suministradas por quien crea el registro no tienen setter para
      ser modificadas después de la creación.
- [x] `linea_investigacion.id` representa la llave generada por MariaDB.
- [x] **Sin** `activo` como campo editable de la ficha
      (`5_data_model.md`, §4).

**Verificación:** cada modelo puede construirse con los datos correspondientes
a su tabla y convertirse a una estructura utilizable por la API sin exponer
`activo` como dato editable.


## Fase 2 — Las interfaces

- [x] `repositorios/IRepositorioAreaConocimiento.php`
- [x] `repositorios/IRepositorioObjetivoDesarrolloSostenible.php`
- [x] `repositorios/IRepositorioAreaAplicacion.php`
- [x] `repositorios/IRepositorioTerminoClave.php`
- [x] `repositorios/IRepositorioUniversidad.php`
- [x] `repositorios/IRepositorioLineaInvestigacion.php`

- [x] `servicios/IServicioAreaConocimiento.php`
- [x] `servicios/IServicioObjetivoDesarrolloSostenible.php`
- [x] `servicios/IServicioAreaAplicacion.php`
- [x] `servicios/IServicioTerminoClave.php`
- [x] `servicios/IServicioUniversidad.php`
- [x] `servicios/IServicioLineaInvestigacion.php`

Las interfaces deben expresar las operaciones necesarias para:

```text
listar
obtener por llave
crear
reemplazar
actualizar parcialmente
retirar
```

**Verificación:** las pruebas de capas ya pueden empezar a escribirse contra
las interfaces aunque todavía no exista la implementación completa.


## Fase 3 — Los repositorios MariaDB

- [x] `RepositorioAreaConocimientoMariaDB.php`
- [x] `RepositorioObjetivoDesarrolloSostenibleMariaDB.php`
- [x] `RepositorioAreaAplicacionMariaDB.php`
- [x] `RepositorioTerminoClaveMariaDB.php`
- [x] `RepositorioUniversidadMariaDB.php`
- [x] `RepositorioLineaInvestigacionMariaDB.php`
- [x] Todos utilizan PDO y prepared statements.
- [x] Todas las consultas normales filtran por `activo = TRUE`.
- [x] El borrado lógico se hace mediante
      `UPDATE ... SET activo = FALSE`.
- [x] El retiro incluye `AND activo = TRUE`.
- [x] Configurar `PDO::MYSQL_ATTR_FOUND_ROWS => true` para evitar 404 falsos en
      UPDATE con los mismos valores.
- [x] Traducir una llave duplicada a `ConflictoExcepcion`.
- [x] `termino_clave` utiliza `termino` como llave.
- [x] `linea_investigacion` obtiene el `id` generado mediante
      `AUTO_INCREMENT`.

**Verificación:** desde PHP se pueden listar los datos de cada recurso y se
obtienen solamente filas activas.

Los conteos iniciales deben coincidir con los definidos en
`5_data_model.md`.


## Fase 4 — Los servicios

- [x] `ServicioAreaConocimiento.php`
- [x] `ServicioObjetivoDesarrolloSostenible.php`
- [x] `ServicioAreaAplicacion.php`
- [x] `ServicioTerminoClave.php`
- [x] `ServicioUniversidad.php`
- [x] `ServicioLineaInvestigacion.php`
- [x] Los servicios dependen de interfaces de repositorio.
- [x] Lanzan `NoEncontradoExcepcion` cuando una ficha no existe o está retirada.
- [x] Un PATCH vacío produce `InvalidArgumentException`.
- [x] Los servicios **nunca** devuelven códigos HTTP.
- [x] `servicios/ensamblador.php` es el único punto que construye las
      implementaciones concretas.

**Verificación:** cada servicio puede ejecutarse con un repositorio falso en
memoria y sus reglas funcionan sin MariaDB.


## Fase 5 — Los controladores

- [x] `ControladorAreaConocimiento.php`
- [x] `ControladorObjetivoDesarrolloSostenible.php`
- [x] `ControladorAreaAplicacion.php`
- [x] `ControladorTerminoClave.php`
- [x] `ControladorUniversidad.php`
- [x] `ControladorLineaInvestigacion.php`
- [x] Validar campos obligatorios, tipos y longitudes según `2_spec.md`.
- [x] Mantener una lista blanca de campos permitidos.
- [x] Rechazar `activo` como campo de POST, PUT o PATCH.
- [x] Rechazar las llaves primarias en PUT y PATCH.
- [x] En `linea_investigacion`, rechazar también `id` en POST.
- [x] Utilizar la misma lógica de validación para PUT y PATCH, diferenciando
      qué campos son obligatorios.
- [x] Traducir las excepciones a los códigos definidos en `6_contracts.md`.

La traducción esperada es:

```text
cuerpo inválido                    → 422
operación inválida                 → 400
recurso inexistente o retirado     → 404
llave duplicada                    → 409
error inesperado                   → 500
```

**Verificación:** enviar cuerpos válidos e inválidos directamente a cada
controlador y comprobar que el servicio solo se ejecuta cuando la forma de la
petición es válida.


## Fase 6 — El enrutador

- [x] `index.php` reconoce las rutas específicas de los seis recursos.
- [x] No existe una ruta genérica como `/api/{tabla}`.
- [x] Implementar `GET /` como diagnóstico de la API.
- [x] Registrar para cada recurso:

```text
GET    /api/recurso
GET    /api/recurso/{llave}
POST   /api/recurso
PUT    /api/recurso/{llave}
PATCH  /api/recurso/{llave}
DELETE /api/recurso/{llave}
```

- [x] `termino_clave` utiliza `{termino}` como llave en la ruta.
- [x] Los demás recursos individuales utilizan `{id}`.
- [x] Una ruta existente con un método no permitido responde 405.
- [x] El 404 de **ruta inexistente** se diferencia del 404 de
      **registro inexistente**.

**Verificación:** ejecutar manualmente los contratos definidos en
`6_contracts.md`.

Cuando los puertos y el compose estén confirmados, estos mismos pasos se
convertirán en los comandos ejecutables de `7_quickstart.md`.


## Fase 7 — Las pruebas de capas

- [x] `pruebas/prueba_capas.php`.
- [x] Crear repositorios falsos en memoria para los seis servicios.
- [x] Los repositorios falsos también realizan borrado lógico.
- [x] Comprobar búsqueda de registros activos.
- [x] Comprobar recurso inexistente.
- [x] Comprobar recurso retirado.
- [x] Comprobar PUT completo.
- [x] Comprobar PATCH parcial.
- [x] Comprobar PATCH vacío.
- [x] Comprobar que retirar dos veces produce el comportamiento de
      `NoEncontradoExcepcion`.
- [x] Comprobar que las reglas funcionan **con MariaDB apagada**.
- [x] `pruebas/prueba_capas_restantes.php`: pruebas con repositorios falsos
      de área de conocimiento, ODS y área de aplicación (30 casos).
- [x] `pruebas/prueba_controladores_restantes.php`: 51 comprobaciones
      simuladas de los controladores de esos tres recursos.

**Verificación:**

```text
php pruebas/prueba_capas.php
```

Todas las verificaciones deben terminar en `[OK]`.

El comando Docker exacto se añadirá al `7_quickstart.md` cuando el nombre real
del servicio API esté definido.


## Fase 8 — LA PANTALLA (la otra mitad de la versión)

En el repositorio del frontend:

- [x] `cliente_api.php`: el único componente que habla con la API mediante HTTP.
- [x] **Cero PDO** en el frontend.
- [x] **Cero credenciales de MariaDB**.
- [x] `index.php` enruta las pantallas.
- [x] `rutas_restantes.php` recibe las rutas de los tres recursos integrados
      y delega en sus vistas sin sustituir `index.php` como punto de entrada.
- [x] `vistas/crud_restantes.php` incorpora listados y formularios para
      `area_conocimiento`, `objetivo_desarrollo_sostenible` y `area_aplicacion`.
- [x] `vistas/inicio.php` y `vistas/plantilla.php` muestran los seis recursos
      y conservan la navegación de los tres CRUD originales.
- [x] Permitir servir directamente los archivos estáticos cuando se utilice
      `php -S`.
- [x] `vistas/` contiene la plantilla, inicio, listados, formularios y 404.
- [x] Bootstrap está descargado dentro de `publico/`, no mediante CDN.
- [x] Pantalla para `area_conocimiento`.
- [x] Pantalla para `objetivo_desarrollo_sostenible`.
- [x] Pantalla para `area_aplicacion`.
- [x] Pantalla para `termino_clave`.
- [x] Pantalla para `universidad`.
- [x] Pantalla para `linea_investigacion`.
- [x] Cada recurso permite listar, crear, editar y retirar.
- [x] El frontend diferencia guardar la ficha completa de guardar cambios
      parciales.
- [x] Un listado 204 muestra «Todavía no hay registros» y no un error.
- [x] Un 409 muestra un mensaje comprensible de llave ocupada.
- [x] Si la API está apagada, la pantalla continúa respondiendo y no muestra
      datos de MariaDB.

**Verificación:** recorrer manualmente los seis recursos desde el navegador.

**Comprobación efectuada:** el integrante probó desde el navegador los seis
CRUD, incluido el rechazo de claves duplicadas. La versión integrada también
se comprobó con API simulada. **Pendiente:** repetir con la API apagada en
el Docker final y comprobar manualmente la experiencia de todos los errores.

Después se preparará el guion de humo del frontend y su comando definitivo se
documentará en `7_quickstart.md`.


## Fase 9 — Completar el quickstart

- [x] Confirmar los nombres reales de los servicios Docker.
- [x] Confirmar los puertos reales de API, frontend y phpMyAdmin.
- [x] Completar `7_quickstart.md` con comandos que hayan sido ejecutados de
      verdad.
- [x] Comprobar los conteos iniciales.
- [x] Comprobar POST → 201.
- [x] Comprobar PUT y PATCH.
- [x] Comprobar DELETE lógico.
- [x] Comprobar segundo DELETE → 404.
- [x] Comprobar llave duplicada → 409.
- [x] Comprobar validaciones → 422.
- [x] Comprobar PATCH vacío → 400.
- [x] Comprobar prueba de capas sin MariaDB.
- [x] Comprobar frontend con la API apagada.

**Verificación:** otra persona puede seguir `7_quickstart.md` desde cero sin
tener que adivinar puertos, nombres de servicios ni comandos.

**Pendiente de documentación:** el archivo `7_quickstart.md` recibido aún
contiene un esquema de secciones, no todos los comandos ejecutables. Antes de
marcar terminada esta fase hay que completarlo, ejecutarlo desde cero y
verificar los seis datos iniciales de `universidad` en `db/init.sql`.


## Fase 10 — Cerrar

- [ ] Preparar la colección de Postman con los seis recursos y los casos
      principales definidos en `6_contracts.md`.
- [ ] Ejecutar todos los criterios de aceptación de `2_spec.md`.
- [ ] Confirmar que los 6 registros iniciales de `universidad` están cargados.
- [ ] Confirmar que `termino_clave` y `linea_investigacion` pueden iniciar
      vacías sin romper el frontend.
- [ ] Completar `9_checklist.md`.
- [ ] Revisar el checklist manualmente: que una prueba responda no garantiza
      que la interfaz sea comprensible.
- [ ] Confirmar que no quedaron secretos versionados.
- [x] Confirmar que la v1 no implementó funcionalidad de versiones posteriores.
- [ ] Integrar los cambios mediante las ramas y Pull Requests definidos para
      el proyecto.
- [ ] Crear el tag `v1` solamente después de que todos los criterios estén
      aprobados.
