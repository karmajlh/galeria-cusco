<?php
declare(strict_types=1);
$config = require __DIR__ . '/config.php';
$slug = trim((string)($_GET['slug'] ?? ''));
try {
    $pdo = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $config['database']), $config['user'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $storeStmt = $pdo->prepare("SELECT id, nombre, eslogan, ciudad, direccion, telefono_whatsapp FROM tiendas WHERE slug = ? AND estado = 'activa'");
    $storeStmt->execute([$slug]);
    $store = $storeStmt->fetch();
    if (!$store) { http_response_code(404); exit('Tienda no encontrada.'); }
    $location = null;
    if ($pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='galeria_ubicaciones'")->fetchColumn()) {
        $l=$pdo->prepare('SELECT latitud,longitud FROM galeria_ubicaciones WHERE tienda_id=?');
        $l->execute([$store['id']]); $location=$l->fetch();
    }
    $productsStmt = $pdo->prepare("SELECT id, nombre, descripcion, precio, precio_oferta FROM productos WHERE tienda_id = ? AND estado = 'activo' ORDER BY es_destacado DESC, nombre");
    $productsStmt->execute([$store['id']]);
    $products = $productsStmt->fetchAll();
} catch (Throwable $error) { http_response_code(500); exit('No se pudo cargar la tienda.'); }
function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= h($store['nombre']) ?></title><style>body{font-family:system-ui;margin:0;background:#f6f3ee;color:#24211e}main{max-width:1000px;margin:auto;padding:40px 20px}a{color:#886c42}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:18px}.card{background:#fff;padding:20px;border-radius:16px;box-shadow:0 4px 18px #0001}.price{font-weight:700;font-size:1.3rem}</style></head><body><main><p><a href="/">← Todas las tiendas</a></p><h1><?= h($store['nombre']) ?></h1><p><?= h($store['eslogan'] ?? '') ?> · <?= h($store['ciudad']) ?></p><p><?=h($store['direccion']??'')?></p><p><a href="https://wa.me/<?=h(preg_replace('/\D/','',$store['telefono_whatsapp']))?>">Contactar por WhatsApp</a><?php if ($location && $location['latitud']!==null): ?> · <a href="https://www.google.com/maps?q=<?=urlencode($location['latitud'].','.$location['longitud'])?>">Ver ubicación GPS</a><?php endif ?></p><section class="grid"><?php if (!$products): ?><p>Aún no hay productos publicados.</p><?php else: foreach ($products as $product): ?><article class="card"><h2><a href="producto.php?id=<?= (int)$product['id'] ?>"><?= h($product['nombre']) ?></a></h2><p><?= h($product['descripcion'] ?? '') ?></p><p class="price">S/ <?= h((string)($product['precio_oferta'] ?? $product['precio'])) ?></p></article><?php endforeach; endif; ?></section></main></body></html>
