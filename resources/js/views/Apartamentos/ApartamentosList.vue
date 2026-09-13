<template>
  <div>
    <!-- Top Header -->
    <div class="d-flex flex-column flex-md-row justify-space-between align-start align-md-center gap-3 mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold text-slate-900">Apartamentos, Unidades y Alícuotas</h1>
        <p class="text-caption text-slate-500">
          Configuración de metros cuadrados, torres/edificios, porcentaje de alícuotas para avisos de cobro y asignación de propietarios
        </p>
      </div>
      <div class="d-flex flex-wrap align-center gap-2">
        <!-- Asistente de Alícuotas Menu -->
        <v-menu location="bottom end">
          <template #activator="{ props }">
            <v-btn
              v-bind="props"
              color="indigo"
              variant="tonal"
              prepend-icon="mdi-calculator-variant"
              class="font-weight-medium"
            >
              Asistente de Alícuotas
            </v-btn>
          </template>
          <v-list density="compact" elevation="3" class="rounded-xl py-2" min-width="260">
            <v-list-item
              prepend-icon="mdi-equal-box"
              title="Distribuir 100% Equitativo"
              subtitle="Divide el 100% entre todas las unidades por igual"
              @click="confirmarDistribucionEquitativa"
            />
            <v-divider class="my-1" />
            <v-list-item
              prepend-icon="mdi-ruler-square"
              title="Calcular por Metraje (m²)"
              subtitle="Calcula alícuotas según el área de construcción"
              @click="confirmarCalculoMetraje"
            />
          </v-list>
        </v-menu>

        <!-- Catálogo de Alícuotas del Condominio -->
        <v-btn
          color="indigo"
          variant="tonal"
          prepend-icon="mdi-tune"
          @click="openAlicuotasCatalogDialog"
        >
          Catálogo de Alícuotas
        </v-btn>

        <!-- Descargar Plantilla Excel/CSV -->
        <v-btn
          color="secondary"
          variant="tonal"
          prepend-icon="mdi-file-excel"
          :loading="descargandoPlantilla"
          @click="descargarPlantilla"
        >
          Plantilla Excel
        </v-btn>

        <!-- Importar Excel/CSV (Solo Super Admin) -->
        <v-btn
          v-if="authStore.isMaster"
          color="success"
          variant="flat"
          prepend-icon="mdi-file-upload"
          @click="openImportDialog"
        >
          Importar Excel/CSV
        </v-btn>

        <!-- Nueva Unidad Manual -->
        <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateDialog">
          Nueva Unidad
        </v-btn>
      </div>
    </div>

    <!-- Summary Statistics Row -->
    <v-row class="mb-4">
      <v-col cols="12" sm="4">
        <v-card class="pa-4 bg-white border border-slate-100" height="100%">
          <div class="d-flex justify-space-between align-center">
            <div>
              <div class="text-caption text-slate-500 font-weight-bold">TOTAL DE UNIDADES</div>
              <div class="text-h5 font-weight-bold text-slate-900 mt-1">
                {{ aptosList.length }} <span class="text-body-2 font-weight-normal text-slate-500">aptos</span>
              </div>
            </div>
            <v-avatar color="primary" variant="tonal" size="44">
              <v-icon icon="mdi-home-city" color="primary" />
            </v-avatar>
          </div>
          <div class="mt-2 text-caption text-slate-600">
            <span class="text-success font-weight-bold">{{ totalOcupados }} Ocupados</span> • 
            <span class="text-warning font-weight-bold">{{ totalDesocupados }} Desocupados</span>
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="4">
        <v-card class="pa-4 bg-white border border-slate-100" height="100%">
          <div class="d-flex justify-space-between align-center">
            <div>
              <div class="text-caption text-slate-500 font-weight-bold">SUMA DE ALÍCUOTAS</div>
              <div class="text-h5 font-weight-bold text-slate-900 mt-1">
                {{ sumaAlicuotas.toFixed(4) }}%
              </div>
            </div>
            <v-avatar :color="esAlicuotaCompleta ? 'success' : 'warning'" variant="tonal" size="44">
              <v-icon :icon="esAlicuotaCompleta ? 'mdi-check-decagram' : 'mdi-alert-circle'" :color="esAlicuotaCompleta ? 'success' : 'warning'" />
            </v-avatar>
          </div>
          <div class="mt-2">
            <v-chip
              :color="esAlicuotaCompleta ? 'success' : 'warning'"
              size="x-small"
              variant="flat"
              class="font-weight-bold"
            >
              {{ esAlicuotaCompleta ? '100% Distribuido' : `Falta ${(100 - sumaAlicuotas).toFixed(4)}% por asignar` }}
            </v-chip>
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="4">
        <v-card class="pa-4 bg-white border border-slate-100" height="100%">
          <div class="d-flex justify-space-between align-center">
            <div>
              <div class="text-caption text-slate-500 font-weight-bold">ESTRUCTURA DEL INMUEBLE</div>
              <div class="text-subtitle-1 font-weight-bold text-slate-900 text-truncate mt-1" style="max-width: 200px;">
                {{ authStore.activeCondominio?.nombre || 'Condominio' }}
              </div>
            </div>
            <v-avatar color="purple" variant="tonal" size="44">
              <v-icon icon="mdi-office-building-marker" color="purple" />
            </v-avatar>
          </div>
          <div class="mt-2 text-caption text-slate-600">
            <span v-if="torresList.length > 1" class="text-purple font-weight-bold">
              🏘️ {{ torresList.length }} torres en este complejo
            </span>
            <span v-else class="text-slate-500">
              🏢 Edificio independiente
            </span>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <!-- Units Table -->
    <v-card class="bg-white border border-slate-100">
      <DataTableHeader
        v-model:search="search"
        v-model:per-page="perPage"
        :per-page-options="perPageOptions"
        placeholder="Buscar por apto o propietario..."
      >
        <template #actions>
          <v-select
            v-if="torresList.length > 1"
            v-model="torreFiltro"
            :items="torreFiltroOptions"
            item-title="title"
            item-value="value"
            density="compact"
            variant="outlined"
            hide-details
            style="min-width: 160px; max-width: 200px;"
            class="bg-white rounded"
            prepend-inner-icon="mdi-office-building"
          />
          <v-select
            v-model="ocupadoFiltro"
            :items="[
              { title: 'Todos los estados', value: '' },
              { title: 'Ocupados', value: 'ocupado' },
              { title: 'Desocupados', value: 'desocupado' },
            ]"
            item-title="title"
            item-value="value"
            density="compact"
            variant="outlined"
            hide-details
            style="min-width: 140px; max-width: 160px;"
            class="bg-white rounded"
          />
        </template>
      </DataTableHeader>

      <v-table density="comfortable" hover>
        <thead>
          <tr class="bg-slate-50 text-slate-700">
            <SortHeader col-key="numero" width="180px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
              Número / Piso / Torre
            </SortHeader>
            <SortHeader col-key="alicuota" width="160px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
              Alícuotas (%)
            </SortHeader>
            <SortHeader col-key="metros_cuadrados" width="110px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
              Área (m²)
            </SortHeader>
            <SortHeader col-key="habitaciones" width="150px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
              Habitaciones / Baños
            </SortHeader>
            <SortHeader col-key="propietario" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
              Propietario Asignado
            </SortHeader>
            <SortHeader col-key="ocupado" width="110px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
              Estado
            </SortHeader>
            <th class="text-right" style="width: 100px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="apto in filteredApartamentos" :key="apto.id">
            <td>
              <div class="font-weight-bold text-slate-900">
                Apto {{ apto.numero }}
                <v-chip v-if="apto.grupo_alicuota" size="x-small" color="primary" variant="outlined" class="ml-1 font-weight-bold">
                  Ali {{ apto.grupo_alicuota }}
                </v-chip>
              </div>
              <div class="text-caption text-slate-500">
                Piso {{ apto.piso }}
                <span v-if="apto.condominio?.torre_bloque">• {{ apto.condominio.nombre }} ({{ apto.condominio.torre_bloque }})</span>
                <span v-else-if="apto.condominio">• {{ apto.condominio.nombre }}</span>
              </div>
            </td>
            <td>
              <div class="d-flex flex-column gap-1">
                <div v-if="apto.alicuotas_detalle && apto.alicuotas_detalle.length" class="d-flex flex-wrap gap-1">
                  <v-chip
                    v-for="ali in apto.alicuotas_detalle"
                    :key="ali.id || ali.numero"
                    size="x-small"
                    :color="getChipColor(ali.numero)"
                    variant="tonal"
                    class="font-weight-bold"
                  >
                    {{ ali.nombre }}: {{ Number(ali.porcentaje).toFixed(4) }}%
                  </v-chip>
                </div>
                <span v-else class="font-weight-bold text-indigo-700">
                  Ali 1: {{ Number(apto.alicuota || 0).toFixed(4) }}%
                </span>
              </div>
            </td>
            <td>
              <span class="text-slate-700 font-weight-medium">{{ apto.metros_cuadrados }} m²</span>
            </td>
            <td>
              <div class="text-slate-700 text-caption">
                🛏️ {{ apto.habitaciones || 0 }} Hab. • 🚿 {{ apto.banos || 0 }} Baños
              </div>
            </td>
            <td>
              <div v-if="apto.propietarios && apto.propietarios.length">
                <div class="font-weight-medium text-slate-900">
                  {{ apto.propietarios[0].nombre_completo }}
                </div>
                <div class="text-caption text-slate-500">
                  CI: {{ apto.propietarios[0].cedula }}
                </div>
              </div>
              <div v-else class="text-caption text-slate-400 italic">
                Sin propietario asignado
              </div>
            </td>
            <td>
              <v-chip :color="apto.ocupado ? 'success' : 'warning'" size="x-small" variant="tonal">
                {{ apto.ocupado ? 'Ocupado' : 'Desocupado' }}
              </v-chip>
            </td>
            <td class="text-right">
              <v-btn icon="mdi-pencil" size="small" variant="text" color="slate-600" @click="editApto(apto)" />
              <v-btn icon="mdi-delete" size="small" variant="text" color="error" @click="deleteApto(apto.id)" />
            </td>
          </tr>
          <tr v-if="!filteredApartamentos.length">
            <td colspan="7" class="text-center py-6 text-slate-400">
              No se encontraron apartamentos registrados para este criterio de búsqueda.
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
    <v-dialog v-model="dialog" max-width="720" scrollable>
      <v-card class="pa-4">
        <v-card-title class="font-weight-bold">
          {{ isEditing ? 'Editar Apartamento y Alícuotas' : 'Registrar Nueva Unidad' }}
        </v-card-title>
        <v-card-text>
          <v-form @submit.prevent="saveApto">
            <v-row>
              <v-col v-if="torresSelectOptions.length > 1" cols="12">
                <v-select
                  v-model="form.condominio_id"
                  label="Torre / Edificio al que Pertenece"
                  :items="torresSelectOptions"
                  item-title="label"
                  item-value="id"
                  prepend-inner-icon="mdi-office-building-marker"
                  required
                />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field v-model="form.numero" label="Número de Apartamento (ej. 101, 01-A)" required />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field v-model="form.piso" label="Piso (ej. PB, 1, 2)" required />
              </v-col>
              
              <!-- Panel de Configuración de Alícuotas Relacionales -->
              <v-col cols="12">
                <div class="pa-3 bg-slate-50 border border-slate-200 rounded-xl mb-1">
                  <div class="d-flex flex-column flex-sm-row justify-space-between align-start align-sm-center gap-2 mb-3">
                    <div>
                      <div class="font-weight-bold text-slate-800 text-subtitle-2 d-flex align-center">
                        <v-icon icon="mdi-calculator" size="small" class="mr-1" color="indigo" />
                        Alícuotas Asignadas a esta Unidad
                      </div>
                      <div class="text-caption text-slate-500">
                        Configura los porcentajes correspondientes a este inmueble según el catálogo del condominio.
                      </div>
                    </div>
                  </div>

                  <v-row density="compact">
                    <v-col
                      v-for="ali in catalogoAlicuotas"
                      :key="ali.id"
                      cols="12"
                      sm="6"
                    >
                      <v-text-field
                        v-model="formAlicuotas[ali.id]"
                        :label="`Ali ${ali.numero} - ${ali.nombre} (%)`"
                        type="number"
                        step="0.00000001"
                        placeholder="0.00000000"
                        density="comfortable"
                        variant="outlined"
                        class="bg-white"
                        :hint="ali.descripcion || ''"
                        :persistent-hint="!!ali.descripcion"
                      />
                    </v-col>
                  </v-row>
                </div>
              </v-col>

              <v-col cols="12" sm="6">
                <v-text-field v-model="form.metros_cuadrados" label="Área de Construcción (m²)" type="number" required />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field v-model="form.grupo_alicuota" label="Identificador / Torre" placeholder="1, 2 o 3" />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field v-model="form.habitaciones" label="Habitaciones" type="number" />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field v-model="form.banos" label="Baños" type="number" />
              </v-col>
              <v-col cols="12">
                <v-switch v-model="form.ocupado" label="Apartamento Ocupado / Habitado" color="primary" />
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn variant="text" @click="dialog = false">Cancelar</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveApto">Guardar Unidad</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dialog for Managing Condominium Alicuotas Catalog -->
    <v-dialog v-model="alicuotasCatalogDialog" max-width="700" scrollable>
      <v-card class="rounded-2xl">
        <div class="pa-4 bg-slate-50 border-b border-slate-200 d-flex flex-wrap justify-space-between align-center gap-3">
          <div>
            <div class="text-h6 font-weight-bold text-slate-900 d-flex align-center">
              <v-icon icon="mdi-tune" color="indigo" class="mr-2" />
              Catálogo de Alícuotas del Condominio
            </div>
            <div class="text-caption text-slate-500">
              Personaliza o agrega grupos de alícuotas (ej. Gastos Generales, Torre A, Estacionamiento, Maleteros)
            </div>
          </div>
          <v-btn
            color="primary"
            variant="flat"
            prepend-icon="mdi-plus-circle"
            class="font-weight-bold"
            @click="openCreateAlicuotaDef"
          >
            + Nueva Alícuota
          </v-btn>
        </div>

        <v-card-text class="pa-4">
          <v-table density="comfortable" class="border rounded-xl">
            <thead>
              <tr class="bg-slate-100">
                <th style="width: 15%;" class="font-weight-bold">Código</th>
                <th style="width: 45%;" class="font-weight-bold">Nombre de Alícuota</th>
                <th style="width: 25%;" class="font-weight-bold">Descripción</th>
                <th style="width: 15%;" class="text-right font-weight-bold">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in catalogoAlicuotas" :key="item.id">
                <td>
                  <v-chip size="small" color="indigo" variant="tonal" class="font-weight-bold">
                    Ali {{ item.numero }}
                  </v-chip>
                </td>
                <td>
                  <span class="font-weight-bold text-slate-900">{{ item.nombre }}</span>
                  <v-chip v-if="item.numero === 1" size="x-small" color="primary" variant="flat" class="ml-2">Principal</v-chip>
                </td>
                <td class="text-caption text-slate-600">{{ item.descripcion || '-' }}</td>
                <td class="text-right">
                  <v-btn icon="mdi-pencil" size="small" variant="text" color="indigo" @click="editAlicuotaDef(item)" />
                  <v-btn v-if="item.numero !== 1" icon="mdi-delete" size="small" variant="text" color="error" @click="deleteAlicuotaDef(item)" />
                </td>
              </tr>
            </tbody>
          </v-table>
        </v-card-text>
        <v-card-actions class="pa-4 pt-0 justify-end">
          <v-btn variant="text" @click="alicuotasCatalogDialog = false">Cerrar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Sub-dialog to create/edit an alicuota definition in catalog -->
    <v-dialog v-model="editAlicuotaDefDialog" max-width="480">
      <v-card class="pa-4">
        <v-card-title class="font-weight-bold">
          {{ isEditingDef ? 'Editar Alícuota del Catálogo' : 'Nueva Alícuota en el Catálogo' }}
        </v-card-title>
        <v-card-text>
          <v-form @submit.prevent="saveAlicuotaDef">
            <v-text-field
              v-model="defForm.nombre"
              label="Nombre de la Alícuota (ej. Torre B, Estacionamiento Sótano, Local Comercial)"
              required
              class="mb-3"
            />
            <v-text-field
              v-model="defForm.descripcion"
              label="Descripción o Notas Adicionales"
              placeholder="Aplica solo para propietarios de la torre"
              class="mb-3"
            />
          </v-form>
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn variant="text" @click="editAlicuotaDefDialog = false">Cancelar</v-btn>
          <v-btn color="primary" :loading="savingDef" @click="saveAlicuotaDef">Guardar Alícuota</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dialog for Excel / CSV Import -->
    <v-dialog v-model="importDialog" max-width="650" scrollable>
      <v-card class="rounded-2xl pa-2">
        <v-card-title class="d-flex justify-space-between align-center px-4 pt-4 pb-2">
          <div class="d-flex align-center gap-2">
            <v-avatar color="success" variant="tonal" size="36">
              <v-icon icon="mdi-file-excel" color="success" />
            </v-avatar>
            <div>
              <div class="text-h6 font-weight-bold text-slate-900">Importación Masiva de Inmuebles</div>
              <div class="text-caption text-slate-500">Carga apartamentos, alícuotas y propietarios desde archivo CSV o Excel</div>
            </div>
          </div>
          <v-btn icon="mdi-close" variant="text" size="small" @click="importDialog = false" />
        </v-card-title>

        <v-card-text class="px-4 py-2">
          <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl mb-4 text-xs text-slate-600 d-flex justify-space-between align-center">
            <div>
              <strong>¿No tienes el formato adecuado?</strong><br>
              Descarga la plantilla base con las columnas preconfiguradas y ejemplos listos para rellenar.
            </div>
            <v-btn
              size="small"
              color="secondary"
              variant="tonal"
              prepend-icon="mdi-download"
              :loading="descargandoPlantilla"
              @click="descargarPlantilla"
            >
              Descargar
            </v-btn>
          </div>

          <v-file-input
            v-model="importFile"
            label="Selecciona tu archivo (.csv o .xlsx)"
            accept=".csv, .xlsx, .txt"
            prepend-icon=""
            prepend-inner-icon="mdi-paperclip"
            variant="outlined"
            density="comfortable"
            show-size
            class="mb-3"
            :rules="[v => !!v || 'Debes seleccionar un archivo']"
          />

          <div v-if="importResults" class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-900 text-xs mt-2">
            <div class="font-bold mb-1">✅ Resultados de la importación:</div>
            <div>• Unidades creadas: <strong>{{ importResults.creados }}</strong></div>
            <div>• Unidades actualizadas: <strong>{{ importResults.actualizados }}</strong></div>
            <div>• Suma total de alícuotas resultante: <strong>{{ importResults.suma_alicuotas }}%</strong></div>
          </div>
        </v-card-text>

        <v-card-actions class="px-4 pb-4 pt-2 justify-end gap-2">
          <v-btn variant="text" @click="importDialog = false">Cancelar</v-btn>
          <v-btn
            color="success"
            variant="flat"
            :loading="importing"
            :disabled="!importFile"
            prepend-icon="mdi-cloud-upload"
            @click="processImport"
          >
            Procesar e Importar
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import { useAuthStore } from '../../store/auth';
import { usePagination } from '../../composables/usePagination';
import DataTableHeader from '../../components/DataTableHeader.vue';
import DataTableFooter from '../../components/DataTableFooter.vue';
import SortHeader from '../../components/SortHeader.vue';

