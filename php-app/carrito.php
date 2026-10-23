<?php
declare(strict_types=1);
session_start();
$cart = $_SESSION['cart'] ?? [];
$total = 0.0;
foreach ($cart as $item) $total += (float)$item['price'] * (int)$item['quantity'];
function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Carrito · Galería Cusco</title><style>body{font-family:system-ui;margin:0;background:#f6f3ee;color:#24211e}main{max-width:800px;margin:auto;padding:40px 20px}a{color:#886c42}.item{background:#fff;padding:18px;margin:12px 0;border-radius:14px;display:flex;justify-content:space-between;gap:20px}.total{font-size:1.5rem;font-weight:700}</style></head><body><main><p><a href="/">← Volver a tiendas</a></p><h1>Tu carrito</h1><?php if (!$cart): ?><p>Tu carrito está vacío.</p><?php else: foreach ($cart as $id => $item): ?><article class="item"><div><h2><?= h($item['name']) ?></h2><p>S/ <?= number_format((float)$item['price'], 2) ?> × <?= (int)$item['quantity'] ?></p></div><p>S/ <?= number_format((float)$item['price'] * (int)$item['quantity'], 2) ?></p></article><?php endforeach; ?><p class="total">Total: S/ <?= number_format($total, 2) ?></p><p>Próximamente: formulario de entrega y confirmación de pedido.</p><?php endif; ?></main></body></html>
