<template>
  <div class="owner-dashboard-container">
    <!-- 1. Native Banking App Top Header & Hero Card (Fase 2.1) -->
    <div class="mb-4">
      <div class="d-flex justify-space-between align-center mb-3">
        <div>
          <span class="text-caption text-slate-400 font-weight-medium">PORTAL DEL RESIDENTE</span>
          <h1 class="text-h6 font-weight-bold text-slate-900 leading-tight">
            Hola, {{ authStore.user?.name }}
          </h1>
        </div>
        <v-chip size="small" color="primary" variant="tonal" class="font-weight-bold font-mono">
          Apto. {{ unidad?.numero || 'N/D' }}
        </v-chip>
      </div>

      <!-- Hero Card: Deuda Prominente Dual USD / Bs. (Estilo App Bancaria) -->
      <v-card class="hero-bank-card pa-5 rounded-2xl text-white shadow-lg overflow-hidden position-relative border-0">
        <div class="d-flex justify-space-between align-start mb-2">
          <div>
            <div class="text-caption text-emerald-200 font-weight-bold text-uppercase tracking-wider">
              Estado de Cuenta Oficial
            </div>
            <div class="d-flex align-center gap-2 mt-1">
              <v-chip
                v-if="solvente"
                size="small"
                color="emerald"
                variant="flat"
                class="font-weight-bold text-white shadow-sm"
              >
                <v-icon icon="mdi-shield-check" start size="14" /> Residencia Solvente
              </v-chip>
              <v-chip
                v-else
                size="small"
                color="rose-darken-1"
                variant="flat"
                class="font-weight-bold text-white shadow-sm"
              >
                <v-icon icon="mdi-alert-circle" start size="14" /> Saldo Pendiente
              </v-chip>
              <span class="text-caption text-slate-300 font-mono">BCV: Bs. {{ formatNumber(tasaCambio) }}</span>
            </div>
          </div>
          <v-btn
            icon="mdi-certificate-outline"
            variant="tonal"
            color="white"
            size="small"
            title="Ver Constancia de Solvencia Digital"
            @click="solvenciaDialog = true"
          />
        </div>

        <!-- Saldo Dual Gigante -->
        <div class="my-4">
          <div class="text-caption text-slate-300 font-medium">TOTAL ADEUDADO</div>
          <div class="d-flex align-baseline gap-2 flex-wrap">
            <span class="text-h3 font-weight-extrabold font-mono text-white">
              ${{ formatNumber(deudaActualUsd) }}
            </span>
            <span class="text-h6 font-weight-bold text-emerald-300 font-mono">
              USD
            </span>
          </div>
          <div class="text-subtitle-1 font-mono font-weight-medium text-slate-200 mt-1">
            ≈ Bs. {{ formatNumber(deudaActualBs) }}
          </div>
        </div>

        <!-- Botones de Acción Rápida Móvil (PWA) -->
        <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-t border-white/20">
          <v-btn
            color="emerald-accent-3"
            variant="flat"
            prepend-icon="mdi-cash-plus"
            class="font-weight-bold text-slate-950 flex-grow-1"
            size="large"
            rounded="xl"
            @click="openReportarPago"
          >
            Reportar Mi Pago
          </v-btn>
          <v-btn
            color="white"
            variant="tonal"
            prepend-icon="mdi-bank-outline"
            class="font-weight-bold flex-grow-1"
            size="large"
            rounded="xl"
            @click="cuentasDrawer = true"
          >
            Datos para Pagar
          </v-btn>
        </div>
      </v-card>
    </div>

    <!-- Quick Tabs: 1. Mis Recibos (Sin forzar PDF), 2. Pagos Reportados, 3. Mi Unidad -->
    <v-card class="bg-white border border-slate-100 rounded-xl mb-4 shadow-sm">
      <v-tabs v-model="tabResidente" color="primary" grow>
        <v-tab value="recibos" class="font-weight-bold" prepend-icon="mdi-receipt-text-outline">
          Mis Recibos
        </v-tab>
        <v-tab value="pagos" class="font-weight-bold" prepend-icon="mdi-history">
          Historial Pagos
        </v-tab>
        <v-tab value="unidad" class="font-weight-bold" prepend-icon="mdi-home-outline">
          Mi Inmueble
        </v-tab>
      </v-tabs>
    </v-card>

    <v-window v-model="tabResidente">
      <!-- TAB 1: MIS RECIBOS (Con visualizador en pantalla sin forzar descarga) -->
      <v-window-item value="recibos">
        <v-card class="bg-white border border-slate-100 rounded-xl shadow-sm pa-4">
          <div class="d-flex justify-space-between align-center mb-4">
            <div>
              <h3 class="text-subtitle-1 font-weight-bold text-slate-900">Historial de Recibos Emitidos</h3>
              <p class="text-caption text-slate-500 mb-0">Consulta tus renglones directamente en pantalla o descarga el PDF</p>
            </div>
            <v-chip size="small" color="primary" variant="tonal" class="font-weight-bold">
              {{ recibosRecientes.length }} Recibos
            </v-chip>
          </div>

          <div v-if="recibosRecientes.length > 0" class="d-flex flex-column gap-3">
            <v-card
              v-for="recibo in recibosRecientes"
              :key="recibo.id"
              class="pa-4 bg-slate-50 border border-slate-200 rounded-xl hover:shadow-md transition-all"
              variant="flat"
            >
              <div class="d-flex flex-column flex-sm-row justify-space-between align-start align-sm-center gap-3">
                <div>
                  <div class="d-flex align-center gap-2 mb-1">
                    <span class="font-weight-bold text-slate-900 font-mono">{{ recibo.numero_factura || 'Recibo #' + recibo.id }}</span>
                    <v-chip
                      size="x-small"
                      :color="recibo.estado === 'pagado' ? 'emerald' : (recibo.estado === 'parcial' ? 'amber-darken-2' : 'rose')"
                      variant="flat"
                      class="font-weight-bold text-white text-uppercase"
                    >
                      {{ recibo.estado }}
                    </v-chip>
                  </div>
                  <div class="text-caption text-slate-500 font-weight-medium">
                    Período: <strong class="text-slate-800">{{ recibo.periodo }}</strong> • Vence: {{ formatDate(recibo.fecha_vencimiento) }}
                  </div>
                </div>

                <div class="d-flex align-center justify-space-between justify-sm-end w-100 w-sm-auto gap-4">
                  <div class="text-right">
                    <div class="text-subtitle-2 font-weight-bold text-slate-900 font-mono">
                      ${{ formatNumber(recibo.monto_total_usd) }} USD
                    </div>
                    <div class="text-caption text-slate-500 font-mono">
                      Bs. {{ formatNumber(recibo.monto_total) }}
                    </div>
                  </div>

                  <div class="d-flex gap-1">
                    <!-- Ver Desglose Directo en Pantalla (Fase 2.1) -->
                    <v-btn
                      color="primary"
                      variant="tonal"
                      size="small"
                      prepend-icon="mdi-eye-outline"
                      class="font-weight-bold"
                      @click="verDetalleRecibo(recibo)"
                    >
                      Ver En Pantalla
                    </v-btn>
                    <!-- Descargar PDF -->
                    <v-btn
                      icon="mdi-file-pdf-box"
                      variant="text"
                      color="rose-600"
                      size="small"
                      title="Descargar PDF"
                      :href="'/api/v1/reportes/recibo-invoice/' + recibo.id"
                      target="_blank"
                    />
                  </div>
                </div>
              </div>
            </v-card>
          </div>

          <div v-else class="text-center py-8 text-slate-400">
            <v-icon icon="mdi-file-document-outline" size="48" class="mb-2 text-slate-300" />
            <p class="text-body-2 mb-0">No se registran recibos de cobro emitidos para esta unidad.</p>
          </div>
        </v-card>
      </v-window-item>

      <!-- TAB 2: HISTORIAL DE PAGOS REPORTADOS -->
      <v-window-item value="pagos">
        <v-card class="bg-white border border-slate-100 rounded-xl shadow-sm">
          <div class="pa-4 d-flex justify-space-between align-center border-b border-slate-100">
            <h3 class="text-subtitle-1 font-weight-bold text-slate-900">Comprobantes y Notificaciones</h3>
            <v-btn color="success" size="small" variant="flat" prepend-icon="mdi-cash-plus" class="font-weight-bold" @click="openReportarPago">
              Notificar Pago
            </v-btn>
          </div>

          <DataTableHeader
            v-model:search="search"
            v-model:per-page="perPage"
            :per-page-options="perPageOptions"
            placeholder="Buscar por referencia..."
          />

          <div class="table-responsive-container">
            <v-table density="comfortable" hover>
              <thead>
                <tr class="bg-slate-50 text-slate-700">
                  <th>Fecha</th>
                  <th>Método</th>
                  <th>Referencia</th>
                  <th>Monto Pagado</th>
                  <th>Estado</th>
                  <th class="text-right">Comprobante</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="pago in paginatedItems" :key="pago.id">
                  <td class="text-caption font-mono text-slate-600">{{ pago.fecha_pago }}</td>
                  <td class="text-capitalize font-weight-medium text-slate-800">{{ pago.metodo_pago }}</td>
                  <td><code class="font-mono text-primary font-weight-bold">{{ pago.referencia || '-' }}</code></td>
                  <td>
                    <div class="font-weight-bold text-slate-900 font-mono">{{ authStore.formatMoney(pago.monto) }}</div>
                    <div class="text-caption text-slate-400 font-mono">${{ formatNumber(pago.monto_divisa || (pago.monto / tasaCambio)) }} USD</div>
                  </td>
                  <td>
                    <v-chip
                      :color="pago.estado === 'aprobado' ? 'emerald' : (pago.estado === 'pendiente' ? 'amber-darken-2' : 'rose')"
                      size="x-small"
                      variant="flat"
                      class="text-uppercase font-weight-bold text-white"
                    >
                      {{ pago.estado }}
                    </v-chip>
                  </td>
                  <td class="text-right">
                    <v-btn
                      v-if="pago.estado === 'aprobado'"
                      size="small"
                      variant="tonal"
                      color="primary"
                      prepend-icon="mdi-file-certificate-outline"
                      :href="'/api/v1/reportes/recibo/' + pago.id"
                      target="_blank"
                    >
                      Recibo RP
                    </v-btn>
                    <span v-else class="text-caption text-slate-400 italic">En revisión</span>
                  </td>
                </tr>
                <tr v-if="!paginatedItems.length">
                  <td colspan="6" class="text-center text-slate-400 py-6">
                    No se han registrado pagos aún.
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
      </v-window-item>

      <!-- TAB 3: MI INMUEBLE -->
      <v-window-item value="unidad">
        <v-card class="pa-5 bg-white border border-slate-100 rounded-xl shadow-sm">
          <div class="d-flex align-center gap-3 mb-4">
            <v-avatar color="primary" variant="tonal" size="48">
              <v-icon icon="mdi-home-city-outline" size="26" color="primary" />
            </v-avatar>
            <div>
              <h3 class="text-subtitle-1 font-weight-bold text-slate-900">Unidad Residencial Apto. {{ unidad?.numero }}</h3>
              <p class="text-caption text-slate-500 mb-0">Copropietario Principal: {{ authStore.user?.name }}</p>
            </div>
          </div>

          <v-row class="text-caption">
            <v-col cols="6" sm="3">
              <div class="text-slate-400 font-weight-medium">PISO</div>
              <div class="text-body-2 font-weight-bold text-slate-800">{{ unidad?.piso || '-' }}</div>
            </v-col>
            <v-col cols="6" sm="3">
              <div class="text-slate-400 font-weight-medium">ÁREA</div>
              <div class="text-body-2 font-weight-bold text-slate-800">{{ unidad?.metros_cuadrados || 0 }} m²</div>
            </v-col>
            <v-col cols="6" sm="3">
              <div class="text-slate-400 font-weight-medium">ALÍCUOTA BASE</div>
              <div class="text-body-2 font-weight-bold text-indigo-700">{{ Number(unidad?.alicuota || 0).toFixed(4) }}%</div>
            </v-col>
            <v-col cols="6" sm="3">
              <div class="text-slate-400 font-weight-medium">ESTADO</div>
              <div class="text-body-2 font-weight-bold text-emerald-700">Habitado / Al Día</div>
            </v-col>
          </v-row>
        </v-card>
      </v-window-item>
    </v-window>

    <!-- Drawer / Modal para Cuentas Bancarias y Pago Móvil de la Residencia -->
    <v-dialog v-model="cuentasDrawer" max-width="560">
      <v-card class="pa-5 rounded-2xl">
        <div class="d-flex justify-space-between align-center mb-3">
          <div class="d-flex align-center gap-2">
            <v-icon icon="mdi-bank-check" color="emerald" size="24" />
            <h3 class="text-subtitle-1 font-weight-bold text-slate-900">Cuentas Bancarias del Condominio</h3>
          </div>
          <v-btn icon="mdi-close" variant="text" size="small" @click="cuentasDrawer = false" />
        </div>

        <p class="text-caption text-slate-500 mb-4">
          Realiza tus transferencias o Pago Móvil únicamente a las cuentas oficiales de la administración.
        </p>

        <div v-if="cuentasBancarias.length > 0" class="d-flex flex-column gap-3">
          <div
            v-for="c in cuentasBancarias"
            :key="c.id"
            class="pa-3.5 bg-slate-50 border border-slate-200 rounded-xl"
          >
            <div class="d-flex justify-space-between align-center mb-1">
              <strong class="text-slate-900">{{ c.banco }}</strong>
              <v-chip size="x-small" color="primary" variant="tonal" class="font-weight-bold text-uppercase">
                {{ c.tipo_cuenta }}
              </v-chip>
            </div>
            <div class="text-caption text-slate-600 font-mono">
              <div>Cuenta: <strong>{{ c.numero_cuenta }}</strong></div>
              <div>Titular: {{ c.titular }} • RIF/CI: {{ c.identificacion }}</div>
              <div v-if="c.telefono_pago_movil" class="mt-1 text-emerald-700 font-weight-bold">
                📱 Pago Móvil: {{ c.telefono_pago_movil }}
              </div>
            </div>
          </div>
        </div>
        <div v-else class="text-center py-6 text-slate-400 text-caption">
          No hay cuentas registradas. Consulta con tu administrador.
        </div>
      </v-card>
    </v-dialog>

    <!-- Modal para Ver Detalles del Recibo en Pantalla (Fase 2.1 sin forzar PDF) -->
    <v-dialog v-model="reciboModal" max-width="680" scrollable>
      <v-card class="rounded-2xl overflow-hidden" v-if="reciboSeleccionado">
        <div class="bg-slate-900 text-white pa-4 d-flex justify-space-between align-center">
          <div>
            <div class="text-caption text-slate-400">DESGLOSE DEL RECIBO</div>
            <div class="text-subtitle-1 font-weight-bold">{{ reciboSeleccionado.numero_factura }} • {{ reciboSeleccionado.periodo }}</div>
          </div>
          <v-btn icon="mdi-close" variant="text" color="white" size="small" @click="reciboModal = false" />
        </div>

        <v-card-text class="pa-5">
          <!-- Totales -->
          <div class="d-flex justify-space-between align-center pa-4 bg-emerald-50 border border-emerald-200 rounded-xl mb-4">
            <div>
              <span class="text-caption text-emerald-800 font-weight-bold">MONTO TOTAL A PAGAR</span>
              <div class="text-h5 font-weight-bold text-emerald-900 font-mono">
                ${{ formatNumber(reciboSeleccionado.monto_total_usd) }} USD
              </div>
            </div>
            <div class="text-right">
              <span class="text-caption text-slate-500">Tasa BCV: Bs. {{ formatNumber(reciboSeleccionado.tasa_cambio) }}</span>
              <div class="text-subtitle-1 font-weight-bold text-slate-800 font-mono">
                Bs. {{ formatNumber(reciboSeleccionado.monto_total) }}
              </div>
            </div>
          </div>

          <!-- Renglones de Gastos -->
          <div class="text-caption font-weight-bold text-slate-700 text-uppercase mb-2">
            Renglones Presupuestados del Inmueble:
          </div>
          <div class="table-responsive-container border border-slate-200 rounded-lg overflow-hidden">
            <v-table density="compact">
              <thead>
                <tr class="bg-slate-50 text-caption font-weight-bold text-slate-700">
                  <th>Concepto</th>
                  <th class="text-right">Total Edificio</th>
                  <th class="text-right">Mi Cuota ($)</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(g, gIdx) in parseGastos(reciboSeleccionado.detalles_gastos)" :key="gIdx">
                  <td class="text-caption font-medium">{{ g.concepto }}</td>
                  <td class="text-right text-caption font-mono text-slate-500">${{ formatNumber(g.monto) }}</td>
                  <td class="text-right text-caption font-mono font-weight-bold text-slate-900">${{ formatNumber(g.alicu) }}</td>
                </tr>
              </tbody>
            </v-table>
          </div>
        </v-card-text>

        <v-card-actions class="pa-4 bg-slate-50 d-flex justify-space-between border-t border-slate-100">
          <v-btn
            color="rose-600"
            variant="tonal"
            prepend-icon="mdi-file-pdf-box"
            :href="'/api/v1/reportes/recibo-invoice/' + reciboSeleccionado.id"
            target="_blank"
          >
            Descargar en PDF
          </v-btn>
          <v-btn color="primary" variant="flat" class="font-weight-bold px-5" @click="reciboModal = false">
            Listo
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Modal de Constancia Digital de Solvencia -->
    <v-dialog v-model="solvenciaDialog" max-width="500">
      <v-card class="pa-6 rounded-2xl text-center">
        <v-avatar color="emerald-lighten-5" size="64" class="mx-auto mb-3">
          <v-icon icon="mdi-shield-check" color="emerald-darken-2" size="36" />
        </v-avatar>
        <h3 class="text-h6 font-weight-bold text-slate-900">Constancia de Solvencia Digital</h3>
        <p class="text-caption text-slate-500 mb-4">Certificación de Paz y Salvo en el Sistema AZPRO</p>

        <div class="pa-4 bg-slate-50 border border-slate-200 rounded-xl text-left text-caption mb-4">
          <div class="mb-1"><strong>Inmueble:</strong> Apartamento {{ unidad?.numero }}</div>
          <div class="mb-1"><strong>Titular:</strong> {{ authStore.user?.name }}</div>
          <div class="mb-1"><strong>Estado Contable:</strong> <span class="text-emerald-700 font-weight-bold">SOLVENTE AL DÍA</span></div>
          <div><strong>Fecha de Emisión:</strong> {{ new Date().toLocaleDateString('es-VE') }}</div>
        </div>

        <v-btn color="primary" variant="flat" block class="font-weight-bold" @click="solvenciaDialog = false">
          Cerrar
        </v-btn>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../../store/auth';
