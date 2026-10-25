# Pruebas de integracion HTTP de Docente V2.
# Ejecutar desde la raiz del backend: powershell -NoProfile -ExecutionPolicy Bypass -File .\pruebas_docente_v2.ps1
# ATENCION: crea una linea de investigacion y un docente ficticios; ambos se retiran logicamente al terminar.
$ErrorActionPreference = 'Stop'
$baseUrl = 'http://localhost:8111'
$cedula = Get-Random -Minimum 1200000000 -Maximum 1800000000
$cedulaAlterna = $cedula + 1
$idLinea = $null
$docenteCreado = $false
$lineaCreada = $false
$totalPruebas = 0

function Enviar-API {
    param([string]$Metodo, [string]$Ruta, [hashtable]$Datos = $null)
    $parametros = @{
        Uri = "$baseUrl$Ruta"
        Method = $Metodo
        UseBasicParsing = $true
        ErrorAction = 'Stop'
    }
    if ($null -ne $Datos) {
        $json = $Datos | ConvertTo-Json -Depth 10 -Compress
        $parametros.Body = [System.Text.Encoding]::UTF8.GetBytes($json)
        $parametros.ContentType = 'application/json; charset=utf-8'
    }
    try {
        $respuesta = Invoke-WebRequest @parametros
        $codigo = [int] $respuesta.StatusCode
        $contenido = [string] $respuesta.Content
    } catch {
        if ($null -eq $_.Exception.Response) { throw "No hay conexion a la API: $($_.Exception.Message)" }
        $codigo = [int] $_.Exception.Response.StatusCode
        $contenido = [string] $_.ErrorDetails.Message
    }
    $datosDecodificados = $null
    if ($contenido.Trim().StartsWith('{') -or $contenido.Trim().StartsWith('[')) {
        try { $datosDecodificados = $contenido | ConvertFrom-Json } catch {}
    }
    return [pscustomobject]@{ Codigo = $codigo; Datos = $datosDecodificados; Contenido = $contenido }
}

function Comprobar {
    param([string]$Nombre, [string]$Metodo, [string]$Ruta, [int]$Esperado, [hashtable]$Datos = $null)
    $resultado = Enviar-API -Metodo $Metodo -Ruta $Ruta -Datos $Datos
    if ($resultado.Codigo -ne $Esperado) {
        Write-Host "ERROR ($($resultado.Codigo), esperado $Esperado): $Nombre" -ForegroundColor Red
        Write-Host $resultado.Contenido
        throw "Fallo: $Nombre"
    }
    $script:totalPruebas++
    Write-Host "APROBADO ($Esperado): $Nombre" -ForegroundColor Green
    return $resultado
}

function Verificar {
    param([bool]$Condicion, [string]$Descripcion)
    if (-not $Condicion) { throw "Los datos no coinciden: $Descripcion" }
    Write-Host "  Datos correctos: $Descripcion" -ForegroundColor DarkGreen
}

Write-Host '=== PRUEBAS DOCENTE V2 ==='
Write-Host "Cedula temporal ficticia: $cedula"

