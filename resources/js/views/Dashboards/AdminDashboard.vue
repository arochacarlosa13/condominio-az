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

    <!-- Semáforo de Salud Financiera del Condominio (Fase 2.2) -->
    <v-card
      v-if="semaforoFinanciero"
      class="pa-4 pa-sm-5 rounded-xl border mb-6 shadow-sm"
      :class="{
        'bg-emerald-50/70 border-emerald-300 text-emerald-950': semaforoFinanciero.color === 'verde',
        'bg-amber-50/70 border-amber-300 text-amber-950': semaforoFinanciero.color === 'amarillo',
        'bg-rose-50/70 border-rose-300 text-rose-950': semaforoFinanciero.color === 'rojo'
      }"
    >
      <div class="d-flex flex-column flex-md-row justify-space-between align-start align-md-center gap-4">
        <div class="d-flex align-center gap-3">
          <v-avatar
            :color="semaforoFinanciero.color === 'verde' ? 'emerald' : (semaforoFinanciero.color === 'amarillo' ? 'amber-darken-2' : 'rose')"
            size="48"
            class="elevation-2"
          >
            <v-icon :icon="semaforoFinanciero.icon" color="white" size="26" />
          </v-avatar>
          <div>
            <div class="d-flex align-center gap-2">
              <span class="text-caption font-weight-bold text-uppercase tracking-wider">
                Índice de Salud Financiera:
              </span>
              <v-chip
                :color="semaforoFinanciero.color === 'verde' ? 'emerald' : (semaforoFinanciero.color === 'amarillo' ? 'amber-darken-3' : 'rose')"
                size="small"
                variant="flat"
                class="font-weight-bold text-white"
              >
                {{ semaforoFinanciero.estado }} ({{ semaforoFinanciero.ratio_recaudacion }}% Recaudado)
              </v-chip>
            </div>
            <div class="text-subtitle-1 font-weight-bold mt-0.5">
              {{ semaforoFinanciero.descripcion }}
            </div>
          </div>
        </div>

        <div class="d-flex align-center gap-3 w-100 w-md-auto justify-space-between justify-md-end">
          <div class="text-right">
            <div class="text-caption font-weight-medium">Presupuesto del Mes</div>
            <div class="text-subtitle-2 font-weight-bold font-mono">
              ${{ formatNumber(semaforoFinanciero.total_facturado_mes_usd) }} USD
            </div>
          </div>
          <v-btn
            color="primary"
            variant="flat"
            size="small"
            prepend-icon="mdi-bullhorn"
            to="/notificaciones"
            class="font-weight-bold"
          >
            Cobranza Preventiva
          </v-btn>
        </div>
      </div>
    </v-card>

    <!-- Visual Charts & Indicators Row -->
    <v-row class="mb-6">
      <!-- 1. Antigüedad de la Deuda y Morosidad (Fase 2.2) -->
      <v-col cols="12" md="6">
        <v-card class="pa-4 pa-sm-5 bg-white border border-slate-100 rounded-xl shadow-sm" height="100%">
          <div class="d-flex justify-space-between align-center mb-4">
            <div>
              <div class="text-subtitle-1 font-weight-bold text-slate-900">Morosidad por Antigüedad</div>
              <div class="text-caption text-slate-500">Segmentación de cuentas por cobrar según días vencidos</div>
            </div>
            <v-icon icon="mdi-chart-timeline-variant-shimmer" color="primary" />
          </div>

          <div class="d-flex flex-column gap-3">
            <!-- Al día -->
            <div>
              <div class="d-flex justify-space-between text-caption font-weight-medium text-slate-700 mb-1">
                <span>Al Día / No Vencidos ({{ antiguedadDeuda.conteos?.al_dia || 0 }} recibos)</span>
                <span class="font-weight-bold text-emerald-700 font-mono">
                  Bs. {{ formatNumber(antiguedadDeuda.montos_bs?.al_dia) }} (${{ formatNumber(antiguedadDeuda.montos_usd?.al_dia) }})
                </span>
              </div>
              <v-progress-linear :model-value="calcPorcentaje(antiguedadDeuda.montos_bs?.al_dia)" color="emerald" height="8" rounded />
            </div>

            <!-- 1 a 30 días -->
            <div>
              <div class="d-flex justify-space-between text-caption font-weight-medium text-slate-700 mb-1">
                <span>1 a 30 días de mora ({{ antiguedadDeuda.conteos?.dias_1_30 || 0 }} recibos)</span>
                <span class="font-weight-bold text-amber-700 font-mono">
                  Bs. {{ formatNumber(antiguedadDeuda.montos_bs?.dias_1_30) }} (${{ formatNumber(antiguedadDeuda.montos_usd?.dias_1_30) }})
                </span>
              </div>
              <v-progress-linear :model-value="calcPorcentaje(antiguedadDeuda.montos_bs?.dias_1_30)" color="amber-darken-2" height="8" rounded />
            </div>

            <!-- 31 a 60 días -->
            <div>
              <div class="d-flex justify-space-between text-caption font-weight-medium text-slate-700 mb-1">
                <span>31 a 60 días ({{ antiguedadDeuda.conteos?.dias_31_60 || 0 }} recibos)</span>
                <span class="font-weight-bold text-orange-700 font-mono">
                  Bs. {{ formatNumber(antiguedadDeuda.montos_bs?.dias_31_60) }} (${{ formatNumber(antiguedadDeuda.montos_usd?.dias_31_60) }})
                </span>
              </div>
              <v-progress-linear :model-value="calcPorcentaje(antiguedadDeuda.montos_bs?.dias_31_60)" color="orange-darken-2" height="8" rounded />
            </div>

            <!-- 61 a 90 días -->
            <div>
              <div class="d-flex justify-space-between text-caption font-weight-medium text-slate-700 mb-1">
                <span>61 a 90 días ({{ antiguedadDeuda.conteos?.dias_61_90 || 0 }} recibos)</span>
                <span class="font-weight-bold text-rose-700 font-mono">
                  Bs. {{ formatNumber(antiguedadDeuda.montos_bs?.dias_61_90) }} (${{ formatNumber(antiguedadDeuda.montos_usd?.dias_61_90) }})
                </span>
              </div>
              <v-progress-linear :model-value="calcPorcentaje(antiguedadDeuda.montos_bs?.dias_61_90)" color="rose" height="8" rounded />
            </div>

            <!-- +90 días -->
            <div>
              <div class="d-flex justify-space-between text-caption font-weight-medium text-slate-700 mb-1">
                <span>Más de 90 días / Jurídico ({{ antiguedadDeuda.conteos?.dias_mas_90 || 0 }} recibos)</span>
                <span class="font-weight-bold text-purple-900 font-mono">
                  Bs. {{ formatNumber(antiguedadDeuda.montos_bs?.dias_mas_90) }} (${{ formatNumber(antiguedadDeuda.montos_usd?.dias_mas_90) }})
                </span>
              </div>
              <v-progress-linear :model-value="calcPorcentaje(antiguedadDeuda.montos_bs?.dias_mas_90)" color="purple-darken-3" height="8" rounded />
            </div>
          </div>
        </v-card>
      </v-col>

      <!-- 2. Distribución de Gastos por Categoría (Fase 2.2) -->
      <v-col cols="12" md="6">
        <v-card class="pa-4 pa-sm-5 bg-white border border-slate-100 rounded-xl shadow-sm" height="100%">
          <div class="d-flex justify-space-between align-center mb-4">
            <div>
              <div class="text-subtitle-1 font-weight-bold text-slate-900">Distribución de Gastos</div>
              <div class="text-caption text-slate-500">Destino de los fondos comunitarios por rubro</div>
            </div>
            <v-icon icon="mdi-chart-pie" color="indigo" />
          </div>

          <div v-if="distribucionGastos.length > 0" class="d-flex flex-column gap-3">
            <div v-for="(g, idx) in distribucionGastos" :key="idx" class="mb-2">
              <div class="d-flex justify-space-between text-caption font-weight-medium text-slate-700 mb-1">
                <span class="font-weight-bold">{{ g.categoria }}</span>
                <span class="font-mono text-slate-900">${{ formatNumber(g.total_usd) }} USD (Bs. {{ formatNumber(g.total_bs) }})</span>
              </div>
              <v-progress-linear
                :model-value="calcPorcentajeGasto(g.total_bs)"
                :color="getColorGasto(idx)"
                height="8"
                rounded
              />
            </div>
          </div>

          <div v-else class="text-center py-8 text-slate-400">
            <v-icon icon="mdi-receipt-text-clock" size="36" class="mb-1 text-slate-300" />
            <div class="text-caption">No se han registrado facturas pagadas este mes aún.</div>
          </div>

          <div class="mt-4 pt-3 border-t border-slate-100 text-center">
            <v-btn to="/contabilidad/admin" color="primary" variant="tonal" size="small" prepend-icon="mdi-calculator" class="font-weight-bold">
              Ver Libro Mayor y Facturas →
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
const semaforoFinanciero = ref(null);
const antiguedadDeuda = ref({ conteos: {}, montos_bs: {}, montos_usd: {} });
const distribucionGastos = ref([]);

