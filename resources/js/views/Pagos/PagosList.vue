<template>
  <div>
    <!-- Top Header -->
    <div class="d-flex flex-column flex-sm-row justify-space-between align-start align-sm-center gap-3 mb-4 mb-sm-6">
      <div>
        <h1 class="text-h5 font-weight-bold text-slate-900">Gestión de Pagos y Recibos</h1>
        <p class="text-caption text-slate-500">Historial bancario, conciliación de facturas y emisión de comprobantes digitales en PDF</p>
      </div>
      <div class="d-flex gap-2 w-100 w-sm-auto">
        <v-btn color="success" prepend-icon="mdi-cash-plus" class="w-100 w-sm-auto font-weight-bold" @click="openPaymentDialog">
          {{ authStore.isPropietario ? 'Notificar Mi Pago' : 'Registrar Pago Manual' }}
        </v-btn>
      </div>
    </div>

    <!-- Tabs Navigation -->
    <v-card class="bg-white border border-slate-100 mb-4 rounded-xl">
      <v-tabs v-model="activeTab" color="primary" align-tabs="start">
        <v-tab value="pagos" prepend-icon="mdi-receipt-text-outline" class="font-weight-bold">
          Historial de Pagos y Recibos
        </v-tab>
        <v-tab value="notas_credito" prepend-icon="mdi-cash-refund" class="font-weight-bold">
          Notas de Crédito (Saldos a Favor)
          <v-chip
            v-if="creditNotesDisponiblesCount > 0"
            size="x-small"
            color="emerald-darken-1"
            variant="flat"
            class="ml-2 font-weight-bold"
          >
            {{ creditNotesDisponiblesCount }} Disponibles
          </v-chip>
        </v-tab>
      </v-tabs>
    </v-card>

    <v-window v-model="activeTab">
      <!-- ===== Tab 1: PAGOS Y RECIBOS ===== -->
      <v-window-item value="pagos">
        <!-- Payments List Table with DataTable controls -->
        <v-card class="bg-white border border-slate-100 rounded-xl">
          <DataTableHeader
            v-model:search="search"
            v-model:per-page="perPage"
            :per-page-options="perPageOptions"
            placeholder="Buscar por referencia, apartamento, banco..."
          />

          <div class="table-responsive-container">
            <v-table density="comfortable" hover style="min-width: 640px;">
              <thead>
                <tr class="bg-slate-50 text-slate-700">
                  <SortHeader col-key="fecha_pago" width="110px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
                    Fecha
                  </SortHeader>
                  <SortHeader col-key="apartamento" width="180px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
                    Factura / Apartamento
                  </SortHeader>
                  <SortHeader col-key="metodo_pago" width="140px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
                    Método / Banco
                  </SortHeader>
                  <SortHeader col-key="referencia" width="120px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
                    Referencia
                  </SortHeader>
                  <SortHeader col-key="monto" width="120px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
                    Monto
                  </SortHeader>
                  <SortHeader col-key="estado" width="100px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
                    Estado
                  </SortHeader>
                  <th class="text-right" style="width: 140px;">Recibo / Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="pago in paginatedItems" :key="pago.id">
                  <td>{{ formatDate(pago.fecha_pago) }}</td>
                  <td>
                    <div class="font-weight-bold text-slate-900">
                      Apartamento {{ pago.invoice?.apartamento?.numero || pago.invoices?.[0]?.apartamento?.numero || '-' }}
                    </div>
                    
                    <!-- Si el pago cubre 1 solo recibo -->
                    <div v-if="!pago.invoices || pago.invoices.length <= 1" class="text-caption text-slate-500">
                      {{ pago.invoice?.numero_factura || 'Recibo' }} ({{ pago.invoice?.periodo }})
                    </div>

                    <!-- Si cubre N recibos (Pago Múltiple) -->
                    <div v-else class="mt-1">
                      <v-chip size="x-small" color="primary" variant="tonal" class="font-weight-bold mb-1">
                        📋 {{ pago.invoices.length }} Recibos Cubiertos
                      </v-chip>
                      <div v-for="inv in pago.invoices" :key="inv.id" class="d-flex align-center gap-1 text-caption">
                        <a :href="`/api/v1/reportes/recibo-invoice/${inv.id}`" target="_blank" class="text-primary font-weight-bold text-decoration-none hover:underline d-inline-flex align-center">
                          <v-icon icon="mdi-file-document-outline" size="12" class="mr-0.5" />
                          {{ inv.numero_factura }}
                        </a>
                        <span class="text-slate-500 font-mono">({{ inv.periodo }})</span>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div v-if="pago.metodo_pago === 'nota_credito'">
                      <v-chip size="x-small" color="teal-darken-2" variant="flat" class="font-weight-bold">
                        🎟️ Nota de Crédito
                      </v-chip>
                      <div class="text-caption text-teal-800 font-mono font-weight-medium mt-0.5">
                        {{ pago.referencia || 'Saldo a favor' }}
                      </div>
                    </div>
                    <div v-else>
                      <div class="text-capitalize font-weight-medium">{{ (pago.metodo_pago || '').replace('_', ' ') }}</div>
                      <div class="text-caption text-slate-500">{{ pago.banco || '-' }}</div>
                    </div>
                  </td>
                  <td><code>{{ pago.referencia || 'S/R' }}</code></td>
                  <td class="font-weight-bold text-slate-900">
                    {{ authStore.formatMoney(pago.monto) }}
                  </td>
                  <td>
                    <v-chip
                      :color="pago.estado === 'aprobado' ? 'success' : (pago.estado === 'pendiente' ? 'warning' : 'error')"
                      size="x-small"
                      variant="flat"
                      class="text-capitalize font-weight-bold"
                    >
                      {{ pago.estado === 'aprobado' ? '✓ Aprobado' : pago.estado }}
                    </v-chip>
                    <div v-if="pago.numero_recibo_pago" class="text-caption font-weight-bold text-primary font-mono mt-1">
                      {{ pago.numero_recibo_pago }}
                    </div>
                  </td>
                  <td class="text-right">
                    <!-- Botón Ver Detalles del Pago -->
                    <v-btn icon="mdi-eye" size="small" variant="tonal" color="primary" class="mr-1" title="Ver Detalles Completos del Pago" @click="openViewPaymentDialog(pago)" />

                    <!-- Botón Previsualizar Borrador RP (PDF) para Administradores en pagos pendientes -->
                    <v-btn
                      v-if="!authStore.isPropietario && pago.estado === 'pendiente'"
                      icon="mdi-file-eye-outline"
                      size="small"
                      variant="tonal"
                      color="indigo"
                      class="mr-1"
                      title="Previsualizar Borrador de Recibo RP (PDF)"
                      @click="openPreviewRPDialog(pago)"
                    />

                    <!-- Acciones para Administrador en Pagos No Aprobados (Editar, Aprobar, Rechazar) -->
                    <template v-if="!authStore.isPropietario && pago.estado !== 'aprobado'">
                      <v-btn icon="mdi-pencil" size="small" variant="tonal" color="warning" class="mr-1" title="Editar Registro de Pago" @click="openEditPaymentDialog(pago)" />
                      <v-btn v-if="pago.estado === 'pendiente'" icon="mdi-check" size="small" variant="tonal" color="success" class="mr-1" title="Aprobar Pago y Generar Recibo RP" @click="approvePayment(pago.id)" />
                      <v-btn icon="mdi-close" size="small" variant="tonal" color="error" class="mr-1" title="Rechazar Pago con Justificación" @click="openRejectDialog(pago.id)" />
                    </template>

                    <!-- Botón Comprobante RP Oficial (PDF) -->
                    <v-btn
                      v-if="pago.estado === 'aprobado'"
                      icon="mdi-file-pdf-box"
                      size="small"
                      variant="tonal"
                      color="error"
                      class="mr-1"
                      :href="`/api/v1/reportes/recibo/${pago.id}`"
                      target="_blank"
                      title="Descargar Comprobante RP Oficial de Pago (PDF)"
                    />

                    <!-- Botón Reenviar Comprobante RP por Correo (Exclusivo Administrador) -->
                    <v-btn
                      v-if="!authStore.isPropietario && pago.estado === 'aprobado'"
                      icon="mdi-email-send-outline"
                      size="small"
                      variant="tonal"
                      color="primary"
                      class="mr-1"
                      :loading="resendingEmailId === pago.id"
                      title="Reenviar Comprobante RP por Correo Electrónico"
                      @click="resendPaymentReceiptEmail(pago.id)"
                    />

                    <!-- Botones PDF individuales para cada recibo cubierto -->
                    <template v-if="pago.invoices && pago.invoices.length > 0">
                      <v-btn
                        v-for="inv in pago.invoices"
                        :key="'tb-pdf-' + inv.id"
                        icon="mdi-file-document-outline"
                        size="small"
                        variant="text"
                        color="primary"
                        class="mr-0.5"
                        :href="`/api/v1/reportes/recibo-invoice/${inv.id}`"
                        target="_blank"
                        :title="`Descargar PDF Recibo: ${inv.numero_factura} (${inv.periodo})`"
                      />
                    </template>
                    <template v-else-if="pago.invoice_id">
                      <v-btn
                        icon="mdi-file-document-outline"
                        size="small"
                        variant="text"
                        color="primary"
                        class="mr-0.5"
                        :href="`/api/v1/reportes/recibo-invoice/${pago.invoice_id}`"
                        target="_blank"
                        :title="`Descargar PDF del Recibo ${pago.invoice?.numero_factura || ''}`"
                      />
                    </template>
                  </td>
                </tr>
                <tr v-if="!paginatedItems.length">
                  <td colspan="7" class="text-center py-6 text-slate-400">
                    {{ search ? 'No se encontraron pagos que coincidan con la búsqueda.' : 'No se encontraron pagos registrados.' }}
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

      <!-- ===== Tab 2: NOTAS DE CRÉDITO & SALDOS A FAVOR ===== -->
      <v-window-item value="notas_credito">
        <v-card class="bg-white border border-slate-100 rounded-xl">
          <DataTableHeader
            v-model:search="pNotas.search.value"
            v-model:per-page="pNotas.perPage.value"
            :per-page-options="perPageOptions"
            placeholder="Buscar por N° nota de crédito, apartamento, copropietario..."
          />

          <div class="table-responsive-container">
            <v-table density="comfortable" hover style="min-width: 640px;">
              <thead>
                <tr class="bg-slate-50 text-slate-700">
                  <SortHeader col-key="fecha_emision" width="120px" :sort-by="pNotas.sortBy.value" :sort-desc="pNotas.sortDesc.value" @sort="pNotas.sort">
                    Fecha Emisión
                  </SortHeader>
                  <SortHeader col-key="inmueble" width="180px" :sort-by="pNotas.sortBy.value" :sort-desc="pNotas.sortDesc.value" @sort="pNotas.sort">
                    Inmueble / Copropietario
                  </SortHeader>
                  <SortHeader col-key="numero_nota_credito" width="150px" :sort-by="pNotas.sortBy.value" :sort-desc="pNotas.sortDesc.value" @sort="pNotas.sort">
                    N° Nota de Crédito
                  </SortHeader>
                  <SortHeader col-key="recibo" width="150px" :sort-by="pNotas.sortBy.value" :sort-desc="pNotas.sortDesc.value" @sort="pNotas.sort">
                    Recibo Origen
                  </SortHeader>
                  <SortHeader col-key="monto_original" width="130px" :sort-by="pNotas.sortBy.value" :sort-desc="pNotas.sortDesc.value" @sort="pNotas.sort">
                    Monto Original
                  </SortHeader>
                  <SortHeader col-key="monto_disponible" width="150px" :sort-by="pNotas.sortBy.value" :sort-desc="pNotas.sortDesc.value" @sort="pNotas.sort">
                    Saldo a Favor
                  </SortHeader>
                  <SortHeader col-key="estado" width="100px" :sort-by="pNotas.sortBy.value" :sort-desc="pNotas.sortDesc.value" @sort="pNotas.sort">
                    Estado
                  </SortHeader>
                  <th class="text-right" style="width: 90px;">Detalle</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="nc in pNotas.paginatedItems.value" :key="nc.id">
                  <td>{{ formatDate(nc.fecha_emision) }}</td>
                  <td>
                    <div class="font-weight-bold text-slate-900">
                      Apartamento {{ nc.apartamento?.numero || '-' }}
                    </div>
                    <div class="text-caption text-slate-500">
                      {{ nc.apartamento?.propietarios?.[0]?.nombre_completo || 'Copropietario' }}
                    </div>
                  </td>
                  <td>
                    <v-chip size="small" color="teal-darken-2" variant="tonal" class="font-weight-bold font-mono">
                      {{ nc.numero_nota_credito }}
                    </v-chip>
                  </td>
                  <td>
                    <div class="text-caption font-mono font-weight-bold text-slate-700">
                      {{ nc.origen_pago?.numero_recibo_pago || 'Pago #' + (nc.payment_id || '-') }}
                    </div>
                    <div class="text-caption text-slate-500">
                      Tasa emisión: Bs. {{ Number(nc.tasa_cambio || 36.50).toFixed(2) }}
                    </div>
                  </td>
                  <td>
                    <div class="font-weight-bold text-slate-900">
                      Bs. {{ Number(nc.monto_original).toLocaleString('es-VE', { minimumFractionDigits: 2 }) }}
                    </div>
                    <div class="text-caption text-slate-500 font-mono">
                      ${{ Number(nc.monto_original_usd).toFixed(2) }} USD
                    </div>
                  </td>
                  <td>
                    <div class="font-weight-bold" :class="Number(nc.monto_disponible) > 0 ? 'text-emerald-700' : 'text-slate-500'">
                      Bs. {{ Number(nc.monto_disponible).toLocaleString('es-VE', { minimumFractionDigits: 2 }) }}
                    </div>
                    <div class="text-caption font-mono font-weight-medium" :class="Number(nc.monto_disponible_usd) > 0 ? 'text-emerald-600' : 'text-slate-400'">
                      ${{ Number(nc.monto_disponible_usd).toFixed(2) }} USD
                    </div>
                  </td>
                  <td>
                    <v-chip
                      :color="nc.estado === 'disponible' ? 'success' : (nc.estado === 'parcial' ? 'warning' : 'default')"
                      size="x-small"
                      variant="flat"
                      class="text-uppercase font-weight-bold"
                    >
                      {{ nc.estado }}
                    </v-chip>
                  </td>
                  <td class="text-right">
                    <div class="d-flex align-center justify-end gap-1">
                      <v-btn
                        icon="mdi-file-pdf-box"
                        size="small"
                        variant="tonal"
                        color="error"
                        title="Descargar Nota de Crédito en PDF"
                        :href="'/api/v1/reportes/nota-credito/' + nc.id"
                        target="_blank"
                      />
                      <v-btn
                        icon="mdi-eye"
                        size="small"
                        variant="tonal"
                        color="teal-darken-2"
                        title="Ver Detalles y Aplicaciones de la Nota de Crédito"
                        @click="openViewCreditNoteDialog(nc)"
                      />
                    </div>
                  </td>
                </tr>
                <tr v-if="!pNotas.paginatedItems.value.length">
                  <td colspan="8" class="text-center py-6 text-slate-400">
                    {{ pNotas.search.value ? 'No se encontraron notas de crédito que coincidan con la búsqueda.' : 'No hay notas de crédito registradas.' }}
                  </td>
                </tr>
              </tbody>
            </v-table>
          </div>

          <DataTableFooter
            v-model:current-page="pNotas.currentPage.value"
            :total-pages="pNotas.totalPages.value"
            :total-items="pNotas.totalItems.value"
            :original-total="pNotas.originalTotal.value"
            :start-index="pNotas.startIndex.value"
            :end-index="pNotas.endIndex.value"
            :search="pNotas.search.value"
          />
        </v-card>
      </v-window-item>
    </v-window>

    <!-- Dialog for Registering / Notifying Payment -->
    <v-dialog v-model="dialog" max-width="720" scrollable :fullscreen="$vuetify.display.xs" persistent>
      <v-card class="rounded-xl overflow-hidden border border-slate-100 shadow-2xl d-flex flex-column" style="max-height: 92vh;">
        <!-- Header del Modal con gradiente elegante -->
        <div class="bg-slate-900 text-white pa-4 pa-sm-5 d-flex align-center justify-space-between flex-shrink-0">
          <div class="d-flex align-center gap-3">
            <v-avatar color="primary" size="42" class="elevation-2">
              <v-icon icon="mdi-cash-fast" color="white" size="24" />
            </v-avatar>
            <div>
              <div class="text-subtitle-1 font-weight-bold leading-tight">
                {{ isEditingPayment ? 'Editar Registro de Pago' : (authStore.isPropietario ? 'Notificar Pago Realizado' : 'Registrar Pago en Nombre de Copropietario') }}
              </div>
              <div class="text-caption text-slate-300">
                {{ isEditingPayment ? 'Modifica la información registrada de la transacción' : 'Selecciona los recibos, el método de pago y completa los datos de la transacción' }}
              </div>
            </div>
          </div>
          <v-btn icon="mdi-close" variant="text" color="white" density="comfortable" @click="dialog = false" />
        </div>

        <v-card-text class="pa-4 pa-sm-5 overflow-y-auto" style="max-height: 72vh;">
          <template v-if="!invoicesSelect.length && !isEditingPayment && authStore.isPropietario">
            <v-alert type="info" variant="tonal" class="rounded-xl mb-0 font-weight-medium">
              <v-icon start icon="mdi-check-circle-outline" />
              No tienes recibos pendientes de cobro en este momento. ¡Tu cuenta se encuentra al día!
            </v-alert>
          </template>

          <v-form v-else @submit.prevent="submitPayment">
            <!-- 1. SELECTOR DE APARTAMENTO (Exclusivo para Administrador) -->
            <div v-if="!authStore.isPropietario && !isEditingPayment" class="mb-4">
              <label class="text-caption font-weight-bold text-slate-700 text-uppercase tracking-wider d-block mb-1">
                🏠 1. Seleccionar Inmueble / Copropietario *
              </label>
              <v-autocomplete
                v-model="selectedAdminApartamentoId"
                :items="apartamentosOptions"
                item-title="label"
                item-value="id"
                placeholder="Buscar por número de apartamento o nombre del propietario..."
                variant="outlined"
                density="comfortable"
                clearable
                hide-details
                color="primary"
                class="bg-slate-50 rounded-lg font-weight-medium"
                @update:model-value="onAdminApartamentoChange"
              />
            </div>

            <!-- 2. SELECTOR MÚLTIPLE DE RECIBOS PENDIENTES -->
            <div class="mb-4">
              <div class="d-flex justify-space-between align-center mb-1">
                <label class="text-caption font-weight-bold text-slate-700 text-uppercase tracking-wider">
                  📋 {{ authStore.isPropietario ? '1.' : '2.' }} Recibos de Cobro a Saldar (Selección Múltiple) *
                </label>
                <v-btn
                  v-if="displayedPendingInvoices.length > 1 && (authStore.isPropietario || selectedAdminApartamentoId)"
                  size="x-small"
                  variant="text"
                  color="primary"
                  class="font-weight-bold px-1"
                  @click="toggleAdminSelectAllInvoices"
                >
                  {{ selectedAdminInvoiceIds.length === displayedPendingInvoices.length ? 'Deseleccionar Todos' : 'Seleccionar Todos' }}
                </v-btn>
              </div>

              <!-- Si es administrador y aún no ha seleccionado ningún apartamento -->
              <div v-if="!authStore.isPropietario && !selectedAdminApartamentoId" class="border border-dashed border-slate-300 rounded-xl pa-5 text-center bg-slate-50 text-slate-500">
                <v-icon icon="mdi-home-search-outline" size="32" color="slate-400" class="mb-1 d-block mx-auto" />
                <div class="text-caption font-weight-bold text-slate-700">Por favor selecciona un Inmueble / Copropietario arriba</div>
                <div class="text-caption text-xs text-slate-400">Los recibos de cobro pendientes se cargarán y vendrán preseleccionados automáticamente.</div>
              </div>

              <!-- Si ya está seleccionado el apartamento o es propietario -->
              <div v-else class="border border-slate-200 rounded-xl pa-2 bg-slate-50 overflow-y-auto" style="max-height: 170px;">
                <div
                  v-for="inv in displayedPendingInvoices"
                  :key="inv.id"
                  class="d-flex justify-space-between align-center pa-2.5 rounded-lg bg-white border border-slate-200 mb-1.5 cursor-pointer hover:border-primary transition-all"
                  @click="toggleAdminInvoiceChoice(inv.id)"
                >
                  <div class="d-flex align-center gap-2">
                    <v-checkbox-btn
                      :model-value="selectedAdminInvoiceIds.includes(inv.id)"
                      color="primary"
                      density="compact"
                      class="ma-0 pa-0"
                      @update:model-value="toggleAdminInvoiceChoice(inv.id)"
                      @click.stop
                    />
                    <div>
                      <div class="text-caption font-weight-bold text-slate-900">
                        Apto. {{ inv.apartamento?.numero || '-' }} • {{ inv.periodo }}
                        <span class="text-primary font-mono font-weight-bold">({{ inv.numero_factura || 'RI-Recibo' }})</span>
                      </div>
                      <div class="text-caption text-slate-500">
                        {{ inv.apartamento?.propietarios?.[0]?.nombre_completo || 'Copropietario' }}
                      </div>
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="text-caption font-weight-bold text-primary font-mono">
                      Bs. {{ formatMoneyBs(getInvoiceSaldoBs(inv)) }}
                    </div>
                    <div class="text-caption text-slate-500 font-mono">
                      ${{ (getInvoiceTotalUsd(inv) * (getInvoiceSaldoBs(inv) / (getInvoiceTotalBs(inv) || 1))).toFixed(2) }} USD
                    </div>
                  </div>
                </div>

                <div v-if="!displayedPendingInvoices.length" class="text-center py-4 text-caption text-slate-400">
                  {{ selectedAdminApartamentoId ? 'Este apartamento se encuentra al día. No tiene recibos pendientes por saldar.' : 'No hay recibos pendientes de cobro.' }}
                </div>
              </div>
            </div>

            <!-- Resumen del Saldo Acumulado -->
            <div v-if="selectedAdminInvoiceIds.length" class="bg-blue-50 border border-blue-200 rounded-xl pa-3 mb-4 text-caption text-blue-900">
              <div class="d-flex justify-space-between align-center mb-1">
                <span class="text-slate-700 font-weight-medium">Recibos Seleccionados: <strong>{{ selectedAdminInvoiceIds.length }}</strong></span>
                <v-chip size="x-small" color="primary" variant="flat" class="font-weight-bold">
                  Total Deuda Seleccionada
                </v-chip>
              </div>
              <div class="d-flex justify-space-between align-center font-weight-bold text-subtitle-2 text-primary">
                <span>Monto Total a Saldar:</span>
                <span>
                  Bs. {{ formatMoneyBs(adminTotalSelectedBs) }}
                  <span class="text-caption font-weight-normal text-slate-600">
                    (${{ adminTotalSelectedUsd.toFixed(2) }} USD)
                  </span>
                </span>
              </div>
            </div>

            <!-- 3. SELECTOR VISUAL DE MÉTODOS DE PAGO (Tarjetas Interactivas) -->
            <div class="mb-4">
              <label class="text-caption font-weight-bold text-slate-700 text-uppercase tracking-wider d-block mb-2">
                💳 {{ authStore.isPropietario ? '2.' : '3.' }} Selecciona el Método de Pago Utilizado *
              </label>
              <v-row density="compact">
                <v-col cols="6" sm="4" v-for="metodo in metodosPagoDisponibles" :key="metodo.value">
                  <div
                    class="pa-3 rounded-xl border text-center cursor-pointer transition-all h-100 d-flex flex-column align-center justify-center ga-1"
                    :class="form.metodo_pago === metodo.value ? 'bg-primary text-white border-primary shadow-md elevation-1' : 'bg-white text-slate-700 border-slate-200 hover:border-primary hover:bg-slate-50'"
                    @click="onSelectMetodoPago(metodo.value)"
                  >
                    <v-icon :icon="metodo.icon" :color="form.metodo_pago === metodo.value ? 'white' : metodo.iconColor" size="24" />
                    <span class="text-caption font-weight-bold leading-tight">{{ metodo.title }}</span>
                    <span class="text-caption text-xs" :class="form.metodo_pago === metodo.value ? 'text-white opacity-90' : 'text-slate-400'">
                      {{ metodo.subtitle }}
                    </span>
                  </div>
                </v-col>
              </v-row>
            </div>

            <!-- 4. BLOQUE INFORMATIVO DE DESTINO (Datos del Condominio con Botón de Copiado) -->
            <!-- Caso Pago Móvil -->
            <div v-if="form.metodo_pago === 'pago_movil'" class="mb-4 bg-deep-purple-50 border border-deep-purple-200 rounded-xl pa-3.5">
              <div class="d-flex justify-space-between align-center mb-2">
                <div class="d-flex align-center gap-2">
                  <v-icon icon="mdi-cellphone-check" color="deep-purple-darken-2" size="20" />
                  <span class="text-caption font-weight-bold text-deep-purple-900 text-uppercase">
                    Datos de Pago Móvil del Condominio
                  </span>
                </div>
                <v-btn
                  size="x-small"
                  color="deep-purple-darken-2"
                  variant="flat"
                  prepend-icon="mdi-content-copy"
                  class="font-weight-bold"
                  @click="copyPagoMovilData"
                >
                  Copiar Datos
                </v-btn>
              </div>

              <!-- Selector si hay múltiples cuentas de pago móvil -->
              <div v-if="pagoMovilCuentas.length > 1" class="mb-2">
                <v-select
                  v-model="form.cuenta_bancaria_id"
                  :items="pagoMovilCuentas"
                  item-title="banco_nombre"
                  item-value="id"
                  label="Seleccionar Canal Pago Móvil Destino"
                  variant="outlined"
                  density="compact"
                  hide-details
                  class="bg-white rounded"
                />
              </div>

              <div class="bg-white pa-3 rounded-lg border border-deep-purple-100 text-caption text-slate-800 d-flex flex-column gap-1">
                <div>Banco: <strong class="text-deep-purple-900">{{ currentPagoMovilDestino.banco }}</strong></div>
                <div>Teléfono: <strong class="font-mono text-deep-purple-900">{{ currentPagoMovilDestino.telefono }}</strong></div>
                <div>RIF / Cédula: <strong class="font-mono text-deep-purple-900">{{ currentPagoMovilDestino.rif }}</strong></div>
                <div>Titular: <strong>{{ currentPagoMovilDestino.titular }}</strong></div>
              </div>
            </div>

            <!-- Caso Transferencia Bancaria -->
            <div v-else-if="form.metodo_pago === 'transferencia'" class="mb-4 bg-blue-50 border border-blue-200 rounded-xl pa-3.5">
              <div class="d-flex justify-space-between align-center mb-2">
                <div class="d-flex align-center gap-2">
                  <v-icon icon="mdi-bank-outline" color="blue-darken-2" size="20" />
                  <span class="text-caption font-weight-bold text-blue-900 text-uppercase">
                    Cuenta Bancaria Receptora del Condominio
                  </span>
                </div>
                <v-btn
                  size="x-small"
                  color="blue-darken-2"
                  variant="flat"
                  prepend-icon="mdi-content-copy"
                  class="font-weight-bold"
                  @click="copyCuentaBancariaData"
                >
                  Copiar N° Cuenta
                </v-btn>
              </div>

              <!-- Selector de Cuenta Bancaria del Condominio -->
              <div v-if="transferenciaCuentas.length" class="mb-2">
                <v-select
                  v-model="form.cuenta_bancaria_id"
                  :items="transferenciaCuentas"
                  :item-title="item => `${item.banco_nombre} - ${item.tipo_cuenta} (${item.moneda})`"
                  item-value="id"
                  label="Cuenta del Edificio donde se recibió la transferencia"
                  variant="outlined"
                  density="compact"
                  hide-details
                  class="bg-white rounded"
                />
              </div>

              <div class="bg-white pa-3 rounded-lg border border-blue-100 text-caption text-slate-800 d-flex flex-column gap-1">
                <div>Banco: <strong class="text-blue-900">{{ currentTransferenciaDestino.banco }}</strong></div>
                <div>N° Cuenta: <strong class="font-mono text-blue-900 font-weight-bold">{{ currentTransferenciaDestino.numero }}</strong></div>
                <div>RIF / Titular: <strong class="font-mono">{{ currentTransferenciaDestino.rif }}</strong> — {{ currentTransferenciaDestino.titular }}</div>
              </div>
            </div>

            <!-- Caso Efectivo en Divisas -->
            <div v-else-if="form.metodo_pago === 'efectivo_usd'" class="mb-4 bg-emerald-50 border border-emerald-200 rounded-xl pa-3.5">
              <div class="d-flex align-center gap-2 mb-2">
                <v-icon icon="mdi-currency-usd" color="emerald-darken-2" size="20" />
                <span class="text-caption font-weight-bold text-emerald-900 text-uppercase">
                  Recepción de Efectivo en Divisas ($ USD / EUR)
                </span>
              </div>
              <div class="bg-white pa-3 rounded-lg border border-emerald-100 text-caption text-slate-800">
                <div>Lugar de Entrega: <strong>{{ currentEfectivoUsdDestino.banco_nombre || 'Oficina de Administración / Conserjería' }}</strong></div>
                <div class="text-slate-600 mt-0.5">
                  💬 {{ currentEfectivoUsdDestino.instrucciones || 'El dinero físico debe ser entregado al personal autorizado del edificio. El recibo se emitirá al recibir los billetes.' }}
                </div>
              </div>
            </div>

            <!-- Caso Efectivo en Bolívares -->
            <div v-else-if="form.metodo_pago === 'efectivo_ves'" class="mb-4 bg-amber-50 border border-amber-200 rounded-xl pa-3.5">
              <div class="d-flex align-center gap-2 mb-2">
                <v-icon icon="mdi-cash" color="amber-darken-3" size="20" />
                <span class="text-caption font-weight-bold text-amber-900 text-uppercase">
                  Recepción de Efectivo en Bolívares (Bs. VES)
                </span>
              </div>
              <div class="bg-white pa-3 rounded-lg border border-amber-100 text-caption text-slate-800">
                <div>Lugar de Entrega: <strong>{{ currentEfectivoVesDestino.banco_nombre || 'Caja de Administración / Conserjería' }}</strong></div>
                <div class="text-slate-600 mt-0.5">
                  💬 {{ currentEfectivoVesDestino.instrucciones || 'Entrega física en moneda nacional en caja del condominio.' }}
                </div>
              </div>
            </div>

            <!-- Caso Zelle / Otros -->
            <div v-else-if="form.metodo_pago === 'zelle'" class="mb-4 bg-purple-50 border border-purple-200 rounded-xl pa-3.5">
              <div class="d-flex justify-space-between align-center mb-2">
                <div class="d-flex align-center gap-2">
                  <v-icon icon="mdi-send-circle" color="purple-darken-2" size="20" />
                  <span class="text-caption font-weight-bold text-purple-900 text-uppercase">
                    Datos Zelle del Condominio
                  </span>
                </div>
                <v-btn
                  size="x-small"
                  color="purple-darken-2"
                  variant="flat"
                  prepend-icon="mdi-content-copy"
                  class="font-weight-bold"
                  @click="copyZelleData"
                >
                  Copiar Correo
                </v-btn>
              </div>
              <div class="bg-white pa-3 rounded-lg border border-purple-100 text-caption text-slate-800">
                <div>Correo Zelle: <strong class="font-mono text-purple-900">{{ currentZelleDestino.correo }}</strong></div>
                <div>Titular: <strong>{{ currentZelleDestino.titular }}</strong></div>
              </div>
            </div>

            <!-- 5. FORMULARIO ADAPTATIVO DE MONTOS Y DATOS DE ORIGEN (EMISOR) -->
            <!-- Montos según Moneda (Efectivo Divisas vs Bolívares) -->
            <div v-if="form.metodo_pago === 'efectivo_usd' || form.metodo_pago === 'zelle'" class="mb-4">
              <v-row density="compact">
                <v-col cols="12" sm="6">
                  <label class="text-caption font-weight-bold text-slate-700 text-uppercase tracking-wider d-block mb-1">
                    Monto en Divisas ($ USD) *
                  </label>
                  <v-text-field
                    v-model="form.monto_divisa"
                    type="number"
                    step="0.01"
                    required
                    variant="outlined"
                    density="comfortable"
                    prefix="$"
                    hide-details
                    color="primary"
                    class="rounded-lg font-weight-bold font-mono"
                    @update:model-value="onMontoDivisaInput"
                  />
                </v-col>
                <v-col cols="12" sm="6">
                  <label class="text-caption font-weight-bold text-slate-700 text-uppercase tracking-wider d-block mb-1">
                    Contravalor Contable en Bolívares (Bs.)
                  </label>
                  <v-text-field
                    v-model="form.monto"
                    type="number"
                    step="0.01"
                    required
                    readonly
                    prepend-inner-icon="mdi-lock"
                    variant="outlined"
                    density="comfortable"
                    prefix="Bs."
                    :hint="'🔒 Calculado exactamente a tasa oficial BCV (Bs. ' + activeTasaCambio.toFixed(2) + ')'"
                    persistent-hint
                    color="primary"
                    class="rounded-lg font-weight-bold font-mono bg-slate-50"
                  />
                </v-col>
              </v-row>
              <div class="text-caption text-slate-500 mt-1 d-flex justify-space-between">
                <span>Tasa Oficial BCV: <strong>Bs. {{ activeTasaCambio.toFixed(2) }}</strong></span>
                <span class="text-emerald-700 font-weight-bold">
                  Recibiendo: ${{ Number(form.monto_divisa || 0).toFixed(2) }} USD = Bs. {{ formatMoneyBs(form.monto) }}
                </span>
              </div>
            </div>

            <div v-else class="mb-4">
              <label class="text-caption font-weight-bold text-slate-700 text-uppercase tracking-wider d-block mb-1">
                Monto Reportado (Bs.) *
              </label>
              <v-text-field
                v-model="form.monto"
                type="number"
                step="0.01"
                required
                variant="outlined"
                density="comfortable"
                prefix="Bs."
                hide-details
                color="primary"
                class="rounded-lg font-weight-bold font-mono"
                @update:model-value="onMontoBsInput"
              />
              <div class="text-caption text-slate-500 mt-1 d-flex justify-space-between">
                <span>Equivalente referencial BCV:</span>
                <strong class="text-primary font-weight-bold">
                  ${{ (Number(form.monto || 0) / activeTasaCambio).toFixed(2) }} USD
                  <span class="text-slate-400 font-weight-normal">(Tasa: Bs. {{ activeTasaCambio.toFixed(2) }})</span>
                </strong>
              </div>
            </div>

            <!-- Campos de Transacción según Método -->
            <!-- Caso Pago Móvil -->
            <template v-if="form.metodo_pago === 'pago_movil'">
              <v-row density="compact" class="mb-2">
                <v-col cols="12" sm="6">
                  <label class="text-caption font-weight-bold text-slate-700 text-uppercase tracking-wider d-block mb-1">
                    Banco Emisor (Desde dónde pagaste) *
                  </label>
                  <v-combobox
                    v-model="form.banco_origen"
                    :items="bancosCatalogoList"
                    placeholder="Ej: Banco de Venezuela"
                    variant="outlined"
                    density="comfortable"
                    hide-details
                    color="primary"
                  />
                </v-col>
                <v-col cols="12" sm="6">
                  <label class="text-caption font-weight-bold text-slate-700 text-uppercase tracking-wider d-block mb-1">
                    Teléfono Emisor del Pago Móvil *
                  </label>
                  <v-text-field
                    v-model="form.telefono_origen"
                    placeholder="Ej: 0412-1234567"
                    required
                    variant="outlined"
                    density="comfortable"
                    hide-details
                    color="primary"
                    class="font-mono"
                  />
                </v-col>
              </v-row>

              <v-row density="compact" class="mb-2">
                <v-col cols="12" sm="6">
                  <label class="text-caption font-weight-bold text-slate-700 text-uppercase tracking-wider d-block mb-1">
                    N° de Referencia / Aprobación *
                  </label>
                  <v-text-field
                    v-model="form.referencia"
                    placeholder="Ej: 489201"
                    required
                    variant="outlined"
                    density="comfortable"
                    hide-details
                    color="primary"
                    class="font-mono"
                  />
                </v-col>
                <v-col cols="12" sm="6">
                  <label class="text-caption font-weight-bold text-slate-700 text-uppercase tracking-wider d-block mb-1">
                    Fecha del Pago *
                  </label>
                  <v-text-field
                    v-model="form.fecha_pago"
                    type="date"
                    required
                    variant="outlined"
                    density="comfortable"
                    hide-details
                    color="primary"
                  />
                </v-col>
              </v-row>
            </template>

            <!-- Caso Transferencia Bancaria -->
            <template v-else-if="form.metodo_pago === 'transferencia'">
              <v-row density="compact" class="mb-2">
                <v-col cols="12" sm="6">
                  <label class="text-caption font-weight-bold text-slate-700 text-uppercase tracking-wider d-block mb-1">
                    Banco Emisor (Desde dónde transferiste) *
                  </label>
                  <v-combobox
                    v-model="form.banco_origen"
                    :items="bancosCatalogoList"
                    placeholder="Ej: Mercantil / Banesco"
                    variant="outlined"
                    density="comfortable"
                    hide-details
                    color="primary"
                  />
                </v-col>
                <v-col cols="12" sm="6">
                  <label class="text-caption font-weight-bold text-slate-700 text-uppercase tracking-wider d-block mb-1">
                    Número de Transferencia / Referencia *
                  </label>
                  <v-text-field
                    v-model="form.referencia"
                    placeholder="Ej: 009848291"
                    required
                    variant="outlined"
                    density="comfortable"
                    hide-details
                    color="primary"
                    class="font-mono"
                  />
                </v-col>
              </v-row>

              <v-row density="compact" class="mb-2">
                <v-col cols="12" sm="6">
                  <label class="text-caption font-weight-bold text-slate-700 text-uppercase tracking-wider d-block mb-1">
                    Fecha de la Transferencia *
                  </label>
                  <v-text-field
                    v-model="form.fecha_pago"
                    type="date"
                    required
                    variant="outlined"
                    density="comfortable"
                    hide-details
                    color="primary"
                  />
                </v-col>
              </v-row>
            </template>

            <!-- Caso Efectivo (Divisas o Bolívares) -->
            <template v-else-if="form.metodo_pago === 'efectivo_usd' || form.metodo_pago === 'efectivo_ves'">
              <v-row density="compact" class="mb-2">
                <v-col cols="12" sm="6">
                  <label class="text-caption font-weight-bold text-slate-700 text-uppercase tracking-wider d-block mb-1">
                    Fecha de Entrega del Efectivo *
                  </label>
                  <v-text-field
                    v-model="form.fecha_pago"
                    type="date"
                    required
                    variant="outlined"
                    density="comfortable"
                    hide-details
                    color="primary"
                  />
                </v-col>
                <v-col cols="12" sm="6">
                  <label class="text-caption font-weight-bold text-slate-700 text-uppercase tracking-wider d-block mb-1">
                    Persona que Entrega / Receptor
                  </label>
                  <v-text-field
                    v-model="form.referencia"
                    placeholder="Ej: Entregado por el copropietario a administración"
                    variant="outlined"
                    density="comfortable"
                    hide-details
                    color="primary"
                  />
                </v-col>
              </v-row>
            </template>

            <!-- Caso Zelle / Otros -->
            <template v-else-if="form.metodo_pago === 'zelle'">
              <v-row density="compact" class="mb-2">
                <v-col cols="12" sm="6">
                  <label class="text-caption font-weight-bold text-slate-700 text-uppercase tracking-wider d-block mb-1">
                    Correo Emisor / Titular Zelle *
                  </label>
                  <v-text-field
                    v-model="form.banco_origen"
                    placeholder="Ej: titular@email.com"
                    variant="outlined"
                    density="comfortable"
                    hide-details
                    color="primary"
                  />
                </v-col>
                <v-col cols="12" sm="6">
                  <label class="text-caption font-weight-bold text-slate-700 text-uppercase tracking-wider d-block mb-1">
                    Código de Confirmación Zelle *
                  </label>
                  <v-text-field
                    v-model="form.referencia"
                    placeholder="Ej: ZEL-94829"
                    required
                    variant="outlined"
                    density="comfortable"
                    hide-details
                    color="primary"
                    class="font-mono"
                  />
                </v-col>
              </v-row>
              <v-row density="compact" class="mb-2">
                <v-col cols="12" sm="6">
                  <label class="text-caption font-weight-bold text-slate-700 text-uppercase tracking-wider d-block mb-1">
                    Fecha del Envío Zelle *
                  </label>
                  <v-text-field
                    v-model="form.fecha_pago"
                    type="date"
                    required
                    variant="outlined"
                    density="comfortable"
                    hide-details
                    color="primary"
                  />
                </v-col>
              </v-row>
            </template>

            <!-- Adjuntar Comprobante (Para métodos digitales) -->
            <div v-if="['pago_movil', 'transferencia', 'zelle'].includes(form.metodo_pago)" class="mb-3">
              <label class="text-caption font-weight-bold text-slate-700 text-uppercase tracking-wider d-block mb-1">
                Adjuntar Comprobante Digital (Captura de Pantalla o PDF)
              </label>
              <v-file-input
                v-model="comprobanteFile"
                accept="image/png, image/jpeg, image/jpg, application/pdf"
                prepend-icon="mdi-paperclip"
                placeholder="Selecciona el comprobante..."
                variant="outlined"
                density="comfortable"
                hide-details
                color="primary"
                show-size
              />
            </div>

            <!-- Observaciones / Notas -->
            <div class="mb-3">
              <label class="text-caption font-weight-bold text-slate-700 text-uppercase tracking-wider d-block mb-1">
                Observaciones Adicionales
              </label>
              <v-textarea
                v-model="form.observaciones"
                placeholder="Detalles adicionales sobre la transacción..."
                rows="2"
                variant="outlined"
                density="comfortable"
                hide-details
                color="primary"
              />
            </div>

            <!-- Opción para Administrador: Aprobación Inmediata -->
            <div v-if="!authStore.isPropietario && !isEditingPayment" class="bg-emerald-50 border border-emerald-200 pa-3 rounded-xl mb-2">
              <v-checkbox
                v-model="form.aprobar_inmediato"
                color="success"
                label="⚡ Aprobar y emitir Recibo Oficial RP inmediatamente"
                density="compact"
                hide-details
                class="ma-0 pa-0 font-weight-bold text-emerald-900"
              />
              <div class="text-caption text-emerald-800 ml-8">
                Marca esta opción si ya confirmaste los fondos en el banco o si recibiste el efectivo en mano. Generará el recibo contable de inmediato.
              </div>
            </div>
          </v-form>
        </v-card-text>

        <v-card-actions class="pa-4 bg-slate-50 d-flex justify-end gap-2 border-t border-slate-100">
          <v-btn variant="text" color="slate-600" class="font-weight-medium" @click="dialog = false">Cancelar</v-btn>
          <v-btn
            v-if="displayedPendingInvoices.length || isEditingPayment"
            color="primary"
            variant="flat"
            class="font-weight-bold px-5"
            :loading="saving"
            @click="submitPayment"
          >
            {{ isEditingPayment ? 'Guardar Cambios' : (authStore.isPropietario ? 'Enviar Notificación de Pago' : (form.aprobar_inmediato ? 'Registrar y Aprobar Pago' : 'Registrar Pago')) }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Modal para Visualizar Detalles Completos del Pago (Admin & Propietario) -->
    <v-dialog v-model="viewDialog" max-width="720" scrollable persistent>
      <v-card class="rounded-xl overflow-hidden border border-slate-100 shadow-2xl d-flex flex-column" style="max-height: 90vh;">
        <!-- Header del Modal -->
        <div class="bg-slate-900 text-white pa-4 d-flex align-center justify-space-between flex-shrink-0">
          <div class="d-flex align-center gap-3">
            <v-avatar color="primary" size="36" class="elevation-1">
              <v-icon icon="mdi-file-search-outline" color="white" size="20" />
            </v-avatar>
            <div>
              <div class="text-subtitle-1 font-weight-bold leading-tight">
                Detalles del Pago Notificado
              </div>
              <div class="text-caption text-slate-300">
                Resumen completo de la transacción para verificación bancaria
              </div>
            </div>
          </div>
          <v-btn icon="mdi-close" variant="text" color="white" density="comfortable" @click="viewDialog = false" />
        </div>

        <v-card-text class="pa-5 overflow-y-auto" style="max-height: 68vh;">
          <div v-if="selectedPayment" class="d-flex flex-column ga-4">
            
            <!-- 1. Hero Card: Monto & Estado (Resumen de Impacto Inmediato) -->
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl pa-4 d-flex flex-column flex-sm-row justify-space-between align-start align-sm-center ga-3">
              <div>
                <span class="text-caption font-weight-bold text-emerald-800 text-uppercase tracking-wider d-block mb-1">
                  Monto Reportado
                </span>
                <div class="text-h4 font-weight-black text-emerald-700 font-mono leading-none">
                  Bs. {{ formatMoneyBs(selectedPayment.monto) }}
                </div>
                <div class="text-caption font-weight-bold text-emerald-800 mt-1">
                  Equivalente: <span class="font-mono text-subtitle-2">${{ (selectedPayment.monto / (selectedPayment.tasa_cambio || 36.50)).toFixed(2) }} USD</span>
                  <span class="text-slate-500 font-weight-normal ml-1">(Tasa BCV: Bs. {{ selectedPayment.tasa_cambio || '36.50' }})</span>
                </div>
              </div>

              <div class="text-sm-right">
                <span class="text-caption font-weight-bold text-slate-500 text-uppercase d-block mb-1">Estado</span>
                <v-chip
                  :color="selectedPayment.estado === 'aprobado' ? 'success' : (selectedPayment.estado === 'pendiente' ? 'warning' : 'error')"
                  size="small"
                  variant="flat"
                  class="font-weight-bold text-uppercase px-3"
                >
                  {{ selectedPayment.estado === 'aprobado' ? '✓ Aprobado' : (selectedPayment.estado === 'pendiente' ? '⏳ Pendiente' : selectedPayment.estado) }}
                </v-chip>
                <div v-if="selectedPayment.numero_recibo_pago" class="text-caption font-weight-bold text-primary font-mono mt-1">
                  Recibo: {{ selectedPayment.numero_recibo_pago }}
                </div>
              </div>
            </div>

            <!-- Bloque Destacado de Justificación de Rechazo si el pago fue Rechazado -->
            <div v-if="selectedPayment.estado === 'rechazado' || selectedPayment.motivo_rechazo" class="bg-rose-50 border border-rose-200 pa-4 rounded-xl text-rose-900">
              <div class="font-weight-bold text-uppercase tracking-wider text-rose-900 d-flex align-center gap-2 mb-1">
                <v-icon icon="mdi-alert-circle" color="rose-700" size="20" />
                Motivo de Rechazo por Administración
              </div>
              <p class="text-body-2 font-italic text-rose-800 mb-0 font-weight-medium">
                "{{ selectedPayment.motivo_rechazo || selectedPayment.observaciones || 'La referencia o el monto notificado no concilia con la cuenta del banco.' }}"
              </p>
            </div>

            <!-- Bloque Destacado si el pago utilizó o generó Nota de Crédito -->
            <div v-if="selectedPayment.metodo_pago === 'nota_credito' || selectedPayment.credit_note_id" class="bg-teal-50 border border-teal-200 pa-4 rounded-xl text-teal-900">
              <div class="font-weight-bold text-uppercase tracking-wider text-teal-900 d-flex align-center gap-2 mb-1">
                <v-icon icon="mdi-cash-refund" color="teal-700" size="20" />
                Pago Financiado por Nota de Crédito
              </div>
              <p class="text-body-2 text-teal-800 mb-0 font-weight-medium">
                Este pago fue abonado automáticamente utilizando el saldo a favor disponible de la Nota de Crédito <strong>{{ selectedPayment.referencia || selectedPayment.credit_note?.numero_nota_credito }}</strong>.
              </p>
            </div>

            <div v-if="selectedPayment.nota_credito_generada" class="bg-emerald-50 border border-emerald-300 pa-4 rounded-xl text-emerald-950">
              <div class="font-weight-bold text-uppercase tracking-wider text-emerald-900 d-flex align-center gap-2 mb-1">
                <v-icon icon="mdi-check-decagram" color="emerald-700" size="20" />
                Excedente Generado: Nota de Crédito {{ selectedPayment.nota_credito_generada.numero_nota_credito }}
              </div>
              <p class="text-body-2 text-emerald-900 mb-0 font-weight-medium">
                Este pago superó la deuda requerida por un excedente de <strong>Bs. {{ formatMoneyBs(selectedPayment.nota_credito_generada.monto_original) }} (${{ Number(selectedPayment.nota_credito_generada.monto_original_usd).toFixed(2) }} USD)</strong>. Se acreditó automáticamente la Nota de Crédito a favor del propietario.
              </p>
            </div>

            <!-- 2. Bloque: Origen del Pago (Residente & Recibo) -->
            <div class="border border-slate-200 rounded-xl overflow-hidden">
              <div class="bg-slate-100 px-4 py-2 text-caption font-weight-bold text-slate-700 text-uppercase tracking-wider d-flex align-center gap-2">
                <v-icon icon="mdi-account-home-outline" size="16" color="slate-600" />
                Inmueble & Propietario
              </div>
              <div class="pa-4 bg-white">
                <v-row density="compact">
                  <v-col cols="12" sm="4">
                    <span class="text-caption text-slate-400 d-block font-weight-medium">Inmueble / Apto:</span>
                    <strong class="text-body-1 font-weight-bold text-slate-900">
                      Apartamento {{ selectedPayment.invoice?.apartamento?.numero || selectedPayment.invoices?.[0]?.apartamento?.numero || '-' }}
                    </strong>
                  </v-col>
                  <v-col cols="12" sm="4">
                    <span class="text-caption text-slate-400 d-block font-weight-medium">Propietario:</span>
                    <strong class="text-body-2 font-weight-bold text-slate-800">
                      {{ selectedPayment.invoice?.apartamento?.propietarios?.[0]?.nombre_completo || selectedPayment.invoices?.[0]?.apartamento?.propietarios?.[0]?.nombre_completo || 'Residente Registrado' }}
                    </strong>
                  </v-col>
                  <v-col cols="12" sm="4">
                    <span class="text-caption text-slate-400 d-block font-weight-medium">Recibo Principal:</span>
                    <strong class="text-body-2 font-weight-bold text-primary font-mono">
                      {{ selectedPayment.invoice?.numero_factura || '-' }}
                    </strong>
                    <span class="text-caption text-slate-500 d-block">Período: {{ selectedPayment.invoice?.periodo || '-' }}</span>
                  </v-col>
                </v-row>

                <!-- Desglose Múltiple si cubre más de 1 recibo -->
                <div v-if="selectedPayment.invoices && selectedPayment.invoices.length > 1" class="mt-3 border-t border-slate-100 pt-2">
                  <span class="text-caption font-weight-bold text-slate-700 text-uppercase d-block mb-1">
                    📋 Recibos de Cobro Cubiertos por este Único Pago ({{ selectedPayment.invoices.length }})
                  </span>
                  <div class="bg-blue-50/60 pa-2.5 rounded-lg border border-blue-100 space-y-1">
                    <div v-for="inv in selectedPayment.invoices" :key="inv.id" class="d-flex justify-space-between align-center text-caption py-1.5 border-b border-blue-100 last:border-0">
                      <div class="d-flex align-center gap-2">
                        <v-btn
                          size="x-small"
                          color="primary"
                          variant="tonal"
                          prepend-icon="mdi-file-pdf-box"
                          class="font-weight-bold"
                          :href="`/api/v1/reportes/recibo-invoice/${inv.id}`"
                          target="_blank"
                          title="Descargar PDF de este Recibo de Cobro Individual"
                        >
                          PDF Recibo
                        </v-btn>
                        <span class="text-slate-800 font-weight-medium">
                          Recibo <strong>{{ inv.numero_factura }}</strong> ({{ inv.periodo }}) — Apto. {{ inv.apartamento?.numero || '-' }}
                        </span>
                      </div>
                      <span class="font-weight-bold text-blue-900 font-mono">
                        Bs. {{ formatMoneyBs(inv.pivot?.monto_aplicado || 0) }} aplicados
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- 3. Bloque: Datos de Conciliación - Recepción del Edificio vs Emisión del Copropietario -->
            <div class="border border-slate-200 rounded-xl overflow-hidden">
              <div class="bg-slate-100 px-4 py-2.5 text-caption font-weight-bold text-slate-700 text-uppercase tracking-wider d-flex align-center gap-2">
                <v-icon icon="mdi-bank-check" size="18" color="slate-600" />
                Conciliación Bancaria y Contable (Recepción vs Emisión)
              </div>
              <div class="pa-4 bg-white">
                <v-row density="compact">
                  <!-- Canal / Cuenta Destino del Edificio -->
                  <v-col cols="12" sm="6">
                    <div class="bg-blue-50/60 pa-3 rounded-lg border border-blue-100 h-100">
                      <span class="text-caption text-blue-900 font-weight-bold d-block text-uppercase mb-1">
                        🏛️ Canal Receptor del Condominio
                      </span>
                      <div class="text-caption text-slate-800 d-flex flex-column gap-0.5">
                        <div>
                          Entidad / Caja: <strong>{{ selectedPayment.cuentaBancaria?.banco_nombre || selectedPayment.condominio?.banco_nombre || 'Principal' }}</strong>
                        </div>
                        <div v-if="selectedPayment.cuentaBancaria?.numero_cuenta">
                          N° Cuenta: <strong class="font-mono text-primary">{{ selectedPayment.cuentaBancaria.numero_cuenta }}</strong>
                        </div>
                        <div v-if="selectedPayment.cuentaBancaria?.telefono_pago_movil">
                          Pago Móvil Destino: <strong class="font-mono text-primary">{{ selectedPayment.cuentaBancaria.telefono_pago_movil }}</strong>
                        </div>
                        <div v-if="selectedPayment.cuentaBancaria?.titular_nombre">
                          Titular: <strong>{{ selectedPayment.cuentaBancaria.titular_nombre }}</strong>
                        </div>
                        <div class="text-slate-500 font-italic mt-1" v-if="selectedPayment.cuentaBancaria?.tipo_cuenta">
                          Tipo: {{ selectedPayment.cuentaBancaria.tipo_cuenta.replace('_', ' ') }}
                        </div>
                      </div>
                    </div>
                  </v-col>

                  <!-- Datos de la Transacción Emitida por el Copropietario -->
                  <v-col cols="12" sm="6">
                    <div class="bg-slate-50 pa-3 rounded-lg border border-slate-200 h-100">
                      <span class="text-caption text-slate-700 font-weight-bold d-block text-uppercase mb-1">
                        📤 Datos de Emisión (Copropietario)
                      </span>
                      <div class="text-caption text-slate-800 d-flex flex-column gap-0.5">
                        <div>
                          Método: <v-chip size="x-small" color="slate-800" variant="tonal" class="font-weight-bold text-capitalize">{{ (selectedPayment.metodo_pago || '').replace('_', ' ') }}</v-chip>
                        </div>
                        <div>
                          Banco Emisor: <strong>{{ selectedPayment.banco_origen || selectedPayment.banco || 'No especificado' }}</strong>
                        </div>
                        <div v-if="selectedPayment.telefono_origen">
                          Teléfono Emisor: <strong class="font-mono text-primary">{{ selectedPayment.telefono_origen }}</strong>
                        </div>
                        <div>
                          N° Referencia: <code class="bg-white text-primary px-1.5 py-0.5 rounded font-mono font-weight-bold border border-slate-200">{{ selectedPayment.referencia || 'S/R' }}</code>
                        </div>
                        <div>
                          Fecha de Pago: <strong>{{ formatDate(selectedPayment.fecha_pago) }}</strong>
                        </div>
                      </div>
                    </div>
                  </v-col>
                </v-row>

                <!-- Comprobante Adjunto si existe -->
                <div v-if="selectedPayment.comprobante_path" class="mt-3 bg-slate-50 pa-3 rounded-lg border border-slate-200 d-flex align-center justify-space-between">
                  <div class="d-flex align-center gap-2">
                    <v-icon icon="mdi-paperclip" color="primary" size="20" />
                    <span class="text-caption font-weight-bold text-slate-800">Comprobante Digital Adjunto</span>
                  </div>
                  <v-btn
                    size="small"
                    color="primary"
                    variant="tonal"
                    prepend-icon="mdi-open-in-new"
                    class="font-weight-bold"
                    :href="'/storage/' + selectedPayment.comprobante_path"
                    target="_blank"
                  >
                    Ver Comprobante
                  </v-btn>
                </div>

                <div v-if="selectedPayment.observaciones" class="mt-3 text-caption">
                  <span class="text-slate-400 font-weight-medium d-block">Observaciones / Notas de la Operación:</span>
                  <p class="text-slate-800 font-italic mb-0 bg-slate-50 pa-2.5 rounded mt-1 border border-slate-100">
                    "{{ selectedPayment.observaciones }}"
                  </p>
                </div>
              </div>
            </div>

          </div>
        </v-card-text>

        <!-- Actions -->
        <v-card-actions class="pa-4 bg-slate-50 d-flex justify-space-between gap-2 border-t border-slate-100">
          <v-btn variant="text" color="slate-600" class="font-weight-medium" @click="viewDialog = false">Cerrar</v-btn>
          
          <div class="d-flex gap-2">
            <!-- Botón Corregir y Reenviar Pago (Si el pago fue rechazado) -->
            <v-btn
              v-if="selectedPayment?.estado === 'rechazado'"
              color="warning"
              variant="flat"
              prepend-icon="mdi-pencil-box-multiple"
              class="font-weight-bold"
              @click="openEditPaymentDialog(selectedPayment); viewDialog = false;"
            >
              Corregir y Reenviar Pago
            </v-btn>

            <!-- Editar Pago Registrado (Si no está aprobado y es Admin) -->
            <v-btn
              v-else-if="!authStore.isPropietario && selectedPayment?.estado !== 'aprobado'"
              color="warning"
              variant="tonal"
              prepend-icon="mdi-pencil"
              class="font-weight-bold"
              @click="openEditPaymentDialog(selectedPayment); viewDialog = false;"
            >
              Editar Registro
            </v-btn>

            <!-- Acciones de Validación para Admin -->
            <template v-if="!authStore.isPropietario && selectedPayment?.estado === 'pendiente'">
              <v-btn
                color="indigo"
                variant="tonal"
                prepend-icon="mdi-file-eye-outline"
                class="font-weight-bold"
                @click="openPreviewRPDialog(selectedPayment)"
              >
                Previsualizar Borrador RP
              </v-btn>
              <v-btn
                color="error"
                variant="tonal"
                prepend-icon="mdi-close"
                class="font-weight-bold"
                @click="openRejectDialog(selectedPayment.id)"
              >
                Rechazar
              </v-btn>
              <v-btn
                color="success"
                variant="flat"
                prepend-icon="mdi-check-decagram"
                class="font-weight-bold px-4"
                @click="approvePayment(selectedPayment.id); viewDialog = false;"
              >
                Aprobar y Emitir Recibo RP
              </v-btn>
            </template>

            <template v-else-if="selectedPayment?.estado === 'aprobado'">
              <v-btn
                v-if="!authStore.isPropietario"
                color="primary"
                variant="tonal"
                prepend-icon="mdi-email-send-outline"
                class="font-weight-bold"
                :loading="resendingEmailId === selectedPayment.id"
                @click="resendPaymentReceiptEmail(selectedPayment.id)"
              >
                Reenviar Recibo RP por Correo
              </v-btn>

              <v-btn
                color="error"
                variant="flat"
                prepend-icon="mdi-file-pdf-box"
                class="font-weight-bold"
                :href="'/api/v1/reportes/recibo/' + selectedPayment.id"
                target="_blank"
              >
                Descargar Recibo RP (PDF)
              </v-btn>
            </template>
          </div>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Modal Dedicado para Rechazo de Pago con Justificación Obligatoria (Mínimo 40 Caracteres) -->
    <v-dialog v-model="rejectDialog" max-width="540" persistent>
      <v-card class="rounded-xl overflow-hidden border border-rose-100 shadow-2xl">
        <div class="bg-rose-700 text-white pa-4 d-flex align-center justify-space-between">
          <div class="d-flex align-center gap-3">
            <v-avatar color="white" size="36" class="elevation-1">
              <v-icon icon="mdi-alert-circle-outline" color="rose-700" size="22" />
            </v-avatar>
            <div>
              <div class="text-subtitle-1 font-weight-bold leading-tight">
                Rechazar Notificación de Pago
              </div>
              <div class="text-caption text-rose-100">
                Justificación obligatoria para notificar al propietario
              </div>
            </div>
          </div>
          <v-btn icon="mdi-close" variant="text" color="white" density="comfortable" @click="rejectDialog = false" />
        </div>

        <v-card-text class="pa-5">
          <v-alert type="warning" variant="tonal" class="rounded-lg mb-4 text-caption">
            <v-icon start icon="mdi-information-outline" />
            Esta explicación será enviada al propietario para que pueda corregir la información o emitir nuevamente su comprobante.
          </v-alert>

          <div>
            <label class="text-caption font-weight-bold text-slate-700 text-uppercase tracking-wider d-block mb-1">
              Motivo o Justificación del Rechazo * (Mínimo 40 caracteres)
            </label>
            <v-textarea
              v-model="rejectMotivo"
              placeholder="Explique detalladamente la razón del rechazo (ej. El número de referencia notificado no coincide con la transferencia recibida en el banco BNC el día 25/08...)"
              rows="4"
              variant="outlined"
              density="comfortable"
              color="error"
              class="rounded-lg font-medium"
              hide-details="auto"
              :rules="[v => (v && v.trim().length >= 40) || 'La justificación debe contener al menos 40 caracteres.']"
            />
            <div class="d-flex justify-space-between align-center mt-2 text-caption">
              <span :class="rejectMotivo.trim().length < 40 ? 'text-rose-600 font-weight-bold' : 'text-emerald-700 font-weight-bold'">
                {{ rejectMotivo.trim().length }} / 40 caracteres requeridos
              </span>
              <span v-if="rejectMotivo.trim().length < 40" class="text-rose-500 font-italic">
                Faltan {{ 40 - rejectMotivo.trim().length }} caracteres
              </span>
              <span v-else class="text-emerald-600 font-weight-bold">
                ✓ Longitud válida
              </span>
            </div>
          </div>
        </v-card-text>

        <v-card-actions class="pa-4 bg-slate-50 d-flex justify-space-between border-t border-slate-100">
          <v-btn variant="text" color="slate-600" class="font-weight-medium" @click="rejectDialog = false">Cancelar</v-btn>
          <v-btn
            color="error"
            variant="flat"
            class="font-weight-bold px-5"
            :disabled="rejectMotivo.trim().length < 40"
            :loading="rejecting"
            @click="submitRejection"
          >
            Confirmar Rechazo de Pago
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Modal para Previsualización en Borrador del Recibo RP -->
    <v-dialog v-model="previewRPDialog" max-width="1100" width="92vw" scrollable :fullscreen="$vuetify.display.smAndDown">
      <v-card class="rounded-xl overflow-hidden border border-indigo-100 shadow-2xl d-flex flex-column" style="height: 92vh; max-height: 92vh;">
        <!-- Header del Modal -->
        <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white pa-4 d-flex align-center justify-space-between flex-shrink-0 border-b border-indigo-900/50">
          <div class="d-flex align-center gap-3">
            <v-avatar color="indigo-darken-1" size="40" class="elevation-2">
              <v-icon icon="mdi-file-eye-outline" color="white" size="24" />
            </v-avatar>
            <div>
              <div class="d-flex align-center gap-2">
                <span class="text-subtitle-1 font-weight-bold leading-tight">
                  Previsualización en Borrador del Recibo RP
                </span>
                <v-chip size="x-small" color="warning" variant="flat" class="font-weight-black">
                  MODO BORRADOR
                </v-chip>
              </div>
              <div class="text-caption text-indigo-200">
                Verifique el cálculo, montos aplicados y comprobante antes de aprobar la transacción
              </div>
            </div>
          </div>
          <div class="d-flex align-center gap-2">
            <v-btn
              v-if="previewRPUrl"
              icon="mdi-open-in-new"
              variant="text"
              color="white"
              density="comfortable"
              :href="previewRPUrl"
              target="_blank"
              title="Abrir PDF en pestaña nueva"
            />
            <v-btn icon="mdi-close" variant="text" color="white" density="comfortable" @click="previewRPDialog = false" />
          </div>
        </div>

        <!-- Banner de Información y Alerta de Borrador -->
        <div class="bg-amber-50 border-b border-amber-200 px-4 py-2.5 d-flex align-center justify-space-between flex-shrink-0 text-caption text-amber-900 font-weight-medium">
          <div class="d-flex align-center gap-2">
            <v-icon icon="mdi-shield-alert-outline" color="amber-darken-3" size="18" />
            <span>
              Este documento es un <strong>Borrador de Trabajo</strong> generado en tiempo real. Al hacer clic en <strong>"Aprobar y Emitir"</strong>, se registrará el número RP oficial y se saldarán las cuentas.
            </span>
          </div>
          <span v-if="previewingPayment" class="font-mono text-amber-950 font-weight-bold d-none d-sm-inline">
            Apto. {{ previewingPayment.invoice?.apartamento?.numero || previewingPayment.invoices?.[0]?.apartamento?.numero || '-' }} | Ref: {{ previewingPayment.referencia || 'S/R' }}
          </span>
        </div>

        <!-- Cuerpo del Modal con Visor PDF Iframe de Pantalla Completa -->
        <v-card-text class="pa-0 flex-grow-1 bg-slate-100 d-flex flex-column" style="height: calc(92vh - 145px); overflow: hidden;">
          <iframe
            v-if="previewRPDialog && previewRPUrl"
            :src="previewRPUrl"
            class="w-100 flex-grow-1 border-0"
            style="height: 100%; min-height: 550px; display: block;"
            title="Previsualización Recibo RP"
          ></iframe>
        </v-card-text>

        <!-- Footer / Acciones Directas -->
        <v-card-actions class="pa-3.5 bg-white border-t border-slate-200 d-flex flex-wrap justify-space-between align-center flex-shrink-0 gap-2">
          <v-btn
            variant="text"
            color="slate-600"
            class="font-weight-medium"
            @click="previewRPDialog = false"
          >
            Cerrar Visor
          </v-btn>

          <div class="d-flex align-center gap-2">
            <v-btn
              v-if="!authStore.isPropietario && previewingPayment?.estado === 'pendiente'"
              color="error"
              variant="tonal"
              prepend-icon="mdi-close"
              class="font-weight-bold"
              @click="openRejectDialog(previewingPayment.id); previewRPDialog = false;"
            >
              Rechazar Pago
            </v-btn>

            <v-btn
              v-if="!authStore.isPropietario && previewingPayment?.estado === 'pendiente'"
              color="success"
              variant="flat"
              prepend-icon="mdi-check-decagram"
              class="font-weight-bold px-4"
              @click="approvePayment(previewingPayment.id); previewRPDialog = false;"
            >
              Aprobar y Emitir Recibo RP Oficial
            </v-btn>

            <v-btn
              v-else-if="previewingPayment?.estado === 'aprobado'"
              color="error"
              variant="flat"
              prepend-icon="mdi-file-pdf-box"
              class="font-weight-bold"
              :href="'/api/v1/reportes/recibo/' + previewingPayment.id"
              target="_blank"
            >
              Descargar Recibo RP Oficial
            </v-btn>
          </div>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Modal para Visualizar Detalles de la Nota de Crédito -->
    <v-dialog v-model="viewCreditNoteDialog" max-width="700" scrollable persistent>
      <v-card class="rounded-xl overflow-hidden border border-slate-100 shadow-2xl d-flex flex-column" style="max-height: 90vh;">
        <!-- Header -->
        <div class="bg-gradient-to-r from-teal-900 via-emerald-900 to-slate-900 text-white pa-4 d-flex align-center justify-space-between flex-shrink-0">
          <div class="d-flex align-center gap-3">
            <v-avatar color="teal-darken-1" size="36" class="elevation-1">
              <v-icon icon="mdi-cash-refund" color="white" size="20" />
            </v-avatar>
            <div>
              <div class="text-subtitle-1 font-weight-bold leading-tight">
                Nota de Crédito {{ selectedCreditNote?.numero_nota_credito }}
              </div>
              <div class="text-caption text-teal-200">
                Detalles del saldo a favor y registro de aplicaciones
              </div>
            </div>
          </div>
          <v-btn icon="mdi-close" variant="text" color="white" density="comfortable" @click="viewCreditNoteDialog = false" />
        </div>

        <v-card-text class="pa-5 overflow-y-auto" style="max-height: 68vh;">
          <div v-if="selectedCreditNote" class="d-flex flex-column ga-4">
            <!-- Resumen de Saldo -->
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl pa-4 d-flex flex-column flex-sm-row justify-space-between align-start align-sm-center ga-3">
              <div>
                <span class="text-caption font-weight-bold text-emerald-800 text-uppercase tracking-wider d-block mb-1">
                  Saldo Disponible a Favor
                </span>
                <div class="text-h4 font-weight-black text-emerald-700 font-mono leading-none">
                  Bs. {{ formatMoneyBs(selectedCreditNote.monto_disponible) }}
                </div>
                <div class="text-caption font-weight-bold text-emerald-800 mt-1">
                  Equivalente: <span class="font-mono text-subtitle-2">${{ Number(selectedCreditNote.monto_disponible_usd).toFixed(2) }} USD</span>
                </div>
              </div>

              <div class="text-sm-right">
                <span class="text-caption font-weight-bold text-slate-500 text-uppercase d-block mb-1">Estado</span>
                <v-chip
                  :color="selectedCreditNote.estado === 'disponible' ? 'success' : (selectedCreditNote.estado === 'parcial' ? 'warning' : 'default')"
                  size="small"
                  variant="flat"
                  class="font-weight-bold text-uppercase px-3"
                >
                  {{ selectedCreditNote.estado }}
                </v-chip>
                <div class="text-caption font-weight-bold text-slate-500 mt-1">
                  Monto Original: Bs. {{ formatMoneyBs(selectedCreditNote.monto_original) }} (${{ Number(selectedCreditNote.monto_original_usd).toFixed(2) }} USD)
                </div>
              </div>
            </div>

            <!-- Inmueble & Origen -->
            <div class="border border-slate-200 rounded-xl overflow-hidden">
              <div class="bg-slate-100 px-4 py-2 text-caption font-weight-bold text-slate-700 text-uppercase tracking-wider d-flex align-center gap-2">
                <v-icon icon="mdi-home-outline" size="16" color="slate-600" />
                Inmueble Beneficiado & Origen
              </div>
              <div class="pa-4 bg-white">
                <v-row density="compact">
                  <v-col cols="12" sm="4">
                    <span class="text-caption text-slate-400 d-block font-weight-medium">Inmueble:</span>
                    <strong class="text-body-1 font-weight-bold text-slate-900">
                      Apartamento {{ selectedCreditNote.apartamento?.numero || '-' }}
                    </strong>
                  </v-col>
                  <v-col cols="12" sm="4">
                    <span class="text-caption text-slate-400 d-block font-weight-medium">Propietario:</span>
                    <strong class="text-body-2 font-weight-bold text-slate-800">
                      {{ selectedCreditNote.apartamento?.propietarios?.[0]?.nombre_completo || 'Copropietario' }}
                    </strong>
                  </v-col>
                  <v-col cols="12" sm="4">
                    <span class="text-caption text-slate-400 d-block font-weight-medium">Pago Origen:</span>
                    <strong class="text-body-2 font-weight-bold text-teal-800 font-mono">
                      {{ selectedCreditNote.origen_pago?.numero_recibo_pago || 'Pago #' + (selectedCreditNote.payment_id || '-') }}
                    </strong>
                    <span class="text-caption text-slate-500 d-block">Fecha: {{ formatDate(selectedCreditNote.fecha_emision) }}</span>
                  </v-col>
                </v-row>
              </div>
            </div>

            <!-- Historial de Pagos donde se ha aplicado este crédito -->
            <div class="border border-slate-200 rounded-xl overflow-hidden">
              <div class="bg-slate-100 px-4 py-2 text-caption font-weight-bold text-slate-700 text-uppercase tracking-wider d-flex align-center gap-2">
                <v-icon icon="mdi-history" size="16" color="slate-600" />
                Historial de Aplicaciones / Descuentos Realizados
              </div>
              <div class="pa-4 bg-white">
                <div v-if="selectedCreditNote.pagos_aplicados && selectedCreditNote.pagos_aplicados.length > 0" class="space-y-2">
                  <div
                    v-for="appPago in selectedCreditNote.pagos_aplicados"
                    :key="appPago.id"
                    class="d-flex justify-space-between align-center pa-2.5 bg-slate-50 rounded-lg border border-slate-200"
                  >
                    <div>
                      <div class="text-caption font-weight-bold text-slate-900">
                        {{ appPago.numero_recibo_pago || 'Notificación #' + appPago.id }}
                      </div>
                      <div class="text-caption text-slate-500">
                        Fecha: {{ formatDate(appPago.fecha_pago) }} • Estado: 
                        <v-chip size="x-small" :color="appPago.estado === 'aprobado' ? 'success' : 'warning'" variant="flat">
                          {{ appPago.estado }}
                        </v-chip>
                      </div>
                    </div>
                    <div class="text-right">
                      <div class="font-weight-bold text-primary font-mono text-body-2">
                        - Bs. {{ formatMoneyBs(appPago.monto) }}
                      </div>
                      <div class="text-caption text-slate-500 font-mono">
                        (-${{ (appPago.monto / (appPago.tasa_cambio || 36.50)).toFixed(2) }} USD)
                      </div>
                    </div>
                  </div>
                </div>
                <div v-else class="text-center py-4 text-slate-400 text-caption">
                  Esta nota de crédito aún no ha sido consumida en ningún recibo de cobro.
                </div>
              </div>
            </div>
          </div>
        </v-card-text>

        <v-card-actions class="pa-4 bg-slate-50 d-flex justify-space-between align-center border-t border-slate-100">
          <v-btn color="slate-700" variant="tonal" class="font-weight-medium" @click="viewCreditNoteDialog = false">
            Cerrar
          </v-btn>

          <v-btn
            v-if="selectedCreditNote?.id"
            color="error"
            variant="flat"
            prepend-icon="mdi-file-pdf-box"
            class="font-weight-bold"
            :href="'/api/v1/reportes/nota-credito/' + selectedCreditNote.id"
            target="_blank"
          >
            Descargar Documento Oficial (PDF)
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../../store/auth';
import { usePagination } from '../../composables/usePagination';
import DataTableHeader from '../../components/DataTableHeader.vue';
import DataTableFooter from '../../components/DataTableFooter.vue';
import SortHeader from '../../components/SortHeader.vue';

const authStore = useAuthStore();
const activeTab = ref('pagos');
const paymentsList = ref([]);
const creditNotesList = ref([]);
const invoicesSelect = ref([]);
const rawInvoicesList = ref([]);
const apartamentosList = ref([]);
const selectedAdminApartamentoId = ref(null);
const condominioCuentas = ref([]);
const bancosCatalogoList = ref([
    'Banco de Venezuela',
    'Banesco',
    'Banco Mercantil',
    'BBVA Provincial',
    'Banco Nacional de Crédito BNC',
    'Bancamiga',
    'Banplus',
    'Banco del Tesoro',
    'Banco Exterior',
    'Banco Plaza',
    'Banco Activo',
    '100% Banco',
    'Mi Banco',
    'Sofitasa'
]);

const dialog = ref(false);
const viewDialog = ref(false);
const viewCreditNoteDialog = ref(false);
const selectedCreditNote = ref(null);
const previewRPDialog = ref(false);
const previewingPayment = ref(null);
const selectedPayment = ref(null);
const saving = ref(false);
const comprobanteFile = ref(null);

const metodosPagoDisponibles = [
    { title: 'Pago Móvil', subtitle: 'Bolívares (VES)', value: 'pago_movil', icon: 'mdi-cellphone-check', iconColor: 'deep-purple-darken-1' },
    { title: 'Transferencia', subtitle: 'Bs. o Divisas', value: 'transferencia', icon: 'mdi-bank-outline', iconColor: 'blue-darken-2' },
    { title: 'Efectivo Divisas', subtitle: '$ USD / EUR', value: 'efectivo_usd', icon: 'mdi-currency-usd', iconColor: 'emerald-darken-2' },
    { title: 'Efectivo Bolívares', subtitle: 'Bs. VES', value: 'efectivo_ves', icon: 'mdi-cash', iconColor: 'amber-darken-3' },
    { title: 'Zelle / Otros', subtitle: 'Cuentas Extranjeras', value: 'zelle', icon: 'mdi-send-circle', iconColor: 'purple-darken-2' },
];

const selectedAdminInvoiceIds = ref([]);

const form = ref({
    invoice_ids: [],
    invoice_id: null,
    cuenta_bancaria_id: null,
    monto: '0.00',
    monto_divisa: '0.00',
    moneda_origen: 'VES',
    metodo_pago: 'pago_movil',
    banco: 'Banco de Venezuela',
    banco_origen: 'Banco de Venezuela',
    telefono_origen: '',
    cedula_origen: '',
    referencia: '',
    fecha_pago: new Date().toISOString().substring(0, 10),
    observaciones: '',
    aprobar_inmediato: false,
});

const creditNotesDisponiblesCount = computed(() => {
    return creditNotesList.value.filter(n => n.estado === 'disponible' || n.estado === 'parcial').length;
});

const openViewCreditNoteDialog = (nc) => {
    selectedCreditNote.value = nc;
    viewCreditNoteDialog.value = true;
};

const previewRPUrl = computed(() => {
    return previewingPayment.value ? `/api/v1/reportes/recibo/${previewingPayment.value.id}` : '';
});

const openPreviewRPDialog = (pago) => {
    if (!pago) return;
    previewingPayment.value = pago;
    previewRPDialog.value = true;
};

const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const day = String(d.getUTCDate()).padStart(2, '0');
    const month = String(d.getUTCMonth() + 1).padStart(2, '0');
    const year = d.getUTCFullYear();
    return `${day}/${month}/${year}`;
};

