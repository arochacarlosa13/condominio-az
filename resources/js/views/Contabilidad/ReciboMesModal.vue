<template>
  <v-dialog v-model="dialogModel" max-width="1150" scrollable persistent>
    <v-card class="rounded-xl overflow-hidden shadow-2xl">
      <!-- Encabezado con gradiente verde esmeralda / azul profesional -->
      <v-card-title class="pa-0">
        <div class="bg-gradient-to-r from-emerald-800 via-teal-800 to-slate-900 px-6 py-4 text-white d-flex align-center justify-space-between">
          <div class="d-flex align-center gap-3">
            <v-avatar color="emerald-lighten-4" size="42" class="elevation-2">
              <v-icon color="emerald-darken-3" size="24">mdi-receipt-text-plus</v-icon>
            </v-avatar>
            <div>
              <div class="text-h6 font-weight-bold leading-tight">Preparar y Emitir Recibo del Mes</div>
              <div class="text-caption text-emerald-200">
                Asistente guiado • Distribución por alícuotas • 10% Fondo de Reserva reglamentario
              </div>
            </div>
          </div>
          <v-btn icon="mdi-close" variant="text" color="white" size="small" @click="cerrar" />
        </div>

        <!-- Barra de Progreso de Pasos (Wizard Tabs) -->
        <div class="bg-slate-100 border-b border-slate-200 px-6 py-2 d-flex align-center justify-space-between flex-wrap gap-2">
          <div class="d-flex align-center gap-1 sm:gap-4">
            <!-- Paso 1 -->
            <button
              type="button"
              class="d-flex align-center gap-2 py-1 px-3 rounded-lg text-caption font-weight-bold transition-all"
              :class="pasoActual === 1 ? 'bg-emerald-700 text-white shadow-sm' : (pasoMaximoAlcanzado >= 1 ? 'text-emerald-900 hover:bg-emerald-100' : 'text-slate-400 cursor-not-allowed')"
              @click="irAlPaso(1)"
            >
              <v-icon size="16">{{ form.periodo ? 'mdi-check-circle' : 'mdi-numeric-1-circle' }}</v-icon>
              <span>1. Período y Condiciones</span>
            </button>

            <v-icon size="14" color="slate-400">mdi-chevron-right</v-icon>

            <!-- Paso 2 -->
            <button
              type="button"
              class="d-flex align-center gap-2 py-1 px-3 rounded-lg text-caption font-weight-bold transition-all"
              :class="pasoActual === 2 ? 'bg-emerald-700 text-white shadow-sm' : (pasoMaximoAlcanzado >= 2 ? 'text-emerald-900 hover:bg-emerald-100' : 'text-slate-400 cursor-not-allowed')"
              @click="irAlPaso(2)"
            >
              <v-icon size="16">{{ conceptosSeleccionadosCount > 0 ? 'mdi-check-circle' : 'mdi-numeric-2-circle' }}</v-icon>
              <span>2. Presupuesto y Gastos ({{ conceptosSeleccionadosCount }})</span>
            </button>

            <v-icon size="14" color="slate-400">mdi-chevron-right</v-icon>

            <!-- Paso 3 -->
            <button
              type="button"
              class="d-flex align-center gap-2 py-1 px-3 rounded-lg text-caption font-weight-bold transition-all"
              :class="pasoActual === 3 ? 'bg-emerald-700 text-white shadow-sm' : (pasoMaximoAlcanzado >= 3 ? 'text-emerald-900 hover:bg-emerald-100' : 'text-slate-400 cursor-not-allowed')"
              @click="irAlPaso(3)"
            >
              <v-icon size="16">mdi-numeric-3-circle</v-icon>
              <span>3. Distribución y Emisión</span>
            </button>
          </div>

          <!-- Indicador de Tasa Activa fija en cabecera -->
          <div class="d-flex align-center gap-2 bg-white px-3 py-1 rounded-full border border-slate-200">
            <v-icon size="14" color="emerald-darken-2">mdi-lock-outline</v-icon>
            <span class="text-caption text-slate-600">Tasa BCV:</span>
            <strong class="text-caption font-mono text-emerald-900 font-weight-bold">
              Bs. {{ Number(tasaOficialActiva).toFixed(2) }}
            </strong>
          </div>
        </div>
      </v-card-title>

      <!-- Contenido de los Pasos -->
      <v-card-text class="pa-6 bg-slate-50" style="max-height: 72vh;">

        <!-- ============================================================ -->
        <!-- PASO 1: PERÍODO Y CONDICIONES GENERALES                      -->
        <!-- ============================================================ -->
        <div v-show="pasoActual === 1">
          <div class="mb-4">
            <div class="text-subtitle-1 font-weight-bold text-slate-900 d-flex align-center gap-2">
              <v-icon color="emerald">mdi-calendar-check</v-icon>
              Paso 1: Período a Facturar y Condiciones Cambiarias
            </div>
            <div class="text-caption text-slate-500">
              Establece el mes contable y los plazos de vencimiento. La tasa oficial se toma automáticamente de la configuración del sistema.
            </div>
          </div>

          <v-card class="p-5 rounded-xl border border-slate-200 bg-white mb-5 shadow-sm" elevation="0">
            <v-row>
              <!-- Período del Mes -->
              <v-col cols="12" md="6">
                <label class="text-caption font-weight-bold text-slate-700 mb-1 d-block text-uppercase">
                  Período a Emitir *
                </label>
                <v-text-field
                  v-model="form.periodo"
                  placeholder="Ej: Septiembre del 2026"
                  variant="outlined"
                  density="comfortable"
                  color="emerald"
                  prepend-inner-icon="mdi-calendar-month"
                  hide-details="auto"
                  class="font-weight-medium"
                  :rules="[v => !!v?.trim() || 'El período es obligatorio']"
                />
                <span class="text-caption text-slate-400 mt-1 d-block">
                  Ejemplos: <em>Septiembre del 2026</em>, <em>Octubre 2026</em>, <em>2026-09</em>.
                </span>
              </v-col>

              <!-- Plazo para Vencimiento -->
              <v-col cols="12" md="6">
                <label class="text-caption font-weight-bold text-slate-700 mb-1 d-block text-uppercase">
                  Plazo de Pago (Días Continuos)
                </label>
                <div class="d-flex align-center gap-2 mb-2">
                  <v-btn
                    size="x-small"
                    :variant="form.dias_vencimiento === 5 ? 'flat' : 'tonal'"
                    color="emerald"
                    @click="form.dias_vencimiento = 5"
                  >
                    5 días (Legal)
                  </v-btn>
                  <v-btn
                    size="x-small"
                    :variant="form.dias_vencimiento === 10 ? 'flat' : 'tonal'"
                    color="emerald"
                    @click="form.dias_vencimiento = 10"
                  >
                    10 días
                  </v-btn>
                  <v-btn
                    size="x-small"
                    :variant="form.dias_vencimiento === 15 ? 'flat' : 'tonal'"
                    color="emerald"
                    @click="form.dias_vencimiento = 15"
                  >
                    15 días
                  </v-btn>
                </div>
                <div class="d-flex align-center gap-2">
                  <v-text-field
                    v-model.number="form.dias_vencimiento"
                    type="number"
                    min="1"
                    max="60"
                    density="compact"
                    variant="outlined"
                    color="emerald"
                    hide-details
                    style="max-width: 110px;"
                  />
                  <span class="text-caption text-slate-600 font-weight-medium">
                    días continuos desde la fecha de emisión
                  </span>
                </div>
              </v-col>
            </v-row>
          </v-card>

          <!-- Banners de Garantía Cambiaria y Reserva -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl d-flex align-start gap-3">
              <v-icon color="emerald-darken-2" class="mt-1">mdi-lock-check</v-icon>
              <div>
                <div class="text-caption font-weight-bold text-emerald-900">Tasa Oficial Central BCV</div>
                <div class="text-caption text-emerald-700">
                  Bloqueada en <strong>Bs. {{ Number(tasaOficialActiva).toFixed(2) }} / USD</strong>. Protegida por el Master para asegurar concordancia legal.
                </div>
              </div>
            </div>

            <div class="p-3 bg-indigo-50 border border-indigo-200 rounded-xl d-flex align-start gap-3">
              <v-icon color="indigo-darken-2" class="mt-1">mdi-scale-balance</v-icon>
              <div>
                <div class="text-caption font-weight-bold text-indigo-900">Fondo de Reserva Reglamentario (10%)</div>
                <div class="text-caption text-indigo-700">
                  El sistema calculará automáticamente el 10% del total de gastos comunes del mes como fondo de reserva de ley.
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ============================================================ -->
        <!-- PASO 2: PRESUPUESTO Y CONCEPTOS DEL MES                     -->
        <!-- ============================================================ -->
        <div v-show="pasoActual === 2">
          <div class="d-flex align-center justify-space-between mb-3 flex-wrap gap-2">
            <div>
              <div class="text-subtitle-1 font-weight-bold text-slate-900 d-flex align-center gap-2">
                <v-icon color="emerald">mdi-format-list-numbered</v-icon>
                Paso 2: Presupuesto y Gastos del Mes
              </div>
              <div class="text-caption text-slate-500">
                Selecciona o importa los gastos a cobrar este mes. El 10% de Fondo de Reserva se calcula en vivo.
              </div>
            </div>

            <div class="d-flex align-center gap-2">
              <v-btn
                color="indigo-darken-1"
                variant="flat"
                size="small"
                prepend-icon="mdi-download"
                :loading="importandoGastos"
                @click="importarGastosEjecutados"
              >
                📥 Importar Gastos Ejecutados del Mes
              </v-btn>

              <v-btn
                color="emerald-darken-2"
                variant="flat"
                size="small"
                prepend-icon="mdi-plus-circle"
                @click="agregarNuevoConcepto"
              >
                Agregar Gasto
              </v-btn>
            </div>
          </div>

          <!-- Tabla limpia de conceptos -->
          <v-card class="rounded-xl border border-slate-200 overflow-hidden bg-white mb-4 shadow-sm" elevation="0">
            <v-table density="comfortable" hover style="max-height: 320px;">
              <thead>
                <tr class="bg-slate-100 text-slate-700">
                  <th style="width: 5%;" class="text-center font-weight-bold">
                    <v-checkbox-btn
                      :model-value="todosSeleccionados"
                      density="compact"
                      color="emerald"
                      @update:model-value="toggleSeleccionarTodos"
                    />
                  </th>
                  <th style="width: 16%;" class="font-weight-bold">Alícuota</th>
                  <th style="width: 45%;" class="font-weight-bold">Concepto de Gasto / Descripción</th>
                  <th style="width: 14%;" class="font-weight-bold">Categoría</th>
                  <th style="width: 14%;" class="text-right font-weight-bold">Monto ($ USD)</th>
                  <th style="width: 6%;" class="text-center font-weight-bold"></th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(item, idx) in conceptos"
                  :key="item.id || idx"
                  :class="{'opacity-50 bg-slate-50': !item.incluir, 'hover:bg-slate-50': item.incluir}"
                >
                  <td class="text-center pa-1">
                    <v-checkbox-btn
                      v-model="item.incluir"
                      density="compact"
                      color="emerald"
                    />
                  </td>
                  <td class="pa-1">
                    <v-select
                      v-model="item.ali"
                      :items="alicuotasCatalogo"
                      item-title="title"
                      item-value="value"
                      variant="plain"
                      density="compact"
                      hide-details
                      class="text-caption font-weight-bold"
                    />
                  </td>
                  <td class="pa-1">
                    <v-text-field
                      v-model="item.concepto"
                      placeholder="Ej: Mantenimiento de Ascensores"
                      variant="plain"
                      density="compact"
                      hide-details
                      class="font-weight-medium text-slate-800"
                    />
                    <v-text-field
                      v-if="item.incluir"
                      v-model="item.comentario_adicional"
                      placeholder="Nota opcional (ej: Cuota 2 de 3, Repuesto X)"
                      variant="plain"
                      density="compact"
                      hide-details
                      class="text-caption text-slate-500 mt-n1"
                    />
                  </td>
                  <td class="pa-1">
                    <v-chip size="x-small" color="secondary" variant="tonal" class="text-capitalize">
                      {{ item.categoria || 'mantenimiento' }}
                    </v-chip>
                  </td>
                  <td class="pa-1 text-right">
                    <v-text-field
                      v-model.number="item.monto_base"
                      type="number"
                      step="0.01"
                      min="0"
                      prefix="$"
                      variant="plain"
                      density="compact"
                      hide-details
                      class="font-mono text-right font-weight-bold text-emerald-900"
                    />
                  </td>
                  <td class="text-center pa-1">
                    <div class="d-flex align-center justify-center">
                      <v-btn
                        icon="mdi-content-copy"
                        variant="text"
                        color="indigo"
                        size="x-small"
                        title="Duplicar ítem"
                        @click="duplicarConcepto(item, idx)"
                      />
                      <v-btn
                        icon="mdi-trash-can-outline"
                        variant="text"
                        color="red-lighten-1"
                        size="x-small"
                        title="Quitar ítem"
                        @click="eliminarConcepto(idx)"
                      />
                    </div>
                  </td>
                </tr>
              </tbody>
            </v-table>
          </v-card>

          <!-- Tarjetas KPI de Totales en Tiempo Real -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <v-card class="p-3 bg-white border border-slate-200 rounded-xl" elevation="0">
              <div class="text-caption text-slate-500 font-weight-bold text-uppercase">1. Gastos Comunes Mes</div>
              <div class="text-h6 font-weight-black text-slate-900 font-mono mt-1">
                ${{ Number(subtotalGastosComunesUsd).toFixed(2) }} USD
              </div>
              <div class="text-caption text-slate-400">
                Bs. {{ Number(subtotalGastosComunesUsd * tasaOficialActiva).toFixed(2) }}
              </div>
            </v-card>

            <v-card class="p-3 bg-indigo-50 border border-indigo-200 rounded-xl" elevation="0">
              <div class="text-caption text-indigo-800 font-weight-bold text-uppercase">2. Fondo Reserva (10%)</div>
              <div class="text-h6 font-weight-black text-indigo-950 font-mono mt-1">
                +${{ Number(montoFondoReservaUsd).toFixed(2) }} USD
              </div>
              <div class="text-caption text-indigo-700">
                10% reglamentario de ley
              </div>
            </v-card>

            <v-card class="p-3 bg-emerald-50 border-2 border-emerald-400 rounded-xl shadow-sm" elevation="0">
              <div class="text-caption text-emerald-800 font-weight-bold text-uppercase">3. Presupuesto Total Edificio</div>
              <div class="text-h6 font-weight-black text-emerald-950 font-mono mt-1">
                ${{ Number(totalPresupuestoConReservaUsd).toFixed(2) }} USD
              </div>
              <div class="text-caption text-emerald-700 font-weight-medium">
                Bs. {{ Number(totalPresupuestoConReservaUsd * tasaOficialActiva).toFixed(2) }}
              </div>
            </v-card>
          </div>
        </div>

        <!-- ============================================================ -->
        <!-- PASO 3: DISTRIBUCIÓN POR ALÍCUOTA Y PREVISUALIZACIÓN        -->
        <!-- ============================================================ -->
        <div v-show="pasoActual === 3">
          <div class="d-flex align-center justify-space-between mb-3 flex-wrap gap-2">
            <div>
              <div class="text-subtitle-1 font-weight-bold text-slate-900 d-flex align-center gap-2">
                <v-icon color="emerald">mdi-scale-balance</v-icon>
                Paso 3: Distribución por Alícuota y Emisión
              </div>
              <div class="text-caption text-slate-500">
                Revisa la cuota calculada para cada apartamento según su alícuota legal registrada en documento.
              </div>
            </div>

            <!-- Buscador rápido -->
            <v-text-field
              v-model="filtroBusqueda"
              placeholder="Buscar por número o propietario..."
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              density="compact"
              color="emerald"
              hide-details
              style="max-width: 280px;"
            />
          </div>

          <!-- Resumen Banner Superior -->
          <div class="p-3 bg-emerald-50 border border-emerald-300 text-emerald-900 rounded-xl mb-3 text-caption d-flex align-center justify-space-between flex-wrap gap-2">
            <div class="d-flex align-center gap-2">
              <v-icon color="emerald-darken-2" size="18">mdi-check-circle</v-icon>
              <span>
                Presupuesto a Facturar: <strong>${{ totalPresupuestoConReservaUsd.toFixed(2) }} USD</strong> (Gastos: ${{ subtotalGastosComunesUsd.toFixed(2) }} + 10% Reserva: ${{ montoFondoReservaUsd.toFixed(2) }}).
              </span>
            </div>
            <div>
              Total Inmuebles: <strong>{{ listaApartamentos.length }}</strong>
            </div>
          </div>

          <!-- Tabla de Apartamentos y Cuotas Estimadas -->
          <v-card class="rounded-xl border border-slate-200 overflow-hidden bg-white shadow-sm" elevation="0">
            <v-table density="compact" style="max-height: 300px;">
              <thead>
                <tr class="bg-slate-100 text-slate-700">
                  <th style="width: 15%;" class="font-weight-bold">Inmueble</th>
                  <th style="width: 35%;" class="font-weight-bold">Propietario</th>
                  <th style="width: 15%;" class="text-center font-weight-bold">Alícuota %</th>
                  <th style="width: 18%;" class="text-right font-weight-bold">Cuota Mensual ($)</th>
                  <th style="width: 17%;" class="text-right font-weight-bold">Total en Bs.</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="apto in apartamentosFiltrados"
                  :key="apto.id"
                  class="hover:bg-slate-50"
                >
                  <td class="font-weight-bold">
                    Apto {{ apto.numero }}
                    <span class="text-caption text-slate-400 d-block">Piso {{ apto.piso || 'PB' }}</span>
                  </td>
                  <td>
                    <div class="text-truncate font-weight-medium text-slate-800" style="max-width: 250px;" :title="apto.propietarios?.[0]?.nombre_completo">
                      {{ apto.propietarios?.[0]?.nombre_completo || 'Sin Propietario' }}
                    </div>
                    <span class="text-caption text-slate-500">{{ apto.propietarios?.[0]?.cedula || 'N/A' }}</span>
                  </td>
                  <td class="text-center font-mono font-weight-bold text-slate-700">
                    {{ Number(apto.alicuota_general || 0).toFixed(4) }}%
                  </td>
                  <td class="text-right font-mono font-weight-bold text-emerald-900">
                    ${{ Number(calcularCuotaApto(apto)).toFixed(2) }} USD
                  </td>
                  <td class="text-right font-mono font-weight-bold text-slate-700">
                    Bs. {{ Number(calcularCuotaApto(apto) * tasaOficialActiva).toFixed(2) }}
                  </td>
                </tr>
              </tbody>
            </v-table>
          </v-card>
        </div>

      </v-card-text>

      <v-divider />

      <!-- Barra de Navegación y Botones de Acción -->
      <v-card-actions class="pa-4 bg-white d-flex align-center justify-space-between flex-wrap gap-2">
        <div>
          <v-btn
            v-if="pasoActual > 1"
            variant="tonal"
            color="slate-700"
            prepend-icon="mdi-arrow-left"
            @click="pasoActual--"
          >
            Atrás
          </v-btn>
          <v-btn
            v-else
            variant="text"
            color="slate-600"
            @click="cerrar"
          >
            Cancelar
          </v-btn>
        </div>

        <div class="d-flex align-center gap-2">
          <!-- Siguiente Paso 1 -->
          <v-btn
            v-if="pasoActual === 1"
            color="emerald-darken-2"
            variant="flat"
            append-icon="mdi-arrow-right"
            :disabled="!form.periodo?.trim()"
            @click="avanzarAlPaso2"
          >
            Continuar al Presupuesto
          </v-btn>

          <!-- Siguiente Paso 2 -->
          <v-btn
            v-else-if="pasoActual === 2"
            color="emerald-darken-2"
            variant="flat"
            append-icon="mdi-arrow-right"
            :disabled="conceptosSeleccionadosCount <= 0 || subtotalGastosComunesUsd <= 0"
            @click="avanzarAlPaso3"
          >
            Revisar Distribución por Alícuota
          </v-btn>

          <!-- Acciones de Emisión Final (Paso 3) -->
          <template v-else-if="pasoActual === 3">
            <v-btn
              color="emerald-lighten-1"
              variant="outlined"
              prepend-icon="mdi-file-document-edit-outline"
              :loading="emitiendo"
              @click="guardarEmision(false)"
            >
              Guardar en Borrador (Revisar PDF)
            </v-btn>

            <v-btn
              color="emerald-darken-2"
              variant="flat"
              prepend-icon="mdi-shield-check"
              class="elevation-2"
              :loading="emitiendo"
              @click="confirmarCertificacion"
            >
              Guardar y Certificar Ahora
            </v-btn>
          </template>
        </div>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  condominioId: {
    type: [Number, String],
    default: null,
  },
  tasaCambioCentral: {
    type: [Number, String],
    default: 36.50,
  },
  conceptosBase: {
    type: Array,
    default: () => [],
  },
  alicuotasDisponibles: {
    type: Array,
    default: () => [],
  },
  initialData: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['update:modelValue', 'guardado']);

