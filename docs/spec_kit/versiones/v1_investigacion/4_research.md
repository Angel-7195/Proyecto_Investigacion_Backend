# Investigación y decisiones — v1: Investigación (PHP)

Cada decisión con su alternativa descartada. Una decisión sin alternativa no
es una decisión: es lo primero que se le ocurrió a alguien.

## D-v1-1 — PHP puro, sin framework y sin Composer

**Alternativas.** (a) Utilizar un framework como Laravel o Slim para resolver
el enrutamiento, la validación y parte del manejo de errores. (b) Utilizar PHP
puro con las extensiones disponibles en el entorno del proyecto.

**Decisión: (b).** La API y el frontend se desarrollan en PHP puro, sin
framework y sin Composer. De esta manera quedan explícitos el enrutamiento,
la validación de los cuerpos, la separación entre controlador, servicio y
repositorio, y la traducción de las excepciones a códigos HTTP.

**Consecuencias.** Hay más código que escribir manualmente y algunas tareas
que un framework resolvería automáticamente deben implementarse en el
proyecto. A cambio, cada responsabilidad puede localizarse directamente en
los archivos de la aplicación y la arquitectura por capas queda visible.

**Estado:** vigente.

## D-v1-2 — MariaDB como motor de base de datos

**Alternativas.** (a) Utilizar otro motor relacional, como PostgreSQL.
(b) Mantener MariaDB, que es el motor utilizado por el proyecto y por el
script de inicialización disponible.

**Decisión: (b).** La v1 utiliza MariaDB. Los repositorios acceden al motor
mediante PDO y todas las consultas utilizan prepared statements.

La decisión mantiene el proyecto alineado con el entorno definido para la
ruta de PHP y permite trabajar directamente sobre `db/init.sql`, que contiene
la estructura y los datos de referencia del módulo.

**Consecuencias.** Algunas decisiones técnicas de persistencia dependen del
comportamiento de MariaDB y PDO. Por ejemplo, el manejo de `rowCount()` en
operaciones UPDATE debe contemplarse para no interpretar como inexistente una
fila que fue encontrada pero recibió exactamente los mismos valores.

El resto de las capas no depende directamente de MariaDB: solamente las
implementaciones de los repositorios conocen PDO y SQL.

**Estado:** vigente.

## D-v1-3 — La v1 se construye sobre los seis recursos sin claves foráneas

**Contexto.** El repositorio guía desarrolla su primera versión sobre un solo
recurso, `area_conocimiento`. El módulo de Investigación de este proyecto
define un alcance diferente para la v1.

**Alternativas.** (a) Repetir el alcance del repositorio guía y construir
solamente `area_conocimiento`. (b) Adaptar el método del repositorio guía al
alcance real del módulo y construir los seis recursos sin claves foráneas.

**Decisión: (b).** La v1 implementa CRUD completo, tanto en la API como en el
frontend, para:

- `area_conocimiento`
- `objetivo_desarrollo_sostenible`
- `area_aplicacion`
- `termino_clave`
- `universidad`
- `linea_investigacion`

Son las seis tablas del módulo que no dependen de claves foráneas.

**Consecuencias.** La v1 contiene más clases, rutas y pantallas que el ejemplo
del repositorio guía. Sin embargo, todos los recursos siguen la misma
arquitectura por capas y permiten construir la base técnica que utilizarán las
versiones posteriores.

Las tablas con claves foráneas quedan fuera de esta versión para no anticipar
las reglas de integridad y relaciones que corresponden a versiones
posteriores.

**Estado:** vigente.

## D-v1-4 — El borrado es lógico mediante `activo`

**Contexto.** El esquema original del módulo no incorpora una columna para
representar que un registro fue retirado sin eliminarlo físicamente. La
metodología del proyecto establece que las tablas trabajadas en cada versión
deben soportar borrado lógico.

