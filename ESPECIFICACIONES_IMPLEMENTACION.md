# Especificaciones de Implementación y Optimización - Condominio SaaS

Este documento contiene el detalle técnico y la arquitectura del trabajo realizado en el proyecto para integrar el frontend con Inertia.js + Vue.js y optimizar la base de datos y la facturación de SaaS.

---

## 🛠️ Arquitectura del Sistema

### 1. Modelo de Datos y Correcciones del Backend
Se corrigieron problemas estructurales y se establecieron relaciones de base de datos robustas:
- **`CondominioScope`**: Mapeado correctamente bajo `App\Scopes\CondominioScope`. Asegura que cada administrador y propietario visualice únicamente los registros asociados a su propio `condominio_id` (mecanismo multi-tenant seguro).
- **`Condominio`**: Modelo Eloquent centralizado que ahora incluye soporte para suscripciones SaaS:
  - `plan_suscripcion` ('gratuito', 'basico', 'premium').
  - `fecha_vencimiento_suscripcion` (fecha de expiración).
  - `estado_suscripcion` ('activo', 'vencido', 'suspendido').
- **Relaciones Completas**:
  - `Condominio` → posee muchos `Apartamentos`, `Users`, `CommonAreas`, `Invoices`, `Payments` y `Reservations`.
  - `Invoice` → pertenece a un `Apartamento` y `Condominio`, y tiene muchos `Payments`.
  - `Payment` → pertenece a un `Invoice`, `Condominio` y es registrado por un `User` (administrador o propietario).
  - `Reservation` → pertenece a un `CommonArea`, `User`, `Apartamento` y `Condominio`.
  - `CommonArea` → pertenece a un `Condominio` y posee muchas `Reservations`.

### 2. Capa de Frontend (Inertia.js + Vue.js 3)
Se integró una experiencia SPA (Single Page Application) sin la complejidad de una API externa, utilizando la pila monolítica de Inertia:
- **Middleware `HandleInertiaRequests`**: Comparte automáticamente el estado del usuario autenticado (con su rol y condominio asociado) y mensajes flash de éxito o error hacia el frontend en cada petición.
- **Vite + Tailwind CSS v4 + Vue**: Configurado en `vite.config.js` para compilar vistas Vue dinámicas y eficientes de forma transparente.
- **Plantilla Argon Dashboard (Tema Claro y Sobrio)**: Diseñado en base a la estructura visual de Argon Dashboard de Creative Tim. Incorpora un Menú Lateral Izquierdo (Sidebar) fijo para la navegación por módulos, un encabezado superior con gradiente azul brillante (`from-blue-600 to-indigo-700`) y tarjetas de métricas/tablas con fondo blanco y sombras suaves sobre un fondo general gris claro (`bg-gray-50`), garantizando una interfaz sumamente seria, limpia y sobria.

### 3. Roles y Dashboards Implementados

#### A. Master Admin
- **Dashboard (`/master-dashboard`)**:
  - Panel para administrar clientes y condominios a nivel de plataforma SaaS.
  - Formulario de creación y edición de condominios.
  - Modificación del plan y estado de suscripción de cada cliente.
  - **Facturación SaaS**: Cálculo dinámico en tiempo real del costo mensual basado en el número de apartamentos:
    - *Plan Básico*: $0.50 mensual por apartamento.
    - *Plan Premium*: $1.00 mensual por apartamento.
    - *Plan Gratuito*: $0.00 mensual.

#### B. Administrador de Condominio
- **Dashboard (`/admin-dashboard`)**:
  - Métricas clave en cabecera (ingresos recaudados, apartamentos ocupados, facturas pendientes, deuda por cobrar).
  - Pestaña de **Apartamentos** con formulario de creación.
  - Pestaña de **Propietarios** que permite dar de alta a un usuario, generarle una cuenta de acceso y asociarlo como propietario de un apartamento de forma integrada.
  - Pestaña de **Facturas** para emitir cobros mensuales de cuotas de mantenimiento.
  - Pestaña de **Pagos** para visualizar el historial bancario y registrar pagos recibidos.
  - Pestaña de **Reservas** para controlar y aprobar/rechazar solicitudes de uso de áreas comunes.

#### C. Propietario / Residente
- **Dashboard (`/owner-dashboard`)**:
  - Resumen visual de deuda pendiente y reservas aprobadas.
  - Pestaña de **Facturas y Pagos**: Permite revisar deudas, ver el historial de aportes y reportar transferencias o pago móvil con referencia bancaria.
  - Pestaña de **Reservas**: Muestra las áreas comunes disponibles, sus costos por uso, capacidades máximas, y permite rellenar solicitudes de reserva detallando el horario deseado.