const authStore = useAuthStore();
const aptosList = ref([]);
const dialog = ref(false);
const isEditing = ref(false);
const saving = ref(false);
const torreFiltro = ref('');
const ocupadoFiltro = ref('');

const importDialog = ref(false);
const importFile = ref(null);
const importing = ref(false);
const importResults = ref(null);

const preFilteredAptos = computed(() => {
    let items = aptosList.value;
    if (torreFiltro.value) {
        items = items.filter(a => String(a.condominio_id) === String(torreFiltro.value));
    }
    if (ocupadoFiltro.value === 'ocupado') {
        items = items.filter(a => a.ocupado);
    } else if (ocupadoFiltro.value === 'desocupado') {
        items = items.filter(a => !a.ocupado);
    }
    return items;
});

const customGetters = {
    numero: (a) => a.numero,
    alicuota: (a) => Number(a.alicuota || 0),
    metros_cuadrados: (a) => Number(a.metros_cuadrados || 0),
    habitaciones: (a) => Number(a.habitaciones || 0),
    propietario: (a) => a.propietarios?.[0]?.nombre_completo || a.propietarios?.[0]?.name || '',
    ocupado: (a) => (a.ocupado ? 'Ocupado' : 'Desocupado'),
};

const {
    search,
    currentPage,
    perPage,
    perPageOptions,
    paginatedItems: filteredApartamentos,
    originalTotal,
    totalItems,
    totalPages,
    startIndex,
    endIndex,
    sortBy,
    sortDesc,
    sort,
} = usePagination(preFilteredAptos, {
    perPage: 10,
    initialSortBy: 'numero',
    customGetters,
});