**Alternativas.** (a) Eliminar físicamente las filas mediante `DELETE FROM`.
(b) Agregar el campo `activo` y utilizarlo para retirar los registros sin
borrarlos de MariaDB.

**Decisión: (b).** Las seis tablas trabajadas en la v1 utilizan un campo
`activo BOOLEAN NOT NULL DEFAULT TRUE`.

Una operación DELETE no elimina la fila. Cambia `activo` a falso y las
consultas normales solamente trabajan con registros activos.

**Consecuencias.** Una fila retirada continúa existiendo en MariaDB, pero para
quien utiliza la API se comporta como un recurso que ya no está disponible.

Esto obliga a que los listados y las búsquedas individuales incluyan
explícitamente la condición `activo = TRUE`. También permite conservar las
llaves y los datos históricos aunque el registro haya sido retirado.

El `init.sql` actual agrega `activo` también a tablas de versiones posteriores.
La v1 solamente utiliza esta regla sobre los seis recursos definidos en su
alcance; las demás tablas no forman parte de la implementación de esta
versión.

**Estado:** vigente.

## D-v1-5 — Los códigos HTTP se mantienen separados de las reglas de negocio

**Contexto.** `2_spec.md` define respuestas diferentes para situaciones como
un recurso inexistente, un cuerpo inválido, una operación inválida, una llave
duplicada o un error inesperado.

**Alternativas.** (a) Hacer que los servicios devuelvan directamente códigos
como 404, 409 o 422. (b) Mantener los códigos HTTP en el controlador y
representar los problemas de negocio mediante excepciones.

**Decisión: (b).** Los servicios no conocen códigos HTTP.

Por ejemplo, cuando un recurso no existe o ya fue retirado se utiliza
`NoEncontradoExcepcion`. Cuando MariaDB detecta que una llave primaria ya está
ocupada, el repositorio traduce el conflicto a `ConflictoExcepcion`.

El controlador recibe esas situaciones y las convierte en las respuestas HTTP
definidas en `2_spec.md`.

**Consecuencias.** La lógica de negocio no queda acoplada al protocolo HTTP y
puede probarse independientemente del controlador.

Los seis recursos utilizan los mismos criterios generales de estado HTTP, pero
el formato exacto de las respuestas JSON se documentará posteriormente en
`6_contracts.md`.

Así se evita definir en este documento un sobre de respuesta que todavía no ha
sido establecido por los contratos de la v1.

**Estado:** vigente.

## D-v1-6 — El frontend no comparte código con la API, aunque ambos usan PHP

**Contexto.** La API y el frontend están desarrollados en PHP. Técnicamente
sería posible hacer `require_once` de modelos, servicios u otros archivos del
repositorio de la API desde el frontend.

**Alternativas.** (a) Compartir clases entre los dos proyectos para evitar
repetir nombres de campos y estructuras. (b) Mantenerlos completamente
separados y hacer que el frontend conozca a la API solamente mediante HTTP.

**Decisión: (b).** El frontend no importa modelos, servicios, repositorios ni
ningún otro archivo interno de la API.

`cliente_api.php` es el punto encargado de comunicarse con la API mediante
HTTP. El frontend trabaja con los datos recibidos en las respuestas y no con
las clases PHP utilizadas dentro del backend.

**Consecuencias.** Algunos nombres de campos aparecen tanto en la API como en
las vistas, por lo que existe una pequeña duplicación.

Se acepta esa duplicación para conservar una separación real entre ambos
proyectos. Un cambio interno en una clase de la API no debe romper el
frontend mientras el contrato HTTP siga siendo el mismo.

La separación también puede comprobarse apagando la API: el frontend continúa
respondiendo, pero no obtiene datos de MariaDB ni intenta conectarse
directamente a ella.

**Estado:** vigente.

## D-v1-7 — Cada recurso tiene clases y endpoints específicos