const openViewPaymentDialog = (pago) => {
    selectedPayment.value = pago;
    viewDialog.value = true;
};

const customGettersPagos = {
    apartamento: (p) => p.invoice?.apartamento?.numero || p.invoices?.[0]?.apartamento?.numero || '',
    monto: (p) => Number(p.monto || 0),
};

const customGettersNotas = {
    inmueble: (nc) => nc.apartamento?.numero || '',
    monto_original: (nc) => Number(nc.monto_original || 0),
    monto_disponible: (nc) => Number(nc.monto_disponible || 0),
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
} = usePagination(paymentsList, {
    perPage: 10,
    initialSortBy: 'fecha_pago',
    initialSortDesc: true,
    customGetters: customGettersPagos,
});

const pNotas = usePagination(creditNotesList, {
    perPage: 10,
    initialSortBy: 'fecha_emision',
    initialSortDesc: true,
    customGetters: customGettersNotas,
});

// Cuentas del condominio filtradas por tipo
const pagoMovilCuentas = computed(() => {
    return condominioCuentas.value.filter(c => c.activo && (c.es_pago_movil || c.tipo_cuenta === 'pago_movil'));
});

const transferenciaCuentas = computed(() => {
    return condominioCuentas.value.filter(c => c.activo && !c.es_pago_movil && c.tipo_cuenta !== 'pago_movil' && !['efectivo_usd', 'efectivo_ves'].includes(c.tipo_cuenta));
});

