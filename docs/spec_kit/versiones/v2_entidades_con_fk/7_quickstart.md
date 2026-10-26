# 7. Quickstart — V2: Investigación

> GUÍA PROVISIONAL. Los comandos de arranque son generales; servicios y rutas deben comprobarse contra los repositorios reales; según las pruebas comunicadas, el frontend responde en `8110`, la API en `8111`, MariaDB en `13330` y phpMyAdmin en `8105`. **No afirmar que estas pruebas ya pasaron.**

## 1. Precondiciones

- V1 comprobada y, si correspondía, tag `v1` en `main`.
- Documentación V2 aprobada; `db/init.sql` cotejado con `5_data_model.md` y rutas cerradas en `6_contracts.md`.
- Backend y frontend están en **repositorios distintos**.
- Archivos `.env` fuera de Git y `.env.example` disponible, como exige la metodología del proyecto de aula.

## 2. Verificar configuración y levantar servicios

Desde la carpeta donde esté el `docker-compose.yml` **real**:

```powershell
docker compose config
docker compose up -d --build
docker compose ps
```

Si API y frontend tienen composiciones diferentes, repetir en la carpeta correcta de cada repositorio. No asumir nombres de contenedor ni puertos del repositorio guía.

## 3. Configurar URL de API para comprobar respuestas (ejemplo editable)

```powershell
# Reemplazar el puerto por el realmente publicado por Docker
$API = 'http://localhost:8111'  # URL comunicada por el equipo; verificar en docker compose ps
Invoke-RestMethod -Uri "$API/"
Invoke-WebRequest -Uri "$API/api/docente" -Method GET
```

La ruta `/api/docente` es **propuesta y todavía no implementada** hasta aprobar `6_contracts.md`.

## 4. Verificaciones por cada entidad

- GET listado y GET individual.
- POST válido, luego recuperación por PK real.
- PUT completo y PUT incompleto.
- PATCH de un campo y PATCH vacío `{}`.
- FK inexistente o lógicamente retirada (409 según contrato acordado).
- POST duplicado (409).
- DELETE lógico y segundo DELETE (404).
- Pantalla lista, alta, edición, retiro y selectores cargados desde la API.

**No ejecutar pruebas destructivas sobre semillas oficiales**: usar registros de prueba diseñados para la validación y retirar solo los creados para ella.

## 5. Regresión V1 y separación

1. Ejecutar todos los escenarios del `7_quickstart.md` de V1 y de su `9_checklist.md`.
2. Ejecutar las pruebas de servicios con repositorio falso y MariaDB apagada.
3. Detener únicamente la API, manteniendo frontend y BD encendidos: abrir la pantalla y confirmar navegación y mensaje de indisponibilidad, sin datos.
4. Confirmar que el CSS local se sirve con tipo `text/css` y que Bootstrap no requiere CDN.
5. Restaurar API y verificar que se recupera la navegación normal.

## 5.1. Verificaciones V1 reportadas por el equipo

- La reorganización del frontend por vistas y rutas fue probada manualmente con los **seis CRUD**: el equipo informó que funcionan.
- En Windows, `php -l` devolvió «No syntax errors detected» en los archivos PHP reorganizados.
- En el frontend, `Invoke-WebRequest http://localhost:8110/publico/css/estilos.css` devolvió `StatusCode 200`.
- Los cambios del frontend fueron subidos a `feature/angel-investigacion-v2` en GitHub, **sin evidencia aquí de PR fusionado ni tag V2**.

Estas comprobaciones **no sustituyen** la regresión documentada del backend, el cierre de V1 ni las pruebas nuevas V2.

## 6. Registro de ejecución — completar después de probar

| Prueba | Comando o ruta concreta | Resultado real | Responsable/fecha |
|---|---|---|---|
| Arranque | Por confirmar | Pendiente | |
| GET docente | Por confirmar | Pendiente | |
| POST semillero con FK inválida | Por confirmar | Pendiente | |
| Asociación puente duplicada | Por confirmar | Pendiente | |
| Regresión V1 | Por confirmar | Pendiente | |
| Frontend sin API | Por confirmar | Pendiente | |

Al cerrar V2, reemplazar este documento provisional por una guía ejecutable y reproducible, con puertos/nombres reales, datos de prueba y resultados observados.
