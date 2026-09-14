<template>
  <div class="flujo-caja-container">
    <!-- Top Summary Banner -->
    <v-card class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white pa-5 rounded-xl mb-6 shadow-sm">
      <div class="d-flex flex-column flex-md-row justify-space-between align-start align-md-center gap-4">
        <div>
          <div class="d-flex align-center gap-2 mb-1">
            <v-chip color="emerald" size="small" variant="flat" class="font-weight-bold text-white">
              <v-icon icon="mdi-pulse" start size="14" /> Tiempo Real
            </v-chip>
            <span class="text-caption text-slate-300">Tesorería Bancaria Oficial del Condominio</span>
          </div>
          <h2 class="text-h6 font-weight-bold mb-1">Libro de Flujo de Caja y Balance de Tesorería</h2>
          <p class="text-caption text-slate-300 mb-0 font-mono">
            Saldo Bancario Disponible = Total Cobrado en Banco - Facturas de Contratistas Pagadas
          </p>
        </div>
        <div class="d-flex gap-2 align-center">
          <v-btn
            color="primary"
            variant="tonal"
            prepend-icon="mdi-refresh"
            class="text-white"
            :loading="loading"
            @click="fetchFlujoCaja"
          >
            Actualizar Balance
          </v-btn>
        </div>
      </div>
    </v-card>

    <!-- 3 Big Metric Cards -->
    <v-row class="mb-6">
      <!-- 1. Saldo Bancario Disponible -->
      <v-col cols="12" md="4">
        <v-card class="pa-5 bg-white border border-slate-100 rounded-xl shadow-sm" height="100%">
          <div class="d-flex justify-space-between align-center mb-2">
            <span class="text-caption font-weight-bold text-slate-500 text-uppercase tracking-wider">
              Saldo Bancario Disponible
            </span>
            <v-avatar color="emerald" variant="tonal" size="38">
              <v-icon icon="mdi-bank" color="emerald" size="20" />
            </v-avatar>
          </div>
          <div class="text-h4 font-weight-bold text-slate-900 font-mono">
            Bs. {{ formatNumber(resumen.saldo_bancario_disponible_bs) }}
          </div>
          <div class="text-subtitle-1 font-weight-bold text-emerald-700 mt-1 font-mono">
            ≈ ${{ formatNumber(resumen.saldo_bancario_disponible_usd) }} USD
          </div>
          <v-divider class="my-3" />
          <div class="text-caption text-slate-500 d-flex align-center gap-1">
            <v-icon icon="mdi-shield-check" size="14" color="emerald" />
            Liquidez inmediata en cuentas de la residencia
          </div>
        </v-card>
      </v-col>

      <!-- 2. Total Cobrado en Banco -->
      <v-col cols="12" sm="6" md="4">
        <v-card class="pa-5 bg-white border border-slate-100 rounded-xl shadow-sm" height="100%">
          <div class="d-flex justify-space-between align-center mb-2">
            <span class="text-caption font-weight-bold text-slate-500 text-uppercase tracking-wider">
              Total Cobrado en Banco
            </span>
            <v-avatar color="primary" variant="tonal" size="38">
              <v-icon icon="mdi-cash-plus" color="primary" size="20" />
            </v-avatar>
          </div>
          <div class="text-h5 font-weight-bold text-slate-900 font-mono">
            Bs. {{ formatNumber(resumen.total_cobrado_banco_bs) }}
          </div>
          <div class="text-caption text-slate-500 font-mono mt-1">
            Equivalente: ${{ formatNumber(resumen.total_cobrado_banco_usd) }} USD
          </div>
          <v-divider class="my-3" />
          <div class="text-caption text-slate-500">
            Suma de todos los pagos verificados y aprobados
          </div>
        </v-card>
      </v-col>

      <!-- 3. Facturas de Contratistas Pagadas -->
      <v-col cols="12" sm="6" md="4">
        <v-card class="pa-5 bg-white border border-slate-100 rounded-xl shadow-sm" height="100%">
          <div class="d-flex justify-space-between align-center mb-2">
            <span class="text-caption font-weight-bold text-slate-500 text-uppercase tracking-wider">
              Facturas y Egresos Pagados
            </span>
            <v-avatar color="rose-500" variant="tonal" size="38">
              <v-icon icon="mdi-cash-minus" color="rose-600" size="20" />
            </v-avatar>
          </div>
          <div class="text-h5 font-weight-bold text-rose-700 font-mono">
            Bs. {{ formatNumber(resumen.total_gastos_pagados_bs) }}
          </div>
          <div class="text-caption text-slate-500 font-mono mt-1">
            Equivalente: ${{ formatNumber(resumen.total_gastos_pagados_usd) }} USD
          </div>
          <v-divider class="my-3" />
          <div class="text-caption text-slate-500">
            Egresos operativos y facturas liquidadas a proveedores
          </div>
        </v-card>
      </v-col>
    </v-row>

    <!-- Cuentas Bancarias Grid -->
    <v-card class="bg-white border border-slate-100 rounded-xl mb-6 shadow-sm">
      <div class="pa-4 border-b border-slate-100 d-flex justify-space-between align-center">
        <div>
          <h3 class="text-subtitle-1 font-weight-bold text-slate-900">
            Desglose de Saldos por Cuenta Bancaria
          </h3>
          <p class="text-caption text-slate-500 mb-0">
            Fondos disponibles en cada entidad financiera registrada
          </p>
        </div>
        <v-btn to="/condominios" size="small" variant="text" color="primary" class="font-weight-bold">
          Gestionar Cuentas →
        </v-btn>
      </div>

      <div class="pa-4">
        <v-row v-if="cuentasBancarias.length > 0">
          <v-col
            v-for="cuenta in cuentasBancarias"
            :key="cuenta.id"
            cols="12"
            sm="6"
            md="4"
          >
            <v-card class="pa-4 bg-slate-50 border border-slate-200 rounded-xl" variant="flat">
              <div class="d-flex align-center gap-3 mb-3">
                <v-avatar color="indigo-lighten-5" size="40">
                  <v-icon icon="mdi-bank-outline" color="indigo-darken-3" size="22" />
                </v-avatar>
                <div>
                  <div class="font-weight-bold text-slate-900 text-body-2">{{ cuenta.banco }}</div>
                  <div class="text-caption text-slate-500 text-uppercase">{{ cuenta.tipo_cuenta }}</div>
                </div>
              </div>

              <div class="text-caption text-slate-500 font-mono mb-2">
                N° {{ cuenta.numero_cuenta || 'No configurado' }}
              </div>

              <v-divider class="my-2" />

              <div class="d-flex justify-space-between align-center text-caption mb-1">
                <span class="text-slate-500">Ingresos:</span>
                <span class="font-weight-bold text-slate-800 font-mono">Bs. {{ formatNumber(cuenta.ingresos_bs) }}</span>
              </div>
              <div class="d-flex justify-space-between align-center text-caption mb-2">
                <span class="text-slate-500">Egresos:</span>
                <span class="font-weight-bold text-rose-700 font-mono">- Bs. {{ formatNumber(cuenta.egresos_bs) }}</span>
              </div>

              <div class="pa-2 bg-white rounded-lg border border-slate-200 d-flex justify-space-between align-center">
                <span class="text-caption font-weight-bold text-slate-700">Saldo Disponible:</span>
                <span class="text-body-2 font-weight-bold text-emerald-800 font-mono">
                  Bs. {{ formatNumber(cuenta.saldo_disponible_bs) }}
                </span>
              </div>
            </v-card>
          </v-col>
        </v-row>

        <div v-else class="text-center py-8 text-slate-400">
          <v-icon icon="mdi-bank-off-outline" size="48" class="mb-2 text-slate-300" />
          <p class="text-body-2 mb-0">No se encontraron cuentas bancarias activas registradas para este condominio.</p>
        </div>
      </div>
    </v-card>

    <!-- Monthly Evolution (Last 6 Months) -->
    <v-card class="bg-white border border-slate-100 rounded-xl shadow-sm">
      <div class="pa-4 border-b border-slate-100">
        <h3 class="text-subtitle-1 font-weight-bold text-slate-900">
          Histórico de Flujo de Caja (Últimos 6 Meses)
        </h3>
        <p class="text-caption text-slate-500 mb-0">
          Comparativa mensual de ingresos efectivos recaudados vs gastos liquidados
        </p>
      </div>

      <div class="table-responsive-container">
        <v-table density="comfortable" hover>
          <thead>
            <tr class="bg-slate-50 text-slate-700">
              <th>Mes</th>
              <th>Ingresos Reales (Bs.)</th>
              <th>Ingresos ($ USD)</th>
              <th>Egresos Pagados (Bs.)</th>
              <th>Egresos ($ USD)</th>
              <th>Flujo Neto (Superávit / Déficit)</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, idx) in historico" :key="idx">
              <td class="font-weight-bold text-slate-900">{{ item.mes }}</td>
              <td class="text-emerald-700 font-mono font-weight-medium">
                + Bs. {{ formatNumber(item.ingresos_bs) }}
              </td>
              <td class="text-slate-500 font-mono text-caption">
                ${{ formatNumber(item.ingresos_usd) }}
              </td>
              <td class="text-rose-700 font-mono font-weight-medium">
                - Bs. {{ formatNumber(item.egresos_bs) }}
              </td>
              <td class="text-slate-500 font-mono text-caption">
                ${{ formatNumber(item.egresos_usd) }}
              </td>
              <td>
                <v-chip
                  size="small"
                  :color="item.flujo_neto_bs >= 0 ? 'emerald' : 'rose'"
                  variant="flat"
                  class="font-weight-bold text-white font-mono"
                >
                  <v-icon :icon="item.flujo_neto_bs >= 0 ? 'mdi-trending-up' : 'mdi-trending-down'" start size="14" />
                  {{ item.flujo_neto_bs >= 0 ? '+' : '' }}Bs. {{ formatNumber(item.flujo_neto_bs) }}
                </v-chip>
              </td>
            </tr>
          </tbody>
        </v-table>
      </div>
    </v-card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const loading = ref(false);
const resumen = ref({
  total_cobrado_banco_bs: 0,
  total_cobrado_banco_usd: 0,
  total_gastos_pagados_bs: 0,
  total_gastos_pagados_usd: 0,
  saldo_bancario_disponible_bs: 0,
  saldo_bancario_disponible_usd: 0,
  tasa_cambio: 36.50,
});
const cuentasBancarias = ref([]);
const historico = ref([]);

const fetchFlujoCaja = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/contabilidad/flujo-caja');
    if (res.data?.success) {
      resumen.value = res.data.data.resumen || resumen.value;
      cuentasBancarias.value = res.data.data.cuentas_bancarias || [];
      historico.value = res.data.data.historico_flujo || [];
    }
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Error de Carga',
      text: error.response?.data?.message || 'No se pudo consultar el flujo de caja.',
    });
  } finally {
    loading.value = false;
  }
};

const formatNumber = (val) => {
  if (val === null || val === undefined) return '0,00';
  return Number(val).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

onMounted(() => {
  fetchFlujoCaja();
});
</script>

<style scoped>
.flujo-caja-container {
  animation: fadeIn 0.3s ease;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(6px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
