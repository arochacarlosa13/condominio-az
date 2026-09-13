<template>
  <div class="min-h-screen bg-gray-50 text-slate-700 flex font-sans">
    <!-- SIDEBAR LATERAL (Estilo Argon Dashboard) -->
    <aside class="w-64 bg-white border-r border-gray-200 flex flex-col fixed inset-y-0 left-0 z-30 shadow-sm">
      <!-- Logo / Marca -->
      <div class="h-16 flex items-center gap-3 px-6 border-b border-gray-100">
        <span class="text-xl font-black bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">AZPRO</span>
        <span class="bg-emerald-100 text-emerald-800 text-[9px] uppercase font-extrabold tracking-widest px-2 py-0.5 rounded">Residente</span>
      </div>

      <!-- Info del Residente y Apto -->
      <div v-if="propietario" class="px-6 py-4 bg-gray-50/50 border-b border-gray-100">
        <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Mi Hogar</p>
        <p class="text-sm font-bold text-gray-800 truncate mt-0.5">Apto: {{ apartamentos[0]?.numero }}</p>
        <p class="text-[10px] text-gray-500 mt-1">Piso {{ apartamentos[0]?.piso }} · {{ propietario.condominio?.nombre }}</p>
      </div>

      <!-- Enlaces de Navegación Lateral -->
      <nav class="flex-1 px-4 py-4 space-y-1">
        <div class="text-[10px] uppercase font-bold tracking-wider text-gray-400 px-3 mb-2">Mi Cuenta</div>
        
        <button @click="activeTab = 'facturas'"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-all"
          :class="activeTab === 'facturas' ? 'bg-blue-50 text-blue-600 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          Facturas y Pagos
        </button>

        <button @click="activeTab = 'reservas'"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-all"
          :class="activeTab === 'reservas' ? 'bg-blue-50 text-blue-600 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          Reservar Áreas
        </button>
      </nav>

      <!-- Pie del Sidebar con Perfil -->
      <div class="p-4 border-t border-gray-100 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-sm shadow">
            {{ (propietario?.nombre_completo || $page.props.auth.user.name).charAt(0) }}
          </div>
          <div class="overflow-hidden">
            <p class="text-xs font-bold text-gray-800 truncate">{{ propietario?.nombre_completo || $page.props.auth.user.name }}</p>
            <p class="text-[10px] text-gray-400 uppercase truncate">Propietario</p>
          </div>
        </div>
        <button @click="logout" class="text-gray-400 hover:text-rose-500 transition-colors" title="Cerrar Sesión">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
        </button>
      </div>
    </aside>

    <!-- CONTENEDOR PRINCIPAL -->
    <div class="flex-1 flex flex-col pl-64">
      <!-- HEADER CON GRADIENTE (Estilo Argon) -->
      <div class="bg-gradient-to-r from-blue-600 to-indigo-700 pb-36 pt-8 px-8 relative">
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
          <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Mi Portal</h1>
            <p class="text-blue-100 text-xs mt-1">Consulta tus estados de cuenta, reporta pagos y solicita reservas.</p>
            <div class="mt-2 text-[11px] text-blue-200">
              Tasa del día del condominio: <strong class="text-white">Bs. {{ parseFloat(propietario?.condominio?.tasa_cambio || 36.50).toFixed(2) }}</strong>
            </div>
          </div>

          <!-- Dualidad de Moneda y Configuración de Tasa de Cambio (Venezuela) -->
          <div class="flex flex-col sm:flex-row gap-4 items-stretch sm:items-center w-full lg:w-auto">
            <!-- Toggle de Visualización -->
            <div class="bg-white/10 backdrop-blur-md border border-white/20 px-3 py-1.5 rounded-xl flex items-center justify-between gap-3">
              <span class="text-[9px] uppercase font-bold text-blue-200 block">Moneda:</span>
              <div class="flex bg-black/20 p-0.5 rounded-lg border border-white/10">
                <button @click="currency = 'VES'" class="px-2.5 py-0.5 text-xs font-bold rounded-md transition-all" :class="currency === 'VES' ? 'bg-white text-blue-700 shadow' : 'text-blue-200 hover:text-white'">Bs</button>
                <button @click="currency = 'USD'" class="px-2.5 py-0.5 text-xs font-bold rounded-md transition-all" :class="currency === 'USD' ? 'bg-white text-blue-700 shadow' : 'text-blue-200 hover:text-white'">USD</button>
              </div>
            </div>

            <!-- Card Deuda Total -->
            <div class="bg-white border border-gray-150 px-4 py-2.5 rounded-xl text-center shadow-sm">
              <span class="text-[10px] uppercase font-bold text-gray-400 block">Mi Deuda Pendiente</span>
              <span class="text-base font-black text-rose-600">{{ formatAmount(totalDeudaPendiente) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- CONTENIDO PRINCIPAL FLOTANTE -->
      <div class="px-8 -mt-24 pb-12 flex-1 space-y-8 z-10">
        <!-- El sistema utiliza SweetAlert para las notificaciones en tiempo real -->

        <div v-if="!propietario" class="bg-amber-50 border border-amber-200 text-amber-800 p-6 rounded-xl shadow-sm">
          <h4 class="text-base font-bold mb-1">Falta asignación de apartamento</h4>
          <p class="text-sm">Tu cuenta de usuario no está asociada a ningún apartamento aún. Por favor, comunícate con la administración de tu condominio para que te asignen tu apartamento.</p>
        </div>

        <!-- Alerta de cuotas vencidas (Venezuela UX/UI) -->
        <div v-if="propietario && tieneCuotasVencidas" class="bg-gradient-to-r from-rose-50 to-white border border-rose-200 text-rose-900 p-5 rounded-xl shadow-sm flex flex-col sm:flex-row items-start sm:items-center gap-4">
          <span class="text-2xl">⚠️</span>
          <div>
            <h4 class="font-extrabold text-sm text-rose-800">ATENCIÓN: TIENE CUOTAS DE MANTENIMIENTO VENCIDAS</h4>
            <p class="text-xs text-rose-700 mt-0.5">Por favor regularice su situación a la brevedad. Puede reportar su pago vía transferencia o pago móvil desde el botón lateral de cada factura.</p>
          </div>
        </div>

        <!-- 1. TAB: FACTURAS Y HISTORIAL -->
        <div v-if="propietario && activeTab === 'facturas'" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Listado de Facturas (Izquierda) -->
          <div class="lg:col-span-2 space-y-4">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
              <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <h3 class="font-bold text-gray-800">Mis Recibos y Facturas</h3>
                
                <div class="flex items-center gap-3">
                  <div class="flex items-center gap-1.5 text-xs text-gray-500">
                    <span>Mostrar:</span>
                    <select v-model="invPerPage" class="bg-white border border-gray-200 rounded px-2 py-1 text-gray-700 outline-none focus:border-blue-500 font-semibold">
                      <option :value="5">5</option>
                      <option :value="10">10</option>
                      <option :value="20">20</option>
                    </select>
                  </div>
                  <div class="relative max-w-xs w-full">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                      </svg>
                    </span>
                    <input type="text" v-model="invSearch" placeholder="Buscar factura..." class="w-full pl-9 pr-4 py-1.5 bg-white border border-gray-200 rounded-lg text-xs outline-none focus:border-blue-500 transition-all font-semibold" />
                  </div>
                </div>
              </div>
              <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                  <thead>
                    <tr class="bg-gray-50/50 text-gray-400 text-[11px] uppercase tracking-wider border-b border-gray-200">
                      <th class="px-6 py-3">Número</th>
                      <th class="px-6 py-3">Concepto</th>
                      <th class="px-6 py-3">Monto Total</th>
                      <th class="px-6 py-3">Monto Pendiente</th>
                      <th class="px-6 py-3">Estado</th>
                      <th class="px-6 py-3 text-right">Acción</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100 text-sm text-gray-600">
                    <tr v-for="inv in paginatedInvoices" :key="inv.id" class="hover:bg-gray-50/60 transition-colors">
                      <td class="px-6 py-4 font-mono text-xs">
                        <div class="font-bold text-gray-900">{{ inv.numero_factura }}</div>
                        <div class="text-[10px] text-gray-400 mt-0.5">Vence: {{ formatDate(inv.fecha_vencimiento) }}</div>
                      </td>
                      <td class="px-6 py-4">
                        <div class="text-xs text-gray-700 max-w-xs truncate" :title="inv.descripcion">{{ inv.descripcion || 'Gasto Común' }}</div>
                      </td>
                      <td class="px-6 py-4 font-semibold text-gray-800">
                        {{ formatAmount(inv.monto_total) }}
                      </td>
                      <td class="px-6 py-4 font-bold" :class="inv.estado !== 'pagado' ? 'text-rose-600' : 'text-gray-400'">
                        {{ formatAmount(inv.monto_total - inv.monto_pagado) }}
                      </td>
                      <td class="px-6 py-4">
                        <span class="px-2.5 py-0.5 text-xs font-bold rounded-full uppercase tracking-wider"
                          :class="{
                            'bg-emerald-100 text-emerald-800': inv.estado === 'pagado',
                            'bg-amber-100 text-amber-800': inv.estado === 'pendiente',
                            'bg-sky-100 text-sky-850': inv.estado === 'parcial'
                          }">
                          {{ inv.estado }}
                        </span>
                      </td>
                      <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                          <a :href="'/api/v1/reportes/recibo-invoice/' + inv.id" target="_blank"
                            class="bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-700 text-xs font-bold px-2 py-1 rounded transition-all inline-flex items-center gap-1"
                            title="Descargar Recibo de Cobro Individual (PDF)">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Recibo PDF
                          </a>
                          <button v-if="inv.estado !== 'pagado'" @click="openPayModal(inv)"
                            class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-2.5 py-1 rounded transition-all shadow">
                            Reportar Pago
                          </button>
                          <span v-else class="text-xs text-emerald-600 font-bold">✓ Solvente</span>
                        </div>
                      </td>
                    </tr>
                    <tr v-if="filteredInvoices.length === 0">
                      <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                        No se encontraron facturas con los filtros aplicados.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Footer Paginador DataTable -->
              <div class="px-6 py-3 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4 text-[11px]">
                <div class="text-gray-500">
                  Mostrando <span class="font-bold text-gray-800">{{ Math.min((invPage - 1) * invPerPage + 1, filteredInvoices.length) }}</span> a
                  <span class="font-bold text-gray-800">{{ Math.min(invPage * invPerPage, filteredInvoices.length) }}</span> de
                  <span class="font-bold text-gray-800">{{ filteredInvoices.length }}</span> registros
                </div>
                <div class="flex items-center gap-1">
                  <button @click="invPage = 1" :disabled="invPage === 1" class="px-2 py-1 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">«</button>
                  <button @click="invPage--" :disabled="invPage === 1" class="px-2.5 py-1 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">Ant.</button>
                  <span class="px-2.5 py-1 font-bold text-blue-600 bg-blue-50 rounded border border-blue-100">
                    Pág. {{ invPage }} de {{ Math.ceil(filteredInvoices.length / invPerPage) || 1 }}
                  </span>
                  <button @click="invPage++" :disabled="invPage >= Math.ceil(filteredInvoices.length / invPerPage)" class="px-2.5 py-1 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">Sig.</button>
                  <button @click="invPage = Math.ceil(filteredInvoices.length / invPerPage) || 1" :disabled="invPage >= Math.ceil(filteredInvoices.length / invPerPage)" class="px-2 py-1 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">»</button>
                </div>
              </div>
            </div>
          </div>

          <!-- Historial de Pagos Reportados (Derecha) -->
          <div class="space-y-4">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
              <h3 class="font-bold text-gray-800 border-b border-gray-100 pb-2">Historial de Pagos Reportados</h3>
              <div class="space-y-3 max-h-[400px] overflow-y-auto pr-1">
                <div v-for="pay in payments" :key="pay.id" class="p-3 border border-gray-100 rounded-lg hover:bg-gray-50 transition-colors">
                  <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-gray-800 font-mono">
                      {{ pay.invoices && pay.invoices.length > 1 ? (pay.invoices.length + ' Recibos Cubiertos') : ('Recibo: ' + (pay.invoice?.numero_factura || 'N/A')) }}
                    </span>
                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full uppercase"
                      :class="pay.estado === 'aprobado' ? 'bg-emerald-100 text-emerald-800' : (pay.estado === 'pendiente' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800')">
                      {{ pay.estado === 'aprobado' ? (pay.numero_recibo_pago || '✓ Aprobado') : (pay.estado === 'pendiente' ? '⏳ Verificando Banco' : pay.estado) }}
                    </span>
                  </div>

                  <!-- Desglose de Recibos y botones PDF individuales -->
                  <div v-if="pay.invoices && pay.invoices.length > 0" class="mt-2 text-[11px] bg-slate-50 p-2 rounded border border-slate-100 space-y-1">
                    <div class="font-bold text-slate-700 text-[10px] uppercase">📋 Recibos de Cobro en esta Transacción:</div>
                    <div v-for="inv in pay.invoices" :key="inv.id" class="flex justify-between items-center text-[10px] border-b border-slate-100 pb-1 last:border-0">
                      <span class="text-gray-800 font-semibold">{{ inv.numero_factura }} ({{ inv.periodo }})</span>
                      <div class="flex items-center gap-1.5">
                        <span class="font-mono text-emerald-700 font-bold">Bs. {{ Number(inv.pivot?.monto_aplicado || 0).toFixed(2) }}</span>
                        <a :href="'/api/v1/reportes/recibo-invoice/' + inv.id" target="_blank" class="text-[10px] font-bold text-blue-600 hover:underline bg-white px-1.5 py-0.5 rounded border border-blue-200 shadow-2xs">
                          📄 PDF Recibo
                        </a>
                      </div>
                    </div>
                  </div>

                  <div class="flex justify-between items-center mt-2">
                    <span class="text-xs capitalize text-gray-500">{{ (pay.metodo_pago || '').replace('_', ' ') }}</span>
                    <span class="text-sm font-bold text-emerald-600">{{ formatAmount(pay.monto) }}</span>
                  </div>
                  <div class="text-[10px] text-gray-400 mt-1 flex justify-between border-t border-gray-50 pt-1">
                    <span>Ref: {{ pay.referencia || 'N/A' }}</span>
                    <span class="text-blue-600 font-semibold font-mono">Tasa: Bs. {{ parseFloat(pay.tasa_cambio || 36.50).toFixed(2) }}</span>
                  </div>
                  <div v-if="pay.estado === 'aprobado'" class="mt-2 flex justify-between items-center pt-1 border-t border-emerald-100">
                    <span class="text-[10px] font-bold text-emerald-800 font-mono">{{ pay.numero_recibo_pago }}</span>
                    <a :href="'/api/v1/reportes/recibo/' + pay.id" target="_blank"
                      class="text-[11px] font-bold text-emerald-700 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-2 py-1 rounded transition-all inline-flex items-center gap-1">
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                      Comprobante RP (PDF)
                    </a>
                  </div>
                  <div v-else-if="pay.estado === 'pendiente'" class="mt-2 text-[10px] text-amber-700 italic bg-amber-50/70 p-1.5 rounded border border-amber-200">
                    <span>⏳ En espera de comprobación de saldo en banco por administración para emitir el Recibo RP.</span>
                  </div>
                  <div v-else-if="pay.estado === 'rechazado'" class="mt-2 text-[11px] text-rose-800 bg-rose-50 p-2.5 rounded-lg border border-rose-200 space-y-1">
                    <div class="font-bold flex items-center gap-1 text-rose-900">
                      <span>❌ Pago Rechazado por Administración</span>
                    </div>
                    <p class="text-[10px] text-rose-700 italic font-medium mb-1">
                      "{{ pay.motivo_rechazo || pay.observaciones || 'La referencia o el monto notificado no coincide con el banco.' }}"
                    </p>
                    <button @click="openPayModal(pay.invoice)" class="text-[10px] font-bold bg-rose-600 hover:bg-rose-500 text-white px-2.5 py-1 rounded transition-all shadow-xs inline-flex items-center gap-1">
                      <span>🔄 Corregir y Notificar Nuevamente</span>
                    </button>
                  </div>
                </div>
                <div v-if="payments.length === 0" class="text-center text-xs text-gray-400 py-6">
                  No ha reportado pagos recientemente.
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 2. TAB: RESERVAR ÁREAS COMUNES -->
        <div v-if="propietario && activeTab === 'reservas'" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Formulario e Información de Áreas (Izquierda) -->
          <div class="lg:col-span-2 space-y-6">
            <h3 class="text-lg font-bold text-gray-800">Áreas Comunes Disponibles</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div v-for="area in commonAreas" :key="area.id" class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm space-y-4 hover:shadow transition-all flex flex-col justify-between">
                <div>
                  <h4 class="font-bold text-gray-900 text-base">{{ area.nombre }}</h4>
                  <p class="text-xs text-gray-400 mt-1">{{ area.descripcion || 'Área recreativa para residentes.' }}</p>
                  
                  <div class="grid grid-cols-2 gap-2 mt-4 text-xs">
                    <div class="bg-gray-50 p-2 rounded-lg">
                      <span class="block text-[10px] text-gray-400 font-bold uppercase">Capacidad Máx</span>
                      <span class="font-bold text-gray-800">{{ area.capacidad_maxima }} personas</span>
                    </div>
                    <div class="bg-gray-50 p-2 rounded-lg">
                      <span class="block text-[10px] text-gray-400 font-bold uppercase">Costo Alquiler</span>
                      <span class="font-bold text-blue-600">{{ formatAmount(area.costo_reserva) }}</span>
                    </div>
                  </div>
                </div>

                <button @click="openReservaModal(area)"
                  class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs py-2 rounded-lg mt-4 transition-all shadow">
                  Reservar Área
                </button>
              </div>
            </div>
          </div>

          <!-- Mis Reservas Realizadas (Derecha) -->
          <div class="space-y-4">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
              <div class="border-b border-gray-100 pb-2 space-y-3">
                <h3 class="font-bold text-gray-800">Mis Solicitudes de Reserva</h3>
                <div class="relative w-full">
                  <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-gray-400">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                  </span>
                  <input type="text" v-model="resSearch" placeholder="Filtrar reservas..." class="w-full pl-8 pr-3 py-1 bg-gray-50 border border-gray-200 rounded-lg text-[11px] outline-none focus:border-blue-500 transition-all font-semibold" />
                </div>
              </div>
              <div class="space-y-3 max-h-[350px] overflow-y-auto pr-1">
                <div v-for="res in paginatedReservations" :key="res.id" class="p-3 border border-gray-100 rounded-lg hover:bg-gray-50 transition-colors space-y-2">
                  <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-gray-800">{{ res.common_area?.nombre }}</span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded uppercase"
                      :class="{
                        'bg-emerald-100 text-emerald-800': res.estado === 'aprobada',
                        'bg-rose-100 text-rose-800': res.estado === 'rechazada',
                        'bg-amber-100 text-amber-800': res.estado === 'pendiente'
                      }">
                      {{ res.estado }}
                    </span>
                  </div>
                  <div class="text-[11px] text-gray-500 space-y-0.5 font-mono">
                    <div>Fecha: {{ formatDate(res.fecha_reserva) }}</div>
                    <div>Horario: {{ res.hora_inicio.substring(0, 5) }} - {{ res.hora_fin.substring(0, 5) }}</div>
                  </div>
                  <div class="text-[10px] text-gray-400 border-t border-gray-50 pt-1 flex justify-between">
                    <span>Monto: {{ formatAmount(res.costo_uso) }}</span>
                    <span>Personas: {{ res.numero_personas }}</span>
                  </div>
                </div>
                <div v-if="filteredReservations.length === 0" class="text-center text-xs text-gray-400 py-6">
                  No se encontraron reservas con los filtros aplicados.
                </div>
              </div>

              <!-- Paginador simple para reservas -->
              <div v-if="filteredReservations.length > resPerPage" class="flex justify-between items-center border-t border-gray-50 pt-2 text-[10px]">
                <button @click="resPage--" :disabled="resPage === 1" class="px-2 py-0.5 rounded border border-gray-200 bg-white disabled:opacity-40 transition-all font-semibold">Ant.</button>
                <span class="text-gray-500 font-bold">Pág. {{ resPage }} de {{ Math.ceil(filteredReservations.length / resPerPage) }}</span>
                <button @click="resPage++" :disabled="resPage >= Math.ceil(filteredReservations.length / resPerPage)" class="px-2 py-0.5 rounded border border-gray-200 bg-white disabled:opacity-40 transition-all font-semibold">Sig.</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODALES DE OPERACIÓN -->

    <!-- Modal Reportar Pago -->
    <div v-if="payModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 animate-fade-in">
      <div class="bg-white border border-gray-150 w-full max-w-lg rounded-2xl shadow-xl p-6 max-h-[90vh] flex flex-col">
        <div class="flex justify-between items-center border-b border-gray-100 pb-3 mb-4 shrink-0">
          <h3 class="text-lg font-bold text-gray-900">Notificar Pago Realizado</h3>
          <button @click="payModalOpen = false" class="text-gray-400 hover:text-gray-600 text-lg font-bold">✕</button>
        </div>

        <form @submit.prevent="savePayment" class="flex-1 overflow-y-auto space-y-4 pr-1">
          <!-- Selector Múltiple de Recibos de Cobro Pendientes -->
          <div>
            <div class="flex justify-between items-center mb-1">
              <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Recibos de Cobro / Facturas a Pagar</label>
              <button type="button" @click="toggleSelectAllInvoices" class="text-xs font-semibold text-blue-600 hover:underline">
                {{ selectedInvoiceIds.length === pendingInvoices.length ? 'Deseleccionar Todos' : 'Seleccionar Todos' }}
              </button>
            </div>

            <div class="bg-gray-50 border border-gray-200 rounded-xl p-2.5 max-h-48 overflow-y-auto space-y-2">
              <div v-for="inv in pendingInvoices" :key="inv.id" class="flex items-center justify-between p-2 rounded-lg bg-white border border-gray-150 hover:border-blue-300 transition-all cursor-pointer" @click="toggleInvoiceChoice(inv.id)">
                <div class="flex items-center gap-2.5">
                  <input type="checkbox" :value="inv.id" v-model="selectedInvoiceIds" @change="onInvoiceCheckboxChange" class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500 cursor-pointer" @click.stop />
                  <div>
                    <div class="text-xs font-bold text-gray-900">{{ inv.numero_factura || 'RI2026-X' }} ({{ inv.periodo }})</div>
                    <div class="text-[11px] text-gray-500">Cuota Condominio • {{ inv.apartamento?.numero ? 'Apto. ' + inv.apartamento.numero : 'Unidad' }}</div>
                  </div>
                </div>
                <div class="text-right">
                  <div class="text-xs font-bold text-blue-700 font-mono">Bs. {{ getInvoiceSaldoBs(inv).toFixed(2) }}</div>
                  <div class="text-[10px] text-gray-500 font-mono">${{ (getInvoiceTotalUsd(inv) * (getInvoiceSaldoBs(inv) / (getInvoiceTotalBs(inv) || 1))).toFixed(2) }} USD</div>
                </div>
              </div>

              <div v-if="!pendingInvoices.length" class="text-center py-4 text-xs text-gray-400">
                No tiene recibos pendientes por pagar.
              </div>
            </div>
          </div>

          <!-- Resumen Combinado de Facturas Seleccionadas -->
          <div v-if="selectedInvoiceIds.length" class="bg-blue-50/80 border border-blue-200 rounded-xl p-3 text-xs text-blue-900 space-y-1">
            <div class="flex justify-between items-center font-bold border-b border-blue-200 pb-1">
              <span>Recibos Seleccionados: {{ selectedInvoiceIds.length }}</span>
              <span class="text-blue-700">Total Acumulado</span>
            </div>
            <div class="flex justify-between items-center text-sm font-extrabold text-blue-950 pt-0.5">
              <span>Monto Total a Saldar:</span>
              <span class="font-mono">Bs. {{ totalSelectedBs.toFixed(2) }} (${{ totalSelectedUsd.toFixed(2) }} USD)</span>
            </div>
          </div>

          <!-- Cuentas y Pago Móvil Autorizados del Condominio (Multi-Cuenta N) -->
          <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-800 space-y-2">
            <div class="font-bold text-slate-900 border-b border-slate-200 pb-1.5 flex justify-between items-center">
              <span>🏦 Cuentas Autorizadas del Condominio</span>
              <span class="text-blue-600 font-normal text-[11px]">{{ propietario?.condominio?.nombre }}</span>
            </div>

            <!-- Lista Dinámica de Cuentas y Pago Móvil -->
            <div v-if="condominioCuentas.length" class="space-y-2">
              <div class="text-[11px] font-bold text-gray-600">Seleccione la Cuenta/Pago Móvil al que realizó el pago:</div>
              <select v-model="selectedCuentaId" class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-800 outline-none focus:border-blue-500 font-medium">
                <option :value="null">-- Seleccionar Cuenta Destino (Opcional) --</option>
                <option v-for="cta in condominioCuentas" :key="cta.id" :value="cta.id">
                  {{ cta.banco_nombre }} | {{ cta.es_pago_movil ? 'Pago Móvil: ' + cta.telefono_pago_movil : (cta.moneda + ': ' + cta.numero_cuenta) }}
                </option>
              </select>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px] pt-1 border-t border-slate-150">
                <div v-for="cta in condominioCuentas" :key="'info-'+cta.id" class="bg-white p-2 rounded border border-slate-150">
                  <div class="font-bold text-slate-900 flex justify-between">
                    <span>{{ cta.banco_nombre }}</span>
                    <span class="text-[10px] uppercase font-bold text-blue-600">{{ cta.moneda }}</span>
                  </div>
                  <template v-if="cta.es_pago_movil">
                    <div class="text-gray-600">📱 Pago Móvil: <strong>{{ cta.telefono_pago_movil }}</strong></div>
                    <div class="text-gray-600">CI/RIF: <strong>{{ cta.titular_identificacion }}</strong></div>
                  </template>
                  <template v-else>
                    <div class="text-gray-600">💳 Cta: <strong class="font-mono text-[10px]">{{ cta.numero_cuenta }}</strong></div>
                    <div class="text-gray-500 text-[10px]">{{ cta.titular_nombre }}</div>
                  </template>
                </div>
              </div>
            </div>

            <!-- Fallback Legacy -->
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px] pt-1">
              <div>
                <span class="text-gray-500 font-semibold block">📱 Pago Móvil:</span>
                <span>Banco: <strong>{{ propietario?.condominio?.pago_movil_banco || propietario?.condominio?.banco_nombre || 'BNC' }}</strong></span><br>
                <span>CI/RIF: <strong>{{ propietario?.condominio?.pago_movil_cedula || propietario?.condominio?.rif || 'J-300576531' }}</strong></span><br>
                <span>Teléfono: <strong>{{ propietario?.condominio?.pago_movil_telefono || propietario?.condominio?.telefono || '0414-1234567' }}</strong></span>
              </div>
              <div>
                <span class="text-gray-500 font-semibold block">💳 Transferencia Bancaria Bs.:</span>
                <span>Banco: <strong>{{ propietario?.condominio?.banco_nombre || 'BNC' }}</strong></span><br>
                <span>Cuenta: <strong class="font-mono text-[10px]">{{ propietario?.condominio?.cuenta_bancaria_bs || '0191 0514 8221 0001 8351' }}</strong></span>
              </div>
            </div>
          </div>

          <!-- Campo Monto a Notificar -->
          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Monto a Reportar (Bs.)</label>
            <input v-model="paymentForm.monto" type="number" step="0.01" required class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-gray-800 font-mono font-bold outline-none focus:border-blue-500 text-sm transition-all" />
            <p class="text-xs text-gray-500 mt-1 flex justify-between">
              <span>Equivalente referencial:</span>
              <strong class="text-blue-600 font-mono">${{ (paymentForm.monto / (propietario?.condominio?.tasa_cambio || 36.50)).toFixed(2) }} USD (Tasa BCV: {{ propietario?.condominio?.tasa_cambio || '36.50' }})</strong>
            </p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Método de Pago</label>
              <select v-model="paymentForm.metodo_pago" required class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all">
                <option value="pago_movil">Pago Móvil</option>
                <option value="transferencia">Transferencia Bancaria</option>
                <option value="deposito">Depósito Bancario</option>
                <option value="efectivo">Efectivo</option>
                <option value="otro">Otro</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Banco de Origen</label>
              <select v-model="paymentForm.banco" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all">
                <option value="">Seleccione un banco</option>
                <option v-for="banco in bancos" :key="banco.id" :value="banco.nombre">{{ banco.nombre }}</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Número de Referencia</label>
              <input v-model="paymentForm.referencia" type="text" placeholder="Ej: 994829" required class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm font-mono transition-all" />
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Fecha de Transacción</label>
              <input v-model="paymentForm.fecha_pago" type="date" required class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Observaciones / Notas</label>
            <input v-model="paymentForm.observaciones" type="text" placeholder="Notas adicionales sobre el pago" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
          </div>

          <div class="bg-amber-50 border border-amber-200 rounded-lg p-2.5 text-[11px] text-amber-800 flex items-start gap-2">
            <span>ℹ️</span>
            <span>Su pago quedará en estado <strong>Pendiente de verificación bancaria</strong>. Una vez que la administración confirme el ingreso del dinero en la cuenta, se le emitirá su <strong>Recibo de Pago Oficial (RP)</strong> en PDF.</span>
          </div>

          <div class="sticky bottom-0 bg-white pt-3 pb-1 border-t border-gray-100 flex justify-end gap-3 z-10">
            <button type="button" @click="payModalOpen = false" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2 rounded-lg text-sm font-medium transition-all">Cancelar</button>
            <button type="submit" :disabled="paymentForm.processing" class="bg-blue-600 hover:bg-blue-500 text-white px-5 py-2 rounded-lg text-sm font-bold transition-all shadow-md">Notificar Pago</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Solicitar Reserva -->
    <div v-if="reservaModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
      <div class="bg-white border border-gray-150 w-full max-w-md rounded-2xl shadow-xl p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4 border-b border-gray-100 pb-2">Reservar: {{ selectedArea?.nombre }}</h3>
        <p class="text-xs text-gray-500 mb-4">Costo de reserva a cargar en cuenta: <strong>{{ formatAmount(selectedArea?.costo_reserva) }}</strong></p>
        <form @submit.prevent="saveReserva" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Fecha Requerida</label>
            <input v-model="reservaForm.fecha_reserva" type="date" required class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Hora Inicio</label>
              <input v-model="reservaForm.hora_inicio" type="time" required class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Hora Fin</label>
              <input v-model="reservaForm.hora_fin" type="time" required class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
            </div>
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Número de Personas</label>
            <input v-model="reservaForm.numero_personas" type="number" min="1" :max="selectedArea?.capacidad_maxima" required class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Motivo / Evento</label>
            <input v-model="reservaForm.motivo" type="text" placeholder="Ej: Cumpleaños infantil" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
          </div>
          <div class="flex justify-end gap-3 mt-6">
            <button type="button" @click="reservaModalOpen = false" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2 rounded text-sm transition-all">Cancelar</button>
            <button type="submit" :disabled="reservaForm.processing" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded text-sm transition-all font-semibold shadow">Solicitar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const page = usePage();

