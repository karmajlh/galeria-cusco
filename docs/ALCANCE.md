# Estado de la implementación

La especificación original se conserva íntegra en [ESPECIFICACION-ARQUITECTURA.md](ESPECIFICACION-ARQUITECTURA.md). Por indicación posterior del usuario, el motor es MySQL 8.0.16+ o 8.4 en su hosting. Las listas de imágenes utilizan JSON y los enums son columnas nativas MySQL.

La aplicación ya tiene la arquitectura modular, modelos Prisma, migraciones, permisos del servidor, rutas públicas, checkout, almacenamiento de comprobantes y paneles de tienda, administración y delivery. La versión anterior de demostración se mantiene aislada en `/demo`.

El SQL del documento define siete tablas, no ocho. Se preservan sus nombres y cinco enums. La extensión `pedidos.liquidacion_id` permite relacionar los pedidos liquidados con su registro contable. Se añaden restricciones e índices sin introducir nuevas tablas.

Sin `DATABASE_URL`, la aplicación sirve datos de muestra y desactiva operaciones reales. No se sustituyen errores de conexión por datos ficticios cuando sí existe una URL configurada.

Las pruebas unitarias son locales; las de integración requieren un MySQL de pruebas. El workflow prepara MySQL 8.0 y 8.4. No se han aplicado migraciones a una base remota ni comprobado una cuenta real de Cloudinary. El pipeline preparado requiere conectar GitHub y el hosting. Consulta [README.md](../README.md) y [HOSTING-MYSQL.md](HOSTING-MYSQL.md) para el arranque y los límites pendientes.
