# 🚀 Guía de Despliegue en Producción - Sistema AZPRO Condominios

Esta guía documenta los pasos exactos para subir y ejecutar la aplicación en cualquier servidor VPS (Ubuntu/Debian/CentOS) o Hosting cPanel/Plesk sin necesidad de modificar el código fuente. Toda la configuración se gestiona mediante el archivo `.env`.

---

## 📋 Requisitos del Servidor
- **PHP**: `>= 8.2` (con extensiones: `pdo`, `pgsql` o `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `gd`, `bcmath`, `curl`).
- **Base de Datos**: PostgreSQL 14+ o MySQL 8.0+ / MariaDB 10.5+.
- **Servidor Web**: Nginx o Apache HTTPD.
- **Node.js**: `>= 18.0` (para compilar los assets del frontend Vite).
- **Composer**: `>= 2.0`.

---

## 🛠️ Pasos para el Despliegue

### 1. Clonar o subir el código al servidor
```bash
cd /var/www/html/condominio-az # o el directorio correspondiente en tu servidor
```

### 2. Configurar el archivo de variables de entorno `.env`
Copia el archivo de ejemplo y ajusta los parámetros de producción:
```bash
cp .env.example .env
nano .env
```

**Parámetros Clave en `.env`:**
```ini
APP_NAME="Sistema AZPRO Condominios"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tudominio.com
FORCE_HTTPS=true

# Configuración de Base de Datos (PostgreSQL o MySQL)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=condominiosaas
DB_USERNAME=postgres
DB_PASSWORD=tu_clave_segura
```

### 3. Instalar dependencias backend PHP
```bash
composer install --no-dev --optimize-autoloader
```

### 4. Generar la clave de la aplicación
```bash
php artisan key:generate
```

### 5. Ejecutar migraciones y sembrado inicial de datos
```bash
php artisan migrate --force
php artisan db:seed --force
```

### 6. Crear enlace simbólico para imágenes y documentos subidos
```bash
php artisan storage:link
```

### 7. Compilar los recursos del Frontend (Vue 3 + Vuetify)
```bash
npm install
npm run build
```

### 8. Optimizar la caché de Laravel para alto rendimiento
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🌐 Configuración del Servidor Web

### Opción A: Configuración para Nginx
Crea o edita el archivo `/etc/nginx/sites-available/condominio-az`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name tudominio.com www.tudominio.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name tudominio.com www.tudominio.com;

    root /var/www/html/condominio-az/public;
    index index.php index.html;

    # Certificados SSL (Let's Encrypt / Certbot)
    ssl_certificate /etc/letsencrypt/live/tudominio.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/tudominio.com/privkey.pem;

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

### Opción B: Apache / cPanel
- Asegúrate de que el **DocumentRoot** del dominio apunte al directorio `/public` de la aplicación.
- El archivo `.htaccess` ya está incluido en `/public` y gestionará el enrutamiento de Vue SPA y la API Laravel automáticamente.

---

## ⚡ Comandos para Futuras Actualizaciones
Cuando subas una actualización o cambio al servidor, simplemente ejecuta:
```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
