<template>
  <v-dialog v-model="dialogModel" max-width="1100" scrollable persistent>
    <v-card class="rounded-xl overflow-hidden shadow-2xl">
      <!-- Encabezado con estilo profesional púrpura -->
      <v-card-title class="pa-0">
        <div class="bg-gradient-to-r from-purple-800 via-indigo-800 to-purple-900 px-6 py-4 text-white d-flex align-center justify-space-between">
          <div class="d-flex align-center gap-3">
            <v-avatar color="purple-lighten-4" size="42" class="elevation-2">
              <v-icon color="purple-darken-3" size="24">mdi-lightning-bolt</v-icon>
            </v-avatar>
            <div>
              <div class="text-h6 font-weight-bold leading-tight">Asistente de Cuota Extraordinaria</div>
              <div class="text-caption text-purple-200">
                Paso a paso • Distribución a partes iguales • 0% Fondo de Reserva
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
              :class="pasoActual === 1 ? 'bg-purple-700 text-white shadow-sm' : (pasoMaximoAlcanzado >= 1 ? 'text-purple-900 hover:bg-purple-100' : 'text-slate-400 cursor-not-allowed')"
              @click="irAlPaso(1)"
            >
              <v-icon size="16">{{ form.titulo_proyecto ? 'mdi-check-circle' : 'mdi-numeric-1-circle' }}</v-icon>
              <span>1. Proyecto y Plazos</span>
            </button>

            <v-icon size="14" color="slate-400">mdi-chevron-right</v-icon>

            <!-- Paso 2 -->
            <button
              type="button"
              class="d-flex align-center gap-2 py-1 px-3 rounded-lg text-caption font-weight-bold transition-all"
              :class="pasoActual === 2 ? 'bg-purple-700 text-white shadow-sm' : (pasoMaximoAlcanzado >= 2 ? 'text-purple-900 hover:bg-purple-100' : 'text-slate-400 cursor-not-allowed')"
              @click="irAlPaso(2)"
            >
              <v-icon size="16">{{ totalPresupuestoUsd > 0 ? 'mdi-check-circle' : 'mdi-numeric-2-circle' }}</v-icon>
              <span>2. Presupuesto del Gasto</span>
            </button>

            <v-icon size="14" color="slate-400">mdi-chevron-right</v-icon>

            <!-- Paso 3 -->
            <button
              type="button"
              class="d-flex align-center gap-2 py-1 px-3 rounded-lg text-caption font-weight-bold transition-all"
              :class="pasoActual === 3 ? 'bg-purple-700 text-white shadow-sm' : (pasoMaximoAlcanzado >= 3 ? 'text-purple-900 hover:bg-purple-100' : 'text-slate-400 cursor-not-allowed')"
              @click="irAlPaso(3)"
            >
              <v-icon size="16">mdi-numeric-3-circle</v-icon>
              <span>3. Distribución y Emisión</span>
            </button>
          </div>

          <!-- Indicador de Tasa Activa fija en cabecera -->
          <div class="d-flex align-center gap-2 bg-white px-3 py-1 rounded-full border border-slate-200">
            <v-icon size="14" color="indigo-darken-2">mdi-lock-outline</v-icon>
            <span class="text-caption text-slate-600">Tasa BCV:</span>
            <strong class="text-caption font-mono text-indigo-900 font-weight-bold">
              Bs. {{ Number(tasaOficialActiva).toFixed(2) }}
            </strong>
          </div>
        </div>
      </v-card-title>

      <!-- Cuerpo con V-Window para navegación limpia -->
      <v-card-text class="pa-6 bg-slate-50" style="max-height: 72vh;">
        
        <!-- ============================================================ -->
        <!-- PASO 1: DATOS DEL PROYECTO Y CONDICIONES GENERALES           -->
        <!-- ============================================================ -->
        <div v-show="pasoActual === 1">
          <div class="mb-4">
            <div class="text-subtitle-1 font-weight-bold text-slate-900 d-flex align-center gap-2">
              <v-icon color="purple">mdi-clipboard-text-outline</v-icon>
              Paso 1: Información del Proyecto Extraordinario
            </div>
            <div class="text-caption text-slate-500">
              Indica el motivo o trabajo a realizar. Los códigos y tasas se gestionan automáticamente.
            </div>
          </div>

          <v-card class="p-5 rounded-xl border border-slate-200 bg-white mb-5 shadow-sm" elevation="0">
            <!-- Título del proyecto destacado -->
            <div class="mb-4">
              <label class="text-caption font-weight-bold text-slate-700 mb-1 d-block text-uppercase">
                Nombre o Motivo del Proyecto Extraordinario *
              </label>
              <v-text-field
                v-model="form.titulo_proyecto"
                placeholder="Ej: Reparación Mayor Bomba Hidroneumática #2"
                variant="outlined"
                density="comfortable"
                color="purple"
                prepend-inner-icon="mdi-tools"
                hide-details="auto"
                class="font-weight-medium"
                autofocus
                :rules="[v => !!v?.trim() || 'El nombre del proyecto es obligatorio']"
              />
              <span class="text-caption text-slate-400 mt-1 d-block">
                Este nombre aparecerá como título oficial en el recibo de cobro de cada propietario.
              </span>
            </div>

            <v-row class="mt-2">
              <!-- Identificador de Cuota (Automático) -->
              <v-col cols="12" sm="6">
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 h-100">
                  <div class="d-flex align-center justify-space-between mb-1">
                    <span class="text-caption font-weight-bold text-slate-600 text-uppercase">
                      Identificador de Cuota:
                    </span>
                    <v-chip size="x-small" color="purple" variant="flat" class="font-weight-bold">
                      AUTOMÁTICO
                    </v-chip>
                  </div>
                  <div class="text-h6 font-mono font-weight-bold text-purple-900">
                    {{ form.periodo || 'EXT-AUTO' }}
                  </div>
                  <div class="text-caption text-slate-400 mt-1">
                    🔒 Correlativo único consecutivo asignado por el sistema.
                  </div>
                </div>
              </v-col>

              <!-- Plazo para Vencimiento -->
              <v-col cols="12" sm="6">
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 h-100">
                  <span class="text-caption font-weight-bold text-slate-600 text-uppercase d-block mb-1">
                    Plazo de Pago:
                  </span>
                  <div class="d-flex align-center gap-2 mb-2">
                    <v-btn
                      size="x-small"
                      :variant="form.dias_vencimiento === 5 ? 'flat' : 'tonal'"
                      color="purple"
                      @click="form.dias_vencimiento = 5"
                    >
                      5 días
                    </v-btn>
                    <v-btn
                      size="x-small"
                      :variant="form.dias_vencimiento === 10 ? 'flat' : 'tonal'"
                      color="purple"
                      @click="form.dias_vencimiento = 10"
                    >
                      10 días
                    </v-btn>
                    <v-btn
                      size="x-small"
                      :variant="form.dias_vencimiento === 15 ? 'flat' : 'tonal'"
                      color="purple"
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
                      color="purple"
                      hide-details
                      style="max-width: 100px;"
                    />
                    <span class="text-caption text-slate-600 font-weight-medium">
                      días continuos a partir de hoy
                    </span>
                  </div>
                </div>
              </v-col>
            </v-row>
          </v-card>

          <!-- Banner Informativo de Garantía de Tasa y Fondo de Reserva -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-3 bg-indigo-50 border border-indigo-200 rounded-xl d-flex align-start gap-3">
              <v-icon color="indigo-darken-2" class="mt-1">mdi-shield-check</v-icon>
              <div>
                <div class="text-caption font-weight-bold text-indigo-900">Tasa Oficial Central BCV</div>
                <div class="text-caption text-indigo-700">
                  Bloqueada en <strong>Bs. {{ Number(tasaOficialActiva).toFixed(2) }}</strong>. Fijada por el Master para asegurar concordancia cambiaria.
                </div>
              </div>
            </div>

            <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl d-flex align-start gap-3">
              <v-icon color="emerald-darken-2" class="mt-1">mdi-check-decagram</v-icon>
              <div>
                <div class="text-caption font-weight-bold text-emerald-900">Exento de Fondo de Reserva (0%)</div>
                <div class="text-caption text-emerald-700">
                  Al ser un recibo de cuota extraordinaria, no se recarga el 10% del fondo de reserva reglamentario del mes.
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ============================================================ -->
        <!-- PASO 2: PRESUPUESTO Y CONCEPTOS DEL PROYECTO                -->
        <!-- ============================================================ -->
        <div v-show="pasoActual === 2">
          <div class="d-flex align-center justify-space-between mb-3 flex-wrap gap-2">
            <div>
              <div class="text-subtitle-1 font-weight-bold text-slate-900 d-flex align-center gap-2">
                <v-icon color="purple">mdi-format-list-numbered</v-icon>
                Paso 2: Presupuesto Desglosado del Proyecto
              </div>
              <div class="text-caption text-slate-500">
                Agrega los gastos del proyecto. La suma total se dividirá de forma equitativa entre los apartamentos.
              </div>
            </div>
            <v-btn
              color="purple-darken-2"
              variant="flat"
              size="small"
              prepend-icon="mdi-plus-circle"
              @click="agregarConcepto"
            >
              Agregar Ítem al Gasto
            </v-btn>
          </div>

          <!-- Tabla limpia de conceptos -->
          <v-card class="rounded-xl border border-slate-200 overflow-hidden bg-white mb-4 shadow-sm" elevation="0">
            <v-table density="comfortable">
              <thead>
                <tr class="bg-slate-100 text-slate-700">
                  <th style="width: 5%;" class="text-center font-weight-bold">#</th>
                  <th style="width: 65%;" class="font-weight-bold">Descripción del Concepto / Adquisición / Servicio</th>
                  <th style="width: 22%;" class="text-right font-weight-bold">Monto ($ USD)</th>
                  <th style="width: 8%;" class="text-center"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, idx) in form.conceptos" :key="idx" class="hover:bg-slate-50">
                  <td class="text-center font-weight-bold text-slate-400">{{ idx + 1 }}</td>
                  <td class="pa-2">
                    <v-text-field
                      v-model="item.concepto"
                      placeholder="Ej: Compra de Bomba Sumergible 7.5HP, Mano de obra técnica..."
                      variant="plain"
                      density="compact"
                      hide-details
                      class="font-weight-medium text-slate-800"
                    />
                  </td>
                  <td class="pa-2 text-right">
                    <v-text-field
                      v-model.number="item.monto"
                      type="number"
                      step="0.01"
                      min="0"
                      prefix="$"
                      variant="plain"
                      density="compact"
                      hide-details
                      class="font-mono text-right font-weight-bold text-purple-900"
                    />
                  </td>
                  <td class="text-center pa-2">
                    <v-btn
                      icon="mdi-trash-can-outline"
                      variant="text"
                      color="red-lighten-1"
                      size="small"
                      :disabled="form.conceptos.length <= 1"
                      title="Eliminar este ítem"
                      @click="eliminarConcepto(idx)"
                    />
                  </td>
                </tr>
              </tbody>
            </v-table>
          </v-card>

          <!-- Resumen de Totales del Presupuesto -->
          <div class="p-4 bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-200 rounded-xl d-flex align-center justify-space-between flex-wrap gap-4">
            <div>
              <div class="text-caption text-purple-700 font-weight-bold text-uppercase">
                Presupuesto Total a Repartir:
              </div>
              <div class="text-caption text-slate-500">
                Se dividirá entre los <strong>{{ aptosParticipantesCount }} apartamentos</strong> participantes en el Paso 3.
              </div>
            </div>
            <div class="text-right">
              <div class="text-h5 font-weight-black text-purple-900 font-mono">
                ${{ Number(totalPresupuestoUsd).toFixed(2) }} USD
              </div>
              <div class="text-caption font-mono text-indigo-800 font-weight-bold">
                Equivalente BCV: Bs. {{ Number(totalPresupuestoUsd * tasaOficialActiva).toFixed(2) }}
              </div>
            </div>
          </div>
        </div>

        <!-- ============================================================ -->
        <!-- PASO 3: DISTRIBUCIÓN EQUITATIVA Y MATRIZ DE COBRO          -->
        <!-- ============================================================ -->
        <div v-show="pasoActual === 3">
          <!-- 3 Tarjetas de Métricas Claras -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
            <v-card class="p-3 bg-white border border-slate-200 rounded-xl" elevation="0">
              <div class="text-caption text-slate-500 font-weight-bold text-uppercase">1. Presupuesto Total</div>
              <div class="text-h6 font-weight-black text-purple-900 font-mono mt-1">
                ${{ Number(totalPresupuestoUsd).toFixed(2) }} USD
              </div>
              <div class="text-caption text-slate-400">Gasto total del proyecto</div>
            </v-card>

            <v-card class="p-3 bg-white border border-slate-200 rounded-xl" elevation="0">
              <div class="text-caption text-slate-500 font-weight-bold text-uppercase">2. Apartamentos a Cobrar</div>
              <div class="text-h6 font-weight-black text-indigo-900 font-mono mt-1">
                {{ aptosParticipantesCount }} de {{ listaApartamentos.length }}
              </div>
              <div class="text-caption text-slate-400">
                {{ listaApartamentos.length - aptosParticipantesCount }} inmueble(s) exonerado(s)
              </div>
            </v-card>

            <v-card class="p-3 bg-purple-50 border-2 border-purple-400 rounded-xl shadow-sm" elevation="0">
              <div class="text-caption text-purple-800 font-weight-bold text-uppercase">3. Cuota por Apartamento</div>
              <div class="text-h6 font-weight-black text-purple-950 font-mono mt-1">
                ${{ Number(cuotaBaseExactaUsd).toFixed(2) }} USD
              </div>
              <div class="text-caption text-purple-700 font-weight-medium">
                Bs. {{ Number(cuotaBaseExactaUsd * tasaOficialActiva).toFixed(2) }} c/u
              </div>
            </v-card>
          </div>

          <!-- Banner de Estado de Recaudación y Rebalanceo -->
          <div
            v-if="diferenciaRecaudacionUsd !== 0"
            class="mb-4 p-3 rounded-xl border d-flex align-center justify-space-between flex-wrap gap-2"
            :class="diferenciaRecaudacionUsd < 0 ? 'bg-amber-50 border-amber-300 text-amber-900' : 'bg-blue-50 border-blue-300 text-blue-900'"
          >
            <div class="d-flex align-center gap-2">
              <v-icon :color="diferenciaRecaudacionUsd < 0 ? 'amber-darken-3' : 'blue-darken-3'">
                {{ diferenciaRecaudacionUsd < 0 ? 'mdi-alert-circle-outline' : 'mdi-information-outline' }}
              </v-icon>
              <div>
                <span class="text-caption font-weight-bold">
                  {{ diferenciaRecaudacionUsd < 0 ? 'Faltante de Recaudación:' : 'Excedente de Recaudación:' }}
                </span>
                <span class="font-mono font-weight-bold ml-1">
                  ${{ Math.abs(diferenciaRecaudacionUsd).toFixed(2) }} USD
                </span>
                <span class="text-caption ml-1 text-slate-600">
                  (Total a recaudar: ${{ totalRecaudarUsdCalculado.toFixed(2) }} vs Presupuesto: ${{ totalPresupuestoUsd.toFixed(2) }})
                </span>
              </div>
            </div>

            <!-- Botón de Rebalanceo en 1 Clic -->
            <v-btn
              v-if="diferenciaRecaudacionUsd !== 0 && aptosParticipantesCount > 0"
              size="small"
              color="purple-darken-2"
              variant="flat"
              prepend-icon="mdi-scale-balance"
              @click="rebalancearCuotaEntreParticipantes"
            >
              ⚖️ Repartir diferencia equitativamente entre los {{ aptosParticipantesCount }} que pagan
            </v-btn>
          </div>

          <div
            v-else
            class="mb-4 p-2 px-4 rounded-xl bg-emerald-50 border border-emerald-300 text-emerald-900 text-caption d-flex align-center gap-2"
          >
            <v-icon color="emerald-darken-2" size="18">mdi-check-circle</v-icon>
            <span class="font-weight-medium">
              <strong>Presupuesto 100% Cubierto:</strong> Cada apartamento pagará exactamente ${{ cuotaBaseExactaUsd.toFixed(2) }} USD (Bs. {{ Number(cuotaBaseExactaUsd * tasaOficialActiva).toFixed(2) }}).
            </span>
          </div>

          <!-- Barra de Búsqueda y Filtros de Apartamentos -->
          <div class="d-flex align-center justify-space-between gap-3 mb-2 flex-wrap">
            <div class="text-caption font-weight-bold text-slate-700 text-uppercase">
              Detalle por Apartamento (Puedes exonerar o ajustar montos específicos):
            </div>
            <v-text-field
              v-model="filtroBusqueda"
              placeholder="Buscar por número o propietario..."
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              density="compact"
              color="purple"
              hide-details
              style="max-width: 280px;"
            />
          </div>

          <!-- Tabla de Inmuebles -->
          <v-card class="rounded-xl border border-slate-200 overflow-hidden bg-white shadow-sm" elevation="0">
            <v-table density="compact" style="max-height: 280px;">
              <thead>
                <tr class="bg-slate-100 text-slate-700">
                  <th style="width: 14%;" class="font-weight-bold">Inmueble</th>
                  <th style="width: 30%;" class="font-weight-bold">Propietario</th>
                  <th style="width: 20%;" class="text-center font-weight-bold">Participación</th>
                  <th style="width: 18%;" class="text-right font-weight-bold">Monto a Cobrar ($)</th>
                  <th style="width: 18%;" class="text-right font-weight-bold">Total en Bs. (BCV)</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="apto in apartamentosFiltrados"
                  :key="apto.apartamento_id"
                  :class="apto.es_exonerado ? 'bg-red-50/60 text-slate-400' : 'hover:bg-slate-50'"
                >
                  <td class="font-weight-bold">
                    Apto {{ apto.numero }}
                    <span class="text-caption text-slate-400 d-block">Piso {{ apto.piso || 'PB' }}</span>
                  </td>
                  <td>
                    <div class="text-truncate font-weight-medium text-slate-800" style="max-width: 220px;" :title="apto.propietario_nombre">
                      {{ apto.propietario_nombre }}
                    </div>
                    <span class="text-caption text-slate-500">{{ apto.propietario_cedula }}</span>
                  </td>
                  <td class="text-center">
                    <v-switch
                      v-model="apto.es_exonerado"
                      color="red"
                      density="compact"
                      hide-details
                      class="d-inline-flex"
                      :label="apto.es_exonerado ? 'Exonerado' : 'Participa'"
                      @update:model-value="onExoneracionToggle(apto)"
                    />
                  </td>
                  <td class="text-right">
                    <div v-if="!apto.es_exonerado">
                      <v-text-field
                        v-model.number="apto.cuota_final_usd"
                        type="number"
                        step="0.01"
                        min="0"
                        prefix="$"
                        variant="plain"
                        density="compact"
                        hide-details
                        class="font-mono text-right font-weight-bold text-slate-900"
                      />
                    </div>
                    <div v-else class="text-caption text-red-600 font-weight-bold">
                      $0.00 (Exento)
                    </div>
                  </td>
                  <td class="text-right font-mono font-weight-bold">
                    <span v-if="!apto.es_exonerado" class="text-indigo-900">
                      Bs. {{ Number((apto.cuota_final_usd || 0) * tasaOficialActiva).toFixed(2) }}
                    </span>
                    <span v-else class="text-slate-400">Bs. 0.00</span>
                  </td>
                </tr>
              </tbody>
            </v-table>
          </v-card>
        </div>

      </v-card-text>

      <v-divider />

      <!-- Barra de Navegación y Acciones Inferiores -->
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
          <!-- Botón Siguiente (Pasos 1 y 2) -->
          <v-btn
            v-if="pasoActual === 1"
            color="purple-darken-2"
            variant="flat"
            append-icon="mdi-arrow-right"
            :disabled="!form.titulo_proyecto?.trim()"
            @click="avanzarAlPaso2"
          >
            Continuar al Presupuesto
          </v-btn>

          <v-btn
            v-else-if="pasoActual === 2"
            color="purple-darken-2"
            variant="flat"
            append-icon="mdi-arrow-right"
            :disabled="totalPresupuestoUsd <= 0"
            @click="avanzarAlPaso3"
          >
            Revisar Distribución por Apartamento
          </v-btn>

          <!-- Botones de Emisión Final (Paso 3) -->
          <template v-else-if="pasoActual === 3">
            <v-btn
              color="purple-lighten-1"
              variant="outlined"
              prepend-icon="mdi-file-document-edit-outline"
              :loading="guardando"
              @click="guardarCuota(false)"
            >
              Guardar Borrador (Revisar PDF)
            </v-btn>

            <v-btn
              color="purple-darken-2"
              variant="flat"
              prepend-icon="mdi-shield-check"
              class="elevation-2"
              :loading="guardando"
              @click="confirmarCertificacion"
            >
              Certificar y Emitir Recibos
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
});

