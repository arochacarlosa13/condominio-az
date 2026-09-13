<template>
  <div>
    <!-- Top Header -->
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold text-slate-900">Control de Acceso y Visitantes</h1>
        <p class="text-caption text-slate-500">Bitácora de entradas, salidas y registro vehicular de vigilancia</p>
      </div>
      <div class="d-flex gap-2">
        <v-btn color="primary" prepend-icon="mdi-account-plus" @click="openDialog">
          Registrar Entrada
        </v-btn>
        <v-btn color="secondary" prepend-icon="mdi-printer" variant="tonal" href="/api/v1/reportes/visitantes" target="_blank">
          Reporte PDF
        </v-btn>
      </div>
    </div>

    <!-- Table List with DataTable controls -->
    <v-card class="bg-white border border-slate-100">
      <DataTableHeader
        v-model:search="search"
        v-model:per-page="perPage"
        :per-page-options="perPageOptions"
        placeholder="Buscar por nombre, cédula, placa o apto..."
      />

      <v-table density="comfortable" hover>
        <thead>
          <tr class="bg-slate-50 text-slate-700">
            <SortHeader col-key="hora_entrada" width="130px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
              Fecha / Entrada
            </SortHeader>
            <SortHeader col-key="nombre_completo" width="180px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
              Visitante y Cédula
            </SortHeader>
            <SortHeader col-key="apartamento" width="130px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
              Unidad Destino
            </SortHeader>
            <SortHeader col-key="placa_vehiculo" width="120px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
              Placa Vehicular
            </SortHeader>
            <SortHeader col-key="motivo" width="160px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
              Motivo
            </SortHeader>
            <SortHeader col-key="hora_salida" width="130px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
              Estado / Salida
            </SortHeader>
            <th class="text-right" style="width: 110px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="vis in paginatedItems" :key="vis.id">
            <td>
              <div class="font-weight-bold text-slate-900">{{ formatDateTime(vis.hora_entrada) }}</div>
            </td>
            <td>
              <div class="font-weight-bold">{{ vis.nombre_completo }}</div>
              <div class="text-caption text-slate-500">{{ vis.cedula }}</div>
            </td>
            <td>
              <v-chip size="small" color="primary" variant="tonal">
                Apto. {{ vis.apartamento?.numero || '-' }}
              </v-chip>
            </td>
            <td><code>{{ vis.placa_vehiculo || 'Peatonal' }}</code></td>
            <td class="text-caption text-slate-600">{{ vis.motivo || '-' }}</td>
            <td>
              <v-chip :color="vis.hora_salida ? 'secondary' : 'success'" size="x-small" variant="flat">
                {{ vis.hora_salida ? formatDateTime(vis.hora_salida) : 'En Instalaciones' }}
              </v-chip>
            </td>
            <td class="text-right">
              <v-btn
                v-if="!vis.hora_salida"
                color="warning"
                size="small"
                variant="tonal"
                prepend-icon="mdi-logout"
                @click="registrarSalida(vis.id)"
              >
                Marcar Salida
              </v-btn>
            </td>
          </tr>
          <tr v-if="!paginatedItems.length">
            <td colspan="7" class="text-center py-6 text-slate-400">
              {{ search ? 'No se encontraron visitantes que coincidan con la búsqueda.' : 'No hay registros de visitantes actualmente.' }}
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

    <!-- Dialog for New Visitor Check-In -->
    <v-dialog v-model="dialog" max-width="500">
      <v-card class="pa-4">
        <v-card-title class="font-weight-bold">Registrar Entrada de Visitante</v-card-title>
        <v-card-text>
          <v-form @submit.prevent="saveVisitor">
            <v-text-field v-model="form.nombre_completo" label="Nombre Completo" required class="mb-3" />
            <v-text-field v-model="form.cedula" label="Cédula de Identidad" required class="mb-3" />
            <v-select
              v-model="form.apartamento_id"
              label="Apartamento / Unidad a Visitar"
              :items="apartamentosSelect"
              item-title="label"
              item-value="id"
              required
              class="mb-3"
            />
            <v-text-field v-model="form.placa_vehiculo" label="Placa del Vehículo (Opcional)" class="mb-3" />
            <v-textarea v-model="form.motivo" label="Motivo de la Visita" rows="2" />
          </v-form>
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn variant="text" @click="dialog = false">Cancelar</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveVisitor">Registrar Entrada</v-btn>
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
const visitantesList = ref([]);
const apartamentosSelect = ref([]);
const dialog = ref(false);
const saving = ref(false);

const customGettersVis = {
    apartamento: (v) => v.apartamento?.numero || '',
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
} = usePagination(visitantesList, {
    perPage: 10,
    initialSortBy: 'hora_entrada',
    initialSortDesc: true,
    customGetters: customGettersVis,
});

const form = ref({
    nombre_completo: '',
    cedula: '',
    apartamento_id: null,
    placa_vehiculo: '',
    motivo: '',
});

const formatDateTime = (dt) => {
    if (!dt) return '-';
    return new Date(dt).toLocaleString('es-VE', { dateStyle: 'short', timeStyle: 'short' });
};

const fetchVisitantes = async () => {
    try {
        const { data } = await axios.get('/visitantes');
        if (data.success) visitantesList.value = data.data.data || data.data;
    } catch (e) {
        authStore.notify('Error al cargar visitantes', 'error');
    }
};

const fetchApartamentos = async () => {
    try {
        const { data } = await axios.get('/apartamentos');
        if (data.success) {
            const list = data.data.data || data.data;
            apartamentosSelect.value = list.map(a => ({ id: a.id, label: `Apto. ${a.numero} (Piso ${a.piso})` }));
            if (apartamentosSelect.value.length) form.value.apartamento_id = apartamentosSelect.value[0].id;
        }
    } catch (e) {}
};

const openDialog = () => {
    form.value = {
        nombre_completo: '',
        cedula: '',
        apartamento_id: apartamentosSelect.value[0]?.id || null,
        placa_vehiculo: '',
        motivo: '',
    };
    dialog.value = true;
};

const saveVisitor = async () => {
    saving.value = true;
    try {
        await axios.post('/visitantes', form.value);
        authStore.notify('Entrada de visitante registrada');
        dialog.value = false;
        fetchVisitantes();
    } catch (e) {
        authStore.notify('Error al registrar visitante', 'error');
    } finally {
        saving.value = false;
    }
};

const registrarSalida = async (id) => {
    try {
        await axios.put(`/visitantes/${id}/salida`);
        authStore.notify('Salida registrada');
        fetchVisitantes();
    } catch (e) {
        authStore.notify('Error al marcar salida', 'error');
    }
};

onMounted(() => {
    fetchVisitantes();
    fetchApartamentos();
});
</script>