const totalOcupados = computed(() => aptosList.value.filter(a => a.ocupado).length);
const totalDesocupados = computed(() => aptosList.value.filter(a => !a.ocupado).length);

const sumaAlicuotas = computed(() => {
    return aptosList.value.reduce((acc, a) => acc + Number(a.alicuota || 0), 0);
});

const esAlicuotaCompleta = computed(() => {
    return Math.abs(sumaAlicuotas.value - 100) < 0.01;
});

const torresList = computed(() => {
    const list = [];
    const active = authStore.activeCondominio;
    if (active) {
        if (active.torres && active.torres.length) {
            active.torres.forEach(t => {
                if (!list.some(item => item.id === t.id)) list.push(t);
            });
        } else if (active.parent && active.parent.torres && active.parent.torres.length) {
            active.parent.torres.forEach(t => {
                if (!list.some(item => item.id === t.id)) list.push(t);
            });
        } else {
            list.push(active);
        }
    }
    return list;
});

const torreFiltroOptions = computed(() => {
    const opts = [{ title: 'Todas las Torres', value: '' }];
    torresList.value.forEach(t => {
        opts.push({
            title: t.torre_bloque ? `${t.nombre} (${t.torre_bloque})` : t.nombre,
            value: t.id,
        });
    });
    return opts;
});

