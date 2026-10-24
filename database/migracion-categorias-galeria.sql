-- Ejecutar una sola vez en mixshopc_galeria después del esquema inicial.
CREATE TABLE IF NOT EXISTS galeria_categorias (id INT NOT NULL AUTO_INCREMENT, nombre VARCHAR(120) NOT NULL, slug VARCHAR(140) NOT NULL, descripcion TEXT NULL, orden INT NOT NULL DEFAULT 0, estado ENUM('activa','inactiva') NOT NULL DEFAULT 'activa', created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3), PRIMARY KEY(id), UNIQUE KEY galeria_categorias_slug_key(slug)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE tiendas ADD COLUMN IF NOT EXISTS galeria_categoria_id INT NULL AFTER id;
ALTER TABLE tiendas ADD INDEX IF NOT EXISTS tiendas_galeria_categoria_idx (galeria_categoria_id);
ALTER TABLE tiendas ADD CONSTRAINT tiendas_galeria_categoria_fk FOREIGN KEY (galeria_categoria_id) REFERENCES galeria_categorias(id) ON DELETE SET NULL ON UPDATE CASCADE;
INSERT INTO galeria_categorias (nombre,slug,descripcion,orden) SELECT 'General','general','Tiendas de la Galería Cusco',0 WHERE NOT EXISTS (SELECT 1 FROM galeria_categorias WHERE slug='general');
UPDATE tiendas SET galeria_categoria_id=(SELECT id FROM galeria_categorias WHERE slug='general') WHERE galeria_categoria_id IS NULL;
