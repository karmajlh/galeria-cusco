# Galería Virtual Cusco

Marketplace multitienda con Next.js App Router, TypeScript estricto, Tailwind CSS 4, componentes compatibles con Shadcn UI, Lucide, MySQL y Prisma 7. La base utiliza MySQL 8.0.16+ o 8.4 por decisión del usuario. Se conserva la arquitectura entregada en [docs/ESPECIFICACION-ARQUITECTURA.md](docs/ESPECIFICACION-ARQUITECTURA.md).

## Inicio local

Node.js 24 recomendado. No se incluyen credenciales ni cuentas de pago predeterminadas.

```powershell
npm ci
npm run db:generate
npm run dev
```

Abre http://localhost:3000. Sin `DATABASE_URL`, se muestra un catálogo de muestra y se bloquean pedidos, acceso y registro reales. La demostración anterior se conserva en `/demo`; utiliza datos locales separados.

## Activar MySQL y almacenamiento

1. Ejecuta `npm run setup:env` y consulta [la guía del hosting](docs/HOSTING-MYSQL.md) y configura `DATABASE_URL` para una base de desarrollo vacía. Prisma CLI carga `.env`; Next también lo carga. En hosting, utiliza variables de entorno del proyecto.
2. Configura `NEXTAUTH_SECRET` con un valor aleatorio de al menos 32 caracteres y `NEXTAUTH_URL` con el origen de esta instalación. Se implementan JWT con JOSE y cookies HTTP-only; **no se utiliza NextAuth**, se conservan los nombres de variables solicitados. El rol y la tienda se consultan en la BD en cada petición autenticada.
3. Configura las tres variables de Cloudinary. Los comprobantes se suben como recursos `authenticated`; no se almacenan en el disco efímero del hosting.
4. Ejecuta `npm run db:deploy` sobre la base elegida. Hay dos migraciones: esquema inicial y restricciones de integridad.
5. Configura temporalmente `SEED_ADMIN_EMAIL` y `SEED_ADMIN_PASSWORD` (mínimo 12 caracteres) y ejecuta `npm run db:seed`. El comando crea únicamente el primer SuperAdmin; no modifica usuarios existentes. Retira las variables de inicialización después de usarlas.
6. Ingresa en `/login`, crea/activa tiendas y configura productos, medios de pago y colaboradores.

**No aplicar las migraciones iniciales directamente a una BD con tablas existentes.** En ese caso se requiere una línea base de Prisma y revisión de diferencias. Ninguna migración se ha aplicado al hosting del usuario.

## Rutas y módulos

| Ruta | Función |
| --- | --- |
| `/` | Buscador global del catálogo, banners navegables, categorías y destacados |
| `/tiendas` | Directorio con búsqueda |
| `/tienda/[slug]` | Logo, banner, eslogan, WhatsApp y catálogo por categorías |
| `/producto/[id]` | Galería de imágenes, descripción y carrito |
| `/carrito` | Cantidades, checkout independiente por tienda, Yape/Plin/BCP y voucher |
| `/pedidos`, `/tracking/[code]` | Consulta de estados sin revelar dirección, teléfono ni voucher |
| `/login`, `/registro` | Inicio de sesión y solicitud de tienda pendiente de aprobación |
| `/dashboard` | Productos, categorías, empresa, QR, revisión de pagos y comisiones |
| `/admin` | Tiendas, modalidades, logística, altas de usuarios y liquidaciones |
| `/delivery` | Entregas del repartidor autenticado, contacto, mapa externo y cambios de estado |
| `/demo/*` | Prototipo anterior aislado; ningún acceso a MySQL |

`src/app/(public)`, `(checkout)` y `(auth)` organizan las rutas sin modificar sus URLs. Los formularios, tarjetas, UI y paneles están separados en `src/components`. Las mutaciones se concentran en `src/lib/actions`, la conexión en `src/lib/db.ts`, autenticación en `src/lib/auth.ts` y contratos serializables en `src/types`.

Tailwind 4 utiliza configuración CSS y `postcss.config.mjs`; no necesita el `tailwind.config.js` del ejemplo de Tailwind 3. `components.json` configura los aliases y componentes de Shadcn.

## Reglas implementadas