const coloresGastos = ['primary', 'indigo', 'emerald', 'teal', 'amber', 'purple', 'rose'];
const getColorGasto = (idx) => coloresGastos[idx % coloresGastos.length];

const formatNumber = (val) => {
    if (val === null || val === undefined) return '0.00';
    return Number(val).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const calcPorcentaje = (monto) => {
    const total = Object.values(antiguedadDeuda.value.montos_bs || {}).reduce((a, b) => a + Number(b), 0);
    if (!total || total <= 0) return 0;
    return Math.min(100, Math.round((Number(monto || 0) / total) * 100));
};

const calcPorcentajeGasto = (monto) => {
    const total = distribucionGastos.value.reduce((acc, g) => acc + Number(g.total_bs), 0);
    if (!total || total <= 0) return 0;
    return Math.min(100, Math.round((Number(monto || 0) / total) * 100));
};

const fetchData = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get('/dashboard/admin');
        if (data.success) {
            metrics.value = data.data.tarjetas;
            pagos6Meses.value = data.data.grafica_pagos_6_meses || [];
            estadoPagos.value = data.data.grafica_estado_pagos || { pagado: 0, pendiente: 0, vencido: 0 };
            semaforoFinanciero.value = data.data.semaforo_financiero || null;
            antiguedadDeuda.value = data.data.antiguedad_deuda || { conteos: {}, montos_bs: {}, montos_usd: {} };
            distribucionGastos.value = data.data.distribucion_gastos || [];
        }
    } catch (e) {
        authStore.notify('Error al cargar datos del Administrador', 'error');
    } finally {
        loading.value = false;
    }
};

onMounted(fetchData);
</script>
