<template>
  <div class="space-y-6">
    <!-- Header Principal -->
    <div class="d-flex flex-column flex-sm-row justify-space-between align-sm-center gap-4">
      <div>
        <div class="d-flex align-center gap-2">
          <v-avatar color="indigo-lighten-5" size="42" class="text-indigo-darken-2">
            <v-icon icon="mdi-vote-outline" size="24" />
          </v-avatar>
          <div>
            <h1 class="text-h5 font-weight-black text-slate-900">Asambleas y Votaciones Ponderadas</h1>
            <p class="text-caption text-slate-500">
              Cómputo en tiempo real de quórum legal y decisiones ponderadas por la alícuota de cada inmueble.
            </p>
          </div>
        </div>
      </div>

      <div class="d-flex align-center gap-2">
        <v-btn
          v-if="canManage"
          color="indigo-darken-1"
          prepend-icon="mdi-plus"
          class="font-weight-bold"
          @click="openCreateDialog"
        >
          Convocar Nueva Asamblea
        </v-btn>
        <v-btn
          icon="mdi-refresh"
          variant="tonal"
          color="slate-600"
          :loading="loading"
          @click="fetchPolls"
        />
      </div>
    </div>

    <!-- Banner Informativo del Sistema de Votación Ponderada -->
    <v-card class="bg-gradient-to-r from-indigo-900 to-slate-900 text-white rounded-2xl pa-5 shadow-lg border-0">
      <div class="d-flex flex-column flex-md-row justify-space-between align-md-center gap-4">
        <div>
          <div class="d-flex align-center gap-2 mb-1">
            <v-chip color="amber-accent-3" size="x-small" variant="flat" class="font-weight-black text-slate-950">
              LEY DE PROPIEDAD HORIZONTAL
            </v-chip>
            <span class="text-caption text-slate-300">Artículo 22 • Validez Legal de Decisiones</span>
          </div>
          <h2 class="text-h6 font-weight-black mb-1">Ponderación Estricta por Alícuota Inmobiliaria</h2>
          <p class="text-caption text-slate-300 mb-0" style="max-width: 650px;">
            Cada voto emitido no computa como una unidad simple, sino con el porcentaje de alícuota exacto registrado en el documento de condominio del inmueble. El quórum se valida al alcanzar más del 50% de las alícuotas totales de la torre.
          </p>
        </div>

        <div class="d-flex gap-4">
          <div class="text-center px-4 py-2 bg-white/10 rounded-xl">
            <div class="text-h6 font-weight-black text-amber-accent-3">{{ stats.activas }}</div>
            <div class="text-xs text-slate-300">Asambleas Activas</div>
          </div>
          <div class="text-center px-4 py-2 bg-white/10 rounded-xl">
            <div class="text-h6 font-weight-black text-emerald-400">{{ stats.conQuorum }}</div>
            <div class="text-xs text-slate-300">Quórum Alcanzado</div>
          </div>
        </div>
      </div>
    </v-card>

    <!-- Listado de Asambleas / Votaciones -->
    <div v-if="loading && !polls.length" class="text-center py-12">
      <v-progress-circular indeterminate color="indigo" size="48" />
      <div class="text-caption text-slate-400 mt-2">Cargando asambleas del condominio...</div>
    </div>

    <div v-else-if="!polls.length" class="text-center py-12 bg-white rounded-2xl border border-slate-100 pa-8">
      <v-avatar color="indigo-lighten-5" size="64" class="mb-3 text-indigo">
        <v-icon icon="mdi-ballot-outline" size="36" />
      </v-avatar>
      <h3 class="text-h6 font-weight-bold text-slate-800">No hay asambleas o votaciones registradas</h3>
      <p class="text-caption text-slate-500 mb-4">
        Aperture la primera consulta para someter a votación decisiones comunitarias entre los copropietarios.
      </p>
      <v-btn v-if="canManage" color="indigo" prepend-icon="mdi-plus" @click="openCreateDialog">
        Convocar Primera Asamblea
      </v-btn>
    </div>

    <div v-else class="space-y-6">
      <v-card
        v-for="poll in polls"
        :key="poll.id"
        class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden"
      >
        <!-- Header de la Card -->
        <div class="pa-5 border-b border-slate-100 d-flex flex-column flex-sm-row justify-space-between align-sm-center gap-3 bg-slate-50/50">
          <div>
            <div class="d-flex align-center gap-2 mb-1">
              <v-chip
                :color="poll.estado === 'activa' ? 'success' : 'default'"
                size="small"
                variant="flat"
                class="font-weight-bold text-capitalize"
              >
                {{ poll.estado }}
              </v-chip>

              <v-chip
                v-if="poll.quorum_alcanzado"
                color="indigo"
                size="small"
                variant="tonal"
                class="font-weight-bold"
              >
                ✓ Quórum Legal Alcanzado ({{ poll.quorum_alicuota_pct }}%)
              </v-chip>
              <v-chip
                v-else
                color="warning"
                size="small"
                variant="tonal"
                class="font-weight-bold"
              >
                Quórum en Proceso ({{ poll.quorum_alicuota_pct }}% de 50%)
              </v-chip>

              <v-chip v-if="poll.ha_votado" color="emerald" size="small" variant="flat" class="text-white font-weight-bold">
                ✓ Mi Inmueble Votó
              </v-chip>
            </div>

            <h2 class="text-h6 font-weight-black text-slate-900 mb-1">{{ poll.titulo }}</h2>
            <div class="text-caption text-slate-500">
              Convocada por: <strong>{{ poll.creado_por }}</strong> • Vence: <strong>{{ poll.fecha_fin }}</strong>
            </div>
          </div>

          <div v-if="canManage && poll.estado === 'activa'">
            <v-btn
              color="error"
              variant="tonal"
              size="small"
              prepend-icon="mdi-check-all"
              :loading="finalizingId === poll.id"
              @click="finalizarPoll(poll.id)"
            >
              Cerrar Asamblea
            </v-btn>
          </div>
        </div>

        <!-- Descripción y Medidor de Quórum -->
        <div class="pa-5">
          <p class="text-body-2 text-slate-700 leading-relaxed mb-4">
            {{ poll.descripcion }}
          </p>

          <!-- Barra de Progreso de Quórum Ponderado -->
          <div class="bg-slate-50 rounded-xl pa-4 border border-slate-100 mb-5">
            <div class="d-flex justify-space-between align-center mb-1.5">
              <span class="text-caption font-weight-bold text-slate-700">
                Participación Ponderada de la Torre (Alícuota Total: {{ poll.quorum_alicuota_pct }}% / 100%)
              </span>
              <span class="text-caption font-weight-bold" :class="poll.quorum_alcanzado ? 'text-indigo-600' : 'text-amber-600'">
                {{ poll.total_votos }} inmuebles ({{ poll.alicuota_votada_total }}% de alícuotas)
              </span>
            </div>

            <v-progress-linear
              :model-value="poll.quorum_alicuota_pct"
              :color="poll.quorum_alcanzado ? 'indigo' : 'amber-darken-1'"
              height="10"
              rounded
            />

            <div class="d-flex justify-space-between text-xs text-slate-400 mt-1">
              <span>0% Inicio</span>
              <span class="font-weight-bold text-slate-600">50% Quórum Legal Ordinario</span>
              <span class="font-weight-bold text-slate-600">75% Mayoría Calificada</span>
              <span>100% Totalidad</span>
            </div>
          </div>

          <!-- Opciones y Votación -->
          <div>
            <h3 class="text-caption font-weight-bold text-slate-800 text-uppercase tracking-wider mb-3">
              Opciones Somitadas a Votación
            </h3>

            <div class="space-y-3">
              <div
                v-for="opc in poll.opciones"
                :key="opc.id"
                :class="[
                  'p-4 rounded-xl border transition-all',
                  poll.mi_voto_opcion_id === opc.id
                    ? 'border-indigo-500 bg-indigo-50/40 shadow-sm'
                    : 'border-slate-200 bg-white hover:border-slate-300'
                ]"
              >
                <div class="d-flex justify-space-between align-center mb-2">
                  <div class="d-flex align-center gap-2">
                    <v-icon
                      v-if="poll.mi_voto_opcion_id === opc.id"
                      icon="mdi-check-circle"
                      color="indigo"
                      size="20"
                    />
                    <span class="font-weight-bold text-slate-900 text-body-2">{{ opc.texto }}</span>
                  </div>

                  <div class="text-right">
                    <span class="text-caption font-weight-black text-slate-900">
                      {{ opc.alicuota_acumulada }}% alícuota
                    </span>
                    <span class="text-xs text-slate-500 ml-2">
                      ({{ opc.votos_count }} votos • {{ opc.porcentaje_votos_emitidos }}%)
                    </span>
                  </div>
                </div>

                <!-- Barra de ponderación de la opción -->
                <v-progress-linear
                  :model-value="opc.porcentaje_votos_emitidos"
                  :color="poll.mi_voto_opcion_id === opc.id ? 'indigo' : 'slate-400'"
                  height="6"
                  rounded
                />
              </div>
            </div>

            <!-- Panel para emitir voto si no ha votado y la asamblea está activa -->
            <div
              v-if="poll.estado === 'activa' && !poll.ha_votado"
              class="mt-5 pa-4 bg-indigo-50/60 rounded-xl border border-indigo-100"
            >
              <div class="font-weight-bold text-indigo-900 text-caption mb-2">
                🗳️ EMITIR MI VOTO COMO COPROPIETARIO:
              </div>
              <v-radio-group v-model="selectedOptionByPoll[poll.id]" hide-details density="compact">
                <v-radio
                  v-for="opc in poll.opciones"
                  :key="opc.id"
                  :value="opc.id"
                  :label="opc.texto"
                  color="indigo"
                />
              </v-radio-group>

              <div class="mt-3 text-right">
                <v-btn
                  color="indigo-darken-1"
                  variant="flat"
                  class="font-weight-bold"
                  prepend-icon="mdi-vote"
                  :disabled="!selectedOptionByPoll[poll.id]"
                  :loading="votingId === poll.id"
                  @click="emitirVoto(poll.id)"
                >
                  Confirmar y Registrar Mi Voto
                </v-btn>
              </div>
            </div>
          </div>
        </div>
      </v-card>
    </div>

    <!-- Modal para Convocar Nueva Asamblea -->
    <v-dialog v-model="createDialog" max-width="650" persistent>
      <v-card class="rounded-2xl pa-5">
        <v-card-title class="pa-0 font-weight-black text-h6 text-slate-900 mb-1">
          Convocar Nueva Asamblea o Consulta
        </v-card-title>
        <p class="text-caption text-slate-500 mb-4">
          Defina el asunto a consultar y las opciones que los copropietarios podrán elegir.
        </p>

        <v-form ref="createFormRef" @submit.prevent="submitCreatePoll">
          <div class="space-y-4">
            <div>
              <label class="text-caption font-weight-bold text-slate-700 mb-1 d-block">Título del Asunto / Consulta</label>
              <v-text-field
                v-model="newPoll.titulo"
                placeholder="Ej: Aprobación de Presupuesto para Impermeabilización de Azotea"
                density="comfortable"
                variant="outlined"
                color="indigo"
                :rules="[v => !!v || 'El título es obligatorio']"
              />
            </div>

            <div>
              <label class="text-caption font-weight-bold text-slate-700 mb-1 d-block">Descripción Detallada y Motivos</label>
              <v-textarea
                v-model="newPoll.descripcion"
                placeholder="Explique el alcance del proyecto, cotizaciones adjuntas o consideraciones que los propietarios deben evaluar..."
                rows="3"
                density="comfortable"
                variant="outlined"
                color="indigo"
                :rules="[v => !!v || 'La descripción es obligatoria']"
              />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="text-caption font-weight-bold text-slate-700 mb-1 d-block">Fecha Inicio</label>
                <v-text-field
                  v-model="newPoll.fecha_inicio"
                  type="datetime-local"
                  density="comfortable"
                  variant="outlined"
                  color="indigo"
                  :rules="[v => !!v || 'Fecha inicio requerida']"
                />
              </div>

              <div>
                <label class="text-caption font-weight-bold text-slate-700 mb-1 d-block">Fecha Límite Cierre</label>
                <v-text-field
                  v-model="newPoll.fecha_fin"
                  type="datetime-local"
                  density="comfortable"
                  variant="outlined"
                  color="indigo"
                  :rules="[v => !!v || 'Fecha fin requerida']"
                />
              </div>
            </div>

            <!-- Opciones Dinámicas -->
            <div>
              <div class="d-flex justify-space-between align-center mb-2">
                <label class="text-caption font-weight-bold text-slate-700">Opciones de Respuesta (Mínimo 2)</label>
                <v-btn
                  size="x-small"
                  variant="text"
                  color="indigo"
                  prepend-icon="mdi-plus"
                  @click="addOption"
                >
                  Agregar Opción
                </v-btn>
              </div>

              <div class="space-y-2">
                <div v-for="(opt, idx) in newPoll.opciones" :key="idx" class="d-flex align-center gap-2">
                  <v-text-field
                    v-model="opt.texto"
                    :placeholder="`Opción ${idx + 1}`"
                    density="compact"
                    variant="outlined"
                    color="indigo"
                    hide-details
                    class="flex-grow-1"
                  />
                  <v-btn
                    v-if="newPoll.opciones.length > 2"
                    icon="mdi-trash-can-outline"
                    variant="text"
                    color="error"
                    size="small"
                    @click="removeOption(idx)"
                  />
                </div>
              </div>
            </div>
          </div>

          <div class="d-flex justify-end gap-2 mt-6">
            <v-btn variant="tonal" color="slate-600" @click="createDialog = false">
              Cancelar
            </v-btn>
            <v-btn
              type="submit"
              color="indigo-darken-1"
              variant="flat"
              class="font-weight-bold"
              :loading="creating"
            >
              Publicar Convocatoria
            </v-btn>
          </div>
        </v-form>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../../store/auth';

