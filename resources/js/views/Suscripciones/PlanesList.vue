<template>
  <div>
    <!-- Top Header -->
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 :class="['text-h5 font-weight-bold', isDark ? 'text-white' : 'text-slate-900']">Planes y Suscripciones SaaS</h1>
        <p :class="['text-caption', isDark ? 'text-slate-400' : 'text-slate-500']">
          Administración de planes comercializados en la Landing Page y asignados a los condominios
        </p>
      </div>
      <v-btn color="primary" prepend-icon="mdi-plus" class="font-weight-bold" @click="openCreateDialog">
        Nuevo Plan SaaS
      </v-btn>
    </div>

    <!-- Loading Progress -->
    <div v-if="loading" class="text-center py-12">
      <v-progress-circular indeterminate color="primary" size="48" />
      <div class="text-caption text-slate-400 mt-3">Cargando planes de suscripción...</div>
    </div>

    <!-- Plans Cards Grid -->
    <v-row v-else>
      <v-col v-for="plan in planesList" :key="plan.id" cols="12" md="4">
        <v-card
          :class="['pa-6 border d-flex flex-column justify-space-between transition-colors relative', isDark ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200']"
          height="100%"
          :style="plan.destacado ? 'border: 2px solid #2563EB !important;' : ''"
        >
          <!-- Badge Destacado -->
          <div v-if="plan.badge" class="absolute -top-3 left-1/2 -translate-x-1/2 bg-blue-600 text-white text-xs font-weight-black px-3 py-0.5 rounded-full uppercase tracking-wider shadow">
            {{ plan.badge }}
          </div>

          <div>
            <div class="d-flex justify-space-between align-center mb-3 mt-1">
              <span class="text-h6 font-weight-bold text-blue-500">{{ plan.nombre }}</span>
              <v-chip :color="plan.activo ? 'success' : 'error'" size="x-small" variant="flat" class="font-weight-bold">
                {{ plan.activo ? 'Activo' : 'Inactivo' }}
              </v-chip>
            </div>

            <p :class="['text-body-2 mb-4 leading-relaxed', isDark ? 'text-slate-400' : 'text-slate-600']">
              {{ plan.descripcion || 'Sin descripción' }}
            </p>

            <div :class="['mb-4 pa-4 rounded-xl text-center border', isDark ? 'bg-slate-950 border-slate-800' : 'bg-slate-50 border-slate-200']">
              <div :class="['text-caption font-weight-medium', isDark ? 'text-slate-400' : 'text-slate-500']">Cuota Fija Mensual</div>
              <div class="text-h4 font-weight-black text-blue-500 mt-1">${{ Number(plan.precio_mensual || 0).toFixed(2) }} USD</div>
              <div class="text-caption text-slate-400 mt-0.5">Hasta {{ plan.max_apartamentos || 'Ilimitados' }} Apartamentos</div>
            </div>

            <!-- Features list -->
            <ul :class="['space-y-2 mb-6 text-caption', isDark ? 'text-slate-300' : 'text-slate-700']">
              <li v-for="(feat, fIdx) in (plan.caracteristicas || [])" :key="fIdx" class="d-flex align-center gap-2">
                <v-icon icon="mdi-check-circle" color="blue" size="16" />
                <span>{{ feat }}</span>
              </li>
            </ul>
          </div>

          <div>
            <v-divider class="my-4" />
            <div class="d-flex justify-end gap-2">
              <v-btn size="small" variant="tonal" color="primary" prepend-icon="mdi-pencil" class="font-weight-bold" @click="editPlan(plan)">
                Editar
              </v-btn>
            </div>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <!-- Dialog for Create / Edit -->
    <v-dialog v-model="dialog" max-width="540">
      <v-card :class="['pa-5 rounded-2xl border', isDark ? 'bg-slate-900 border-slate-800 text-white' : 'bg-white border-slate-200']">
        <v-card-title class="font-weight-bold text-h6 px-0 pb-3">
          {{ isEditing ? 'Editar Plan SaaS' : 'Nuevo Plan SaaS' }}
        </v-card-title>
        
        <v-card-text class="px-0 pt-2">
          <v-form ref="formRef" @submit.prevent="savePlan">
            <v-text-field v-model="form.nombre" label="Nombre del Plan" required class="mb-3" placeholder="Ej. Plan Básico" />
            <v-textarea v-model="form.descripcion" label="Descripción Comercial" rows="2" class="mb-3" placeholder="Resumen corto..." />
            
            <div class="grid grid-cols-2 gap-3 mb-3">
              <v-text-field v-model.number="form.precio_mensual" label="Precio Mensual ($ USD)" type="number" step="0.01" required />
              <v-text-field v-model.number="form.max_apartamentos" label="Máx. Apartamentos" type="number" required />
            </div>

            <v-text-field v-model="form.badge" label="Etiqueta Badge (Opcional)" placeholder="Ej. MÁS POPULAR" class="mb-3" />

            <div class="mb-4">
              <label class="text-caption font-weight-bold d-block mb-1">Características (Una por línea)</label>
              <v-textarea v-model="caracteristicasTexto" rows="4" placeholder="Una característica por línea..." />
            </div>

            <div class="d-flex align-center justify-space-between gap-4">
              <v-switch v-model="form.destacado" label="Plan Destacado en Landing" color="blue" hide-details />
              <v-switch v-model="form.activo" label="Plan Activo" color="success" hide-details />
            </div>
          </v-form>
        </v-card-text>

        <v-card-actions class="justify-end gap-2 px-0 pt-4 border-t border-slate-800">
          <v-btn variant="text" @click="dialog = false">Cancelar</v-btn>
          <v-btn color="primary" class="font-weight-bold px-6" :loading="saving" @click="savePlan">Guardar Plan</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../../store/auth';
