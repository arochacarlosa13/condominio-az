<template>
  <div>
    <!-- Top Header -->
    <div class="d-flex flex-column flex-sm-row justify-space-between align-start align-sm-center gap-3 mb-4 mb-sm-6">
      <div>
        <h1 class="text-h5 font-weight-bold text-slate-900">Panel del Residente</h1>
        <p class="text-caption text-slate-500">
          Bienvenido(a), {{ authStore.user?.name }} • Unidad: Apto. {{ unidad?.numero || 'N/A' }}
        </p>
      </div>
      <v-btn color="success" prepend-icon="mdi-cash-plus" to="/pagos" class="font-weight-bold w-100 w-sm-auto">
        Notificar Pago Realizado
      </v-btn>
    </div>

    <!-- Summary Row -->
    <v-row class="mb-4 mb-sm-6">
      <!-- Unit Details Card -->
      <v-col cols="12" sm="6" md="4">
        <v-card class="pa-4 pa-sm-5 bg-white border border-slate-100" height="100%">
          <div class="d-flex align-center mb-3">
            <v-avatar color="primary" variant="tonal" class="mr-3">
              <v-icon icon="mdi-home-outline" />
            </v-avatar>
            <div>
              <div class="text-caption text-slate-500 font-weight-bold">DETALLES DE MI UNIDAD</div>
              <div class="text-subtitle-1 font-weight-bold text-slate-900">Apto. {{ unidad?.numero }}</div>
            </div>
          </div>
          <v-divider class="my-3" />
          <div class="text-caption text-slate-600 d-flex flex-column gap-2">
            <div><strong>Piso:</strong> {{ unidad?.piso }}</div>
            <div><strong>Área:</strong> {{ unidad?.metros_cuadrados }} m²</div>
            <div><strong>Habitaciones:</strong> {{ unidad?.habitaciones }} • <strong>Baños:</strong> {{ unidad?.banos }}</div>
            <div><strong>Estado:</strong> {{ unidad?.ocupado ? 'Ocupado / Habitado' : 'Desocupado' }}</div>
          </div>
        </v-card>
      </v-col>

      <!-- Account Balance Card -->
      <v-col cols="12" sm="6" md="4">
        <v-card class="pa-4 pa-sm-5 bg-white border border-slate-100" height="100%">
          <div class="d-flex align-center mb-3">
            <v-avatar color="error" variant="tonal" class="mr-3">
              <v-icon icon="mdi-credit-card-clock-outline" />
            </v-avatar>
            <div>
              <div class="text-caption text-slate-500 font-weight-bold">ESTADO DE CUENTA</div>
              <div class="text-subtitle-1 font-weight-bold text-slate-900">Deuda Pendiente</div>
            </div>
          </div>
          <v-divider class="my-3" />
          <div class="text-h5 font-weight-bold" :class="deudaActualBs > 0 ? 'text-error' : 'text-success'">
            {{ authStore.formatMoney(deudaActualBs) }}
          </div>
          <div class="text-caption text-slate-500 mt-1">
            Equivalente: ${{ Number(deudaActualUsd).toFixed(2) }} USD
          </div>
        </v-card>
      </v-col>

      <!-- Upcoming Reservations / Quick Action -->
      <v-col cols="12" sm="12" md="4">
        <v-card class="pa-4 pa-sm-5 bg-white border border-slate-100" height="100%">
          <div class="d-flex align-center mb-3">
            <v-avatar color="warning" variant="tonal" class="mr-3">
              <v-icon icon="mdi-calendar-star" />
            </v-avatar>
            <div>
              <div class="text-caption text-slate-500 font-weight-bold">RESERVAS ACTIVAS</div>
              <div class="text-subtitle-1 font-weight-bold text-slate-900">{{ proximasReservas.length }} Solicitudes</div>
            </div>
          </div>
          <v-divider class="my-3" />
          <div v-if="proximasReservas.length" class="text-caption text-slate-600">
            <div v-for="res in proximasReservas" :key="res.id" class="mb-1">
              • <strong>{{ res.common_area?.nombre }}:</strong> {{ res.fecha_reserva }} ({{ res.estado }})
            </div>
          </div>
          <div v-else class="text-caption text-slate-400">
            No tienes áreas comunes reservadas para este mes.
          </div>
        </v-card>
      </v-col>
    </v-row>

    <!-- Recent Payments History Table with DataTable controls -->
    <v-card class="bg-white border border-slate-100">
      <div class="pa-4 d-flex justify-space-between align-center border-b border-slate-100 flex-wrap gap-2">
        <div class="text-subtitle-1 font-weight-bold text-slate-900">Historial de Pagos Reportados</div>
        <v-btn to="/pagos" color="primary" variant="text" size="small">Ver Todos los Pagos →</v-btn>
      </div>

      <DataTableHeader
        v-model:search="search"
        v-model:per-page="perPage"
        :per-page-options="perPageOptions"
        placeholder="Buscar pago por referencia o método..."
      />

      <div class="table-responsive-container">
        <v-table density="comfortable" hover style="min-width: 580px;">
          <thead>
            <tr class="bg-slate-50 text-slate-700">
              <th>Fecha</th>
              <th>Método de Pago</th>
              <th>Referencia</th>
              <th>Monto</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="pago in paginatedItems" :key="pago.id">
              <td>{{ pago.fecha_pago }}</td>
              <td class="text-capitalize">{{ pago.metodo_pago }}</td>
              <td><code>{{ pago.referencia || '-' }}</code></td>
              <td class="font-weight-bold">{{ authStore.formatMoney(pago.monto) }}</td>
              <td>
                <v-chip
                  :color="pago.estado === 'aprobado' ? 'success' : (pago.estado === 'pendiente' ? 'warning' : 'error')"
                  size="small"
                  variant="flat"
                  class="text-capitalize"
                >
                  {{ pago.estado }}
                </v-chip>
              </td>
            </tr>
            <tr v-if="!paginatedItems.length">
              <td colspan="5" class="text-center text-slate-400 py-4">
                {{ search ? 'No se encontraron pagos que coincidan con la búsqueda.' : 'No hay pagos registrados recientemente.' }}
              </td>
            </tr>
          </tbody>
        </v-table>
      </div>

      <DataTableFooter
        v-model:current-page="currentPage"
        :total-pages="totalPages"
        :total-items="totalItems"
        :original-total="originalTotal"
        :start-index="startIndex"
        :end-index="endIndex"
        :search="search"
      />
    </v-card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../../store/auth';
import { usePagination } from '../../composables/usePagination';
import DataTableHeader from '../../components/DataTableHeader.vue';
import DataTableFooter from '../../components/DataTableFooter.vue';

const authStore = useAuthStore();
const unidad = ref(null);
const deudaActualBs = ref(0);
const deudaActualUsd = ref(0);
const ultimosPagos = ref([]);
const proximasReservas = ref([]);

const {
    search,
    currentPage,
    perPage,
    perPageOptions,
    paginatedItems,
    originalTotal,
    totalItems,
    totalPages,
    startIndex,
    endIndex,
} = usePagination(ultimosPagos, { perPage: 5 });

const fetchData = async () => {
    try {
        const { data } = await axios.get('/dashboard/propietario');
        if (data.success) {
            unidad.value = data.data.unidad;
            deudaActualBs.value = data.data.deuda_actual_bs;
            deudaActualUsd.value = data.data.deuda_actual_usd;
            ultimosPagos.value = data.data.ultimos_pagos || [];
            proximasReservas.value = data.data.proximas_reservas || [];
        }
    } catch (e) {
        authStore.notify('Error al cargar datos del Propietario', 'error');
    }
};

onMounted(fetchData);
</script>