// Watcher de mensajes flash para SweetAlert
watch(() => page.props.flash, (flash) => {
  if (flash && flash.success) {
    Swal.fire({
      icon: 'success',
      title: '¡Éxito!',
      text: flash.success,
      confirmButtonColor: '#3b82f6',
      timer: 3000,
      timerProgressBar: true
    });
  }
  if (flash && flash.error) {
    Swal.fire({
      icon: 'error',
      title: '¡Error!',
      text: flash.error,
      confirmButtonColor: '#ef4444'
    });
  }
}, { deep: true, immediate: true });

const props = defineProps({
  propietario: Object,
  apartamentos: Array,
  invoices: Array,
  payments: Array,
  reservations: Array,
  commonAreas: Array,
  bancos: Array
});

const activeTab = ref('facturas');
const currency = ref('VES'); // Moneda de visualización por defecto Bs

// Modales y Selección Múltiple
const payModalOpen = ref(false);
const reservaModalOpen = ref(false);
const selectedInvoice = ref(null);
const selectedInvoiceIds = ref([]);
const selectedCuentaId = ref(null);
const condominioCuentas = ref([]);
const selectedArea = ref(null);

const fetchCuentasBancarias = async () => {
  try {
    const res = await axios.get('/condominios/cuentas-bancarias');
    if (res.data.success) {
      condominioCuentas.value = res.data.data;
    }
  } catch (e) {}
};
fetchCuentasBancarias();

