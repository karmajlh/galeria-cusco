<?php
declare(strict_types=1);
require __DIR__.'/../php-app/galeria-core.php';
$checks=0;
function check(bool $ok, string $message): void { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; }
function rejects(callable $fn, string $message): void { try { $fn(); } catch (InvalidArgumentException $e) { check(true,$message); return; } check(false,$message); }
$input=['nombre'=>'Tienda Cusco','eslogan'=>'Arte local','telefono_whatsapp'=>'+51987654321','direccion'=>'Calle del Sol 100','ciudad'=>'Cusco','modalidad'=>'alquilada_comision','porcentaje_comision'=>'12.50','galeria_categoria_id'=>'1','estado'=>'activa'];
$v=gallery_store_data($input);
check(count($v)===9 && $v['galeria_categoria_id']===1,'Campos de tienda');
check(array_keys($v)===['nombre','eslogan','telefono_whatsapp','direccion','ciudad','modalidad','porcentaje_comision','galeria_categoria_id','estado'],'Orden de parámetros SQL');
foreach (['porcentaje_comision'=>['abc','-1','101','1.234'],'estado'=>['eliminada'],'modalidad'=>['desconocida'],'galeria_categoria_id'=>['0','-5','abc'],'nombre'=>['',str_repeat('a',151)],'telefono_whatsapp'=>['<script>']] as $field=>$cases) foreach ($cases as $case) rejects(fn()=>gallery_store_data(array_replace($input,[$field=>$case])),'Rechazar '.$field);
check(gallery_coordinates([])===[null,null],'GPS opcional');
check(gallery_coordinates(['latitud'=>'-13.5167','longitud'=>'-71.9781'])===['-13.5167000','-71.9781000'],'GPS Cusco');
foreach ([['latitud'=>'91','longitud'=>'0'],['latitud'=>'0','longitud'=>'181'],['latitud'=>'0'],['latitud'=>'NaN','longitud'=>'2']] as $case) rejects(fn()=>gallery_coordinates($case),'GPS inválido');
check(gh('<script>')==='&lt;script&gt;','Escape HTML');
echo "$checks validaciones aprobadas.\n";