const torresSelectOptions = computed(() => {
    return torresList.value.map(t => ({
        id: t.id,
        label: t.torre_bloque ? `${t.nombre} (${t.torre_bloque})` : t.nombre,
    }));
});

const getChipColor = (num) => {
    const colors = ['primary', 'purple', 'teal', 'cyan-darken-3', 'amber-darken-3', 'pink', 'deep-purple', 'indigo', 'blue-grey', 'brown', 'deep-orange', 'green-darken-2'];
    return colors[(num - 1) % colors.length];
};

const catalogoAlicuotas = ref([]);
const formAlicuotas = ref({});

const alicuotasCatalogDialog = ref(false);
const editAlicuotaDefDialog = ref(false);
const isEditingDef = ref(false);
const savingDef = ref(false);
const defForm = ref({
    id: null,
    nombre: '',
    descripcion: '',
    numero: null,
});

const fetchCatalogoAlicuotas = async () => {
    try {
        const condoId = authStore.activeCondominioId;
        if (!condoId) return;
        const { data } = await axios.get(`/condominios/${condoId}/alicuotas`);
        if (data.success) {
            catalogoAlicuotas.value = data.data;
        }
    } catch (e) {
        console.error('Error al cargar catálogo de alícuotas', e);
    }
};

const openAlicuotasCatalogDialog = async () => {
    await fetchCatalogoAlicuotas();
    alicuotasCatalogDialog.value = true;
};

