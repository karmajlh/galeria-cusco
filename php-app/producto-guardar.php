<?php
declare(strict_types=1); session_start(); if(empty($_SESSION['user'])){header('Location: login.php');exit;}
$config=require __DIR__.'/config.php'; $store=(int)($_POST['tienda_id']??0); $name=trim((string)($_POST['nombre']??'')); $description=trim((string)($_POST['descripcion']??'')); $price=(float)($_POST['precio']??-1);
if($store<1||$name===''||$price<0){exit('Datos de producto inválidos.');}
try{$pdo=new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',$config['host'],$config['port'],$config['database']),$config['user'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);$stmt=$pdo->prepare("INSERT INTO productos (tienda_id,nombre,descripcion,precio,imagenes_urls,estado) VALUES (?,?,?,?, '[]','activo')");$stmt->execute([$store,$name,$description,number_format($price,2,'.','')]);header('Location: panel.php');exit;}catch(Throwable $e){http_response_code(500);exit('No se pudo guardar el producto.');}