const efectivoUsdCuentas = computed(() => {
    return condominioCuentas.value.filter(c => c.activo && c.tipo_cuenta === 'efectivo_usd');
});

const efectivoVesCuentas = computed(() => {
    return condominioCuentas.value.filter(c => c.activo && c.tipo_cuenta === 'efectivo_ves');
});

const zelleCuentas = computed(() => {
    return condominioCuentas.value.filter(c => c.activo && c.tipo_cuenta === 'zelle');
});

const currentPagoMovilDestino = computed(() => {
    if (form.value.cuenta_bancaria_id) {
        const found = pagoMovilCuentas.value.find(c => c.id === form.value.cuenta_bancaria_id);
        if (found) {
            return {
                banco: found.banco_nombre,
                telefono: found.telefono_pago_movil || '0414-1234567',
                rif: found.titular_identificacion || 'J-300576531',
                titular: found.titular_nombre || 'Condominio',
            };
        }
    }
    const first = pagoMovilCuentas.value[0];
    if (first) {
        return {
            banco: first.banco_nombre,
            telefono: first.telefono_pago_movil || '0414-1234567',
            rif: first.titular_identificacion || 'J-300576531',
            titular: first.titular_nombre || 'Condominio',
        };
    }
    return {
        banco: authStore.condominio?.pago_movil_banco || authStore.condominio?.banco_nombre || 'Banco de Venezuela',
        telefono: authStore.condominio?.pago_movil_telefono || '0414-1234567',
        rif: authStore.condominio?.pago_movil_cedula || authStore.condominio?.rif || 'J-300576531',
        titular: authStore.condominio?.nombre || 'Condominio',
    };
});