// Navegación por Pasos
const pasoActual = ref(1);
const pasoMaximoAlcanzado = ref(1);
const filtroBusqueda = ref('');

const dialogModel = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val),
});

const tasaOficialActiva = computed(() => {
  return Number(props.tasaCambioCentral || 36.50);
});

const form = ref({
  periodo: '',
  dias_vencimiento: 5,
  descripcion: '',
});

const conceptos = ref([]);
const listaApartamentos = ref([]);
const emitiendo = ref(false);
const importandoGastos = ref(false);

const alicuotasCatalogo = computed(() => {
  if (props.alicuotasDisponibles && props.alicuotasDisponibles.length > 0) {
    return props.alicuotasDisponibles.map(a => ({
      title: `Ali ${a.numero} - ${a.nombre}`,
      value: String(a.id || a.numero),
    }));
  }
  return [
    { title: 'Ali 1 - Gastos Generales (Edificio)', value: '1' },
    { title: 'Ali 2 - Torre / Sector', value: '2' },
    { title: 'Ali 3 - Estacionamiento / Garaje', value: '3' },
    { title: 'Ali 4 - Maleteros / Depósitos', value: '4' },
    { title: 'Ali 5 - Locales Comerciales', value: '5' },
  ];
});

// Cálculos del presupuesto
const conceptosSeleccionados = computed(() => {
  return conceptos.value.filter(c => c.incluir && c.concepto?.trim() !== '');
});

