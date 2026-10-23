-- Galeria Virtual Cusco: importacion manual para MariaDB 10.11
-- Ejecutar UNA SOLA VEZ en la base vacia mixshopc_galeria.
-- No contiene DROP, usuarios predeterminados ni contrasenas.
-- Si hay un error, detenerse: DDL puede quedar aplicado parcialmente.
USE `mixshopc_galeria`;
SET NAMES utf8mb4;
SET SESSION time_zone = '+00:00';

-- CreateTable
CREATE TABLE `tiendas` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(150) NOT NULL,
    `slug` VARCHAR(150) NOT NULL,
    `eslogan` TEXT NULL,
    `logo_url` TEXT NULL,
    `banner_url` TEXT NULL,
    `telefono_whatsapp` VARCHAR(20) NOT NULL,
    `direccion` TEXT NULL,
    `ciudad` VARCHAR(100) NOT NULL DEFAULT 'Cusco',
    `modalidad` ENUM('alquilada_fija', 'alquilada_comision', 'administrada') NOT NULL DEFAULT 'alquilada_fija',
    `porcentaje_comision` DECIMAL(5, 2) NOT NULL DEFAULT 0,
    `datos_pago_qr` JSON NULL,
    `estado` ENUM('activa', 'suspendida', 'pendiente') NOT NULL DEFAULT 'activa',
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

    UNIQUE INDEX `tiendas_slug_key`(`slug`),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `usuarios` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `tienda_id` INTEGER NULL,
    `nombre` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `rol` ENUM('superadmin', 'admin_tienda', 'vendedor', 'repartidor') NOT NULL DEFAULT 'admin_tienda',
    `telefono` VARCHAR(20) NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

    UNIQUE INDEX `usuarios_email_key`(`email`),
    INDEX `usuarios_tienda_id_idx`(`tienda_id`),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `categorias` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `tienda_id` INTEGER NOT NULL,
    `nombre` VARCHAR(100) NOT NULL,
    `orden` INTEGER NOT NULL DEFAULT 0,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

    INDEX `categorias_tienda_id_orden_idx`(`tienda_id`, `orden`),
    UNIQUE INDEX `categorias_id_tienda_id_key`(`id`, `tienda_id`),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `productos` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `tienda_id` INTEGER NOT NULL,
    `categoria_id` INTEGER NULL,
    `nombre` VARCHAR(200) NOT NULL,
    `descripcion` TEXT NULL,
    `precio` DECIMAL(10, 2) NOT NULL,
    `precio_oferta` DECIMAL(10, 2) NULL,
    `imagenes_urls` JSON NOT NULL,
    `es_destacado` BOOLEAN NOT NULL DEFAULT false,
    `estado` VARCHAR(20) NOT NULL DEFAULT 'activo',
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

    INDEX `productos_tienda_id_estado_idx`(`tienda_id`, `estado`),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `pedidos` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `codigo_pedido` VARCHAR(20) NOT NULL,
    `tienda_id` INTEGER NOT NULL,
    `repartidor_id` INTEGER NULL,
    `cliente_nombre` VARCHAR(150) NOT NULL,
    `cliente_telefono` VARCHAR(20) NOT NULL,
    `cliente_direccion` TEXT NOT NULL,
    `cliente_referencia` TEXT NULL,
    `monto_total` DECIMAL(10, 2) NOT NULL,
    `metodo_pago` VARCHAR(50) NOT NULL,
    `comprobante_url` TEXT NULL,
    `porcentaje_comision_aplicado` DECIMAL(5, 2) NOT NULL DEFAULT 0,
    `monto_comision` DECIMAL(10, 2) NOT NULL DEFAULT 0,
    `monto_neto_tienda` DECIMAL(10, 2) NOT NULL DEFAULT 0,
    `estado_pago` ENUM('pendiente_validacion', 'aprobado', 'rechazado') NOT NULL DEFAULT 'pendiente_validacion',
    `estado_envio` ENUM('pendiente', 'recibido', 'en_transito', 'llego', 'entregado') NOT NULL DEFAULT 'pendiente',
    `estado_liquidacion` VARCHAR(30) NOT NULL DEFAULT 'pendiente',
    `liquidacion_id` INTEGER NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

    UNIQUE INDEX `pedidos_codigo_pedido_key`(`codigo_pedido`),
    INDEX `pedidos_tienda_id_estado_pago_estado_liquidacion_idx`(`tienda_id`, `estado_pago`, `estado_liquidacion`),
    INDEX `pedidos_repartidor_id_estado_envio_idx`(`repartidor_id`, `estado_envio`),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `pedido_detalles` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `pedido_id` INTEGER NOT NULL,
    `producto_id` INTEGER NULL,
    `nombre_producto` VARCHAR(200) NOT NULL,
    `cantidad` INTEGER NOT NULL,
    `precio_unitario` DECIMAL(10, 2) NOT NULL,

    INDEX `pedido_detalles_pedido_id_idx`(`pedido_id`),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `liquidaciones_comisiones` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `tienda_id` INTEGER NOT NULL,
    `monto_total_ventas` DECIMAL(10, 2) NOT NULL,
    `monto_comision_pagada` DECIMAL(10, 2) NOT NULL,
    `periodo_inicio` DATE NOT NULL,
    `periodo_fin` DATE NOT NULL,
    `comprobante_pago_url` TEXT NULL,
    `estado` VARCHAR(20) NOT NULL DEFAULT 'completado',
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

    INDEX `liquidaciones_comisiones_tienda_id_periodo_inicio_periodo_fi_idx`(`tienda_id`, `periodo_inicio`, `periodo_fin`),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- AddForeignKey
