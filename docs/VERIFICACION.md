# Verificación de la adaptación a MySQL

Fecha: 21 de octubre de 2026.

- Prisma valida el esquema MySQL y genera el cliente. La migración inicial se genera desde ese esquema; una segunda añade restricciones CHECK y ocho triggers.
- `npm run build`: compilación de producción y comprobación TypeScript completadas correctamente.
- `npm test`: 10 pruebas unitarias aprobadas (permisos, dinero, estados, contraseñas, conexión TLS y listas JSON).
- La prueba de integración MySQL se omite cuando falta MYSQL_TEST_DATABASE_URL. No se ejecutaron las migraciones contra un servidor MySQL en esta máquina.
- `npm run db:check`: detecta DATABASE_URL vacía y termina indicando la configuración pendiente, sin intentar conexiones.
- `.env` creado con secreto aleatorio y exclusión de Git comprobada. No contiene credenciales de hosting.
- CI preparado para aplicar migraciones y ejecutar integración sobre MySQL 8.0 y 8.4. Todavía no ejecutado en GitHub.

La revisión anterior verificó rutas públicas, redirecciones de paneles sin sesión, búsqueda y carrito con catálogo de muestra. Esas comprobaciones no certifican conexión MySQL, sesiones reales, Cloudinary ni el checkout completo con servicios externos.

Pendiente: configurar el hosting, verificar su versión, TLS y permisos, ejecutar las migraciones sobre una base vacía y comprobar el flujo completo. También faltan pruebas en dispositivos físicos y despliegue remoto.

El repositorio local está en `main`, sin commit ni remoto. El documento Word preexistente no fue modificado.
