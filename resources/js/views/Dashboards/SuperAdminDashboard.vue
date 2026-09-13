<template>
  <div>
    <!-- Top Greeting & Header -->
    <div class="d-flex flex-column flex-sm-row justify-space-between align-start align-sm-center gap-3 mb-4 mb-sm-6">
      <div>
        <h1 class="text-h5 font-weight-bold text-slate-900">Dashboard Super Admin</h1>
        <p class="text-caption text-slate-500">Métricas globales, facturación SaaS y control contable de la plataforma</p>
      </div>
      <div class="d-flex flex-wrap gap-2 w-100 w-sm-auto">
        <v-btn color="primary" prepend-icon="mdi-office-building" variant="flat" to="/condominios" class="flex-grow-1 flex-sm-grow-0">
          Condominios y Torres
        </v-btn>
        <v-btn color="primary" prepend-icon="mdi-refresh" variant="tonal" :loading="loading" class="flex-grow-1 flex-sm-grow-0" @click="fetchData">
          Actualizar Datos
        </v-btn>
      </div>
    </div>

    <!-- Metric Summary Cards -->
    <v-row class="mb-4 mb-sm-6">
      <v-col cols="12" sm="6" md="3">
        <v-card class="pa-4 bg-white border border-slate-100">
          <div class="d-flex justify-space-between align-center">
            <div>
              <div class="text-caption text-slate-500 font-weight-medium">Condominios Activos</div>
              <div class="text-h5 font-weight-bold text-slate-900 mt-1">{{ metrics.total_condominios_activos }}</div>
            </div>
            <v-avatar color="primary" variant="tonal" size="44">
              <v-icon icon="mdi-office-building" color="primary" size="22" />
            </v-avatar>
          </div>
          <div class="mt-2 text-caption text-success font-weight-medium">
            <v-icon icon="mdi-check-circle-outline" size="14" /> Suscripción activa
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card class="pa-4 bg-white border border-slate-100">
          <div class="d-flex justify-space-between align-center">
            <div>
              <div class="text-caption text-slate-500 font-weight-medium">Total Apartamentos</div>
              <div class="text-h5 font-weight-bold text-slate-900 mt-1">{{ metrics.total_apartamentos }}</div>
            </div>
            <v-avatar color="info" variant="tonal" size="44">
              <v-icon icon="mdi-home-group" color="info" size="22" />
            </v-avatar>
          </div>
          <div class="mt-2 text-caption text-slate-500 font-weight-medium">
            Unidades bajo administración
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card class="pa-4 bg-white border border-slate-100">
          <div class="d-flex justify-space-between align-center">
            <div>
              <div class="text-caption text-slate-500 font-weight-medium">Recaudación Total SaaS</div>
              <div class="text-h6 font-weight-bold text-slate-900 mt-1">
                {{ authStore.formatMoney(metrics.recaudacion_total_bs) }}
              </div>
            </div>
            <v-avatar color="success" variant="tonal" size="44">
              <v-icon icon="mdi-cash-multiple" color="success" size="22" />
            </v-avatar>
          </div>
          <div class="mt-2 text-caption text-slate-500">
            Equivalente: ${{ Number(metrics.recaudacion_total_usd).toFixed(2) }} USD
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card class="pa-4 bg-white border border-slate-100">
          <div class="d-flex justify-space-between align-center">
            <div>
              <div class="text-caption text-slate-500 font-weight-medium">Solventes vs Morosos</div>
              <div class="text-h6 font-weight-bold text-slate-900 mt-1">
                <span class="text-success">{{ metrics.condominios_solventes }}</span> / 
                <span class="text-error">{{ metrics.condominios_morosos }}</span>
              </div>
            </div>
            <v-avatar color="warning" variant="tonal" size="44">
              <v-icon icon="mdi-scale-balance" color="warning" size="22" />
            </v-avatar>
          </div>
          <div class="mt-2 text-caption text-slate-500">
            {{ metrics.condominios_morosos }} condominios con pagos vencidos
          </div>
        </v-card>
      </v-col>
    </v-row>

    <!-- Visual Charts Row -->
    <v-row>
      <!-- Comparativa Barras Solvente vs Moroso -->
      <v-col cols="12" md="4">
        <v-card class="pa-4 pa-sm-5 bg-white border border-slate-100" height="100%">
          <div class="text-subtitle-1 font-weight-bold text-slate-900 mb-4">Solvencia de Condominios</div>
          <div class="d-flex flex-column justify-center align-center py-4">
            <div class="w-100 mb-4">
              <div class="d-flex justify-space-between text-body-2 mb-1">
                <span>Condominios Solventes</span>
                <span class="font-weight-bold text-success">{{ metrics.condominios_solventes }}</span>
              </div>
              <v-progress-linear
                :model-value="(metrics.condominios_solventes / (metrics.condominios_solventes + metrics.condominios_morosos || 1)) * 100"
                color="success"
                height="10"
                rounded
              />
            </div>
            <div class="w-100">
              <div class="d-flex justify-space-between text-body-2 mb-1">
                <span>Condominios Morosos</span>
                <span class="font-weight-bold text-error">{{ metrics.condominios_morosos }}</span>
              </div>
              <v-progress-linear
                :model-value="(metrics.condominios_morosos / (metrics.condominios_solventes + metrics.condominios_morosos || 1)) * 100"
                color="error"
                height="10"
                rounded
              />
            </div>
          </div>
        </v-card>
      </v-col>

      <!-- Distribución de Planes de Suscripción (Torta/Lista) -->
      <v-col cols="12" md="4">
        <v-card class="pa-4 pa-sm-5 bg-white border border-slate-100" height="100%">
          <div class="text-subtitle-1 font-weight-bold text-slate-900 mb-4">Planes Contratados</div>
          <v-list density="compact" class="pa-0">
            <v-list-item
              v-for="(plan, index) in planesList"
              :key="index"
              class="px-0 py-2 border-b border-slate-100"
            >
              <template #prepend>
                <v-avatar color="primary" variant="tonal" size="32" class="mr-3">
                  <v-icon icon="mdi-star" size="18" />
                </v-avatar>
              </template>
              <v-list-item-title class="font-weight-bold text-slate-800">{{ plan.nombre }}</v-list-item-title>
              <template #append>
                <v-chip color="primary" size="small" variant="flat" class="font-weight-bold">
                  {{ plan.cantidad }} condominios
                </v-chip>
              </template>
            </v-list-item>
          </v-list>
        </v-card>
      </v-col>

      <!-- Evolución de Ingresos Mensuales (Líneas / Progreso) -->
      <v-col cols="12" md="4">
        <v-card class="pa-4 pa-sm-5 bg-white border border-slate-100" height="100%">
          <div class="text-subtitle-1 font-weight-bold text-slate-900 mb-4">Evolución de Ingresos (6 Meses)</div>
          <div class="d-flex flex-column gap-3">
            <div v-for="(item, i) in lineasIngresos" :key="i" class="mb-3">
              <div class="d-flex justify-space-between text-caption font-weight-medium text-slate-700 mb-1">
                <span>{{ item.mes }}</span>
                <span>${{ Number(item.total_usd).toFixed(2) }} USD</span>
              </div>
              <v-progress-linear
                :model-value="Math.min(100, (item.total_usd / 200) * 100)"
                color="info"
                height="8"
                rounded
              />
            </div>
          </div>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../../store/auth';

const authStore = useAuthStore();
const loading = ref(false);

const metrics = ref({
    total_condominios_activos: 0,
    total_apartamentos: 0,
    recaudacion_total_usd: 0,
    recaudacion_total_bs: 0,
    condominios_solventes: 0,
    condominios_morosos: 0,
});

const planesList = ref([]);
const lineasIngresos = ref([]);

const fetchData = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get('/dashboard/super-admin');
        if (data.success) {
            metrics.value = data.data.tarjetas;
            planesList.value = data.data.grafica_planes || [];
            lineasIngresos.value = data.data.grafica_ingresos_lineas || [];
        }
    } catch (e) {
        authStore.notify('Error al cargar datos del Super Admin', 'error');
    } finally {
        loading.value = false;
    }
};

onMounted(fetchData);
</script>
