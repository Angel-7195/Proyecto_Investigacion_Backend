# Checklist de cierre — v1: Investigación (PHP)

> Esto **no lo pasa un guion**. Las pruebas comprueban que el sistema
> responda; esta lista comprueba que esté bien hecho, que la documentación
> coincida con el código y que la aplicación se entienda. La firma una persona.

**Revisión parcial (04/10/2026).** Se marcan únicamente criterios observados
en el código aportado o comprobados durante las pruebas. Los ítems sin marcar
pueden estar implementados, pero necesitan evidencia adicional antes de
firmar este checklist o crear el tag `v1`.


## A. Funciona

- [ ] `docker compose up -d --build` levanta correctamente todos los servicios
      definidos para la v1.
- [x] La API responde correctamente en el puerto definido en
      `docker-compose.yml`.
- [x] El frontend responde correctamente en el puerto definido en
      `docker-compose.yml`.
- [ ] Los criterios de aceptación de [2_spec.md](2_spec.md) pasan a mano
      utilizando los comandos de [7_quickstart.md](7_quickstart.md).
- [x] Los seis recursos de la v1 funcionan de extremo a extremo:
      `area_conocimiento`, `objetivo_desarrollo_sostenible`,
      `area_aplicacion`, `termino_clave`, `universidad` y
      `linea_investigacion`.
- [x] Cada recurso permite listar, consultar una ficha, crear, reemplazar,
      actualizar parcialmente y retirar.
- [ ] Los listados vacíos responden `204` sin cuerpo y el frontend los trata
      como un estado válido, no como un error.
- [x] Un POST válido responde `201`.
- [x] PUT y PATCH responden `200` cuando la operación es válida.
- [x] El borrado lógico responde `200` la primera vez y `404` si se intenta
      retirar nuevamente la misma ficha.
- [x] Una llave primaria duplicada responde `409`.
- [x] Un cuerpo inválido responde `422`.
- [x] Un PATCH sin campos para modificar responde `400`.
- [x] La prueba de capas termina correctamente y se ejecutó con MariaDB
      **apagada** al menos una vez.
- [ ] El frontend también fue probado con la API **apagada** y continúa
      mostrando una pantalla utilizable sin consultar directamente MariaDB.


## B. Las capas están de verdad cortadas

- [ ] **Ningún** archivo de `servicios/` conoce HTTP, códigos de estado,
      `$_GET`, `$_POST` ni detalles del controlador.
- [ ] **Ningún** archivo fuera de `repositorios/` contiene SQL ni utiliza PDO
      para acceder a MariaDB.
- [x] Los servicios dependen de interfaces de repositorio y no de las
      implementaciones MariaDB directamente.
- [x] Cada uno de los seis recursos tiene su propia interfaz de repositorio.
- [x] Cada uno de los seis recursos tiene su propia interfaz de servicio.
- [x] Cada recurso tiene su propio controlador, servicio, repositorio y modelo.
- [x] No existe un CRUD genérico que reciba el nombre de una tabla para decidir
      qué SQL ejecutar.
- [x] `servicios/ensamblador.php` es el punto encargado de construir las
      implementaciones concretas necesarias.
- [x] El frontend **no utiliza PDO**.
- [x] El frontend **no contiene credenciales de MariaDB**.
- [x] El frontend **no hace `require`** de modelos, servicios, repositorios ni
      otros archivos internos de la API.
- [x] El frontend se comunica con la API únicamente mediante HTTP.
- [x] `index.php` sigue siendo el punto de entrada del frontend y delega
      únicamente las rutas de tres recursos a `rutas_restantes.php`.
- [x] `vistas/crud_restantes.php` contiene listados y formularios de esos tres
      recursos sin conectarse directamente a MariaDB.
- [ ] Apagar la API impide obtener datos desde el frontend aunque MariaDB siga
      funcionando. Eso demuestra que el frontend no tiene un camino alterno
      hacia la base de datos.


## C. El contrato y la documentación dicen lo mismo que el código

- [ ] Cada ruta documentada en [6_contracts.md](6_contracts.md) existe realmente
      en la API.
- [ ] Cada ruta documenta sus desenlaces de **éxito y error**, no solamente el
      caso feliz.
- [ ] Los códigos HTTP implementados coinciden con
      [6_contracts.md](6_contracts.md).
- [x] POST devuelve `201` cuando crea correctamente.
- [x] Una llave duplicada devuelve `409`.
- [x] Un recurso inexistente o retirado devuelve `404`.
- [x] Una petición con campos inválidos devuelve `422`.
- [x] Una operación inválida, como PATCH `{}`, devuelve `400`.
- [ ] Una ruta existente utilizada con un método no permitido devuelve `405`.
- [ ] Los errores inesperados quedan controlados como `500`.
- [ ] Los ejemplos de [6_contracts.md](6_contracts.md) son coherentes entre sí:
      una ficha creada puede consultarse, modificarse y retirarse utilizando la
      misma llave.
- [x] `termino_clave` utiliza realmente `termino` como llave y no un `id`
      inventado.
- [x] `linea_investigacion` genera su `id` mediante `AUTO_INCREMENT`.
- [x] El `id` generado al crear una línea de investigación se devuelve al
      cliente.
- [ ] `activo` no se acepta en POST, PUT ni PATCH.
- [ ] Las llaves primarias no pueden modificarse mediante PUT o PATCH.
- [ ] Los comandos de [7_quickstart.md](7_quickstart.md) fueron ejecutados
      exactamente como están escritos y producen los resultados documentados.
- [ ] [5_data_model.md](5_data_model.md) coincide con el esquema utilizado
      realmente por MariaDB.
- [ ] Los cambios documentados en la cabecera de `db/init.sql` coinciden con
      los explicados en [5_data_model.md](5_data_model.md).