**Contexto.** Los seis recursos de la v1 realizan operaciones CRUD similares,
por lo que técnicamente sería posible construir un controlador y un
repositorio genéricos que recibieran el nombre de la tabla.

**Alternativas.** (a) Crear una ruta genérica como `/api/{tabla}` y reutilizar
las mismas clases para cualquier recurso. (b) Dar a cada recurso sus propias
rutas, controlador, servicio, interfaces, repositorio y modelo.

**Decisión: (b).** Cada uno de los seis recursos tiene clases y endpoints
específicos.

Por ejemplo, `universidad` utiliza `ControladorUniversidad`,
`ServicioUniversidad`, `IRepositorioUniversidad` y
`RepositorioUniversidadMariaDB`, mientras que `termino_clave` tiene sus
propios componentes.

La API tampoco recibe el nombre de una tabla para decidir dinámicamente qué
SQL ejecutar.

**Consecuencias.** Existe más código y algunos métodos tendrán una estructura
parecida entre recursos.

Se acepta esa repetición porque cada recurso puede tener campos, llaves y
reglas diferentes. Un cambio en `linea_investigacion`, por ejemplo, no obliga
a modificar el comportamiento de `area_conocimiento`.

También queda explícito qué recursos están disponibles en la v1 y se evita
construir un CRUD genérico que termine dando acceso accidental a tablas de
versiones posteriores.

**Estado:** vigente.

## D-v1-8 — Las llaves primarias respetan la estructura de cada recurso

**Contexto.** Los seis recursos de la v1 no utilizan la misma estrategia para
sus llaves primarias.

`area_conocimiento`, `objetivo_desarrollo_sostenible`, `area_aplicacion` y
`universidad` reciben un campo `id` como parte de los datos del registro.

`termino_clave` utiliza el propio campo `termino` como llave primaria.

`linea_investigacion` utiliza un `id` autoincremental generado por MariaDB.

**Alternativas.** (a) Forzar a los seis recursos a utilizar siempre un `id`
enviado por el cliente para que todos los CRUD sean iguales. (b) Respetar la
estructura definida por cada tabla.

**Decisión: (b).** La API conserva la estrategia de identificación definida
por el modelo de datos.

Por eso el POST de `linea_investigacion` no exige un `id`, mientras que los
recursos cuya llave es proporcionada por el cliente sí deben recibirla.

En `termino_clave`, las operaciones individuales identifican el recurso
mediante `termino`, no mediante un campo `id` inventado solamente para
uniformar la API.

**Consecuencias.** Los controladores, servicios y repositorios no pueden asumir
que todos los recursos tienen exactamente la misma llave.

Hay pequeñas diferencias entre los seis CRUD, pero esas diferencias reflejan
el esquema real en lugar de ocultarlo detrás de una abstracción artificial.

**Estado:** vigente.

## D-v1-9 — Las credenciales se obtienen desde variables de entorno

**Contexto.** La API necesita datos de conexión para acceder a MariaDB. Esos
valores cambian entre entornos y pueden contener información que no debe
quedar almacenada en Git.

**Alternativas.** (a) Escribir el usuario, la contraseña y el DSN directamente
en los archivos PHP. (b) Obtenerlos mediante variables de entorno y mantener
las credenciales reales fuera del repositorio.

**Decisión: (b).** La API obtiene la configuración de MariaDB mediante
variables de entorno utilizando `getenv()`.

El repositorio conserva un `.env.example` con los nombres de las variables
necesarias, pero el archivo `.env` con los valores reales no se versiona.

El frontend tampoco recibe credenciales de MariaDB. Solamente necesita la
configuración necesaria para comunicarse con la API.

**Consecuencias.** Para ejecutar el proyecto hay que configurar previamente las
variables requeridas en cada entorno.

A cambio, las credenciales no quedan escritas en el código ni se publican al
hacer commit. El mismo código puede utilizarse con distintas configuraciones
sin modificar los archivos PHP.

