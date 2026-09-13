<template>
  <div>
    <!-- Top Bar -->
    <div class="d-flex flex-column flex-sm-row justify-space-between align-start align-sm-center gap-3 mb-4 mb-sm-6">
      <div>
        <h1 class="text-h5 font-weight-bold text-slate-900">Dashboard Administrador</h1>
        <p class="text-caption text-slate-500">
          Resumen operativo del condominio • Tasa del día: <strong>Bs. {{ metrics.tasa_cambio }} / USD</strong>
        </p>
      </div>
      <div class="d-flex flex-wrap gap-2 w-100 w-sm-auto">
        <v-btn color="teal" prepend-icon="mdi-bank-cog" variant="tonal" to="/condominios" class="flex-grow-1 flex-sm-grow-0" title="Configurar cuentas bancarias y datos de Pago Móvil de la residencia">
          Cuentas y Pago Móvil
        </v-btn>
        <v-btn color="primary" prepend-icon="mdi-currency-usd" variant="tonal" class="flex-grow-1 flex-sm-grow-0" @click="authStore.toggleCurrency">
          {{ authStore.currency }}
        </v-btn>
        <v-btn color="primary" prepend-icon="mdi-refresh" variant="flat" :loading="loading" class="flex-grow-1 flex-sm-grow-0" @click="fetchData">
          Actualizar
        </v-btn>
      </div>
    </div>

    <!-- Summary Cards Row -->
    <v-row class="mb-4 mb-sm-6">
      <v-col cols="12" sm="6" md="3">
        <v-card class="pa-4 bg-white border border-slate-100">
          <div class="d-flex justify-space-between align-center">
            <div>
              <div class="text-caption text-slate-500 font-weight-medium">Total Apartamentos</div>
              <div class="text-h5 font-weight-bold text-slate-900 mt-1">{{ metrics.total_unidades }}</div>
            </div>
            <v-avatar color="primary" variant="tonal" size="44">
              <v-icon icon="mdi-home-city" color="primary" size="22" />
            </v-avatar>
          </div>
          <div class="mt-2 text-caption text-slate-500">Unidades residenciales</div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card class="pa-4 bg-white border border-slate-100">
          <div class="d-flex justify-space-between align-center">
            <div>
              <div class="text-caption text-slate-500 font-weight-medium">Propietarios Registrados</div>
              <div class="text-h5 font-weight-bold text-slate-900 mt-1">{{ metrics.total_propietarios }}</div>
            </div>
            <v-avatar color="info" variant="tonal" size="44">
              <v-icon icon="mdi-account-group" color="info" size="22" />
            </v-avatar>
          </div>
          <div class="mt-2 text-caption text-slate-500">Copropietarios con acceso</div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card class="pa-4 bg-white border border-slate-100">
          <div class="d-flex justify-space-between align-center">
            <div>
              <div class="text-caption text-slate-500 font-weight-medium">Recaudado este Mes</div>
              <div class="text-h6 font-weight-bold text-success mt-1">
                {{ authStore.formatMoney(metrics.recaudado_mes_bs) }}
              </div>
            </div>
            <v-avatar color="success" variant="tonal" size="44">
              <v-icon icon="mdi-check-decagram" color="success" size="22" />
            </v-avatar>
          </div>
          <div class="mt-2 text-caption text-slate-500">
            Pendiente: <strong class="text-error">{{ authStore.formatMoney(metrics.pendiente_mes_bs) }}</strong>
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card class="pa-4 bg-white border border-slate-100">
          <div class="d-flex justify-space-between align-center">
            <div>
              <div class="text-caption text-slate-500 font-weight-medium">Reservas del Mes</div>
              <div class="text-h5 font-weight-bold text-slate-900 mt-1">{{ metrics.reservas_mes }}</div>
            </div>
            <v-avatar color="warning" variant="tonal" size="44">
              <v-icon icon="mdi-calendar-check" color="warning" size="22" />
            </v-avatar>
          </div>
          <div class="mt-2 text-caption text-slate-500">Solicitudes de áreas comunes</div>
        </v-card>
      </v-col>
    </v-row>

    <!-- Visual Charts & Indicators Row -->
    <v-row>
      <!-- Historial de Pagos últimos 6 meses (Barras) -->
      <v-col cols="12" md="6">
        <v-card class="pa-4 pa-sm-5 bg-white border border-slate-100" height="100%">
          <div class="text-subtitle-1 font-weight-bold text-slate-900 mb-4">Recaudación Últimos 6 Meses</div>
          <div class="d-flex flex-column gap-3">
            <div v-for="(item, i) in pagos6Meses" :key="i" class="mb-3">
              <div class="d-flex justify-space-between text-caption font-weight-medium text-slate-700 mb-1">
                <span>{{ item.mes }}</span>
                <span class="font-weight-bold">{{ authStore.formatMoney(item.total_bs) }}</span>
              </div>
              <v-progress-linear
                :model-value="Math.min(100, (item.total_bs / 1000000) * 100)"
                color="primary"
                height="10"
                rounded
              />
            </div>
          </div>
        </v-card>
      </v-col>

      <!-- Estado de Pagos (Torta / Distribución) -->
      <v-col cols="12" md="6">
        <v-card class="pa-4 pa-sm-5 bg-white border border-slate-100" height="100%">
          <div class="text-subtitle-1 font-weight-bold text-slate-900 mb-4">Estado de Expensas del Condominio</div>
          <div class="pa-4 bg-slate-50 rounded-lg">
            <div class="d-flex justify-space-between align-center mb-3">
              <div class="d-flex align-center">
                <v-icon icon="mdi-checkbox-marked-circle" color="success" class="mr-2" />
                <span class="text-body-2 font-weight-medium">Facturas Pagadas</span>
              </div>
              <v-chip color="success" size="small" variant="flat">{{ estadoPagos.pagado }} facturas</v-chip>
            </div>

            <div class="d-flex justify-space-between align-center mb-3">
              <div class="d-flex align-center">
                <v-icon icon="mdi-clock-outline" color="warning" class="mr-2" />
                <span class="text-body-2 font-weight-medium">Facturas Pendientes</span>
              </div>
              <v-chip color="warning" size="small" variant="flat">{{ estadoPagos.pendiente }} facturas</v-chip>
            </div>

            <div class="d-flex justify-space-between align-center">
              <div class="d-flex align-center">
                <v-icon icon="mdi-alert-circle" color="error" class="mr-2" />
                <span class="text-body-2 font-weight-medium">Facturas Vencidas / Mora</span>
              </div>
              <v-chip color="error" size="small" variant="flat">{{ estadoPagos.vencido }} facturas</v-chip>
            </div>
          </div>

          <div class="mt-4 text-center">
            <v-btn to="/contabilidad/admin" color="primary" variant="tonal" prepend-icon="mdi-calculator" class="w-100 w-sm-auto">
              Ir a Contabilidad y Cobranza
            </v-btn>
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
    total_unidades: 0,
    total_propietarios: 0,
    recaudado_mes_bs: 0,
    recaudado_mes_usd: 0,
    pendiente_mes_bs: 0,
    pendiente_mes_usd: 0,
    reservas_mes: 0,
    tasa_cambio: 36.50,
});

const pagos6Meses = ref([]);
const estadoPagos = ref({ pagado: 0, pendiente: 0, vencido: 0 });

const fetchData = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get('/dashboard/admin');
        if (data.success) {
            metrics.value = data.data.tarjetas;
            pagos6Meses.value = data.data.grafica_pagos_6_meses || [];
            estadoPagos.value = data.data.grafica_estado_pagos || { pagado: 0, pendiente: 0, vencido: 0 };
        }
    } catch (e) {
        authStore.notify('Error al cargar datos del Administrador', 'error');
    } finally {
        loading.value = false;
    }
};

onMounted(fetchData);
</script>
