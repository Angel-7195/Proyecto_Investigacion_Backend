<?php

declare(strict_types=1);

require_once __DIR__ . '/../servicios/ServicioAreaConocimiento.php';
require_once __DIR__ . '/../servicios/ServicioObjetivoDesarrolloSostenible.php';
require_once __DIR__ . '/../servicios/ServicioAreaAplicacion.php';
require_once __DIR__ . '/../excepciones/ConflictoExcepcion.php';

$fallos = 0;
function probarR(string $descripcion, callable $f): void
{
    global $fallos;
    try {
        $f();
        echo "[OK] {$descripcion}" . PHP_EOL;
    } catch (Throwable $e) {
        $fallos++;
        echo "[ERROR] {$descripcion}: {$e->getMessage()}" . PHP_EOL;
    }
}
function verificarR(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}
function esperarR(string $excepcion, callable $f): void
{
    try {
        $f();
    } catch (Throwable $e) {
        if ($e instanceof $excepcion) {
            return;
        }
        throw $e;
    }
    throw new RuntimeException("No se lanzó {$excepcion}.");
}

class FalsoAreaConocimiento implements IRepositorioAreaConocimiento
{
    private array $registros = [];
    public function listar(): array
    {
        return array_values(array_map(
            static fn(array $r): AreaConocimiento => $r['modelo'],
            array_filter($this->registros, static fn(array $r): bool => $r['activo'])
        ));
    }
    public function obtenerPorId(string $id): ?AreaConocimiento
    {
        return ($this->registros[$id]['activo'] ?? false)
            ? $this->registros[$id]['modelo']
            : null;
    }
    public function crear(AreaConocimiento $registro): int
    {
        $id = $registro->getId();
        if (array_key_exists($id, $this->registros)) {
            throw new ConflictoExcepcion('Llave ya ocupada.');
        }
        $this->registros[$id] = ['modelo' => $registro, 'activo' => true];
        return 1;
    }
    public function reemplazar(string $id, array $datos): int
    {
        $registro = $this->obtenerPorId($id);
        if ($registro === null) {
            return 0;
        }
        $registro->setGranArea($datos['gran_area']);
        $registro->setArea($datos['area']);
        $registro->setDisciplina($datos['disciplina']);
        return 1;
    }
    public function actualizar(string $id, array $datos): int
    {
        $registro = $this->obtenerPorId($id);
        if ($registro === null) {
            return 0;
        }
        if (array_key_exists('gran_area', $datos)) {
            $registro->setGranArea($datos['gran_area']);
        }
        if (array_key_exists('area', $datos)) {
            $registro->setArea($datos['area']);
        }
        if (array_key_exists('disciplina', $datos)) {
            $registro->setDisciplina($datos['disciplina']);
        }
        return 1;
    }
    public function retirar(string $id): int
    {
        if ($this->obtenerPorId($id) === null) {
            return 0;
        }
        $this->registros[$id]['activo'] = false;
        return 1;
    }
}

class FalsoObjetivoDesarrolloSostenible implements IRepositorioObjetivoDesarrolloSostenible
{
    private array $registros = [];
    public function listar(): array
    {
        return array_values(array_map(
            static fn(array $r): ObjetivoDesarrolloSostenible => $r['modelo'],
            array_filter($this->registros, static fn(array $r): bool => $r['activo'])
        ));
    }
    public function obtenerPorId(int $id): ?ObjetivoDesarrolloSostenible
    {
        return ($this->registros[$id]['activo'] ?? false)
            ? $this->registros[$id]['modelo']
            : null;
    }
    public function crear(ObjetivoDesarrolloSostenible $registro): int
    {
        $id = $registro->getId();
        if (array_key_exists($id, $this->registros)) {
            throw new ConflictoExcepcion('Llave ya ocupada.');
        }
        $this->registros[$id] = ['modelo' => $registro, 'activo' => true];
        return 1;
    }
    public function reemplazar(int $id, array $datos): int
    {
        $registro = $this->obtenerPorId($id);
        if ($registro === null) {
            return 0;
        }
        $registro->setNombre($datos['nombre']);
        $registro->setCategoria($datos['categoria']);
        return 1;
    }
    public function actualizar(int $id, array $datos): int
    {
        $registro = $this->obtenerPorId($id);
        if ($registro === null) {
            return 0;
        }
        if (array_key_exists('nombre', $datos)) {
            $registro->setNombre($datos['nombre']);
        }
        if (array_key_exists('categoria', $datos)) {
            $registro->setCategoria($datos['categoria']);
        }
        return 1;
    }
    public function retirar(int $id): int
    {
        if ($this->obtenerPorId($id) === null) {
            return 0;
        }
        $this->registros[$id]['activo'] = false;
        return 1;
    }
}

