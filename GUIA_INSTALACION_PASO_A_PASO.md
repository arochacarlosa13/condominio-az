# 📘 Guía Completa de Instalación y Despliegue - Condominio SaaS (AZ)

Esta guía detalla paso a paso cómo clonar, configurar y poner en marcha el sistema **Condominio SaaS** en cualquier computadora desde cero (Windows, macOS o Linux).

---

## 📋 1. Requisitos Previos del Sistema

Asegúrate de contar con los siguientes programas instalados en la PC:

1. **PHP**: Versión `>= 8.2` o `8.3` (con extensiones habilitadas: `curl`, `fileinfo`, `gd`, `mbstring`, `openssl`, `pdo_pgsql`, `pgsql`, `pdo_sqlite`, `sqlite3`, `zip`).
2. **Composer**: Gestor de dependencias de PHP (`>= 2.5`).
3. **Node.js**: Versión LTS (`>= 20` o `24`) con `npm`.
4. **Base de Datos**: **PostgreSQL** (`v15+` o `v17`) o **MySQL** / **SQLite**.
5. **Git** (Opcional pero recomendado para control de versiones).

---

## ⚡ 2. Instalación Rápida Automática en Windows (PowerShell)

Si estás en una PC con Windows 10/11 nueva, puedes ejecutar el script automatizado que hemos dejado en el proyecto:

```powershell
# Ejecutar como Administrador en PowerShell:
powershell -ExecutionPolicy Bypass -File .\setup_windows.ps1
```

Este script descarga PHP 8.3, habilita sus extensiones, instala Composer, descarga Node.js si falta, y agrega todo al `PATH`.

---

## 🛠️ 3. Instalación Paso a Paso (Manual)

### Paso 1: Clonar o Copiar el Repositorio
Copia la carpeta del proyecto en la nueva PC o clónala con Git:
```bash
git clone <url-del-repositorio> condominio-az
cd condominio-az
```

---

### Paso 2: Configurar las Variables de Entorno (`.env`)
Si no existe el archivo `.env`, crea una copia desde `.env.example`:
```bash
cp .env.example .env
```

Abre `.env` y configura la base de datos (por ejemplo, con PostgreSQL):
```dotenv
APP_NAME=CondominioSaaS
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

# Conexión PostgreSQL:
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=condominiosaas
DB_USERNAME=postgres
DB_PASSWORD=tu_clave_aqui
```

---

### Paso 3: Instalar Dependencias de PHP y JavaScript
Ejecuta en la terminal:
```bash
# 1. Dependencias PHP (Laravel)
composer install

# 2. Dependencias JavaScript (Vue 3, Inertia, Tailwind v4, SweetAlert2, Vuetify)
npm install --legacy-peer-deps
```

---

### Paso 4: Generar la Clave de la Aplicación
```bash
php artisan key:generate
```

---

### Paso 5: Crear la Base de Datos y Ejecutar Migraciones con Datos de Prueba
1. Abre tu gestor de base de datos (pgAdmin, psql, DBeaver) y crea la base de datos `condominiosaas`.
   ```sql
   CREATE DATABASE condominiosaas;
   ```
2. Corre las migraciones y seeders de Laravel para crear las tablas y los usuarios iniciales:
   ```bash
   php artisan migrate:fresh --seed
   ```

---

### Paso 6: Compilar los Recursos Frontend (Vite)
Compila los componentes Vue y estilos para la primera ejecución:
```bash
npm run build
```

---

## 🚀 4. Cómo Iniciar el Proyecto para Trabajar (Día a Día)

Para trabajar en el proyecto necesitas dos procesos corriendo:

### Opción A (Recomendada - Dos terminales):
* **Terminal 1 (Servidor Backend Laravel):**
  ```bash
  php artisan serve
  ```
  *(Disponible en `http://127.0.0.1:8000`)*

* **Terminal 2 (Compilador Frontend en Tiempo Real):**
  ```bash
  npm run dev
  ```

---

### Opción B (Comando Integrado de Composer):
```bash
composer dev
```

---

## 🔑 5. Credenciales de Acceso por Defecto

El seeder inicial genera los 3 roles principales con la misma contraseña de prueba (`123456`):

| Rol | Correo Electrónico | Contraseña | Descripción / URL |
| :--- | :--- | :--- | :--- |
| **Master Admin** | `master@condominio.com` | `123456` | Administra la plataforma SaaS, condominios, planes y bancos (`/master-dashboard`). |
| **Administrador** | `admin1@condominio.com` | `123456` | Administra el condominio, apartamentos, propietarios, facturación y pagos (`/admin-dashboard`). |
| **Propietario** | `owner1@condominio.com` | `123456` | Consulta deudas, reporta transferencias/pago móvil y reserva áreas comunes (`/owner-dashboard`). |

---

## 🧪 6. Ejecución de Pruebas Unitarias

Para verificar que todo el sistema y la lógica de negocio funcionen correctamente:
```bash
php artisan test
```

---

## ⚠️ 7. Solución de Problemas Frecuentes

1. **Error: `Call to undefined function pg_connect()` o `pdo_pgsql driver not found`**:
   - Asegúrate de que en `php.ini` tengas descomentadas las líneas:
     ```ini
     extension_dir = "ext"
     extension=pdo_pgsql
     extension=pgsql
     ```
2. **Error: `Vite manifest not found`**:
   - Corre `npm run build` o mantén `npm run dev` en ejecución.
3. **Problemas con caché de configuración**:
   - Limpia la caché ejecutando:
     ```bash
     php artisan config:clear
     php artisan route:clear
     php artisan view:clear
     ```
