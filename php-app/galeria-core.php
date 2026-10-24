<?php
declare(strict_types=1);
function gh($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function gallery_db(): PDO {
    $c = require __DIR__.'/config.php';
    return new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], $c['port'], $c['database']), $c['user'], $c['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
}
function gallery_admin(PDO $db): void {
    if (empty($_SESSION['user']['id'])) { header('Location: login.php'); exit; }
    $q=$db->prepare('SELECT rol FROM usuarios WHERE id=?'); $q->execute([$_SESSION['user']['id']]);
    if ($q->fetchColumn() !== 'superadmin') { http_response_code(403); exit('Solo el administrador de Galería Cusco puede gestionar este módulo.'); }
}
function gallery_text(array $data, string $key, int $max, bool $required=true): string {
    $v=trim((string)($data[$key]??''));
    if (($required && $v==='') || mb_strlen($v)>$max) throw new InvalidArgumentException('Revisa el campo '.str_replace('_',' ',$key).'.');
    return $v;
}
function gallery_store_data(array $data): array {
    $values=[];
    foreach (['nombre'=>150,'eslogan'=>255,'telefono_whatsapp'=>20,'direccion'=>255,'ciudad'=>100] as $key=>$max) $values[$key]=gallery_text($data,$key,$max,$key!=='eslogan');
    if (!preg_match('/^\+?[0-9 ()-]{7,20}$/D', $values['telefono_whatsapp'])) throw new InvalidArgumentException('Escribe un WhatsApp válido con código de país.');
    $values['modalidad']=(string)($data['modalidad']??'');
    if (!in_array($values['modalidad'],['alquilada_fija','alquilada_comision','administrada'],true)) throw new InvalidArgumentException('Selecciona una modalidad.');
    $commission=(string)($data['porcentaje_comision']??'');
    if (!preg_match('/^\d{1,3}(\.\d{1,2})?$/D',$commission) || (float)$commission>100) throw new InvalidArgumentException('La comisión debe estar entre 0 y 100.');
    $values['porcentaje_comision']=$commission;
    $values['galeria_categoria_id']=filter_var($data['galeria_categoria_id']??0,FILTER_VALIDATE_INT);
    if (!$values['galeria_categoria_id'] || $values['galeria_categoria_id']<1) throw new InvalidArgumentException('Selecciona una categoría.');
    $values['estado']=(string)($data['estado']??'activa');
    if (!in_array($values['estado'],['activa','pendiente','suspendida'],true)) throw new InvalidArgumentException('Selecciona un estado válido.');
    return $values;
}
function gallery_coordinates(array $data): array {
    $lat=trim((string)($data['latitud']??'')); $lon=trim((string)($data['longitud']??''));
    if ($lat==='' && $lon==='') return [null,null];
    if (!is_numeric($lat)||!is_numeric($lon)||abs((float)$lat)>90||abs((float)$lon)>180) throw new InvalidArgumentException('Completa ambas coordenadas GPS: latitud entre -90 y 90 y longitud entre -180 y 180.');
    return [number_format((float)$lat,7,'.',''),number_format((float)$lon,7,'.','')];
}