const emit = defineEmits(['update:modelValue', 'guardado']);

// Estado de Pasos
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
  titulo_proyecto: '',
  periodo: '',
  dias_vencimiento: 5,
  conceptos: [
    { concepto: '', monto: 0 }
  ],
});

const listaApartamentos = ref([]);
const cargando = ref(false);
const guardando = ref(false);

// Presupuesto total en $ USD
const totalPresupuestoUsd = computed(() => {
  return Number(form.value.conceptos.reduce((acc, c) => acc + (Number(c.monto) || 0), 0).toFixed(2));
});

// Conteo de apartamentos activos (no exonerados)
const aptosParticipantesCount = computed(() => {
  return listaApartamentos.value.filter(a => !a.es_exonerado).length;
});

// Cuota Base Exacta = Presupuesto / Apartamentos Participantes
const cuotaBaseExactaUsd = computed(() => {
  const participantes = aptosParticipantesCount.value || 1;
  if (totalPresupuestoUsd.value <= 0) return 0;
  return Number((totalPresupuestoUsd.value / participantes).toFixed(2));
});

// Total que se cobrará sumando cada fila individual
const totalRecaudarUsdCalculado = computed(() => {
  return Number(listaApartamentos.value.reduce((acc, a) => {
    return acc + (a.es_exonerado ? 0 : (Number(a.cuota_final_usd) || 0));
  }, 0).toFixed(2));
});