const conceptosSeleccionadosCount = computed(() => conceptosSeleccionados.value.length);

const todosSeleccionados = computed(() => {
  return conceptos.value.length > 0 && conceptos.value.every(c => c.incluir);
});

const subtotalGastosComunesUsd = computed(() => {
  return Number(conceptosSeleccionados.value.reduce((acc, c) => acc + (Number(c.monto_base) || 0), 0).toFixed(2));
});

const montoFondoReservaUsd = computed(() => {
  return Number((subtotalGastosComunesUsd.value * 0.10).toFixed(2));
});

const totalPresupuestoConReservaUsd = computed(() => {
  return Number((subtotalGastosComunesUsd.value + montoFondoReservaUsd.value).toFixed(2));
});

// Lista filtrada de apartamentos
const apartamentosFiltrados = computed(() => {
  const q = filtroBusqueda.value.toLowerCase().trim();
  if (!q) return listaApartamentos.value;
  return listaApartamentos.value.filter(a => {
    const num = (a.numero || '').toLowerCase();
    const prop = (a.propietarios?.[0]?.nombre_completo || '').toLowerCase();
    const ced = (a.propietarios?.[0]?.cedula || '').toLowerCase();
    return num.includes(q) || prop.includes(q) || ced.includes(q);
  });
});