const currentTransferenciaDestino = computed(() => {
    if (form.value.cuenta_bancaria_id) {
        const found = transferenciaCuentas.value.find(c => c.id === form.value.cuenta_bancaria_id);
        if (found) {
            return {
                banco: found.banco_nombre,
                numero: found.numero_cuenta || '0102 0000 00 0000000000',
                rif: found.titular_identificacion || 'J-300576531',
                titular: found.titular_nombre || 'Condominio',
            };
        }
    }
    const first = transferenciaCuentas.value[0];
    if (first) {
        return {
            banco: first.banco_nombre,
            numero: first.numero_cuenta || '0102 0000 00 0000000000',
            rif: first.titular_identificacion || 'J-300576531',
            titular: first.titular_nombre || 'Condominio',
        };
    }
    return {
        banco: authStore.condominio?.banco_nombre || 'Banco Nacional de Crédito BNC',
        numero: authStore.condominio?.cuenta_bancaria_bs || '0191 0514 8221 0001 8351',
        rif: authStore.condominio?.rif || 'J-300576531',
        titular: authStore.condominio?.nombre || 'Condominio',
    };
});

const currentEfectivoUsdDestino = computed(() => {
    return efectivoUsdCuentas.value[0] || {
        banco_nombre: 'Oficina de Administración / Conserjería',
        instrucciones: 'El dinero físico en dólares debe ser entregado al personal autorizado del edificio.',
    };
});

