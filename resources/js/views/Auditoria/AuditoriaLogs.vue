<template>
  <div>
    <!-- Top Header -->
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold text-slate-900">Bitácora de Auditoría del Sistema</h1>
        <p class="text-caption text-slate-500">Trazabilidad completa de acciones, cambios de datos, inicios de sesión e IPs de acceso</p>
      </div>
      <v-btn color="primary" prepend-icon="mdi-refresh" variant="tonal" @click="fetchLogs">
        Actualizar Bitácora
      </v-btn>
    </div>

    <!-- Logs Table with DataTable controls -->
    <v-card class="bg-white border border-slate-100">
      <DataTableHeader
        v-model:search="search"
        v-model:per-page="perPage"
        :per-page-options="perPageOptions"
        placeholder="Buscar por usuario, acción, módulo o IP..."
      />

      <v-table density="comfortable" hover>
        <thead>
          <tr class="bg-slate-50 text-slate-700">
            <SortHeader col-key="created_at" width="140px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
              Fecha y Hora
            </SortHeader>
            <SortHeader col-key="user" width="180px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
              Usuario Responsable
            </SortHeader>
            <SortHeader col-key="accion" width="110px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
              Acción
            </SortHeader>
            <SortHeader col-key="modelo" width="160px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
              Módulo / Modelo
            </SortHeader>
            <SortHeader col-key="ip" width="130px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
              Dirección IP
            </SortHeader>
            <th class="text-right" style="width: 100px;">Detalles</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="log in paginatedItems" :key="log.id">
            <td class="text-caption font-weight-bold text-slate-900">
              {{ formatDateTime(log.created_at) }}
            </td>
            <td>
              <div class="font-weight-medium">{{ log.user?.name || 'Sistema / Invitado' }}</div>
              <div class="text-caption text-slate-500">{{ log.user?.email || 'N/A' }}</div>
            </td>
            <td>
              <v-chip
                :color="log.accion === 'eliminar' ? 'error' : (log.accion === 'crear' ? 'success' : 'primary')"
                size="x-small"
                variant="flat"
                class="text-capitalize"
              >
                {{ log.accion }}
              </v-chip>
            </td>
            <td class="font-weight-medium text-slate-800">{{ log.modelo }}</td>
            <td><code>{{ log.ip }}</code></td>
            <td class="text-right">
              <v-btn
                icon="mdi-code-json"
                size="small"
                variant="text"
                color="primary"
                title="Ver Datos Afectados"
                @click="showData(log)"
              />
            </td>
          </tr>
          <tr v-if="!paginatedItems.length">
            <td colspan="6" class="text-center py-6 text-slate-400">
              {{ search ? 'No se encontraron registros de auditoría que coincidan con la búsqueda.' : 'No hay registros de auditoría disponibles.' }}
            </td>
          </tr>
        </tbody>
      </v-table>

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

    <!-- Dialog for viewing JSON payloads -->
    <v-dialog v-model="dialog" max-width="600">
      <v-card class="pa-4">
        <v-card-title class="font-weight-bold">Detalle del Registro de Auditoría</v-card-title>
        <v-card-text>
          <div class="mb-3 text-caption text-slate-500">
            <strong>Navegador / User Agent:</strong> {{ selectedLog?.user_agent }}
          </div>
          <div class="font-weight-bold text-caption text-slate-800 mb-1">Carga de Datos (Payload):</div>
          <pre class="pa-3 bg-slate-900 text-slate-100 rounded-lg text-caption overflow-x-auto" style="max-height: 300px;">
{{ JSON.stringify(selectedLog?.datos_nuevos || selectedLog?.datos_anteriores || {}, null, 2) }}
          </pre>
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn color="primary" @click="dialog = false">Cerrar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../../store/auth';
import { usePagination } from '../../composables/usePagination';
import DataTableHeader from '../../components/DataTableHeader.vue';
import DataTableFooter from '../../components/DataTableFooter.vue';
import SortHeader from '../../components/SortHeader.vue';

const authStore = useAuthStore();
const logsList = ref([]);
const dialog = ref(false);
const selectedLog = ref(null);

const customGettersAudit = {
    user: (l) => l.user?.name || 'Sistema / Invitado',
};

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
    sortBy,
    sortDesc,
    sort,
} = usePagination(logsList, {
    perPage: 10,
    initialSortBy: 'created_at',
    initialSortDesc: true,
    customGetters: customGettersAudit,
});

const formatDateTime = (dt) => {
    if (!dt) return '-';
    return new Date(dt).toLocaleString('es-VE');
};

const fetchLogs = async () => {
    try {
        const { data } = await axios.get('/auditoria');
        if (data.success) logsList.value = data.data.data || data.data;
    } catch (e) {
        authStore.notify('Error al cargar bitácora de auditoría', 'error');
    }
};

const showData = (log) => {
    selectedLog.value = log;
    dialog.value = true;
};

onMounted(fetchLogs);
</script>