- AdminTienda y Vendedor operan su tienda; Vendedor no modifica datos de pago ni crea usuarios. SuperAdmin administra comercios y solo opera catálogo/pagos de tiendas en modalidad `administrada`. Repartidor ve y actualiza únicamente pedidos asignados.
- Sesiones JWT de ocho horas; cookies HTTP-only, SameSite y Secure en producción. Contraseñas con scrypt y salt individual; cambiar el hash invalida las sesiones anteriores. Tiendas suspendidas o pendientes no pueden iniciar sesión ni recibir compras.
- Pago y envío son estados separados. El pago se revisa una vez; el envío requiere pago aprobado y avanza `pendiente → recibido → en_transito → llego → entregado`.
- Precios y comisiones se calculan en servidor a partir de productos de una única tienda. Si el total cambió respecto al mostrado, la operación se rechaza. Cantidades de 1 a 99; máximo 50 líneas por pedido.
- Códigos `ORD-` con 96 bits aleatorios y restricción UNIQUE. El código permite reintentar sin duplicar el pedido; también funciona como enlace privado de seguimiento. No usar códigos secuenciales públicos para datos de clientes.
- Vouchers PNG/JPG de hasta 3 MB, verificación de firma del archivo y almacenamiento privado. La lectura exige permisos de la tienda y genera un enlace de 60 segundos. El campo `comprobante_url` guarda el identificador del recurso privado, no una URL pública.
- Comisión en céntimos con redondeo al céntimo más cercano; cero para modalidad fija. Tasa, comisión y neto se guardan como instantáneas en el pedido.
- Liquidaciones serializables sobre pedidos aprobados, entregados y pendientes, con vínculo a cada pedido. Registrar una liquidación representa un pago ya recibido, no mueve dinero. Los periodos se interpretan en hora de Perú.
- Tracking mediante consulta automática cada 10 segundos. La PWA incluye iconos 192/512, manifest y página sin conexión; no almacena respuestas privadas ni permite mutaciones sin conexión.

## Compatibilidad con el SQL adjunto

El texto menciona ocho tablas, pero su SQL contiene **siete tablas y cinco enums**: el bloque 1 define tipos. Se conservan esas siete tablas y sus nombres en español. Se incorporan defaults no nulos, índices, restricciones monetarias y triggers de aislamiento. Se añade `pedidos.liquidacion_id` para auditar y evitar liquidaciones duplicadas. No se inventa una octava tabla.

No hay columna de stock en el SQL recibido: la disponibilidad se controla con `activo`, `agotado` y `oculto`. No se implementa inventario cuantitativo. El SQL tampoco define una tarifa de delivery; el checkout muestra solo el importe de productos y lo informa antes de pagar.

## Verificaciones

```powershell
npm run db:validate
npm run typecheck
npm test
npm run build
```

Las pruebas unitarias cubren permisos, estados, dinero, contraseñas, configuración MySQL y conversión JSON. `npm run test:integration` requiere MYSQL_TEST_DATABASE_URL con una base exclusiva terminada en `_test` y migraciones aplicadas. Sin esa variable se omite explícitamente. GitHub Actions prepara pruebas sobre MySQL 8.0 y 8.4; su ejecución remota está pendiente.

## GitHub, Vercel y Netlify

`.github/workflows/deploy.yml` instala dependencias, valida Prisma, aplica las migraciones a MySQL de CI, ejecuta pruebas y compila. En `main` puede invocar un deploy hook oficial de Vercel o Netlify mediante el secreto `DEPLOY_HOOK_URL` y el environment `production`. Sin el secreto, solo valida. El hook solicita el despliegue; no confirma que el hosting terminó correctamente. Configura la rama de producción del hook como `main` y desactiva despliegues automáticos paralelos si quieres exigir el paso de CI.

En Vercel selecciona Next.js; en Netlify se incluye `netlify.toml` y se utiliza su integración Next.js. Build: `npm run build`. Configura todas las variables del servicio en el hosting, no en el YAML. Ejecuta las migraciones de producción como paso controlado antes de activar una versión que las requiera. El workflow no usa credenciales de producción para migrar.

No se creó un repositorio remoto, se configuraron secretos externos ni se publicó la aplicación.

## Límites pendientes de validar con los servicios reales

- Conexión TLS al MySQL del hosting, permisos del usuario y estrategia de backups.
- Flujo completo con sesión, subida/lectura privada de Cloudinary y transacciones sobre el servidor remoto.
- Instalación PWA en dispositivos Android/iOS reales; no hay GPS en vivo ni notificaciones push. El mapa abre la dirección en Google Maps con acción del repartidor.
- Rate limiting incluido por proceso: antes de exposición pública con múltiples instancias, configurar límites distribuidos en el hosting/WAF. No hay recuperación automática de contraseñas ni verificación de correo.
- Catálogo carga las tiendas activas; listas administrativas limitadas a los últimos 100 productos/pedidos/liquidaciones. La paginación de grandes catálogos queda pendiente.
- Imágenes de productos, logos, banners y QR se configuran por URL HTTPS; el upload integrado se destina a vouchers. No hay editor visual ni carga directa de imágenes del catálogo.
- Definir términos comerciales para tarifas fijas, envío, impuestos, cancelaciones/reembolsos y liquidaciones de tiendas administradas. El módulo de liquidaciones actual registra comisiones recibidas por la galería.
