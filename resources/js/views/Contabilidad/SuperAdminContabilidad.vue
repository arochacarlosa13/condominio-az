<template>
  <div>
    <!-- Top Header -->
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold text-slate-900">Cobranza SaaS y Control Contable Global</h1>
        <p class="text-caption text-slate-500">Facturación a condominios, conciliación de pagos SaaS y auditoría/desbloqueo de recibos</p>
      </div>
      <div class="d-flex gap-2">
        <v-btn color="primary" prepend-icon="mdi-plus-box" @click="openInvoiceDialog">
          Emitir Cobro SaaS
        </v-btn>
        <v-btn color="success" prepend-icon="mdi-cash-plus" @click="openPaymentDialog">
          Registrar Pago Recibido
        </v-btn>
      </div>
    </div>

    <!-- Financial KPI Summary Cards -->
    <v-row class="mb-6">
      <v-col cols="12" sm="6" md="3">
        <v-card class="pa-4 bg-white border border-slate-100">
          <div class="text-caption text-slate-500 font-weight-medium">Recaudado Total SaaS</div>
          <div class="text-h6 font-weight-bold text-success mt-1">
            {{ authStore.formatMoney(summary.total_recaudado_bs) }}
          </div>
          <div class="text-caption text-slate-400">Equiv: ${{ Number(summary.total_recaudado_usd).toFixed(2) }} USD</div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card class="pa-4 bg-white border border-slate-100">
          <div class="text-caption text-slate-500 font-weight-medium">Deuda por Cobrar SaaS</div>
          <div class="text-h6 font-weight-bold text-error mt-1">
            {{ authStore.formatMoney(summary.deuda_pendiente_bs) }}
          </div>
          <div class="text-caption text-slate-400">Equiv: ${{ Number(summary.deuda_pendiente_usd).toFixed(2) }} USD</div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card class="pa-4 bg-white border border-slate-100">
          <div class="text-caption text-slate-500 font-weight-medium">Proyección Mensual</div>
          <div class="text-h6 font-weight-bold text-primary mt-1">
            ${{ Number(summary.proyeccion_mensual_usd).toFixed(2) }} USD
          </div>
          <div class="text-caption text-slate-400">{{ summary.total_apartamentos }} apartamentos suscritos</div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card class="pa-4 bg-white border border-slate-100">
          <div class="text-caption text-slate-500 font-weight-medium">Condominios Activos</div>
          <div class="text-h6 font-weight-bold text-slate-900 mt-1">
            {{ summary.condominios_activos }} / {{ summary.total_condominios }}
          </div>
          <div class="text-caption text-slate-400">Plataforma Multi-Tenant</div>
        </v-card>
      </v-col>
    </v-row>

    <!-- Super Admin Tabs -->
    <v-tabs v-model="tab" color="primary" class="mb-6 bg-white border border-slate-100 rounded-lg">
      <v-tab value="saas" prepend-icon="mdi-cash-register">Cobranza SaaS a Condominios</v-tab>
      <v-tab value="desbloqueo" prepend-icon="mdi-lock-reset">
        Auditoría y Desbloqueo de Recibos
        <v-badge v-if="periodosCertificadosCount > 0" :content="periodosCertificadosCount" color="primary" inline class="ml-2" />
      </v-tab>
    </v-tabs>

    <v-window v-model="tab">
      <!-- Tab 1: SaaS Invoices Table -->
      <v-window-item value="saas">
        <v-card class="bg-white border border-slate-100">
          <div class="pa-4 border-b border-slate-100 font-weight-bold text-subtitle-1 text-slate-900">
            Facturas Emitidas a Administradores de Condominio
          </div>

          <DataTableHeader
            v-model:search="search"
            v-model:per-page="perPage"
            :per-page-options="perPageOptions"
            placeholder="Buscar por N° factura, condominio o período..."
          />

          <v-table density="comfortable" hover>
            <thead>
              <tr class="bg-slate-50 text-slate-700">
                <SortHeader col-key="numero_factura" width="160px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
                  N° Factura / Período
                </SortHeader>
                <SortHeader col-key="condominio" width="180px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
                  Condominio
                </SortHeader>
                <SortHeader col-key="fecha_vencimiento" width="120px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
                  Vencimiento
                </SortHeader>
                <SortHeader col-key="monto_total_usd" width="140px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
                  Monto USD / Bs.
                </SortHeader>
                <SortHeader col-key="monto_pagado_usd" width="110px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
                  Abonado
                </SortHeader>
                <SortHeader col-key="estado" width="100px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
                  Estado
                </SortHeader>
              </tr>
            </thead>
            <tbody>
              <tr v-for="inv in paginatedItems" :key="inv.id">
                <td>
                  <div class="font-weight-bold text-slate-900">{{ inv.numero_factura }}</div>
                  <div class="text-caption text-slate-500">Período: {{ inv.periodo }}</div>
                </td>
                <td><strong>{{ inv.condominio?.nombre }}</strong></td>
                <td>{{ inv.fecha_vencimiento }}</td>
                <td>
                  <div class="font-weight-bold">${{ Number(inv.monto_total_usd).toFixed(2) }} USD</div>
                  <div class="text-caption text-slate-500">{{ authStore.formatMoney(inv.monto_total_bs) }}</div>
                </td>
                <td>
                  <span class="text-success font-weight-bold">${{ Number(inv.monto_pagado_usd).toFixed(2) }}</span>
                </td>
                <td>
                  <v-chip
                    :color="inv.estado === 'pagado' ? 'success' : (inv.estado === 'pendiente' ? 'warning' : 'error')"
                    size="x-small"
                    variant="flat"
                    class="text-capitalize"
                  >
                    {{ inv.estado }}
                  </v-chip>
                </td>
              </tr>
              <tr v-if="!paginatedItems.length">
                <td colspan="6" class="text-center py-6 text-slate-400">
                  {{ search ? 'No se encontraron facturas que coincidan con la búsqueda.' : 'No hay cobros de suscripción registrados.' }}
                </td>
              </tr>
            </tbody>
          </v-table>

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

      <!-- Tab 2: Condominium Receipt Auditing and Unlock Management -->
      <v-window-item value="desbloqueo">
        <v-card class="bg-white border border-slate-100">
          <div class="pa-4 border-b border-slate-100 d-flex justify-space-between align-center">
            <div>
              <div class="font-weight-bold text-subtitle-1 text-slate-900">
                Auditoría y Reapertura de Recibos Mensuales de Condominios
              </div>
              <div class="text-caption text-slate-500">
                Como Super Administrador, puedes devolver recibos certificados al estado <strong>Borrador</strong> para permitir correcciones a los administradores (Máximo 2 reaperturas por recibo).
              </div>
            </div>
            <v-chip color="teal" size="small" variant="flat" class="font-weight-bold">
              Control de Seguridad Máx. 2 Intentos
            </v-chip>
          </div>

          <DataTableHeader
            v-model:search="pPeriodos.search.value"
            v-model:per-page="pPeriodos.perPage.value"
            :per-page-options="pPeriodos.perPageOptions"
            placeholder="Buscar por condominio, período..."
          />

          <v-table density="comfortable" hover>
            <thead>
              <tr class="bg-slate-50 text-slate-700">
                <SortHeader col-key="condominio_nombre" width="180px" :sort-by="pPeriodos.sortBy.value" :sort-desc="pPeriodos.sortDesc.value" @sort="pPeriodos.sort">
                  Condominio / Residencia
                </SortHeader>
                <SortHeader col-key="periodo" width="120px" :sort-by="pPeriodos.sortBy.value" :sort-desc="pPeriodos.sortDesc.value" @sort="pPeriodos.sort">
                  Período
                </SortHeader>
                <SortHeader col-key="total_monto_usd" width="140px" :sort-by="pPeriodos.sortBy.value" :sort-desc="pPeriodos.sortDesc.value" @sort="pPeriodos.sort">
                  Aptos / Total
                </SortHeader>
                <SortHeader col-key="estado_certificacion" width="130px" :sort-by="pPeriodos.sortBy.value" :sort-desc="pPeriodos.sortDesc.value" @sort="pPeriodos.sort">
                  Estado Actual
                </SortHeader>
                <SortHeader col-key="certificado_por" width="130px" :sort-by="pPeriodos.sortBy.value" :sort-desc="pPeriodos.sortDesc.value" @sort="pPeriodos.sort">
                  Certificado Por
                </SortHeader>
                <SortHeader col-key="veces_reabierto" width="120px" :sort-by="pPeriodos.sortBy.value" :sort-desc="pPeriodos.sortDesc.value" @sort="pPeriodos.sort">
                  Reaperturas
                </SortHeader>
                <th class="text-right" style="width: 140px;">Acción Super Admin</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in pPeriodos.paginatedItems.value" :key="p.key">
                <td>
                  <div class="font-weight-bold text-slate-900">{{ p.condominio_nombre }}</div>
                  <div class="text-caption text-slate-500">Emisión: {{ p.fecha_emision || '-' }}</div>
                </td>
                <td class="font-weight-bold text-slate-800">{{ p.periodo }}</td>
                <td>
                  <div class="font-weight-bold">${{ Number(p.total_monto_usd).toFixed(2) }} USD</div>
                  <div class="text-caption text-slate-500">{{ p.total_apartamentos }} apartamentos</div>
                </td>
                <td>
                  <v-chip
                    :color="p.estado_certificacion === 'certificado' ? 'success' : 'warning'"
                    size="small"
                    variant="flat"
                    class="font-weight-bold text-uppercase"
                  >
                    <v-icon start :icon="p.estado_certificacion === 'certificado' ? 'mdi-shield-check' : 'mdi-file-document-edit'" />
                    {{ p.estado_certificacion === 'certificado' ? 'Certificado / Bloqueado' : 'Borrador / Editable' }}
                  </v-chip>
                </td>
                <td>
                  <div v-if="p.fecha_certificacion" class="text-caption text-slate-700">
                    <strong>{{ p.certificado_por || 'Admin' }}</strong>
                    <div class="text-slate-400">{{ p.fecha_certificacion }}</div>
                  </div>
                  <div v-else class="text-caption text-amber-700 italic">En edición (borrador)</div>
                </td>
                <td>
                  <v-chip
                    :color="p.veces_reabierto >= 2 ? 'error' : (p.veces_reabierto > 0 ? 'warning' : 'secondary')"
                    size="small"
                    variant="tonal"
                    class="font-weight-bold"
                  >
                    {{ p.veces_reabierto }} de 2 utilizadas
                  </v-chip>
                  <div v-if="p.motivo_reapertura" class="text-caption text-slate-500 text-truncate" style="max-width: 160px;" :title="p.motivo_reapertura">
                    Último motivo: {{ p.motivo_reapertura }}
                  </div>
                </td>
                <td class="text-right">
                  <!-- Botón de Devolver a Borrador (Desbloquear) -->
                  <v-btn
                    v-if="p.estado_certificacion === 'certificado'"
                    :disabled="p.veces_reabierto >= 2"
                    size="small"
                    variant="flat"
                    :color="p.veces_reabierto >= 2 ? 'grey' : 'amber-darken-3'"
                    prepend-icon="mdi-lock-reset"
                    class="font-weight-bold text-capitalize"
                    @click="openReabrirDialog(p)"
                  >
                    {{ p.veces_reabierto >= 2 ? 'Límite (2/2)' : 'Devolver a Borrador' }}
                  </v-btn>
                  <v-chip v-else size="small" color="info" variant="tonal">
                    Ya en Borrador
                  </v-chip>
                </td>
              </tr>
              <tr v-if="!pPeriodos.paginatedItems.value.length">
                <td colspan="7" class="text-center py-6 text-slate-400">
                  {{ pPeriodos.search.value ? 'No se encontraron recibos que coincidan con la búsqueda.' : 'No hay períodos registrados en ningún condominio.' }}
                </td>
              </tr>
            </tbody>
          </v-table>

          <DataTableFooter
            v-model:current-page="pPeriodos.currentPage.value"
            :total-pages="pPeriodos.totalPages.value"
            :total-items="pPeriodos.totalItems.value"
            :original-total="pPeriodos.originalTotal.value"
            :start-index="pPeriodos.startIndex.value"
            :end-index="pPeriodos.endIndex.value"
            :search="pPeriodos.search.value"
          />
        </v-card>
      </v-window-item>
    </v-window>

    <!-- Dialog for Reopening / Unlocking a Certified Receipt -->
    <v-dialog v-model="reabrirDialog" max-width="550">
      <v-card class="pa-4" v-if="selectedPeriodo">
        <v-card-title class="font-weight-bold d-flex align-center text-amber-darken-4">
          <v-icon icon="mdi-lock-open-alert" color="amber-darken-3" class="mr-2" />
          Devolver Recibo a Estado Borrador
        </v-card-title>
        <v-card-text>
          <p class="text-body-2 text-slate-700 mb-3">
            Estás a punto de desbloquear el recibo del período <strong>{{ selectedPeriodo.periodo }}</strong> perteneciente a <strong>{{ selectedPeriodo.condominio_nombre }}</strong>.
          </p>

          <v-alert type="warning" variant="tonal" density="compact" class="mb-4 text-caption">
            <div>Intento de reapertura: <strong>{{ selectedPeriodo.veces_reabierto + 1 }} de 2 permitidas</strong>.</div>
            <div class="mt-1">Al devolverlo a borrador, el administrador de este condominio podrá modificar los conceptos, ajustar montos y volver a certificarlo.</div>
          </v-alert>

          <v-textarea
            v-model="reabrirMotivo"
            label="Motivo o Justificación de la Reapertura (Requerido)"
            rows="3"
            placeholder="Ej. Solicitud del administrador por error en el cálculo del concepto de gas comunal."
            required
            class="mb-2"
          />
        </v-card-text>
        <v-card-actions class="justify-end gap-2">
          <v-btn variant="text" @click="reabrirDialog = false">Cancelar</v-btn>
          <v-btn
            color="amber-darken-3"
            variant="flat"
            :loading="reabriendo"
            prepend-icon="mdi-lock-reset"
            @click="ejecutarReapertura"
          >
            Confirmar Reapertura
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dialog for Generating SaaS Invoice -->
    <v-dialog v-model="invoiceDialog" max-width="500">
      <v-card class="pa-4">
        <v-card-title class="font-weight-bold">Emitir Factura de Suscripción a Condominio</v-card-title>
        <v-card-text>
          <v-form @submit.prevent="saveInvoice">
            <v-select
              v-model="invoiceForm.condominio_id"
              label="Seleccionar Condominio"
              :items="condosSelect"
              item-title="nombre"
              item-value="id"
              required
              class="mb-3"
              @update:model-value="onCondoInvoiceSelect"
            />
            <v-text-field v-model="invoiceForm.periodo" label="Período (ej. 202608)" required class="mb-3" />
            <v-text-field v-model="invoiceForm.monto_usd" label="Monto a Cobrar ($ USD)" type="number" step="0.01" required class="mb-3" />
            <v-text-field
              v-model="invoiceForm.tasa_cambio"
              label="Tasa de Cambio Oficial (Bs./$)"
              type="number"
              step="0.01"
              readonly
              prepend-inner-icon="mdi-lock"
              hint="🔒 Tasa Oficial fija del sistema (sincronizada automáticamente / módulo central)"
              persistent-hint
              required
              class="mb-3 bg-slate-50"
            />
            <v-text-field v-model="invoiceForm.fecha_vencimiento" label="Fecha de Vencimiento" type="date" required class="mb-3" />
          </v-form>
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn variant="text" @click="invoiceDialog = false">Cancelar</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveInvoice">Emitir Cobro</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dialog for Registering SaaS Payment -->
    <v-dialog v-model="paymentDialog" max-width="500">
      <v-card class="pa-4">
        <v-card-title class="font-weight-bold">Registrar Pago de Condominio</v-card-title>
        <v-card-text>
          <v-form @submit.prevent="savePayment">
            <v-select
              v-model="paymentForm.condominio_id"
              label="Condominio Emisor"
              :items="condosSelect"
              item-title="nombre"
              item-value="id"
              required
              class="mb-3"
              @update:model-value="onCondoPaymentSelect"
            />
            <v-text-field v-model="paymentForm.monto_usd" label="Monto Recibido ($ USD)" type="number" step="0.01" required class="mb-3" />
            <v-text-field
              v-model="paymentForm.tasa_cambio"
              label="Tasa Oficial del Día (Bs./$)"
              type="number"
              step="0.01"
              readonly
              prepend-inner-icon="mdi-lock"
              hint="🔒 Tasa Oficial fija del sistema (sincronizada automáticamente / módulo central)"
              persistent-hint
              class="mb-3 bg-slate-50"
            />
            <v-text-field v-model="paymentForm.fecha_pago" label="Fecha del Pago" type="date" required class="mb-3" />
            <v-text-field v-model="paymentForm.referencia" label="Número de Referencia" required class="mb-3" />
          </v-form>
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn variant="text" @click="paymentDialog = false">Cancelar</v-btn>
          <v-btn color="success" :loading="saving" @click="savePayment">Registrar Pago</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import { useAuthStore } from '../../store/auth';
