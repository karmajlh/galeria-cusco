# Galería Cusco: catálogo de tiendas

La portada permite buscar por nombre, slogan o ciudad y filtrar por categoría general. Solo muestra tiendas activas con categoría visible, o tiendas antiguas todavía sin categoría.

El superadmin entra a `galeria-admin.php` desde el panel. Puede crear y editar categorías; registrar una tienda y su administrador en una transacción; editar datos sin cambiar el enlace existente; agregar administradores a tiendas existentes. Los administradores se autentican con el correo y contraseña definidos al crearlos.

## Activación única

La migración anterior de categorías generales debe estar aplicada (ya confirmada en el hosting). En el nuevo panel, pulsar **Activar módulo** crea exclusivamente la tabla de coordenadas GPS, si falta. Conserva los registros existentes. No requiere pegar SQL en phpMyAdmin.

## Prueba de aceptación

1. Entrar como superadmin. Crear una categoría, por ejemplo Artesanías.
2. Registrar una tienda con categoría, dirección, WhatsApp internacional, modalidad y comisión, más administrador y contraseña de 12 a 72 caracteres. Las coordenadas son opcionales, pero deben completarse juntas.
3. Comprobar que aparece en el filtro Artesanías y que el catálogo incluye contacto y enlace al mapa cuando tiene GPS.
4. Editar nombre, modalidad, comisión y estado. Comprobar que se precargan los valores guardados y que el enlace del catálogo se mantiene.
5. Intentar crear otra tienda con un correo ya registrado. Debe mostrarse el error y no quedar una tienda parcial.
6. Ocultar una categoría o suspender una tienda: deja de aparecer en la portada; se puede reactivar.
7. Entrar con la cuenta de administrador creada: accede a su panel de tienda. Abrir la administración global debe devolver acceso denegado.

El trabajo actual cubre la galería y el alta de tiendas/administradores. Los módulos anteriores de productos, pedidos y usuarios requieren una revisión integral de permisos antes de operar con comerciantes externos.