watch(() => props.modelValue, async (val) => {
  if (val) {
    inicializarFormulario();
    await cargarApartamentos();
  }
});

function inicializarFormulario() {
  pasoActual.value = 1;
  pasoMaximoAlcanzado.value = 1;
  filtroBusqueda.value = '';

  // Si estamos editando un borrador previo
  if (props.initialData) {
    if (props.initialData.periodo) form.value.periodo = props.initialData.periodo;
    if (props.initialData.dias_vencimiento) form.value.dias_vencimiento = props.initialData.dias_vencimiento;
    if (props.initialData.descripcion) form.value.descripcion = props.initialData.descripcion;

    if (props.initialData.detalles_gastos && props.initialData.detalles_gastos.length > 0) {
      conceptos.value = props.initialData.detalles_gastos.map((dg, idx) => {
        let conceptoBase = dg.concepto || '';
        let comentarioAdicional = '';
        const matchParentesis = conceptoBase.match(/^(.*?)\s*\(([^)]+)\)$/);
        if (matchParentesis) {
          conceptoBase = matchParentesis[1].trim();
          comentarioAdicional = matchParentesis[2].trim();
        }
        return {
          id: dg.id || `draft_${idx}`,
          ali: String(dg.condominio_alicuota_id || dg.ali || '1'),
          condominio_alicuota_id: String(dg.condominio_alicuota_id || dg.ali || '1'),
          concepto: conceptoBase,
          comentario_adicional: comentarioAdicional,
          categoria: dg.categoria || 'mantenimiento',
          monto_base: Number(dg.monto || dg.monto_base || 0),
          incluir: true,
        };
      });
      return;
    }
  }

  // Sugerir nombre del mes actual en español
  const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
  const now = new Date();
  form.value.periodo = `${meses[now.getMonth()]} del ${now.getFullYear()}`;
  form.value.dias_vencimiento = 5;
  form.value.descripcion = '';

  // Cargar conceptos base del catálogo si existen
  if (props.conceptosBase && props.conceptosBase.length > 0) {
    conceptos.value = props.conceptosBase
      .filter(c => c.tipo !== 'no_comun')
      .map(c => ({
        id: c.id,
        ali: String(c.ali || '1'),
        condominio_alicuota_id: String(c.ali || '1'),
        concepto: c.concepto,
        comentario_adicional: '',
        categoria: c.categoria || 'mantenimiento',
        monto_base: Number(c.monto_base || 0),
        incluir: true,
      }));
  } else {
    conceptos.value = [
      { id: '1', ali: '1', concepto: 'Mantenimiento General', comentario_adicional: '', categoria: 'mantenimiento', monto_base: 0, incluir: true }
    ];
  }
}