const openCreateAlicuotaDef = () => {
    isEditingDef.value = false;
    defForm.value = {
        id: null,
        nombre: '',
        descripcion: '',
        numero: null,
    };
    editAlicuotaDefDialog.value = true;
};

const editAlicuotaDef = (item) => {
    isEditingDef.value = true;
    defForm.value = { ...item };
    editAlicuotaDefDialog.value = true;
};

const saveAlicuotaDef = async () => {
    if (!defForm.value.nombre) {
        authStore.notify('El nombre de la alícuota es obligatorio', 'warning');
        return;
    }
    savingDef.value = true;
    try {
        if (isEditingDef.value) {
            await axios.put(`/condominio-alicuotas/${defForm.value.id}`, defForm.value);
            authStore.notify('Alícuota actualizada en el catálogo');
        } else {
            const condoId = authStore.activeCondominioId;
            await axios.post(`/condominios/${condoId}/alicuotas`, defForm.value);
            authStore.notify('Nueva alícuota agregada al catálogo');
        }
        editAlicuotaDefDialog.value = false;
        await fetchCatalogoAlicuotas();
        await fetchApartamentos();
    } catch (e) {
        authStore.notify(e.response?.data?.message || 'Error al guardar alícuota', 'error');
    } finally {
        savingDef.value = false;
    }
};

