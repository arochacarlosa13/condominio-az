<template>
  <div class="conciliacion-container">
    <!-- Top Information Banner -->
    <v-card class="bg-slate-900 text-white pa-5 rounded-xl mb-5 shadow-sm">
      <div class="d-flex flex-column flex-md-row justify-space-between align-start align-md-center gap-4">
        <div>
          <div class="d-flex align-center gap-2 mb-1">
            <v-chip color="emerald" size="small" variant="flat" class="font-weight-bold text-white">
              <v-icon icon="mdi-shield-check" start size="14" /> Cero Error Humano
            </v-chip>
            <span class="text-caption text-slate-400">Algoritmo Auto-Matching por Referencia y Monto</span>
          </div>
          <h2 class="text-h6 font-weight-bold mb-1">Conciliación Bancaria Inteligente</h2>
          <p class="text-caption text-slate-300 mb-0" style="max-width: 680px;">
            Carga el extracto digital de tu banco (Banesco, Mercantil, BDV, Provincial, BNC, etc.). El sistema contrastará automáticamente los créditos con las notificaciones de pago registradas por los residentes.
          </p>
        </div>
        <div class="d-flex gap-2">
          <v-btn
            color="emerald"
            prepend-icon="mdi-file-upload-outline"
            class="font-weight-bold text-white px-5"
            size="large"
            rounded="lg"
            :loading="analizando"
            @click="triggerFileInput"
          >
            Subir Extracto Bancario
          </v-btn>
          <input
            ref="fileInput"
            type="file"
            accept=".csv,.txt,.xlsx,.xls"
            class="d-none"
            @change="handleFileUpload"
          >
        </div>
      </div>
    </v-card>

    <!-- Drop Zone / Empty State when no file analyzed yet -->
    <v-card
      v-if="!resultado"
      class="border-dashed border-2 border-slate-300 rounded-xl pa-8 text-center bg-slate-50 hover:bg-slate-100 transition-all cursor-pointer"
      @click="triggerFileInput"
      @dragover.prevent
      @drop.prevent="handleDrop"
    >
      <v-avatar color="primary" variant="tonal" size="64" class="mb-3">
        <v-icon icon="mdi-file-excel-outline" size="36" color="primary" />
      </v-avatar>
      <h3 class="text-subtitle-1 font-weight-bold text-slate-800">
        Arrastra tu archivo CSV o extracto bancario aquí
      </h3>
      <p class="text-caption text-slate-500 mb-4">
        Formatos compatibles: CSV delimitado por punto y coma (;) o comas (,), TXT y exportes bancarios estándar.
      </p>
      <v-btn color="primary" variant="outlined" prepend-icon="mdi-paperclip" size="small" class="font-weight-bold">
        Examinar mis archivos
      </v-btn>
    </v-card>

    <!-- Results Panel when analysis is ready -->
    <div v-else>
      <!-- Summary Metrics Bar -->
      <v-row class="mb-4">
        <v-col cols="12" sm="6" md="3">
          <v-card class="pa-4 bg-white border border-slate-100 rounded-xl">
            <div class="text-caption text-slate-500 font-weight-bold">MOVIMIENTOS LEÍDOS</div>
            <div class="text-h5 font-weight-bold text-slate-900 mt-1">
              {{ resultado.total_movimientos_leidos }}
            </div>
            <div class="text-caption text-slate-400 mt-1">Créditos encontrados en extracto</div>
          </v-card>
        </v-col>

        <v-col cols="12" sm="6" md="3">
          <v-card class="pa-4 bg-emerald-50 border border-emerald-200 rounded-xl">
            <div class="text-caption text-emerald-700 font-weight-bold d-flex align-center gap-1">
              <v-icon icon="mdi-check-circle" size="14" color="emerald" /> COINCIDENCIAS EXACTAS
            </div>
            <div class="text-h5 font-weight-bold text-emerald-800 mt-1">
              {{ resultado.coincidencias_exactas }}
            </div>
            <div class="text-caption text-emerald-600 mt-1">Listos para aprobación masiva</div>
          </v-card>
        </v-col>

        <v-col cols="12" sm="6" md="3">
          <v-card class="pa-4 bg-amber-50 border border-amber-200 rounded-xl">
            <div class="text-caption text-amber-700 font-weight-bold d-flex align-center gap-1">
              <v-icon icon="mdi-alert-circle" size="14" color="amber-darken-2" /> DIFERENCIA DE MONTO
            </div>
            <div class="text-h5 font-weight-bold text-amber-900 mt-1">
              {{ resultado.coincidencias_parciales }}
            </div>
            <div class="text-caption text-amber-700 mt-1">Requieren revisión de céntimos</div>
          </v-card>
        </v-col>

        <v-col cols="12" sm="6" md="3">
          <v-card class="pa-4 bg-slate-50 border border-slate-200 rounded-xl">
            <div class="text-caption text-slate-600 font-weight-bold">SIN COINCIDENCIA</div>
            <div class="text-h5 font-weight-bold text-slate-700 mt-1">
              {{ resultado.movimientos_sin_pago }}
            </div>
            <div class="text-caption text-slate-500 mt-1">Depósitos sin pago reportado</div>
          </v-card>
        </v-col>
      </v-row>

      <!-- Action Bar and Filters -->
      <v-card class="bg-white border border-slate-100 rounded-xl mb-4 pa-4">
        <div class="d-flex flex-column flex-sm-row justify-space-between align-start align-sm-center gap-3">
          <!-- Filter Chips -->
          <div class="d-flex flex-wrap gap-2">
            <v-chip
              :color="filtroEstado === 'todos' ? 'primary' : 'default'"
              :variant="filtroEstado === 'todos' ? 'flat' : 'outlined'"
              size="small"
              class="cursor-pointer font-weight-bold"
              @click="filtroEstado = 'todos'"
            >
              Todos ({{ resultado.movimientos.length }})
            </v-chip>
            <v-chip
              :color="filtroEstado === 'exacto' ? 'emerald' : 'default'"
              :variant="filtroEstado === 'exacto' ? 'flat' : 'outlined'"
              size="small"
              class="cursor-pointer font-weight-bold"
              @click="filtroEstado = 'exacto'"
            >
              Exactos ({{ resultado.coincidencias_exactas }})
            </v-chip>
            <v-chip
              :color="filtroEstado === 'parcial' ? 'amber' : 'default'"
              :variant="filtroEstado === 'parcial' ? 'flat' : 'outlined'"
              size="small"
              class="cursor-pointer font-weight-bold"
              @click="filtroEstado = 'parcial'"
            >
              Con Diferencias ({{ resultado.coincidencias_parciales }})
            </v-chip>
            <v-chip
              :color="filtroEstado === 'sin_coincidencia' ? 'slate' : 'default'"
              :variant="filtroEstado === 'sin_coincidencia' ? 'flat' : 'outlined'"
              size="small"
              class="cursor-pointer font-weight-bold"
              @click="filtroEstado = 'sin_coincidencia'"
            >
              Sin Pago ({{ resultado.movimientos_sin_pago }})
            </v-chip>
          </div>

          <!-- Batch Approval Actions -->
          <div class="d-flex gap-2 w-100 w-sm-auto">
            <v-btn
              variant="outlined"
              color="primary"
              size="small"
              class="font-weight-bold"
              @click="seleccionarTodosExactos"
            >
              Seleccionar Exactos
            </v-btn>
            <v-btn
              color="success"
              variant="flat"
              prepend-icon="mdi-check-all"
              class="font-weight-bold"
              :disabled="selectedPagos.length === 0"
              :loading="aprobandoLote"
              @click="confirmarAprobacionMasiva"
            >
              Aprobar {{ selectedPagos.length }} Conciliados
            </v-btn>
            <v-btn
              variant="text"
              color="error"
              size="small"
              icon="mdi-close"
              title="Descartar análisis y cargar otro"
              @click="resultado = null"
            />
          </div>
        </div>
      </v-card>

      <!-- Matching Table -->
      <v-card class="bg-white border border-slate-100 rounded-xl">
        <div class="table-responsive-container">
          <v-table density="comfortable" hover style="min-width: 760px;">
            <thead>
              <tr class="bg-slate-50 text-slate-700">
                <th style="width: 44px;">
                  <v-checkbox-btn
                    :model-value="todosSeleccionados"
                    :indeterminate="seleccionIndeterminada"
                    @update:model-value="toggleSeleccionarTodos"
                  />
                </th>
                <th>Fecha Banco</th>
                <th>Referencia Banco</th>
                <th>Monto Banco</th>
                <th>Pago del Sistema</th>
                <th>Estatus de Coincidencia</th>
                <th class="text-right">Acción</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(item, idx) in movimientosFiltrados"
                :key="idx"
                :class="{
                  'bg-emerald-50/40': item.match_status === 'exacto' && selectedPagos.includes(item.pago_id),
                  'bg-amber-50/40': item.match_status === 'parcial'
                }"
              >
                <!-- Checkbox -->
                <td>
                  <v-checkbox-btn
                    v-if="item.pago_id"
                    :model-value="selectedPagos.includes(item.pago_id)"
                    @update:model-value="toggleSeleccion(item.pago_id)"
                  />
                </td>

                <!-- Fecha -->
                <td class="text-caption font-mono text-slate-600">
                  {{ item.fecha }}
                </td>

                <!-- Ref Banco -->
                <td>
                  <div class="font-weight-bold font-mono text-slate-900">
                    {{ item.referencia_banco || 'S/R' }}
                  </div>
                  <div class="text-caption text-slate-400 text-truncate" style="max-width: 220px;" :title="item.descripcion">
                    {{ item.descripcion }}
                  </div>
                </td>

                <!-- Monto Banco -->
                <td>
                  <div class="font-weight-bold text-slate-900">
                    Bs. {{ formatNumber(item.monto_bs) }}
                  </div>
                  <div class="text-caption text-slate-400 font-mono">
                    ≈ ${{ Number(item.monto_usd).toFixed(2) }} USD
                  </div>
                </td>

                <!-- Pago Sistema -->
                <td>
                  <div v-if="item.pago_detalles">
                    <div class="font-weight-bold text-slate-900">
                      Apto. {{ item.pago_detalles.apartamento }}
                    </div>
                    <div class="text-caption text-slate-500 font-mono">
                      Ref: <strong>{{ item.pago_detalles.referencia_reportada || 'N/D' }}</strong> • Bs. {{ formatNumber(item.pago_detalles.monto_reportado_bs) }}
                    </div>
                  </div>
                  <div v-else class="text-caption text-slate-400 italic">
                    Sin notificación registrada en sistema
                  </div>
                </td>

                <!-- Estatus de Coincidencia -->
                <td>
                  <v-chip
                    v-if="item.match_status === 'exacto'"
                    color="emerald"
                    size="small"
                    variant="flat"
                    class="font-weight-bold text-white"
                  >
                    <v-icon icon="mdi-check-decagram" start size="14" /> 100% Exacto
                  </v-chip>
                  <v-chip
                    v-else-if="item.match_status === 'parcial'"
                    color="amber-darken-2"
                    size="small"
                    variant="flat"
                    class="font-weight-bold text-white"
                  >
                    <v-icon icon="mdi-alert" start size="14" /> Diferencia
                  </v-chip>
                  <v-chip
                    v-else
                    color="slate"
                    size="small"
                    variant="tonal"
                    class="font-weight-medium text-slate-600"
                  >
                    No Notificado
                  </v-chip>
                  <div v-if="item.motivo_diferencia" class="text-caption text-slate-500 mt-0.5">
                    {{ item.motivo_diferencia }}
                  </div>
                </td>

                <!-- Accion individual -->
                <td class="text-right">
                  <v-btn
                    v-if="item.pago_id"
                    size="small"
                    color="primary"
                    variant="tonal"
                    class="font-weight-bold"
                    @click="aprobarIndividual(item.pago_id)"
                  >
                    Aprobar
                  </v-btn>
                </td>
              </tr>
            </tbody>
          </v-table>
        </div>
      </v-card>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const emit = defineEmits(['pagos-actualizados']);

