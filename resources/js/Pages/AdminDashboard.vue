<template>
  <div class="min-h-screen bg-gray-50 text-slate-700 flex font-sans">
    <!-- SIDEBAR LATERAL (Estilo Argon Dashboard) -->
    <aside class="w-64 bg-white border-r border-gray-200 flex flex-col fixed inset-y-0 left-0 z-30 shadow-sm">
      <!-- Logo / Marca -->
      <div class="h-16 flex items-center gap-3 px-6 border-b border-gray-100">
        <span class="text-xl font-black bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">AZPRO</span>
        <span class="bg-indigo-100 text-indigo-800 text-[9px] uppercase font-extrabold tracking-widest px-2 py-0.5 rounded">Admin</span>
      </div>

      <!-- Info del Condominio Actual -->
      <div class="px-6 py-4 bg-gray-50/50 border-b border-gray-100">
        <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Condominio</p>
        <p class="text-sm font-bold text-gray-800 truncate mt-0.5" :title="condominio.nombre">{{ condominio.nombre }}</p>
        <p class="text-[10px] text-blue-600 font-semibold mt-1">Plan: <span class="uppercase">{{ condominio.plan_suscripcion }}</span></p>
      </div>

      <!-- Enlaces de Navegación Lateral -->
      <nav class="flex-1 px-4 py-4 space-y-1">
        <div class="text-[10px] uppercase font-bold tracking-wider text-gray-400 px-3 mb-2">Operaciones</div>
        
        <button @click="activeTab = 'apartamentos'"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-all"
          :class="activeTab === 'apartamentos' ? 'bg-blue-50 text-blue-600 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
          Apartamentos
        </button>

        <button @click="activeTab = 'propietarios'"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-all"
          :class="activeTab === 'propietarios' ? 'bg-blue-50 text-blue-600 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          Propietarios
        </button>

        <button @click="activeTab = 'invoices'"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-all"
          :class="activeTab === 'invoices' ? 'bg-blue-50 text-blue-600 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          Facturas y Cobros
        </button>

        <button @click="activeTab = 'payments'"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-all"
          :class="activeTab === 'payments' ? 'bg-blue-50 text-blue-600 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-20c5.523 0 10 4.477 10 10s-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2z" />
          </svg>
          Pagos Recibidos
        </button>

        <button @click="activeTab = 'reservas'"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-all"
          :class="activeTab === 'reservas' ? 'bg-blue-50 text-blue-600 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          Reservas de Áreas
        </button>
      </nav>

      <!-- Pie del Sidebar con Perfil -->
      <div class="p-4 border-t border-gray-100 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow">
            {{ $page.props.auth.user.name.charAt(0) }}
          </div>
          <div class="overflow-hidden">
            <p class="text-xs font-bold text-gray-800 truncate">{{ $page.props.auth.user.name }}</p>
            <p class="text-[10px] text-gray-400 uppercase truncate">Administrador</p>
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
            <h1 class="text-2xl font-bold text-white tracking-tight">Panel de Control</h1>
            <p class="text-blue-100 text-xs mt-1">Gestiona los apartamentos, facturas y pagos de <strong>{{ condominio.nombre }}</strong>.</p>
            <div class="mt-2 text-[11px] text-blue-200">
              Tarifa Fija Mensual por Apto: <strong class="text-white">{{ formatAmount(condominio.cuota_mantenimiento_base) }}</strong>
            </div>
          </div>

          <!-- Dualidad de Moneda y Configuración de Tasa de Cambio (Venezuela) -->
          <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center w-full lg:w-auto">
            <!-- Toggle de Visualización -->
            <div class="bg-white/10 backdrop-blur-md border border-white/20 px-3 py-1.5 rounded-xl flex items-center justify-between gap-3">
              <span class="text-[9px] uppercase font-bold text-blue-200 block">Moneda:</span>
              <div class="flex bg-black/20 p-0.5 rounded-lg border border-white/10">
                <button @click="currency = 'VES'" class="px-2.5 py-0.5 text-xs font-bold rounded-md transition-all" :class="currency === 'VES' ? 'bg-white text-blue-700 shadow' : 'text-blue-200 hover:text-white'">Bs</button>
                <button @click="currency = 'USD'" class="px-2.5 py-0.5 text-xs font-bold rounded-md transition-all" :class="currency === 'USD' ? 'bg-white text-blue-700 shadow' : 'text-blue-200 hover:text-white'">USD</button>
              </div>
            </div>

            <!-- Editor de Tasa Dolar Oficial -->
            <div class="bg-white/10 backdrop-blur-md border border-white/20 px-3 py-1.5 rounded-xl flex items-center gap-2">
              <div>
                <span class="text-[9px] uppercase font-bold text-blue-200 block">Tasa Dólar (Bs)</span>
                <input type="number" step="0.01" v-model="tasaCambioForm.tasa_cambio" class="bg-black/20 border border-white/10 rounded px-2 py-0.5 text-white text-xs font-bold w-20 outline-none focus:border-white" />
              </div>
              <button @click="updateTasa" :disabled="tasaCambioForm.processing" class="bg-white text-blue-700 text-xs font-bold px-2 py-1.5 rounded transition-all shadow hover:bg-blue-50">
                ✓
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- CONTENIDO PRINCIPAL FLOTANTE -->
      <div class="px-8 -mt-24 pb-12 flex-1 space-y-8 z-10">
        <!-- El sistema utiliza SweetAlert para las notificaciones en tiempo real -->

        <!-- Alerta de Suscripción Inactiva -->
        <div v-if="condominio.estado_suscripcion !== 'activo'" class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-xl text-sm shadow-sm">
          ⚠️ La suscripción de este condominio se encuentra <strong>{{ condominio.estado_suscripcion }}</strong>. La creación de apartamentos, facturas y propietarios está deshabilitada hasta regularizar su estatus en el SaaS.
        </div>

        <!-- Tarjetas de Métricas -->
        <div class="grid grid-cols-2 lg:grid-cols-6 gap-6">
          <div class="bg-white border border-gray-150 p-4 rounded-xl shadow-sm">
            <p class="text-gray-400 text-[10px] uppercase font-bold tracking-wider">Apartamentos</p>
            <p class="text-xl font-black mt-1 text-gray-800">{{ reportes.total_apartamentos }}</p>
          </div>
          <div class="bg-white border border-gray-150 p-4 rounded-xl shadow-sm">
            <p class="text-gray-400 text-[10px] uppercase font-bold tracking-wider">Ocupados</p>
            <p class="text-xl font-black mt-1 text-emerald-600">{{ reportes.apartamentos_ocupados }}</p>
          </div>
          <div class="bg-white border border-gray-150 p-4 rounded-xl shadow-sm">
            <p class="text-gray-400 text-[10px] uppercase font-bold tracking-wider">Propietarios</p>
            <p class="text-xl font-black mt-1 text-purple-600">{{ reportes.total_propietarios }}</p>
          </div>
          <div class="bg-white border border-gray-150 p-4 rounded-xl shadow-sm">
            <p class="text-gray-400 text-[10px] uppercase font-bold tracking-wider">Por Cobrar</p>
            <p class="text-xl font-black mt-1 text-amber-600">{{ reportes.facturas_pendientes }}</p>
          </div>
          <div class="bg-white border border-gray-150 p-4 rounded-xl shadow-sm col-span-2 flex flex-col justify-between">
            <div>
              <p class="text-gray-400 text-[10px] uppercase font-bold tracking-wider">Recaudación Total</p>
              <p class="text-xl font-black mt-1 text-blue-600">{{ formatAmount(reportes.total_recaudado) }}</p>
            </div>
          </div>
        </div>

        <!-- 1. TAB CONTENT: APARTAMENTOS -->
        <div v-if="activeTab === 'apartamentos'" class="space-y-6">
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h3 class="text-lg font-bold text-gray-800">Apartamentos Registrados</h3>
            <div class="flex items-center gap-3">
              <div class="flex items-center gap-1.5 text-xs text-gray-500">
                <span>Mostrar:</span>
                <select v-model="aptoPerPage" class="bg-white border border-gray-200 rounded px-2 py-1 text-gray-700 outline-none focus:border-blue-500 font-semibold">
                  <option :value="5">5</option>
                  <option :value="10">10</option>
                  <option :value="20">20</option>
                  <option :value="50">50</option>
                </select>
              </div>
              <div class="relative max-w-xs w-full">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                </span>
                <input type="text" v-model="aptoSearch" placeholder="Buscar apartamento..." class="w-full pl-9 pr-4 py-1.5 bg-white border border-gray-200 rounded-lg text-xs outline-none focus:border-blue-500 transition-all font-semibold" />
              </div>
              <button @click="openCreateAptoModal" :disabled="condominio.estado_suscripcion !== 'activo'"
                class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold px-3 py-2 rounded-lg transition-all shadow disabled:opacity-50 whitespace-nowrap">
                + Nuevo Apartamento
              </button>
            </div>
          </div>
          <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-gray-50/50 text-gray-400 text-[11px] uppercase tracking-wider border-b border-gray-200">
                  <th class="px-6 py-4">Número</th>
                  <th class="px-6 py-4">Piso</th>
                  <th class="px-6 py-4">M² / Medidas</th>
                  <th class="px-6 py-4 text-center">Estado</th>
                  <th class="px-6 py-4">Descripción</th>
                </tr>
              </thead>
                <tbody class="divide-y divide-gray-100 text-sm text-gray-600">
                  <tr v-for="apto in paginatedApartamentos" :key="apto.id" class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-6 py-4 font-bold text-gray-900">
                      Apto: {{ apto.numero }}
                    </td>
                    <td class="px-6 py-4 font-semibold text-gray-700">
                      Piso {{ apto.piso }}
                    </td>
                    <td class="px-6 py-4 font-mono text-xs">
                      {{ apto.metros_cuadrados }} m²
                    </td>
                    <td class="px-6 py-4 text-center">
                      <span class="px-2.5 py-0.5 text-xs font-bold rounded-full uppercase"
                        :class="apto.activo ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-500'">
                        {{ apto.activo ? 'Habitado' : 'Vacío' }}
                      </span>
                    </td>
                    <td class="px-6 py-4 text-gray-400 max-w-xs truncate">
                      {{ apto.descripcion || 'Sin descripción' }}
                    </td>
                  </tr>
                  <tr v-if="filteredApartamentos.length === 0">
                    <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                      No se encontraron apartamentos con los filtros aplicados.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Footer Paginador DataTable -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
              <div class="text-gray-500">
                Mostrando <span class="font-bold text-gray-800">{{ Math.min((aptoPage - 1) * aptoPerPage + 1, filteredApartamentos.length) }}</span> a
                <span class="font-bold text-gray-800">{{ Math.min(aptoPage * aptoPerPage, filteredApartamentos.length) }}</span> de
                <span class="font-bold text-gray-800">{{ filteredApartamentos.length }}</span> registros
              </div>
              <div class="flex items-center gap-1.5">
                <button @click="aptoPage = 1" :disabled="aptoPage === 1" class="px-2.5 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">«</button>
                <button @click="aptoPage--" :disabled="aptoPage === 1" class="px-3 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">Anterior</button>
                <span class="px-3 py-1.5 font-bold text-blue-600 bg-blue-50 rounded border border-blue-100">
                  Pág. {{ aptoPage }} de {{ Math.ceil(filteredApartamentos.length / aptoPerPage) || 1 }}
                </span>
                <button @click="aptoPage++" :disabled="aptoPage >= Math.ceil(filteredApartamentos.length / aptoPerPage)" class="px-3 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">Siguiente</button>
                <button @click="aptoPage = Math.ceil(filteredApartamentos.length / aptoPerPage) || 1" :disabled="aptoPage >= Math.ceil(filteredApartamentos.length / aptoPerPage)" class="px-2.5 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">»</button>
              </div>
            </div>
          </div>
        </div>

        <!-- 2. TAB CONTENT: PROPIETARIOS -->
        <div v-if="activeTab === 'propietarios'" class="space-y-6">
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h3 class="text-lg font-bold text-gray-800">Directorio de Propietarios y Copropietarios</h3>
            <div class="flex items-center gap-3">
              <div class="flex items-center gap-1.5 text-xs text-gray-500">
                <span>Mostrar:</span>
                <select v-model="propPerPage" class="bg-white border border-gray-200 rounded px-2 py-1 text-gray-700 outline-none focus:border-blue-500 font-semibold">
                  <option :value="5">5</option>
                  <option :value="10">10</option>
                  <option :value="20">20</option>
                  <option :value="50">50</option>
                </select>
              </div>
              <div class="relative max-w-xs w-full">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                </span>
                <input type="text" v-model="propSearch" placeholder="Buscar residente..." class="w-full pl-9 pr-4 py-1.5 bg-white border border-gray-200 rounded-lg text-xs outline-none focus:border-blue-500 transition-all font-semibold" />
              </div>
              <button @click="openCreatePropietarioModal" :disabled="condominio.estado_suscripcion !== 'activo'"
                class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold px-3 py-2 rounded-lg transition-all shadow disabled:opacity-50 whitespace-nowrap">
                + Registrar Propietario
              </button>
            </div>
          </div>
          <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-gray-50/50 text-gray-400 text-[11px] uppercase tracking-wider border-b border-gray-200">
                  <th class="px-6 py-4">Propietario / Residente</th>
                  <th class="px-6 py-4">Cédula / RIF</th>
                  <th class="px-6 py-4">Apartamento</th>
                  <th class="px-6 py-4">Contacto</th>
                  <th class="px-6 py-4">Correo de Acceso</th>
                </tr>
              </thead>
                <tbody class="divide-y divide-gray-100 text-sm text-gray-600">
                  <tr v-for="prop in paginatedPropietarios" :key="prop.id" class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-6 py-4">
                      <div class="font-bold text-gray-900">{{ prop.user?.name }}</div>
                    </td>
                    <td class="px-6 py-4 font-mono text-xs">
                      {{ prop.cedula }}
                    </td>
                    <td class="px-6 py-4">
                      <span v-if="prop.apartamento" class="px-2 py-0.5 text-xs font-bold rounded bg-blue-50 text-blue-600 border border-blue-100">
                        Apto: {{ prop.apartamento.numero }} (Piso {{ prop.apartamento.piso }})
                      </span>
                      <span v-else class="text-xs text-gray-400 italic">No asignado</span>
                    </td>
                    <td class="px-6 py-4">
                      <div class="text-xs text-gray-700 font-semibold">{{ prop.telefono }}</div>
                    </td>
                    <td class="px-6 py-4 font-mono text-xs text-gray-400">
                      {{ prop.user?.email }}
                    </td>
                  </tr>
                  <tr v-if="filteredPropietarios.length === 0">
                    <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                      No se encontraron residentes con los filtros aplicados.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Footer Paginador DataTable -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
              <div class="text-gray-500">
                Mostrando <span class="font-bold text-gray-800">{{ Math.min((propPage - 1) * propPerPage + 1, filteredPropietarios.length) }}</span> a
                <span class="font-bold text-gray-800">{{ Math.min(propPage * propPerPage, filteredPropietarios.length) }}</span> de
                <span class="font-bold text-gray-800">{{ filteredPropietarios.length }}</span> registros
              </div>
              <div class="flex items-center gap-1.5">
                <button @click="propPage = 1" :disabled="propPage === 1" class="px-2.5 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">«</button>
                <button @click="propPage--" :disabled="propPage === 1" class="px-3 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">Anterior</button>
                <span class="px-3 py-1.5 font-bold text-blue-600 bg-blue-50 rounded border border-blue-100">
                  Pág. {{ propPage }} de {{ Math.ceil(filteredPropietarios.length / propPerPage) || 1 }}
                </span>
                <button @click="propPage++" :disabled="propPage >= Math.ceil(filteredPropietarios.length / propPerPage)" class="px-3 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">Siguiente</button>
                <button @click="propPage = Math.ceil(filteredPropietarios.length / propPerPage) || 1" :disabled="propPage >= Math.ceil(filteredPropietarios.length / propPerPage)" class="px-2.5 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">»</button>
              </div>
            </div>
          </div>
        </div>

        <!-- 3. TAB CONTENT: FACTURAS -->
        <div v-if="activeTab === 'invoices'" class="space-y-6">
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h3 class="text-lg font-bold text-gray-800">Facturación de Cuotas de Mantenimiento</h3>
            <div class="flex items-center gap-3">
              <div class="flex items-center gap-1.5 text-xs text-gray-500">
                <span>Mostrar:</span>
                <select v-model="invPerPage" class="bg-white border border-gray-200 rounded px-2 py-1 text-gray-700 outline-none focus:border-blue-500 font-semibold">
                  <option :value="5">5</option>
                  <option :value="10">10</option>
                  <option :value="20">20</option>
                  <option :value="50">50</option>
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
              <button @click="openCreateInvoiceModal" :disabled="condominio.estado_suscripcion !== 'activo'"
                class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold px-3 py-2 rounded-lg transition-all shadow disabled:opacity-50 whitespace-nowrap">
                + Emitir Factura
              </button>
            </div>
          </div>
          <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-gray-50/50 text-gray-400 text-[11px] uppercase tracking-wider border-b border-gray-200">
                  <th class="px-6 py-4">N° Factura</th>
                  <th class="px-6 py-4">Apartamento</th>
                  <th class="px-6 py-4">Monto Total</th>
                  <th class="px-6 py-4 text-center">Pagado</th>
                  <th class="px-6 py-4">Fecha de Emisión</th>
                  <th class="px-6 py-4 text-center">Estado</th>
                  <th class="px-6 py-4 text-right">Operaciones</th>
                </tr>
              </thead>
                <tbody class="divide-y divide-gray-100 text-sm text-gray-600">
                  <tr v-for="inv in paginatedInvoices" :key="inv.id" class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-6 py-4 font-bold text-gray-900 font-mono">
                      {{ inv.numero_factura }}
                    </td>
                    <td class="px-6 py-4">
                      <span class="px-2 py-0.5 text-xs font-bold rounded bg-gray-100 text-gray-700">
                        Apto: {{ inv.apartamento?.numero }}
                      </span>
                    </td>
                    <td class="px-6 py-4 font-bold text-gray-800">
                      {{ formatAmount(inv.monto_total) }}
                    </td>
                    <td class="px-6 py-4 text-center font-semibold text-emerald-600">
                      {{ formatAmount(inv.monto_pagado) }}
                    </td>
                    <td class="px-6 py-4 font-mono text-xs">
                      {{ new Date(inv.fecha_emision).toLocaleDateString('es-ES') }}
                    </td>
                    <td class="px-6 py-4 text-center">
                      <span class="px-2.5 py-0.5 text-xs font-bold rounded-full uppercase tracking-wider"
                        :class="{
                          'bg-emerald-100 text-emerald-800': inv.estado_pago === 'pagado',
                          'bg-amber-100 text-amber-800': inv.estado_pago === 'parcial',
                          'bg-rose-100 text-rose-800': inv.estado_pago === 'pendiente'
                        }">
                        {{ inv.estado_pago }}
                      </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                      <button v-if="inv.estado_pago !== 'pagado'" @click="openPaymentModal(inv)"
                        class="bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-800 text-xs font-bold px-2.5 py-1.5 rounded transition-all">
                        Registrar Pago
                      </button>
                      <span v-else class="text-xs text-emerald-600 font-bold">✓ Totalmente Solventado</span>
                    </td>
                  </tr>
                  <tr v-if="filteredInvoices.length === 0">
                    <td colspan="7" class="px-6 py-8 text-center text-gray-400">
                      No se encontraron facturas con los filtros aplicados.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Footer Paginador DataTable -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
              <div class="text-gray-500">
                Mostrando <span class="font-bold text-gray-800">{{ Math.min((invPage - 1) * invPerPage + 1, filteredInvoices.length) }}</span> a
                <span class="font-bold text-gray-800">{{ Math.min(invPage * invPerPage, filteredInvoices.length) }}</span> de
                <span class="font-bold text-gray-800">{{ filteredInvoices.length }}</span> registros
              </div>
              <div class="flex items-center gap-1.5">
                <button @click="invPage = 1" :disabled="invPage === 1" class="px-2.5 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">«</button>
                <button @click="invPage--" :disabled="invPage === 1" class="px-3 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">Anterior</button>
                <span class="px-3 py-1.5 font-bold text-blue-600 bg-blue-50 rounded border border-blue-100">
                  Pág. {{ invPage }} de {{ Math.ceil(filteredInvoices.length / invPerPage) || 1 }}
                </span>
                <button @click="invPage++" :disabled="invPage >= Math.ceil(filteredInvoices.length / invPerPage)" class="px-3 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">Siguiente</button>
                <button @click="invPage = Math.ceil(filteredInvoices.length / invPerPage) || 1" :disabled="invPage >= Math.ceil(filteredInvoices.length / invPerPage)" class="px-2.5 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">»</button>
              </div>
            </div>
          </div>
        </div>

        <!-- 4. TAB CONTENT: PAGOS -->
        <div v-if="activeTab === 'payments'" class="space-y-6">
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h3 class="text-lg font-bold text-gray-800">Historial de Cobros y Conciliaciones Bancarias</h3>
            <div class="flex items-center gap-3">
              <div class="flex items-center gap-1.5 text-xs text-gray-500">
                <span>Mostrar:</span>
                <select v-model="payPerPage" class="bg-white border border-gray-200 rounded px-2 py-1 text-gray-700 outline-none focus:border-blue-500 font-semibold">
                  <option :value="5">5</option>
                  <option :value="10">10</option>
                  <option :value="20">20</option>
                  <option :value="50">50</option>
                </select>
              </div>
              <div class="relative max-w-xs w-full">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                </span>
                <input type="text" v-model="paySearch" placeholder="Buscar pago..." class="w-full pl-9 pr-4 py-1.5 bg-white border border-gray-200 rounded-lg text-xs outline-none focus:border-blue-500 transition-all font-semibold" />
              </div>
            </div>
          </div>
          <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-gray-50/50 text-gray-400 text-[11px] uppercase tracking-wider border-b border-gray-200">
                  <th class="px-6 py-4">Fecha de Pago</th>
                  <th class="px-6 py-4">Factura Relacionada</th>
                  <th class="px-6 py-4">Método</th>
                  <th class="px-6 py-4">Banco Destino</th>
                  <th class="px-6 py-4">Referencia</th>
                  <th class="px-6 py-4">Monto Aportado</th>
                  <th class="px-6 py-4 text-center">Tasa Aplicada</th>
                  <th class="px-6 py-4 text-right">Equivalencia Histórica</th>
                </tr>
              </thead>
                <tbody class="divide-y divide-gray-100 text-sm text-gray-600">
                  <tr v-for="pay in paginatedPayments" :key="pay.id" class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-6 py-4 font-mono text-xs text-gray-400">
                      {{ new Date(pay.fecha_pago).toLocaleDateString('es-ES') }}
                    </td>
                    <td class="px-6 py-4 font-mono font-bold text-gray-800">
                      {{ pay.invoice?.numero_factura }}
                    </td>
                    <td class="px-6 py-4 capitalize font-semibold text-gray-600">
                      {{ pay.metodo_pago.replace('_', ' ') }}
                    </td>
                    <td class="px-6 py-4 font-bold text-gray-800">
                      {{ pay.banco || 'N/A' }}
                    </td>
                    <td class="px-6 py-4 font-mono text-xs text-blue-600">
                      {{ pay.referencia || 'Efectivo/Caja' }}
                    </td>
                    <td class="px-6 py-4 font-bold text-emerald-600">
                      {{ formatAmount(pay.monto) }}
                    </td>
                    <td class="px-6 py-4 text-center font-bold font-mono text-xs text-gray-500">
                      Bs. {{ parseFloat(pay.tasa_cambio || condominio.tasa_cambio || 36.50).toFixed(2) }}
                    </td>
                    <td class="px-6 py-4 text-right font-semibold text-blue-600">
                      ${{ (pay.monto / (pay.tasa_cambio || condominio.tasa_cambio || 36.50)).toFixed(2) }} USD
                    </td>
                  </tr>
                  <tr v-if="filteredPayments.length === 0">
                    <td colspan="8" class="px-6 py-8 text-center text-gray-400">
                      No se encontraron transacciones de pago con los filtros aplicados.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Footer Paginador DataTable -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
              <div class="text-gray-500">
                Mostrando <span class="font-bold text-gray-800">{{ Math.min((payPage - 1) * payPerPage + 1, filteredPayments.length) }}</span> a
                <span class="font-bold text-gray-800">{{ Math.min(payPage * payPerPage, filteredPayments.length) }}</span> de
                <span class="font-bold text-gray-800">{{ filteredPayments.length }}</span> registros
              </div>
              <div class="flex items-center gap-1.5">
                <button @click="payPage = 1" :disabled="payPage === 1" class="px-2.5 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">«</button>
                <button @click="payPage--" :disabled="payPage === 1" class="px-3 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">Anterior</button>
                <span class="px-3 py-1.5 font-bold text-blue-600 bg-blue-50 rounded border border-blue-100">
                  Pág. {{ payPage }} de {{ Math.ceil(filteredPayments.length / payPerPage) || 1 }}
                </span>
                <button @click="payPage++" :disabled="payPage >= Math.ceil(filteredPayments.length / payPerPage)" class="px-3 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">Siguiente</button>
                <button @click="payPage = Math.ceil(filteredPayments.length / payPerPage) || 1" :disabled="payPage >= Math.ceil(filteredPayments.length / payPerPage)" class="px-2.5 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">»</button>
              </div>
            </div>
          </div>

        <!-- 5. TAB CONTENT: RESERVAS -->
        <div v-if="activeTab === 'reservas'" class="space-y-6">
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h3 class="text-lg font-bold text-gray-800">Solicitudes de Alquiler de Áreas Comunes</h3>
            <div class="flex items-center gap-3">
              <div class="flex items-center gap-1.5 text-xs text-gray-500">
                <span>Mostrar:</span>
                <select v-model="resPerPage" class="bg-white border border-gray-200 rounded px-2 py-1 text-gray-700 outline-none focus:border-blue-500 font-semibold">
                  <option :value="5">5</option>
                  <option :value="10">10</option>
                  <option :value="20">20</option>
                  <option :value="50">50</option>
                </select>
              </div>
              <div class="relative max-w-xs w-full">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                </span>
                <input type="text" v-model="resSearch" placeholder="Buscar reserva..." class="w-full pl-9 pr-4 py-1.5 bg-white border border-gray-200 rounded-lg text-xs outline-none focus:border-blue-500 transition-all font-semibold" />
              </div>
            </div>
          </div>
          <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-gray-50/50 text-gray-400 text-[11px] uppercase tracking-wider border-b border-gray-200">
                  <th class="px-6 py-4">Área Común</th>
                  <th class="px-6 py-4">Propietario / Residente</th>
                  <th class="px-6 py-4">Fecha Reservada</th>
                  <th class="px-6 py-4">Horario</th>
                  <th class="px-6 py-4 text-center">Personas</th>
                  <th class="px-6 py-4">Costo de Uso</th>
                  <th class="px-6 py-4 text-center">Estado</th>
                  <th class="px-6 py-4 text-right">Acciones</th>
                </tr>
              </thead>
                <tbody class="divide-y divide-gray-100 text-sm text-gray-600">
                  <tr v-for="res in paginatedReservations" :key="res.id" class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-6 py-4 font-bold text-gray-900">
                      {{ res.common_area?.nombre }}
                    </td>
                    <td class="px-6 py-4 font-semibold text-gray-700">
                      {{ res.user?.name }} (Apto: {{ res.apartamento?.numero }})
                    </td>
                    <td class="px-6 py-4 font-mono text-xs">
                      {{ new Date(res.fecha_reserva).toLocaleDateString('es-ES') }}
                    </td>
                    <td class="px-6 py-4 text-xs font-semibold text-gray-500">
                      {{ res.hora_inicio.substring(0, 5) }} a {{ res.hora_fin.substring(0, 5) }}
                    </td>
                    <td class="px-6 py-4 text-center">
                      {{ res.numero_personas }} / {{ res.common_area?.capacidad_maxima }}
                    </td>
                    <td class="px-6 py-4 font-bold text-gray-800">
                      {{ formatAmount(res.costo_uso) }}
                    </td>
                    <td class="px-6 py-4 text-center">
                      <span class="px-2.5 py-0.5 text-xs font-bold rounded-full uppercase tracking-wider"
                        :class="{
                          'bg-emerald-100 text-emerald-800': res.estado === 'aprobada',
                          'bg-rose-100 text-rose-800': res.estado === 'rechazada',
                          'bg-amber-100 text-amber-800': res.estado === 'pendiente'
                        }">
                        {{ res.estado }}
                      </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                      <div v-if="res.estado === 'pendiente'" class="flex items-center justify-end gap-2">
                        <button @click="changeResStatus(res, 'aprobada')"
                          class="bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-bold px-2.5 py-1 rounded transition-all">
                          Aprobar
                        </button>
                        <button @click="changeResStatus(res, 'rechazada')"
                          class="bg-rose-50 hover:bg-rose-100 text-rose-800 text-xs font-bold px-2.5 py-1 rounded transition-all">
                          Rechazar
                        </button>
                      </div>
                      <span v-else class="text-xs text-gray-400 italic">Procesado</span>
                    </td>
                  </tr>
                  <tr v-if="filteredReservations.length === 0">
                    <td colspan="8" class="px-6 py-8 text-center text-gray-400">
                      No se encontraron solicitudes de reserva con los filtros aplicados.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Footer Paginador DataTable -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
              <div class="text-gray-500">
                Mostrando <span class="font-bold text-gray-800">{{ Math.min((resPage - 1) * resPerPage + 1, filteredReservations.length) }}</span> a
                <span class="font-bold text-gray-800">{{ Math.min(resPage * resPerPage, filteredReservations.length) }}</span> de
                <span class="font-bold text-gray-800">{{ filteredReservations.length }}</span> registros
              </div>
              <div class="flex items-center gap-1.5">
                <button @click="resPage = 1" :disabled="resPage === 1" class="px-2.5 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">«</button>
                <button @click="resPage--" :disabled="resPage === 1" class="px-3 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">Anterior</button>
                <span class="px-3 py-1.5 font-bold text-blue-600 bg-blue-50 rounded border border-blue-100">
                  Pág. {{ resPage }} de {{ Math.ceil(filteredReservations.length / resPerPage) || 1 }}
                </span>
                <button @click="resPage++" :disabled="resPage >= Math.ceil(filteredReservations.length / resPerPage)" class="px-3 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">Siguiente</button>
                <button @click="resPage = Math.ceil(filteredReservations.length / resPerPage) || 1" :disabled="resPage >= Math.ceil(filteredReservations.length / resPerPage)" class="px-2.5 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">»</button>
              </div>
            </div>
        </div>

    <!-- MODALES DE CONTROL -->

    <!-- Modal Registrar Pago -->
    <div v-if="paymentModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
      <div class="bg-white border border-gray-150 w-full max-w-md rounded-2xl shadow-xl p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4 border-b border-gray-100 pb-2">Registrar Pago: {{ selectedInvoice?.numero_factura }}</h3>
        <p class="text-xs text-gray-500 mb-4">Total de la Factura: <strong>${{ selectedInvoice?.monto_total }} USD</strong> (Recaudado hasta ahora: ${{ selectedInvoice?.monto_pagado }} USD)</p>
        <form @submit.prevent="savePayment" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Monto del Pago (Bs.)</label>
            <input v-model="paymentForm.monto" type="number" step="0.01" required :max="selectedInvoice?.monto_total - selectedInvoice?.monto_pagado" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
            <p class="text-xs text-gray-400 mt-1">
              Equivalente referencial: <strong class="text-blue-600">${{ (paymentForm.monto / (condominio.tasa_cambio || 36.50)).toFixed(2) }} USD</strong>
            </p>
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Método de Pago</label>
            <select v-model="paymentForm.metodo_pago" required class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all">
              <option value="transferencia">Transferencia Bancaria</option>
              <option value="pago_movil">Pago Móvil</option>
              <option value="efectivo">Efectivo</option>
              <option value="deposito">Depósito</option>
              <option value="otro">Otro</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Referencia</label>
            <input v-model="paymentForm.referencia" type="text" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Banco Destinatario</label>
            <select v-model="paymentForm.banco" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all">
              <option value="">Seleccione un banco (Si aplica)</option>
              <option v-for="banco in bancos" :key="banco.id" :value="banco.nombre">{{ banco.nombre }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Observaciones</label>
            <input v-model="paymentForm.observaciones" type="text" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
          </div>
          <div class="flex justify-end gap-3 mt-6">
            <button type="button" @click="paymentModalOpen = false" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2 rounded text-sm transition-all">Cancelar</button>
            <button type="submit" :disabled="paymentForm.processing" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded text-sm transition-all font-semibold shadow">Registrar Pago</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Nuevo Apartamento -->
    <div v-if="aptoModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
      <div class="bg-white border border-gray-150 w-full max-w-md rounded-2xl shadow-xl p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4 border-b border-gray-100 pb-2">Registrar Apartamento</h3>
        <form @submit.prevent="saveApto" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Número de Apartamento</label>
            <input v-model="aptoForm.numero" type="text" required class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Piso</label>
            <input v-model="aptoForm.piso" type="text" required class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Metros Cuadrados (m²)</label>
            <input v-model="aptoForm.metros_cuadrados" type="number" step="0.01" required class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Descripción</label>
            <input v-model="aptoForm.descripcion" type="text" placeholder="Ej: Penthouse con terraza" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
          </div>
          <div class="flex justify-end gap-3 mt-6">
            <button type="button" @click="aptoModalOpen = false" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2 rounded text-sm transition-all">Cancelar</button>
            <button type="submit" :disabled="aptoForm.processing" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded text-sm transition-all font-semibold shadow">Guardar</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Nuevo Propietario -->
    <div v-if="propietarioModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
      <div class="bg-white border border-gray-150 w-full max-w-lg rounded-2xl shadow-xl p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4 border-b border-gray-100 pb-2">Registrar Residente</h3>
        <form @submit.prevent="savePropietario" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Nombre Completo</label>
              <input v-model="propietarioForm.name" type="text" required class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Cédula / Identificación</label>
              <input v-model="propietarioForm.cedula" type="text" required class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Teléfono Movil</label>
              <input v-model="propietarioForm.telefono" type="text" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
            </div>
            <div class="col-span-2">
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Correo Electrónico (Acceso al Portal)</label>
              <input v-model="propietarioForm.email" type="email" required class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
            </div>
            <div class="col-span-2">
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Asignar Apartamento</label>
              <select v-model="propietarioForm.apartamento_id" required class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all">
                <option value="">Seleccione el Apartamento</option>
                <option v-for="apto in apartamentos.filter(a => !a.activo)" :key="apto.id" :value="apto.id">
                  Apto: {{ apto.numero }} (Piso {{ apto.piso }})
                </option>
              </select>
            </div>
          </div>
          <div class="flex justify-end gap-3 mt-6">
            <button type="button" @click="propietarioModalOpen = false" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2 rounded text-sm transition-all">Cancelar</button>
            <button type="submit" :disabled="propietarioForm.processing" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded text-sm transition-all font-semibold shadow">Guardar</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Emitir Factura / Cobro -->
    <div v-if="invoiceModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
      <div class="bg-white border border-gray-150 w-full max-w-md rounded-2xl shadow-xl p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4 border-b border-gray-100 pb-2">Generar Facturación Mensual</h3>
        <form @submit.prevent="saveInvoice" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Seleccionar Apartamento de Destino</label>
            <select v-model="invoiceForm.apartamento_id" required class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all">
              <option value="">Seleccione un Apartamento...</option>
              <option v-for="apto in apartamentos" :key="apto.id" :value="apto.id">
                Apto {{ apto.numero }} (Piso {{ apto.piso }} - {{ apto.activo ? 'Habitado' : 'Vacío' }})
              </option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Monto de la Factura (USD)</label>
            <input v-model="invoiceForm.monto_total" type="number" step="0.01" required class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
            <p class="text-[10px] text-gray-400 mt-1">
              Por defecto se precarga la cuota de mantenimiento base configurada: <strong>${{ (condominio.cuota_mantenimiento_base / (condominio.tasa_cambio || 36.50)).toFixed(2) }} USD</strong>
            </p>
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Fecha de Emisión</label>
            <input v-model="invoiceForm.fecha_emision" type="date" required class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Descripción / Concepto</label>
            <input v-model="invoiceForm.descripcion" type="text" required placeholder="Ej: Gasto común del mes de Julio 2026" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
          </div>
          <div class="flex justify-end gap-3 mt-6">
            <button type="button" @click="invoiceModalOpen = false" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2 rounded text-sm transition-all">Cancelar</button>
            <button type="submit" :disabled="invoiceForm.processing" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded text-sm transition-all font-semibold shadow">Generar</button>
          </div>
        </form>
      </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
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
  condominio: Object,
  apartamentos: Array,
  propietarios: Array,
  invoices: Array,
  payments: Array,
  reservations: Array,
  commonAreas: Array,
  reportes: Object,
  bancos: Array
});

