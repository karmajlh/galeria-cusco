<?php
declare(strict_types=1);
require __DIR__.'/galeria-core.php';
$category=max(0,(int)($_GET['categoria']??0)); $search=mb_substr(trim((string)($_GET['q']??'')),0,100);
try {
    $db=gallery_db();
    $categories=$db->query("SELECT c.id,c.nombre,COUNT(t.id) AS cantidad FROM galeria_categorias c LEFT JOIN tiendas t ON t.galeria_categoria_id=c.id AND t.estado='activa' WHERE c.estado='activa' GROUP BY c.id,c.nombre,c.orden ORDER BY c.orden,c.nombre")->fetchAll();
    $sql="SELECT t.id,t.nombre,t.slug,t.eslogan,t.ciudad,t.direccion,t.telefono_whatsapp,c.nombre AS categoria FROM tiendas t LEFT JOIN galeria_categorias c ON c.id=t.galeria_categoria_id WHERE t.estado='activa' AND (c.estado='activa' OR t.galeria_categoria_id IS NULL)";
    $params=[];
    if ($category) { $sql.=' AND c.id=?'; $params[]=$category; }
    if ($search!=='') { $sql.=' AND (t.nombre LIKE ? OR t.eslogan LIKE ? OR t.ciudad LIKE ?)'; $term='%'.$search.'%'; array_push($params,$term,$term,$term); }
    $q=$db->prepare($sql.' ORDER BY t.nombre'); $q->execute($params); $stores=$q->fetchAll();
} catch (Throwable $e) { http_response_code(503); exit('La galería no está disponible temporalmente. Intenta nuevamente.'); }
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Galería Cusco · Tiendas locales</title><meta name="description" content="Encuentra tiendas de Cusco por categoría y descubre sus catálogos de productos."><link rel="stylesheet" href="galeria.css"></head><body>
<header><nav><a class="brand" href="index.php">GALERÍA CUSCO</a><div><a href="seguimiento.php">Mis pedidos</a> · <a href="login.php">Acceso de tiendas</a></div></nav></header>
<main><section class="hero"><p class="eyebrow">Comercios de nuestra ciudad</p><h1>Encuentra tu próxima tienda favorita.</h1><p class="muted">Explora las categorías, conoce las tiendas de Cusco y visita sus catálogos.</p></section>
<form class="search" method="get"><input type="hidden" name="categoria" value="<?=$category?>"><input aria-label="Buscar tiendas por nombre o ciudad" name="q" value="<?=gh($search)?>" maxlength="100" placeholder="Busca una tienda, su slogan o ciudad…"><button>Buscar tiendas</button></form>
<nav class="categories" aria-label="Categorías de tiendas" style="padding:0"><a class="<?=$category===0?'active':''?>" <?=!$category?'aria-current="page"':''?> href="?<?=gh(http_build_query(['q'=>$search]))?>">Todas las tiendas</a><?php foreach ($categories as $c): ?><a class="<?=$category===(int)$c['id']?'active':''?>" <?=$category===(int)$c['id']?'aria-current="page"':''?> href="?<?=gh(http_build_query(['categoria'=>$c['id'],'q'=>$search]))?>"><?=gh($c['nombre'])?> (<?=$c['cantidad']?>)</a><?php endforeach ?></nav>
<p class="muted"><?=count($stores)?> tiendas encontradas</p><section class="grid" aria-label="Catálogo de tiendas">
<?php foreach ($stores as $s): ?><article class="card"><span class="tag"><?=gh($s['categoria']??'Comercio local')?></span><h2><?=gh($s['nombre'])?></h2><p><?=gh($s['eslogan']??'')?></p><p class="muted"><?=gh($s['ciudad'])?><br><?=gh($s['direccion']??'')?></p><div class="actions"><a class="button" href="tienda.php?slug=<?=urlencode($s['slug'])?>">Ver catálogo →</a><?php $phone=preg_replace('/\D/','',$s['telefono_whatsapp']); if ($phone): ?><a href="https://wa.me/<?=gh($phone)?>">WhatsApp</a><?php endif ?></div></article><?php endforeach ?>
<?php if (!$stores): ?><div class="card"><h2>No encontramos tiendas</h2><p>Prueba otra categoría o cambia la búsqueda.</p><a href="index.php">Ver todas las tiendas</a></div><?php endif ?></section></main></body></html>