// Diferencia entre lo que se cobrará y el presupuesto del proyecto
const diferenciaRecaudacionUsd = computed(() => {
  return Number((totalRecaudarUsdCalculado.value - totalPresupuestoUsd.value).toFixed(2));
});

// Lista filtrada por el buscador
const apartamentosFiltrados = computed(() => {
  const q = filtroBusqueda.value.toLowerCase().trim();
  if (!q) return listaApartamentos.value;
  return listaApartamentos.value.filter(a => {
    const num = (a.numero || '').toLowerCase();
    const prop = (a.propietario_nombre || '').toLowerCase();
    const ced = (a.propietario_cedula || '').toLowerCase();
    return num.includes(q) || prop.includes(q) || ced.includes(q);
  });
});

watch(() => props.modelValue, async (val) => {
  if (val) {
    inicializarFormulario();
    await cargarDatosIniciales();
  }
});

function inicializarFormulario() {
  pasoActual.value = 1;
  pasoMaximoAlcanzado.value = 1;
  filtroBusqueda.value = '';
  form.value = {
    titulo_proyecto: '',
    periodo: 'Calculando...',
    dias_vencimiento: 5,
    conceptos: [
      { concepto: '', monto: 0 }
    ],
  };
}

function irAlPaso(paso) {
  if (paso <= pasoMaximoAlcanzado.value) {
    pasoActual.value = paso;
  }
}

