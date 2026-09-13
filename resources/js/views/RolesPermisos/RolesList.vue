<template>
  <div>
    <!-- Top Bar -->
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold text-slate-900">Roles y Permisos del Sistema</h1>
        <p class="text-caption text-slate-500">Administra los roles de acceso y asigna permisos por módulos y menús</p>
      </div>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateDialog">
        Nuevo Rol
      </v-btn>
    </div>

    <!-- Roles Table with DataTable controls -->
    <v-card class="bg-white border border-slate-100">
      <DataTableHeader
        v-model:search="search"
        v-model:per-page="perPage"
        :per-page-options="perPageOptions"
        placeholder="Buscar por rol, slug o descripción..."
      />

      <v-table density="comfortable" hover>
        <thead>
          <tr class="bg-slate-50 text-slate-700">
            <SortHeader col-key="nombre" width="180px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
              Nombre del Rol
            </SortHeader>
            <SortHeader col-key="slug" width="140px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
              Slug
            </SortHeader>
            <SortHeader col-key="descripcion" width="220px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
              Descripción
            </SortHeader>
            <SortHeader col-key="permisos" width="160px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
              Permisos Asignados
            </SortHeader>
            <SortHeader col-key="status" width="110px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
              Estado
            </SortHeader>
            <th class="text-right" style="width: 100px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="rol in paginatedItems" :key="rol.id">
            <td class="font-weight-bold text-slate-800">{{ rol.nombre }}</td>
            <td><code>{{ rol.slug }}</code></td>
            <td class="text-caption text-slate-600">{{ rol.descripcion || '-' }}</td>
            <td>
              <v-chip size="small" color="primary" variant="tonal">
                {{ rol.permissions?.length || 0 }} permisos
              </v-chip>
            </td>
            <td>
              <v-chip :color="rol.status ? 'success' : 'error'" size="x-small" variant="flat">
                {{ rol.status ? 'Activo' : 'Inactivo' }}
              </v-chip>
            </td>
            <td class="text-right">
              <v-btn icon="mdi-shield-edit" size="small" variant="text" color="primary" @click="editRole(rol)" />
              <v-btn icon="mdi-delete" size="small" variant="text" color="error" @click="deleteRole(rol.id)" />
            </td>
          </tr>
          <tr v-if="!paginatedItems.length">
            <td colspan="6" class="text-center py-6 text-slate-400">
              {{ search ? 'No se encontraron roles que coincidan con la búsqueda.' : 'No hay roles registrados.' }}
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

    <!-- Role Create / Edit Dialog with Module Checkboxes Matrix -->
    <v-dialog v-model="dialog" max-width="800" scrollable>
      <v-card class="pa-4">
        <v-card-title class="font-weight-bold">
          {{ isEditing ? 'Editar Rol y Permisos' : 'Crear Nuevo Rol' }}
        </v-card-title>
        <v-card-text>
          <v-row>
            <v-col cols="12" sm="6">
              <v-text-field v-model="form.nombre" label="Nombre del Rol" required />
            </v-col>
            <v-col cols="12" sm="6">
              <v-text-field v-model="form.descripcion" label="Descripción" />
            </v-col>
          </v-row>

          <v-divider class="my-4" />

          <!-- Permissions Grouped by Module Checkboxes -->
          <div class="text-subtitle-2 font-weight-bold text-slate-900 mb-3">
            Matriz de Permisos por Módulo:
          </div>

          <div v-for="(perms, modulo) in permissionsGrouped" :key="modulo" class="mb-4 pa-3 bg-slate-50 rounded-lg">
            <div class="font-weight-bold text-caption text-uppercase text-primary mb-2">
              Módulo: {{ modulo }}
            </div>
            <v-row dense>
              <v-col v-for="perm in perms" :key="perm.id" cols="12" sm="6" md="4">
                <v-checkbox
                  v-model="form.permissions"
                  :value="perm.id"
                  :label="perm.nombre"
                  density="compact"
                  hide-details
                  color="primary"
                />
              </v-col>
            </v-row>
          </div>
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn variant="text" @click="dialog = false">Cancelar</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveRole">Guardar Rol</v-btn>
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
const rolesList = ref([]);
const permissionsGrouped = ref({});
const dialog = ref(false);
const isEditing = ref(false);
const saving = ref(false);

const customGettersRoles = {
    permisos: (r) => r.permissions?.length || 0,
    status: (r) => (r.status ? 'Activo' : 'Inactivo'),
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
} = usePagination(rolesList, {
    perPage: 10,
    initialSortBy: 'nombre',
    customGetters: customGettersRoles,
});

const form = ref({
    id: null,
    nombre: '',
    descripcion: '',
    status: true,
    permissions: [],
    menus: [],
});

const fetchData = async () => {
    try {
        const [rolesRes, permsRes] = await Promise.all([
            axios.get('/roles'),
            axios.get('/permissions'),
        ]);
        if (rolesRes.data.success) {
            rolesList.value = rolesRes.data.data.data || rolesRes.data.data;
        }
        if (permsRes.data.success) {
            permissionsGrouped.value = permsRes.data.data;
        }
    } catch (e) {
        authStore.notify('Error al cargar roles y permisos', 'error');
    }
};

const openCreateDialog = () => {
    isEditing.value = false;
    form.value = { id: null, nombre: '', descripcion: '', status: true, permissions: [], menus: [] };
    dialog.value = true;
};

const editRole = (rol) => {
    isEditing.value = true;
    form.value = {
        id: rol.id,
        nombre: rol.nombre,
        descripcion: rol.descripcion,
        status: rol.status,
        permissions: rol.permissions?.map(p => p.id) || [],
        menus: rol.menus?.map(m => m.id) || [],
    };
    dialog.value = true;
};

const saveRole = async () => {
    saving.value = true;
    try {
        if (isEditing.value) {
            await axios.put(`/roles/${form.value.id}`, form.value);
            authStore.notify('Rol actualizado exitosamente');
        } else {
            await axios.post('/roles', form.value);
            authStore.notify('Rol creado exitosamente');
        }
        dialog.value = false;
        fetchData();
    } catch (e) {
        authStore.notify('Error al guardar rol', 'error');
    } finally {
        saving.value = false;
    }
};

const deleteRole = async (id) => {
    if (!confirm('¿Está seguro de eliminar lógicamente este rol?')) return;
    try {
        await axios.delete(`/roles/${id}`);
        authStore.notify('Rol eliminado');
        fetchData();
    } catch (e) {
        authStore.notify('Error al eliminar', 'error');
    }
};

onMounted(fetchData);
</script>
