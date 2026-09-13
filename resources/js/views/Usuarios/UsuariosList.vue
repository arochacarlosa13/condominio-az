<template>
  <div>
    <!-- Top Header -->
    <div class="d-flex flex-column flex-sm-row justify-space-between align-start align-sm-center gap-3 mb-4 mb-sm-6">
      <div>
        <h1 class="text-h5 font-weight-bold text-slate-900">
          {{ authStore.isMaster ? 'Gestión Global de Usuarios' : 'Gestión de Propietarios y Residentes' }}
        </h1>
        <p class="text-caption text-slate-500">
          {{ authStore.isMaster 
              ? 'Administra todos los usuarios de la plataforma, roles, asignaciones de condominios y desbloqueo de accesos' 
              : `Administra los propietarios y cuentas de acceso de: ${condoDisplayText}` }}
        </p>
      </div>
      <div class="d-flex flex-wrap align-center gap-2 w-100 w-sm-auto">
        <v-btn
          color="success"
          variant="tonal"
          prepend-icon="mdi-file-excel"
          class="font-weight-medium"
          @click="exportarExcel"
        >
          Exportar Excel
        </v-btn>
        <v-btn
          color="error"
          variant="tonal"
          prepend-icon="mdi-file-pdf-box"
          class="font-weight-medium"
          @click="exportarPdf"
        >
          Exportar PDF
        </v-btn>
        <v-btn color="primary" prepend-icon="mdi-account-plus" class="font-weight-bold" @click="openCreateDialog">
          {{ authStore.isMaster ? 'Nuevo Usuario' : 'Nuevo Propietario' }}
        </v-btn>
      </div>
    </div>

    <!-- Users Table with DataTable controls -->
    <v-card class="bg-white border border-slate-100">
      <DataTableHeader
        v-model:search="search"
        v-model:per-page="perPage"
        :per-page-options="perPageOptions"
        placeholder="Buscar por nombre, cédula o correo..."
      >
        <template #actions>
          <v-select
            v-if="authStore.isMaster"
            v-model="rolFiltro"
            :items="[
              { title: 'Todos los roles', value: '' },
              { title: 'Super Admin', value: 'Super Admin' },
              { title: 'Admin Condominio', value: 'Admin de Condominio' },
              { title: 'Supervisor', value: 'Supervisor' },
              { title: 'Analista', value: 'Analista del Sistema' },
              { title: 'Propietario', value: 'Propietario/Residente' },
            ]"
            item-title="title"
            item-value="value"
            density="compact"
            variant="outlined"
            hide-details
            style="min-width: 140px;"
            class="bg-white rounded"
            @update:model-value="fetchUsers"
          />
          <v-select
            v-if="authStore.isMaster"
            v-model="condominioFiltro"
            :items="condosFilterOptions"
            item-title="title"
            item-value="value"
            density="compact"
            variant="outlined"
            hide-details
            style="min-width: 170px;"
            class="bg-white rounded"
            @update:model-value="fetchUsers"
          />
          <v-select
            v-model="apartamentoFiltro"
            :items="apartamentosFilterOptions"
            item-title="title"
            item-value="value"
            density="compact"
            variant="outlined"
            hide-details
            placeholder="Filtrar por apartamento"
            style="min-width: 190px; max-width: 230px;"
            class="bg-white rounded"
            prepend-inner-icon="mdi-home-outline"
            clearable
            @update:model-value="fetchUsers"
          />
          <v-btn
            variant="tonal"
            color="warning"
            size="small"
            prepend-icon="mdi-lock-alert"
            :class="{ 'font-weight-bold': soloBloqueados }"
            @click="toggleBloqueados"
          >
            {{ soloBloqueados ? 'Todos' : 'Bloqueados' }}
          </v-btn>
        </template>
      </DataTableHeader>

      <div class="table-responsive-container">
        <v-table density="comfortable" hover style="min-width: 760px;">
          <thead>
            <tr class="bg-slate-50 text-slate-700">
              <SortHeader col-key="name" width="180px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
                Nombre y Cédula
              </SortHeader>
              <SortHeader col-key="email" width="180px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
                Correo Electrónico
              </SortHeader>
              <SortHeader col-key="telefono" width="120px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
                Teléfono
              </SortHeader>
              <SortHeader col-key="rol" width="140px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
                Rol Asignado
              </SortHeader>
              <SortHeader col-key="condominio" width="180px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
                {{ authStore.isMaster ? 'Condominio(s) / Unidad' : 'Condominio / Unidad' }}
              </SortHeader>
              <SortHeader col-key="estado" width="100px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
                Estado
              </SortHeader>
              <th class="text-right" style="width: 100px;">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in paginatedItems" :key="user.id">
              <td>
                <div class="font-weight-bold text-slate-900">{{ user.name }}</div>
                <div class="text-caption text-slate-500">{{ user.cedula || 'Sin cédula' }}</div>
              </td>
              <td>{{ user.email }}</td>
              <td class="text-caption text-slate-600">{{ user.telefono || '-' }}</td>
              <td>
                <v-chip
                  :color="user.rol === 'Super Admin' || user.rol === 'master' ? 'purple' : (user.rol?.includes('Admin') ? 'primary' : 'secondary')"
                  size="small"
                  variant="tonal"
                  class="font-weight-bold"
                >
                  {{ user.rol }}
                </v-chip>
              </td>
              <td>
                <!-- Caso 1: Super Admin / Master -->
                <div v-if="user.rol === 'Super Admin' || user.rol === 'master'">
                  <v-chip size="small" variant="tonal" color="purple" prepend-icon="mdi-earth" class="font-weight-medium">
                    Acceso Global
                  </v-chip>
                </div>

                <!-- Caso 2: Propietario / Residente con Apartamento -->
                <div v-else-if="user.rol === 'Propietario/Residente' || user.rol === 'propietario'">
                  <div class="d-flex align-center font-weight-medium text-slate-800 text-body-2">
                    <v-icon size="16" color="primary" class="mr-1">mdi-home-outline</v-icon>
                    <span>{{ formatCondoName(user.condominio) || 'Sin condominio' }}</span>
                  </div>
                  <div v-if="user.apartamento" class="text-caption text-slate-500 d-flex align-center mt-0.5">
                    <v-icon size="13" class="mr-1 text-slate-400">mdi-door-closed</v-icon>
                    <span>Apto. {{ user.apartamento.numero }} (Piso {{ user.apartamento.piso }})</span>
                  </div>
                </div>

                <!-- Caso 3: Administrador, Supervisor, Analista (puede tener 1 o múltiples condominios/torres) -->
                <div v-else>
                  <!-- Si tiene 0 condominios -->
                  <div v-if="getUserAssignedCondos(user).length === 0" class="text-caption text-slate-400 italic">
                    Sin condominios asignados
                  </div>

                  <!-- Si tiene 1 condominio -->
                  <div v-else-if="getUserAssignedCondos(user).length === 1" class="d-flex align-center font-weight-medium text-slate-800 text-body-2">
                    <v-icon size="16" color="primary" class="mr-1">mdi-office-building</v-icon>
                    <span>{{ formatCondoName(getUserAssignedCondos(user)[0]) }}</span>
                  </div>

                  <!-- Si tiene múltiples condominios (ej. Admin gestionando varios) -->
                  <div v-else class="py-1">
                    <div class="text-caption font-weight-bold text-primary mb-1 d-flex align-center">
                      <v-icon size="14" class="mr-1">mdi-office-building-cog</v-icon>
                      <span>{{ getUserAssignedCondos(user).length }} condominios asignados:</span>
                    </div>
                    <div class="d-flex flex-wrap" style="gap: 4px; max-width: 380px;">
                      <v-chip
                        v-for="condo in getUserAssignedCondos(user)"
                        :key="condo.id"
                        size="x-small"
                        variant="tonal"
                        color="primary"
                        class="font-weight-medium"
                      >
                        <v-icon start size="12">mdi-office-building-marker</v-icon>
                        {{ formatCondoName(condo) }}
                      </v-chip>
                    </div>
                  </div>
                </div>
              </td>
              <td>
                <v-chip
                  v-if="user.bloqueado_hasta"
                  color="error"
                  size="x-small"
                  variant="flat"
                  class="font-weight-bold"
                >
                  Bloqueado ({{ user.intentos_fallidos }} fallos)
                </v-chip>
                <v-chip
                  v-else
                  :color="user.activo ? 'success' : 'error'"
                  size="x-small"
                  variant="flat"
                >
                  {{ user.activo ? 'Activo' : 'Inactivo' }}
                </v-chip>
              </td>
              <td class="text-right">
                <!-- Desbloquear usuario si está suspendido -->
                <v-btn
                  v-if="user.bloqueado_hasta || user.intentos_fallidos > 0"
                  icon="mdi-lock-open-variant"
                  size="small"
                  variant="tonal"
                  color="warning"
                  class="mr-1"
                  title="Desbloquear y Reiniciar Intentos"
                  @click="desbloquearUser(user.id)"
                />

                <v-btn icon="mdi-pencil" size="small" variant="text" color="primary" @click="editUser(user)" />
                <v-btn icon="mdi-delete" size="small" variant="text" color="error" @click="deleteUser(user.id)" />
              </td>
            </tr>
            <tr v-if="!paginatedItems.length">
              <td colspan="7" class="text-center py-6 text-slate-400">
                {{ search ? 'No se encontraron usuarios que coincidan con la búsqueda.' : 'No se encontraron registros.' }}
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

    <!-- Dialog for Create / Edit User -->
    <v-dialog v-model="dialog" max-width="700" :fullscreen="$vuetify.display.xs" scrollable>
      <v-card class="pa-4">
        <v-card-title class="font-weight-bold d-flex align-center justify-space-between">
          <span>{{ isEditing ? (authStore.isMaster ? 'Editar Usuario' : 'Editar Propietario') : (authStore.isMaster ? 'Registrar Nuevo Usuario' : 'Registrar Nuevo Propietario') }}</span>
          <v-btn v-if="$vuetify.display.xs" icon="mdi-close" variant="text" size="small" @click="dialog = false" />
        </v-card-title>
        <v-card-text>
          <!-- Info banner for Admin Condominio -->
          <v-alert
            v-if="!authStore.isMaster"
            type="info"
            variant="tonal"
            density="compact"
            class="mb-4 text-caption"
            icon="mdi-shield-account"
          >
            Registrando usuario con rol <strong>Propietario / Residente</strong> asignado a: <strong>{{ condoDisplayText }}</strong>
          </v-alert>

          <v-form @submit.prevent="saveUser">
            <v-row>
              <v-col cols="12" sm="6">
                <v-text-field v-model="form.name" label="Nombre y Apellido" required />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field v-model="form.cedula" label="Cédula de Identidad (ej. V-12345678)" />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field v-model="form.email" label="Correo Electrónico" required />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field v-model="form.telefono" label="Teléfono de Contacto" />
              </v-col>

              <!-- Rol selector: editable para Master, bloqueado a Propietario para Admin de Condominio -->
              <v-col cols="12" sm="6">
                <v-select
                  v-if="authStore.isMaster"
                  v-model="form.rol"
                  label="Rol en el Sistema"
                  :items="rolesOptions"
                  required
                />
                <v-text-field
                  v-else
                  model-value="Propietario / Residente"
                  label="Rol en el Sistema"
                  readonly
                  disabled
                  prepend-inner-icon="mdi-account-badge"
                  hint="Los administradores de condominio sólo pueden registrar Propietarios"
                  persistent-hint
                />
              </v-col>

              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="form.password"
                  label="Contraseña"
                  type="password"
                  :placeholder="isEditing ? 'Dejar en blanco para no cambiar' : 'Mínimo 6 caracteres'"
                  :required="!isEditing"
                />
              </v-col>

              <!-- Banner para Super Admin -->
              <v-col cols="12" v-if="authStore.isMaster && (form.rol === 'Super Admin' || form.rol === 'master')">
                <v-alert type="info" variant="tonal" density="compact" icon="mdi-earth">
                  Este usuario tiene rol de Super Administrador con acceso global a todos los condominios del sistema.
                </v-alert>
              </v-col>

              <!-- Asignación Múltiple de Condominios para Administrador, Supervisor, Analista -->
              <v-col
                v-else-if="authStore.isMaster && form.rol !== 'Propietario/Residente' && form.rol !== 'propietario'"
                cols="12"
              >
                <v-select
                  v-model="form.condominio_ids"
                  label="Condominios y Torres Asignados"
                  :items="condosList"
                  item-title="displayName"
                  item-value="id"
                  multiple
                  chips
                  closable-chips
                  clearable
                  variant="outlined"
                  density="comfortable"
                  hint="Selecciona uno o más condominios/torres que este administrador gestionará"
                  persistent-hint
                >
                  <template #chip="{ item, props }">
                    <v-chip v-bind="props" color="primary" variant="tonal" size="small" class="font-weight-medium">
                      <v-icon start size="14">mdi-office-building-marker</v-icon>
                      {{ item.title }}
                    </v-chip>
                  </template>
                </v-select>
              </v-col>

              <!-- Condominio y Apartamento selector para rol Propietario (Super Admin) -->
              <template v-else-if="authStore.isMaster && (form.rol === 'Propietario/Residente' || form.rol === 'propietario')">
                <v-col cols="12" sm="6">
                  <v-select
                    v-model="form.condominio_id"
                    label="Condominio Asignado"
                    :items="condosList"
                    item-title="displayName"
                    item-value="id"
                    clearable
                    variant="outlined"
                    density="comfortable"
                    @update:model-value="fetchApartamentosCondo"
                  />
                </v-col>
                <v-col cols="12" sm="6">
                  <v-select
                    v-model="form.apartamento_id"
                    label="Apartamento / Unidad"
                    :items="apartamentosList"
                    item-title="label"
                    item-value="id"
                    clearable
                    variant="outlined"
                    density="comfortable"
                    placeholder="Seleccionar apartamento"
                  />
                </v-col>
              </template>

              <!-- Propietario registrado por Admin de Condominio -->
              <template v-else-if="!authStore.isMaster">
                <v-col cols="12" sm="6">
                  <v-text-field
                    :model-value="condoDisplayText"
                    label="Condominio Asignado"
                    readonly
                    disabled
                    prepend-inner-icon="mdi-office-building-marker"
                    hint="Fijado al condominio/torre que estás administrando actualmente"
                    persistent-hint
                  />
                </v-col>
                <v-col cols="12" sm="6">
                  <v-select
                    v-model="form.apartamento_id"
                    label="Apartamento / Unidad"
                    :items="apartamentosList"
                    item-title="label"
                    item-value="id"
                    clearable
                    variant="outlined"
                    density="comfortable"
                    placeholder="Seleccionar apartamento"
                  />
                </v-col>
              </template>

              <v-col cols="12" sm="6">
                <v-switch v-model="form.activo" label="Usuario Activo" color="primary" />
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn variant="text" @click="dialog = false">Cancelar</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveUser">
            {{ isEditing ? 'Guardar Cambios' : (authStore.isMaster ? 'Guardar Usuario' : 'Guardar Propietario') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../../store/auth';
import { usePagination } from '../../composables/usePagination';
import DataTableHeader from '../../components/DataTableHeader.vue';
import DataTableFooter from '../../components/DataTableFooter.vue';
import SortHeader from '../../components/SortHeader.vue';

const authStore = useAuthStore();
const usersList = ref([]);
const condosList = ref([]);
const apartamentosList = ref([]);
const rolFiltro = ref('');
const condominioFiltro = ref(null);
const apartamentoFiltro = ref(null);
const soloBloqueados = ref(false);
const dialog = ref(false);
const isEditing = ref(false);
const saving = ref(false);

const customGettersUsers = {
    condominio: (u) => u.condominio?.nombre || '',
    estado: (u) => (u.bloqueado ? 'Bloqueado' : (u.activo ? 'Activo' : 'Inactivo')),
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
} = usePagination(usersList, {
    perPage: 10,
    initialSortBy: 'name',
    customGetters: customGettersUsers,
});

const rolesOptions = [
    'Super Admin',
    'Admin de Condominio',
    'Supervisor',
    'Analista del Sistema',
    'Propietario/Residente',
];

const condosFilterOptions = computed(() => {
    return [
        { title: 'Todos los condominios', value: null },
        ...condosList.value.map(c => ({
            title: c.displayName || formatCondoName(c),
            value: c.id
        }))
    ];
});

const apartamentosFilterOptions = computed(() => {
    return [
        { title: 'Todos los apartamentos', value: null },
        { title: '⚠️ Sin apartamento asignado', value: 'sin_apartamento' },
        ...apartamentosList.value.map(a => ({
            title: a.label || `Apto. ${a.numero} (Piso ${a.piso})`,
            value: a.id
        }))
    ];
});

const condoDisplayText = computed(() => {
    const active = authStore.activeCondominio;
    if (!active) return 'Condominio Actual';
    if (active.parent) {
        return `${active.nombre} (${active.parent.nombre})`;
    }
    if (active.torre_bloque) {
        return `${active.nombre} - ${active.torre_bloque}`;
    }
    return active.nombre;
});

// Devuelve la lista deduplicada de condominios asociados al usuario
const getUserAssignedCondos = (user) => {
    if (!user) return [];
    if (user.rol === 'Super Admin' || user.rol === 'master') return [];

    const list = [];
    const addedIds = new Set();

    if (Array.isArray(user.condominios) && user.condominios.length > 0) {
        user.condominios.forEach(c => {
            if (!addedIds.has(c.id)) {
                addedIds.add(c.id);
                list.push(c);
            }
        });
    }

    if (user.condominio && !addedIds.has(user.condominio.id)) {
        addedIds.add(user.condominio.id);
        list.unshift(user.condominio);
    }

    return list;
};

// Formato de visualización con jerarquía (Conjunto - Torre)
const formatCondoName = (condo) => {
    if (!condo) return '';
    if (condo.parent) {
        return `${condo.parent.nombre} - ${condo.nombre || condo.torre_bloque}`;
    }
    if (condo.torre_bloque && !condo.nombre.includes(condo.torre_bloque)) {
        return `${condo.nombre} (${condo.torre_bloque})`;
    }
    return condo.nombre;
};

const form = ref({
    id: null,
    name: '',
    cedula: '',
    email: '',
    telefono: '',
    rol: 'Propietario/Residente',
    password: '',
    condominio_id: null,
    condominio_ids: [],
    apartamento_id: null,
    activo: true,
});

const toggleBloqueados = () => {
    soloBloqueados.value = !soloBloqueados.value;
    fetchUsers();
};

const fetchUsers = async () => {
    try {
        const activeCondoId = authStore.activeCondominioId || authStore.activeCondominio?.id;
        const targetCondoId = authStore.isMaster ? (condominioFiltro.value || undefined) : activeCondoId;
        const params = {
            rol: authStore.isMaster ? (rolFiltro.value || undefined) : undefined,
            condominio_id: targetCondoId,
            apartamento_id: apartamentoFiltro.value || undefined,
            bloqueado: soloBloqueados.value ? true : undefined,
            per_page: 200,
        };
        const { data } = await axios.get('/users', { params });
        if (data.success) {
            usersList.value = data.data.data || data.data;
        }
    } catch (e) {
        authStore.notify('Error al cargar lista de usuarios', 'error');
    }
};

const fetchCondos = async () => {
    if (!authStore.isMaster) return;
    try {
        const { data } = await axios.get('/condominios', { params: { per_page: 100 } });
        if (data.success) {
            const raw = data.data.data || data.data || [];
            condosList.value = raw.map(c => ({
                ...c,
                displayName: formatCondoName(c),
            }));
        }
    } catch (e) {}
};

const fetchApartamentosCondo = async (condoId) => {
    const id = condoId || authStore.activeCondominioId || authStore.activeCondominio?.id;
    if (!id) {
        apartamentosList.value = [];
        return;
    }
    try {
        const { data } = await axios.get('/apartamentos', { params: { condominio_id: id } });
        if (data.success) {
            const list = data.data.data || data.data;
            apartamentosList.value = list.map(a => ({
                id: a.id,
                label: `Apto. ${a.numero} (Piso ${a.piso})`,
            }));
        }
    } catch (e) {}
};

const openCreateDialog = () => {
    isEditing.value = false;
    const defaultCondoId = authStore.isMaster ? (condosList.value[0]?.id || null) : (authStore.activeCondominioId || authStore.activeCondominio?.id || null);
    form.value = {
        id: null,
        name: '',
        cedula: '',
        email: '',
        telefono: '',
        rol: authStore.isMaster ? 'Admin de Condominio' : 'Propietario/Residente',
        password: '123456',
        condominio_id: defaultCondoId,
        condominio_ids: defaultCondoId ? [defaultCondoId] : [],
        apartamento_id: null,
        activo: true,
    };
    if (defaultCondoId) {
        fetchApartamentosCondo(defaultCondoId);
    }
    dialog.value = true;
};

const editUser = (user) => {
    isEditing.value = true;
    const assignedCondos = getUserAssignedCondos(user);
    const assignedIds = assignedCondos.map(c => c.id);
    const primaryCondoId = user.condominio_id || (assignedIds.length > 0 ? assignedIds[0] : null);

    form.value = {
        id: user.id,
        name: user.name,
        cedula: user.cedula,
        email: user.email,
        telefono: user.telefono,
        rol: authStore.isMaster ? user.rol : 'Propietario/Residente',
        password: '',
        condominio_id: primaryCondoId,
        condominio_ids: assignedIds,
        apartamento_id: user.apartamento_id,
        activo: user.activo,
    };

    if (primaryCondoId) {
        fetchApartamentosCondo(primaryCondoId);
    }
    dialog.value = true;
};

const saveUser = async () => {
    saving.value = true;
    try {
        if (!authStore.isMaster) {
            form.value.rol = 'Propietario/Residente';
            form.value.condominio_id = authStore.activeCondominioId || authStore.activeCondominio?.id;
            form.value.condominio_ids = [form.value.condominio_id];
        } else {
            if (form.value.rol === 'Super Admin' || form.value.rol === 'master') {
                form.value.condominio_id = null;
                form.value.condominio_ids = [];
                form.value.apartamento_id = null;
            } else if (form.value.rol === 'Propietario/Residente' || form.value.rol === 'propietario') {
                form.value.condominio_ids = form.value.condominio_id ? [form.value.condominio_id] : [];
            } else {
                // Admin, Supervisor, Analista
                if (form.value.condominio_ids && form.value.condominio_ids.length > 0) {
                    form.value.condominio_id = form.value.condominio_ids[0];
                } else {
                    form.value.condominio_id = null;
                    form.value.condominio_ids = [];
                }
                form.value.apartamento_id = null;
            }
        }

        if (isEditing.value) {
            await axios.put(`/users/${form.value.id}`, form.value);
            authStore.notify('Usuario actualizado exitosamente');
        } else {
            await axios.post('/users', form.value);
            authStore.notify(authStore.isMaster ? 'Usuario registrado exitosamente' : 'Propietario registrado exitosamente');
        }
        dialog.value = false;
        fetchUsers();
    } catch (e) {
        authStore.notify(e.response?.data?.message || 'Error al guardar usuario', 'error');
    } finally {
        saving.value = false;
    }
};

const desbloquearUser = async (id) => {
    try {
        await axios.put(`/users/${id}/desbloquear`);
        authStore.notify('Usuario desbloqueado e intentos reiniciados');
        fetchUsers();
    } catch (e) {
        authStore.notify('Error al desbloquear usuario', 'error');
    }
};

const deleteUser = async (id) => {
    if (!confirm('¿Está seguro de eliminar lógicamente este usuario?')) return;
    try {
        await axios.delete(`/users/${id}`);
        authStore.notify('Usuario eliminado lógicamente');
        fetchUsers();
    } catch (e) {
        authStore.notify('Error al eliminar usuario', 'error');
    }
};

const exportarPdf = () => {
    const params = new URLSearchParams();
    if (authStore.isMaster && condominioFiltro.value) {
        params.append('condominio_id', condominioFiltro.value);
    } else if (!authStore.isMaster && (authStore.activeCondominioId || authStore.activeCondominio?.id)) {
        params.append('condominio_id', authStore.activeCondominioId || authStore.activeCondominio.id);
    }
    if (apartamentoFiltro.value) {
        params.append('apartamento_id', apartamentoFiltro.value);
    }
    if (search.value) {
        params.append('search', search.value);
    }
    if (rolFiltro.value) {
        params.append('rol', rolFiltro.value);
    }
    const url = `/api/v1/reportes/propietarios-pdf?${params.toString()}`;
    window.open(url, '_blank');
};

const exportarExcel = () => {
    const params = new URLSearchParams();
    if (authStore.isMaster && condominioFiltro.value) {
        params.append('condominio_id', condominioFiltro.value);
    } else if (!authStore.isMaster && (authStore.activeCondominioId || authStore.activeCondominio?.id)) {
        params.append('condominio_id', authStore.activeCondominioId || authStore.activeCondominio.id);
    }
    if (apartamentoFiltro.value) {
        params.append('apartamento_id', apartamentoFiltro.value);
    }
    if (search.value) {
        params.append('search', search.value);
    }
    if (rolFiltro.value) {
        params.append('rol', rolFiltro.value);
    }
    const url = `/api/v1/reportes/propietarios-excel?${params.toString()}`;
    window.open(url, '_blank');
};

// Si el usuario cambia de torre/condominio activo en la cabecera, refrescar los usuarios automáticamente
watch(() => authStore.activeCondominioId, (newId) => {
    apartamentoFiltro.value = null;
    fetchApartamentosCondo(newId);
    fetchUsers();
});

watch(condominioFiltro, (newCondoId) => {
    apartamentoFiltro.value = null;
    fetchApartamentosCondo(newCondoId);
    fetchUsers();
});

onMounted(() => {
    fetchUsers();
    fetchCondos();
    const condoId = authStore.isMaster ? condominioFiltro.value : (authStore.activeCondominioId || authStore.activeCondominio?.id);
    fetchApartamentosCondo(condoId);
});
</script>