// Forms
const paymentForm = useForm({
  monto: 0,
  metodo_pago: 'pago_movil',
  referencia: '',
  banco: '',
  fecha_pago: new Date().toISOString().substring(0, 10),
  observaciones: ''
});

const getInvoiceTotalBs = (inv) => {
  if (!inv) return 0;
  return Number(inv.monto_total_bs_efectivo || inv.monto_total || 0);
};

const getInvoiceTotalUsd = (inv) => {
  if (!inv) return 0;
  if (inv.monto_total_usd && Number(inv.monto_total_usd) > 0) return Number(inv.monto_total_usd);
  const tasa = Number(inv.tasa_efectiva || inv.tasa_cambio || props.propietario?.condominio?.tasa_cambio || 36.50);
  return getInvoiceTotalBs(inv) / tasa;
};

const getInvoiceSaldoBs = (inv) => {
  if (!inv) return 0;
  const totalBs = getInvoiceTotalBs(inv);
  const pagadoBs = Number(inv.monto_pagado || 0);
  return Math.max(0, totalBs - pagadoBs);
};

const pendingInvoices = computed(() => {
  return (props.invoices || []).filter(inv => {
    const saldo = getInvoiceSaldoBs(inv);
    return inv.estado !== 'pagado' && saldo > 0.01;
  });
});

