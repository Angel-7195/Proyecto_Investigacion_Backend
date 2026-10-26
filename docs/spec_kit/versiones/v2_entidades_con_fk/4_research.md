# 4. Investigación y decisiones — V2

> Decisiones propuestas. Verificar en equipo y contra el código real; cada una registra contexto, alternativas, decisión y consecuencias.

## D-V2-01 — Reorganizar por recurso sin modificar contratos
**Contexto.** Con seis entidades V1 y diez nuevas, archivos planos dificultan navegar `servicios/` y `repositorios/`.
**Alternativas.** (a) Mantener todas las clases en una carpeta plana; (b) organizar subcarpetas por recurso; (c) crear repositorios/controladores SQL genéricos.
**Decisión propuesta: (b).** Mantener las interfaces y clases por recurso y agruparlas en carpetas sin cambiar nombres públicos ni rutas.
**Consecuencias.** Es necesario corregir `require_once` y pruebas. El cambio es interno; no aporta por sí mismo mejoras de rendimiento, pero sí organización y mantenibilidad.
**Estado:** propuesto.

## D-V2-02 — Conservar las tres capas y el ensamblador único
**Alternativas.** (a) mezclar validación, SQL y HTTP por simplicidad; (b) conservar controlador → servicio → repositorio con interfaces.
**Decisión propuesta: (b).** Los controladores validan forma, los servicios aplican reglas y los repositorios acceden a MariaDB; el ensamblador construye las dependencias.
**Consecuencias.** Más archivos, pruebas aisladas y menores efectos cruzados.
**Estado:** propuesto.

## D-V2-03 — Manejo de claves foráneas y conflictos
**Alternativas.** (a) mostrar errores crudos de PDO o devolver siempre 500; (b) traducir una violación de integridad conocida a `ConflictoExcepcion` y 409.
**Decisión propuesta: (b).** Distinguir error de FK, duplicado y fallos inesperados; el frontend explica el conflicto sin mostrar SQL.
**Consecuencias.** Hay que verificar códigos SQLSTATE/códigos del motor y evitar clasificar fallos de conexión como conflicto. Debe confirmarse cómo validar la existencia lógica (`activo`) de las referencias.
**Estado:** propuesto.

## D-V2-04 — Identificadores de tablas puente
**Alternativas.** (a) inventar un `id` común para todas; (b) respetar exactamente la PK del script SQL.
**Decisión propuesta: (b).** No inventar columnas ni rutas individuales hasta conocer las PK reales.
**Consecuencias.** Algunas asociaciones podrían requerir llaves compuestas y rutas distintas, por especificar en `6_contracts.md`.
**Estado:** pendiente de verificar DDL.

## D-V2-05 — Desplegables FK desde la API
**Alternativas.** (a) mantener opciones de FK fijas en HTML; (b) consultar recursos relacionados por HTTP.
**Decisión propuesta: (b).** Formularios renderizan opciones recibidas de la API y se comprueban de nuevo en backend al guardar.
**Consecuencias.** Si faltan opciones o API falla, el frontend lo comunica; los formularios no convierten etiquetas visibles en IDs a mano.
**Estado:** propuesto.

## D-V2-06 — PHP + HTML + CSS sin alterar stack
**Alternativas.** (a) reescribir frontend con JS SPA o framework; (b) PHP server-side con HTML, CSS y Bootstrap local.
**Decisión propuesta: (b).** CSS en `publico/`, HTML en vistas PHP, cURL para HTTP. Sin CDN y sin acceso directo a BD.
**Consecuencias.** Respeta el curso y permite mejorar interfaz. JS, de requerirse, sería solo para interacción, no sustituto de la arquitectura.
**Estado:** propuesto.

## D-V2-07 — Borrado lógico con dependencias
**Alternativas.** (a) borrar físicamente para activar restricciones FK; (b) mantener borrado lógico y definir una política para padres con hijos activos.
**Decisión propuesta: (b), política pendiente.** El proyecto exige `activo = FALSE`, pero se debe decidir si bloquear retiros de padres relacionados o cómo manejar las asociaciones activas.
**Consecuencias.** No se puede inferir la política a partir del DDL: el borrado lógico no dispara un `ON DELETE RESTRICT`.
**Estado:** NECESITA ACLARACIÓN.

## D-V2-08 — No anticipar V3/V4
**Alternativas.** (a) agregar login, JWT, dashboard y publicación durante V2; (b) limitar V2 a diez FK y la reorganización que habilita mantenimiento.
**Decisión propuesta: (b).** Los cambios visuales de V2 son de orden/estilos básicos; no implementar aún las capacidades reservadas para V3/V4.
**Consecuencias.** Se mantiene un alcance verificable.
**Estado:** propuesto.

## Fuentes internas de decisión
- `modulo_investigacion.md`: distribución V1/V2/V3 y las diez entidades con FK.
- `0_METODOLOGIA.md`: SDD, compuertas, separación de repositorios, PHP + HTTP y cierre con tag.
- Documentos de la V1 de este proyecto: `2_spec.md` a `9_checklist.md` y constitución aplicable.
- **Pendiente:** `db/init.sql` y código actual de ambos repositorios para validar detalles y rutas de inclusiones.

## D-V2-09 — Inventario de PK/FK confirmado en la copia SQL

**Fuente:** `init(2).sql` compartido y el módulo `modulo_investigacion.md`. Se identifican **3 PK simples sin `AUTO_INCREMENT`** y **7 PK compuestas** de dos columnas. Se documentan literalmente en `5_data_model.md`. Verificar contra `db/init.sql` de la rama backend antes de cerrar la compuerta.

**Alternativa descartada:** introducir un campo `id` inventado en tablas puente, porque cambia el esquema dado.

## D-V2-10 — Errores de ortografía presentes en el esquema

La copia SQL contiene `docente.nacionalidaad` y `grupo_investigacion.universidsad`; son nombres de columnas funcionalmente válidos y ligados a los contratos. La documentación los mantiene sin corregir silenciosamente. Una eventual normalización exige acuerdo, migración y registro de cambio de esquema.

## D-V2-11 — Ajustar el plan a las rutas reales del equipo

Ya se reorganizaron los repositorios y servicios V1 con prefijos `Repositorios_` y `Servicios_`, y el frontend V1 ya separa `rutas/` y `vistas/` por recurso, con CSS local. El árbol anterior del borrador V2 era conceptual; no debe inducir una nueva refactorización innecesaria.

## D-V2-12 — Operaciones de asociaciones y borrado lógico

**[NECESITA ACLARACIÓN]** Cinco tablas puente solo tienen dos componentes de PK y `activo`; una actualización de esos dos componentes cambia la identidad y contradice la regla de no modificar PK. Definir si PUT/PATCH devolverán `405` o existirán operaciones expresas de reemplazo de asociación.

**[NECESITA ACLARACIÓN]** Re-crear una pareja retirada entra en conflicto de PK porque la fila permanece tras `DELETE` lógico. Evaluar reactivación explícita, 409 o la política aprobada por el equipo, sin asumirla.

## D-V2-13 — Cliente HTTP y exigencia de cURL

El frontend anterior del equipo usaba `file_get_contents()` con `stream_context_create()` para acceder a la API. Eso preserva la separación HTTP, pero `0_METODOLOGIA.md` §6 especifica **cURL**. Confirmar si en la rama actual sigue así y ajustar el cliente sin cambiar formularios/rutas antes de declarar cumplimiento literal de la metodología.
