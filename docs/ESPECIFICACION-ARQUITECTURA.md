Codex debe seguir esta arquitectura modular para el repositorio de GitHub:
Plaintext
```
galeria-virtual-pwa/
├── .github/
│   └── workflows/
│       └── deploy.yml          # CI/CD para Vercel / Netlify
├── public/
│   ├── manifest.json           # Configuración PWA
│   ├── sw.js                   # Service Worker para PWA / Cache
│   └── icons/                  # Iconos PWA (192x192, 512x512)
├── src/
│   ├── app/                    # Next.js App Router
│   │   ├── (public)/           # Rutas públicas (Galería)
│   │   │   ├── page.tsx        # Home / Feed Principal
│   │   │   ├── tiendas/        # Directorio de Tiendas
│   │   │   └── tienda/[slug]/  # Micro-sitio de cada comercio
│   │   ├── (checkout)/
│   │   │   ├── carrito/
│   │   │   └── tracking/[code]/# Tracking de envío para cliente
│   │   ├── (auth)/             # Login / Registro
│   │   ├── admin/              # Panel SuperAdmin (Dueño de Galería)
│   │   ├── dashboard/          # Panel Admin de Tienda
│   │   └── delivery/           # PWA Repartidor / Motorizado
│   ├── components/             # Componentes UI reutilizables
│   │   ├── ui/                 # Botones, Modales, Badges (Shadcn/Tailwind)
│   │   ├── galeria/            # Tarjetas de tiendas, productos, feeds
│   │   └── forms/              # Formulario de producto, upload voucher
│   ├── lib/                    # Configuración DB, Auth, Utilities
│   │   ├── db.ts               # Cliente Prisma / Drizzle DB
│   │   ├── auth.ts             # JWT / NextAuth / Auth0
│   │   └── utils.ts            # Helpers, calculador de comisiones
│   └── types/                  # Definiciones de TypeScript
│       └── index.ts
├── .env.example
├── next.config.js
├── tailwind.config.js
├── tsconfig.json
└── package.json

```
Este es el script SQL completo que Codex debe utilizar para generar las migraciones (Prisma / Drizzle ORM):
SQL
```
-- 1. TIPOS ENUMERADOS (PostgreSQL)
CREATE TYPE modalidad_tienda AS ENUM ('alquilada_fija', 'alquilada_comision', 'administrada');
CREATE TYPE estado_tienda AS ENUM ('activa', 'suspendida', 'pendiente');
CREATE TYPE rol_usuario AS ENUM ('superadmin', 'admin_tienda', 'vendedor', 'repartidor');
CREATE TYPE estado_pago_pedido AS ENUM ('pendiente_validacion', 'aprobado', 'rechazado');
CREATE TYPE estado_envio_pedido AS ENUM ('pendiente', 'recibido', 'en_transito', 'llego', 'entregado');

-- 2. TABLA DE TIENDAS (TENANTS)
CREATE TABLE tiendas (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    slug VARCHAR(150) UNIQUE NOT NULL,
    eslogan TEXT,
    logo_url TEXT,
    banner_url TEXT,
    telefono_whatsapp VARCHAR(20) NOT NULL,
    direccion TEXT,
    ciudad VARCHAR(100) DEFAULT 'Cusco',
    modalidad modalidad_tienda DEFAULT 'alquilada_fija',
    porcentaje_comision DECIMAL(5,2) DEFAULT 0.00,
    datos_pago_qr JSONB, -- { yape: "", plin: "", ccp_bcp: "", cci: "" }
    estado estado_tienda DEFAULT 'activa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. TABLA DE USUARIOS Y ROLES
CREATE TABLE usuarios (
    id SERIAL PRIMARY KEY,
    tienda_id INT REFERENCES tiendas(id) ON DELETE CASCADE, -- NULL si es SuperAdmin o Repartidor Global
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol rol_usuario DEFAULT 'admin_tienda',
    telefono VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. CATEGORÍAS
CREATE TABLE categorias (
    id SERIAL PRIMARY KEY,
    tienda_id INT NOT NULL REFERENCES tiendas(id) ON DELETE CASCADE,
    nombre VARCHAR(100) NOT NULL,
    orden INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. PRODUCTOS
CREATE TABLE productos (
    id SERIAL PRIMARY KEY,
    tienda_id INT NOT NULL REFERENCES tiendas(id) ON DELETE CASCADE,
    categoria_id INT REFERENCES categorias(id) ON DELETE SET NULL,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    precio_oferta DECIMAL(10,2),
    imagenes_urls TEXT[], -- Array de URLs
    es_destacado BOOLEAN DEFAULT false,
    estado VARCHAR(20) DEFAULT 'activo', -- activo, agotado, oculto
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. PEDIDOS
CREATE TABLE pedidos (
    id SERIAL PRIMARY KEY,
    codigo_pedido VARCHAR(20) UNIQUE NOT NULL, -- Ej: ORD-1024
    tienda_id INT NOT NULL REFERENCES tiendas(id) ON DELETE CASCADE,
    repartidor_id INT REFERENCES usuarios(id) ON DELETE SET NULL,
    
    -- Datos del Cliente
    cliente_nombre VARCHAR(150) NOT NULL,
    cliente_telefono VARCHAR(20) NOT NULL,
    cliente_direccion TEXT NOT NULL,
    cliente_referencia TEXT,
    
    -- Montos y Comisiones
    monto_total DECIMAL(10,2) NOT NULL,
    metodo_pago VARCHAR(50) NOT NULL, -- 'yape', 'plin', 'transferencia'
    comprobante_url TEXT, -- Voucher subido por el cliente
    porcentaje_comision_aplicado DECIMAL(5,2) DEFAULT 0.00,
    monto_comision DECIMAL(10,2) DEFAULT 0.00,
    monto_neto_tienda DECIMAL(10,2) DEFAULT 0.00,
    
    -- Estados
    estado_pago estado_pago_pedido DEFAULT 'pendiente_validacion',
    estado_envio estado_envio_pedido DEFAULT 'pendiente',
    estado_liquidacion VARCHAR(30) DEFAULT 'pendiente', -- 'pendiente', 'liquidado'
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 7. DETALLE DE PEDIDOS
CREATE TABLE pedido_detalles (
    id SERIAL PRIMARY KEY,
    pedido_id INT NOT NULL REFERENCES pedidos(id) ON DELETE CASCADE,
    producto_id INT REFERENCES productos(id) ON DELETE SET NULL,
    nombre_producto VARCHAR(200) NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL
);

-- 8. LIQUIDACIONES DE COMISIONES
CREATE TABLE liquidaciones_comisiones (
    id SERIAL PRIMARY KEY,
    tienda_id INT NOT NULL REFERENCES tiendas(id) ON DELETE CASCADE,
    monto_total_ventas DECIMAL(10,2) NOT NULL,
    monto_comision_pagada DECIMAL(10,2) NOT NULL,
    periodo_inicio DATE NOT NULL,
    periodo_fin DATE NOT NULL,
    comprobante_pago_url TEXT,
    estado VARCHAR(20) DEFAULT 'completado',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

```
Codex debe generar las conexiones utilizando estas variables de entorno en GitHub y Vercel/Netlify:
Fragmento de código
```
# BASE DE DATOS EN HOSTING PROPIO
DATABASE_URL="postgresql://usuario_db:password_db@ip_o_host_de_tu_hosting:5432/nombre_bd?sslmode=require"

# AUTENTICACIÓN Y SEGURIDAD
NEXTAUTH_SECRET="super-secret-key-para-jwt-pwa"
NEXTAUTH_URL="https://tu-galeria-cusco.vercel.app"

# STORAGE DE IMÁGENES (Cloudinary o S3)
CLOUDINARY_CLOUD_NAME="tu_cloud_name"
CLOUDINARY_API_KEY="tu_api_key"
CLOUDINARY_API_SECRET="tu_api_secret"

# APP SETTINGS
NEXT_PUBLIC_APP_NAME="Galería Virtual Cusco"
NEXT_PUBLIC_BASE_URL="https://tu-galeria-cusco.vercel.app"

```
Puedes indicarle a Codex que ejecute las tareas ordenadas por Fases:
1. Crear el proyecto Next.js 14+ con TypeScript, Tailwind CSS, Lucide Icons y Shadcn UI.
2. Configurar el esquema de Prisma/Drizzle con las 8 tablas de la base de datos SQL.
3. Crear las utilidades de conexión a la BD en hosting remoto mediante process.env.DATABASE_URL.
4. Configurar el manifiesto PWA (manifest.json) y el Service Worker para instalación móvil.
1. Construir la página Home (/): Buscador global, Carrusel de banners, Grid de productos destacados.
2. Construir la vista de Directorio de Tiendas (/tiendas).
3. Construir la ruta dinámica de Micro-sitio (/tienda/[slug]): Muestra el logo, banner, eslogan, datos de contacto de la tienda y su catálogo organizado por categorías.
4. Construir la vista de Detalle de Producto con galería de imágenes y botón "Agregar al Carrito".
1. Implementar la pantalla de Carrito de Compras.
2. Construir el flujo de Checkout: Formulario de datos del cliente + Selección de Pago (Yape, Plin, Transferencia BCP) + Formulario de subida de imagen del Voucher (comprobante_url).
3. Crear el generador de código de pedido único (Ej. ORD-1024).
4. Construir la página pública de Tracking (/tracking/[code]): Muestra la línea de tiempo interactiva (Pendiente -> Aprobado -> Recibido -> En Tránsito -> Llegó -> Entregado).
1. Crear la vista /delivery protegida por rol repartidor.
2. Listar envíos pendientes con mapa, dirección, teléfono y botones de cambio de estado en un solo toque (Recibido, En Tránsito, Llegó, Entregado).
1. Construir el Dashboard de la Tienda (/dashboard):
   - CRUD de Productos y Categorías.
   - Gestión de Datos de Pago QR (Yape, Plin) y datos de la empresa.
   - Modulo de Pedidos: Ver voucher cargado por el cliente, botón "Aprobar Pago" / "Rechazar".
   - Si la tienda es modalidad alquilada_comision, mostrar el desglose de comisión adeudada.
2. Construir el Panel del SuperAdmin (/admin):
   - Alta y baja de Tiendas seleccionando Modalidad (alquilada_fija, alquilada_comision, administrada).
   - Si es administrada, permitir al SuperAdmin operar la tienda.
   - Módulo de Liquidación de Comisiones acumuladas por cada tienda.