const selectedInvoicesObjects = computed(() => {
  return pendingInvoices.value.filter(inv => selectedInvoiceIds.value.includes(inv.id));
});

const totalSelectedBs = computed(() => {
  return selectedInvoicesObjects.value.reduce((sum, inv) => sum + getInvoiceSaldoBs(inv), 0);
});

const totalSelectedUsd = computed(() => {
  return selectedInvoicesObjects.value.reduce((sum, inv) => {
    const totalBs = getInvoiceTotalBs(inv) || 1;
    return sum + (getInvoiceTotalUsd(inv) * (getInvoiceSaldoBs(inv) / totalBs));
  }, 0);
});

const toggleInvoiceChoice = (id) => {
  const idx = selectedInvoiceIds.value.indexOf(id);
  if (idx > -1) {
    selectedInvoiceIds.value.splice(idx, 1);
  } else {
    selectedInvoiceIds.value.push(id);
  }
  paymentForm.monto = totalSelectedBs.value.toFixed(2);
};

const toggleSelectAllInvoices = () => {
  if (selectedInvoiceIds.value.length === pendingInvoices.value.length) {
    selectedInvoiceIds.value = [];
  } else {
    selectedInvoiceIds.value = pendingInvoices.value.map(i => i.id);
  }
  paymentForm.monto = totalSelectedBs.value.toFixed(2);
};

