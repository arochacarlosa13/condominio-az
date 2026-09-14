# 🚀 Hoja de Ruta para Convertir AZPRO en el Sistema #1 del Mercado

> **Documento de Mejoras Prioritarias y Excelencia Operativa**  
> *Fecha de creación: Septiembre del 2026*  
> *Objetivo: Superar a toda la competencia en UX/UI, rigor contable, seguridad criptográfica, automatización y experiencia del residente.*

---

## 📌 Estado de la Plataforma
El sistema cuenta actualmente con una arquitectura moderna en **Laravel 11 + Vue 3 + Vuetify 3**, asistente guiado de emisión en 3 pasos, cobro y notificación por WhatsApp, tablas reactivas con ordenamiento interactivo en todo el sistema, y bimoneda USD/BCV con congelamiento de tasa.

Para consagrarse indiscutiblemente como **el mejor software del mercado**, se deben abordar las siguientes mejoras estratégicas:

---

## 🌟 Fase 1: Integridad de Cálculos y Rigor Contable (El "Cerebro")

- [x] **1.1. Conciliación Bancaria Inteligente**:
  - Permitir al administrador cargar el extracto bancario en formato Excel o CSV (Banesco, Mercantil, BDV, Provincial, etc.).
  - Algoritmo de emparejamiento automático (*auto-matching*) por monto y número de referencia.
  - Aprobación de decenas de pagos en segundos con 0% margen de error humano. *(Completado: ConciliacionBancariaService + ConciliacionBancariaTab.vue + Aprobación por Lote)*

- [x] **1.2. Ajuste Automático de Redondeo al Céntimo ($\pm 0.01$)**:
  - Mecanismo de compensación matemática automática en la última unidad o en el Fondo de Reserva para asegurar que la sumatoria de las cuotas de todas las alícuotas cuadre con exactitud milimétrica al total presupuestado ($100.0000\%$). *(Completado: InvoiceController::generarMasivo balanceo en 2 pasadas)*

- [x] **1.3. Libro de Flujo de Caja en Tiempo Real**:
  - Balance en vivo de tesorería:
    $$\text{Saldo Bancario Disponible} = \text{Total Cobrado en Banco} - \text{Facturas de Contratistas Pagadas}$$
  - Vista gráfica de ingresos reales vs egresos ejecutados por mes. *(Completado: ContabilidadController::flujoCaja + FlujoCajaTab.vue)*

---

## 🎨 Fase 2: UX / UI y Facilidad de Uso (La "Cara")

- [x] **2.1. Portal Móvil Ultraliviano para el Propietario (PWA)**:
  - Vista del residente optimizada para teléfonos móviles (estilo aplicación bancaria nativa).
  - Tarjeta de deuda prominente con monto dual: *"Total adeudado: $25.00 (Bs. 912.50)"*.
  - Botón directo **"Reportar Pago"** con subida de comprobante bancario.
  - Consulta de historial de recibos y solvencias sin descargas obligatorias de PDF. *(Completado: Rediseño Premium OwnerDashboard.vue + Visor de Recibo en Pantalla + Certificado Digital de Solvencia)*

- [x] **2.2. Dashboard Ejecutivo con Semáforos Financieros**:
  - Gráfico de morosidad segmentado por antigüedad (al día, 30 días, 60 días, 90+ días).
  - Gráfico de pastel de distribución de gastos (Servicios, Mantenimiento, Nómina, Suministros).
  - Indicador visual de salud financiera del condominio (Semáforo Verde/Amarillo/Rojo). *(Completado: DashboardController::adminCondominio + AdminDashboard.vue con widgets reactivos)*

- [x] **2.3. Onboarding y Tour Interactivo Guiado**:
  - Integración de guía paso a paso con globos explicativos para administradores primerizos.
  - Reducción de la curva de aprendizaje a cero minutos. *(Completado: GuidedTourDialog.vue + Lanzador en Header AppBar + Persistencia Local)*

---

## 🛡️ Fase 3: Seguridad e Integridad de Datos (La "Armadura")

- [x] **3.1. Sellado Criptográfico SHA-256 y Código QR en PDFs**:
  - Generación de un hash digital inmutable para cada recibo y comprobante emitido.
  - Código QR impreso en el pie de página que permite a cualquier propietario, banco o inquilino escanearlo con la cámara y validar en la plataforma web la autenticidad e integridad del documento. *(Completado: Hasheo SHA-256 en recibos + QR Data URI + Página Pública Blade certificacion_recibo.blade.php)*

- [x] **3.2. Copias de Seguridad (Backups) Automáticas en la Nube**:
  - Tarea programada diaria para exportar la base de datos y archivos a almacenamiento seguro.
  - Botón de descarga de respaldo manual con 1 clic para el Super Administrador. *(Completado: GenerarBackupCommand + Backup diario 02:00 AM + Endpoint /api/v1/auditoria/backup/descargar + Botón 1-clic en AuditoriaLogs.vue)*

- [x] **3.3. Autenticación en Dos Pasos (2FA)**:
  - Protección de acceso para cuentas de Administrador y Super Administrador mediante código temporal. *(Completado: Migración users + Interceptor 2FA en AuthController::login + Endpoint /api/v1/verificar-2fa + Pantalla OTP en Login.vue)*

---

## ⚡ Fase 4: Automatización y Cobranza Preventiva (El "Motor de Crecimiento")

- [x] **4.1. Cobranza Preventiva Automática**:
  - Disparo de notificaciones de cortesía programadas:
    - 3 días antes del vencimiento del recibo.
    - El día del vencimiento con el estado de cuenta y enlace de pago.
  - Reducción drástica de la tasa de morosidad sin fricción humana. *(Completado: CobranzaPreventivaCommand programado 08:00 AM + Disparo manual en NotificacionesList.vue)*

- [x] **4.2. Módulo de Asambleas y Votaciones por Alícuota**:
  - Registro de consultas o proyectos extraordinarios para votación digital (Sí / No / Abstención).
  - Cálculo de quórum instantáneo ponderado por el porcentaje de alícuota de cada copropietario. *(Completado: Modelos Poll, PollOption, Vote + PollController con ponderación legal + Vista AsambleasList.vue + Menú navegación)*

---

*Nota: Este archivo sirve como guía maestra para las próximas jornadas de desarrollo.*
