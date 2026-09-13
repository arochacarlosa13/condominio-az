<template>
  <div>
    <!-- Top Bar -->
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold text-slate-900">Incidencias y Reportes de Daños</h1>
        <p class="text-caption text-slate-500">Gestión de filtraciones, problemas eléctricos, mantenimiento correctivo y asignación de técnicos</p>
      </div>
      <v-btn color="primary" prepend-icon="mdi-alert-plus" @click="openDialog">
        Reportar Incidencia
      </v-btn>
    </div>

    <!-- Issues Table with DataTable controls -->
    <v-card class="bg-white border border-slate-100">
      <DataTableHeader
        v-model:search="search"
        v-model:per-page="perPage"
        :per-page-options="perPageOptions"
        placeholder="Buscar incidencia, apartamento, responsable..."
      />

      <v-table density="comfortable" hover>
        <thead>
          <tr class="bg-slate-50 text-slate-700">
            <SortHeader col-key="created_at" width="110px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
              Fecha
            </SortHeader>
            <SortHeader col-key="titulo" width="220px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
              Título y Descripción
            </SortHeader>
            <SortHeader col-key="apartamento" width="130px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
              Unidad / Apto
            </SortHeader>
            <SortHeader col-key="prioridad" width="110px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
              Prioridad
            </SortHeader>
            <SortHeader col-key="responsable_nombre" width="160px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
              Responsable / Técnico
            </SortHeader>
            <SortHeader col-key="estado" width="110px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
              Estado
            </SortHeader>
            <th class="text-right" style="width: 100px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="inc in paginatedItems" :key="inc.id">
            <td>{{ formatDate(inc.created_at) }}</td>
            <td>
              <div class="font-weight-bold text-slate-900">{{ inc.titulo }}</div>
              <div class="text-caption text-slate-500">{{ inc.descripcion }}</div>
            </td>
            <td>
              <v-chip size="small" color="primary" variant="tonal">
                Apto. {{ inc.apartamento?.numero || '-' }}
              </v-chip>
            </td>
            <td>
              <v-chip
                :color="inc.prioridad === 'alta' ? 'error' : (inc.prioridad === 'media' ? 'warning' : 'info')"
                size="x-small"
                variant="flat"
                class="text-uppercase"
              >
                {{ inc.prioridad }}
              </v-chip>
            </td>
            <td class="text-caption text-slate-700 font-weight-medium">
              {{ inc.responsable_nombre || 'Sin asignar' }}
            </td>
            <td>
              <v-chip
                :color="inc.estado === 'resuelto' ? 'success' : (inc.estado === 'en_proceso' ? 'warning' : 'secondary')"
                size="x-small"
                variant="flat"
                class="text-capitalize"
              >
                {{ inc.estado }}
              </v-chip>
            </td>
            <td class="text-right">
              <v-btn
                v-if="!authStore.isPropietario"
                icon="mdi-account-wrench"
                size="small"
                variant="text"
                color="primary"
                title="Asignar Responsable y Estado"
                @click="editIncidencia(inc)"
              />
            </td>
          </tr>
          <tr v-if="!paginatedItems.length">
            <td colspan="7" class="text-center py-6 text-slate-400">
              {{ search ? 'No se encontraron incidencias que coincidan con la búsqueda.' : 'No hay incidencias reportadas actualmente.' }}
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

    <!-- Dialog for Reporting Issue -->
    <v-dialog v-model="dialog" max-width="500">
      <v-card class="pa-4">
        <v-card-title class="font-weight-bold">Reportar Daño o Incidencia</v-card-title>
        <v-card-text>
          <v-form @submit.prevent="saveIncidencia">
            <v-text-field v-model="form.titulo" label="Título (ej. Filtración en pared)" required class="mb-3" />
            <v-textarea v-model="form.descripcion" label="Descripción detallada del problema" rows="3" required class="mb-3" />
            <v-select
              v-model="form.prioridad"
              label="Nivel de Prioridad"
              :items="['baja', 'media', 'alta']"
              class="mb-3"
            />
          </v-form>
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn variant="text" @click="dialog = false">Cancelar</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveIncidencia">Enviar Reporte</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dialog for Admin Updating Status -->
    <v-dialog v-model="editDialog" max-width="500">
      <v-card class="pa-4">
        <v-card-title class="font-weight-bold">Gestionar Incidencia</v-card-title>
        <v-card-text>
          <v-form @submit.prevent="updateStatus">
            <v-text-field v-model="editForm.responsable_nombre" label="Técnico / Responsable Asignado" class="mb-3" />
            <v-text-field v-model="editForm.fecha_solucion" label="Fecha Estimada de Solución" type="date" class="mb-3" />
            <v-select
              v-model="editForm.estado"
              label="Estado de la Reparación"
              :items="['reportado', 'en_proceso', 'resuelto']"
              class="mb-3"
            />
          </v-form>
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn variant="text" @click="editDialog = false">Cancelar</v-btn>
          <v-btn color="primary" :loading="saving" @click="updateStatus">Actualizar Estado</v-btn>
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
const incidenciasList = ref([]);
const dialog = ref(false);
const editDialog = ref(false);
const saving = ref(false);

const customGettersInc = {
    apartamento: (i) => i.apartamento?.numero || '',
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
} = usePagination(incidenciasList, {
    perPage: 10,
    initialSortBy: 'created_at',
    initialSortDesc: true,
    customGetters: customGettersInc,
});

const form = ref({
    titulo: '',
    descripcion: '',
    prioridad: 'media',
});

const editForm = ref({
    id: null,
    responsable_nombre: '',
    fecha_solucion: '',
    estado: 'en_proceso',
});

const formatDate = (dt) => {
    if (!dt) return '-';
    return new Date(dt).toLocaleDateString('es-VE');
};

const fetchIncidencias = async () => {
    try {
        const { data } = await axios.get('/incidencias');
        if (data.success) incidenciasList.value = data.data.data || data.data;
    } catch (e) {
        authStore.notify('Error al cargar incidencias', 'error');
    }
};

const openDialog = () => {
    form.value = { titulo: '', descripcion: '', prioridad: 'media' };
    dialog.value = true;
};

const editIncidencia = (inc) => {
    editForm.value = {
        id: inc.id,
        responsable_nombre: inc.responsable_nombre || '',
        fecha_solucion: inc.fecha_solucion || '',
        estado: inc.estado,
    };
    editDialog.value = true;
};

const saveIncidencia = async () => {
    saving.value = true;
    try {
        await axios.post('/incidencias', form.value);
        authStore.notify('Incidencia reportada correctamente');
        dialog.value = false;
        fetchIncidencias();
    } catch (e) {
        authStore.notify('Error al reportar incidencia', 'error');
    } finally {
        saving.value = false;
    }
};

const updateStatus = async () => {
    saving.value = true;
    try {
        await axios.put(`/incidencias/${editForm.value.id}`, editForm.value);
        authStore.notify('Estado de la incidencia actualizado');
        editDialog.value = false;
        fetchIncidencias();
    } catch (e) {
        authStore.notify('Error al actualizar incidencia', 'error');
    } finally {
        saving.value = false;
    }
};

onMounted(fetchIncidencias);
</script>