const onInvoiceCheckboxChange = () => {
  paymentForm.monto = totalSelectedBs.value.toFixed(2);
};

const openPayModal = (invoice = null) => {
  paymentForm.reset();
  paymentForm.fecha_pago = new Date().toISOString().substring(0, 10);
  paymentForm.metodo_pago = 'pago_movil';
  selectedCuentaId.value = condominioCuentas.value[0]?.id || null;

  if (invoice && invoice.id) {
    selectedInvoiceIds.value = [invoice.id];
  } else if (pendingInvoices.value.length) {
    selectedInvoiceIds.value = [pendingInvoices.value[0].id];
  } else {
    selectedInvoiceIds.value = [];
  }

  paymentForm.monto = totalSelectedBs.value.toFixed(2);
  payModalOpen.value = true;
};

const savePayment = async () => {
  if (!selectedInvoiceIds.value.length) {
    Swal.fire('Atención', 'Debe seleccionar al menos un recibo de cobro para notificar el pago.', 'warning');
    return;
  }
  try {
    const payload = {
      invoice_ids: selectedInvoiceIds.value,
      invoice_id: selectedInvoiceIds.value[0],
      cuenta_bancaria_id: selectedCuentaId.value || null,
      monto: paymentForm.monto,
      metodo_pago: paymentForm.metodo_pago,
      referencia: paymentForm.referencia,
      banco: paymentForm.banco,
      fecha_pago: paymentForm.fecha_pago,
      observaciones: paymentForm.observaciones,
    };
    const res = await axios.post('/payments', payload);
    if (res.data.success) {
      payModalOpen.value = false;
      Swal.fire({
        icon: 'success',
        title: '¡Pago Registrado!',
        text: res.data.message || 'Pago registrado exitosamente. En espera de verificación bancaria.',
        confirmButtonColor: '#3b82f6'
      });
      router.reload();
    }
  } catch (e) {
    Swal.fire('Error', e.response?.data?.message || 'Error al notificar el pago.', 'error');
  }
};

