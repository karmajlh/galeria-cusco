<?php
declare(strict_types=1);
header('Location: galeria-admin.php?editar='.(int)($_GET['id']??0).'#formulario');
exit;
