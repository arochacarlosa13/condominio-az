# Sistema Integral de Cuentas, Métodos de Cobro y Registro Adaptativo de Pagos

Este documento detalla la arquitectura técnica, base de datos, flujos de usuario y reglas de negocio del sistema de gestión de cuentas bancarias y notificación/registro adaptativo de pagos en **Condominio AZ**.

---

## 1. Módulos y Arquitectura

### 🏛️ 1. Configuración de Cuentas y Canales de Cobro del Edificio
- **Ubicación:** `resources/js/views/Condominios/CondominiosList.vue`
- **Controlador API:** `app/Http/Controllers/Api/v1/CondominioCuentaController.php`
- **Modelo:** `app/Models/CondominioCuentaBancaria.php`
- **Rutas API:**
  - `GET /api/v1/condominios/cuentas-bancarias` (Filtro `?solo_activas=1` para selectores de pago)
  - `POST /api/v1/condominios/cuentas-bancarias`
  - `PUT /api/v1/condominios/cuentas-bancarias/{id}`
  - `DELETE /api/v1/condominios/cuentas-bancarias/{id}`
  - `GET /api/v1/bancos` (Catálogo ordenado de bancos de Venezuela)

#### Canales de Pago Disponibles:
1. 📱 **Pago Móvil (Bs. VES):** Banco, Teléfono, Cédula/RIF y Titular.
2. 🏦 **Transferencia Bancaria (Bs. VES / USD):** Banco, Tipo de Cuenta (Corriente/Ahorros), Moneda, Número de Cuenta (20 dígitos), Titular y RIF.
3. 💵 **Caja de Efectivo en Divisas ($ USD / EUR):** Punto de recepción física (Oficina / Conserjería) e instrucciones de entrega.
4. 🇻🇪 **Caja de Efectivo en Bolívares (Bs. VES):** Punto de recepción física e instrucciones.
5. 🌐 **Zelle / Canales Digitales ($ USD):** Correo electrónico del titular y nombre del beneficiario.

---

### 💳 2. Notificación y Registro Adaptativo de Pagos
- **Ubicación:** `resources/js/views/Pagos/PagosList.vue`
- **Controlador API:** `app/Http/Controllers/Api/v1/PaymentController.php`
- **Modelo:** `app/Models/Payment.php`

#### Flujo para el Administrador:
1. **Selector de Inmueble:** Inicia vacío (`null`) y oculta la lista de recibos por defecto hasta que el usuario elija un apartamento.
2. **Preselección Automática:** Al seleccionar el inmueble, carga todos sus recibos impagos y los marca preseleccionados por defecto, calculando el total acumulado en **Bs.** y **USD**.
3. **Opción de Aprobación Inmediata:** Checkbox *"⚡ Aprobar y emitir Recibo Oficial RP inmediatamente"* para saldar la deuda en tiempo real si el dinero ya fue verificado o recibido en mano.

#### Flujo para el Copropietario:
- Visualiza únicamente sus recibos adeudados.
- Selector interactivo de **5 Métodos de Pago**:
  - **Pago Móvil:** Muestra los datos del condominio con botón **"Copiar Datos"**, y solicita Banco Emisor, Teléfono Celular Emisor, N° de Aprobación, Fecha, Monto en Bs. y Comprobante.
  - **Transferencia Bancaria:** Muestra el número de cuenta con botón **"Copiar N° Cuenta"**, y solicita Banco Emisor, N° de Transferencia, Fecha, Monto y Comprobante.
  - **Efectivo en Divisas ($ USD):** Solicita monto en dólares y realiza la **conversión automática a Bolívares (Bs.)** a la tasa oficial BCV para el registro contable. Muestra indicaciones de entrega en oficina.
  - **Efectivo en Bolívares (Bs. VES):** Solicita monto en Bolívares con instrucciones de entrega en caja.
  - **Zelle / Otros ($ USD):** Muestra el correo Zelle del edificio con botón de copiado y solicita confirmación del emisor.

---

## 2. Validaciones Indispensables de Notificación

| Campo / Regla | Condición | Mensaje / Acción |
| :--- | :--- | :--- |
| **Inmueble / Copropietario** | Obligatorio para Administrador | *"Por favor selecciona primero el inmueble / copropietario."* |
| **Recibos Seleccionados** | Al menos 1 recibo | *"Debe seleccionar al menos un recibo de cobro a saldar."* |
| **Monto** | Mayor a `0.00` | *"Debe ingresar un monto válido a pagar (mayor a 0)."* |
| **Fecha de Pago** | Fecha válida | *"Debe indicar la fecha del pago."* |
| **Pago Móvil** | Banco emisor, Teléfono (mín. 7 dígitos), Referencia (mín. 4 dígitos) | Validaciones específicas antes del envío |
| **Transferencia** | Banco emisor y Número de referencia (mín. 4 dígitos) | Validación de campos obligatorios |
| **Zelle** | Correo emisor, Código de confirmación (mín. 3 dígitos), Monto en USD | Validación de campos en divisas |
| **Efectivo Divisas** | Monto en USD mayor a `0.00` | Conversión contable automática a Bs. a tasa BCV |

---

## 3. Base de Datos y Migraciones

### 📄 Migración `2026_08_29_000002_add_extended_payment_details_to_payments.php`
Agrega a la tabla `payments`:
- `banco_origen` (nullable string)
- `telefono_origen` (nullable string)
- `cedula_origen` (nullable string)
- `moneda_origen` (string, default 'VES')
- `monto_divisa` (nullable decimal 12,2)
- `comprobante_path` (nullable string)

### 📄 Migración `2026_08_30_000001_alter_metodo_pago_in_payments_table.php`
- Elimina la restricción `payments_metodo_pago_check` en PostgreSQL.
- Convierte la columna `metodo_pago` a `VARCHAR(50)` para soportar libremente `efectivo_usd`, `efectivo_ves`, `zelle`, `pago_movil`, `transferencia`, etc.

---

## 4. Conciliación Contable y Emisión Oficial (`viewDialog`)

En el modal de detalles de pago se presenta una división transparente:
1. **🏛️ Canal Receptor del Condominio:** Banco, cuenta o caja física donde ingresaron los fondos.
2. **📤 Datos de Emisión del Copropietario:** Banco emisor, teléfono, referencia, fecha y enlace para consultar el comprobante digital adjunto.
3. **Acciones Disponibles:**
   - Previsualizar Borrador RP (PDF Iframe en tiempo real).
   - Rechazar pago con justificación obligatoria (mínimo 40 caracteres).
   - Aprobar y emitir Recibo RP oficial.
   - **Envío Automático de Correo Electrónico:** Al aprobar el pago, se despacha automáticamente el Recibo Oficial RP (`emails/recibo_pago.blade.php`) al correo del propietario con el detalle de facturas saldadas, montos en Bs./USD y enlace de descarga PDF.
   - **Botón Reenviar Recibo RP por Correo:** Disponible tanto en la tabla principal como en el modal de detalles para que el administrador pueda reenviar el comprobante en cualquier momento (`POST /api/v1/payments/{payment}/reenviar-email`).
   - Descargar Recibo Oficial RP en PDF.