const currentEfectivoVesDestino = computed(() => {
    return efectivoVesCuentas.value[0] || {
        banco_nombre: 'Caja de Administración / Conserjería',
        instrucciones: 'Entrega física en moneda nacional en caja del condominio.',
    };
});

const currentZelleDestino = computed(() => {
    const found = zelleCuentas.value[0];
    if (found) {
        return {
            correo: found.numero_cuenta || 'condominio@zelle.com',
            titular: found.titular_nombre || authStore.condominio?.nombre || 'Condominio',
        };
    }
    return {
        correo: authStore.condominio?.email || 'condominio@zelle.com',
        titular: authStore.condominio?.nombre || 'Condominio',
    };
});

const activeTasaCambio = computed(() => {
    const firstInv = displayedPendingInvoices.value[0];
    return Number(firstInv?.tasa_efectiva || firstInv?.tasa_cambio || authStore.tasaCambioCentral || authStore.tasaCambio || 36.50);
});

// Filtro de facturas según el apartamento seleccionado (o todas si es propietario)
const displayedPendingInvoices = computed(() => {
    if (authStore.isPropietario) return rawInvoicesList.value;
    if (!selectedAdminApartamentoId.value) return rawInvoicesList.value;
    return rawInvoicesList.value.filter(inv => Number(inv.apartamento_id) === Number(selectedAdminApartamentoId.value));
});