function irAlPaso(paso) {
  if (paso <= pasoMaximoAlcanzado.value) {
    pasoActual.value = paso;
  }
}

function avanzarAlPaso2() {
  if (!form.value.periodo?.trim()) {
    Swal.fire({
      icon: 'warning',
      title: 'Período Obligatorio',
      text: 'Indica el período a facturar (ej. Septiembre del 2026).',
    });
    return;
  }
  pasoActual.value = 2;
  if (pasoMaximoAlcanzado.value < 2) pasoMaximoAlcanzado.value = 2;
}

function avanzarAlPaso3() {
  if (conceptosSeleccionadosCount.value <= 0 || subtotalGastosComunesUsd.value <= 0) {
    Swal.fire({
      icon: 'warning',
      title: 'Presupuesto Requerido',
      text: 'Debes tener al menos un concepto seleccionado con monto superior a cero.',
    });
    return;
  }
  pasoActual.value = 3;
  if (pasoMaximoAlcanzado.value < 3) pasoMaximoAlcanzado.value = 3;
}

function toggleSeleccionarTodos(val) {
  conceptos.value.forEach(c => { c.incluir = val; });
}

function agregarNuevoConcepto() {
  conceptos.value.push({
    id: `custom_${Date.now()}`,
    ali: '1',
    concepto: '',
    comentario_adicional: '',
    categoria: 'mantenimiento',
    monto_base: 0,
    incluir: true,
  });
}

