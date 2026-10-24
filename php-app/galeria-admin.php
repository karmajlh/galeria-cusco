<?php
declare(strict_types=1);
session_start();
require __DIR__.'/galeria-core.php';
try { $db=gallery_db(); gallery_admin($db); } catch (Throwable $e) { http_response_code(503); exit('No se pudo conectar con la galería.'); }
$_SESSION['galeria_csrf']??=bin2hex(random_bytes(32));
$token=$_SESSION['galeria_csrf']; $error=''; $submitted=[];
$ready=(bool)$db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='galeria_ubicaciones'")->fetchColumn();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        if (!hash_equals($token,(string)($_POST['csrf']??''))) { http_response_code(403); throw new InvalidArgumentException('La sesión del formulario venció. Recarga la página.'); }
        $action=(string)($_POST['action']??'');
        if ($action==='setup') {
            $db->exec('CREATE TABLE IF NOT EXISTS galeria_ubicaciones (tienda_id INT NOT NULL PRIMARY KEY, latitud DECIMAL(10,7) NULL, longitud DECIMAL(10,7) NULL, FOREIGN KEY (tienda_id) REFERENCES tiendas(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        } elseif ($action==='category') {
            $name=gallery_text($_POST,'nombre',120); $description=gallery_text($_POST,'descripcion',2000,false);
            $id=max(0,(int)($_POST['id']??0)); $order=filter_var($_POST['orden']??0,FILTER_VALIDATE_INT);
            $state=(string)($_POST['estado']??'activa');
            if ($order===false || $order<0 || !in_array($state,['activa','inactiva'],true)) throw new InvalidArgumentException('Revisa el orden y el estado de la categoría.');
            $q=$db->prepare('SELECT id FROM galeria_categorias WHERE nombre=? AND id<>?'); $q->execute([$name,$id]);
            if ($q->fetch()) throw new InvalidArgumentException('Ya existe una categoría con ese nombre.');
            if ($id) { $q=$db->prepare('UPDATE galeria_categorias SET nombre=?,descripcion=?,orden=?,estado=? WHERE id=?'); $q->execute([$name,$description,$order,$state,$id]); }
            else { $q=$db->prepare('INSERT INTO galeria_categorias(nombre,slug,descripcion,orden,estado) VALUES(?,?,?,?,?)'); $q->execute([$name,'categoria-'.bin2hex(random_bytes(8)),$description,$order,$state]); }
        } elseif ($action==='store') {
            if (!$ready) throw new InvalidArgumentException('Activa primero el módulo de ubicación.');
            $submitted=$_POST; unset($submitted['admin_password']);
            $values=gallery_store_data($_POST); [$lat,$lon]=gallery_coordinates($_POST); $id=max(0,(int)($_POST['id']??0));
            $q=$db->prepare('SELECT id FROM galeria_categorias WHERE id=?'); $q->execute([$values['galeria_categoria_id']]);
            if (!$q->fetch()) throw new InvalidArgumentException('La categoría seleccionada no existe.');
            $addAdmin=!$id || !empty($_POST['add_admin']);
            if ($addAdmin) {
                $adminName=gallery_text($_POST,'admin_nombre',100); $email=gallery_text($_POST,'admin_email',150);
                $password=(string)($_POST['admin_password']??'');
                if (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($password)<12 || strlen($password)>72) throw new InvalidArgumentException('El administrador necesita un correo válido y una contraseña de 12 a 72 caracteres.');
                $hash=password_hash($password,PASSWORD_DEFAULT);
            }
            $db->beginTransaction();
            if ($id) {
                $q=$db->prepare('SELECT id FROM tiendas WHERE id=? FOR UPDATE'); $q->execute([$id]);
                if (!$q->fetch()) throw new InvalidArgumentException('La tienda no existe.');
                $q=$db->prepare('UPDATE tiendas SET nombre=?,eslogan=?,telefono_whatsapp=?,direccion=?,ciudad=?,modalidad=?,porcentaje_comision=?,galeria_categoria_id=?,estado=? WHERE id=?');
                $q->execute([...array_values($values),$id]);
            } else {
                $q=$db->prepare('INSERT INTO tiendas(nombre,eslogan,telefono_whatsapp,direccion,ciudad,modalidad,porcentaje_comision,galeria_categoria_id,estado,slug) VALUES(?,?,?,?,?,?,?,?,?,?)');
                $q->execute([...array_values($values),'tienda-'.bin2hex(random_bytes(8))]); $id=(int)$db->lastInsertId();
            }
            $q=$db->prepare('INSERT INTO galeria_ubicaciones(tienda_id,latitud,longitud) VALUES(?,?,?) ON DUPLICATE KEY UPDATE latitud=VALUES(latitud),longitud=VALUES(longitud)'); $q->execute([$id,$lat,$lon]);
            if ($addAdmin) {
                $q=$db->prepare("INSERT INTO usuarios(tienda_id,nombre,email,password_hash,rol) VALUES(?,?,?,?,'admin_tienda')"); $q->execute([$id,$adminName,$email,$hash]);
            }
            $db->commit();
        } else throw new InvalidArgumentException('Acción no válida.');
        $_SESSION['galeria_notice']='Cambios guardados correctamente.';
        header('Location: galeria-admin.php'); exit;
    } catch (InvalidArgumentException $e) { if ($db->inTransaction()) $db->rollBack(); $error=$e->getMessage(); }
    catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); $error=$e instanceof PDOException && ($e->errorInfo[1]??0)===1062 ? 'Ese correo ya está registrado. La tienda y sus datos no se modificaron.' : 'No se pudo guardar. Intenta nuevamente.'; error_log('Galeria admin: '.get_class($e).' '.$e->getCode()); }
}
$cats=$db->query('SELECT * FROM galeria_categorias ORDER BY orden,nombre')->fetchAll();
$stores=$db->query('SELECT t.*,c.nombre AS categoria FROM tiendas t LEFT JOIN galeria_categorias c ON c.id=t.galeria_categoria_id ORDER BY t.nombre')->fetchAll();
$admins=$db->query("SELECT tienda_id,nombre,email FROM usuarios WHERE rol='admin_tienda' ORDER BY nombre")->fetchAll();
$edit=null; $editId=(int)($_GET['editar']??($_POST['id']??0));
foreach ($stores as $s) if ((int)$s['id']===$editId) $edit=$s;
if ($edit && $ready) { $q=$db->prepare('SELECT latitud,longitud FROM galeria_ubicaciones WHERE tienda_id=?'); $q->execute([$editId]); $edit=array_merge($edit,$q->fetch()?:[]); }
$form=$submitted?:($edit?:['ciudad'=>'Cusco','porcentaje_comision'=>'0','estado'=>'activa','modalidad'=>'alquilada_fija']);
$catEdit=null; foreach ($cats as $cat) if ((int)$cat['id']===(int)($_GET['categoria']??0)) $catEdit=$cat;
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Administración · Galería Cusco</title><link rel="stylesheet" href="galeria.css"></head><body>
<header><nav><a class="brand" href="index.php">GALERÍA CUSCO</a><div><a href="panel.php">Panel</a> · <a href="logout.php">Cerrar sesión</a></div></nav></header><main>
<h1>Administración de la galería</h1><p class="muted">Organiza las categorías, registra las tiendas y crea sus administradores.</p>
<?php if ($error): ?><p class="notice error" role="alert"><?=gh($error)?></p><?php endif ?>
<?php if (isset($_SESSION['galeria_notice'])): ?><p class="notice" role="status"><?=gh($_SESSION['galeria_notice'])?></p><?php unset($_SESSION['galeria_notice']); endif ?>
<?php if (!$ready): ?><section class="card"><h2>Activar ubicación GPS</h2><p>Prepara el almacenamiento de coordenadas conservando las tiendas existentes. Se realiza una sola vez.</p><form method="post"><input type="hidden" name="csrf" value="<?=gh($token)?>"><input type="hidden" name="action" value="setup"><button>Activar módulo</button></form></section><?php endif ?>
<section class="card" id="categorias"><h2><?= $catEdit?'Editar categoría':'Categorías de tiendas' ?></h2><form method="post"><input type="hidden" name="csrf" value="<?=gh($token)?>"><input type="hidden" name="action" value="category"><input type="hidden" name="id" value="<?=gh($catEdit['id']??0)?>"><div class="fields">
<label>Nombre<input name="nombre" maxlength="120" required value="<?=gh($catEdit['nombre']??'')?>"></label><label>Descripción<input name="descripcion" maxlength="2000" value="<?=gh($catEdit['descripcion']??'')?>"></label><label>Orden<input type="number" name="orden" min="0" required value="<?=gh($catEdit['orden']??0)?>"></label><label>Estado<select name="estado"><?php foreach (['activa'=>'Visible','inactiva'=>'Oculta'] as $v=>$label): ?><option value="<?=$v?>" <?=($catEdit['estado']??'activa')===$v?'selected':''?>><?=$label?></option><?php endforeach ?></select></label></div><button><?= $catEdit?'Guardar categoría':'Crear categoría' ?></button></form><p>Una categoría oculta también oculta sus tiendas de la portada.</p><div class="categories"><?php foreach ($cats as $c): ?><a href="?categoria=<?=$c['id']?>#categorias"><?=gh($c['nombre'])?> · <?=gh($c['estado'])?></a><?php endforeach ?></div></section>
<?php if ($ready): ?><section class="card" id="formulario"><h2><?=$edit?'Editar tienda':'Registrar tienda y administrador'?></h2><form method="post"><input type="hidden" name="csrf" value="<?=gh($token)?>"><input type="hidden" name="action" value="store"><input type="hidden" name="id" value="<?=gh($edit['id']??0)?>"><div class="fields">
<?php foreach (['nombre'=>['Nombre',150],'eslogan'=>['Slogan',255],'telefono_whatsapp'=>['WhatsApp con código de país',20],'direccion'=>['Dirección',255],'ciudad'=>['Ciudad',100]] as $key=>[$label,$max]): ?><label><?=$label?><input name="<?=$key?>" maxlength="<?=$max?>" <?=$key!=='eslogan'?'required':''?> value="<?=gh($form[$key]??'')?>"></label><?php endforeach ?>
<label>Categoría<select name="galeria_categoria_id" required><option value="">Selecciona una categoría</option><?php foreach ($cats as $c): ?><option value="<?=$c['id']?>" <?=($form['galeria_categoria_id']??0)==$c['id']?'selected':''?>><?=gh($c['nombre'])?></option><?php endforeach ?></select></label>
<label>Modalidad<select name="modalidad"><?php foreach (['alquilada_fija'=>'Alquiler fijo','alquilada_comision'=>'Alquiler por comisión','administrada'=>'Administrada'] as $v=>$label): ?><option value="<?=$v?>" <?=($form['modalidad']??'')===$v?'selected':''?>><?=$label?></option><?php endforeach ?></select></label>
<label>Comisión (%)<input name="porcentaje_comision" type="number" min="0" max="100" step="0.01" required value="<?=gh($form['porcentaje_comision']??0)?>"></label>
<label>Estado<select name="estado"><?php foreach (['activa'=>'Activa','pendiente'=>'Pendiente','suspendida'=>'Suspendida'] as $v=>$label): ?><option value="<?=$v?>" <?=($form['estado']??'activa')===$v?'selected':''?>><?=$label?></option><?php endforeach ?></select></label>
<label>Latitud GPS <small>(opcional)</small><input name="latitud" type="number" min="-90" max="90" step="any" placeholder="-13.5167" value="<?=gh($form['latitud']??'')?>"></label><label>Longitud GPS <small>(opcional)</small><input name="longitud" type="number" min="-180" max="180" step="any" placeholder="-71.9781" value="<?=gh($form['longitud']??'')?>"></label></div>
<h3>Administrador de la tienda</h3><?php if ($edit): ?><p>Los administradores existentes conservan su cuenta. Completa estos campos solo para agregar otro administrador.</p><label><input style="width:auto;display:inline" type="checkbox" name="add_admin" value="1" <?=!empty($form['add_admin'])?'checked':''?>> Agregar administrador</label><?php endif ?>
<div class="fields"><label>Nombre del administrador<input name="admin_nombre" maxlength="100" <?=!$edit?'required':''?> value="<?=gh($form['admin_nombre']??'')?>"></label><label>Correo de acceso<input name="admin_email" type="email" maxlength="150" <?=!$edit?'required':''?> value="<?=gh($form['admin_email']??'')?>"></label><label>Contraseña<input name="admin_password" type="password" minlength="12" maxlength="72" autocomplete="new-password" <?=!$edit?'required':''?>><small>De 12 a 72 caracteres. El administrador ingresará con este correo y contraseña.</small></label></div><button>Guardar tienda<?=$edit?'':' y administrador'?></button> <a href="galeria-admin.php">Limpiar formulario</a></form></section><?php endif ?>
<section class="card"><h2>Tiendas registradas (<?=count($stores)?>)</h2><div class="table-wrap"><table><thead><tr><th>Tienda</th><th>Categoría</th><th>Administradores</th><th>Estado</th><th>Acciones</th></tr></thead><tbody><?php foreach ($stores as $s): ?><tr><td><?=gh($s['nombre'])?><br><small><?=gh($s['ciudad'])?></small></td><td><?=gh($s['categoria']??'Sin categoría')?></td><td><?php $found=false; foreach ($admins as $a): if ((int)$a['tienda_id']!==(int)$s['id']) continue; $found=true; ?><?=gh($a['nombre'])?><br><small><?=gh($a['email'])?></small><br><?php endforeach; if (!$found): ?>Sin administrador asignado<?php endif ?></td><td><?=gh($s['estado'])?></td><td><a href="?editar=<?=$s['id']?>#formulario">Editar</a> · <a href="tienda.php?slug=<?=urlencode($s['slug'])?>">Catálogo</a></td></tr><?php endforeach ?></tbody></table></div></section></main></body></html>