import { usePagination } from '../../composables/usePagination';
import DataTableHeader from '../../components/DataTableHeader.vue';
import DataTableFooter from '../../components/DataTableFooter.vue';
import SortHeader from '../../components/SortHeader.vue';

const authStore = useAuthStore();
const route = useRoute();
const tab = ref(route.query.tab === 'desbloqueo' ? 'desbloqueo' : 'saas');
const invoicesList = ref([]);
const periodosGlobalList = ref([]);
const condosSelect = ref([]);

watch(() => route.query.tab, (newTab) => {
    if (newTab) tab.value = newTab;
});

const summary = ref({
    total_recaudado_bs: 0,
    total_recaudado_usd: 0,
    deuda_pendiente_bs: 0,
    deuda_pendiente_usd: 0,
    proyeccion_mensual_usd: 0,
    condominios_activos: 0,
    total_condominios: 0,
    total_apartamentos: 0,
});

const customGettersSaas = {
    condominio: (i) => i.condominio?.nombre || '',
    monto_total_usd: (i) => Number(i.monto_total_usd || 0),
    monto_pagado_usd: (i) => Number(i.monto_pagado_usd || 0),
};

const customGettersPeriodos = {
    total_monto_usd: (p) => Number(p.total_monto_usd || 0),
    veces_reabierto: (p) => Number(p.veces_reabierto || 0),
};

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
    sortBy,
    sortDesc,
    sort,
} = usePagination(invoicesList, {
    perPage: 10,
    initialSortBy: 'fecha_vencimiento',
    initialSortDesc: true,
    customGetters: customGettersSaas,
});