const authStore = useAuthStore();
const polls = ref([]);
const loading = ref(false);
const creating = ref(false);
const votingId = ref(null);
const finalizingId = ref(null);

const createDialog = ref(false);
const createFormRef = ref(null);
const selectedOptionByPoll = reactive({});

const canManage = computed(() => {
    return authStore.isMaster || authStore.isAdmin || authStore.isSupervisor;
});

const stats = computed(() => {
    const activas = polls.value.filter(p => p.estado === 'activa').length;
    const conQuorum = polls.value.filter(p => p.quorum_alcanzado).length;
    return { activas, conQuorum };
});

const newPoll = reactive({
    titulo: '',
    descripcion: '',
    fecha_inicio: '',
    fecha_fin: '',
    opciones: [
        { texto: 'A favor / Aprobar' },
        { texto: 'En contra / Rechazar' },
        { texto: 'Abstención' },
    ],
});

const addOption = () => {
    newPoll.opciones.push({ texto: '' });
};

const removeOption = (idx) => {
    if (newPoll.opciones.length > 2) {
        newPoll.opciones.splice(idx, 1);
    }
};

const openCreateDialog = () => {
    const now = new Date();
    const future = new Date();
    future.setDate(future.getDate() + 7);

    newPoll.titulo = '';
    newPoll.descripcion = '';
    newPoll.fecha_inicio = now.toISOString().slice(0, 16);
    newPoll.fecha_fin = future.toISOString().slice(0, 16);
    newPoll.opciones = [
        { texto: 'A favor / Aprobar' },
        { texto: 'En contra / Rechazar' },
        { texto: 'Abstención' },
    ];
    createDialog.value = true;
};