**Estado:** vigente.

## D-v1-10 — `universidad` debe iniciar con los 6 registros de referencia

**Contexto.** El módulo de Investigación establece que `universidad` forma
parte de la v1 y debe iniciar con 6 registros provenientes de los datos de
referencia.

Durante la revisión del `init.sql` se encontró que la tabla está definida,
pero esos registros iniciales todavía deben incorporarse para cumplir el
estado esperado de la versión.

**Alternativas.** (a) Dejar `universidad` vacía y permitir que los registros se
creen manualmente desde el CRUD. (b) Incorporar los 6 registros definidos por
los datos de referencia antes de cerrar la v1.

**Decisión: (b).** `universidad` debe quedar inicializada con los 6 registros
de referencia como parte de la implementación de la v1.

Los valores no se inventan ni se crean manualmente a partir de ejemplos. Deben
obtenerse de la fuente de datos entregada para el módulo y agregarse al
`init.sql`.

**Consecuencias.** El script de inicialización necesita una modificación antes
de cerrar la versión.

La comprobación de datos iniciales debe verificar que existan los 6 registros
esperados de `universidad`, además de los catálogos ya definidos para
`area_conocimiento`, `objetivo_desarrollo_sostenible` y `area_aplicacion`.

Hasta que esa carga esté incorporada y comprobada, el requisito de datos
iniciales de `universidad` no puede considerarse terminado.

**Estado:** vigente.

## D-v1-11 — Separar las rutas y las vistas del frontend durante la integración

**Contexto.** El plan inicial situaba el enrutamiento del frontend en
`front_php/index.php` y organizaba las pantallas dentro de `vistas/`. Durante
la integración del trabajo de los dos integrantes se completaron los seis
CRUD: tres ya disponían de rutas y vistas propias y los otros tres requerían
sus pantallas de creación, edición y retiro. Concentrarlo todo en `index.php`
habría ampliado el archivo y dificultado revisar cambios independientes.

**Alternativas.** (a) Incorporar todas las rutas y formularios adicionales
directamente en `index.php` y en las vistas existentes. (b) Mantener `index.php`
como punto de entrada y separar las rutas y pantallas de los tres recursos
integrados en archivos auxiliares específicos.

**Decisión: (b).** Se agregó `front_php/rutas_restantes.php` para atender las
rutas CRUD de `area_conocimiento`, `objetivo_desarrollo_sostenible` y
`area_aplicacion`; sus listados y formularios se agruparon en
`front_php/vistas/crud_restantes.php`. `index.php` continúa coordinando las
rutas del frontend y conserva las de `termino_clave`, `universidad` y
`linea_investigacion`. `vistas/inicio.php` y `vistas/plantilla.php` se ampliaron
para ofrecer acceso a los seis recursos.

La configuración de `crud_restantes.php` enumera explícitamente estos tres
recursos y sus campos: **no es un CRUD genérico que acceda a tablas
arbitrarias**. `cliente_api.php` continúa siendo el único componente del
frontend que realiza las peticiones HTTP a la API. No se añadieron conexiones
PDO ni credenciales de MariaDB al frontend.

**Consecuencias.** Se añadieron dos archivos y una delegación desde
`index.php`, pero las responsabilidades previstas, los contratos HTTP y los
requisitos de la v1 no cambiaron. La integración puede revisarse por partes
y resulta más fácil conservar los CRUD ya existentes. Como contrapartida,
se deben mantener coherentes los enlaces y las rutas distribuidas, y el árbol
real de archivos debe quedar reflejado en `3_plan.md`.

**Comprobación realizada.** Se probaron desde el navegador las operaciones
CRUD de los seis recursos y el rechazo de identificadores duplicados. Las
pruebas automatizadas con una API simulada no sustituyen las comprobaciones
pendientes de cierre, como ejecutar el quickstart completo desde cero.

**Estado:** vigente.
