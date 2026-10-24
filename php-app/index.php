<?php
declare(strict_types=1);

$configPath = __DIR__ . '/config.php';
if (!is_file($configPath)) {
    http_response_code(500);
    exit('Falta config.php. Copia config.example.php y completa los datos del hosting.');
}
$config = require $configPath;
try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $config['database']),
        $config['user'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC],
    );
    $stores = $pdo->query("SELECT t.id, t.nombre, t.slug, t.eslogan, t.ciudad, COALESCE(c.nombre, 'Tiendas') AS categoria FROM tiendas t LEFT JOIN galeria_categorias c ON c.id = t.galeria_categoria_id AND c.estado = 'activa' WHERE t.estado = 'activa' ORDER BY c.orden, c.nombre, t.nombre")->fetchAll();
} catch (Throwable $error) {
    http_response_code(500);
    exit('No se pudo conectar con la base de datos. Verifica config.php.');
}
function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
?><!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Galería Virtual Cusco</title><style>body{font-family:system-ui;margin:0;background:#f6f3ee;color:#24211e}main{max-width:1000px;margin:auto;padding:40px 20px}h1{font-size:clamp(2rem,5vw,4rem)}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:18px}.card{background:#fff;padding:20px;border-radius:16px;box-shadow:0 4px 18px #0001}.tag{color:#886c42}</style></head>
<body><main><p class="tag">GALERÍA VIRTUAL CUSCO</p><h1>Tiendas locales, cerca de ti.</h1><p>Descubre comercios y productos de Cusco.</p><?php $category = null; foreach ($stores as $store): if ($category !== $store['categoria']): if ($category !== null): ?></section><?php endif; $category = $store['categoria']; ?><h2><?= h($category) ?></h2><section class="grid"><?php endif; ?><article class="card"><h2><?= h($store['nombre']) ?></h2><p><?= h($store['eslogan'] ?? 'Comercio local cusqueño') ?></p><p class="tag"><?= h($store['ciudad']) ?></p><a href="tienda.php?slug=<?= urlencode($store['slug']) ?>">Ver tienda →</a></article><?php endforeach; if ($category !== null): ?></section><?php endif; ?></main></body></html>