function avanzarAlPaso2() {
  if (!form.value.titulo_proyecto?.trim()) {
    Swal.fire({
      icon: 'warning',
      title: 'Título Obligatorio',
      text: 'Por favor ingresa el nombre o motivo del proyecto extraordinario.',
    });
    return;
  }
  pasoActual.value = 2;
  if (pasoMaximoAlcanzado.value < 2) pasoMaximoAlcanzado.value = 2;
}

function avanzarAlPaso3() {
  if (totalPresupuestoUsd.value <= 0) {
    Swal.fire({
      icon: 'warning',
      title: 'Presupuesto Requerido',
      text: 'Debes registrar al menos un ítem con monto mayor a $0 USD.',
    });
    return;
  }

  // Asignar cuota base exacta a todos los apartamentos activos
  aplicarCuotaBaseATodos();

  pasoActual.value = 3;
  if (pasoMaximoAlcanzado.value < 3) pasoMaximoAlcanzado.value = 3;
}

function agregarConcepto() {
  form.value.conceptos.push({ concepto: '', monto: 0 });
}

function eliminarConcepto(idx) {
  if (form.value.conceptos.length > 1) {
    form.value.conceptos.splice(idx, 1);
  }
}

function aplicarCuotaBaseATodos() {
  const cuota = cuotaBaseExactaUsd.value;
  listaApartamentos.value.forEach(apto => {
    if (!apto.es_exonerado) {
      apto.cuota_final_usd = cuota;
    } else {
      apto.cuota_final_usd = 0;
    }
  });
}