const activeTab = ref('apartamentos');
const currency = ref('VES'); // Moneda de visualización por defecto Bs

// Modales
const aptoModalOpen = ref(false);
const propietarioModalOpen = ref(false);
const invoiceModalOpen = ref(false);
const paymentModalOpen = ref(false);
const selectedInvoice = ref(null);

// Forms
const tasaCambioForm = useForm({
  tasa_cambio: props.condominio.tasa_cambio || 36.50
});

const aptoForm = useForm({
  numero: '',
  piso: '',
  metros_cuadrados: 0,
  descripcion: ''
});

const propietarioForm = useForm({
  name: '',
  email: '',
  cedula: '',
  telefono: '',
  apartamento_id: ''
});

const invoiceForm = useForm({
  apartamento_id: '',
  monto_total: (props.condominio.cuota_mantenimiento_base / (props.condominio.tasa_cambio || 36.50)).toFixed(2),
  fecha_emision: new Date().toISOString().split('T')[0],
  descripcion: ''
});

const paymentForm = useForm({
  monto: 0,
  metodo_pago: 'transferencia',
  referencia: '',
  banco: '',
  observaciones: ''
});

const statusForm = useForm({
  estado: '',
  notas: ''
});

// DataTable - Apartamentos
const aptoSearch = ref('');
const aptoPage = ref(1);
const aptoPerPage = ref(10);
const filteredApartamentos = computed(() => {
  const q = aptoSearch.value.toLowerCase().trim();
  if (!q) return props.apartamentos;
  return props.apartamentos.filter(a => 
    a.numero.toLowerCase().includes(q) || 
    a.piso.toLowerCase().includes(q) ||
    (a.descripcion || '').toLowerCase().includes(q)
  );
});
const paginatedApartamentos = computed(() => {
  const start = (aptoPage.value - 1) * aptoPerPage.value;
  return filteredApartamentos.value.slice(start, start + aptoPerPage.value);
});
watch(aptoSearch, () => { aptoPage.value = 1; });

