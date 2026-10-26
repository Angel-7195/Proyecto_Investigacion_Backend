# 9. Checklist — V2: Investigación

> COMPUERTA 3 de requisitos y cierre. Este documento NO está aprobado solo por haber sido generado. Verificar en equipo y firmar. La revisión de requisitos debe ocurrir ANTES de programar; la de resultados, ANTES del tag.

> Avances técnicos documentados ≠ aprobación de compuertas. Las casillas se cierran tras revisar en equipo.

## A. Especificación completa (requisito previo)
- [ ] `2_spec.md` define diez recursos con FK y seis de V1 preservados.
- [ ] Las clarificaciones C1–C12 tienen decisión registrada, sin supuestos ocultos.
- [ ] Las claves, tipos, FK, columnas obligatorias y `activo` coinciden con el **`db/init.sql` real**; cotejo pendiente aunque ya se transcribió la copia `init(2).sql`.
- [ ] Se aprobaron rutas individuales con **dos componentes** para las siete tablas puente de PK compuesta.
- [ ] Se decidió cómo tratar padres retirados con hijos activos y el re-POST de una relación lógicamente retirada.
- [ ] Los campos y rutas exactas están presentes en `6_contracts.md`; no quedan marcadores «pendiente».
- [ ] El plan pasó el chequeo de constitución del equipo y respeta los secretos por variables de entorno.
- [ ] Cada requisito tiene criterio comprobable único, y el equipo lo aprobó.

## B. Refactorización de V1
- [ ] Los archivos de repositorios y servicios están agrupados por entidad.
- [ ] `ensamblador.php` sigue componiendo las dependencias concretas en un solo sitio.
- [ ] Los `require_once`, includes y referencias de tests se corrigieron.
- [ ] No cambiaron las rutas HTTP ni los sobres de respuesta de V1.
- [ ] Las seis entidades V1 pasan sus pruebas de capas/API/Frontend después del refactor.
- [ ] Las vistas V1 están agrupadas por recurso y la plantilla compartida sigue funcionando.
- [ ] CSS/Bootstrap locales responden correctamente sin conexión a CDN.

## C. API y reglas de V2
- [ ] Los diez CRUD V2 están implementados de extremo a extremo.
- [ ] Los repositorios usan PDO preparado; no hay SQL en controladores/servicios.
- [ ] Los servicios dependen de interfaces y desconocen HTTP.
- [ ] PK simples y compuestas coinciden con el SQL existente.
- [ ] Los valores FK inválidos no se guardan y su conflicto se representa según contrato.
- [ ] Una relación con padre retirado respeta la regla aprobada.
- [ ] Las asociaciones duplicadas no se crean.
- [ ] DELETE es lógico y una segunda operación devuelve 404.
- [ ] Listados y GET normales ocultan registros retirados.
- [ ] PUT incompleto devuelve 422; PATCH parcial conserva otros campos; PATCH `{}` devuelve 400.
- [ ] Se verificaron 201/200/204/400/404/405/409/422/500 donde correspondan.

## D. Frontend V2
- [ ] Cada una de las diez entidades tiene pantalla para sus operaciones.
- [ ] Las FK aparecen como selectores cargados desde GET de la API.
- [ ] Formularios preservan entradas ante errores y muestran mensajes comprensibles.
- [ ] Lista sin registros muestra «Todavía no hay registros», no fallo.
- [ ] La UI usa «retirar» para el borrado lógico.
- [ ] No hay PDO ni secretos de BD en el repositorio frontend; la comunicación cumple la exigencia de cURL o la excepción quedó aprobada.
- [ ] Con API apagada, el frontend mantiene su interfaz y no muestra datos.

## E. Cierre
- [ ] Los 16 CRUD pasan regresión y aceptación.
- [ ] `7_quickstart.md` contiene comandos realmente reproducidos en ambos repositorios.
- [ ] Documentos y código coinciden, sin cambios de esquema no registrados.
- [ ] Se verificó `.env` ignorado por Git y `.env.example` seguro.
- [ ] Los aportes llegaron por ramas y PR; los commits representan trabajo comprobable.
- [ ] Se completó la revisión humana del equipo y no quedan ambigüedades.
- [ ] Solo entonces se creó el tag `v2` desde main.

**Revisó:** __________________   **Fecha:** ______________

**Observaciones para V3:** ___________________________________________