---

## 🚀 Comandos y Configuración para el Futuro

### 1. Instalación de Dependencias
Si se clona el proyecto en otro entorno, ejecutar:
```bash
# Dependencias PHP
composer install

# Dependencias JS
npm install --legacy-peer-deps
```

### 2. Inicialización de Base de Datos
Para limpiar y sembrar la base de datos con todos los datos y usuarios de prueba (Master, Admin, Propietarios):
```bash
php artisan migrate:fresh --seed
```

### 3. Credenciales de Prueba por Defecto (Unificadas)
El sistema incluye los siguientes usuarios configurados en el seeder con la clave unificada solicitada:
- **Master Admin**:
  - Correo: `master@condominio.com`
  - Contraseña: `123456`
- **Administrador de Condominio (Admin 1)**:
  - Correo: `admin1@condominio.com`
  - Contraseña: `123456`
- **Propietario Asociado (Propietario 1)**:
  - Correo: `owner1@condominio.com`
  - Contraseña: `123456`

### 4. Localización para Venezuela (Bolívares, Tasa de Cambio y CRUD de Bancos)
La plataforma ha sido optimizada para el entorno multidivisa de Venezuela:
- **Bolívar como Moneda Principal**: Toda la contabilidad base de facturas, cuotas y pagos se calcula y almacena en Bolívares (Bs.).
- **Selector Multimoneda VES/USD**: Los dashboards de administrador y propietario incluyen un selector para alternar la visualización del panel completo entre Bolívares (Bs.) y Divisas (USD) en tiempo real.
- **Tasa del Dólar por Pago**: Cada transacción de pago se congela en la base de datos con la tasa de cambio activa del condominio de ese día específico. Las conversiones del historial de pagos se realizan con base en su tasa histórica respectiva.
- **CRUD de Bancos Nacionales**: El Master Admin gestiona desde su panel un catálogo nacional de bancos venezolanos (Banesco, Banco de Venezuela, Mercantil, etc.). Este CRUD alimenta dinámicamente los selectores (`<select>`) del formulario de pagos en las interfaces de administración y propietarios.

### 4. Compilación del Frontend
Para compilar y empaquetar los assets en producción (genera el manifest requerido por Inertia/Laravel):
```bash
npm run build
```

### 5. Desarrollo en Vivo
Para iniciar el servidor de desarrollo y el compilador de assets en vivo en paralelo:
```bash
# Alternativa 1 (usando artisan y vite por separado)
php artisan serve
npm run dev

# Alternativa 2 (ejecuta el script integrado en composer.json)
composer dev
```

### 6. Ejecución de Pruebas
```bash
php artisan test
```

### 7. Sistema de Notificaciones con SweetAlert2
Se ha integrado **SweetAlert2** para gestionar toda la interacción reactiva y visual del usuario:
- **Watcher reactivo de Inertia Flash**: Los mensajes flash de Laravel (`success` y `error`) son detectados automáticamente mediante un watcher de Vue y mostrados en elegantes modales SweetAlert autotemporizados.
- **Ventanas de Confirmación Personalizadas**: Se retiraron los `confirm()` nativos del navegador al eliminar elementos (planes, bancos, etc.), reemplazándolos por cuadros de diálogo de confirmación de SweetAlert interactivos.
- **Login Autenticado**: Alertas en tiempo real en caso de fallos de credenciales o errores generales en la autenticación.

### 8. Lógica de DataTables Reactivos Integrada
Se han implementado funcionalidades avanzadas estilo DataTable en todas las tablas del sistema de forma reactiva nativa en Vue 3 + Tailwind CSS v4:
- **Buscador Dinámico Instantáneo**: Filtrado ultrarrápido al escribir caracteres clave en cualquier atributo de registro.
- **Controles de Registros por Página**: Selector para definir límites (5, 10, 20 o 50 registros visibles a la vez).
- **Indicador Dinámico**: Muestra el total de registros ("Mostrando X a Y de Z registros").
- **Paginador Interactivo**: Navegación ágil mediante botones de Primero, Anterior, Siguiente y Último.
- **Vistas Afectadas**: 
  - *Super Admin*: Clientes SaaS (Condominios), Tarifas y Planes, Catálogo de Bancos, Bitácora de Auditoría.
  - *Administrador*: Apartamentos, Residentes, Facturas, Pagos Recibidos, Reservas.
  - *Propietario*: Recibos y Facturas, Solicitudes de Reserva.
