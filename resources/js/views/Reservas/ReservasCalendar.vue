<template>
  <div>
    <!-- Top Bar -->
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold text-slate-900">Reserva de Áreas Comunes</h1>
        <p class="text-caption text-slate-500">Piscina, Salón de Fiestas, Quincho y espacios recreativos del condominio</p>
      </div>
      <div class="d-flex gap-2">
        <v-btn color="primary" prepend-icon="mdi-calendar-plus" @click="openReservaDialog">
          Solicitar Reserva
        </v-btn>
        <v-btn v-if="!authStore.isPropietario" color="secondary" variant="tonal" prepend-icon="mdi-plus" @click="openAreaDialog">
          Nueva Área Común
        </v-btn>
      </div>
    </div>

    <!-- Common Areas Cards Grid -->
    <v-row class="mb-6">
      <v-col v-for="area in areasList" :key="area.id" cols="12" sm="6" md="4">
        <v-card class="pa-4 bg-white border border-slate-100" height="100%">
          <div class="d-flex justify-space-between align-center mb-2">
            <span class="text-h6 font-weight-bold text-slate-900">{{ area.nombre }}</span>
            <v-chip :color="area.activo ? 'success' : 'error'" size="x-small" variant="flat">
              {{ area.activo ? 'Disponible' : 'Mantenimiento' }}
            </v-chip>
          </div>
          <p class="text-caption text-slate-500 mb-3">{{ area.descripcion || 'Espacio compartido del condominio.' }}</p>

          <div class="d-flex justify-space-between text-caption text-slate-700 bg-slate-50 pa-3 rounded-lg">
            <div>Capacidad: <strong>{{ area.capacidad_maxima }} personas</strong></div>
            <div>Costo: <strong>{{ authStore.formatMoney(area.costo_reserva) }}</strong></div>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <!-- Reservations Table List with DataTable controls -->
    <v-card class="bg-white border border-slate-100">
      <div class="pa-4 font-weight-bold text-subtitle-1 text-slate-900 border-b border-slate-100">
        Calendario y Solicitudes de Reserva
      </div>

      <DataTableHeader
        v-model:search="search"
        v-model:per-page="perPage"
        :per-page-options="perPageOptions"
        placeholder="Buscar por área, solicitante, motivo o fecha..."
      />

      <v-table density="comfortable" hover>
        <thead>
          <tr class="bg-slate-50 text-slate-700">
            <th>Fecha Reservada</th>
            <th>Área Común</th>
            <th>Horario</th>
            <th>Solicitante / Apto</th>
            <th>Motivo</th>
            <th>Estado</th>
            <th class="text-right">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="res in paginatedItems" :key="res.id">
            <td class="font-weight-bold text-slate-900">{{ res.fecha_reserva }}</td>
            <td><strong>{{ res.common_area?.nombre }}</strong></td>
            <td><code>{{ res.hora_inicio }} - {{ res.hora_fin }}</code></td>
            <td>
              <div class="font-weight-medium">Apto. {{ res.apartamento?.numero || '-' }}</div>
              <div class="text-caption text-slate-500">{{ res.user?.name }}</div>
            </td>
            <td class="text-caption text-slate-600">{{ res.motivo }}</td>
            <td>
              <v-chip
                :color="res.estado === 'aprobada' ? 'success' : (res.estado === 'pendiente' ? 'warning' : 'error')"
                size="x-small"
                variant="flat"
                class="text-capitalize"
              >
                {{ res.estado }}
              </v-chip>
            </td>
            <td class="text-right">
              <!-- Admin Approve / Reject actions -->
              <template v-if="!authStore.isPropietario && res.estado === 'pendiente'">
                <v-btn icon="mdi-check" size="small" variant="tonal" color="success" class="mr-1" @click="approveReserva(res.id)" />
                <v-btn icon="mdi-close" size="small" variant="tonal" color="error" class="mr-1" @click="rejectReserva(res.id)" />
              </template>

              <!-- Owner Cancel action -->
              <v-btn
                v-if="authStore.isPropietario && res.estado === 'pendiente'"
                size="small"
                color="error"
                variant="tonal"
                @click="cancelReserva(res.id)"
              >
                Cancelar
              </v-btn>
            </td>
          </tr>
          <tr v-if="!paginatedItems.length">
            <td colspan="7" class="text-center py-6 text-slate-400">
              {{ search ? 'No se encontraron reservas que coincidan con la búsqueda.' : 'No hay reservas programadas.' }}
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

    <!-- Dialog for New Reservation -->
    <v-dialog v-model="reservaDialog" max-width="500">
      <v-card class="pa-4">
        <v-card-title class="font-weight-bold">Solicitar Reserva de Área Común</v-card-title>
        <v-card-text>
          <v-form @submit.prevent="saveReserva">
            <v-select
              v-model="reservaForm.common_area_id"
              label="Área Común Deseada"
              :items="areasList"
              item-title="nombre"
              item-value="id"
              required
              class="mb-3"
            />
            <v-text-field v-model="reservaForm.fecha_reserva" label="Fecha de la Reserva" type="date" required class="mb-3" />
            <v-row>
              <v-col cols="6">
                <v-text-field v-model="reservaForm.hora_inicio" label="Hora Inicio (ej. 14:00)" required class="mb-3" />
              </v-col>
              <v-col cols="6">
                <v-text-field v-model="reservaForm.hora_fin" label="Hora Fin (ej. 18:00)" required class="mb-3" />
              </v-col>
            </v-row>
            <v-text-field v-model="reservaForm.cantidad_personas" label="Cantidad de Personas" type="number" class="mb-3" />
            <v-textarea v-model="reservaForm.motivo" label="Motivo del Evento o Uso" rows="2" required />
          </v-form>
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn variant="text" @click="reservaDialog = false">Cancelar</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveReserva">Enviar Solicitud</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dialog for New Common Area -->
    <v-dialog v-model="areaDialog" max-width="500">
      <v-card class="pa-4">
        <v-card-title class="font-weight-bold">Registrar Nueva Área Común</v-card-title>
        <v-card-text>
          <v-form @submit.prevent="saveArea">
            <v-text-field v-model="areaForm.nombre" label="Nombre del Área (ej. Quincho Parrillera)" required class="mb-3" />
            <v-textarea v-model="areaForm.descripcion" label="Descripción" rows="2" class="mb-3" />
            <v-text-field v-model="areaForm.capacidad_maxima" label="Capacidad Máxima de Personas" type="number" required class="mb-3" />
            <v-text-field v-model="areaForm.costo_reserva" label="Costo por Uso / Arancel (Bs.)" type="number" required class="mb-3" />
            <v-switch v-model="areaForm.activo" label="Disponible para Reservas" color="primary" />
          </v-form>
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn variant="text" @click="areaDialog = false">Cancelar</v-btn>
          <v-btn color="primary" :loading="savingArea" @click="saveArea">Guardar Área</v-btn>
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