const fetchPolls = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get('/asambleas');
        if (data.success) {
            polls.value = data.data;
        }
    } catch (e) {
        authStore.notify('Error al cargar asambleas y votaciones', 'error');
    } finally {
        loading.value = false;
    }
};

const submitCreatePoll = async () => {
    if (!newPoll.titulo || !newPoll.descripcion) return;
    creating.value = true;
    try {
        const { data } = await axios.post('/asambleas', {
            titulo: newPoll.titulo,
            descripcion: newPoll.descripcion,
            fecha_inicio: newPoll.fecha_inicio,
            fecha_fin: newPoll.fecha_fin,
            opciones: newPoll.opciones.filter(o => o.texto.trim() !== ''),
        });
        if (data.success) {
            authStore.notify('Asamblea aperturada exitosamente', 'success');
            createDialog.value = false;
            fetchPolls();
        }
    } catch (e) {
        authStore.notify(e.response?.data?.message || 'Error al convocar asamblea', 'error');
    } finally {
        creating.value = false;
    }
};

const emitirVoto = async (pollId) => {
    const opcionId = selectedOptionByPoll[pollId];
    if (!opcionId) return;

    votingId.value = pollId;
    try {
        const { data } = await axios.post(`/asambleas/${pollId}/votar`, {
            opcion_id: opcionId,
        });
        if (data.success) {
            authStore.notify(data.message || 'Voto registrado exitosamente', 'success');
            fetchPolls();
        }
    } catch (e) {
        authStore.notify(e.response?.data?.message || 'Error al registrar el voto', 'error');
    } finally {
        votingId.value = null;
    }
};

const finalizarPoll = async (pollId) => {
    finalizingId.value = pollId;
    try {
        const { data } = await axios.post(`/asambleas/${pollId}/finalizar`);
        if (data.success) {
            authStore.notify('Asamblea finalizada y resultados fijados', 'success');
            fetchPolls();
        }
    } catch (e) {
        authStore.notify('Error al cerrar asamblea', 'error');
    } finally {
        finalizingId.value = null;
    }
};

onMounted(fetchPolls);
</script>

<style scoped>
.space-y-2 > * + * {
    margin-top: 0.5rem;
}
.space-y-3 > * + * {
    margin-top: 0.75rem;
}
.space-y-4 > * + * {
    margin-top: 1rem;
}
.space-y-6 > * + * {
    margin-top: 1.5rem;
}
</style>