- [ ] Cada decisión vigente de [4_research.md](4_research.md) conserva su
      contexto, alternativas, decisión y consecuencias.
- [x] `3_plan.md` refleja los archivos `rutas_restantes.php` y
      `vistas/crud_restantes.php`, y `4_research.md` justifica su creación
      mediante la decisión D-v1-11 (documentación preparada; pendiente de
      integrar en la rama correspondiente).


## D. La pantalla habla el idioma del usuario

- [ ] Palabras técnicas como `PUT`, `PATCH`, `409`, `422`, `/api/`, PDO o
      MariaDB no aparecen como instrucciones normales para quien utiliza la
      aplicación.
- [x] La interfaz permite distinguir entre guardar una ficha completa y guardar
      solamente los cambios sin exigir al usuario conocer los verbos HTTP.
- [ ] Los errores de validación aparecen en español y explican qué dato debe
      corregirse.
- [ ] Un error de validación **no borra** lo que la persona ya había escrito en
      el formulario.
- [ ] Cuando un listado está vacío, la pantalla informa que todavía no existen
      registros y permite agregar uno nuevo.
- [ ] Una respuesta `204` no se presenta como un fallo del sistema.
- [ ] Una respuesta `409` se muestra como un mensaje comprensible indicando que
      la llave ya está ocupada.
- [ ] Un recurso inexistente o retirado muestra un mensaje comprensible sin
      enseñar simplemente el número `404`.
- [ ] Si ocurre un error interno, la pantalla muestra un mensaje general sin
      exponer detalles sensibles de MariaDB o del servidor.
- [ ] Si la API no responde, la pantalla informa que el servicio no está
      disponible y **sigue en pie**.
- [x] La acción visible para DELETE utiliza la idea de **retirar** el registro,
      porque el borrado es lógico y la fila continúa almacenada.
- [x] Bootstrap se carga desde los archivos locales de `publico/` y no desde un
      CDN.
- [x] Los archivos estáticos se sirven correctamente y no terminan en el 404
      del enrutador del frontend.


## E. El alcance y los datos de la v1 están completos

- [x] La v1 implementa únicamente los seis recursos sin claves foráneas:
      `area_conocimiento`, `objetivo_desarrollo_sostenible`,
      `area_aplicacion`, `termino_clave`, `universidad` y
      `linea_investigacion`.
- [x] No se implementaron CRUD, endpoints ni pantallas para tablas que
      corresponden a versiones posteriores.
- [x] No se adelantaron autenticación, JWT, roles, dashboard ni otras
      funcionalidades reservadas para versiones futuras.
- [ ] `area_conocimiento` inicia con las 218 filas de referencia.
- [ ] `objetivo_desarrollo_sostenible` inicia con las 17 filas de referencia.
- [ ] `area_aplicacion` inicia con las 21 filas de referencia.
- [ ] `universidad` inicia con los 6 registros provenientes de la fuente de
      datos entregada.
- [ ] Los registros de `universidad` **no fueron inventados** para completar la
      cantidad.
- [ ] `termino_clave` puede iniciar sin semillas obligatorias y el sistema
      funciona correctamente.
- [ ] `linea_investigacion` puede iniciar sin semillas obligatorias y el
      sistema funciona correctamente.
- [x] Los SELECT normales de los seis recursos trabajan solamente con
      `activo = TRUE`.
- [x] DELETE no utiliza `DELETE FROM`: cambia el registro a
      `activo = FALSE`.
- [x] Una ficha retirada deja de aparecer en el listado y en el GET individual.
- [x] La fila retirada continúa existiendo físicamente en MariaDB.
- [x] Una llave retirada continúa ocupando su llave primaria y no puede
      reutilizarse silenciosamente mediante un nuevo POST.
- [x] `linea_investigacion.id` sigue siendo responsabilidad de MariaDB mediante
      `AUTO_INCREMENT`.


## F. Configuración, Git y cierre de versión

- [x] Las credenciales de MariaDB se obtienen mediante variables de entorno.
- [ ] `.env` no está versionado.
- [ ] `.env.example` contiene únicamente los nombres y ejemplos seguros de las
      variables necesarias.
- [ ] No hay contraseñas, tokens ni otros secretos escritos directamente en el
      código.
- [x] API y frontend permanecen en los repositorios correspondientes definidos
      para el proyecto.
- [ ] Los cambios importantes de la v1 fueron integrados mediante ramas y Pull
      Requests.
- [ ] [7_quickstart.md](7_quickstart.md) quedó actualizado después de probar los
      puertos, nombres de servicios y comandos reales.
- [x] [8_tasks.md](8_tasks.md) distingue las tareas verificadas de las que
      continúan pendientes de evidencia o implementación.
- [ ] La colección de Postman contiene las operaciones principales de los seis
      recursos.
- [ ] No existe deuda conocida entre lo que dice la especificación y lo que
      hace el código.
- [ ] Todos los criterios anteriores están comprobados antes de crear el tag
      `v1`.


---

**Revisó:** ______________________________

**Fecha:** _______________________________


**Pendientes conocidos antes de firmar el cierre de la v1:**

- Incorporar y verificar en una inicialización nueva los seis registros de
  `universidad` tomados de la fuente entregada; no inventar datos.
- Completar `7_quickstart.md` con comandos reales y ejecutarlo desde cero.
- Probar la versión final del frontend con la API apagada.
- Comprobar los casos de contrato que no constan en las pruebas aportadas,
  especialmente 204 real en los listados pertinentes, 405 y manejo de 500.
- Revisar `.env`, secretos, colección Postman, integración mediante PR y
  todos los criterios pendientes antes de crear el tag `v1`.

**Lo que quedó anotado para la v2:**

```text

```
