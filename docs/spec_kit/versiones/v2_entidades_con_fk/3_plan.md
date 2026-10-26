# 3. Plan técnico — V2: entidades con FK y reorganización

> BORRADOR. El plan se valida una vez contestadas las clarificaciones de `2_spec.md` y verificado el DDL. La V2 es acumulativa: NO se reescribe la V1 ni se cambian los contratos existentes.

## 1. Arquitectura conservada

`HTTP → api_investigacion/index.php → controlador → interfaz servicio → servicio → interfaz repositorio → repositorio MariaDB (PDO) → MariaDB`.

Los controladores validan cuerpos y traducen excepciones a HTTP; los servicios contienen reglas sin hablar de HTTP; los repositorios contienen SQL parametrizado sin gestionar HTML. El **único** ensamblador crea las implementaciones concretas de servicios y repositorios. `declare(strict_types=1);` en todo archivo PHP.

## 2. Organización REAL del BACKEND V1 y propuesta V2

Las seis entidades V1 **ya están agrupadas por recurso**. Se verificó el árbol a partir del paquete de backend reorganizado compartido por el equipo (la revisión final debe hacerse también en la rama vigente):

```text
api_investigacion/
├── index.php
├── controladores/
│   ├── ControladorAreaConocimiento.php
│   ├── ControladorUniversidad.php
│   ├── ... (resto V1)
│   └── ControladorDocente.php                 # V2: pendiente
├── modelos/
│   ├── AreaConocimiento.php
│   ├── ... (resto V1)
│   └── Docente.php                            # V2: pendiente
├── repositorios/
│   ├── Repositorios_AreaConocimiento/
│   │   ├── IRepositorioAreaConocimiento.php
│   │   └── RepositorioAreaConocimientoMariaDB.php
│   ├── Repositorios_Universidad/
│   ├── ... (resto de entidades V1)
│   ├── Repositorios_Docente/                 # V2: propuesto
│   └── ... (resto de entidades V2)
├── servicios/
│   ├── ensamblador.php
│   ├── Servicios_AreaConocimiento/
│   │   ├── IServicioAreaConocimiento.php
│   │   └── ServicioAreaConocimiento.php
│   ├── Servicios_Universidad/
│   ├── ... (resto de entidades V1)
│   ├── Servicios_Docente/                     # V2: propuesto
│   └── ... (resto de entidades V2)
├── excepciones/
└── pruebas/
```

**Regla:** para las diez entidades nuevas seguir la convención existente (`Repositorios_<Entidad>`, `Servicios_<Entidad>`), mantener `ensamblador.php` y no volver a reorganizar V1. No crear rutas SQL genéricas. Las carpetas V2 se incorporarán durante su implementación, no se reportan como existentes.

## 3. Organización REAL del FRONTEND V1 y ampliación V2

Se probó que `index.php` funciona como punto de entrada y que los archivos V1 están distribuidos por entidad:

```text
front_php/
├── index.php
├── cliente_api.php
├── rutas/
│   ├── compartidas/
│   │   └── acciones_catalogo.php
│   ├── rutas_area_conocimiento.php
│   ├── rutas_universidad.php
│   ├── ... (resto de las seis rutas V1)
│   ├── rutas_docente.php                    # V2: pendiente
│   └── ... (resto de las rutas V2)
├── vistas/
│   ├── compartidas/
│   │   ├── plantilla.php
│   │   ├── crud_catalogos.php
│   │   └── no_encontrada.php
│   ├── inicio.php
│   ├── area_conocimiento/ (lista.php, formulario.php)
│   ├── universidad/ (lista.php, formulario.php)
│   ├── ... (resto de las seis vistas V1)
│   ├── docente/                             # V2: pendiente
│   └── ... (resto de las vistas V2)
└── publico/
    ├── bootstrap.min.css
    ├── bootstrap.bundle.min.js
    └── css/
        └── estilos.css
```

El frontend usa vistas PHP para renderizar HTML, Bootstrap local y CSS local. Habla con la API **por HTTP, no por PDO**. La metodología del curso exige **cURL**; hay que comprobar si `cliente_api.php` del frontend sigue usando `file_get_contents()` y, si es así, planear la conversión a cURL sin modificar el contrato HTTP.

**Identidad de marca:** la constitución guía menciona `MANUAL_DE_MARCA.md` y `marca.css`, pero la existencia de esos archivos en el repositorio propio no fue confirmada. Revisar la constitución específica del equipo antes de cerrar la compuerta; la adopción final de imagen corporativa aparece en el alcance de V4.