const reservaForm = useForm({
  common_area_id: '',
  fecha_reserva: '',
  hora_inicio: '',
  hora_fin: '',
  numero_personas: 1,
  motivo: ''
});

// DataTable - Facturas (Propietario)
const invSearch = ref('');
const invPage = ref(1);
const invPerPage = ref(10);
const filteredInvoices = computed(() => {
  const q = invSearch.value.toLowerCase().trim();
  if (!q) return props.invoices;
  return props.invoices.filter(i => 
    i.numero_factura.toLowerCase().includes(q) || 
    i.estado.toLowerCase().includes(q) ||
    (i.descripcion || '').toLowerCase().includes(q)
  );
});
const paginatedInvoices = computed(() => {
  const start = (invPage.value - 1) * invPerPage.value;
  return filteredInvoices.value.slice(start, start + invPerPage.value);
});
watch(invSearch, () => { invPage.value = 1; });

// DataTable - Reservas (Propietario)
const resSearch = ref('');
const resPage = ref(1);
const resPerPage = ref(5); // límite de 5 para reservas en sidebar
const filteredReservations = computed(() => {
  const q = resSearch.value.toLowerCase().trim();
  if (!q) return props.reservations;
  return props.reservations.filter(r => 
    (r.common_area?.nombre || '').toLowerCase().includes(q) || 
    r.estado.toLowerCase().includes(q)
  );
});
const paginatedReservations = computed(() => {
  const start = (resPage.value - 1) * resPerPage.value;
  return filteredReservations.value.slice(start, start + resPerPage.value);
});
watch(resSearch, () => { resPage.value = 1; });