// DataTable - Propietarios
const propSearch = ref('');
const propPage = ref(1);
const propPerPage = ref(10);
const filteredPropietarios = computed(() => {
  const q = propSearch.value.toLowerCase().trim();
  if (!q) return props.propietarios;
  return props.propietarios.filter(p => 
    (p.user?.name || '').toLowerCase().includes(q) || 
    p.cedula.toLowerCase().includes(q) ||
    p.telefono.toLowerCase().includes(q) ||
    (p.user?.email || '').toLowerCase().includes(q) ||
    (p.apartamento?.numero || '').toLowerCase().includes(q)
  );
});
const paginatedPropietarios = computed(() => {
  const start = (propPage.value - 1) * propPerPage.value;
  return filteredPropietarios.value.slice(start, start + propPerPage.value);
});
watch(propSearch, () => { propPage.value = 1; });

// DataTable - Invoices (Facturas)
const invSearch = ref('');
const invPage = ref(1);
const invPerPage = ref(10);
const filteredInvoices = computed(() => {
  const q = invSearch.value.toLowerCase().trim();
  if (!q) return props.invoices;
  return props.invoices.filter(i => 
    i.numero_factura.toLowerCase().includes(q) || 
    (i.apartamento?.numero || '').toLowerCase().includes(q) ||
    i.estado_pago.toLowerCase().includes(q) ||
    (i.descripcion || '').toLowerCase().includes(q)
  );
});
const paginatedInvoices = computed(() => {
  const start = (invPage.value - 1) * invPerPage.value;
  return filteredInvoices.value.slice(start, start + invPerPage.value);
});
watch(invSearch, () => { invPage.value = 1; });

