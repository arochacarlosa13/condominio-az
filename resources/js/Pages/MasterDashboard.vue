<template>
  <div class="min-h-screen bg-gray-50 text-slate-700 flex font-sans">
    <!-- SIDEBAR LATERAL (Estilo Argon Dashboard) -->
    <aside class="w-64 bg-white border-r border-gray-200 flex flex-col fixed inset-y-0 left-0 z-30 shadow-sm">
      <!-- Logo / Marca -->
      <div class="h-16 flex items-center gap-3 px-6 border-b border-gray-100">
        <span class="text-xl font-black bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">AZPRO</span>
        <span class="bg-blue-100 text-blue-800 text-[9px] uppercase font-extrabold tracking-widest px-2 py-0.5 rounded">Master</span>
      </div>

      <!-- Enlaces de Navegación Lateral -->
      <nav class="flex-1 px-4 py-6 space-y-2">
        <div class="text-[10px] uppercase font-bold tracking-wider text-gray-400 px-3 mb-3">Menú Principal</div>
        
        <button @click="activeTab = 'condominios'"
          :class="[activeTab === 'condominios' ? 'bg-blue-50 text-blue-600 font-bold' : 'text-gray-600 hover:bg-gray-50', 'w-full flex items-center px-3 py-2 text-xs rounded-lg transition-colors']">
          <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
          Condominios Registrados
        </button>
        <button @click="activeTab = 'planes'"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-all"
          :class="activeTab === 'bancos' ? 'bg-blue-50 text-blue-600 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'">
          <!-- Icono de Bancos -->
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
          </svg>
          Bancos de Venezuela
        </button>

        <button @click="activeTab = 'auditoria'"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-all"
          :class="activeTab === 'auditoria' ? 'bg-blue-50 text-blue-600 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'">
          <!-- Icono de Auditoría -->
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
          </svg>
          Auditoría
        </button>
      </nav>

      <!-- Pie de Sidebar con Perfil -->
      <div class="p-4 border-t border-gray-100 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-sm shadow">
            {{ $page.props.auth.user.name.charAt(0) }}
          </div>
          <div class="overflow-hidden">
            <p class="text-xs font-bold text-gray-800 truncate">{{ $page.props.auth.user.name }}</p>
            <p class="text-[10px] text-gray-400 uppercase truncate">Super Admin</p>
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
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Administración Central</h1>
            <p class="text-blue-100 text-xs mt-1">Monitorea y configura los recursos del ecosistema TorreViva.</p>
          </div>
          <!-- Botón de acción rápido contextual -->
          <div class="flex gap-2">
            <button v-if="activeTab === 'condominios'" @click="openCreateModal" class="bg-white hover:bg-gray-50 text-blue-600 font-bold text-xs px-4 py-2 rounded-lg shadow transition-all">
              + Registrar Condominio
            </button>
            <button v-if="activeTab === 'planes'" @click="openCreatePlanModal" class="bg-white hover:bg-gray-50 text-blue-600 font-bold text-xs px-4 py-2 rounded-lg shadow transition-all">
              + Crear Plan
            </button>
            <button v-if="activeTab === 'bancos'" @click="openCreateBancoModal" class="bg-white hover:bg-gray-50 text-blue-600 font-bold text-xs px-4 py-2 rounded-lg shadow transition-all">
              + Registrar Banco
            </button>
          </div>
        </div>
      </div>

      <!-- SECCIÓN DE CONTENIDO FLOTANTE -->
      <div class="px-8 -mt-24 pb-12 flex-1 space-y-8 z-10">
        <!-- El sistema utiliza SweetAlert para las notificaciones en tiempo real -->

        <!-- 1. TAB: CONDOMINIOS -->
        <div v-if="activeTab === 'condominios'" class="space-y-8">
          <!-- Métricas e Indicadores de Solvencia -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white border border-gray-150 p-5 rounded-xl shadow-sm flex flex-col justify-between">
              <div>
                <p class="text-gray-400 text-[10px] uppercase font-bold tracking-wider">Condominios Totales</p>
                <p class="text-2xl font-black mt-1 text-gray-800">{{ condominios.length }}</p>
              </div>
              <span class="text-[10px] text-gray-400 mt-4 block">Clientes en plataforma</span>
            </div>
            
            <div class="bg-white border border-gray-150 p-5 rounded-xl shadow-sm flex flex-col justify-between">
              <div>
                <p class="text-gray-400 text-[10px] uppercase font-bold tracking-wider">Apartamentos Suscritos</p>
                <p class="text-2xl font-black mt-1 text-gray-800">{{ totalApartamentos }}</p>
              </div>
              <span class="text-[10px] text-gray-400 mt-4 block">Unidades residenciales</span>
            </div>

            <div class="bg-white border border-gray-150 p-5 rounded-xl shadow-sm flex flex-col justify-between">
              <div>
                <p class="text-gray-400 text-[10px] uppercase font-bold tracking-wider">Recaudación SaaS</p>
                <p class="text-2xl font-black mt-1 text-blue-600">${{ totalRecaudadoProyectado.toFixed(2) }}</p>
              </div>
              <span class="text-[10px] text-gray-400 mt-4 block">Monto proyectado mensual</span>
            </div>

            <!-- Gráfico Donut de Solvencia -->
            <div class="bg-white border border-gray-150 p-5 rounded-xl shadow-sm flex items-center justify-between gap-4">
              <div class="space-y-1">
                <p class="text-gray-400 text-[10px] uppercase font-bold tracking-wider">Estatus Clientes</p>
                <div class="flex items-center gap-1.5 text-xs font-semibold text-gray-700">
                  <span class="w-2.5 h-2.5 bg-emerald-500 rounded-full"></span>
                  <span>Activos: {{ condominiosSolventes }}</span>
                </div>
                <div class="flex items-center gap-1.5 text-xs font-semibold text-gray-700">
                  <span class="w-2.5 h-2.5 bg-rose-500 rounded-full"></span>
                  <span>Morosos: {{ condominiosMorosos }}</span>
                </div>
              </div>
              
              <!-- SVG Donut Chart -->
              <div class="relative flex items-center justify-center">
                <svg viewBox="0 0 36 36" class="w-16 h-16 transform -rotate-90">
                  <circle cx="18" cy="18" r="15.915" fill="none" stroke="#f43f5e" stroke-width="3"></circle>
                  <circle cx="18" cy="18" r="15.915" fill="none" stroke="#10b981" stroke-width="3"
                    :stroke-dasharray="`${solventesPercent} ${100 - solventesPercent}`"
                    stroke-dashoffset="0"></circle>
                </svg>
                <div class="absolute text-center">
                  <span class="text-[10px] font-bold text-gray-700">{{ Math.round(solventesPercent) }}%</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Tabla de Clientes con controles DataTable -->
          <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <h3 class="font-bold text-gray-800">Clientes SaaS Registrados</h3>
              
              <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5 text-xs text-gray-500">
                  <span>Mostrar:</span>
                  <select v-model="condoPerPage" class="bg-white border border-gray-200 rounded px-2 py-1 text-gray-700 outline-none focus:border-blue-500 font-semibold">
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
                  <input type="text" v-model="condoSearch" placeholder="Buscar condominio..." class="w-full pl-9 pr-4 py-1.5 bg-white border border-gray-200 rounded-lg text-xs outline-none focus:border-blue-500 transition-all font-semibold" />
                </div>
              </div>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="border-b border-gray-200 text-gray-400 text-[11px] uppercase tracking-wider bg-gray-50/50">
                    <th class="px-6 py-4">Condominio</th>
                    <th class="px-6 py-4">RIF / Contacto</th>
                    <th class="px-6 py-4 text-center">Apartamentos</th>
                    <th class="px-6 py-4">Plan SaaS</th>
                    <th class="px-6 py-4 text-center">Recaudación</th>
                    <th class="px-6 py-4">Estado Suscripción</th>
                    <th class="px-6 py-4 text-right">Acciones</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm text-gray-600">
                  <tr v-for="condo in paginatedCondominios" :key="condo.id" class="hover:bg-gray-50/60 transition-colors">
                    <td class="px-6 py-4">
                      <div class="font-bold text-gray-900">{{ condo.nombre }}</div>
                      <div class="text-xs text-gray-400 mt-0.5">{{ condo.direccion }}</div>
                    </td>
                    <td class="px-6 py-4">
                      <div class="font-mono text-xs text-gray-700">{{ condo.rif }}</div>
                      <div class="text-xs text-gray-400 mt-0.5">{{ condo.email || 'Sin correo' }}</div>
                    </td>
                    <td class="px-6 py-4 text-center font-semibold">
                      {{ condo.numero_apartamentos }}
                    </td>
                    <td class="px-6 py-4">
                      <span class="px-2 py-0.5 text-xs font-semibold rounded uppercase border"
                        :class="condo.plan ? 'bg-blue-50 text-blue-600 border-blue-100' : 'bg-gray-100 text-gray-400 border-gray-200'">
                        {{ condo.plan ? condo.plan.nombre : 'Sin Plan' }}
                      </span>
                    </td>
                    <td class="px-6 py-4 text-center font-bold text-gray-800">
                      ${{ (condo.costo_suscripcion || 0).toFixed(2) }}
                    </td>
                    <td class="px-6 py-4">
                      <span class="px-2.5 py-0.5 text-xs font-bold rounded-full uppercase tracking-wider"
                        :class="{
                          'bg-emerald-100 text-emerald-800': condo.estado_suscripcion === 'activo',
                          'bg-amber-100 text-amber-800': condo.estado_suscripcion === 'vencido',
                          'bg-rose-100 text-rose-800': condo.estado_suscripcion === 'suspendido'
                        }">
                        {{ condo.estado_suscripcion }}
                      </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                      <div class="flex items-center justify-end gap-2">
                        <button @click="openEditModal(condo)" class="bg-blue-50 hover:bg-blue-100 text-blue-600 text-xs font-bold px-2.5 py-1.5 rounded transition-all">
                          Suscripción
                        </button>
                        <button @click="openEditCondoModal(condo)" class="bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-bold px-2.5 py-1.5 rounded transition-all">
                          Editar
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="filteredCondominios.length === 0">
                    <td colspan="7" class="px-6 py-8 text-center text-gray-400">
                      No se encontraron condominios con los filtros aplicados.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Footer Paginador DataTable -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
              <div class="text-gray-500">
                Mostrando <span class="font-bold text-gray-800">{{ Math.min((condoPage - 1) * condoPerPage + 1, filteredCondominios.length) }}</span> a
                <span class="font-bold text-gray-800">{{ Math.min(condoPage * condoPerPage, filteredCondominios.length) }}</span> de
                <span class="font-bold text-gray-800">{{ filteredCondominios.length }}</span> registros
              </div>
              <div class="flex items-center gap-1.5">
                <button @click="condoPage = 1" :disabled="condoPage === 1" class="px-2.5 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">«</button>
                <button @click="condoPage--" :disabled="condoPage === 1" class="px-3 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">Anterior</button>
                <span class="px-3 py-1.5 font-bold text-blue-600 bg-blue-50 rounded border border-blue-100">
                  Pág. {{ condoPage }} de {{ Math.ceil(filteredCondominios.length / condoPerPage) || 1 }}
                </span>
                <button @click="condoPage++" :disabled="condoPage >= Math.ceil(filteredCondominios.length / condoPerPage)" class="px-3 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">Siguiente</button>
                <button @click="condoPage = Math.ceil(filteredCondominios.length / condoPerPage) || 1" :disabled="condoPage >= Math.ceil(filteredCondominios.length / condoPerPage)" class="px-2.5 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">»</button>
              </div>
            </div>
          </div>
        </div>

        <!-- 2. TAB: PLANES DE SUSCRIPCIÓN -->
        <div v-if="activeTab === 'planes'" class="space-y-6">
          <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <h3 class="font-bold text-gray-800">Tarifas y Planes SaaS</h3>
              
              <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5 text-xs text-gray-500">
                  <span>Mostrar:</span>
                  <select v-model="planPerPage" class="bg-white border border-gray-200 rounded px-2 py-1 text-gray-700 outline-none focus:border-blue-500 font-semibold">
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
                  <input type="text" v-model="planSearch" placeholder="Buscar plan..." class="w-full pl-9 pr-4 py-1.5 bg-white border border-gray-200 rounded-lg text-xs outline-none focus:border-blue-500 transition-all font-semibold" />
                </div>
              </div>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="border-b border-gray-200 text-gray-400 text-[11px] uppercase tracking-wider bg-gray-50/50">
                    <th class="px-6 py-4">Nombre del Plan</th>
                    <th class="px-6 py-4">Descripción</th>
                    <th class="px-6 py-4">Costo Fijo Base</th>
                    <th class="px-6 py-4">Costo por Apartamento</th>
                    <th class="px-6 py-4 text-center">Estado</th>
                    <th class="px-6 py-4 text-right">Acciones</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm text-gray-600">
                  <tr v-for="plan in paginatedPlanes" :key="plan.id" class="hover:bg-gray-50/60 transition-colors">
                    <td class="px-6 py-4 font-bold text-gray-900 uppercase">
                      {{ plan.nombre }}
                    </td>
                    <td class="px-6 py-4 text-gray-400 max-w-xs truncate">
                      {{ plan.descripcion }}
                    </td>
                    <td class="px-6 py-4 font-semibold text-gray-800">
                      ${{ parseFloat(plan.costo_base).toFixed(2) }}
                    </td>
                    <td class="px-6 py-4 font-semibold text-gray-800">
                      ${{ parseFloat(plan.costo_por_apartamento).toFixed(2) }}
                    </td>
                    <td class="px-6 py-4 text-center">
                      <span class="px-2 py-0.5 text-xs font-semibold rounded uppercase border"
                        :class="plan.activo ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-gray-100 text-gray-400 border-gray-200'">
                        {{ plan.activo ? 'Activo' : 'Inactivo' }}
                      </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                      <div class="flex items-center justify-end gap-2">
                        <button @click="openEditPlanModal(plan)" class="bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-bold px-2.5 py-1.5 rounded transition-all">
                          Editar
                        </button>
                        <button @click="deletePlan(plan.id)" class="bg-rose-50 hover:bg-rose-100 text-rose-600 text-xs font-bold px-2.5 py-1.5 rounded transition-all">
                          Eliminar
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="filteredPlanes.length === 0">
                    <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                      No se encontraron planes con los filtros aplicados.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Footer Paginador DataTable -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
              <div class="text-gray-500">
                Mostrando <span class="font-bold text-gray-800">{{ Math.min((planPage - 1) * planPerPage + 1, filteredPlanes.length) }}</span> a
                <span class="font-bold text-gray-800">{{ Math.min(planPage * planPerPage, filteredPlanes.length) }}</span> de
                <span class="font-bold text-gray-800">{{ filteredPlanes.length }}</span> registros
              </div>
              <div class="flex items-center gap-1.5">
                <button @click="planPage = 1" :disabled="planPage === 1" class="px-2.5 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">«</button>
                <button @click="planPage--" :disabled="planPage === 1" class="px-3 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">Anterior</button>
                <span class="px-3 py-1.5 font-bold text-blue-600 bg-blue-50 rounded border border-blue-100">
                  Pág. {{ planPage }} de {{ Math.ceil(filteredPlanes.length / planPerPage) || 1 }}
                </span>
                <button @click="planPage++" :disabled="planPage >= Math.ceil(filteredPlanes.length / planPerPage)" class="px-3 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">Siguiente</button>
                <button @click="planPage = Math.ceil(filteredPlanes.length / planPerPage) || 1" :disabled="planPage >= Math.ceil(filteredPlanes.length / planPerPage)" class="px-2.5 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">»</button>
              </div>
            </div>
          </div>
        </div>

        <!-- 3. TAB: BANCOS DE VENEZUELA -->
        <div v-if="activeTab === 'bancos'" class="space-y-6">
          <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <h3 class="font-bold text-gray-800">Catálogo de Bancos Nacionales</h3>
              
              <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5 text-xs text-gray-500">
                  <span>Mostrar:</span>
                  <select v-model="bancoPerPage" class="bg-white border border-gray-200 rounded px-2 py-1 text-gray-700 outline-none focus:border-blue-500 font-semibold">
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
                  <input type="text" v-model="bancoSearch" placeholder="Buscar banco..." class="w-full pl-9 pr-4 py-1.5 bg-white border border-gray-200 rounded-lg text-xs outline-none focus:border-blue-500 transition-all font-semibold" />
                </div>
              </div>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="border-b border-gray-200 text-gray-400 text-[11px] uppercase tracking-wider bg-gray-50/50">
                    <th class="px-6 py-4">Código</th>
                    <th class="px-6 py-4">Nombre del Banco</th>
                    <th class="px-6 py-4 text-center">Estado</th>
                    <th class="px-6 py-4 text-right">Acciones</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm text-gray-600">
                  <tr v-for="banco in paginatedBancos" :key="banco.id" class="hover:bg-gray-50/60 transition-colors">
                    <td class="px-6 py-4 font-mono font-bold text-blue-600">
                      {{ banco.codigo || 'N/A' }}
                    </td>
                    <td class="px-6 py-4 font-bold text-gray-900">
                      {{ banco.nombre }}
                    </td>
                    <td class="px-6 py-4 text-center">
                      <span class="px-2 py-0.5 text-xs font-semibold rounded uppercase tracking-wider"
                        :class="banco.activo ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-gray-100 text-gray-400 border-gray-200'">
                        {{ banco.activo ? 'Activo' : 'Inactivo' }}
                      </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                      <div class="flex items-center justify-end gap-2">
                        <button @click="openEditBancoModal(banco)" class="bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-bold px-2.5 py-1.5 rounded transition-all">
                          Editar
                        </button>
                        <button @click="deleteBanco(banco.id)" class="bg-rose-50 hover:bg-rose-100 text-rose-600 text-xs font-bold px-2.5 py-1.5 rounded transition-all">
                          Eliminar
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="filteredBancos.length === 0">
                    <td colspan="4" class="px-6 py-8 text-center text-gray-400">
                      No se encontraron bancos con los filtros aplicados.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Footer Paginador DataTable -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
              <div class="text-gray-500">
                Mostrando <span class="font-bold text-gray-800">{{ Math.min((bancoPage - 1) * bancoPerPage + 1, filteredBancos.length) }}</span> a
                <span class="font-bold text-gray-800">{{ Math.min(bancoPage * bancoPerPage, filteredBancos.length) }}</span> de
                <span class="font-bold text-gray-800">{{ filteredBancos.length }}</span> registros
              </div>
              <div class="flex items-center gap-1.5">
                <button @click="bancoPage = 1" :disabled="bancoPage === 1" class="px-2.5 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">«</button>
                <button @click="bancoPage--" :disabled="bancoPage === 1" class="px-3 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">Anterior</button>
                <span class="px-3 py-1.5 font-bold text-blue-600 bg-blue-50 rounded border border-blue-100">
                  Pág. {{ bancoPage }} de {{ Math.ceil(filteredBancos.length / bancoPerPage) || 1 }}
                </span>
                <button @click="bancoPage++" :disabled="bancoPage >= Math.ceil(filteredBancos.length / bancoPerPage)" class="px-3 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">Siguiente</button>
                <button @click="bancoPage = Math.ceil(filteredBancos.length / bancoPerPage) || 1" :disabled="bancoPage >= Math.ceil(filteredBancos.length / bancoPerPage)" class="px-2.5 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">»</button>
              </div>
            </div>
          </div>
        </div>

        <!-- 4. TAB: AUDITORÍA -->
        <div v-if="activeTab === 'auditoria'" class="space-y-6">
          <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <h3 class="font-bold text-gray-800">Bitácora de Eventos de Seguridad</h3>
              
              <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5 text-xs text-gray-500">
                  <span>Mostrar:</span>
                  <select v-model="auditPerPage" class="bg-white border border-gray-200 rounded px-2 py-1 text-gray-700 outline-none focus:border-blue-500 font-semibold">
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
                  <input type="text" v-model="auditSearch" placeholder="Buscar en auditoría..." class="w-full pl-9 pr-4 py-1.5 bg-white border border-gray-200 rounded-lg text-xs outline-none focus:border-blue-500 transition-all font-semibold" />
                </div>
              </div>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="border-b border-gray-200 text-gray-400 text-[11px] uppercase tracking-wider bg-gray-50/50">
                    <th class="px-6 py-4">Fecha y Hora</th>
                    <th class="px-6 py-4">Usuario</th>
                    <th class="px-6 py-4 text-center">Acción</th>
                    <th class="px-6 py-4 text-center">Módulo</th>
                    <th class="px-6 py-4">Detalle</th>
                    <th class="px-6 py-4 text-right">IP</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm text-gray-600">
                  <tr v-for="log in paginatedAuditLogs" :key="log.id" class="hover:bg-gray-50/60 transition-colors">
                    <td class="px-6 py-4 text-gray-400 font-mono text-xs whitespace-nowrap">
                      {{ new Date(log.created_at).toLocaleString('es-ES') }}
                    </td>
                    <td class="px-6 py-4 font-bold text-gray-900">
                      {{ log.user ? log.user.name : 'Sistema/Anónimo' }}
                    </td>
                    <td class="px-6 py-4 text-center">
                      <span class="px-2 py-0.5 text-[10px] font-bold rounded uppercase tracking-wider border"
                        :class="{
                          'bg-emerald-50 text-emerald-600 border-emerald-100': log.accion === 'crear',
                          'bg-amber-50 text-amber-600 border-amber-100': log.accion === 'modificar',
                          'bg-rose-50 text-rose-600 border-rose-100': log.accion === 'eliminar'
                        }">
                        {{ log.accion }}
                      </span>
                    </td>
                    <td class="px-6 py-4 text-center text-xs font-mono text-blue-600">
                      {{ log.modulo }}
                    </td>
                    <td class="px-6 py-4 text-gray-500 text-xs max-w-sm truncate" :title="log.detalle">
                      {{ log.detalle }}
                    </td>
                    <td class="px-6 py-4 font-mono text-xs text-gray-400 text-right">
                      {{ log.ip_address || '127.0.0.1' }}
                    </td>
                  </tr>
                  <tr v-if="filteredAuditLogs.length === 0">
                    <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                      No se encontraron registros de auditoría con los filtros aplicados.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Footer Paginador DataTable -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
              <div class="text-gray-500">
                Mostrando <span class="font-bold text-gray-800">{{ Math.min((auditPage - 1) * auditPerPage + 1, filteredAuditLogs.length) }}</span> a
                <span class="font-bold text-gray-800">{{ Math.min(auditPage * auditPerPage, filteredAuditLogs.length) }}</span> de
                <span class="font-bold text-gray-800">{{ filteredAuditLogs.length }}</span> registros
              </div>
              <div class="flex items-center gap-1.5">
                <button @click="auditPage = 1" :disabled="auditPage === 1" class="px-2.5 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">«</button>
                <button @click="auditPage--" :disabled="auditPage === 1" class="px-3 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">Anterior</button>
                <span class="px-3 py-1.5 font-bold text-blue-600 bg-blue-50 rounded border border-blue-100">
                  Pág. {{ auditPage }} de {{ Math.ceil(filteredAuditLogs.length / auditPerPage) || 1 }}
                </span>
                <button @click="auditPage++" :disabled="auditPage >= Math.ceil(filteredAuditLogs.length / auditPerPage)" class="px-3 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">Siguiente</button>
                <button @click="auditPage = Math.ceil(filteredAuditLogs.length / auditPerPage) || 1" :disabled="auditPage >= Math.ceil(filteredAuditLogs.length / auditPerPage)" class="px-2.5 py-1.5 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-40 transition-all font-semibold">»</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODALES DE CONFIGURACIÓN -->

    <!-- Modal Registrar/Editar Condominio -->
    <div v-if="condoModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 animate-fade-in">
      <div class="bg-white border border-gray-150 w-full max-w-lg rounded-2xl shadow-xl p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4 border-b border-gray-100 pb-2">
          {{ isEditing ? 'Editar Condominio' : 'Registrar Nuevo Condominio' }}
        </h3>
        <form @submit.prevent="saveCondo" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Nombre</label>
              <input v-model="condoForm.nombre" type="text" required class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
            </div>
            <div class="col-span-2">
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Dirección</label>
              <input v-model="condoForm.direccion" type="text" required class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">RIF</label>
              <input v-model="condoForm.rif" type="text" required class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Teléfono</label>
              <input v-model="condoForm.telefono" type="text" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
            </div>
            <div class="col-span-2">
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Correo Electrónico</label>
              <input v-model="condoForm.email" type="email" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Número Apartamentos</label>
              <input v-model="condoForm.numero_apartamentos" type="number" required min="1" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Cuota Mantenimiento Base (Bs)</label>
              <input v-model="condoForm.cuota_mantenimiento_base" type="number" step="0.01" required class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
            </div>
            <div v-if="isEditing">
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Activo</label>
              <select v-model="condoForm.activo" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all">
                <option :value="true">Sí</option>
                <option :value="false">No</option>
              </select>
            </div>
          </div>
          <div class="flex items-center justify-end gap-3 mt-6">
            <button type="button" @click="condoModalOpen = false" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2 rounded text-sm transition-all">Cancelar</button>
            <button type="submit" :disabled="condoForm.processing" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded text-sm transition-all font-semibold shadow">
              {{ condoForm.processing ? 'Guardando...' : 'Guardar' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Suscripción SaaS Dinámica -->
    <div v-if="subscriptionModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
      <div class="bg-white border border-gray-150 w-full max-w-md rounded-2xl shadow-xl p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4 border-b border-gray-100 pb-2">Actualizar Suscripción: {{ selectedCondo?.nombre }}</h3>
        <form @submit.prevent="saveSubscription" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Plan de Suscripción</label>
            <select v-model="subscriptionForm.plan_id" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-850 outline-none focus:border-blue-500 text-sm transition-all">
              <option v-for="plan in planes" :key="plan.id" :value="plan.id">
                {{ plan.nombre.toUpperCase() }} (Base: ${{ parseFloat(plan.costo_base).toFixed(2) }}, Apto: ${{ parseFloat(plan.costo_por_apartamento).toFixed(2) }})
              </option>
            </select>
            <p class="text-xs text-slate-400 mt-2">
              Costo mensual estimado: <strong class="text-blue-600">${{ currentEstimatedCost.toFixed(2) }} USD</strong>
            </p>
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Fecha de Vencimiento</label>
            <input v-model="subscriptionForm.fecha_vencimiento_suscripcion" type="date" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Estado de Suscripción</label>
            <select v-model="subscriptionForm.estado_suscripcion" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all">
              <option value="activo">Activo</option>
              <option value="vencido">Vencido</option>
              <option value="suspendido">Suspendido</option>
            </select>
          </div>
          <div class="flex items-center justify-end gap-3 mt-6">
            <button type="button" @click="subscriptionModalOpen = false" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2 rounded text-sm transition-all">Cancelar</button>
            <button type="submit" :disabled="subscriptionForm.processing" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded text-sm transition-all font-semibold shadow">
              {{ subscriptionForm.processing ? 'Guardando...' : 'Guardar Cambios' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Registrar/Editar Banco -->
    <div v-if="bancoModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
      <div class="bg-white border border-gray-150 w-full max-w-md rounded-2xl shadow-xl p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4 border-b border-gray-100 pb-2">
          {{ isEditingBanco ? 'Editar Banco' : 'Nuevo Banco Venezolano' }}
        </h3>
        <form @submit.prevent="saveBanco" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Nombre del Banco</label>
            <input v-model="bancoForm.nombre" type="text" required placeholder="Ej: Banesco" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Código Bancario (4 dígitos)</label>
            <input v-model="bancoForm.codigo" type="text" maxlength="4" placeholder="Ej: 0134" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm font-mono transition-all" />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Estado</label>
            <select v-model="bancoForm.activo" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all">
              <option :value="true">Activo (Visible en pagos)</option>
              <option :value="false">Inactivo (Oculto en pagos)</option>
            </select>
          </div>
          <div class="flex items-center justify-end gap-3 mt-6">
            <button type="button" @click="bancoModalOpen = false" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2 rounded text-sm transition-all">Cancelar</button>
            <button type="submit" :disabled="bancoForm.processing" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded text-sm transition-all font-semibold shadow">
              {{ bancoForm.processing ? 'Guardando...' : 'Guardar' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Registrar/Editar Plan -->
    <div v-if="planModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
      <div class="bg-white border border-gray-150 w-full max-w-md rounded-2xl shadow-xl p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4 border-b border-gray-100 pb-2">
          {{ isEditingPlan ? 'Editar Plan de Suscripción' : 'Nuevo Plan de Suscripción' }}
        </h3>
        <form @submit.prevent="savePlan" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Nombre del Plan</label>
            <input v-model="planForm.nombre" type="text" required placeholder="Ej: Premium" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Descripción</label>
            <textarea v-model="planForm.descripcion" placeholder="Describa los alcances del plan..." class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm h-20 resize-none transition-all"></textarea>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Costo Fijo Base (USD)</label>
              <input v-model="planForm.costo_base" type="number" step="0.01" required min="0" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Costo / Apto (USD)</label>
              <input v-model="planForm.costo_por_apartamento" type="number" step="0.01" required min="0" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all" />
            </div>
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Estado</label>
            <select v-model="planForm.activo" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 outline-none focus:border-blue-500 text-sm transition-all">
              <option :value="true">Activo</option>
              <option :value="false">Inactivo</option>
            </select>
          </div>
          <div class="flex items-center justify-end gap-3 mt-6">
            <button type="button" @click="planModalOpen = false" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2 rounded text-sm transition-all">Cancelar</button>
            <button type="submit" :disabled="planForm.processing" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded text-sm transition-all font-semibold shadow">
              {{ planForm.processing ? 'Guardando...' : 'Guardar' }}
            </button>
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
  condominios: Array,
  bancos: Array,
  planes: Array,
  auditLogs: Array
});

const condoModalOpen = ref(false);
const subscriptionModalOpen = ref(false);
const isEditing = ref(false);
const selectedCondo = ref(null);

const activeTab = ref('condominios');

const bancoModalOpen = ref(false);
const isEditingBanco = ref(false);
const selectedBanco = ref(null);

const planModalOpen = ref(false);
const isEditingPlan = ref(false);
const selectedPlan = ref(null);

// Forms
const condoForm = useForm({
  id: null,
  nombre: '',
  direccion: '',
  rif: '',
  telefono: '',
  email: '',
  numero_apartamentos: 1,
  cuota_mantenimiento_base: 0,
  activo: true
});

const subscriptionForm = useForm({
  plan_id: '',
  fecha_vencimiento_suscripcion: '',
  estado_suscripcion: 'activo'
});

const bancoForm = useForm({
  id: null,
  nombre: '',
  codigo: '',
  activo: true
});

const planForm = useForm({
  id: null,
  nombre: '',
  descripcion: '',
  costo_base: 0.00,
  costo_por_apartamento: 0.00,
  activo: true
});

// DataTable - Condominios
const condoSearch = ref('');
const condoPage = ref(1);
const condoPerPage = ref(10);
const filteredCondominios = computed(() => {
  const q = condoSearch.value.toLowerCase().trim();
  if (!q) return props.condominios;
  return props.condominios.filter(c => 
    c.nombre.toLowerCase().includes(q) || 
    c.rif.toLowerCase().includes(q) ||
    (c.plan?.nombre || '').toLowerCase().includes(q)
  );
});
const paginatedCondominios = computed(() => {
  const start = (condoPage.value - 1) * condoPerPage.value;
  return filteredCondominios.value.slice(start, start + condoPerPage.value);
});
watch(condoSearch, () => { condoPage.value = 1; });

// DataTable - Planes
const planSearch = ref('');
const planPage = ref(1);
const planPerPage = ref(10);
const filteredPlanes = computed(() => {
  const q = planSearch.value.toLowerCase().trim();
  if (!q) return props.planes;
  return props.planes.filter(p => 
    p.nombre.toLowerCase().includes(q) || 
    (p.descripcion || '').toLowerCase().includes(q)
  );
});
const paginatedPlanes = computed(() => {
  const start = (planPage.value - 1) * planPerPage.value;
  return filteredPlanes.value.slice(start, start + planPerPage.value);
});
watch(planSearch, () => { planPage.value = 1; });

// DataTable - Bancos
const bancoSearch = ref('');
const bancoPage = ref(1);
const bancoPerPage = ref(10);
const filteredBancos = computed(() => {
  const q = bancoSearch.value.toLowerCase().trim();
  if (!q) return props.bancos;
  return props.bancos.filter(b => 
    b.nombre.toLowerCase().includes(q) || 
    (b.codigo || '').toLowerCase().includes(q)
  );
});
const paginatedBancos = computed(() => {
  const start = (bancoPage.value - 1) * bancoPerPage.value;
  return filteredBancos.value.slice(start, start + bancoPerPage.value);
});
watch(bancoSearch, () => { bancoPage.value = 1; });

// DataTable - Auditoría
const auditSearch = ref('');
const auditPage = ref(1);
const auditPerPage = ref(10);
const filteredAuditLogs = computed(() => {
  const q = auditSearch.value.toLowerCase().trim();
  if (!q) return props.auditLogs;
  return props.auditLogs.filter(log => 
    (log.user?.name || '').toLowerCase().includes(q) || 
    log.accion.toLowerCase().includes(q) || 
    log.modulo.toLowerCase().includes(q) || 
    log.detalle.toLowerCase().includes(q) ||
    (log.ip_address || '').toLowerCase().includes(q)
  );
});
const paginatedAuditLogs = computed(() => {
  const start = (auditPage.value - 1) * auditPerPage.value;
  return filteredAuditLogs.value.slice(start, start + auditPerPage.value);
});
watch(auditSearch, () => { auditPage.value = 1; });

// Computed Metrics
const totalApartamentos = computed(() => {
  return props.condominios.reduce((acc, condo) => acc + (condo.numero_apartamentos || 0), 0);
});

const totalRecaudadoProyectado = computed(() => {
  return props.condominios.reduce((acc, condo) => acc + (parseFloat(condo.costo_suscripcion || 0)), 0);
});

const condominiosSolventes = computed(() => {
  return props.condominios.filter(c => c.estado_suscripcion === 'activo').length;
});

const condominiosMorosos = computed(() => {
  return props.condominios.filter(c => c.estado_suscripcion !== 'activo').length;
});

const solventesPercent = computed(() => {
  const total = props.condominios.length;
  return total > 0 ? (condominiosSolventes.value / total) * 100 : 0;
});

const currentEstimatedCost = computed(() => {
  if (!selectedCondo.value) return 0;
  const plan = props.planes.find(p => p.id === subscriptionForm.plan_id);
  if (!plan) return 0;
  return parseFloat(plan.costo_base) + (parseFloat(plan.costo_por_apartamento) * (selectedCondo.value.numero_apartamentos || 0));
});

// Actions
const logout = () => {
  router.post('/logout');
};

const openCreateModal = () => {
  isEditing.value = false;
  condoForm.reset();
  condoModalOpen.value = true;
};

const openEditCondoModal = (condo) => {
  isEditing.value = true;
  condoForm.id = condo.id;
  condoForm.nombre = condo.nombre;
  condoForm.direccion = condo.direccion;
  condoForm.rif = condo.rif;
  condoForm.telefono = condo.telefono;
  condoForm.email = condo.email;
  condoForm.numero_apartamentos = condo.numero_apartamentos;
  condoForm.cuota_mantenimiento_base = condo.cuota_mantenimiento_base;
  condoForm.activo = condo.activo;
  condoModalOpen.value = true;
};

const openEditModal = (condo) => {
  selectedCondo.value = condo;
  subscriptionForm.plan_id = condo.plan_id || (props.planes.length > 0 ? props.planes[0].id : '');
  subscriptionForm.fecha_vencimiento_suscripcion = condo.fecha_vencimiento_suscripcion ? condo.fecha_vencimiento_suscripcion.split('T')[0] : '';
  subscriptionForm.estado_suscripcion = condo.estado_suscripcion || 'activo';
  subscriptionModalOpen.value = true;
};

const saveCondo = () => {
  if (isEditing.value) {
    condoForm.put(`/condominios/${condoForm.id}`, {
      onSuccess: () => {
        condoModalOpen.value = false;
      }
    });
  } else {
    condoForm.post('/condominios', {
      onSuccess: () => {
        condoModalOpen.value = false;
      }
    });
  }
};

const saveSubscription = () => {
  subscriptionForm.put(`/suscripciones/${selectedCondo.value.id}`, {
    onSuccess: () => {
      subscriptionModalOpen.value = false;
    }
  });
};

const openCreateBancoModal = () => {
  isEditingBanco.value = false;
  bancoForm.reset();
  bancoModalOpen.value = true;
};

const openEditBancoModal = (banco) => {
  isEditingBanco.value = true;
  bancoForm.id = banco.id;
  bancoForm.nombre = banco.nombre;
  bancoForm.codigo = banco.codigo;
  bancoForm.activo = banco.activo;
  bancoModalOpen.value = true;
};

const saveBanco = () => {
  if (isEditingBanco.value) {
    bancoForm.put(`/bancos/${bancoForm.id}`, {
      onSuccess: () => {
        bancoModalOpen.value = false;
      }
    });
  } else {
    bancoForm.post('/bancos', {
      onSuccess: () => {
        bancoModalOpen.value = false;
      }
    });
  }
};

const deleteBanco = (id) => {
  Swal.fire({
    title: '¿Estás seguro?',
    text: "Esta acción eliminará el banco seleccionado.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      router.delete(`/bancos/${id}`);
    }
  });
};

const openCreatePlanModal = () => {
  isEditingPlan.value = false;
  planForm.reset();
  planModalOpen.value = true;
};

const openEditPlanModal = (plan) => {
  isEditingPlan.value = true;
  planForm.id = plan.id;
  planForm.nombre = plan.nombre;
  planForm.descripcion = plan.descripcion;
  planForm.costo_base = plan.costo_base;
  planForm.costo_por_apartamento = plan.costo_por_apartamento;
  planForm.activo = plan.activo;
  planModalOpen.value = true;
};

const savePlan = () => {
  if (isEditingPlan.value) {
    planForm.put(`/planes/${planForm.id}`, {
      onSuccess: () => {
        planModalOpen.value = false;
      }
    });
  } else {
    planForm.post('/planes', {
      onSuccess: () => {
        planModalOpen.value = false;
      }
    });
  }
};

const deletePlan = (id) => {
  Swal.fire({
    title: '¿Estás seguro?',
    text: "Esta acción eliminará el plan de suscripción seleccionado.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      router.delete(`/planes/${id}`);
    }
  });
};
</script>