// Opciones de apartamentos para el buscador del administrador
const apartamentosOptions = computed(() => {
    if (apartamentosList.value.length > 0) {
        return apartamentosList.value.map(apto => {
            const num = apto.numero || `Apto. ${apto.id}`;
            const prop = apto.propietarios?.[0]?.nombre_completo || apto.propietario?.nombre_completo || 'Sin Asignar';
            const pendingForApto = rawInvoicesList.value.filter(inv => Number(inv.apartamento_id) === Number(apto.id));
            const saldoAptoBs = pendingForApto.reduce((sum, inv) => sum + getInvoiceSaldoBs(inv), 0);
            
            const badge = pendingForApto.length > 0 
                ? ` [${pendingForApto.length} recibo(s) pendiente(s) - Bs. ${formatMoneyBs(saldoAptoBs)}]` 
                : ' [Al día - Sin deuda]';
            
            return {
                id: apto.id,
                numero: num,
                propietario_nombre: prop,
                recibos_count: pendingForApto.length,
                saldo_bs: saldoAptoBs,
                label: `Apto. ${num} — ${prop}${badge}`,
            };
        });
    }

    const aptosMap = new Map();
    rawInvoicesList.value.forEach(inv => {
        if (!inv.apartamento_id) return;
        const aptoId = inv.apartamento_id;
        if (!aptosMap.has(aptoId)) {
            const num = inv.apartamento?.numero || `Apto. ${aptoId}`;
            const prop = inv.apartamento?.propietarios?.[0]?.nombre_completo || 'Residente';
            aptosMap.set(aptoId, {
                id: aptoId,
                numero: num,
                propietario_nombre: prop,
                recibos_count: 1,
                saldo_bs: getInvoiceSaldoBs(inv),
            });
        } else {
            const entry = aptosMap.get(aptoId);
            entry.recibos_count += 1;
            entry.saldo_bs += getInvoiceSaldoBs(inv);
        }
    });
    return Array.from(aptosMap.values()).map(item => ({
        ...item,
        label: `Apto. ${item.numero} — ${item.propietario_nombre} [${item.recibos_count} recibo(s) - Bs. ${formatMoneyBs(item.saldo_bs)}]`,
    }));
});

