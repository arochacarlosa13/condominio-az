<template>
  <div>
    <!-- Top Header -->
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold text-slate-900">Módulo de Notificaciones</h1>
        <p class="text-caption text-slate-500">Historial de despachos automáticos vía WhatsApp y Email con plantillas predefinidas</p>
      </div>
      <div class="d-flex align-center">
        <v-btn
          color="primary"
          variant="flat"
          class="font-weight-bold mr-3"
          prepend-icon="mdi-clock-alert-outline"
          :loading="sendingPreventiva"
          @click="ejecutarCobranzaPreventiva"
        >
          Cobranza Preventiva (3 Días / Hoy)
        </v-btn>
        <v-btn color="success" prepend-icon="mdi-whatsapp" :loading="sending" @click="enviarCobrosMasivos">
          Recordatorio Manual
        </v-btn>
      </div>
    </div>

    <!-- Notification Templates Showcase Cards -->
    <v-row class="mb-6">
      <v-col cols="12" md="3">
        <v-card class="pa-4 bg-white border border-slate-100" height="100%">
          <div class="text-caption font-weight-bold text-primary mb-1">RECORDATORIO DE PAGO</div>
          <div class="text-caption text-slate-600">
            "Estimado(a) residente, le recordamos que su cuota del mes se encuentra próxima a vencer..."
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" md="3">
        <v-card class="pa-4 bg-white border border-slate-100" height="100%">
          <div class="text-caption font-weight-bold text-success mb-1">PAGO CONCILIADO</div>
          <div class="text-caption text-slate-600">
            "Su pago ha sido aprobado exitosamente. Puede descargar su recibo oficial en el panel..."
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" md="3">
        <v-card class="pa-4 bg-white border border-slate-100" height="100%">
          <div class="text-caption font-weight-bold text-warning mb-1">RESERVA APROBADA</div>
          <div class="text-caption text-slate-600">
            "Su solicitud para el uso de las áreas comunes ha sido aprobada por la administración..."
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" md="3">
        <v-card class="pa-4 bg-white border border-slate-100" height="100%">
          <div class="text-caption font-weight-bold text-info mb-1">BIENVENIDA AL CONDOMINIO</div>
          <div class="text-caption text-slate-600">
            "Bienvenido al sistema. Sus credenciales de acceso son su correo y clave inicial 123456..."
          </div>
        </v-card>
      </v-col>
    </v-row>

    <!-- Dispatched History Table with DataTable controls -->
    <v-card class="bg-white border border-slate-100">
      <div class="pa-4 font-weight-bold text-subtitle-1 text-slate-900 border-b border-slate-100">
        Bitácora de Envíos Realizados
      </div>

      <DataTableHeader
        v-model:search="search"
        v-model:per-page="perPage"
        :per-page-options="perPageOptions"
        placeholder="Buscar por destinatario, plantilla o mensaje..."
      />

      <v-table density="comfortable" hover>
        <thead>
          <tr class="bg-slate-50 text-slate-700">
            <th>Fecha</th>
            <th>Canal</th>
            <th>Plantilla</th>
            <th>Destinatario</th>
            <th>Mensaje</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in paginatedItems" :key="item.id">
            <td>{{ formatDateTime(item.created_at) }}</td>
            <td>
              <v-chip :color="item.tipo === 'whatsapp' ? 'success' : 'info'" size="small" variant="flat" class="text-capitalize">
                <v-icon :icon="item.tipo === 'whatsapp' ? 'mdi-whatsapp' : 'mdi-email'" start size="14" />
                {{ item.tipo }}
              </v-chip>
            </td>
            <td class="font-weight-bold text-slate-800 text-capitalize">{{ item.plantilla }}</td>
            <td><code>{{ item.destinatario }}</code></td>
            <td class="text-caption text-slate-600" style="max-width: 300px;">{{ item.mensaje }}</td>
            <td>
              <v-chip :color="item.estado === 'enviado' ? 'success' : 'error'" size="x-small" variant="flat">
                {{ item.estado }}
              </v-chip>
            </td>
          </tr>
          <tr v-if="!paginatedItems.length">
            <td colspan="6" class="text-center py-6 text-slate-400">
              {{ search ? 'No se encontraron notificaciones que coincidan con la búsqueda.' : 'No hay historial de notificaciones.' }}
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
const historyList = ref([]);
const sending = ref(false);
const sendingPreventiva = ref(false);

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
} = usePagination(historyList, { perPage: 10 });

const formatDateTime = (dt) => {
    if (!dt) return '-';
    return new Date(dt).toLocaleString('es-VE', { dateStyle: 'short', timeStyle: 'short' });
};

const fetchHistory = async () => {
    try {
        const { data } = await axios.get('/notificaciones/historial');
        if (data.success) {
            historyList.value = data.data.data || data.data;
        }
    } catch (e) {
        authStore.notify('Error al cargar historial de notificaciones', 'error');
    }
};

const enviarCobrosMasivos = async () => {
    sending.value = true;
    try {
        const { data } = await axios.post('/notificaciones/recordatorios-cobro');
        authStore.notify(data.message || 'Recordatorios enviados exitosamente');
        fetchHistory();
    } catch (e) {
        authStore.notify('Error al enviar notificaciones masivas', 'error');
    } finally {
        sending.value = false;
    }
};

const ejecutarCobranzaPreventiva = async () => {
    sendingPreventiva.value = true;
    try {
        const { data } = await axios.post('/notificaciones/cobranza-preventiva');
        authStore.notify(data.message || 'Ciclo de cobranza preventiva completado con éxito');
        fetchHistory();
    } catch (e) {
        authStore.notify('Error al ejecutar ciclo de cobranza preventiva', 'error');
    } finally {
        sendingPreventiva.value = false;
    }
};

onMounted(fetchHistory);
</script>
