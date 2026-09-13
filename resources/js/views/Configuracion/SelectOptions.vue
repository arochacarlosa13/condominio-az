<template>
  <div>
    <!-- Header -->
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold text-slate-900">Campos Select Dinámicos</h1>
        <p class="text-caption text-slate-500">Administra los catálogos desplegables (Bancos, Estados, Áreas Comunes, etc.)</p>
      </div>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateDialog">
        Nueva Opción
      </v-btn>
    </div>

    <!-- Options Table with DataTable controls -->
    <v-card class="bg-white border border-slate-100">
      <DataTableHeader
        v-model:search="search"
        v-model:per-page="perPage"
        :per-page-options="perPageOptions"
        placeholder="Buscar por tipo, valor o etiqueta..."
      >
        <template #actions>
          <v-select
            v-model="tipoFiltro"
            :items="[
              { title: 'Todas las categorías', value: '' },
              { title: 'Bancos (bancos)', value: 'bancos' },
              { title: 'Status (status_usuario)', value: 'status_usuario' },
              { title: 'Áreas (tipos_area_comun)', value: 'tipos_area_comun' },
              { title: 'Pagos (metodos_pago)', value: 'metodos_pago' },
              { title: 'Gastos (categorias_gasto)', value: 'categorias_gasto' },
            ]"
            item-title="title"
            item-value="value"
            density="compact"
            variant="outlined"
            hide-details
            style="min-width: 170px;"
            class="bg-white rounded"
            @update:model-value="fetchOptions"
          />
        </template>
      </DataTableHeader>

      <v-table density="comfortable" hover>
        <thead>
          <tr class="bg-slate-50 text-slate-700">
            <th>Tipo / Catálogo</th>
            <th>Valor (Clave)</th>
            <th>Etiqueta Visible</th>
            <th>Estado</th>
            <th class="text-right">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in paginatedItems" :key="item.id">
            <td>
              <v-chip size="small" color="secondary" variant="tonal" class="font-weight-medium">
                {{ item.tipo }}
              </v-chip>
            </td>
            <td><code>{{ item.valor }}</code></td>
            <td class="font-weight-bold text-slate-800">{{ item.etiqueta }}</td>
            <td>
              <v-chip :color="item.activo ? 'success' : 'error'" size="x-small" variant="flat">
                {{ item.activo ? 'Activo' : 'Inactivo' }}
              </v-chip>
            </td>
            <td class="text-right">
              <v-btn icon="mdi-pencil" size="small" variant="text" color="primary" @click="editOption(item)" />
              <v-btn icon="mdi-delete" size="small" variant="text" color="error" @click="deleteOption(item.id)" />
            </td>
          </tr>
          <tr v-if="!paginatedItems.length">
            <td colspan="5" class="text-center py-6 text-slate-400">
              {{ search ? 'No se encontraron opciones que coincidan con la búsqueda.' : 'No se encontraron opciones registradas.' }}
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

    <!-- Dialog for Create / Edit -->
    <v-dialog v-model="dialog" max-width="500">
      <v-card class="pa-4">
        <v-card-title class="font-weight-bold">
          {{ isEditing ? 'Editar Opción' : 'Nueva Opción de Lista' }}
        </v-card-title>
        <v-card-text>
          <v-form @submit.prevent="saveOption">
            <v-select
              v-model="form.tipo"
              label="Tipo de Campo"
              :items="['bancos', 'status_usuario', 'tipos_area_comun', 'metodos_pago', 'categorias_gasto', 'otro']"
              required
              class="mb-3"
            />
            <v-text-field
              v-model="form.valor"
              label="Valor Interno (ej. banesco)"
              required
              class="mb-3"
            />
            <v-text-field
              v-model="form.etiqueta"
              label="Etiqueta Visible (ej. Banesco Banco Universal)"
              required
              class="mb-3"
            />
            <v-switch
              v-model="form.activo"
              label="Opción Activa"
              color="primary"
            />
          </v-form>
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn variant="text" @click="dialog = false">Cancelar</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveOption">Guardar</v-btn>
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
const optionsList = ref([]);
const tipoFiltro = ref('');
const dialog = ref(false);
const isEditing = ref(false);
const saving = ref(false);

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
} = usePagination(optionsList, { perPage: 10 });

const form = ref({
    id: null,
    tipo: 'bancos',
    valor: '',
    etiqueta: '',
    activo: true,
});

const fetchOptions = async () => {
    try {
        const params = tipoFiltro.value ? { tipo: tipoFiltro.value } : {};
        const { data } = await axios.get('/select-options', { params });
        if (data.success) {
            optionsList.value = data.data;
        }
    } catch (e) {
        authStore.notify('Error al cargar opciones', 'error');
    }
};

const openCreateDialog = () => {
    isEditing.value = false;
    form.value = { id: null, tipo: tipoFiltro.value || 'bancos', valor: '', etiqueta: '', activo: true };
    dialog.value = true;
};

const editOption = (item) => {
    isEditing.value = true;
    form.value = { ...item };
    dialog.value = true;
};

const saveOption = async () => {
    saving.value = true;
    try {
        if (isEditing.value) {
            await axios.put(`/select-options/${form.value.id}`, form.value);
            authStore.notify('Opción actualizada');
        } else {
            await axios.post('/select-options', form.value);
            authStore.notify('Opción agregada');
        }
        dialog.value = false;
        fetchOptions();
    } catch (e) {
        authStore.notify('Error al guardar opción', 'error');
    } finally {
        saving.value = false;
    }
};

const deleteOption = async (id) => {
    if (!confirm('¿Está seguro de eliminar lógicamente esta opción?')) return;
    try {
        await axios.delete(`/select-options/${id}`);
        authStore.notify('Opción eliminada');
        fetchOptions();
    } catch (e) {
        authStore.notify('Error al eliminar', 'error');
    }
};

onMounted(fetchOptions);
</script>