const fileInput = ref(null);
const analizando = ref(false);
const aprobandoLote = ref(false);
const resultado = ref(null);
const filtroEstado = ref('todos');
const selectedPagos = ref([]);

const triggerFileInput = () => {
  if (fileInput.value) {
    fileInput.value.click();
  }
};

const handleDrop = (e) => {
  const files = e.dataTransfer.files;
  if (files && files.length > 0) {
    procesarArchivo(files[0]);
  }
};

const handleFileUpload = (e) => {
  const files = e.target.files;
  if (files && files.length > 0) {
    procesarArchivo(files[0]);
  }
};

const procesarArchivo = async (file) => {
  const formData = new FormData();
  formData.append('archivo', file);

  analizando.value = true;
  try {
    const res = await axios.post('/conciliacion/analizar', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    if (res.data?.success) {
      resultado.value = res.data.data;
      // Auto-seleccionar los exactos para máxima conveniencia del administrador
      selectedPagos.value = resultado.value.movimientos
        .filter((m) => m.match_status === 'exacto' && m.pago_id)
        .map((m) => m.pago_id);

      Swal.fire({
        icon: 'success',
        title: 'Extracto Analizado con Éxito',
        html: `Se detectaron <strong>${resultado.value.coincidencias_exactas}</strong> coincidencias exactas listas para aprobar masivamente.`,
        timer: 3000,
        showConfirmButton: false,
      });
    }
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Error al procesar el archivo',
      text: error.response?.data?.message || 'No se pudo leer el archivo bancario.',
    });
  } finally {
    analizando.value = false;
    if (fileInput.value) fileInput.value.value = '';
  }
};