class FalsoAreaAplicacion implements IRepositorioAreaAplicacion
{
    private array $registros = [];
    public function listar(): array
    {
        return array_values(array_map(
            static fn(array $r): AreaAplicacion => $r['modelo'],
            array_filter($this->registros, static fn(array $r): bool => $r['activo'])
        ));
    }
    public function obtenerPorId(int $id): ?AreaAplicacion
    {
        return ($this->registros[$id]['activo'] ?? false)
            ? $this->registros[$id]['modelo']
            : null;
    }
    public function crear(AreaAplicacion $registro): int
    {
        $id = $registro->getId();
        if (array_key_exists($id, $this->registros)) {
            throw new ConflictoExcepcion('Llave ya ocupada.');
        }
        $this->registros[$id] = ['modelo' => $registro, 'activo' => true];
        return 1;
    }
    public function reemplazar(int $id, array $datos): int
    {
        $registro = $this->obtenerPorId($id);
        if ($registro === null) {
            return 0;
        }
        $registro->setNombre($datos['nombre']);
        return 1;
    }
    public function actualizar(int $id, array $datos): int
    {
        $registro = $this->obtenerPorId($id);
        if ($registro === null) {
            return 0;
        }
        if (array_key_exists('nombre', $datos)) {
            $registro->setNombre($datos['nombre']);
        }
        return 1;
    }
    public function retirar(int $id): int
    {
        if ($this->obtenerPorId($id) === null) {
            return 0;
        }
        $this->registros[$id]['activo'] = false;
        return 1;
    }
}
require_once __DIR__ . '/../controladores/ControladorAreaConocimiento.php';
require_once __DIR__ . '/../controladores/ControladorObjetivoDesarrolloSostenible.php';
require_once __DIR__ . '/../controladores/ControladorAreaAplicacion.php';

$spec = [
 ['area_conocimiento','FalsoAreaConocimiento','ServicioAreaConocimiento','ControladorAreaConocimiento','ZZ9001',['id'=>'ZZ9001','gran_area'=>'Ciencia','area'=>'Area','disciplina'=>'Disciplina'],['gran_area'=>'Ciencia 2','area'=>'Area 2','disciplina'=>'Disciplina 2']],
 ['objetivo_desarrollo_sostenible','FalsoObjetivoDesarrolloSostenible','ServicioObjetivoDesarrolloSostenible','ControladorObjetivoDesarrolloSostenible',900001,['id'=>900001,'nombre'=>'Objetivo','categoria'=>'Social'],['nombre'=>'Objetivo 2','categoria'=>'Ambiental']],
 ['area_aplicacion','FalsoAreaAplicacion','ServicioAreaAplicacion','ControladorAreaAplicacion',900001,['id'=>900001,'nombre'=>'Area temporal'],['nombre'=>'Area editada']],
];
$errors=[];$checks=0;
function checkResponse(object $ctrl, string $method, array $args,int $expected, string $description): void {
    global $checks,$errors;
    http_response_code(200);
    ob_start();$ctrl->{$method}(...$args);$body=ob_get_clean();$code=http_response_code();
    $checks++;
    if ($code!==$expected){$errors[]="{$description}: esperado {$expected}, recibí {$code}: {$body}";}
    if($expected===204 && $body!=='') $errors[]="{$description}: 204 incluyó contenido";
    if($expected!==204 && !is_array(json_decode($body,true))) $errors[]="{$description}: no devolvió JSON válido";
}
foreach($spec as [$name,$repo,$service,$ctrlClass,$id,$create,$replace]) {
    $ctrl=new $ctrlClass(new $service(new $repo()));
    checkResponse($ctrl,'listar',[],204,"$name lista vacía");
    checkResponse($ctrl,'crear',[[]],422,"$name POST vacío");
    checkResponse($ctrl,'crear',[array_merge($create,['activo'=>false])],422,"$name POST activo prohibido");
    checkResponse($ctrl,'crear',[$create],201,"$name POST correcto");
    checkResponse($ctrl,'crear',[$create],409,"$name POST duplicado");
    checkResponse($ctrl,'listar',[],200,"$name lista activa");
    checkResponse($ctrl,'obtener',[$id],200,"$name GET por id");
    checkResponse($ctrl,'reemplazar',[$id,[]],422,"$name PUT vacío");
    checkResponse($ctrl,'reemplazar',[$id,array_merge($replace,['id'=>$id])],422,"$name PUT id prohibido");
    checkResponse($ctrl,'reemplazar',[$id,$replace],200,"$name PUT correcto");
    checkResponse($ctrl,'actualizar',[$id,[]],400,"$name PATCH vacío");
    checkResponse($ctrl,'actualizar',[$id,['activo'=>false]],422,"$name PATCH activo prohibido");
    checkResponse($ctrl,'actualizar',[$id,[array_key_first($replace)=>'CAMBIO']],200,"$name PATCH correcto");
    checkResponse($ctrl,'retirar',[$id],200,"$name DELETE correcto");
    checkResponse($ctrl,'retirar',[$id],404,"$name DELETE repetido");
    checkResponse($ctrl,'obtener',[$id],404,"$name GET retirado");
    checkResponse($ctrl,'listar',[],204,"$name lista tras retirar");
}
if ($errors) {foreach($errors as $e) echo "[ERROR] $e\n"; exit(1);}echo "[OK] {$checks} comprobaciones de controladores HTTP (simuladas) pasaron.\n";
