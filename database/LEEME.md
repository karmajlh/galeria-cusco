# Importación manual en phpMyAdmin

Archivo: `galeria-mariadb-phpmyadmin.sql`. Preparado para el servidor MariaDB 10.11.16 mostrado por el usuario. Crea las siete tablas del proyecto, índices, claves foráneas, restricciones y ocho triggers. Usa InnoDB y utf8mb4. No crea cuentas ni inserta contraseñas o datos de muestra.

1. Selecciona `mixshopc_galeria` en phpMyAdmin y comprueba que no tenga tablas.
2. Abre **Importar**, selecciona este archivo, formato SQL y ejecuta. Alternativamente, copia TODO el archivo en la pestaña SQL, incluidas las instrucciones DELIMITER.
3. Comprueba que termine sin errores y que aparezcan siete tablas y ocho triggers. Si falla, guarda el primer error y no vuelvas a ejecutar todo el archivo: MariaDB puede conservar cambios DDL parciales.

El archivo fija `USE mixshopc_galeria` para evitar crear tablas accidentalmente en otra base. No utiliza DROP ni IF NOT EXISTS que oculte diferencias con tablas existentes. Requiere permisos de creación, modificación y TRIGGER.

Se revisó la estructura del archivo localmente; todavía no se ha ejecutado contra MariaDB. Importar el esquema no habilita acceso remoto: la aplicación Next.js necesita una conexión autorizada, o un despliegue con acceso local a esta base. Tampoco crea el primer SuperAdmin: su contraseña debe generarse con el formato scrypt usado por la aplicación.

## Seguimiento de Prisma

Esta importación manual no registra migraciones en `_prisma_migrations`. No ejecutes después `prisma migrate deploy` directamente sobre esta base. Cuando haya conexión, hay que comparar el esquema importado, validar la compatibilidad MariaDB y registrar las migraciones iniciales mediante el procedimiento de baseline de Prisma antes de aplicar cambios nuevos. No se debe marcar una migración como aplicada si la importación quedó incompleta.

El proyecto aún contiene comprobaciones y CI dirigidos a MySQL 8; su adaptación y pruebas con MariaDB siguen pendientes. Este archivo permite avanzar con la creación manual del esquema, no certifica la integración completa.

Referencias consultadas: [JSON en MariaDB](https://github.com/mariadb-corporation/mariadb-docs/blob/main/server/reference/data-types/string-data-types/json.md), [CREATE TRIGGER](https://github.com/mariadb-corporation/mariadb-docs/blob/main/server/server-usage/triggers-events/triggers/create-trigger.md).