const deleteAlicuotaDef = async (item) => {
    const result = await Swal.fire({
        title: `¿Eliminar Alícuota "${item.nombre}"?`,
        text: 'Esta alícuota se desvinculará del catálogo y de los apartamentos que la tengan asignada.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        customClass: { popup: 'rounded-2xl shadow-xl' },
    });

    if (!result.isConfirmed) return;

    try {
        await axios.delete(`/condominio-alicuotas/${item.id}`);
        authStore.notify('Alícuota eliminada del catálogo');
        await fetchCatalogoAlicuotas();
        await fetchApartamentos();
    } catch (e) {
        authStore.notify(e.response?.data?.message || 'Error al eliminar alícuota', 'error');
    }
};

const getEmptyForm = () => {
    return {
        id: null,
        condominio_id: authStore.activeCondominioId || torresList.value[0]?.id || null,
        numero: '',
        piso: '1',
        alicuota: 3.03460000,
        grupo_alicuota: '1',
        metros_cuadrados: 80,
        habitaciones: 2,
        banos: 1,
        ocupado: true,
    };
};

const form = ref(getEmptyForm());

const fetchApartamentos = async () => {
    try {
        const { data } = await axios.get('/apartamentos');
        if (data.success) {
            aptosList.value = data.data.data || data.data;
        }
    } catch (e) {
        authStore.notify('Error al cargar apartamentos', 'error');
    }
};

