<?php
declare(strict_types=1);
session_start();
$config = require __DIR__ . '/config.php';
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$quantity = min(99, max(1, (int)($_POST['quantity'] ?? 1)));
try {
    $pdo = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $config['database']), $config['user'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $stmt = $pdo->prepare("SELECT id, nombre, precio, precio_oferta, tienda_id FROM productos WHERE id=? AND estado='activo'"); $stmt->execute([$id ?: 0]); $product = $stmt->fetch();
    if (!$product) { http_response_code(404); exit('Producto no encontrado.'); }
    if (!empty($_SESSION['cart']) && (int)reset($_SESSION['cart'])['store_id'] !== (int)$product['tienda_id']) { exit('El carrito solo puede contener productos de una tienda.'); }
    $_SESSION['cart'][$product['id']] = ['name'=>$product['nombre'], 'price'=>(float)($product['precio_oferta'] ?? $product['precio']), 'quantity'=>($_SESSION['cart'][$product['id']]['quantity'] ?? 0) + $quantity, 'store_id'=>(int)$product['tienda_id']];
    header('Location: carrito.php'); exit;
} catch (Throwable $error) { http_response_code(500); exit('No se pudo agregar el producto.'); }