// DataTable - Payments (Pagos)
const paySearch = ref('');
const payPage = ref(1);
const payPerPage = ref(10);
const filteredPayments = computed(() => {
  const q = paySearch.value.toLowerCase().trim();
  if (!q) return props.payments;
  return props.payments.filter(p => 
    (p.invoice?.numero_factura || '').toLowerCase().includes(q) || 
    p.metodo_pago.toLowerCase().includes(q) || 
    (p.banco || '').toLowerCase().includes(q) || 
    (p.referencia || '').toLowerCase().includes(q)
  );
});
const paginatedPayments = computed(() => {
  const start = (payPage.value - 1) * payPerPage.value;
  return filteredPayments.value.slice(start, start + payPerPage.value);
});
watch(paySearch, () => { payPage.value = 1; });

// DataTable - Reservas
const resSearch = ref('');
const resPage = ref(1);
const resPerPage = ref(10);
const filteredReservations = computed(() => {
  const q = resSearch.value.toLowerCase().trim();
  if (!q) return props.reservations;
  return props.reservations.filter(r => 
    (r.common_area?.nombre || '').toLowerCase().includes(q) || 
    (r.user?.name || '').toLowerCase().includes(q) || 
    (r.apartamento?.numero || '').toLowerCase().includes(q) ||
    r.estado.toLowerCase().includes(q)
  );
});
const paginatedReservations = computed(() => {
  const start = (resPage.value - 1) * resPerPage.value;
  return filteredReservations.value.slice(start, start + resPerPage.value);
});
watch(resSearch, () => { resPage.value = 1; });