const openCreateDialog = async () => {
    isEditing.value = false;
    form.value = getEmptyForm();
    if (!catalogoAlicuotas.value.length) {
        await fetchCatalogoAlicuotas();
    }
    const alicuotasMap = {};
    catalogoAlicuotas.value.forEach(ali => {
        alicuotasMap[ali.id] = (ali.numero === 1) ? 3.03460000 : 0.00000000;
    });
    formAlicuotas.value = alicuotasMap;
    dialog.value = true;
};

const editApto = async (apto) => {
    isEditing.value = true;
    form.value = {
        ...apto,
        condominio_id: apto.condominio_id || authStore.activeCondominioId || null,
        grupo_alicuota: apto.grupo_alicuota || '1',
    };

    if (!catalogoAlicuotas.value.length) {
        await fetchCatalogoAlicuotas();
    }

    const alicuotasMap = {};
    catalogoAlicuotas.value.forEach(ali => {
        alicuotasMap[ali.id] = 0.00000000;
    });

    if (apto.alicuotas_detalle && apto.alicuotas_detalle.length) {
        apto.alicuotas_detalle.forEach(d => {
            if (d.condominio_alicuota_id) {
                alicuotasMap[d.condominio_alicuota_id] = d.porcentaje;
            }
        });
    } else {
        const ali1 = catalogoAlicuotas.value.find(a => a.numero === 1);
        if (ali1) {
            alicuotasMap[ali1.id] = apto.alicuota || 3.03460000;
        }
    }

    formAlicuotas.value = alicuotasMap;
    dialog.value = true;
};

const saveApto = async () => {
    saving.value = true;
    try {
        const alicuotasPayload = [];
        Object.keys(formAlicuotas.value).forEach(aliId => {
            alicuotasPayload.push({
                condominio_alicuota_id: Number(aliId),
                porcentaje: Number(formAlicuotas.value[aliId] || 0)
            });
        });

        // Encontrar valor de alícuota 1
        const ali1Def = catalogoAlicuotas.value.find(a => a.numero === 1);
        const ali1Val = ali1Def && formAlicuotas.value[ali1Def.id] !== undefined
            ? Number(formAlicuotas.value[ali1Def.id])
            : 3.03460000;

        const payload = {
            ...form.value,
            alicuota: ali1Val,
            alicuotas: alicuotasPayload,
        };

        if (isEditing.value) {
            await axios.put(`/apartamentos/${form.value.id}`, payload);
            authStore.notify('Apartamento y alícuotas actualizados exitosamente');
        } else {
            await axios.post('/apartamentos', payload);
            authStore.notify('Apartamento registrado con sus alícuotas');
        }
        dialog.value = false;
        await fetchApartamentos();
    } catch (e) {
        authStore.notify(e.response?.data?.message || 'Error al guardar apartamento', 'error');
    } finally {
        saving.value = false;
    }
};

const deleteApto = async (id) => {
    const result = await Swal.fire({
        title: '¿Eliminar Apartamento?',
        text: 'Esta acción realizará una eliminación lógica de la unidad.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        customClass: { popup: 'rounded-2xl shadow-xl' },
    });

    if (!result.isConfirmed) return;

    try {
        await axios.delete(`/apartamentos/${id}`);
        authStore.notify('Apartamento eliminado');
        fetchApartamentos();
    } catch (e) {
        authStore.notify('Error al eliminar', 'error');
    }
};

const descargandoPlantilla = ref(false);

const descargarPlantilla = async () => {
    descargandoPlantilla.value = true;
    try {
        const response = await axios.get('/apartamentos/plantilla-excel', {
            responseType: 'blob',
        });
        const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8;' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'plantilla_apartamentos_alicuotas.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
        authStore.notify('Plantilla Excel descargada exitosamente');
    } catch (e) {
        authStore.notify('Error al descargar la plantilla de Excel', 'error');
    } finally {
        descargandoPlantilla.value = false;
    }
};

const openImportDialog = () => {
    importFile.value = null;
    importResults.value = null;
    importDialog.value = true;
};