ALTER TABLE `usuarios` ADD CONSTRAINT `usuarios_tienda_id_fkey` FOREIGN KEY (`tienda_id`) REFERENCES `tiendas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `categorias` ADD CONSTRAINT `categorias_tienda_id_fkey` FOREIGN KEY (`tienda_id`) REFERENCES `tiendas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `productos` ADD CONSTRAINT `productos_tienda_id_fkey` FOREIGN KEY (`tienda_id`) REFERENCES `tiendas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `productos` ADD CONSTRAINT `productos_categoria_id_fkey` FOREIGN KEY (`categoria_id`) REFERENCES `categorias`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `pedidos` ADD CONSTRAINT `pedidos_tienda_id_fkey` FOREIGN KEY (`tienda_id`) REFERENCES `tiendas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `pedidos` ADD CONSTRAINT `pedidos_repartidor_id_fkey` FOREIGN KEY (`repartidor_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `pedidos` ADD CONSTRAINT `pedidos_liquidacion_id_fkey` FOREIGN KEY (`liquidacion_id`) REFERENCES `liquidaciones_comisiones`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `pedido_detalles` ADD CONSTRAINT `pedido_detalles_pedido_id_fkey` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `pedido_detalles` ADD CONSTRAINT `pedido_detalles_producto_id_fkey` FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `liquidaciones_comisiones` ADD CONSTRAINT `liquidaciones_comisiones_tienda_id_fkey` FOREIGN KEY (`tienda_id`) REFERENCES `tiendas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE tiendas ADD CONSTRAINT tienda_comision_rango CHECK (porcentaje_comision BETWEEN 0 AND 100);
ALTER TABLE productos ADD CONSTRAINT producto_precio_positivo CHECK (precio >= 0 AND (precio_oferta IS NULL OR (precio_oferta >= 0 AND precio_oferta <= precio)));
ALTER TABLE productos ADD CONSTRAINT producto_estado_valido CHECK (estado IN ('activo','agotado','oculto'));
ALTER TABLE productos ADD CONSTRAINT producto_imagenes_array CHECK (JSON_TYPE(imagenes_urls) = 'ARRAY' AND JSON_LENGTH(imagenes_urls) <= 8);
ALTER TABLE pedido_detalles ADD CONSTRAINT detalle_cantidad_positiva CHECK (cantidad > 0 AND precio_unitario >= 0);
ALTER TABLE pedidos MODIFY codigo_pedido VARCHAR(20) CHARACTER SET ascii COLLATE ascii_bin NOT NULL;
ALTER TABLE pedidos ADD CONSTRAINT pedido_montos_validos CHECK (monto_total >= 0 AND monto_comision >= 0 AND monto_comision <= monto_total AND monto_neto_tienda = monto_total - monto_comision AND porcentaje_comision_aplicado BETWEEN 0 AND 100);
ALTER TABLE pedidos ADD CONSTRAINT pedido_metodo_valido CHECK (metodo_pago IN ('yape','plin','transferencia'));
ALTER TABLE pedidos ADD CONSTRAINT pedido_pago_antes_envio CHECK (estado_envio='pendiente' OR estado_pago='aprobado');
ALTER TABLE pedidos ADD CONSTRAINT pedido_liquidacion_estado CHECK (estado_liquidacion='pendiente' OR (estado_liquidacion='liquidado' AND estado_pago='aprobado' AND estado_envio='entregado'));
ALTER TABLE liquidaciones_comisiones ADD CONSTRAINT liquidacion_periodo CHECK (periodo_inicio <= periodo_fin AND monto_total_ventas >= 0 AND monto_comision_pagada >= 0 AND monto_comision_pagada <= monto_total_ventas);


DELIMITER $$
CREATE TRIGGER usuario_tenant_insert BEFORE INSERT ON usuarios FOR EACH ROW
BEGIN
  IF (NEW.rol IN ('admin_tienda','vendedor') AND NEW.tienda_id IS NULL) OR (NEW.rol='superadmin' AND NEW.tienda_id IS NOT NULL) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Rol incompatible con la tienda del usuario';
  END IF;
END$$
CREATE TRIGGER usuario_tenant_update BEFORE UPDATE ON usuarios FOR EACH ROW
BEGIN
  IF (NEW.rol IN ('admin_tienda','vendedor') AND NEW.tienda_id IS NULL) OR (NEW.rol='superadmin' AND NEW.tienda_id IS NOT NULL) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Rol incompatible con la tienda del usuario';
  END IF;
END$$

CREATE TRIGGER producto_categoria_insert BEFORE INSERT ON productos FOR EACH ROW
BEGIN
  IF NEW.categoria_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM categorias WHERE id=NEW.categoria_id AND tienda_id=NEW.tienda_id) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='La categoria pertenece a otra tienda';
  END IF;
