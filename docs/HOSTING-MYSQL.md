# Conectar el MySQL de tu hosting

Se requiere MySQL 8.0.16 o posterior de la rama 8, incluido 8.4, con InnoDB, JSON y CHECK activos. El adaptador oficial de Prisma se llama `adapter-mariadb`, pero aquí conecta a MySQL.

1. En el panel del hosting, crea una base vacía exclusiva y un usuario asignado a ella. Usa los nombres completos, incluido el prefijo que agrega cPanel. Las migraciones requieren crear/modificar tablas, índices, claves y ocho triggers. Confirma el permiso TRIGGER con el proveedor; sus políticas de binary logging también pueden restringirlo.
2. Solicita el hostname, puerto, acceso remoto desde el servidor de la aplicación y certificado TLS. `localhost` en Vercel/Netlify apunta a la aplicación, no al hosting MySQL. Verifica con ambos proveedores cómo autorizar las direcciones de salida del despliegue.
3. Ejecuta `npm run setup:env`. Crea `.env` con un secreto aleatorio sin sobrescribir archivos existentes. Completa allí DATABASE_URL; no envíes contraseñas por chat ni las subas a GitHub.

```dotenv
DATABASE_URL="mysql://USUARIO:CLAVE_CODIFICADA@HOST:3306/BASE?sslaccept=strict&connection_limit=5"
```

Codifica los caracteres especiales del usuario y contraseña como componentes de URL. Si el hosting usa una CA privada, añade `&sslcert=hosting-ca.pem` y guarda el certificado en `prisma/hosting-ca.pem`. Para el runtime desplegado también puedes suministrar el PEM mediante MYSQL_SSL_CA; Prisma CLI necesita el archivo indicado por sslcert. No desactives la validación TLS.

4. Ejecuta `npm run db:check`: verifica conexión, versión, TLS, tablas y permisos visibles, sin escribir datos ni mostrar credenciales. Este diagnóstico no garantiza todos los permisos de migración.
5. Sobre la base vacía elegida, ejecuta `npm run db:deploy`. No apliques el esquema inicial a una base con datos o tablas existentes. MySQL puede conservar cambios parciales si falla una migración DDL; revisa el error y el estado antes de reintentar.
6. Configura SEED_ADMIN_EMAIL y SEED_ADMIN_PASSWORD temporalmente y ejecuta `npm run db:seed`; luego retíralos. Completa Cloudinary, NEXTAUTH_URL y las demás variables del ejemplo. Conserva NEXTAUTH_SECRET como secreto estable de la instalación.
7. Configura las mismas variables necesarias en Vercel o Netlify. Revisa el límite global de conexiones del hosting: cada instancia puede abrir hasta connection_limit conexiones.

Las pruebas de integración usan otra base con nombre terminado en `_test`, previamente migrada, mediante MYSQL_TEST_DATABASE_URL. Nunca uses producción para pruebas. El workflow crea bases efímeras para MySQL 8.0 y 8.4. El hosting real todavía no se ha conectado ni modificado.