## 4. Secuencia segura de refactorización

1. Guardar el estado de V1 en main mediante PR; **comprobar** las pruebas y el tag V1 si aún no existe.
2. Crear ramas de trabajo V2 en backend y frontend **a partir de la rama main actualizada**.
3. **Ya realizado según pruebas reportadas por el equipo:** carpetas por entidad de repositorios/servicios de las seis entidades V1; mantener la convención actual `Repositorios_`/`Servicios_` y revisar su rama efectiva.
4. Ejecutar sintaxis PHP, prueba de capas, endpoints API y pantalla V1 de esa entidad.
5. **Ya realizado:** las otras cinco entidades V1 se reestructuraron; registrar las pruebas reportadas en `8_tasks.md`.
6. **Ya realizado según comprobaciones del equipo:** vistas y rutas V1 del frontend agrupadas por entidad; CSS local `publico/css/estilos.css` servido con HTTP 200.
7. El equipo reporta funcionamiento de los seis CRUD después de los cambios. Queda pendiente prueba documentada de frontend **con API apagada** y confirmación de pruebas backend finales.
8. Agregar recursos V2 por dependencias: `docente`, `grupo_investigacion`, `semillero` y luego las asociaciones, **revisando** con el DDL el orden concreto más conveniente.
9. Probar casos de éxito, FK inválida, asociaciones duplicadas, retiros lógicos y selectores de interfaz.
10. Actualizar guía ejecutable y checklist. Integrar por PR y crear tag `v2` solo al aprobar.

**Ejemplo de rutas PHP según convención V1** (revisar las dependencias y archivos reales):

```php
// Desde api_investigacion/servicios/ensamblador.php
require_once __DIR__ . '/../repositorios/Repositorios_AreaConocimiento/IRepositorioAreaConocimiento.php';
require_once __DIR__ . '/Servicios_AreaConocimiento/ServicioAreaConocimiento.php';
```

## 5. Integridad referencial y operaciones

- En repositorios: PDO con prepared statements y traducción de violaciones de integridad/duplicados a `ConflictoExcepcion` cuando corresponda.
- En servicios: validar reglas de negocio y referencias **activas** de acuerdo con la clarificación C4/C5; una FK física existente podría pertenecer a un registro retirado.
- Para tablas puente: definir en `5_data_model.md` las claves antes de construir las rutas de identificación.
- El `activo` no se acepta de POST/PUT/PATCH; las consultas normales filtran activos y DELETE actualiza el estado sin eliminación física.

## 6. Pruebas y verificación

- Prueba de sintaxis PHP de cada archivo modificado.
- Pruebas de servicios con repositorios falsos y BD apagada.
- Pruebas HTTP por recurso, incluidos 204/400/404/409/422 y error de FK.
- Pruebas frontend de los desplegables alimentados de la API y conservación de datos del formulario ante errores.
- Prueba de que la pantalla sobrevive a API apagada, sin acceso alternativo a BD.
- Regresión V1 completa después de cada grupo de movimientos.

## 7. Chequeo de constitución — COMPUERTA 2

| Regla | Plan V2 | Resultado |
|---|---|---|
| PHP puro 8.3+, sin Composer/framework | Se mantienen PHP, PDO, cURL | Por revisar |
| Capas estrictas + interfaces | Una pareja de interfaces por recurso | Por revisar |
| Ensamblador único | `servicios/ensamblador.php` | Por revisar |
| SQL en repositorios y prepared statements | Sí | Por revisar |
| Front separado con HTTP, nunca PDO | Sí | Por revisar |
| Borrado lógico y filtro de activos | Sí; resolver dependencias | Por revisar |
| API específica por recurso | Sí, no ruta genérica | Por revisar |
| Todo en español + strict_types | Sí | Por revisar |
| Secretos solo por entorno | Conforme a la metodología del proyecto de aula | **Resolver conflicto con la constitución del ejemplo** |
| Esquema dado, cambios documentados | Sin cambios especulativos | Por revisar |
| Una versión entrega backend y pantalla | Diez CRUD completos | Por revisar |
| No anticipar versiones futuras | Sin JWT/dashboard | Por revisar |

**No marcar esta compuerta aprobada automáticamente.** Debe contrastarse con la constitución **real del equipo** y sus decisiones de V1.
