# 📘 Manual de Despliegue en Producción - Sistema Inteligente de Gestión de Condominios AZPRO

Este documento contiene la guía completa paso a paso y la especificación técnica para desplegar la aplicación en cualquier entorno de producción (**Hosting Compartido cPanel / Plesk**, **Servidores VPS Cloud** o **Hosting Administrado**) sin necesidad de modificar el código fuente del proyecto.

---

## 📑 Contenido
1. [Requisitos Previos del Servidor](#1-requisitos-previos-del-servidor)
2. [Estructura del Archivo `.env` para Producción](#2-estructura-del-archivo-env-para-producción)
3. [Despliegue Paso a Paso (Método SSH / VPS / Terminal)](#3-despliegue-paso-a-paso-método-ssh--vps--terminal)
4. [Despliegue Paso a Paso (Método Hosting Compartido / cPanel sin SSH)](#4-despliegue-paso-a-paso-método-hosting-compartido--cpanel-sin-ssh)
5. [Configuración de Servidores Web (Nginx y Apache)](#5-configuración-de-servidores-web-nginx-y-apache)
6. [Mantenimiento y Futuras Actualizaciones](#6-mantenimiento-y-futuras-actualizaciones)

---

## 1. Requisitos Previos del Servidor

Para que el sistema funcione de manera óptima, el servidor debe cumplir con los siguientes requerimientos:

- **Versión de PHP**: `>= 8.2`
- **Extensiones PHP obligatorias**:
  - `pdo`
  - `pgsql` (si usas PostgreSQL) o `pdo_mysql` (si usas MySQL / MariaDB)
  - `mbstring`
  - `openssl`
  - `tokenizer`
  - `xml`
  - `gd`
  - `bcmath`
  - `curl`
  - `fileinfo`
- **Base de Datos**: PostgreSQL 14+ o MySQL 8.0+ / MariaDB 10.5+.
- **Node.js**: `>= 18.0` (para compilar los assets del frontend Vite).
- **Composer**: `>= 2.0`.

---

## 2. Estructura del Archivo `.env` para Producción

Crea un archivo llamado `.env` en la raíz del proyecto en el servidor utilizando la siguiente plantilla ajustada a tu dominio y base de datos:

```ini
# ==============================================================================
# CONFIGURACIÓN GENERAL DEL SISTEMA AZPRO (PRODUCCIÓN)
# ==============================================================================
APP_NAME="Sistema AZPRO Condominios"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://micondominio.com
FORCE_HTTPS=true

# Idioma y Zona Horaria
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_VE
APP_TIMEZONE=America/Caracas

# Logs
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

# ==============================================================================
# CONFIGURACIÓN DE BASE DE DATOS
# ==============================================================================
# Opción A: Para PostgreSQL en Producción
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=nombre_de_tu_bd
DB_USERNAME=usuario_postgres
DB_PASSWORD=tu_clave_segura_postgres

# Opción B: Para MySQL / MariaDB en Producción (Descomentar si usas MySQL)
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=nombre_de_tu_bd
# DB_USERNAME=usuario_mysql
# DB_PASSWORD=tu_clave_segura_mysql

# ==============================================================================
# SESIONES, CACHÉ Y ALMACENAMIENTO
# ==============================================================================
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

FILESYSTEM_DISK=public
QUEUE_CONNECTION=database
CACHE_STORE=database

# ==============================================================================
# CONFIGURACIÓN DE CORREO ELECTRÓNICO (SMTP)
# ==============================================================================
MAIL_MAILER=smtp
MAIL_HOST=smtp.tu-servidor-correo.com
MAIL_PORT=587
MAIL_USERNAME=notificaciones@micondominio.com
MAIL_PASSWORD=tu_clave_smtp
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="notificaciones@micondominio.com"
MAIL_FROM_NAME="${APP_NAME}"

# ==============================================================================
# INTEGRACIÓN FRONTEND VITE
# ==============================================================================
VITE_APP_NAME="${APP_NAME}"
VITE_API_BASE_URL=/api/v1
```

---

## 3. Despliegue Paso a Paso (Método SSH / VPS / Terminal)

Si tienes acceso SSH a tu servidor (DigitalOcean, Linode, AWS, Hetzner, VPS cPanel), ejecuta los siguientes comandos:

### Paso 1: Clonar o subir el proyecto al servidor
```bash
cd /var/www/html # o el directorio web de tu servidor
git clone <URL_DE_TU_REPOSITORIO> condominio-az
cd condominio-az
```

### Paso 2: Crear el archivo `.env`
```bash
cp .env.example .env
nano .env
```
*(Ingresa tus credenciales reales de base de datos y dominio, guarda con `Ctrl + O` y sal con `Ctrl + X`)*.

### Paso 3: Instalar dependencias PHP para producción
```bash
composer install --no-dev --optimize-autoloader
```

### Paso 4: Generar la clave de encriptación de la aplicación
```bash
php artisan key:generate
```

### Paso 5: Ejecutar migraciones y sembrado inicial de datos
```bash
php artisan migrate --force
php artisan db:seed --force
```

### Paso 6: Crear el enlace simbólico de almacenamiento público
```bash
php artisan storage:link
```

### Paso 7: Compilar los assets del Frontend (Vue 3 + Vuetify + Tailwind)
```bash
npm install
npm run build
```

### Paso 8: Optimizar la caché de Laravel
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 4. Despliegue Paso a Paso (Método Hosting Compartido / cPanel sin SSH)

Si tu hosting es un cPanel tradicional sin acceso SSH:

### Paso 1: Compilar localmente en tu computadora
En la consola de tu equipo local, dentro de la carpeta del proyecto, ejecuta:
```bash
npm run build
```
Esto generará los archivos finales en la carpeta `public/build`.

### Paso 2: Empaquetar el proyecto
Crea un archivo `.zip` comprimiendo todo el contenido de la carpeta del proyecto **EXCEPTUANDO** la carpeta `node_modules`.

### Paso 3: Subir a cPanel Administrador de Archivos
1. Ingresa a tu cPanel -> **Administrador de Archivos (File Manager)**.
2. Sube el `.zip` a la raíz de tu hosting (fuera de `public_html` si deseas mayor seguridad, o dentro de un subdirectorio).
3. Extrae el contenido del archivo `.zip`.

### Paso 4: Configurar la Base de Datos en cPanel
1. Ingresa a **Bases de datos MySQL® / PostgreSQL®** en cPanel.
2. Crea la base de datos y el usuario con su respectiva contraseña.
3. Asigna todos los privilegios al usuario sobre la base de datos.
4. Importa la estructura inicial o ejecuta la migración desde el terminal web de cPanel si está disponible.

### Paso 5: Apuntar el Dominio a la carpeta `/public`
- En el panel de dominios o subdominios de cPanel, configura el **Document Root** para que apunte a:
  `/ruta-de-tu-proyecto/public`

### Paso 6: Configurar `.env` en el Administrador de Archivos
Abre el Administrador de Archivos, edita el `.env` e ingresa tus credenciales de base de datos creadas en el Paso 4.

---

## 5. Configuración de Servidores Web (Nginx y Apache)

### Configuración para Nginx (VPS)
Crea un archivo de sitio en `/etc/nginx/sites-available/condominio-az`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name micondominio.com www.micondominio.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name micondominio.com www.micondominio.com;

    root /var/www/html/condominio-az/public;
    index index.php index.html;

    # Certificados SSL de Let's Encrypt / Certbot
    ssl_certificate /etc/letsencrypt/live/micondominio.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/micondominio.com/privkey.pem;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Configuración para Apache (`public/.htaccess`)
El archivo `.htaccess` ya está incluido en la carpeta `/public` con la siguiente regla para soportar enrutamiento SPA y peticiones REST API:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

---

## 6. Mantenimiento y Futuras Actualizaciones

Cuando realices cambios en el código o despliegues nuevas funciones a producción, ejecuta los siguientes comandos para actualizar el servidor sin caídas del servicio:

```bash
# 1. Obtener la última versión del código
git pull origin main

# 2. Actualizar dependencias PHP sin afectar desarrollo
composer install --no-dev --optimize-autoloader

# 3. Ejecutar migraciones pendientes
php artisan migrate --force

# 4. Recompilar los componentes Vue 3 / Vite
npm run build

# 5. Limpiar y refrescar la caché de producción
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

*¡El Sistema AZPRO está listo para operar en cualquier hosting o VPS en producción!*