const pPeriodos = usePagination(periodosGlobalList, {
    perPage: 10,
    initialSortBy: 'periodo',
    initialSortDesc: true,
    customGetters: customGettersPeriodos,
});

const periodosCertificadosCount = computed(() => {
    return periodosGlobalList.value.filter(p => p.estado_certificacion === 'certificado').length;
});

const invoiceDialog = ref(false);
const paymentDialog = ref(false);
const saving = ref(false);

const reabrirDialog = ref(false);
const reabriendo = ref(false);
const selectedPeriodo = ref(null);
const reabrirMotivo = ref('');

const invoiceForm = ref({
    condominio_id: null,
    periodo: new Date().getFullYear().toString() + String(new Date().getMonth() + 1).padStart(2, '0'),
    monto_usd: 15.00,
    tasa_cambio: authStore.tasaCambioCentral || authStore.tasaCambio || 36.50,
    fecha_vencimiento: new Date().toISOString().substring(0, 10),
});

const paymentForm = ref({
    condominio_id: null,
    monto_usd: 15.00,
    tasa_cambio: authStore.tasaCambioCentral || authStore.tasaCambio || 36.50,
    fecha_pago: new Date().toISOString().substring(0, 10),
    referencia: '',
    metodo_pago: 'transferencia',
});