const movimientosFiltrados = computed(() => {
  if (!resultado.value?.movimientos) return [];
  if (filtroEstado.value === 'todos') return resultado.value.movimientos;
  return resultado.value.movimientos.filter((m) => m.match_status === filtroEstado.value);
});

const seleccionablesVisibles = computed(() => {
  return movimientosFiltrados.value.filter((m) => m.pago_id).map((m) => m.pago_id);
});

const todosSeleccionados = computed(() => {
  const list = seleccionablesVisibles.value;
  return list.length > 0 && list.every((id) => selectedPagos.value.includes(id));
});

const seleccionIndeterminada = computed(() => {
  const list = seleccionablesVisibles.value;
  const algun = list.some((id) => selectedPagos.value.includes(id));
  return algun && !todosSeleccionados.value;
});

const toggleSeleccion = (pagoId) => {
  const idx = selectedPagos.value.indexOf(pagoId);
  if (idx === -1) {
    selectedPagos.value.push(pagoId);
  } else {
    selectedPagos.value.splice(idx, 1);
  }
};

const toggleSeleccionarTodos = () => {
  const list = seleccionablesVisibles.value;
  if (todosSeleccionados.value) {
    selectedPagos.value = selectedPagos.value.filter((id) => !list.includes(id));
  } else {
    const set = new Set([...selectedPagos.value, ...list]);
    selectedPagos.value = Array.from(set);
  }
};

