<?php
declare(strict_types=1);
$config = require __DIR__ . '/config.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
try {
    $pdo = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $config['database']), $config['user'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $query = $pdo->prepare("SELECT p.*, t.nombre AS tienda_nombre, t.slug AS tienda_slug FROM productos p JOIN tiendas t ON t.id=p.tienda_id WHERE p.id=? AND p.estado='activo' AND t.estado='activa'");
    $query->execute([$id ?: 0]); $product = $query->fetch();
    if (!$product) { http_response_code(404); exit('Producto no encontrado.'); }
} catch (Throwable $error) { http_response_code(500); exit('No se pudo cargar el producto.'); }
function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= h($product['nombre']) ?></title><style>body{font-family:system-ui;margin:0;background:#f6f3ee;color:#24211e}main{max-width:760px;margin:auto;padding:40px 20px}.card{background:#fff;padding:28px;border-radius:18px}.price{font-size:2rem;font-weight:700;color:#886c42}a{color:#886c42}button{background:#886c42;color:#fff;border:0;padding:12px 18px;border-radius:8px;font-size:1rem}</style></head><body><main><p><a href="tienda.php?slug=<?= urlencode($product['tienda_slug']) ?>">← <?= h($product['tienda_nombre']) ?></a> · <a href="carrito.php">Ver carrito</a></p><article class="card"><p><?= h($product['tienda_nombre']) ?></p><h1><?= h($product['nombre']) ?></h1><p><?= nl2br(h($product['descripcion'] ?? '')) ?></p><p class="price">S/ <?= h((string)($product['precio_oferta'] ?? $product['precio'])) ?></p><form method="post" action="agregar-carrito.php"><input type="hidden" name="id" value="<?= (int)$product['id'] ?>"><label>Cantidad <input type="number" name="quantity" min="1" max="99" value="1"></label><button type="submit">Agregar al carrito</button></form></article></main></body></html>