// Conversión y Formateo según divisa seleccionada
const formatAmount = (amountInVES) => {
  if (currency.value === 'VES') {
    return new Intl.NumberFormat('es-VE', { style: 'currency', currency: 'VES' }).format(amountInVES);
  } else {
    const usdVal = amountInVES / (props.condominio.tasa_cambio || 36.50);
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(usdVal);
  }
};

// Actions
const logout = () => {
  router.post('/logout');
};

const updateTasa = () => {
  tasaCambioForm.put('/condominios/tasa-cambio', {
    preserveScroll: true
  });
};

const openCreateAptoModal = () => {
  aptoForm.reset();
  aptoModalOpen.value = true;
};

const saveApto = () => {
  aptoForm.post('/apartamentos', {
    onSuccess: () => {
      aptoModalOpen.value = false;
    }
  });
};

const openCreatePropietarioModal = () => {
  propietarioForm.reset();
  propietarioModalOpen.value = true;
};

const savePropietario = () => {
  propietarioForm.post('/propietarios', {
    onSuccess: () => {
      propietarioModalOpen.value = false;
    }
  });
};

const openCreateInvoiceModal = () => {
  invoiceForm.reset();
  invoiceForm.monto_total = (props.condominio.cuota_mantenimiento_base / (props.condominio.tasa_cambio || 36.50)).toFixed(2);
  invoiceForm.fecha_emision = new Date().toISOString().split('T')[0];
  invoiceModalOpen.value = true;
};

const saveInvoice = () => {
  invoiceForm.post('/invoices', {
    onSuccess: () => {
      invoiceModalOpen.value = false;
    }
  });
};

const openPaymentModal = (invoice) => {
  selectedInvoice.value = invoice;
  paymentForm.reset();
  // El monto total restante por pagar en Bolívares
  paymentForm.monto = (invoice.monto_total - invoice.monto_pagado).toFixed(2);
  paymentModalOpen.value = true;
};

const savePayment = () => {
  paymentForm.post(`/invoices/${selectedInvoice.value.id}/payments`, {
    onSuccess: () => {
      paymentModalOpen.value = false;
    }
  });
};

const changeResStatus = (reserva, status) => {
  statusForm.estado = status;
  statusForm.notas = `Procesada por el Administrador el día ${new Date().toLocaleDateString('es-ES')}`;
  statusForm.post(`/reservas/${reserva.id}/status`, {
    preserveScroll: true
  });
};
</script>