function duplicarConcepto(item, idx) {
  const nuevo = {
    ...item,
    id: `clone_${Date.now()}`,
    comentario_adicional: '',
    incluir: true,
  };
  conceptos.value.splice(idx + 1, 0, nuevo);
}

function eliminarConcepto(idx) {
  if (conceptos.value.length > 1) {
    conceptos.value.splice(idx, 1);
  }
}

async function importarGastosEjecutados() {
  importandoGastos.value = true;
  try {
    const res = await axios.get('/expenses?all=true');
    const expenses = res.data?.data || [];
    if (!expenses.length) {
      Swal.fire({
        icon: 'info',
        title: 'Sin Gastos Registrados',
        text: 'No hay facturas o egresos de proveedores registrados en Cuentas por Pagar.',
      });
      return;
    }

    let agregados = 0;
    expenses.forEach(exp => {
      // Evitar duplicados si ya existe un concepto con la misma descripción
      const existe = conceptos.value.some(c => c.concepto.toLowerCase().trim() === exp.descripcion.toLowerCase().trim());
      if (!existe && Number(exp.monto_usd) > 0) {
        conceptos.value.push({
          id: `exp_${exp.id}`,
          ali: exp.ali || '1',
          concepto: exp.descripcion,
          comentario_adicional: exp.proveedor ? `Proveedor: ${exp.proveedor}` : '',
          categoria: exp.categoria || 'servicios',
          monto_base: Number(exp.monto_usd),
          incluir: true,
        });
        agregados++;
      }
    });

    Swal.fire({
      icon: 'success',
      title: 'Gastos Importados',
      text: `Se agregaron ${agregados} facturas ejecutadas al presupuesto del mes.`,
      timer: 2000,
      showConfirmButton: false,
    });
  } catch (e) {
    console.error(e);
    Swal.fire({
      icon: 'error',
      title: 'Error al importar',
      text: 'No se pudieron consultar los gastos ejecutados.',
    });
  } finally {
    importandoGastos.value = false;
  }
}