function onExoneracionToggle(apto) {
  if (apto.es_exonerado) {
    apto.cuota_final_usd = 0;
  } else {
    apto.cuota_final_usd = cuotaBaseExactaUsd.value;
  }
}

function rebalancearCuotaEntreParticipantes() {
  const cuota = cuotaBaseExactaUsd.value;
  listaApartamentos.value.forEach(apto => {
    if (!apto.es_exonerado) {
      apto.cuota_final_usd = cuota;
    }
  });

  Swal.fire({
    icon: 'success',
    title: 'Cuota Rebalanceada',
    text: `El presupuesto de $${totalPresupuestoUsd.value.toFixed(2)} USD fue repartido equitativamente entre los ${aptosParticipantesCount.value} apartamentos activos ($${cuota.toFixed(2)} c/u).`,
    timer: 2000,
    showConfirmButton: false,
  });
}

async function cargarDatosIniciales() {
  cargando.value = true;
  try {
    // 1. Obtener período correlativo y lista de aptos mediante simulación rápida
    const res = await axios.post('/invoices/cuota-extraordinaria/simular', {
      periodo: '',
      titulo_proyecto: 'Proyecto Inicial',
      conceptos: [{ concepto: 'Presupuesto Base', monto: 0 }],
      modalidad_calculo: 'partes_iguales',
      apartamentos_ajustes: [],
    });

    if (res.data?.success && res.data?.data) {
      form.value.periodo = res.data.data.periodo;
      if (res.data.data.apartamentos) {
        listaApartamentos.value = res.data.data.apartamentos.map(a => ({
          ...a,
          es_exonerado: false,
          cuota_final_usd: 0,
        }));
      }
    }
  } catch (error) {
    console.warn("Fallo en simulación inicial, recurriendo a lista de apartamentos:", error);
    try {
      const aptosRes = await axios.get('/apartamentos');
      const aptosData = aptosRes.data?.data?.data || aptosRes.data?.data || [];
      listaApartamentos.value = aptosData.map(a => ({
        apartamento_id: a.id,
        numero: a.numero,
        piso: a.piso,
        propietario_nombre: a.propietarios?.[0]?.nombre_completo || 'Propietario',
        propietario_cedula: a.propietarios?.[0]?.cedula || 'N/A',
        cuota_final_usd: 0,
        es_exonerado: false,
      }));
    } catch (e) {
      console.error(e);
    }
  } finally {
    cargando.value = false;
  }
}