const formatMoneyBs = (val) => {
    return Number(val || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const getInvoiceTotalBs = (inv) => {
    if (!inv) return 0;
    return Number(inv.monto_total_bs_efectivo || inv.monto_total || 0);
};

const getInvoiceTotalUsd = (inv) => {
    if (!inv) return 0;
    if (inv.monto_total_usd && Number(inv.monto_total_usd) > 0) return Number(inv.monto_total_usd);
    const tasa = Number(inv.tasa_efectiva || inv.tasa_cambio || 36.50);
    return getInvoiceTotalBs(inv) / tasa;
};

const getInvoiceSaldoBs = (inv) => {
    if (!inv) return 0;
    const totalBs = getInvoiceTotalBs(inv);
    const pagadoBs = Number(inv.monto_pagado || 0);
    return Math.max(0, totalBs - pagadoBs);
};

const selectedAdminInvoicesObjects = computed(() => {
    return displayedPendingInvoices.value.filter(inv => selectedAdminInvoiceIds.value.includes(inv.id));
});

const adminTotalSelectedBs = computed(() => {
    return selectedAdminInvoicesObjects.value.reduce((sum, inv) => sum + getInvoiceSaldoBs(inv), 0);
});

const adminTotalSelectedUsd = computed(() => {
    return selectedAdminInvoicesObjects.value.reduce((sum, inv) => {
        const totalBs = getInvoiceTotalBs(inv) || 1;
        return sum + (getInvoiceTotalUsd(inv) * (getInvoiceSaldoBs(inv) / totalBs));
    }, 0);
});

const recalculateTotalAmount = () => {
    const totalBs = adminTotalSelectedBs.value;
    const totalUsd = adminTotalSelectedUsd.value;
    const tasa = Number(activeTasaCambio.value || 36.50);

    if (form.value.metodo_pago === 'efectivo_usd' || form.value.metodo_pago === 'zelle') {
        form.value.moneda_origen = 'USD';
        form.value.monto_divisa = totalUsd.toFixed(2);
        form.value.monto = (totalUsd * tasa).toFixed(2);
    } else {
        form.value.moneda_origen = 'VES';
        form.value.monto = totalBs.toFixed(2);
        form.value.monto_divisa = (totalBs / tasa).toFixed(2);
    }
};

const onAdminApartamentoChange = (aptoId) => {
    selectedAdminApartamentoId.value = aptoId;
    if (!aptoId) {
        selectedAdminInvoiceIds.value = [];
    } else {
        const filtered = rawInvoicesList.value.filter(inv => Number(inv.apartamento_id) === Number(aptoId));
        selectedAdminInvoiceIds.value = filtered.map(i => i.id);
    }
    recalculateTotalAmount();
};

watch(selectedAdminApartamentoId, (newAptoId) => {
    if (!dialog.value) return;
    if (!newAptoId) {
        selectedAdminInvoiceIds.value = [];
    } else {
        const filtered = rawInvoicesList.value.filter(inv => Number(inv.apartamento_id) === Number(newAptoId));
        selectedAdminInvoiceIds.value = filtered.map(i => i.id);
    }
    recalculateTotalAmount();
});

const onSelectMetodoPago = (metodo) => {
    form.value.metodo_pago = metodo;
    if (metodo === 'pago_movil') {
        form.value.moneda_origen = 'VES';
        form.value.cuenta_bancaria_id = pagoMovilCuentas.value[0]?.id || null;
    } else if (metodo === 'transferencia') {
        form.value.moneda_origen = 'VES';
        form.value.cuenta_bancaria_id = transferenciaCuentas.value[0]?.id || null;
    } else if (metodo === 'efectivo_usd') {
        form.value.moneda_origen = 'USD';
        form.value.cuenta_bancaria_id = efectivoUsdCuentas.value[0]?.id || null;
    } else if (metodo === 'efectivo_ves') {
        form.value.moneda_origen = 'VES';
        form.value.cuenta_bancaria_id = efectivoVesCuentas.value[0]?.id || null;
    } else if (metodo === 'zelle') {
        form.value.moneda_origen = 'USD';
        form.value.cuenta_bancaria_id = zelleCuentas.value[0]?.id || null;
    }
    recalculateTotalAmount();
};

const onMontoDivisaInput = (val) => {
    const usd = Number(val || 0);
    const tasa = Number(activeTasaCambio.value || 36.50);
    form.value.monto = (usd * tasa).toFixed(2);
};

const onMontoBsInput = (val) => {
    const bs = Number(val || 0);
    const tasa = Number(activeTasaCambio.value || 36.50);
    form.value.monto_divisa = (bs / tasa).toFixed(2);
};

const copyPagoMovilData = async () => {
    const d = currentPagoMovilDestino.value;
    const text = `Banco: ${d.banco}\nTeléfono: ${d.telefono}\nRIF/CI: ${d.rif}\nTitular: ${d.titular}`;
    try {
        await navigator.clipboard.writeText(text);
        authStore.notify('¡Datos de Pago Móvil copiados al portapapeles!', 'success');
    } catch (e) {
        authStore.notify('No se pudo copiar automáticamente', 'info');
    }
};

const copyCuentaBancariaData = async () => {
    const d = currentTransferenciaDestino.value;
    const text = d.numero;
    try {
        await navigator.clipboard.writeText(text);
        authStore.notify(`¡N° de cuenta ${d.banco} copiado!`, 'success');
    } catch (e) {
        authStore.notify('No se pudo copiar automáticamente', 'info');
    }
};

const copyZelleData = async () => {
    const d = currentZelleDestino.value;
    try {
        await navigator.clipboard.writeText(d.correo);
        authStore.notify('¡Correo Zelle copiado!', 'success');
    } catch (e) {
        authStore.notify('No se pudo copiar automáticamente', 'info');
    }
};

const toggleAdminInvoiceChoice = (id) => {
    const idx = selectedAdminInvoiceIds.value.indexOf(id);
    if (idx > -1) {
        selectedAdminInvoiceIds.value.splice(idx, 1);
    } else {
        selectedAdminInvoiceIds.value.push(id);
    }
    recalculateTotalAmount();
};

const toggleAdminSelectAllInvoices = () => {
    if (selectedAdminInvoiceIds.value.length === displayedPendingInvoices.value.length) {
        selectedAdminInvoiceIds.value = [];
    } else {
        selectedAdminInvoiceIds.value = displayedPendingInvoices.value.map(i => i.id);
    }
    recalculateTotalAmount();
};

const fetchBancosCatalogo = async () => {
    try {
        const { data } = await axios.get('/bancos');
        if (data.success && data.data?.length) {
            bancosCatalogoList.value = data.data.map(b => b.nombre);
        }
    } catch (e) {}
};

const fetchCuentasBancariasAdmin = async () => {
    try {
        const res = await axios.get('/condominios/cuentas-bancarias');
        if (res.data.success) {
            condominioCuentas.value = res.data.data;
        }
    } catch (e) {}
};

const fetchData = async () => {
    try {
        const [payRes, invRes, ncRes, aptsRes] = await Promise.all([
            axios.get('/payments'),
            axios.get('/invoices'),
            axios.get('/credit-notes'),
            axios.get('/apartamentos'),
            fetchBancosCatalogo(),
            fetchCuentasBancariasAdmin(),
        ]);
        if (payRes.data?.success) paymentsList.value = payRes.data.data.data || payRes.data.data;
        if (ncRes.data?.success) creditNotesList.value = ncRes.data.data.data || ncRes.data.data;
        if (aptsRes.data?.success) apartamentosList.value = aptsRes.data.data.data || aptsRes.data.data || [];
        if (invRes.data?.success) {
            const list = invRes.data.data.data || invRes.data.data;
            const pendingList = list.filter(inv => {
                const saldo = getInvoiceSaldoBs(inv);
                return inv.estado !== 'pagado' && saldo > 0.01;
            });
            rawInvoicesList.value = pendingList;
            invoicesSelect.value = pendingList.map(inv => {
                const saldoBs = getInvoiceSaldoBs(inv);
                const totalBs = getInvoiceTotalBs(inv) || 1;
                const saldoUsd = getInvoiceTotalUsd(inv) * (saldoBs / totalBs);
                const numDoc = inv.numero_factura ? ` (${inv.numero_factura})` : '';
                return {
                    id: inv.id,
                    label: `Apto. ${inv.apartamento?.numero || '-'} | ${inv.periodo}${numDoc} — Saldo: Bs. ${formatMoneyBs(saldoBs)} ($${saldoUsd.toFixed(2)} USD)`,
                };
            });
        }
    } catch (e) {
        authStore.notify('Error al cargar pagos y facturas', 'error');
    }
};

const rejectDialog = ref(false);
const rejectMotivo = ref('');
const paymentToRejectId = ref(null);
const rejecting = ref(false);

const isEditingPayment = ref(false);
const editingPaymentId = ref(null);

const openRejectDialog = (id) => {
    paymentToRejectId.value = id;
    rejectMotivo.value = '';
    viewDialog.value = false;
    rejectDialog.value = true;
};

const submitRejection = async () => {
    if (!paymentToRejectId.value || rejectMotivo.value.trim().length < 40) return;
    rejecting.value = true;
    try {
        await axios.put(`/payments/${paymentToRejectId.value}/rechazar`, { motivo: rejectMotivo.value.trim() });
        authStore.notify('Pago rechazado correctamente. Se envió la justificación al propietario.');
        rejectDialog.value = false;
        fetchData();
    } catch (e) {
        const msg = e.response?.data?.message || 'Error al rechazar el pago.';
        authStore.notify(msg, 'error');
    } finally {
        rejecting.value = false;
    }
};

const openPaymentDialog = () => {
    isEditingPayment.value = false;
    editingPaymentId.value = null;
    comprobanteFile.value = null;
    fetchCuentasBancariasAdmin();
    fetchBancosCatalogo();

    if (authStore.isPropietario) {
        selectedAdminApartamentoId.value = null;
        // Para copropietario se preseleccionan sus recibos pendientes
        selectedAdminInvoiceIds.value = rawInvoicesList.value.length ? rawInvoicesList.value.map(i => i.id) : [];
    } else {
        // Para administrador: NO viene preseleccionado ningún inmueble ni recibos por defecto
        selectedAdminApartamentoId.value = null;
        selectedAdminInvoiceIds.value = [];
    }

    const defaultCuenta = pagoMovilCuentas.value[0]?.id || condominioCuentas.value[0]?.id || null;

    form.value = {
        invoice_ids: [...selectedAdminInvoiceIds.value],
        invoice_id: selectedAdminInvoiceIds.value[0] || null,
        cuenta_bancaria_id: defaultCuenta,
        monto: '0.00',
        monto_divisa: '0.00',
        moneda_origen: 'VES',
        metodo_pago: 'pago_movil',
        banco: 'Banco de Venezuela',
        banco_origen: 'Banco de Venezuela',
        telefono_origen: '',
        cedula_origen: '',
        referencia: '',
        fecha_pago: new Date().toISOString().substring(0, 10),
        observaciones: '',
        aprobar_inmediato: false,
    };
    
    recalculateTotalAmount();
    dialog.value = true;
};

const openEditPaymentDialog = (pago) => {
    if (!pago) return;
    isEditingPayment.value = true;
    editingPaymentId.value = pago.id;
    comprobanteFile.value = null;
    viewDialog.value = false;
    fetchCuentasBancariasAdmin();
    fetchBancosCatalogo();
    
    const coveredIds = pago.invoices && pago.invoices.length ? pago.invoices.map(i => i.id) : (pago.invoice_id ? [pago.invoice_id] : []);
    selectedAdminInvoiceIds.value = coveredIds;
    selectedAdminApartamentoId.value = pago.invoice?.apartamento_id || pago.invoices?.[0]?.apartamento_id || null;

    let formattedDate = new Date().toISOString().substring(0, 10);
    if (pago.fecha_pago) {
        if (typeof pago.fecha_pago === 'string') {
            formattedDate = pago.fecha_pago.substring(0, 10);
        } else {
            formattedDate = new Date(pago.fecha_pago).toISOString().substring(0, 10);
        }
    }

    form.value = {
        invoice_ids: coveredIds,
        invoice_id: pago.invoice_id,
        cuenta_bancaria_id: pago.cuenta_bancaria_id || null,
        monto: pago.monto,
        monto_divisa: pago.monto_divisa || (Number(pago.monto) / (Number(pago.tasa_cambio) || 36.50)).toFixed(2),
        moneda_origen: pago.moneda_origen || 'VES',
        metodo_pago: pago.metodo_pago || 'pago_movil',
        banco: pago.banco || pago.banco_origen || 'Banco de Venezuela',
        banco_origen: pago.banco_origen || pago.banco || 'Banco de Venezuela',
        telefono_origen: pago.telefono_origen || '',
        cedula_origen: pago.cedula_origen || '',
        referencia: pago.referencia || '',
        fecha_pago: formattedDate,
        observaciones: pago.observaciones || '',
        aprobar_inmediato: false,
    };
    dialog.value = true;
};

const submitPayment = async () => {
    // 1. Validación de Inmueble para Administrador
    if (!authStore.isPropietario && !isEditingPayment.value && !selectedAdminApartamentoId.value) {
        authStore.notify('Por favor selecciona primero el inmueble / copropietario.', 'warning');
        return;
    }

    // 2. Validación de Recibos Seleccionados
    if (!selectedAdminInvoiceIds.value.length) {
        authStore.notify('Debe seleccionar al menos un recibo de cobro a saldar.', 'warning');
        return;
    }

    // 3. Validación de Monto
    if (!form.value.monto || Number(form.value.monto) <= 0) {
        authStore.notify('Debe ingresar un monto válido a pagar (mayor a 0).', 'warning');
        return;
    }

    // 4. Validación de Fecha
    if (!form.value.fecha_pago) {
        authStore.notify('Debe indicar la fecha del pago.', 'warning');
        return;
    }

    // 5. Validaciones según método de pago
    if (form.value.metodo_pago === 'pago_movil') {
        if (!form.value.banco_origen || !form.value.banco_origen.trim()) {
            authStore.notify('Debe indicar el banco emisor desde donde realizó el Pago Móvil.', 'warning');
            return;
        }
        if (!form.value.telefono_origen || form.value.telefono_origen.trim().length < 7) {
            authStore.notify('Debe ingresar el número de teléfono emisor del Pago Móvil.', 'warning');
            return;
        }
        if (!form.value.referencia || form.value.referencia.trim().length < 4) {
            authStore.notify('Debe ingresar el número de referencia o confirmación del Pago Móvil.', 'warning');
            return;
        }
    } else if (form.value.metodo_pago === 'transferencia') {
        if (!form.value.banco_origen || !form.value.banco_origen.trim()) {
            authStore.notify('Debe indicar el banco emisor de la transferencia.', 'warning');
            return;
        }
        if (!form.value.referencia || form.value.referencia.trim().length < 4) {
            authStore.notify('Debe ingresar el número de referencia de la transferencia bancaria.', 'warning');
            return;
        }
    } else if (form.value.metodo_pago === 'zelle') {
        if (!form.value.banco_origen || !form.value.banco_origen.trim()) {
            authStore.notify('Debe ingresar el correo o titular de la cuenta Zelle emisora.', 'warning');
            return;
        }
        if (!form.value.referencia || form.value.referencia.trim().length < 3) {
            authStore.notify('Debe ingresar el código de confirmación Zelle.', 'warning');
            return;
        }
        if (!form.value.monto_divisa || Number(form.value.monto_divisa) <= 0) {
            authStore.notify('Debe ingresar el monto en dólares ($ USD).', 'warning');
            return;
        }
    } else if (form.value.metodo_pago === 'efectivo_usd') {
        if (!form.value.monto_divisa || Number(form.value.monto_divisa) <= 0) {
            authStore.notify('Debe ingresar el monto en dólares ($ USD) en efectivo a entregar.', 'warning');
            return;
        }
    }

    saving.value = true;
    form.value.invoice_ids = selectedAdminInvoiceIds.value;
    form.value.invoice_id = selectedAdminInvoiceIds.value[0];

    const tasa = Number(activeTasaCambio.value || 36.50);
    if (form.value.metodo_pago === 'efectivo_usd' || form.value.metodo_pago === 'zelle') {
        const usd = Number(form.value.monto_divisa || 0);
        form.value.moneda_origen = 'USD';
        form.value.monto = (usd * tasa).toFixed(2);
        form.value.monto_divisa = usd.toFixed(2);
    } else {
        const bs = Number(form.value.monto || 0);
        form.value.moneda_origen = 'VES';
        form.value.monto = bs.toFixed(2);
        form.value.monto_divisa = (bs / tasa).toFixed(2);
    }

    try {
        const formData = new FormData();
        selectedAdminInvoiceIds.value.forEach(id => {
            formData.append('invoice_ids[]', id);
        });
        formData.append('invoice_id', selectedAdminInvoiceIds.value[0]);
        if (form.value.cuenta_bancaria_id) formData.append('cuenta_bancaria_id', form.value.cuenta_bancaria_id);
        formData.append('monto', form.value.monto);
        if (form.value.monto_divisa) formData.append('monto_divisa', form.value.monto_divisa);
        formData.append('moneda_origen', form.value.moneda_origen || 'VES');
        formData.append('metodo_pago', form.value.metodo_pago);
        formData.append('banco', form.value.banco_origen || form.value.banco || 'Banco');
        formData.append('banco_origen', form.value.banco_origen || form.value.banco || 'Banco');
        if (form.value.telefono_origen) formData.append('telefono_origen', form.value.telefono_origen);
        if (form.value.cedula_origen) formData.append('cedula_origen', form.value.cedula_origen);
        if (form.value.referencia) formData.append('referencia', form.value.referencia);
        formData.append('fecha_pago', form.value.fecha_pago);
        if (form.value.observaciones) formData.append('observaciones', form.value.observaciones);
        if (form.value.aprobar_inmediato) formData.append('aprobar_inmediato', '1');
        if (comprobanteFile.value) {
            formData.append('comprobante', comprobanteFile.value);
        }

        if (isEditingPayment.value && editingPaymentId.value) {
            await axios.put(`/payments/${editingPaymentId.value}`, form.value);
            authStore.notify('Registro de pago modificado correctamente.', 'success');
        } else {
            const { data } = await axios.post('/payments', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            authStore.notify(data.message || 'Pago registrado exitosamente', 'success');
        }
        dialog.value = false;
        fetchData();
    } catch (e) {
        const msg = e.response?.data?.message || 'Error al procesar pago';
        authStore.notify(msg, 'error');
    } finally {
        saving.value = false;
    }
};

const approvingPaymentId = ref(null);

const approvePayment = async (id) => {
    if (approvingPaymentId.value === id) return;
    approvingPaymentId.value = id;
    try {
        const response = await axios.put(`/payments/${id}/aprobar`);
        const msg = response.data?.message || 'Pago aprobado y aplicado a la factura exitosamente';
        authStore.notify(msg, 'success');
        await fetchData();
    } catch (e) {
        const msg = e.response?.data?.message || 'Error al aprobar pago';
        if (e.response?.status === 400 && msg.includes('ya ha sido verificado')) {
            authStore.notify(msg, 'info');
            await fetchData();
        } else {
            authStore.notify(msg, 'error');
        }
    } finally {
        approvingPaymentId.value = null;
    }
};

const resendingEmailId = ref(null);

const resendPaymentReceiptEmail = async (id) => {
    if (!id || resendingEmailId.value === id) return;
    resendingEmailId.value = id;
    try {
        const { data } = await axios.post(`/payments/${id}/reenviar-email`);
        authStore.notify(data.message || 'Recibo RP reenviado exitosamente por correo electrónico.', 'success');
    } catch (e) {
        const msg = e.response?.data?.message || 'Error al reenviar el recibo por correo.';
        authStore.notify(msg, 'error');
    } finally {
        resendingEmailId.value = null;
    }
};

onMounted(fetchData);
</script>