import { useTheme } from '../../composables/useTheme';

const authStore = useAuthStore();
const { isDark } = useTheme();

const planesList = ref([]);
const loading = ref(true);
const dialog = ref(false);
const isEditing = ref(false);
const saving = ref(false);
const caracteristicasTexto = ref('');

const form = ref({
    id: null,
    nombre: '',
    descripcion: '',
    precio_mensual: 25.00,
    max_apartamentos: 50,
    badge: '',
    destacado: false,
    caracteristicas: [],
    activo: true,
});

const fetchPlanes = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get('/planes');
        if (data.success) {
            planesList.value = data.data;
        }
    } catch (e) {
        authStore.notify('Error al cargar planes SaaS', 'error');
    } finally {
        loading.value = false;
    }
};

const openCreateDialog = () => {
    isEditing.value = false;
    form.value = {
        id: null,
        nombre: '',
        descripcion: '',
        precio_mensual: 25.00,
        max_apartamentos: 50,
        badge: '',
        destacado: false,
        caracteristicas: [],
        activo: true,
    };
    caracteristicasTexto.value = '';
    dialog.value = true;
};

const editPlan = (plan) => {
    isEditing.value = true;
    form.value = { ...plan };
    caracteristicasTexto.value = Array.isArray(plan.caracteristicas) ? plan.caracteristicas.join('\n') : '';
    dialog.value = true;
};

const savePlan = async () => {
    saving.value = true;
    try {
        const payload = {
            ...form.value,
            caracteristicas: caracteristicasTexto.value
                .split('\n')
                .map(s => s.trim())
                .filter(s => s.length > 0)
        };

        if (isEditing.value) {
            const { data } = await axios.put(`/planes/${form.value.id}`, payload);
            if (data.success) {
                authStore.notify('Plan SaaS actualizado');
            }
        } else {
            const { data } = await axios.post('/planes', payload);
            if (data.success) {
                authStore.notify('Plan SaaS creado');
            }
        }
        dialog.value = false;
        fetchPlanes();
    } catch (e) {
        authStore.notify(e.response?.data?.message || 'Error al guardar el plan', 'error');
    } finally {
        saving.value = false;
    }
};

onMounted(() => {
    fetchPlanes();
});
</script>

<style scoped>
</style>