const processImport = async () => {
    if (!importFile.value) return;

    importing.value = true;
    importResults.value = null;
    try {
        const formData = new FormData();
        const fileObj = Array.isArray(importFile.value) ? importFile.value[0] : importFile.value;
        formData.append('archivo', fileObj);
        if (authStore.activeCondominioId) {
            formData.append('condominio_id', authStore.activeCondominioId);
        }

        const { data } = await axios.post('/apartamentos/importar-excel', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        if (data.success) {
            importResults.value = data.data;
            authStore.notify(data.message || 'Importación completada');
            await fetchApartamentos();

            await Swal.fire({
                icon: 'success',
                title: '¡Importación Exitosa!',
                html: `
                    <div class="text-sm text-slate-600 text-left">
                        <p class="mb-2">${data.message}</p>
                        <div class="p-3 bg-slate-100 rounded-xl space-y-1">
                            <div>• <strong>${data.data.creados}</strong> nuevas unidades registradas</div>
                            <div>• <strong>${data.data.actualizados}</strong> unidades actualizadas</div>
                            <div>• Suma total de alícuotas: <strong>${data.data.suma_alicuotas}%</strong></div>
                        </div>
                    </div>
                `,
                confirmButtonColor: '#16a34a',
                confirmButtonText: 'Excelente',
                customClass: { popup: 'rounded-3xl shadow-xl' },
            });
            importDialog.value = false;
        }
    } catch (e) {
        await Swal.fire({
            icon: 'error',
            title: 'Error al importar',
            text: e.response?.data?.message || 'Ocurrió un error al procesar el archivo.',
            confirmButtonColor: '#0f172a',
            customClass: { popup: 'rounded-2xl' },
        });
    } finally {
        importing.value = false;
    }
};

const confirmarDistribucionEquitativa = async () => {
    if (!aptosList.value.length) {
        authStore.notify('No hay apartamentos para distribuir', 'warning');
        return;
    }

    const result = await Swal.fire({
        title: '<span class="text-slate-900 font-bold">¿Distribuir Alícuotas Equitativamente?</span>',
        html: `
            <div class="text-left text-sm text-slate-600">
                <p class="mb-2">Esta acción calculará y asignará a cada apartamento la proporción exacta para que la suma total sea <strong>100.00000000%</strong>.</p>
                <div class="p-3 bg-indigo-50 border-l-4 border-indigo-500 rounded-lg text-indigo-950 text-xs">
                    💡 <strong>Fórmula:</strong> 100% ÷ ${aptosList.value.length} apartamentos = <strong>${(100 / aptosList.value.length).toFixed(8)}%</strong> por unidad.
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#64748b',
        confirmButtonText: '⚡ Sí, Distribuir 100%',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        customClass: { popup: 'rounded-3xl shadow-xl' },
    });

    if (!result.isConfirmed) return;

    try {
        const { data } = await axios.post('/apartamentos/distribuir-equitativo', {
            condominio_id: authStore.activeCondominioId,
        });
        if (data.success) {
            authStore.notify(data.message);
            await fetchApartamentos();
            await Swal.fire({
                icon: 'success',
                title: '¡Distribución Completada!',
                text: data.message,
                confirmButtonColor: '#0f172a',
                customClass: { popup: 'rounded-2xl' },
            });
        }
    } catch (e) {
        authStore.notify(e.response?.data?.message || 'Error al distribuir alícuotas', 'error');
    }
};

const confirmarCalculoMetraje = async () => {
    if (!aptosList.value.length) {
        authStore.notify('No hay apartamentos registrados', 'warning');
        return;
    }

    const totalMetros = aptosList.value.reduce((acc, a) => acc + Number(a.metros_cuadrados || 0), 0);
    const result = await Swal.fire({
        title: '<span class="text-slate-900 font-bold">¿Calcular Alícuotas por Metraje (m²)?</span>',
        html: `
            <div class="text-left text-sm text-slate-600">
                <p class="mb-2">El sistema distribuirá el 100% proporcionalmente según los metros cuadrados ($m^2$) de construcción de cada inmueble.</p>
                <div class="p-3 bg-indigo-50 border-l-4 border-indigo-500 rounded-lg text-indigo-950 text-xs">
                    📐 Metraje Total Detectado: <strong>${totalMetros.toFixed(2)} m²</strong> distribuidos en ${aptosList.value.length} unidades.
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#64748b',
        confirmButtonText: '📐 Sí, Calcular por m²',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        customClass: { popup: 'rounded-3xl shadow-xl' },
    });

    if (!result.isConfirmed) return;

    try {
        const { data } = await axios.post('/apartamentos/calcular-metraje', {
            condominio_id: authStore.activeCondominioId,
        });
        if (data.success) {
            authStore.notify(data.message);
            await fetchApartamentos();
            await Swal.fire({
                icon: 'success',
                title: '¡Cálculo por Metraje Aplicado!',
                text: data.message,
                confirmButtonColor: '#0f172a',
                customClass: { popup: 'rounded-2xl' },
            });
        }
    } catch (e) {
        authStore.notify(e.response?.data?.message || 'Error al calcular alícuotas por metraje', 'error');
    }
};

onMounted(async () => {
    await fetchCatalogoAlicuotas();
    await fetchApartamentos();
});
</script>
