# Instalación en el hosting de Galería Cusco

Destino: `/home/mixshopc/galeria-cusco`, dominio `https://galeriacusco.info.gf`, Node 22.18.0, Production, inicio `app.js`.

## Estado confirmado

El usuario importó el SQL en MariaDB 10.11.16. La salida de phpMyAdmin confirma siete tablas y ocho triggers, además de claves y CHECK sin errores. No repetir la importación ni ejecutar `db:deploy`: esta instalación manual no tiene historial de Prisma; antes de futuras migraciones requiere comparación y baseline.

## Subir e instalar

1. Detener esta aplicación en Setup Node.js App. Conservar las carpetas `tmp` y `public` que creó cPanel. Subir el ZIP y extraer su contenido directamente en la raíz indicada: `package.json` y `app.js` deben quedar allí, sin una carpeta adicional. El app.js de prueba se reemplaza por el del proyecto.
2. El paquete incluye fuentes y lockfile; excluye `.env`, node_modules, .next y credenciales. Las dependencias y la compilación deben generarse en Linux, no copiarse desde Windows.
3. En Setup Node.js App, establecer temporalmente `NPM_CONFIG_INCLUDE=dev` para instalar también Prisma, TypeScript y las herramientas de compilación, aunque NODE_ENV sea production. Pulsar Ejecutar NPM Install.
4. En la opción para ejecutar scripts npm, ejecutar `build`. Si la interfaz no ofrece esa opción, usar el terminal cPanel o SSH activando primero el entorno virtual que muestra el panel. Ejecutar `npm install --include=dev` y `npm run build`. Si no hay terminal ni ejecutor de scripts, detenerse y consultar al proveedor. No arrancar una compilación desde una ruta web.
5. Configurar las variables siguientes y ejecutar el script `db:check` desde el hosting. Debe mostrar conexión local, siete tablas y ocho triggers. Este diagnóstico no prueba todo el checkout.
6. Generar NEXTAUTH_SECRET con `npm run setup:env` en el hosting, si se dispone de ejecutor, y completar el archivo .env creado desde el administrador de archivos; alternativamente establecer los valores directamente en Environment variables. No sobrescribir un secreto existente ni compartirlo. Las variables del panel tienen precedencia sobre .env.
7. Crear el primer SuperAdmin con `db:seed` usando temporalmente SEED_ADMIN_EMAIL y SEED_ADMIN_PASSWORD (12 caracteres o más); retirar estas dos variables después. Configurar Cloudinary antes de probar vouchers.
8. Iniciar/reiniciar la aplicación y comprobar HTTPS, catálogo, login y acceso administrativo. El build y las pruebas locales no certifican Passenger ni la conexión real del hosting.

## Variables para esta instalación

```dotenv
DATABASE_URL="mysql://mixshopc_admin:CLAVE_CODIFICADA@127.0.0.1:3306/mixshopc_galeria?connection_limit=3"
NEXTAUTH_URL="https://galeriacusco.info.gf"
NEXTAUTH_SECRET="SECRETO_ALEATORIO_DE_AL_MENOS_32_CARACTERES"
```

Codificar caracteres reservados de la contraseña como componente URL. No añadir sslaccept=strict a esta conexión local: MariaDB no tiene TLS habilitado y el tráfico permanece dentro del servidor. Las conexiones remotas conservan validación TLS obligatoria. Completar las variables Cloudinary de `.env.example`; no cargar el .env de desarrollo con la IP remota anterior.

## Referencias de arranque

El archivo app.js usa el servidor personalizado documentado por Next.js y selecciona explícitamente el servidor HTTP de Passenger: [Passenger reverse port binding](https://www.phusionpassenger.com/docs/advanced_guides/in_depth/node/reverse_port_binding.html). Se conserva el build normal de Next.js, sin output standalone.