function calcularCuotaApto(apto) {
  const alicuota = Number(apto.alicuota_general || 0) / 100;
  return Number((totalPresupuestoConReservaUsd.value * alicuota).toFixed(2));
}

async function cargarApartamentos() {
  try {
    const res = await axios.get('/apartamentos');
    const raw = res.data?.data?.data || res.data?.data || [];
    listaApartamentos.value = raw.map(a => {
      // Buscar alícuota 1 o general
      let ali1 = 0;
      if (a.alicuotas_detalle && Array.isArray(a.alicuotas_detalle)) {
        const found = a.alicuotas_detalle.find(ali => ali.numero === 1 || ali.id === 1);
        if (found) ali1 = found.porcentaje;
      }
      if (!ali1 && a.alicuota) ali1 = a.alicuota;
      if (!ali1) ali1 = 100 / (raw.length || 1);

      return {
        ...a,
        alicuota_general: Number(ali1),
      };
    });
  } catch (e) {
    console.error(e);
  }
}

async function confirmarCertificacion() {
  const result = await Swal.fire({
    title: '¿Confirmar Emisión y Certificación Inmediata?',
    html: `Se emitirán los recibos para el período <strong>${form.value.periodo}</strong> y quedarán <strong>bloqueados permanentemente</strong>.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#059669',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Sí, Generar y Certificar',
    cancelButtonText: 'Cancelar',
  });

  if (result.isConfirmed) {
    guardarEmision(true);
  }
}

async function guardarEmision(certificarInmediatamente) {
  const seleccionados = conceptosSeleccionados.value;
  if (!seleccionados.length) {
    Swal.fire({
      icon: 'warning',
      title: 'Conceptos requeridos',
      text: 'Debes seleccionar al menos un concepto de gasto para emitir los recibos del mes.',
    });
    return;
  }

  emitiendo.value = true;
  let res = null;
  try {
    const payload = {
      periodo: form.value.periodo.trim(),
      tasa_cambio: Number(tasaOficialActiva.value),
      dias_vencimiento: Number(form.value.dias_vencimiento) || 5,
      descripcion: form.value.descripcion || `Recibo de Cobro Ordinario - ${form.value.periodo}`,
      certificar_inmediatamente: certificarInmediatamente,
      conceptos: seleccionados.map(c => {
        let conceptoFinal = c.concepto.trim();
        let extra = (c.comentario_adicional || '').trim();
        if (extra && !conceptoFinal.includes(`(${extra})`)) {
          conceptoFinal = `${conceptoFinal} (${extra})`;
        }
        return {
          condominio_alicuota_id: c.ali || '1',
          ali: c.ali || '1',
          concepto: conceptoFinal,
          comentario_adicional: extra,
          monto: Number(c.monto_base) || 0,
        };
      }),
    };

    res = await axios.post('/invoices/generar-masivo', payload);
  } catch (error) {
    let msg = error.response?.data?.message;
    if (!msg && error.response?.data?.errors) {
      const firstError = Object.values(error.response.data.errors)[0];
      msg = Array.isArray(firstError) ? firstError[0] : String(firstError);
    }
    Swal.fire({
      icon: 'error',
      title: 'Error al Generar',
      text: msg || error.message || 'Ocurrió un problema al emitir los recibos del mes.',
    });
    emitiendo.value = false;
    return;
  }

  emitiendo.value = false;

  if (res?.data?.success) {
    await Swal.fire({
      icon: 'success',
      title: certificarInmediatamente ? '¡Recibos Certificados y Bloqueados!' : '¡Borrador Generado!',
      text: res.data.message || 'Los recibos del mes han sido emitidos exitosamente.',
    });
    cerrar();
    try {
      emit('guardado', res.data.data);
    } catch (e) {
      console.warn(e);
    }
  }
}

function cerrar() {
  dialogModel.value = false;
}
</script>

<style scoped>
:deep(.swal2-container) {
  z-index: 99999 !important;
}
</style>