// Computed Properties
const totalDeudaPendiente = computed(() => {
  return props.invoices.reduce((acc, inv) => {
    if (inv.estado !== 'pagado') {
      return acc + (inv.monto_total - inv.monto_pagado);
    }
    return acc;
  }, 0);
});

const tieneCuotasVencidas = computed(() => {
  return props.invoices.some(inv => inv.estado !== 'pagado');
});

// Conversión y Formateo según divisa seleccionada
const formatAmount = (amountInVES) => {
  if (currency.value === 'VES') {
    return new Intl.NumberFormat('es-VE', { style: 'currency', currency: 'VES' }).format(amountInVES);
  } else {
    const usdVal = amountInVES / (props.propietario?.condominio?.tasa_cambio || 36.50);
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(usdVal);
  }
};

const formatDate = (dateStr) => {
  if (!dateStr) return 'N/A';
  return new Date(dateStr).toLocaleDateString('es-ES');
};

// Actions
const logout = () => {
  router.post('/logout');
};

const openPayModal = (invoice) => {
  selectedInvoice.value = invoice;
  paymentForm.reset();
  paymentForm.monto = (invoice.monto_total - invoice.monto_pagado).toFixed(2);
  payModalOpen.value = true;
};

const savePayment = () => {
  paymentForm.post(`/invoices/${selectedInvoice.value.id}/payments`, {
    onSuccess: () => {
      payModalOpen.value = false;
    }
  });
};

const openReservaModal = (area) => {
  selectedArea.value = area;
  reservaForm.reset();
  reservaForm.common_area_id = area.id;
  reservaForm.numero_personas = 1;
  reservaModalOpen.value = true;
};

const saveReserva = () => {
  reservaForm.post('/reservas', {
    onSuccess: () => {
      reservaModalOpen.value = false;
    }
  });
};
</script>
