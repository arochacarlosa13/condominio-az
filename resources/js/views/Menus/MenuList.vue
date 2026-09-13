<template>
  <div>
    <!-- Header -->
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold text-slate-900">Menús Dinámicos</h1>
        <p class="text-caption text-slate-500">Crea y modifica los ítems de navegación lateral, rutas e iconos de Vuetify</p>
      </div>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateDialog">
        Nuevo Menú
      </v-btn>
    </div>

    <!-- Menus Table with DataTable controls -->
    <v-card class="bg-white border border-slate-100">
      <DataTableHeader
        v-model:search="search"
        v-model:per-page="perPage"
        :per-page-options="perPageOptions"
        placeholder="Buscar por nombre, ruta o permiso..."
      />

      <v-table density="comfortable" hover>
        <thead>
          <tr class="bg-slate-50 text-slate-700">
            <th>Icono</th>
            <th>Nombre del Menú</th>
            <th>Ruta URL</th>
            <th>Orden</th>
            <th>Permiso Vinculado</th>
            <th class="text-right">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="menu in paginatedItems" :key="menu.id">
            <td>
              <v-avatar color="primary" variant="tonal" size="32">
                <v-icon :icon="menu.icono || 'mdi-circle-small'" size="18" />
              </v-avatar>
            </td>
            <td class="font-weight-bold text-slate-800">{{ menu.nombre }}</td>
            <td><code>{{ menu.ruta || '-' }}</code></td>
            <td><v-chip size="x-small" color="secondary">{{ menu.orden }}</v-chip></td>
            <td>
              <v-chip v-if="menu.permission" size="small" color="primary" variant="outlined">
                {{ menu.permission.nombre }}
              </v-chip>
              <span v-else class="text-caption text-slate-400">Público / Todos</span>
            </td>
            <td class="text-right">
              <v-btn icon="mdi-pencil" size="small" variant="text" color="primary" @click="editMenu(menu)" />
              <v-btn icon="mdi-delete" size="small" variant="text" color="error" @click="deleteMenu(menu.id)" />
            </td>
          </tr>
          <tr v-if="!paginatedItems.length">
            <td colspan="6" class="text-center py-6 text-slate-400">
              {{ search ? 'No se encontraron menús que coincidan con la búsqueda.' : 'No hay menús registrados.' }}
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

    <!-- Dialog Create / Edit Menu -->
    <v-dialog v-model="dialog" max-width="500">
      <v-card class="pa-4">
        <v-card-title class="font-weight-bold">
          {{ isEditing ? 'Editar Menú' : 'Nuevo Menú de Navegación' }}
        </v-card-title>
        <v-card-text>
          <v-form @submit.prevent="saveMenu">
            <v-text-field v-model="form.nombre" label="Nombre visible" required class="mb-3" />
            <v-text-field v-model="form.icono" label="Icono MDI (ej. mdi-cash)" class="mb-3" />
            <v-text-field v-model="form.ruta" label="Ruta URL (ej. /pagos)" class="mb-3" />
            <v-text-field v-model="form.orden" label="Orden Numérico" type="number" class="mb-3" />
          </v-form>
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn variant="text" @click="dialog = false">Cancelar</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveMenu">Guardar Menú</v-btn>
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
const menusList = ref([]);
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
} = usePagination(menusList, { perPage: 10 });

const form = ref({
    id: null,
    nombre: '',
    icono: 'mdi-circle-small',
    ruta: '',
    orden: 0,
});

const fetchMenus = async () => {
    try {
        const { data } = await axios.get('/menus');
        if (data.success) {
            menusList.value = data.data;
        }
    } catch (e) {
        authStore.notify('Error al cargar menús', 'error');
    }
};

const openCreateDialog = () => {
    isEditing.value = false;
    form.value = { id: null, nombre: '', icono: 'mdi-circle-small', ruta: '', orden: 0 };
    dialog.value = true;
};

const editMenu = (menu) => {
    isEditing.value = true;
    form.value = { ...menu };
    dialog.value = true;
};

const saveMenu = async () => {
    saving.value = true;
    try {
        if (isEditing.value) {
            await axios.put(`/menus/${form.value.id}`, form.value);
            authStore.notify('Menú actualizado');
        } else {
            await axios.post('/menus', form.value);
            authStore.notify('Menú creado');
        }
        dialog.value = false;
        fetchMenus();
    } catch (e) {
        authStore.notify('Error al guardar menú', 'error');
    } finally {
        saving.value = false;
    }
};

const deleteMenu = async (id) => {
    if (!confirm('¿Está seguro de eliminar lógicamente este menú?')) return;
    try {
        await axios.delete(`/menus/${id}`);
        authStore.notify('Menú eliminado');
        fetchMenus();
    } catch (e) {
        authStore.notify('Error al eliminar', 'error');
    }
};

onMounted(fetchMenus);
</script>