const authStore = useAuthStore();
const areasList = ref([]);
const reservasList = ref([]);
const reservaDialog = ref(false);
const areaDialog = ref(false);
const saving = ref(false);
const savingArea = ref(false);

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
} = usePagination(reservasList, { perPage: 10 });

const reservaForm = ref({
    common_area_id: null,
    fecha_reserva: new Date().toISOString().substring(0, 10),
    hora_inicio: '14:00',
    hora_fin: '18:00',
    motivo: '',
    cantidad_personas: 10,
});

const areaForm = ref({
    nombre: '',
    descripcion: '',
    capacidad_maxima: 30,
    costo_reserva: 50000,
    requiere_reserva: true,
    activo: true,
});

const fetchData = async () => {
    try {
        const [areasRes, resRes] = await Promise.all([
            axios.get('/areas-comunes'),
            axios.get('/reservas'),
        ]);
        if (areasRes.data.success) {
            areasList.value = areasRes.data.data;
            if (areasList.value.length) reservaForm.value.common_area_id = areasList.value[0].id;
        }
        if (resRes.data.success) {
            reservasList.value = resRes.data.data.data || resRes.data.data;
        }
    } catch (e) {
        authStore.notify('Error al cargar áreas y reservas', 'error');
    }
};

const openReservaDialog = () => {
    reservaDialog.value = true;
};

const openAreaDialog = () => {
    areaDialog.value = true;
};

const saveReserva = async () => {
    saving.value = true;
    try {
        await axios.post('/reservas', reservaForm.value);
        authStore.notify('Solicitud de reserva enviada exitosamente');
        reservaDialog.value = false;
        fetchData();
    } catch (e) {
        authStore.notify(e.response?.data?.message || 'Error al solicitar reserva', 'error');
    } finally {
        saving.value = false;
    }
};

const saveArea = async () => {
    savingArea.value = true;
    try {
        await axios.post('/areas-comunes', areaForm.value);
        authStore.notify('Área común registrada exitosamente');
        areaDialog.value = false;
        fetchData();
    } catch (e) {
        authStore.notify('Error al registrar área', 'error');
    } finally {
        savingArea.value = false;
    }
};

const approveReserva = async (id) => {
    try {
        await axios.put(`/reservas/${id}/aprobar`);
        authStore.notify('Reserva aprobada');
        fetchData();
    } catch (e) {
        authStore.notify('Error al aprobar reserva', 'error');
    }
};

const rejectReserva = async (id) => {
    try {
        await axios.put(`/reservas/${id}/rechazar`);
        authStore.notify('Reserva rechazada');
        fetchData();
    } catch (e) {
        authStore.notify('Error al rechazar reserva', 'error');
    }
};

const cancelReserva = async (id) => {
    try {
        await axios.put(`/reservas/${id}/cancelar`);
        authStore.notify('Reserva cancelada');
        fetchData();
    } catch (e) {
        authStore.notify('Error al cancelar reserva', 'error');
    }
};

onMounted(fetchData);
</script>