const onCondoInvoiceSelect = (condoId) => {
    const condo = condosSelect.value.find(c => c.id === condoId);
    if (condo) {
        invoiceForm.value.tasa_cambio = Number(condo.tasa_cambio || authStore.tasaCambioCentral || authStore.tasaCambio || 36.50);
        if (condo.plan?.precio_mensual_usd) {
            invoiceForm.value.monto_usd = Number(condo.plan.precio_mensual_usd);
        }
    }
};

const onCondoPaymentSelect = (condoId) => {
    const condo = condosSelect.value.find(c => c.id === condoId);
    if (condo) {
        paymentForm.value.tasa_cambio = Number(condo.tasa_cambio || authStore.tasaCambioCentral || authStore.tasaCambio || 36.50);
    }
};

const fetchData = async () => {
    try {
        const [invRes, sumRes, condosRes, periodosRes] = await Promise.all([
            axios.get('/saas/invoices'),
            axios.get('/saas/summary'),
            axios.get('/condominios'),
            axios.get('/invoices/periodos-resumen'),
        ]);
        if (invRes.data.success) invoicesList.value = invRes.data.data.data || invRes.data.data;
        if (sumRes.data.success) summary.value = sumRes.data.data;
        if (condosRes.data.success) condosSelect.value = condosRes.data.data.data || condosRes.data.data;
        if (periodosRes.data.success) periodosGlobalList.value = periodosRes.data.data;
    } catch (e) {
        authStore.notify('Error al cargar datos contables de SaaS', 'error');
    }
};

