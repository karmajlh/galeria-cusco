<?php
declare(strict_types=1);
session_start();
$cart = $_SESSION['cart'] ?? [];
if (!$cart) { header('Location: carrito.php'); exit; }
$config = require __DIR__ . '/config.php';
$name = trim((string)($_POST['cliente_nombre'] ?? '')); $phone = trim((string)($_POST['cliente_telefono'] ?? '')); $address = trim((string)($_POST['cliente_direccion'] ?? '')); $reference = trim((string)($_POST['cliente_referencia'] ?? '')); $method = (string)($_POST['metodo_pago'] ?? '');
if ($name === '' || $phone === '' || $address === '' || !in_array($method, ['yape', 'plin', 'transferencia'], true)) exit('Datos de checkout inválidos.');
try {
    $pdo = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $config['database']), $config['user'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $ids = array_keys($cart); $marks = implode(',', array_fill(0, count($ids), '?')); $stmt = $pdo->prepare("SELECT id,nombre,precio,precio_oferta,tienda_id FROM productos WHERE id IN ($marks) AND estado='activo'"); $stmt->execute($ids); $products = [];
    foreach ($stmt as $row) $products[(int)$row['id']] = $row;
    $storeId = (int)reset($cart)['store_id']; $total = 0.0; foreach ($cart as $id => $item) { if (!isset($products[$id]) || (int)$products[$id]['tienda_id'] !== $storeId) throw new RuntimeException('Carrito inválido.'); $total += (float)($products[$id]['precio_oferta'] ?? $products[$id]['precio']) * (int)$item['quantity']; }
    $code = 'ORD-' . strtoupper(bin2hex(random_bytes(8))); $pdo->beginTransaction(); $amount = number_format($total, 2, '.', '');
    $order = $pdo->prepare("INSERT INTO pedidos (codigo_pedido,tienda_id,cliente_nombre,cliente_telefono,cliente_direccion,cliente_referencia,monto_total,metodo_pago,monto_comision,monto_neto_tienda) VALUES (?,?,?,?,?,?,?,?,0,?)"); $order->execute([$code, $storeId, $name, $phone, $address, $reference, $amount, $method, $amount]);
    $orderId = (int)$pdo->lastInsertId(); $detail = $pdo->prepare('INSERT INTO pedido_detalles (pedido_id,producto_id,nombre_producto,cantidad,precio_unitario) VALUES (?,?,?,?,?)'); foreach ($cart as $id => $item) { $price = (float)($products[$id]['precio_oferta'] ?? $products[$id]['precio']); $detail->execute([$orderId, $id, $products[$id]['nombre'], (int)$item['quantity'], number_format($price, 2, '.', '')]); }
    $pdo->commit(); unset($_SESSION['cart']); header('Location: pedido.php?code=' . urlencode($code)); exit;
} catch (Throwable $error) { if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack(); http_response_code(400); exit('No se pudo crear el pedido. Verifica el carrito e inténtalo nuevamente.'); }