try {
    $inicio = Comprobar '1. Diagnostico de API' 'GET' '/' 200
    Verificar ($inicio.Datos.recursos -contains 'docente') 'Docente anunciado en API'

    $lineaTemporal = Comprobar '2. Crear linea temporal' 'POST' '/api/linea_investigacion' 201 @{
        nombre = 'Linea prueba V2'
        descripcion = 'Linea temporal para pruebas de docente V2'
    }
    $idLinea = [int] $lineaTemporal.Datos.datos.id
    Verificar ($idLinea -gt 0) 'ID autoincremental de linea'
    $lineaCreada = $true

    $null = Comprobar '3. Obtener linea temporal' 'GET' "/api/linea_investigacion/$idLinea" 200
    $null = Comprobar '4. Docente inexistente' 'GET' "/api/docente/$cedula" 404

    $nuevo = @{
        cedula = $cedula
        nombres = 'Docente Prueba'
        apellidos = 'Integracion V2'
        genero = 'Otro'
        cargo = 'Investigador'
        fecha_nacimiento = '1990-04-10'
        correo = "docente.$cedula@example.org"
        telefono = '3001234567'
        url_cvlac = 'https://example.org/cvlac'
        fecha_actualizacion = '2026-10-24'
        escalafon = 'Asistente'
        perfil = 'Perfil ficticio para pruebas automatizadas.'
        cat_minciencia = $null
        conv_minciencia = '2026'
        nacionalidaad = 'Colombiana'
        linea_investigacion_principal = $idLinea
    }

    $creado = Comprobar '5. Crear docente con FK valida' 'POST' '/api/docente' 201 $nuevo
    Verificar ($creado.Datos.datos.cedula -eq $cedula -and $creado.Datos.datos.linea_investigacion_principal -eq $idLinea) 'Cedula y FK en POST'
    $docenteCreado = $true

    $obtenido = Comprobar '6. GET docente creado' 'GET' "/api/docente/$cedula" 200
    Verificar ($obtenido.Datos.nombres -eq 'Docente Prueba') 'Nombre guardado'

    $listado = Comprobar '7. Listar docentes activos' 'GET' '/api/docente' 200
    Verificar (@($listado.Datos.datos | Where-Object { $_.cedula -eq $cedula }).Count -eq 1) 'Docente presente en listado'

    $null = Comprobar '8. Cedula duplicada' 'POST' '/api/docente' 409 $nuevo

    $fkIncorrecta = $nuevo.Clone()
    $fkIncorrecta.cedula = $cedulaAlterna
    $fkIncorrecta.linea_investigacion_principal = 1999999999
    $null = Comprobar '9. Linea inexistente' 'POST' '/api/docente' 409 $fkIncorrecta

    $nombreVacio = $nuevo.Clone()
    $nombreVacio.cedula = $cedulaAlterna
    $nombreVacio.nombres = ''
    $null = Comprobar '10. Nombres vacios' 'POST' '/api/docente' 422 $nombreVacio

    $correoInvalido = $nuevo.Clone()
    $correoInvalido.cedula = $cedulaAlterna
    $correoInvalido.correo = 'no-es-email'
    $null = Comprobar '11. Email invalido' 'POST' '/api/docente' 422 $correoInvalido

    $fechaInvalida = $nuevo.Clone()
    $fechaInvalida.cedula = $cedulaAlterna
    $fechaInvalida.fecha_nacimiento = '2026-02-30'
    $null = Comprobar '12. Fecha de nacimiento invalida' 'POST' '/api/docente' 422 $fechaInvalida

    $sinCedula = $nuevo.Clone()
    $sinCedula.Remove('cedula')
    $null = Comprobar '13. POST sin cedula' 'POST' '/api/docente' 422 $sinCedula

    $put = $nuevo.Clone()
    $put.Remove('cedula')
    $put.nombres = 'Docente Editado'
    $put.cargo = 'Profesor'
    $put.cat_minciencia = 'Junior'
    $null = Comprobar '14. PUT reemplazo completo' 'PUT' "/api/docente/$cedula" 200 $put

    $despuesPut = Comprobar '15. Consultar PUT' 'GET' "/api/docente/$cedula" 200
    Verificar ($despuesPut.Datos.nombres -eq 'Docente Editado' -and $despuesPut.Datos.cat_minciencia -eq 'Junior') 'Cambios PUT guardados'

    $null = Comprobar '16. PATCH de campos parciales' 'PATCH' "/api/docente/$cedula" 200 @{ cargo = 'Coordinador'; escalafon = 'Titular' }
    $despuesPatch = Comprobar '17. Verificar PATCH' 'GET' "/api/docente/$cedula" 200
    Verificar ($despuesPatch.Datos.cargo -eq 'Coordinador' -and $despuesPatch.Datos.nombres -eq 'Docente Editado') 'PATCH conserva otros campos'

    $null = Comprobar '18. Quitar linea (NULL)' 'PATCH' "/api/docente/$cedula" 200 @{ linea_investigacion_principal = $null }
    $sinLinea = Comprobar '19. Verificar FK nula' 'GET' "/api/docente/$cedula" 200
    Verificar ($null -eq $sinLinea.Datos.linea_investigacion_principal) 'FK nula aceptada'

    $null = Comprobar '20. Restaurar FK activa' 'PATCH' "/api/docente/$cedula" 200 @{ linea_investigacion_principal = $idLinea }
    $conLinea = Comprobar '21. Verificar FK restaurada' 'GET' "/api/docente/$cedula" 200
    Verificar ($conLinea.Datos.linea_investigacion_principal -eq $idLinea) 'Relacion con linea activa'

    $null = Comprobar '22. PATCH con FK inexistente' 'PATCH' "/api/docente/$cedula" 409 @{ linea_investigacion_principal = 1999999999 }
    $null = Comprobar '23. Campo activo prohibido' 'PATCH' "/api/docente/$cedula" 422 @{ activo = 0 }
    $null = Comprobar '24. Cedula no editable' 'PATCH' "/api/docente/$cedula" 422 @{ cedula = $cedulaAlterna }
    $null = Comprobar '25. Fecha de actualizacion invalida' 'PATCH' "/api/docente/$cedula" 422 @{ fecha_actualizacion = '2026-02-30' }

    $null = Comprobar '26. DELETE logico' 'DELETE' "/api/docente/$cedula" 200
    $docenteCreado = $false
    $null = Comprobar '27. Docente retirado no aparece' 'GET' "/api/docente/$cedula" 404
    $null = Comprobar '28. DELETE repetido' 'DELETE' "/api/docente/$cedula" 404

    $null = Comprobar '29. Retirar linea temporal' 'DELETE' "/api/linea_investigacion/$idLinea" 200
    $lineaCreada = $false
    $null = Comprobar '30. Linea retirada no aparece' 'GET' "/api/linea_investigacion/$idLinea" 404

    Write-Host "`nTodas las $totalPruebas comprobaciones HTTP aprobaron." -ForegroundColor Green
    Write-Host "Las filas ficticias siguen en MariaDB con activo=0 (cedula=$cedula, linea=$idLinea)."
} finally {
    # Limpieza de seguridad: si falla una prueba luego de crear datos, se intenta retiro logico.
    if ($docenteCreado) {
        try { $null = Enviar-API 'DELETE' "/api/docente/$cedula"; Write-Host "Limpieza: se intento retirar docente $cedula" } catch { Write-Warning "No se pudo retirar el docente temporal $cedula" }
    }
    if ($lineaCreada -and $null -ne $idLinea) {
        try { $null = Enviar-API 'DELETE' "/api/linea_investigacion/$idLinea"; Write-Host "Limpieza: se intento retirar linea $idLinea" } catch { Write-Warning "No se pudo retirar la linea temporal $idLinea" }
    }
}