async function confirmarCertificacion() {
  const result = await Swal.fire({
    title: '¿Certificar y Emitir Cuota Extraordinaria?',
    text: `Se generarán los recibos definitivos para '${form.value.titulo_proyecto}' (${form.value.periodo}) y sus montos quedarán bloqueados para cobro.`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#6d28d9',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Sí, Certificar y Emitir',
    cancelButtonText: 'Cancelar',
  });

  if (result.isConfirmed) {
    guardarCuota(true);
  }
}

async function guardarCuota(certificarInmediatamente) {
  if (!form.value.titulo_proyecto?.trim()) {
    Swal.fire({
      icon: 'warning',
      title: 'Datos Incompletos',
      text: 'Debes indicar el nombre o motivo del proyecto extraordinario.',
    });
    return;
  }

  if (totalPresupuestoUsd.value <= 0) {
    Swal.fire({
      icon: 'warning',
      title: 'Presupuesto Vacío',
      text: 'Debes registrar al menos un concepto de gasto con monto superior a cero.',
    });
    return;
  }

  guardando.value = true;
  let res = null;
  try {
    const payload = {
      periodo: (form.value.periodo && form.value.periodo !== 'Calculando...' && form.value.periodo !== 'EXT-AUTO') ? form.value.periodo : '',
      titulo_proyecto: form.value.titulo_proyecto.trim(),
      dias_vencimiento: Number(form.value.dias_vencimiento) || 5,
      conceptos: form.value.conceptos
        .filter(c => c.concepto && c.concepto.trim() !== '')
        .map(c => ({
          concepto: c.concepto.trim(),
          monto: Number(c.monto) || 0,
        })),
      modalidad_calculo: 'partes_iguales',
      certificar_inmediatamente: certificarInmediatamente,
      apartamentos_ajustes: listaApartamentos.value.map(a => ({
        apartamento_id: a.apartamento_id,
        exonerado: Boolean(a.es_exonerado),
        monto_personalizado: a.es_exonerado ? 0 : (Number(a.cuota_final_usd) || 0),
        motivo: a.es_exonerado ? 'Exonerado por asamblea' : '',
      })),
    };

    res = await axios.post('/invoices/cuota-extraordinaria/generar', payload);
  } catch (error) {
    let msg = error.response?.data?.message;
    if (!msg && error.response?.data?.errors) {
      const firstError = Object.values(error.response.data.errors)[0];
      msg = Array.isArray(firstError) ? firstError[0] : String(firstError);
    }
    Swal.fire({
      icon: 'error',
      title: 'Error al Generar',
      text: msg || error.message || 'Ocurrió un problema al emitir los recibos de la cuota extraordinaria.',
    });
    guardando.value = false;
    return;
  }

  guardando.value = false;

  if (res?.data?.success) {
    await Swal.fire({
      icon: 'success',
      title: certificarInmediatamente ? '¡Cuota Extraordinaria Certificada!' : '¡Borrador Generado!',
      text: res.data.message || 'Los recibos han sido generados exitosamente.',
    });
    cerrar();
    try {
      emit('guardado', res.data.data);
    } catch (e) {
      console.warn("Error al emitir evento guardado:", e);
    }
  }
}

function cerrar() {
  dialogModel.value = false;
}
</script>

<style scoped>
/* SweetAlert por encima del modal */
:deep(.swal2-container) {
  z-index: 99999 !important;
}
</style>