END$$
CREATE TRIGGER producto_categoria_update BEFORE UPDATE ON productos FOR EACH ROW
BEGIN
  IF NEW.categoria_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM categorias WHERE id=NEW.categoria_id AND tienda_id=NEW.tienda_id) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='La categoria pertenece a otra tienda';
  END IF;
END$$

CREATE TRIGGER pedido_relaciones_insert BEFORE INSERT ON pedidos FOR EACH ROW
BEGIN
  IF NEW.repartidor_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM usuarios WHERE id=NEW.repartidor_id AND rol='repartidor' AND (tienda_id IS NULL OR tienda_id=NEW.tienda_id)) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Repartidor invalido para la tienda';
  END IF;
  IF NEW.liquidacion_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM liquidaciones_comisiones WHERE id=NEW.liquidacion_id AND tienda_id=NEW.tienda_id) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Liquidacion de otra tienda';
  END IF;
  IF (NEW.estado_liquidacion='pendiente' AND NEW.liquidacion_id IS NOT NULL) OR (NEW.estado_liquidacion='liquidado' AND NEW.liquidacion_id IS NULL) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Vinculo de liquidacion invalido';
  END IF;
END$$
CREATE TRIGGER pedido_relaciones_update BEFORE UPDATE ON pedidos FOR EACH ROW
BEGIN
  IF NEW.repartidor_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM usuarios WHERE id=NEW.repartidor_id AND rol='repartidor' AND (tienda_id IS NULL OR tienda_id=NEW.tienda_id)) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Repartidor invalido para la tienda';
  END IF;
  IF NEW.liquidacion_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM liquidaciones_comisiones WHERE id=NEW.liquidacion_id AND tienda_id=NEW.tienda_id) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Liquidacion de otra tienda';
  END IF;
  IF (NEW.estado_liquidacion='pendiente' AND NEW.liquidacion_id IS NOT NULL) OR (NEW.estado_liquidacion='liquidado' AND NEW.liquidacion_id IS NULL) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Vinculo de liquidacion invalido';
  END IF;
END$$

CREATE TRIGGER detalle_producto_insert BEFORE INSERT ON pedido_detalles FOR EACH ROW
BEGIN
  IF NEW.producto_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM productos p JOIN pedidos o ON o.id=NEW.pedido_id WHERE p.id=NEW.producto_id AND p.tienda_id=o.tienda_id) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Producto de otra tienda';
  END IF;
END$$
CREATE TRIGGER detalle_producto_update BEFORE UPDATE ON pedido_detalles FOR EACH ROW
BEGIN
  IF NEW.producto_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM productos p JOIN pedidos o ON o.id=NEW.pedido_id WHERE p.id=NEW.producto_id AND p.tienda_id=o.tienda_id) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Producto de otra tienda';
  END IF;
END$$

DELIMITER ;

-- Comprobacion final: deben aparecer 7 tablas y 8 triggers.
SHOW TABLES;
SHOW TRIGGERS;