import { usePagination } from '../../composables/usePagination';
import DataTableHeader from '../../components/DataTableHeader.vue';
import DataTableFooter from '../../components/DataTableFooter.vue';

const router = useRouter();
const authStore = useAuthStore();

const unidad = ref(null);
const deudaActualBs = ref(0);
const deudaActualUsd = ref(0);
const solvente = ref(true);
const tasaCambio = ref(36.50);
const ultimosPagos = ref([]);
const recibosRecientes = ref([]);
const cuentasBancarias = ref([]);
const proximasReservas = ref([]);

const tabResidente = ref('recibos');
const cuentasDrawer = ref(false);
const reciboModal = ref(false);
const solvenciaDialog = ref(false);
const reciboSeleccionado = ref(null);

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
} = usePagination(ultimosPagos, { perPage: 5 });

const openReportarPago = () => {
  router.push('/pagos');
};

const verDetalleRecibo = (recibo) => {
  reciboSeleccionado.value = recibo;
  reciboModal.value = true;
};

const parseGastos = (raw) => {
  if (!raw) return [];
  if (typeof raw === 'string') {
    try { return JSON.parse(raw); } catch (e) { return []; }
  }
  return Array.isArray(raw) ? raw : [];
};

const formatNumber = (val) => {
  if (val === null || val === undefined) return '0.00';
  return Number(val).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatDate = (val) => {
  if (!val) return '-';
  return String(val).substring(0, 10);
};

const fetchData = async () => {
    try {
        const { data } = await axios.get('/dashboard/propietario');
        if (data.success) {
            unidad.value = data.data.unidad;
            deudaActualBs.value = data.data.deuda_actual_bs || 0;
            deudaActualUsd.value = data.data.deuda_actual_usd || 0;
            solvente.value = data.data.solvente ?? (deudaActualBs.value <= 0.05);
            tasaCambio.value = data.data.tasa_cambio || 36.50;
            ultimosPagos.value = data.data.ultimos_pagos || [];
            recibosRecientes.value = data.data.recibos_recientes || [];
            cuentasBancarias.value = data.data.cuentas_bancarias || [];
            proximasReservas.value = data.data.proximas_reservas || [];
        }
    } catch (e) {
        authStore.notify('Error al cargar datos del Propietario', 'error');
    }
};

onMounted(fetchData);
</script>

<style scoped>
.owner-dashboard-container {
  animation: fadeIn 0.3s ease;
  max-width: 1000px;
  margin: 0 auto;
}
.hero-bank-card {
  background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 60%, #064e3b 100%);
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(6px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