const seleccionarTodosExactos = () => {
  if (!resultado.value?.movimientos) return;
  const exactos = resultado.value.movimientos
    .filter((m) => m.match_status === 'exacto' && m.pago_id)
    .map((m) => m.pago_id);
  selectedPagos.value = exactos;
};

const confirmarAprobacionMasiva = async () => {
  if (selectedPagos.value.length === 0) return;

  const result = await Swal.fire({
    title: '¿Aprobar Pagos en Lote?',
    html: `Se verificarán y aprobarán <strong>${selectedPagos.value.length}</strong> pagos conciliados.<br>Se emitirán recibos oficiales y se generarán notas de crédito automáticas si existen saldos a favor.`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#10b981',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Sí, aprobar masivamente',
    cancelButtonText: 'Cancelar',
  });

  if (!result.isConfirmed) return;

  aprobandoLote.value = true;
  try {
    const res = await axios.post('/conciliacion/aprobar-lote', {
      payment_ids: selectedPagos.value,
    });

    if (res.data?.success) {
      Swal.fire({
        icon: 'success',
        title: '¡Conciliación Completada!',
        text: res.data.message || 'Pagos aprobados con éxito.',
      });

      emit('pagos-actualizados');
      // Remover los aprobados del resultado local
      resultado.value.movimientos = resultado.value.movimientos.filter(
        (m) => !selectedPagos.value.includes(m.pago_id)
      );
      resultado.value.coincidencias_exactas = resultado.value.movimientos.filter(
        (m) => m.match_status === 'exacto'
      ).length;
      selectedPagos.value = [];
    }
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Error en la aprobación masiva',
      text: error.response?.data?.message || 'Ocurrió un error al procesar los pagos.',
    });
  } finally {
    aprobandoLote.value = false;
  }
};

const aprobarIndividual = async (pagoId) => {
  try {
    const res = await axios.put(`/payments/${pagoId}/aprobar`);
    if (res.data?.success) {
      Swal.fire({
        icon: 'success',
        title: 'Pago Aprobado',
        text: res.data.message || 'Pago verificado con éxito.',
        timer: 2000,
        showConfirmButton: false,
      });
      emit('pagos-actualizados');
      // Remover de la lista
      resultado.value.movimientos = resultado.value.movimientos.filter((m) => m.pago_id !== pagoId);
      selectedPagos.value = selectedPagos.value.filter((id) => id !== pagoId);
    }
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'No se pudo aprobar el pago.',
    });
  }
};

const formatNumber = (val) => {
  if (val === null || val === undefined) return '0,00';
  return Number(val).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

<style scoped>
.conciliacion-container {
  animation: fadeIn 0.3s ease;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(6px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