const openInvoiceDialog = () => {
    const now = new Date();
    const currentPeriod = now.getFullYear().toString() + String(now.getMonth() + 1).padStart(2, '0');
    invoiceForm.value.periodo = currentPeriod;
    invoiceForm.value.tasa_cambio = Number(authStore.tasaCambioCentral || authStore.tasaCambio || 36.50);
    invoiceDialog.value = true;
};

const openPaymentDialog = () => {
    paymentForm.value.tasa_cambio = Number(authStore.tasaCambioCentral || authStore.tasaCambio || 36.50);
    paymentDialog.value = true;
};

const openReabrirDialog = (item) => {
    selectedPeriodo.value = item;
    reabrirMotivo.value = '';
    reabrirDialog.value = true;
};

const ejecutarReapertura = async () => {
    if (!reabrirMotivo.value.trim()) {
        await Swal.fire({
            icon: 'warning',
            title: 'Justificación requerida',
            text: 'Debes ingresar el motivo de auditoría para autorizar la reapertura del recibo.',
            confirmButtonColor: '#0f172a',
            customClass: { popup: 'rounded-2xl' },
        });
        return;
    }

    reabriendo.value = true;
    try {
        const payload = {
            condominio_id: selectedPeriodo.value.condominio_id,
            periodo: selectedPeriodo.value.periodo,
            motivo: reabrirMotivo.value,
        };
        const { data } = await axios.post('/invoices/reabrir-periodo', payload);
        reabrirDialog.value = false;
        fetchData();

        await Swal.fire({
            icon: 'success',
            title: '¡Recibo Devuelto a Borrador!',
            html: `
                <div class="text-sm text-slate-600">
                    ${data.message}<br>
                    <div class="mt-2 p-2 bg-amber-50 rounded text-amber-900 font-bold">
                        Reaperturas restantes disponibles: ${data.data?.reaperturas_restantes ?? 0} de 2
                    </div>
                </div>
            `,
            confirmButtonColor: '#0f172a',
            confirmButtonText: 'Aceptar',
            customClass: { popup: 'rounded-3xl shadow-xl' },
        });
    } catch (e) {
        await Swal.fire({
            icon: 'error',
            title: 'No se pudo reabrir',
            text: e.response?.data?.message || 'Error al intentar devolver el recibo a borrador.',
            confirmButtonColor: '#0f172a',
            customClass: { popup: 'rounded-2xl' },
        });
    } finally {
        reabriendo.value = false;
    }
};

const saveInvoice = async () => {
    saving.value = true;
    try {
        await axios.post('/saas/invoices', invoiceForm.value);
        authStore.notify('Cobro de SaaS emitido correctamente');
        invoiceDialog.value = false;
        fetchData();
    } catch (e) {
        authStore.notify('Error al emitir factura', 'error');
    } finally {
        saving.value = false;
    }
};

const savePayment = async () => {
    saving.value = true;
    try {
        await axios.post('/saas/payments', paymentForm.value);
        authStore.notify('Pago de SaaS registrado y conciliado');
        paymentDialog.value = false;
        fetchData();
    } catch (e) {
        authStore.notify('Error al registrar pago', 'error');
    } finally {
        saving.value = false;
    }
};

onMounted(fetchData);
</script>
