<template>
  <div>
    <!-- Top Header -->
    <div class="d-flex flex-wrap justify-space-between align-center gap-3 mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold text-slate-900">Contabilidad y Finanzas del Condominio</h1>
        <p class="text-caption text-slate-500">Libro Mayor, Estado de Resultados, Catálogo de Gastos y Emisión Mensual de Recibos</p>
      </div>

      <div class="d-flex flex-wrap align-center gap-2">
        <v-btn color="purple-darken-2" prepend-icon="mdi-lightning-bolt" variant="flat" class="elevation-1" @click="openCuotaExtraordinariaModal">
           Emitir Cuota Extraordinaria
        </v-btn>
        <v-btn color="success" prepend-icon="mdi-receipt-text-plus" variant="flat" @click="openEmisionDialog()">
          Preparar Recibo del Mes
        </v-btn>
        <v-btn color="emerald-darken-1" prepend-icon="mdi-receipt-plus" variant="tonal" @click="openNuevoGastoDialog">
          Registrar Gasto / Factura
        </v-btn>
        <v-btn color="primary" prepend-icon="mdi-calculator" variant="tonal" @click="openConceptoDialog">
          Nuevo Concepto
        </v-btn>
        <v-btn color="secondary" prepend-icon="mdi-printer" variant="tonal" href="/api/v1/reportes/morosos" target="_blank">
          Reporte Morosos (PDF)
        </v-btn>
      </div>
    </div>

    <!-- Accounting Tabs -->
    <v-tabs v-model="tab" color="primary" class="mb-6 bg-white border border-slate-100 rounded-lg">
      <v-tab value="recibos" prepend-icon="mdi-shield-check">Recibos y Certificación Mensual</v-tab>
      <v-tab value="conceptos" prepend-icon="mdi-format-list-numbered">Catálogo de Gastos</v-tab>
      <v-tab value="mayor" prepend-icon="mdi-book-open-page-variant">Libro Mayor</v-tab>
      <v-tab value="resultados" prepend-icon="mdi-chart-areaspline">Estado de Resultados (P&G)</v-tab>
      <v-tab value="cobrar" prepend-icon="mdi-account-cash">Cuentas por Cobrar (Mora)</v-tab>
      <v-tab value="pagar" prepend-icon="mdi-receipt">Gastos Ejecutados</v-tab>
    </v-tabs>

    <v-window v-model="tab">
      <!-- 0. Monthly Receipts & Certification Tab -->
      <v-window-item value="recibos">
        <v-card class="bg-white border border-slate-100 mb-6">
          <div class="pa-4 d-flex justify-space-between align-center border-b border-slate-100 flex-wrap gap-2">
            <div>
              <div class="font-weight-bold text-slate-900 text-subtitle-1">Control de Recibos Mensuales y Cuotas Extraordinarias</div>
              <div class="text-caption text-slate-500">
                Los recibos se preparan en <strong>Borrador</strong> para su revisión. Al <strong>Certificarlos</strong>, quedan bloqueados permanentemente para garantizar la integridad contable.
              </div>
            </div>
            
          </div>

          <DataTableHeader
            v-model:search="pPeriodos.search.value"
            v-model:per-page="pPeriodos.perPage.value"
            :per-page-options="pPeriodos.perPageOptions"
            placeholder="Buscar por período, proyecto, estado..."
          />

          <v-table density="comfortable" hover>
            <thead>
              <tr class="bg-slate-50 text-slate-700">
                <SortHeader col-key="periodo" width="180px" :sort-by="pPeriodos.sortBy.value" :sort-desc="pPeriodos.sortDesc.value" @sort="pPeriodos.sort">
                  Período / Proyecto
                </SortHeader>
                <SortHeader col-key="total_apartamentos" width="85px" :sort-by="pPeriodos.sortBy.value" :sort-desc="pPeriodos.sortDesc.value" @sort="pPeriodos.sort">
                  Aptos
                </SortHeader>
                <SortHeader col-key="total_monto_usd" width="150px" :sort-by="pPeriodos.sortBy.value" :sort-desc="pPeriodos.sortDesc.value" @sort="pPeriodos.sort">
                  Presupuesto ($ / Bs.)
                </SortHeader>
                <SortHeader col-key="estado_certificacion" width="135px" :sort-by="pPeriodos.sortBy.value" :sort-desc="pPeriodos.sortDesc.value" @sort="pPeriodos.sort">
                  Certificación
                </SortHeader>
                <SortHeader col-key="fecha_certificacion" width="130px" :sort-by="pPeriodos.sortBy.value" :sort-desc="pPeriodos.sortDesc.value" @sort="pPeriodos.sort">
                  Certificado Por
                </SortHeader>
                <SortHeader col-key="veces_reabierto" width="115px" :sort-by="pPeriodos.sortBy.value" :sort-desc="pPeriodos.sortDesc.value" @sort="pPeriodos.sort">
                  Reaperturas
                </SortHeader>
                <th class="text-right" style="width: 220px; min-width: 200px;">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in pPeriodos.paginatedItems.value" :key="item.key">
                <td class="font-weight-bold text-slate-900">
                  <div class="d-flex align-center gap-1 flex-wrap">
                    <v-icon :icon="item.estado_certificacion === 'certificado' ? 'mdi-lock' : 'mdi-lock-open-variant'" size="small" class="mr-1" :color="item.estado_certificacion === 'certificado' ? 'success' : 'warning'" />
                    <v-chip
                      v-if="item.tipo_recibo === 'extraordinario'"
                      size="x-small"
                      color="purple-darken-2"
                      variant="flat"
                      class="font-weight-bold"
                    >
                      ⚡ CUOTA EXTRAORDINARIA
                    </v-chip>
                    <v-chip
                      v-else
                      size="x-small"
                      color="slate-600"
                      variant="tonal"
                      class="font-weight-bold"
                    >
                      📅 RECIBO DEL MES
                    </v-chip>
                    <span>{{ item.periodo }}</span>
                  </div>
                  <div v-if="item.titulo_proyecto" class="text-caption font-weight-bold text-purple-900 mt-0.5">
                    {{ item.titulo_proyecto }}
                  </div>
                  <div v-if="item.numero_recibo_general" class="text-caption font-weight-bold text-primary">{{ item.numero_recibo_general }}</div>
                  <div class="text-caption text-slate-500">Emisión: {{ item.fecha_emision || '-' }}</div>
                </td>
                <td>
                  <v-chip size="small" color="primary" variant="tonal" class="font-weight-bold">
                    {{ item.total_apartamentos }} aptos
                  </v-chip>
                </td>
                <td>
                  <div class="font-weight-bold text-slate-900">${{ Number(item.total_monto_usd).toFixed(2) }} USD</div>
                  <div class="text-caption text-slate-500">{{ authStore.formatMoney(item.total_monto_bs) }}</div>
                </td>
                <td>
                  <v-chip
                    :color="item.estado_certificacion === 'certificado' ? 'success' : 'warning'"
                    size="small"
                    variant="flat"
                    class="font-weight-bold text-uppercase"
                  >
                    <v-icon start :icon="item.estado_certificacion === 'certificado' ? 'mdi-shield-check' : 'mdi-file-document-edit-outline'" />
                    {{ item.estado_certificacion === 'certificado' ? 'Certificado / Bloqueado' : 'Borrador / Editable' }}
                  </v-chip>
                </td>
                <td>
                  <div v-if="item.fecha_certificacion" class="text-caption text-slate-700 font-weight-medium">
                    {{ item.certificado_por || 'Administrador' }}
                    <div class="text-slate-400">{{ item.fecha_certificacion }}</div>
                  </div>
                  <div v-else class="text-caption text-amber-700 italic">
                    Pendiente de certificar
                  </div>
                </td>
                <td>
                  <v-chip
                    :color="item.veces_reabierto >= 2 ? 'error' : (item.veces_reabierto > 0 ? 'warning' : 'secondary')"
                    size="x-small"
                    variant="tonal"
                    class="font-weight-bold"
                  >
                    Reaperturas: {{ item.veces_reabierto }} / 2
                  </v-chip>
                  <div v-if="item.motivo_reapertura" class="text-caption text-slate-500 text-truncate" style="max-width: 140px;" :title="item.motivo_reapertura">
                    Motivo: {{ item.motivo_reapertura }}
                  </div>
                </td>
                <td class="text-right" style="width: 220px; min-width: 200px;">
                  <!-- Botón Azul: Ver Recibos por Propietario -->
                  <v-btn
                    color="primary"
                    size="small"
                    density="compact"
                    variant="flat"
                    prepend-icon="mdi-account-group"
                    class="mr-1.5 font-weight-bold"
                    title="Ver Recibos de Cobro por Propietario e Inmueble"
                    @click="openPropietariosModal(item)"
                  >
                    Recibos Aptos
                  </v-btn>

                  <!-- Imprimir / Descargar PDF del Recibo General del Edificio -->
                  <v-btn
                    icon="mdi-file-pdf-box"
                    size="small"
                    density="compact"
                    variant="text"
                    :color="item.estado_certificacion === 'borrador' ? 'amber-darken-3' : 'primary'"
                    class="mr-0.5"
                    :title="item.estado_certificacion === 'borrador' ? 'Imprimir PDF Borrador del Recibo General del Edificio' : 'Descargar PDF Recibo General del Edificio'"
                    :href="'/api/v1/reportes/recibo-general?periodo=' + encodeURIComponent(item.periodo) + '&condominio_id=' + item.condominio_id"
                    target="_blank"
                  />

                  <!-- Ver Vista Previa / Desglose -->
                  <v-btn icon="mdi-eye" size="small" density="compact" variant="text" color="primary" class="mr-0.5" title="Ver Desglose del Presupuesto" @click="verDetallePeriodo(item)" />

                  <!-- Certificar y Bloquear (si está en borrador) -->
                  <v-btn
                    v-if="item.estado_certificacion === 'borrador'"
                    icon="mdi-shield-check"
                    size="small"
                    density="compact"
                    variant="tonal"
                    color="success"
                    class="mr-0.5"
                    title="Certificar y Bloquear Recibo"
                    @click="confirmarCertificacion(item.periodo)"
                  />

                  <!-- Editar / Regenerar (si está en borrador) -->
                  <v-btn
                    v-if="item.estado_certificacion === 'borrador'"
                    icon="mdi-pencil"
                    size="small"
                    density="compact"
                    variant="text"
                    color="amber-darken-3"
                    class="mr-0.5"
                    title="Editar Conceptos y Montos"
                    @click="openEmisionDialog(item)"
                  />

                  <!-- Solicitar Desbloqueo (si está certificado) -->
                  <v-btn
                    v-if="item.estado_certificacion === 'certificado'"
                    icon="mdi-lock-alert"
                    size="small"
                    density="compact"
                    variant="tonal"
                    :color="item.veces_reabierto >= 2 ? 'grey' : 'amber-darken-3'"
                    title="Solicitar Desbloqueo al Super Admin"
                    @click="mostrarInfoDesbloqueo(item)"
                  />
                </td>
              </tr>
              <tr v-if="!pPeriodos.paginatedItems.value.length">
                <td colspan="7" class="text-center py-6 text-slate-400">
                  {{ pPeriodos.search.value ? 'No se encontraron períodos que coincidan con la búsqueda.' : 'No hay recibos emitidos aún. Haz clic en "Preparar Recibo del Mes" para generar el primer período.' }}
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

      <!-- 1. Dynamic Concepts Catalog Tab -->
      <v-window-item value="conceptos">
        <!-- Resumen de Costo Total por Grupo de Alícuota -->
        <v-row class="mb-4" v-if="resumenPorGrupo.length">
          <v-col v-for="grp in resumenPorGrupo" :key="grp.ali" cols="12" sm="6" md="4">
            <v-card class="pa-4 bg-white border border-slate-100">
              <div class="d-flex justify-space-between align-center">
                <div>
                  <div class="text-caption text-slate-500 font-weight-bold">
                    GRUPO DE ALÍCUOTA {{ grp.ali }}
                  </div>
                  <div class="text-h6 font-weight-bold text-slate-900 mt-1">
                    ${{ grp.total_usd.toFixed(2) }} USD
                  </div>
                </div>
                <v-chip size="small" color="primary" variant="tonal" class="font-weight-bold">
                  {{ grp.cantidad }} conceptos
                </v-chip>
              </div>
              <div class="mt-2 text-caption text-slate-500">
                Representa el {{ totalConceptosUsd > 0 ? ((grp.total_usd / totalConceptosUsd) * 100).toFixed(1) : 0 }}% del presupuesto total
              </div>
            </v-card>
          </v-col>
        </v-row>

        <v-card class="bg-white border border-slate-100">
          <div class="pa-4 d-flex justify-space-between align-center border-b border-slate-100">
            <div>
              <div class="font-weight-bold text-slate-900 text-subtitle-1">Catálogo General de Gastos del Condominio</div>
              <div class="text-caption text-slate-500">
                Define los conceptos base del condominio. Cada mes podrás seleccionar cuáles conceptos se aplican al recibo.
              </div>
            </div>
            <v-btn color="primary" size="small" prepend-icon="mdi-plus" @click="openConceptoDialog">
              Agregar Concepto
            </v-btn>
          </div>

          <DataTableHeader
            v-model:search="pConceptos.search.value"
            v-model:per-page="pConceptos.perPage.value"
            :per-page-options="pConceptos.perPageOptions"
            placeholder="Buscar concepto o categoría..."
          />

          <v-table density="comfortable" hover>
            <thead>
              <tr class="bg-slate-50 text-slate-700">
                <SortHeader col-key="id" width="60px" :sort-by="pConceptos.sortBy.value" :sort-desc="pConceptos.sortDesc.value" @sort="pConceptos.sort">
                  N°
                </SortHeader>
                <SortHeader col-key="ali" width="80px" :sort-by="pConceptos.sortBy.value" :sort-desc="pConceptos.sortDesc.value" @sort="pConceptos.sort">
                  Ali
                </SortHeader>
                <SortHeader col-key="concepto" :sort-by="pConceptos.sortBy.value" :sort-desc="pConceptos.sortDesc.value" @sort="pConceptos.sort">
                  Concepto / Descripción del Gasto
                </SortHeader>
                <SortHeader col-key="categoria" :sort-by="pConceptos.sortBy.value" :sort-desc="pConceptos.sortDesc.value" @sort="pConceptos.sort">
                  Categoría
                </SortHeader>
                <SortHeader col-key="tipo" :sort-by="pConceptos.sortBy.value" :sort-desc="pConceptos.sortDesc.value" @sort="pConceptos.sort">
                  Tipo
                </SortHeader>
                <SortHeader col-key="monto_base" align="right" :sort-by="pConceptos.sortBy.value" :sort-desc="pConceptos.sortDesc.value" @sort="pConceptos.sort">
                  Monto Base ($ USD)
                </SortHeader>
                <th class="text-right">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(c, idx) in pConceptos.paginatedItems.value" :key="c.id">
                <td class="font-weight-bold">{{ (pConceptos.currentPage.value - 1) * pConceptos.perPage.value + idx + 1 }}</td>
                <td><v-chip size="x-small" color="primary" variant="flat">Ali {{ c.ali || '1' }}</v-chip></td>
                <td class="font-weight-bold text-slate-900">{{ c.concepto }}</td>
                <td class="text-caption text-capitalize">{{ c.categoria || 'Mantenimiento' }}</td>
                <td>
                  <v-chip size="x-small" :color="c.tipo === 'fijo' ? 'info' : 'warning'" variant="tonal" class="text-capitalize">
                    {{ c.tipo || 'Fijo' }}
                  </v-chip>
                </td>
                <td class="text-right font-weight-bold text-slate-900">
                  ${{ Number(c.monto_base).toFixed(2) }}
                </td>
                <td class="text-right">
                  <v-btn icon="mdi-pencil" size="small" variant="text" color="primary" @click="editConcepto(c)" />
                  <v-btn icon="mdi-delete" size="small" variant="text" color="error" @click="deleteConcepto(c.id)" />
                </td>
              </tr>
              <tr v-if="!pConceptos.paginatedItems.value.length">
                <td colspan="7" class="text-center py-6 text-slate-400">
                  {{ pConceptos.search.value ? 'No se encontraron conceptos que coincidan con la búsqueda.' : 'No hay conceptos configurados. Registra los gastos fijos del condominio para emitir los avisos de cobro.' }}
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="bg-slate-50 font-weight-bold">
                <td colspan="5" class="text-right">Total Presupuesto Mensual del Edificio:</td>
                <td class="text-right text-h6 text-primary">${{ totalConceptosUsd.toFixed(2) }} USD</td>
                <td></td>
              </tr>
            </tfoot>
          </v-table>

          <DataTableFooter
            v-model:current-page="pConceptos.currentPage.value"
            :total-pages="pConceptos.totalPages.value"
            :total-items="pConceptos.totalItems.value"
            :original-total="pConceptos.originalTotal.value"
            :start-index="pConceptos.startIndex.value"
            :end-index="pConceptos.endIndex.value"
            :search="pConceptos.search.value"
          />
        </v-card>
      </v-window-item>

      <!-- 2. Libro Mayor Tab -->
      <v-window-item value="mayor">
        <v-row class="mb-4">
          <v-col cols="12" sm="4">
            <v-card class="pa-4 bg-white border border-slate-100">
              <div class="text-caption text-slate-500">Ingresos Totales Registrados</div>
              <div class="text-h6 font-weight-bold text-success mt-1">
                {{ authStore.formatMoney(libroMayorData.total_ingresos_bs) }}
              </div>
            </v-card>
          </v-col>
          <v-col cols="12" sm="4">
            <v-card class="pa-4 bg-white border border-slate-100">
              <div class="text-caption text-slate-500">Egresos Totales Ejecutados</div>
              <div class="text-h6 font-weight-bold text-error mt-1">
                {{ authStore.formatMoney(libroMayorData.total_egresos_bs) }}
              </div>
            </v-card>
          </v-col>
          <v-col cols="12" sm="4">
            <v-card class="pa-4 bg-white border border-slate-100">
              <div class="text-caption text-slate-500">Saldo Disponible en Banco</div>
              <div class="text-h6 font-weight-bold text-primary mt-1">
                {{ authStore.formatMoney(libroMayorData.saldo_disponible_bs) }}
              </div>
            </v-card>
          </v-col>
        </v-row>

        <v-card class="bg-white border border-slate-100">
          <DataTableHeader
            v-model:search="pMayor.search.value"
            v-model:per-page="pMayor.perPage.value"
            :per-page-options="pMayor.perPageOptions"
            placeholder="Buscar por concepto, tipo o referencia..."
          />

          <v-table density="comfortable" hover>
            <thead>
              <tr class="bg-slate-50 text-slate-700">
                <SortHeader col-key="fecha" :sort-by="pMayor.sortBy.value" :sort-desc="pMayor.sortDesc.value" @sort="pMayor.sort">
                  Fecha
                </SortHeader>
                <SortHeader col-key="tipo" :sort-by="pMayor.sortBy.value" :sort-desc="pMayor.sortDesc.value" @sort="pMayor.sort">
                  Tipo de Movimiento
                </SortHeader>
                <SortHeader col-key="concepto" :sort-by="pMayor.sortBy.value" :sort-desc="pMayor.sortDesc.value" @sort="pMayor.sort">
                  Concepto / Descripción
                </SortHeader>
                <SortHeader col-key="referencia" :sort-by="pMayor.sortBy.value" :sort-desc="pMayor.sortDesc.value" @sort="pMayor.sort">
                  Referencia
                </SortHeader>
                <SortHeader col-key="monto_bs" align="right" :sort-by="pMayor.sortBy.value" :sort-desc="pMayor.sortDesc.value" @sort="pMayor.sort">
                  Monto
                </SortHeader>
              </tr>
            </thead>
            <tbody>
              <tr v-for="t in pMayor.paginatedItems.value" :key="t.id">
                <td>{{ t.fecha }}</td>
                <td>
                  <v-chip :color="t.tipo === 'ingreso' ? 'success' : 'error'" size="x-small" variant="flat" class="text-uppercase font-weight-bold">
                    {{ t.tipo }}
                  </v-chip>
                </td>
                <td class="font-weight-medium text-slate-800">{{ t.concepto }}</td>
                <td>{{ t.referencia || '-' }}</td>
                <td class="text-right font-weight-bold" :class="t.tipo === 'ingreso' ? 'text-success' : 'text-error'">
                  {{ t.tipo === 'ingreso' ? '+' : '-' }} {{ authStore.formatMoney(t.monto_bs) }}
                </td>
              </tr>
              <tr v-if="!pMayor.paginatedItems.value.length">
                <td colspan="5" class="text-center py-6 text-slate-400">
                  {{ pMayor.search.value ? 'No se encontraron movimientos que coincidan con la búsqueda.' : 'No hay movimientos contables registrados.' }}
                </td>
              </tr>
            </tbody>
          </v-table>

          <DataTableFooter
            v-model:current-page="pMayor.currentPage.value"
            :total-pages="pMayor.totalPages.value"
            :total-items="pMayor.totalItems.value"
            :original-total="pMayor.originalTotal.value"
            :start-index="pMayor.startIndex.value"
            :end-index="pMayor.endIndex.value"
            :search="pMayor.search.value"
          />
        </v-card>
      </v-window-item>

      <!-- 3. Estado de Resultados (P&G) Tab -->
      <v-window-item value="resultados">
        <v-card class="pa-6 bg-white border border-slate-100">
          <div class="text-subtitle-1 font-weight-bold text-slate-900 mb-4">
            Balance General de Ingresos vs Egresos del Período
          </div>

          <div class="pa-4 bg-slate-50 rounded-lg mb-4">
            <div class="d-flex justify-space-between align-center mb-2">
              <span class="text-body-2 text-slate-700">Total Cuotas Recaudadas:</span>
              <span class="font-weight-bold text-success">{{ authStore.formatMoney(resultadosData.ingresos_totales_bs) }}</span>
            </div>
            <div class="d-flex justify-space-between align-center mb-2">
              <span class="text-body-2 text-slate-700">Total Gastos Operativos:</span>
              <span class="font-weight-bold text-error">- {{ authStore.formatMoney(resultadosData.gastos_totales_bs) }}</span>
            </div>
            <v-divider class="my-2" />
            <div class="d-flex justify-space-between align-center text-subtitle-1 font-weight-bold">
              <span>Utilidad / Superávit Neto:</span>
              <span :class="resultadosData.utilidad_neta_bs >= 0 ? 'text-success' : 'text-error'">
                {{ authStore.formatMoney(resultadosData.utilidad_neta_bs) }}
              </span>
            </div>
          </div>
        </v-card>
      </v-window-item>

      <!-- 4. Cuentas por Cobrar (Mora) Tab -->
      <v-window-item value="cobrar">
        <v-card class="bg-white border border-slate-100">
          <div class="pa-4 d-flex justify-space-between align-center border-b border-slate-100">
            <div class="font-weight-bold text-slate-900">Listado de Facturas y Morosidad</div>
            <div class="text-caption text-slate-500">
              Total Deuda Global: <strong class="text-error">{{ authStore.formatMoney(cuentasCobrarData.total_deuda_bs) }}</strong>
            </div>
          </div>

          <DataTableHeader
            v-model:search="pCobrar.search.value"
            v-model:per-page="pCobrar.perPage.value"
            :per-page-options="pCobrar.perPageOptions"
            placeholder="Buscar por apartamento, propietario, N° factura..."
          />

          <v-table density="comfortable" hover>
            <thead>
              <tr class="bg-slate-50 text-slate-700">
                <SortHeader col-key="apartamento.numero" :sort-by="pCobrar.sortBy.value" :sort-desc="pCobrar.sortDesc.value" @sort="pCobrar.sort">
                  Apartamento
                </SortHeader>
                <SortHeader col-key="apartamento.propietarios.0.nombre_completo" :sort-by="pCobrar.sortBy.value" :sort-desc="pCobrar.sortDesc.value" @sort="pCobrar.sort">
                  Propietario
                </SortHeader>
                <SortHeader col-key="numero_factura" :sort-by="pCobrar.sortBy.value" :sort-desc="pCobrar.sortDesc.value" @sort="pCobrar.sort">
                  N° Factura
                </SortHeader>
                <SortHeader col-key="fecha_vencimiento" :sort-by="pCobrar.sortBy.value" :sort-desc="pCobrar.sortDesc.value" @sort="pCobrar.sort">
                  Vencimiento
                </SortHeader>
                <SortHeader col-key="interes_mora_bs" :sort-by="pCobrar.sortBy.value" :sort-desc="pCobrar.sortDesc.value" @sort="pCobrar.sort">
                  Interés Mora
                </SortHeader>
                <SortHeader col-key="monto_total" align="right" :sort-by="pCobrar.sortBy.value" :sort-desc="pCobrar.sortDesc.value" @sort="pCobrar.sort">
                  Total a Pagar
                </SortHeader>
              </tr>
            </thead>
            <tbody>
              <tr v-for="inv in pCobrar.paginatedItems.value" :key="inv.id">
                <td><strong>Apto. {{ inv.apartamento?.numero }}</strong></td>
                <td>{{ inv.apartamento?.propietarios?.[0]?.nombre_completo || 'N/A' }}</td>
                <td>{{ inv.numero_factura }}</td>
                <td>{{ inv.fecha_vencimiento }}</td>
                <td>
                  <v-chip v-if="inv.es_moroso" size="x-small" color="error" variant="flat">
                    + {{ authStore.formatMoney(inv.interes_mora_bs) }}
                  </v-chip>
                  <span v-else class="text-caption text-slate-400">Al día</span>
                </td>
                <td class="text-right font-weight-bold text-error">
                  {{ authStore.formatMoney(inv.total_a_pagar_bs) }}
                </td>
              </tr>
              <tr v-if="!pCobrar.paginatedItems.value.length">
                <td colspan="6" class="text-center py-6 text-slate-400">
                  {{ pCobrar.search.value ? 'No se encontraron facturas que coincidan con la búsqueda.' : 'No hay deudas pendientes. Todo el condominio se encuentra solvente.' }}
                </td>
              </tr>
            </tbody>
          </v-table>

          <DataTableFooter
            v-model:current-page="pCobrar.currentPage.value"
            :total-pages="pCobrar.totalPages.value"
            :total-items="pCobrar.totalItems.value"
            :original-total="pCobrar.originalTotal.value"
            :start-index="pCobrar.startIndex.value"
            :end-index="pCobrar.endIndex.value"
            :search="pCobrar.search.value"
          />
        </v-card>
      </v-window-item>

      <!-- 5. Cuentas por Pagar (Gastos) Tab -->
      <v-window-item value="pagar">
        <v-card class="bg-white border border-slate-100 mb-6">
          <div class="pa-4 d-flex justify-space-between align-center border-b border-slate-100 flex-wrap gap-2">
            <div>
              <div class="font-weight-bold text-slate-900 text-subtitle-1">Control de Gastos y Facturas de Proveedores</div>
              <div class="text-caption text-slate-500">
                Registra los egresos y facturas emitidas por contratistas. Podrás importarlos con 1 clic al preparar el recibo del mes.
              </div>
            </div>
            <v-btn color="emerald-darken-2" size="small" prepend-icon="mdi-plus-circle" variant="flat" @click="openNuevoGastoDialog">
              ➕ Registrar Factura de Proveedor
            </v-btn>
          </div>

          <DataTableHeader
            v-model:search="pGastos.search.value"
            v-model:per-page="pGastos.perPage.value"
            :per-page-options="pGastos.perPageOptions"
            placeholder="Buscar por descripción, proveedor, categoría..."
          />

          <v-table density="comfortable" hover>
            <thead>
              <tr class="bg-slate-50 text-slate-700">
                <SortHeader col-key="fecha_gasto" :sort-by="pGastos.sortBy.value" :sort-desc="pGastos.sortDesc.value" @sort="pGastos.sort">
                  Fecha
                </SortHeader>
                <SortHeader col-key="descripcion" :sort-by="pGastos.sortBy.value" :sort-desc="pGastos.sortDesc.value" @sort="pGastos.sort">
                  Descripción del Gasto
                </SortHeader>
                <SortHeader col-key="categoria" :sort-by="pGastos.sortBy.value" :sort-desc="pGastos.sortDesc.value" @sort="pGastos.sort">
                  Categoría
                </SortHeader>
                <SortHeader col-key="proveedor" :sort-by="pGastos.sortBy.value" :sort-desc="pGastos.sortDesc.value" @sort="pGastos.sort">
                  Proveedor / Factura
                </SortHeader>
                <SortHeader col-key="estado_pago" :sort-by="pGastos.sortBy.value" :sort-desc="pGastos.sortDesc.value" @sort="pGastos.sort">
                  Estado
                </SortHeader>
                <SortHeader col-key="monto_usd" align="right" :sort-by="pGastos.sortBy.value" :sort-desc="pGastos.sortDesc.value" @sort="pGastos.sort">
                  Monto ($ USD)
                </SortHeader>
                <SortHeader col-key="monto_bs" align="right" :sort-by="pGastos.sortBy.value" :sort-desc="pGastos.sortDesc.value" @sort="pGastos.sort">
                  Monto (Bs.)
                </SortHeader>
              </tr>
            </thead>
            <tbody>
              <tr v-for="exp in pGastos.paginatedItems.value" :key="exp.id">
                <td>{{ exp.fecha_gasto }}</td>
                <td class="font-weight-bold text-slate-900">{{ exp.descripcion }}</td>
                <td><v-chip size="small" color="secondary" variant="tonal" class="text-capitalize">{{ exp.categoria }}</v-chip></td>
                <td>
                  <div class="font-weight-medium text-slate-800">{{ exp.proveedor || 'Sin proveedor' }}</div>
                  <div class="text-caption text-slate-400 font-mono">{{ exp.referencia_pago || 'S/R' }}</div>
                </td>
                <td>
                  <v-chip :color="exp.estado_pago === 'pagado' ? 'success' : 'warning'" size="x-small" variant="flat" class="text-uppercase">
                    {{ exp.estado_pago }}
                  </v-chip>
                </td>
                <td class="text-right font-weight-bold text-slate-900">
                  ${{ Number(exp.monto_usd || (Number(exp.monto_bs || 0) / Number(exp.tasa_cambio || 36.5))).toFixed(2) }} USD
                </td>
                <td class="text-right font-weight-bold text-slate-600">
                  {{ authStore.formatMoney(exp.monto_bs) }}
                </td>
              </tr>
              <tr v-if="!pGastos.paginatedItems.value.length">
                <td colspan="7" class="text-center py-6 text-slate-400">
                  {{ pGastos.search.value ? 'No se encontraron gastos que coincidan con la búsqueda.' : 'No se han registrado gastos en este período.' }}
                </td>
              </tr>
            </tbody>
          </v-table>

          <DataTableFooter
            v-model:current-page="pGastos.currentPage.value"
            :total-pages="pGastos.totalPages.value"
            :total-items="pGastos.totalItems.value"
            :original-total="pGastos.originalTotal.value"
            :start-index="pGastos.startIndex.value"
            :end-index="pGastos.endIndex.value"
            :search="pGastos.search.value"
          />
        </v-card>
      </v-window-item>
    </v-window>

    <!-- Dialog for Adding / Editing Expense Concept -->
    <v-dialog v-model="conceptoDialog" max-width="500">
      <v-card class="pa-4">
        <v-card-title class="font-weight-bold">
          {{ isEditingConcepto ? 'Editar Concepto de Gasto' : 'Nuevo Concepto de Gasto' }}
        </v-card-title>
        <v-card-text>
          <v-form @submit.prevent="saveConcepto">
            <v-text-field v-model="conceptoForm.concepto" label="Descripción del Gasto (ej. Mantenimiento de Ascensores)" required class="mb-3" />
            <v-text-field v-model="conceptoForm.monto_base" label="Monto Total del Edificio ($ USD)" type="number" step="0.01" required class="mb-3" />
            <v-row>
              <v-col cols="12" sm="6">
                <v-select
                  v-model="conceptoForm.ali"
                  label="Grupo de Alícuota"
                  :items="alicuotaOptions"
                  item-title="title"
                  item-value="value"
                  class="mb-3"
                />
              </v-col>
              <v-col cols="12" sm="6">
                <v-select
                  v-model="conceptoForm.tipo"
                  label="Tipo de Gasto"
                  :items="[
                    { title: 'Fijo Común', value: 'fijo' },
                    { title: 'Variable Común', value: 'variable' },
                    { title: 'Extraordinario', value: 'extraordinario' },
                    { title: 'Gasto No Común / Cargo Particular', value: 'no_comun' }
                  ]"
                  item-title="title"
                  item-value="value"
                  class="mb-3"
                />
              </v-col>
            </v-row>
            <v-select v-model="conceptoForm.categoria" label="Categoría" :items="['servicios', 'mantenimiento', 'sueldos', 'suministros', 'otros']" class="mb-3" />
          </v-form>
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn variant="text" @click="conceptoDialog = false">Cancelar</v-btn>
          <v-btn color="primary" :loading="savingConcepto" @click="saveConcepto">Guardar Concepto</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Modal Wizard Moderno: Preparar Recibo del Mes (3 Pasos: Período -> Gastos/Reserva -> Alícuotas/Emisión) -->
    <ReciboMesModal
      v-model="reciboMesDialog"
      :condominio-id="authStore.user?.condominio_id"
      :tasa-cambio-central="authStore.tasaCambioCentral || authStore.tasaCambio || 36.50"
      :conceptos-base="conceptosList"
      :alicuotas-disponibles="alicuotasCatalogoList"
      :initial-data="editingReciboPeriodo"
      @guardado="fetchData"
    />

    <!-- Modal Registrar Factura de Proveedor / Gasto -->
    <v-dialog v-model="nuevoGastoDialog" max-width="620" persistent>
      <v-card class="pa-4 rounded-xl border border-slate-200">
        <v-card-title class="font-weight-bold d-flex justify-space-between align-center">
          <div class="d-flex align-center gap-2">
            <v-icon color="emerald">mdi-receipt-plus</v-icon>
            <span>Registrar Factura de Proveedor / Gasto</span>
          </div>
          <v-btn icon="mdi-close" variant="text" size="small" @click="nuevoGastoDialog = false" />
        </v-card-title>
        <v-card-text>
          <div class="text-caption text-slate-500 mb-4">
            Registra una factura o egreso de contratista/proveedor. Podrás importarlo automáticamente al recibo del mes con un solo clic.
          </div>

          <v-row dense>
            <v-col cols="12">
              <v-text-field
                v-model="gastoForm.descripcion"
                label="Descripción del Gasto / Servicio *"
                placeholder="Ej: Mantenimiento Preventivo de Ascensores - Schindler"
                variant="outlined"
                density="compact"
                required
              />
            </v-col>

            <v-col cols="12" sm="6">
              <v-text-field
                v-model.number="gastoForm.monto_usd"
                label="Monto en Divisas ($ USD) *"
                type="number"
                step="0.01"
                prefix="$"
                variant="outlined"
                density="compact"
                @update:model-value="onMontoUsdGastoChange"
              />
            </v-col>

            <v-col cols="12" sm="6">
              <v-text-field
                v-model.number="gastoForm.monto_bs"
                label="Monto en Bolívares (Bs.)"
                type="number"
                step="0.01"
                prefix="Bs."
                variant="outlined"
                density="compact"
                @update:model-value="onMontoBsGastoChange"
              />
            </v-col>

            <v-col cols="12" sm="6">
              <v-text-field
                :model-value="Number(authStore.tasaCambioCentral || authStore.tasaCambio || 36.50).toFixed(2)"
                label="Tasa Oficial BCV (Bs. / $)"
                readonly
                variant="filled"
                density="compact"
                prepend-inner-icon="mdi-lock"
                hint="Tasa BCV oficial activa"
                persistent-hint
              />
            </v-col>

            <v-col cols="12" sm="6">
              <v-select
                v-model="gastoForm.categoria"
                :items="['mantenimiento', 'servicios', 'sueldos', 'suministros', 'otros']"
                label="Categoría *"
                variant="outlined"
                density="compact"
              />
            </v-col>

            <v-col cols="12" sm="6">
              <v-text-field
                v-model="gastoForm.proveedor"
                label="Proveedor / Empresa"
                placeholder="Ej: Hidrocapital, Schindler, Cantv..."
                variant="outlined"
                density="compact"
              />
            </v-col>

            <v-col cols="12" sm="6">
              <v-text-field
                v-model="gastoForm.referencia_pago"
                label="Nº Factura / Referencia de Pago"
                placeholder="Ej: Factura 00482, Ref 789123"
                variant="outlined"
                density="compact"
              />
            </v-col>

            <v-col cols="12" sm="6">
              <v-text-field
                v-model="gastoForm.fecha_gasto"
                label="Fecha del Gasto *"
                type="date"
                variant="outlined"
                density="compact"
              />
            </v-col>

            <v-col cols="12" sm="6">
              <v-select
                v-model="gastoForm.estado_pago"
                :items="[
                  { title: 'Pagado', value: 'pagado' },
                  { title: 'Pendiente por Pagar', value: 'pendiente' }
                ]"
                label="Estado *"
                variant="outlined"
                density="compact"
              />
            </v-col>
          </v-row>
        </v-card-text>

        <v-card-actions class="d-flex justify-end gap-2 pa-4">
          <v-btn variant="text" color="slate-600" @click="nuevoGastoDialog = false">Cancelar</v-btn>
          <v-btn
            color="emerald-darken-2"
            variant="flat"
            prepend-icon="mdi-content-save"
            :loading="guardandoGasto"
            :disabled="!gastoForm.descripcion || (!gastoForm.monto_usd && !gastoForm.monto_bs)"
            @click="guardarNuevoGasto"
          >
            Guardar Factura
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dialog for Previewing Monthly Receipt Breakdown -->
    <v-dialog v-model="previewDialog" max-width="850" scrollable>
      <v-card class="pa-4" v-if="previewData">
        <v-card-title class="font-weight-bold d-flex justify-space-between align-center">
          <div>
            <span>Vista Previa del Recibo: {{ previewData.periodo }}</span>
            <div class="text-caption text-slate-500 font-weight-normal">{{ previewData.condominio_nombre }}</div>
          </div>
          <v-chip
            :color="previewData.estado_certificacion === 'certificado' ? 'success' : 'warning'"
            variant="flat"
            size="small"
            class="font-weight-bold text-uppercase"
          >
            {{ previewData.estado_certificacion === 'certificado' ? 'Certificado / Bloqueado' : 'Borrador' }}
          </v-chip>
        </v-card-title>
        <v-card-text>
          <!-- Summary Cards -->
          <v-row class="mb-4">
            <v-col cols="12" sm="4">
              <v-card class="pa-3 bg-slate-50 border border-slate-200">
                <div class="text-caption text-slate-500 font-weight-bold">TOTAL FACTURADO ($ USD)</div>
                <div class="text-h6 font-weight-bold text-primary">${{ Number(previewData.total_monto_usd).toFixed(2) }} USD</div>
              </v-card>
            </v-col>
            <v-col cols="12" sm="4">
              <v-card class="pa-3 bg-slate-50 border border-slate-200">
                <div class="text-caption text-slate-500 font-weight-bold">TOTAL EN BOLÍVARES (BS.)</div>
                <div class="text-h6 font-weight-bold text-success">{{ authStore.formatMoney(previewData.total_monto_bs) }}</div>
              </v-card>
            </v-col>
            <v-col cols="12" sm="4">
              <v-card class="pa-3 bg-slate-50 border border-slate-200">
                <div class="text-caption text-slate-500 font-weight-bold">TASA DE CAMBIO BCV</div>
                <div class="text-h6 font-weight-bold text-slate-800">Bs. {{ Number(previewData.tasa_cambio || authStore.tasaCambioCentral || authStore.tasaCambio || 36.50).toFixed(2) }} / $</div>
              </v-card>
            </v-col>
          </v-row>

          <div class="text-subtitle-2 font-weight-bold text-slate-900 mb-2">Desglose de Gastos Congelados para el Recibo:</div>
          <v-table density="compact" class="border border-slate-200 rounded mb-4">
            <thead>
              <tr class="bg-slate-50">
                <th>Ali</th>
                <th>Concepto</th>
                <th class="text-right">Monto Total ($ USD)</th>
                <th class="text-right">Monto Cuota Alícuota ($ USD)</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(g, idx) in previewData.detalles_gastos" :key="idx">
                <td><v-chip size="x-small" color="primary">Ali {{ g.ali || '1' }}</v-chip></td>
                <td class="font-weight-medium">{{ g.concepto }}</td>
                <td class="text-right font-weight-bold">${{ Number(g.monto).toFixed(2) }}</td>
                <td class="text-right text-slate-600">${{ Number(g.alicu || 0).toFixed(2) }}</td>
              </tr>
              <tr v-if="previewData.fondos" class="bg-amber-50/50">
                <td><v-chip size="x-small" color="amber-darken-3">Fondo</v-chip></td>
                <td class="font-weight-bold text-amber-900">{{ previewData.fondos.nombre || 'FONDO DE RESERVA' }}</td>
                <td class="text-right font-weight-bold text-amber-900">${{ Number(previewData.fondos.monto_mes || 0).toFixed(2) }}</td>
                <td class="text-right font-weight-bold text-amber-900">${{ Number(previewData.fondos.monto_alicuota || 0).toFixed(2) }}</td>
              </tr>
            </tbody>
          </v-table>
        </v-card-text>
        <v-card-actions class="justify-end gap-2">
          <v-btn variant="text" @click="previewDialog = false">Cerrar</v-btn>
          <v-btn
            :href="'/api/v1/reportes/recibo-periodo?periodo=' + encodeURIComponent(previewData.periodo) + '&condominio_id=' + previewData.condominio_id"
            target="_blank"
            :color="previewData.estado_certificacion === 'borrador' ? 'amber-darken-3' : 'primary'"
            variant="tonal"
            prepend-icon="mdi-printer"
          >
            {{ previewData.estado_certificacion === 'borrador' ? 'Imprimir PDF Muestra (Borrador)' : 'Descargar PDF Oficial' }}
          </v-btn>
          <v-btn
            v-if="previewData.estado_certificacion === 'borrador'"
            color="success"
            variant="flat"
            prepend-icon="mdi-shield-check"
            @click="confirmarCertificacion(previewData.periodo)"
          >
            Certificar y Bloquear Recibo
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dialog for Information about Unlocking by Super Admin -->
    <v-dialog v-model="desbloqueoInfoDialog" max-width="500">
      <v-card class="pa-4">
        <v-card-title class="font-weight-bold text-amber-darken-4 d-flex align-center">
          <v-icon icon="mdi-lock-alert" color="amber-darken-3" class="mr-2" />
          Recibo Certificado y Bloqueado
        </v-card-title>
        <v-card-text>
          <p class="text-body-2 text-slate-700 mb-3">
            El recibo para el período <strong>{{ selectedPeriodoObj?.periodo }}</strong> ya fue certificado y sus valores han sido bloqueados para garantizar la transparencia contable con los residentes.
          </p>
          <v-alert
            :type="selectedPeriodoObj?.veces_reabierto >= 2 ? 'error' : 'warning'"
            variant="tonal"
            density="compact"
            class="mb-3 text-caption"
          >
            <div class="font-weight-bold">Historial de Reaperturas:</div>
            <div>Utilizadas: <strong>{{ selectedPeriodoObj?.veces_reabierto }} de 2 máximas permitidas</strong>.</div>
            <div v-if="selectedPeriodoObj?.veces_reabierto >= 2" class="mt-1 font-weight-bold text-error">
              ¡Se ha alcanzado el límite máximo de reaperturas! Este recibo no puede volver a modificarse.
            </div>
            <div v-else class="mt-1">
              Quedan <strong>{{ selectedPeriodoObj?.reaperturas_restantes }}</strong> reaperturas posibles.
            </div>
          </v-alert>
          <p v-if="selectedPeriodoObj?.veces_reabierto < 2" class="text-caption text-slate-500">
            Si requieres corregir algún monto o concepto, contacta al <strong>Super Administrador del Sistema</strong>. Él podrá devolver este recibo a estado borrador desde el panel de Super Admin.
          </p>
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn color="primary" variant="flat" @click="desbloqueoInfoDialog = false">Entendido</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dialog: Recibos por Propietario (Botón Azul) -->
    <v-dialog v-model="recibosPropietariosDialog" max-width="1280" width="96vw" scrollable>
      <v-card class="pa-4" v-if="selectedPeriodoForPropietarios">
        <v-card-title class="font-weight-bold d-flex justify-space-between align-center border-b pb-3">
          <div>
            <span>Recibos de Cobro por Propietario e Inmueble</span>
            <div class="text-caption text-slate-500 font-weight-normal">
              Período: <strong>{{ selectedPeriodoForPropietarios.periodo }}</strong> | {{ selectedPeriodoForPropietarios.condominio_nombre }}
            </div>
          </div>
          <div class="d-flex align-center gap-2">
            <v-chip
              :color="selectedPeriodoForPropietarios.estado_certificacion === 'certificado' ? 'success' : 'warning'"
              size="small"
              variant="flat"
              class="font-weight-bold text-uppercase"
            >
              <v-icon start :icon="selectedPeriodoForPropietarios.estado_certificacion === 'certificado' ? 'mdi-shield-check' : 'mdi-file-document-edit-outline'" />
              {{ selectedPeriodoForPropietarios.estado_certificacion === 'certificado' ? 'Certificado / Bloqueado' : 'Borrador / Editable' }}
            </v-chip>
            <v-btn icon="mdi-close" variant="text" size="small" @click="recibosPropietariosDialog = false" />
          </div>
        </v-card-title>

        <v-card-text class="pt-4">
          <div v-if="loadingPropietariosInvoices" class="text-center py-6 text-slate-500">
            <v-progress-circular indeterminate color="primary" class="mr-2" size="24" />
            Cargando recibos por propietario...
          </div>

          <div v-else>
            <div class="d-flex flex-column flex-sm-row justify-space-between align-start align-sm-center gap-3 mb-3">
              <div class="d-flex align-center gap-2">
                <div class="text-caption text-slate-600">
                  Inmuebles facturados en este período: <strong>{{ propietariosInvoicesList.length }}</strong>
                </div>
                <!-- Botón de Descarga Consolidada de Todos los Recibos en un Solo PDF -->
                <v-btn
                  color="secondary"
                  size="small"
                  variant="tonal"
                  prepend-icon="mdi-file-pdf-box"
                  class="font-weight-bold ml-2 shadow-sm"
                  :href="`/api/v1/reportes/recibos-lote-pdf?periodo=${encodeURIComponent(selectedPeriodoForPropietarios.periodo)}&condominio_id=${selectedPeriodoForPropietarios.condominio_id}&tipo_recibo=${selectedPeriodoForPropietarios.tipo_recibo || 'ordinario'}`"
                  target="_blank"
                  title="Descargar todos los recibos de este período en un solo archivo PDF paginado"
                >
                  📄 Descargar Todos en un Solo PDF
                </v-btn>

                <!-- Botón de Envío Masivo por Correo (Solo Períodos Certificados) -->
                <v-btn
                  v-if="selectedPeriodoForPropietarios.estado_certificacion === 'certificado'"
                  color="primary"
                  size="small"
                  variant="flat"
                  prepend-icon="mdi-email-fast-outline"
                  class="font-weight-bold ml-2 shadow-sm"
                  @click="openEnviarRecibosModal"
                >
                  Enviar Recibos por Correo
                </v-btn>
              </div>
              <v-text-field
                v-model="searchPropietarioInvoices"
                placeholder="Buscar por apto, propietario..."
                density="compact"
                variant="outlined"
                hide-details
                style="max-width: 250px;"
                prepend-inner-icon="mdi-magnify"
              />
            </div>

            <v-table density="compact" hover class="border border-slate-200 rounded">
              <thead>
                <tr class="bg-slate-50 text-slate-700">
                  <SortHeader col-key="apartamento.numero" width="90px" :sort-by="propSortBy" :sort-desc="propSortDesc" @sort="sortPropInvoices">
                    Apartamento
                  </SortHeader>
                  <SortHeader col-key="propietario" width="180px" :sort-by="propSortBy" :sort-desc="propSortDesc" @sort="sortPropInvoices">
                    Propietario / Residente
                  </SortHeader>
                  <SortHeader col-key="alicuota" width="80px" align="center" :sort-by="propSortBy" :sort-desc="propSortDesc" @sort="sortPropInvoices">
                    Alícuota %
                  </SortHeader>
                  <SortHeader col-key="cuota_comun" width="105px" align="right" :sort-by="propSortBy" :sort-desc="propSortDesc" @sort="sortPropInvoices">
                    Cuota Común ($)
                  </SortHeader>
                  <SortHeader col-key="gastos_no_comunes" width="120px" align="right" :sort-by="propSortBy" :sort-desc="propSortDesc" @sort="sortPropInvoices">
                    Gastos No Comunes ($)
                  </SortHeader>
                  <SortHeader col-key="total" width="125px" align="right" :sort-by="propSortBy" :sort-desc="propSortDesc" @sort="sortPropInvoices">
                    Total Recibo ($ / Bs.)
                  </SortHeader>
                  <SortHeader col-key="estado" width="85px" align="center" :sort-by="propSortBy" :sort-desc="propSortDesc" @sort="sortPropInvoices">
                    Estado
                  </SortHeader>
                  <th class="text-right" style="width: 125px; min-width: 120px;">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="inv in filteredPropietariosInvoices" :key="inv.id">
                  <td class="font-weight-bold text-slate-900" style="width: 90px;">
                    <v-chip size="small" color="primary" variant="flat" class="font-weight-bold">
                      Apto {{ inv.apartamento?.numero || 'N/A' }}
                    </v-chip>
                    <div class="text-caption text-slate-500">Piso {{ inv.apartamento?.piso || '-' }}</div>
                  </td>
                  <td style="max-width: 180px;">
                    <div class="font-weight-bold text-slate-800 text-uppercase text-truncate" :title="inv.apartamento?.propietarios?.[0]?.nombre_completo || inv.apartamento?.propietarios?.[0]?.name || 'Propietario no asignado'">
                      {{ inv.apartamento?.propietarios?.[0]?.nombre_completo || inv.apartamento?.propietarios?.[0]?.name || 'Propietario no asignado' }}
                    </div>
                    <div class="text-caption text-slate-400">
                      {{ inv.apartamento?.propietarios?.[0]?.cedula || '' }}
                    </div>
                  </td>
                  <td class="text-center" style="width: 80px;">
                    <v-chip size="x-small" color="info" variant="tonal" class="font-weight-bold">
                      {{ Number(inv.apartamento?.alicuota || 3.0346).toFixed(4) }}%
                    </v-chip>
                  </td>
                  <td class="text-right font-weight-bold text-slate-700" style="width: 105px;">
                    ${{ Number(inv.monto_alicuota_usd || 0).toFixed(2) }}
                  </td>
                  <td class="text-right" style="width: 120px;">
                    <div v-if="getGastosNoComunes(inv).length > 0">
                      <div class="font-weight-bold text-success text-caption">
                        +${{ getGastosNoComunesTotal(inv).toFixed(2) }}
                      </div>
                      <div class="d-flex flex-column align-end gap-1 mt-1">
                        <span v-for="(nc, ncIdx) in getGastosNoComunes(inv)" :key="ncIdx" class="text-caption text-slate-600 bg-emerald-50 px-1 py-0.5 rounded border border-emerald-200 d-inline-flex align-center gap-1 text-truncate" style="max-width: 115px;" :title="`${nc.concepto}: $${Number(nc.alicu || nc.monto).toFixed(2)}`">
                          {{ nc.concepto }}: ${{ Number(nc.alicu || nc.monto).toFixed(2) }}
                          <v-icon
                            v-if="selectedPeriodoForPropietarios.estado_certificacion === 'borrador'"
                            icon="mdi-close-circle"
                            size="x-small"
                            color="error"
                            class="cursor-pointer ml-1"
                            title="Eliminar este gasto no común"
                            @click="confirmarEliminarGastoNc(inv, ncOriginalIndex(inv, nc))"
                          />
                        </span>
                      </div>
                    </div>
                    <div v-else class="text-caption text-slate-400 italic">
                      Sin cargos directos
                    </div>
                  </td>
                  <td class="text-right" style="width: 125px;">
                    <div class="font-weight-bold text-primary">${{ Number(inv.monto_total_usd || (inv.monto_total / (inv.tasa_cambio || 36.5))).toFixed(2) }} USD</div>
                    <div class="text-caption text-slate-500">{{ authStore.formatMoney(inv.monto_total) }}</div>
                  </td>
                  <td class="text-center" style="width: 85px;">
                    <v-chip
                      :color="inv.estado === 'pagado' ? 'success' : (inv.estado_certificacion === 'certificado' ? 'info' : 'warning')"
                      size="x-small"
                      variant="flat"
                      class="font-weight-bold text-uppercase"
                    >
                      {{ inv.estado === 'pagado' ? 'Solventado' : (inv.estado_certificacion === 'certificado' ? 'Certificado' : 'Borrador') }}
                    </v-chip>
                  </td>
                  <td class="text-right" style="width: 125px;">
                    <div class="d-flex align-center justify-end gap-1">
                      <!-- Botón Ver / Descargar Recibo Individual (PDF) -->
                      <v-btn
                        icon="mdi-file-pdf-box"
                        size="small"
                        density="compact"
                        variant="text"
                        :color="inv.estado_certificacion === 'borrador' ? 'amber-darken-3' : 'primary'"
                        title="Ver Recibo de Cobro Individual (PDF)"
                        :href="'/api/v1/reportes/recibo-invoice/' + inv.id"
                        target="_blank"
                      />

                      <!-- Botón Notificación de Cobro Directo por WhatsApp -->
                      <v-btn
                        icon="mdi-whatsapp"
                        size="small"
                        density="compact"
                        variant="text"
                        color="success"
                        title="Enviar estado de cuenta y recibo por WhatsApp al Propietario"
                        @click="enviarWhatsAppRecibo(inv)"
                      />

                      <!-- Botón Enviar Recibo por Correo Individual (Solo Certificado) -->
                      <v-btn
                        v-if="selectedPeriodoForPropietarios.estado_certificacion === 'certificado'"
                        icon="mdi-email-outline"
                        size="small"
                        density="compact"
                        variant="text"
                        color="indigo"
                        title="Enviar este recibo por correo al propietario"
                        @click="enviarReciboIndividual(inv)"
                      />

                      <!-- Botón "+" para Agregar Gasto No Común (Visible SOLO si está en Borrador y no certificado) -->
                      <v-btn
                        v-if="selectedPeriodoForPropietarios.estado_certificacion === 'borrador'"
                        icon="mdi-plus-circle"
                        size="small"
                        density="compact"
                        variant="flat"
                        color="success"
                        title="Agregar Gasto No Común a este Inmueble"
                        @click="openAgregarGastoNoComunModal(inv)"
                      />
                    </div>
                  </td>
                </tr>
                <tr v-if="!filteredPropietariosInvoices.length">
                  <td colspan="8" class="text-center py-6 text-slate-400">
                    No se encontraron inmuebles que coincidan con la búsqueda.
                  </td>
                </tr>
              </tbody>
            </v-table>
          </div>
        </v-card-text>

        <v-card-actions class="justify-end">
          <v-btn color="slate-700" variant="tonal" @click="recibosPropietariosDialog = false">Cerrar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Modal de Confirmación y Envío Masivo de Recibos por Correo -->
    <v-dialog v-model="enviarRecibosDialog" max-width="580" persistent>
      <v-card class="rounded-xl overflow-hidden border border-blue-100 shadow-2xl">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 text-white pa-4 d-flex align-center justify-space-between">
          <div class="d-flex align-center gap-3">
            <v-avatar color="blue-darken-1" size="40" class="elevation-2">
              <v-icon icon="mdi-email-fast-outline" color="white" size="24" />
            </v-avatar>
            <div>
              <div class="text-subtitle-1 font-weight-bold leading-tight">
                Envío de Recibos por Correo Electrónico
              </div>
              <div class="text-caption text-blue-200">
                Notificación oficial de cobro a los propietarios de la comunidad
              </div>
            </div>
          </div>
          <v-btn icon="mdi-close" variant="text" color="white" density="comfortable" :disabled="sendingEmails" @click="enviarRecibosDialog = false" />
        </div>

        <v-card-text class="pa-5">
          <v-alert type="info" variant="tonal" class="rounded-lg mb-4 text-caption font-weight-medium">
            <v-icon start icon="mdi-information-outline" />
            El sistema enviará a cada propietario un correo electrónico con el desglose de su cuota del período <strong>{{ selectedPeriodoForPropietarios?.periodo }}</strong>, las cuentas bancarias para pagar y el enlace directo para reportar su transferencia o pago móvil en el sistema.
          </v-alert>

          <div class="bg-slate-50 border border-slate-200 rounded-xl pa-3.5 mb-4">
            <div class="d-flex justify-space-between align-center mb-2">
              <span class="text-caption text-slate-500 font-weight-bold text-uppercase">Condominio:</span>
              <span class="text-caption font-weight-bold text-slate-900">{{ selectedPeriodoForPropietarios?.condominio_nombre }}</span>
            </div>
            <div class="d-flex justify-space-between align-center mb-2">
              <span class="text-caption text-slate-500 font-weight-bold text-uppercase">Período Facturado:</span>
              <span class="text-caption font-weight-bold text-primary">{{ selectedPeriodoForPropietarios?.periodo }}</span>
            </div>
            <div class="d-flex justify-space-between align-center mb-2">
              <span class="text-caption text-slate-500 font-weight-bold text-uppercase">Total Inmuebles a Notificar:</span>
              <span class="text-caption font-weight-bold text-slate-900">{{ targetInvoicesToSend.length }} Inmueble(s)</span>
            </div>
            <div class="d-flex justify-space-between align-center">
              <span class="text-caption text-slate-500 font-weight-bold text-uppercase">Documentos Incluidos:</span>
              <span class="text-caption font-weight-bold text-emerald-700">✓ Recibo Individual (RI) + Recibo General (RG)</span>
            </div>
          </div>

          <div v-if="sendingEmails" class="text-center py-4">
            <v-progress-circular indeterminate color="primary" size="48" width="4" class="mb-3" />
            <div class="text-subtitle-2 font-weight-bold text-slate-800">
              Procesando y despachando correos electrónicos...
            </div>
            <div class="text-caption text-slate-500">
              Por favor, espere mientras el servidor envía las notificaciones a los buzones de los propietarios.
            </div>
          </div>
        </v-card-text>

        <v-card-actions class="pa-4 bg-slate-50 d-flex justify-space-between border-t border-slate-100">
          <v-btn variant="text" color="slate-600" :disabled="sendingEmails" @click="enviarRecibosDialog = false">
            Cancelar
          </v-btn>
          <v-btn
            color="primary"
            variant="flat"
            prepend-icon="mdi-send-check"
            class="font-weight-bold px-5"
            :loading="sendingEmails"
            @click="confirmarEnvioRecibos"
          >
            Confirmar y Enviar {{ targetInvoicesToSend.length > 1 ? `(${targetInvoicesToSend.length} Inmuebles)` : 'Recibo' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dialog: Agregar Gasto No Común a Inmueble Específico -->
    <v-dialog v-model="agregarGastoNoComunDialog" max-width="500">
      <v-card class="pa-4">
        <v-card-title class="font-weight-bold d-flex align-center">
          <v-icon icon="mdi-cash-plus" color="success" class="mr-2" />
          Agregar Gasto No Común / Cargo Particular
        </v-card-title>
        <v-card-subtitle class="text-caption text-slate-500">
          Apartamento: <strong>Apto {{ selectedInvoiceForGastoNc?.apartamento?.numero }}</strong> ({{ selectedInvoiceForGastoNc?.apartamento?.propietarios?.[0]?.nombre_completo || 'Propietario' }})
        </v-card-subtitle>
        <v-card-text class="pt-3">
          <v-alert type="info" variant="tonal" density="compact" class="mb-3 text-caption">
            Este gasto se imputará al 100% de manera exclusiva a la factura individual de este apartamento y se sumará a su total a pagar.
          </v-alert>

          <v-form @submit.prevent="saveGastoNoComun">
            <div class="text-caption font-weight-bold text-slate-700 mb-1">Seleccionar del Catálogo de Gastos No Comunes:</div>
            <v-select
              v-model="selectedConceptoNoComunItem"
              :items="conceptosNoComunesOptions"
              item-title="label"
              return-object
              label="Concepto de Gasto No Común"
              placeholder="Selecciona un concepto..."
              variant="outlined"
              density="compact"
              class="mb-3"
              @update:model-value="onConceptoNoComunSelectChange"
            />

            <div class="text-caption font-weight-bold text-slate-700 mb-1">Descripción o Detalle del Cargo:</div>
            <v-text-field
              v-model="gastoNoComunForm.concepto"
              label="Concepto (ej. Reemplazo de Control de Portón)"
              required
              density="compact"
              variant="outlined"
              class="mb-3"
            />

            <div class="text-caption font-weight-bold text-slate-700 mb-1">Monto a Facturar ($ USD):</div>
            <v-text-field
              v-model.number="gastoNoComunForm.monto_usd"
              label="Monto ($ USD)"
              type="number"
              step="0.01"
              required
              prefix="$"
              density="compact"
              variant="outlined"
              class="mb-3 font-weight-bold"
            />
          </v-form>
        </v-card-text>

        <v-card-actions class="justify-end gap-2">
          <v-btn variant="text" @click="agregarGastoNoComunDialog = false">Cancelar</v-btn>
          <v-btn color="success" variant="flat" :loading="savingGastoNc" @click="saveGastoNoComun">
            Agregar al Recibo
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Modal de Notificación de Aplicación Automática de Notas de Crédito -->
    <v-dialog v-model="creditosAplicadosDialog" max-width="850" persistent scrollable>
      <v-card class="rounded-xl overflow-hidden border border-emerald-200 shadow-2xl">
        <!-- Header -->
        <div class="bg-gradient-to-r from-emerald-900 via-teal-900 to-slate-900 text-white pa-4 d-flex align-center justify-space-between">
          <div class="d-flex align-center gap-3">
            <v-avatar color="emerald-darken-1" size="40" class="elevation-2">
              <v-icon icon="mdi-cash-refund" color="white" size="24" />
            </v-avatar>
            <div>
              <div class="text-subtitle-1 font-weight-bold leading-tight">
                🎉 Aplicación Automática de Notas de Crédito
              </div>
              <div class="text-caption text-emerald-200">
                Se reconocieron y aplicaron excedentes a favor para la emisión del período
              </div>
            </div>
          </div>
          <v-chip color="emerald-accent-2" size="small" variant="flat" class="font-weight-bold text-slate-900">
            {{ creditosAplicadosList.length }} Pago(s) Generado(s)
          </v-chip>
        </div>

        <v-card-text class="pa-5">
          <v-alert type="success" variant="tonal" class="rounded-lg mb-4 text-caption font-weight-medium">
            <v-icon start icon="mdi-check-decagram" />
            El sistema detectó <strong>Notas de Crédito activas con saldo disponible</strong> para los siguientes copropietarios y generó automáticamente sus <strong>Notificaciones de Pago en estado pendiente</strong> para que el administrador las certifique.
          </v-alert>

          <div class="table-responsive-container border border-slate-200 rounded-lg overflow-hidden">
            <v-table density="comfortable" hover>
              <thead>
                <tr class="bg-slate-50 text-slate-700 text-caption font-weight-bold">
                  <th>Inmueble / Copropietario</th>
                  <th>Recibo / Monto</th>
                  <th>N° Nota Crédito</th>
                  <th class="text-right">Monto Aplicado</th>
                  <th class="text-right">Saldo Restante NC</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, idx) in creditosAplicadosList" :key="idx">
                  <td>
                    <div class="font-weight-bold text-slate-900">Apto. {{ item.apartamento_numero }}</div>
                    <div class="text-caption text-slate-500">{{ item.propietario }}</div>
                  </td>
                  <td>
                    <div class="font-weight-medium text-slate-800">{{ item.numero_factura }}</div>
                    <div class="text-caption text-slate-500 font-mono">
                      Bs. {{ Number(item.monto_recibo_bs).toLocaleString('es-VE', { minimumFractionDigits: 2 }) }} (${{ item.monto_recibo_usd }} USD)
                    </div>
                  </td>
                  <td>
                    <v-chip size="x-small" color="emerald-darken-2" variant="tonal" class="font-weight-bold font-mono">
                      {{ item.numero_nota_credito }}
                    </v-chip>
                  </td>
                  <td class="text-right">
                    <div class="font-weight-bold text-success font-mono">
                      - Bs. {{ Number(item.monto_aplicado_bs).toLocaleString('es-VE', { minimumFractionDigits: 2 }) }}
                    </div>
                    <div class="text-caption text-slate-500 font-mono">
                      (-${{ item.monto_aplicado_usd }} USD)
                    </div>
                  </td>
                  <td class="text-right">
                    <div class="font-weight-bold text-slate-700 font-mono">
                      Bs. {{ Number(item.saldo_restante_nc_bs).toLocaleString('es-VE', { minimumFractionDigits: 2 }) }}
                    </div>
                    <div class="text-caption text-slate-500 font-mono">
                      (${{ item.saldo_restante_nc_usd }} USD)
                    </div>
                  </td>
                </tr>
              </tbody>
            </v-table>
          </div>
        </v-card-text>

        <v-card-actions class="pa-4 bg-slate-50 d-flex justify-space-between border-t border-slate-100">
          <v-btn color="slate-700" variant="tonal" class="font-weight-medium" @click="creditosAplicadosDialog = false">
            Entendido
          </v-btn>
          <v-btn
            color="emerald-darken-2"
            variant="flat"
            prepend-icon="mdi-cash-check"
            class="font-weight-bold"
            to="/pagos"
          >
            Ir a Certificar Pagos
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Modal de Emisión de Cuota Extraordinaria -->
    <CuotaExtraordinariaModal
      v-model="cuotaExtraordinariaDialog"
      :condominio-id="authStore.user?.condominio_id"
      :tasa-cambio-central="authStore.tasaCambioCentral || authStore.tasaCambio || 36.50"
      @guardado="onCuotaExtraordinariaGuardada"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import { useAuthStore } from '../../store/auth';
import { usePagination } from '../../composables/usePagination';
import DataTableHeader from '../../components/DataTableHeader.vue';
import DataTableFooter from '../../components/DataTableFooter.vue';
import SortHeader from '../../components/SortHeader.vue';
import CuotaExtraordinariaModal from './CuotaExtraordinariaModal.vue';
import ReciboMesModal from './ReciboMesModal.vue';

const authStore = useAuthStore();
const tab = ref('recibos');
const cuotaExtraordinariaDialog = ref(false);
const reciboMesDialog = ref(false);
const editingReciboPeriodo = ref(null);

function openCuotaExtraordinariaModal() {
  cuotaExtraordinariaDialog.value = true;
}

function onCuotaExtraordinariaGuardada() {
  fetchData();
}

// Modal de Registro Rápido de Factura de Proveedor / Gasto
const nuevoGastoDialog = ref(false);
const guardandoGasto = ref(false);
const gastoForm = ref({
  descripcion: '',
  monto_usd: null,
  monto_bs: null,
  categoria: 'mantenimiento',
  proveedor: '',
  referencia_pago: '',
  fecha_gasto: new Date().toISOString().split('T')[0],
  estado_pago: 'pagado',
});

const periodosList = ref([]);
const conceptosList = ref([]);
const libroMayorData = ref({ transacciones: [], total_ingresos_bs: 0, total_egresos_bs: 0, saldo_disponible_bs: 0 });
const resultadosData = ref({ ingresos_totales_bs: 0, gastos_totales_bs: 0, utilidad_neta_bs: 0 });
const cuentasCobrarData = ref({ facturas_pendientes: [], total_deuda_bs: 0 });
const expensesList = ref([]);

// Paginación y ordenamiento para cada tabla
const pPeriodos = usePagination(periodosList, { perPage: 10, initialSortBy: 'periodo', initialSortDesc: true });
const pConceptos = usePagination(conceptosList, { perPage: 10, initialSortBy: 'id', initialSortDesc: false });
const transaccionesList = computed(() => libroMayorData.value.transacciones || []);
const pMayor = usePagination(transaccionesList, { perPage: 10, initialSortBy: 'fecha', initialSortDesc: true });
const facturasPendientesList = computed(() => cuentasCobrarData.value.facturas_pendientes || []);
const pCobrar = usePagination(facturasPendientesList, { perPage: 10, initialSortBy: 'apartamento.numero', initialSortDesc: false });
const pGastos = usePagination(expensesList, { perPage: 10, initialSortBy: 'fecha_gasto', initialSortDesc: true });

const conceptoDialog = ref(false);
const isEditingConcepto = ref(false);
const savingConcepto = ref(false);

const creditosAplicadosDialog = ref(false);
const creditosAplicadosList = ref([]);

const previewDialog = ref(false);
const previewData = ref(null);

const desbloqueoInfoDialog = ref(false);
const selectedPeriodoObj = ref(null);

const alicuotasCatalogoList = ref([]);

const alicuotaOptions = computed(() => {
    if (alicuotasCatalogoList.value.length > 0) {
        return alicuotasCatalogoList.value.map(a => ({
            title: `Ali ${a.numero} - ${a.nombre}`,
            value: String(a.id),
        }));
    }
    return [
        { title: 'Ali 1 - Gastos Generales (Edificio)', value: '1' },
        { title: 'Ali 2 - Torre / Sector', value: '2' },
        { title: 'Ali 3 - Estacionamiento / Garaje', value: '3' },
        { title: 'Ali 4 - Maleteros / Depósitos', value: '4' },
        { title: 'Ali 5 - Locales Comerciales', value: '5' },
        { title: 'Ali 6 - Áreas Recreativas / Club', value: '6' },
        { title: 'Ali 7 - Alícuota Especial 7', value: '7' },
        { title: 'Ali 8 - Alícuota Especial 8', value: '8' },
        { title: 'Ali 9 - Alícuota Especial 9', value: '9' },
        { title: 'Ali 10 - Alícuota Especial 10', value: '10' },
        { title: 'Ali 11 - Alícuota Especial 11', value: '11' },
        { title: 'Ali 12 - Alícuota Especial 12', value: '12' },
    ];
});

const conceptoForm = ref({
    id: null,
    concepto: '',
    monto_base: 50.00,
    ali: '1',
    tipo: 'fijo',
    categoria: 'mantenimiento',
});

const totalConceptosUsd = computed(() => {
    return conceptosList.value.reduce((acc, c) => acc + Number(c.monto_base || 0), 0);
});

const resumenPorGrupo = computed(() => {
    const grupos = {};
    conceptosList.value.forEach(c => {
        const aliKey = String(c.ali || '1');
        if (!grupos[aliKey]) {
            grupos[aliKey] = {
                ali: aliKey,
                total_usd: 0,
                cantidad: 0,
            };
        }
        grupos[aliKey].total_usd += Number(c.monto_base || 0);
        grupos[aliKey].cantidad += 1;
    });
    return Object.values(grupos).sort((a, b) => a.ali.localeCompare(b.ali));
});

const fetchData = async () => {
    try {
        const condoId = authStore.activeCondominioId;
        const promises = [
            axios.get('/invoices/periodos-resumen'),
            axios.get('/conceptos-gasto'),
            axios.get('/contabilidad/libro-mayor'),
            axios.get('/contabilidad/estado-resultados'),
            axios.get('/contabilidad/cuentas-por-cobrar'),
            axios.get('/expenses'),
        ];
        if (condoId) {
            promises.push(axios.get(`/condominios/${condoId}/alicuotas`));
        }

        const [periodosRes, conceptosRes, mayorRes, resRes, cobrarRes, expRes, aliRes] = await Promise.all(promises);
        if (periodosRes.data.success) periodosList.value = periodosRes.data.data;
        if (conceptosRes.data.success) conceptosList.value = conceptosRes.data.data;
        if (mayorRes.data.success) libroMayorData.value = mayorRes.data.data;
        if (resRes.data.success) resultadosData.value = resRes.data.data;
        if (cobrarRes.data.success) cuentasCobrarData.value = cobrarRes.data.data;
        if (expRes.data.success) expensesList.value = expRes.data.data.data || expRes.data.data;
        if (aliRes && aliRes.data?.success) alicuotasCatalogoList.value = aliRes.data.data;
    } catch (e) {
        authStore.notify('Error al cargar datos contables', 'error');
    }
};

const openConceptoDialog = () => {
    isEditingConcepto.value = false;
    conceptoForm.value = {
        id: null,
        concepto: '',
        monto_base: 50.00,
        ali: '1',
        tipo: 'fijo',
        categoria: 'mantenimiento'
    };
    conceptoDialog.value = true;
};

const editConcepto = (c) => {
    isEditingConcepto.value = true;
    conceptoForm.value = { ...c };
    conceptoDialog.value = true;
};

const saveConcepto = async () => {
    savingConcepto.value = true;
    try {
        if (isEditingConcepto.value) {
            await axios.put(`/conceptos-gasto/${conceptoForm.value.id}`, conceptoForm.value);
            authStore.notify('Concepto de gasto actualizado');
        } else {
            await axios.post('/conceptos-gasto', conceptoForm.value);
            authStore.notify('Concepto de gasto agregado al catálogo');
        }
        conceptoDialog.value = false;
        fetchData();
    } catch (e) {
        authStore.notify('Error al guardar concepto', 'error');
    } finally {
        savingConcepto.value = false;
    }
};

const deleteConcepto = async (id) => {
    const result = await Swal.fire({
        title: '¿Eliminar Concepto de Gasto?',
        text: 'Este concepto ya no estará disponible para la emisión de futuros recibos.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        customClass: { popup: 'rounded-2xl shadow-xl' },
    });

    if (!result.isConfirmed) return;

    try {
        await axios.delete(`/conceptos-gasto/${id}`);
        authStore.notify('Concepto eliminado del catálogo');
        fetchData();
    } catch (e) {
        authStore.notify('Error al eliminar concepto', 'error');
    }
};

const openEmisionDialog = (item = null) => {
    editingReciboPeriodo.value = item;
    reciboMesDialog.value = true;
};

// Acciones para Registro de Facturas de Proveedores / Gastos
const openNuevoGastoDialog = () => {
    gastoForm.value = {
        descripcion: '',
        monto_usd: null,
        monto_bs: null,
        categoria: 'mantenimiento',
        proveedor: '',
        referencia_pago: '',
        fecha_gasto: new Date().toISOString().split('T')[0],
        estado_pago: 'pagado',
    };
    nuevoGastoDialog.value = true;
};

const onMontoUsdGastoChange = (val) => {
    const tasa = Number(authStore.tasaCambioCentral || authStore.tasaCambio || 36.50);
    const usd = Number(val) || 0;
    if (usd > 0) {
        gastoForm.value.monto_bs = Number((usd * tasa).toFixed(2));
    }
};

const onMontoBsGastoChange = (val) => {
    const tasa = Number(authStore.tasaCambioCentral || authStore.tasaCambio || 36.50);
    const bs = Number(val) || 0;
    if (bs > 0 && tasa > 0) {
        gastoForm.value.monto_usd = Number((bs / tasa).toFixed(2));
    }
};

const guardarNuevoGasto = async () => {
    if (!gastoForm.value.descripcion?.trim()) {
        authStore.notify('La descripción del gasto es obligatoria', 'warning');
        return;
    }
    const tasa = Number(authStore.tasaCambioCentral || authStore.tasaCambio || 36.50);
    let montoUsd = Number(gastoForm.value.monto_usd) || 0;
    let montoBs = Number(gastoForm.value.monto_bs) || 0;

    if (montoUsd <= 0 && montoBs <= 0) {
        authStore.notify('Debes ingresar el monto del gasto', 'warning');
        return;
    }
    if (montoBs <= 0 && montoUsd > 0) {
        montoBs = Number((montoUsd * tasa).toFixed(2));
    }
    if (montoUsd <= 0 && montoBs > 0) {
        montoUsd = Number((montoBs / tasa).toFixed(2));
    }

    guardandoGasto.value = true;
    try {
        const payload = {
            descripcion: gastoForm.value.descripcion.trim(),
            monto_usd: montoUsd,
            monto_bs: montoBs,
            tasa_cambio: tasa,
            categoria: gastoForm.value.categoria,
            proveedor: gastoForm.value.proveedor,
            referencia_pago: gastoForm.value.referencia_pago,
            fecha_gasto: gastoForm.value.fecha_gasto,
            estado_pago: gastoForm.value.estado_pago,
        };
        const { data } = await axios.post('/expenses', payload);
        if (data.success) {
            authStore.notify('Factura de gasto registrada exitosamente', 'success');
            nuevoGastoDialog.value = false;
            fetchData();
        }
    } catch (e) {
        authStore.notify(e.response?.data?.message || 'Error al guardar el gasto', 'error');
    } finally {
        guardandoGasto.value = false;
    }
};

// Cobro y Notificación directa por WhatsApp
const enviarWhatsAppRecibo = (inv) => {
    if (!inv) return;
    const prop = inv.apartamento?.propietarios?.[0] || {};
    const aptoNum = inv.apartamento?.numero || 'N/A';
    const nombre = prop.nombre_completo || prop.name || 'Estimado(a) Propietario(a)';
    let rawPhone = (prop.telefono || prop.celular || prop.whatsapp || '').toString().replace(/\D/g, '');

    const montoUsd = Number(inv.monto_total_usd || (inv.monto_total / (inv.tasa_cambio || 36.5))).toFixed(2);
    const montoBs = Number(inv.monto_total || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const tasaBcv = Number(inv.tasa_cambio || authStore.tasaCambioCentral || 36.5).toFixed(2);
    const condoName = selectedPeriodoForPropietarios.value?.condominio_nombre || 'Condominio';
    const pdfUrl = `${window.location.origin}/api/v1/reportes/recibo-invoice/${inv.id}`;

    const mensaje = `¡Hola, *${nombre}*! 👋\n\n` +
      `Le informamos que ya se encuentra disponible el *Aviso de Cobro de Condominio* correspondiente a *${inv.periodo}* para su inmueble *Apto ${aptoNum}*.\n\n` +
      `🏢 *Condominio:* ${condoName}\n` +
      `💵 *Total a Pagar en Divisas:* $${montoUsd} USD\n` +
      `🇻🇪 *Total en Bolívares:* Bs. ${montoBs} (Tasa BCV: Bs. ${tasaBcv})\n\n` +
      `📄 Puede consultar y descargar su recibo en PDF mediante este enlace directo:\n${pdfUrl}\n\n` +
      `Agradecemos reportar su pago a la brevedad posible en la plataforma.\n¡Muchas gracias por su compromiso con nuestra comunidad!`;

    const abrirWhatsApp = (telefono) => {
        let clean = telefono.replace(/\D/g, '');
        if (clean.startsWith('0')) {
            clean = '58' + clean.slice(1);
        } else if (!clean.startsWith('58') && clean.length === 10) {
            clean = '58' + clean;
        }
        const url = `https://api.whatsapp.com/send?phone=${clean}&text=${encodeURIComponent(mensaje)}`;
        window.open(url, '_blank');
    };

    if (rawPhone) {
        abrirWhatsApp(rawPhone);
    } else {
        Swal.fire({
            title: 'WhatsApp del Propietario',
            html: `El Apto <strong>${aptoNum}</strong> (${nombre}) no tiene teléfono registrado.<br>Ingrese el número para abrir WhatsApp:`,
            input: 'text',
            inputPlaceholder: '04141234567 o 584141234567',
            showCancelButton: true,
            confirmButtonText: 'Abrir WhatsApp',
            cancelButtonText: 'Copiar Mensaje',
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#64748b',
        }).then((result) => {
            if (result.isConfirmed && result.value?.trim()) {
                abrirWhatsApp(result.value.trim());
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                navigator.clipboard.writeText(mensaje);
                Swal.fire({
                    icon: 'success',
                    title: '¡Mensaje Copiado!',
                    text: 'El mensaje de cobro ha sido copiado al portapapeles para que lo pegues donde desees.',
                    timer: 2500,
                    showConfirmButton: false,
                });
            }
        });
    }
};

const verDetallePeriodo = (item) => {
    previewData.value = item;
    previewDialog.value = true;
};

const confirmarCertificacion = async (periodo) => {
    const result = await Swal.fire({
        title: '<span class="text-slate-900 font-bold">¿Certificar y Bloquear Recibo?</span>',
        html: `
            <div class="text-left text-sm text-slate-600 mt-2">
                <p class="mb-3">Estás a punto de <strong>validar y bloquear definitivamente</strong> los recibos del mes:</p>
                
                <div class="p-3 bg-slate-100 rounded-xl border border-slate-200 text-center font-bold text-base text-slate-800 mb-3">
                    📅 Período: <strong>${periodo}</strong>
                </div>

                <div class="p-3 bg-amber-50 border-l-4 border-amber-500 rounded-lg text-amber-900 text-xs mb-2">
                    <div class="font-bold text-amber-950 mb-1">⚠️ Efectos de la Certificación:</div>
                    <ul class="list-disc list-inside space-y-1">
                        <li>El recibo quedará <strong>bloqueado permanentemente</strong> para el administrador.</li>
                        <li>El PDF oficial se emitirá <strong>limpio sin marcas de agua</strong> para los propietarios.</li>
                        <li>Cualquier modificación posterior requerirá autorización del <strong>Super Administrador</strong> (máx. 2 reaperturas).</li>
                    </ul>
                </div>
            </div>
        `,
        icon: 'warning',
        iconColor: '#f59e0b',
        showCancelButton: true,
        confirmButtonColor: '#16a34a',
        cancelButtonColor: '#64748b',
        confirmButtonText: '🛡️ Sí, Certificar y Bloquear',
        cancelButtonText: 'Volver y Revisar',
        reverseButtons: true,
        focusCancel: true,
        customClass: {
            popup: 'rounded-3xl shadow-2xl p-6',
            confirmButton: 'px-5 py-2.5 rounded-xl font-bold shadow-md',
            cancelButton: 'px-5 py-2.5 rounded-xl font-medium',
        },
    });

    if (!result.isConfirmed) return;

    try {
        const { data } = await axios.post('/invoices/certificar-periodo', { periodo });
        previewDialog.value = false;
        fetchData();

        await Swal.fire({
            icon: 'success',
            title: '¡Recibo Certificado con Éxito!',
            html: `
                <div class="text-sm text-slate-600">
                    Los recibos del período <strong>${periodo}</strong> han sido sellados y bloqueados correctamente.<br>
                    Ya puedes descargar el PDF oficial para su distribución.
                </div>
            `,
            confirmButtonColor: '#0f172a',
            confirmButtonText: 'Excelente',
            customClass: {
                popup: 'rounded-3xl shadow-2xl p-6',
                confirmButton: 'px-6 py-2.5 rounded-xl font-bold',
            },
        });
    } catch (e) {
        await Swal.fire({
            icon: 'error',
            title: 'No se pudo certificar',
            text: e.response?.data?.message || 'Error al intentar certificar el período.',
            confirmButtonColor: '#0f172a',
            customClass: { popup: 'rounded-2xl' },
        });
    }
};

// --- Modal Recibos por Propietario & Gastos No Comunes ---
const recibosPropietariosDialog = ref(false);
const selectedPeriodoForPropietarios = ref(null);
const propietariosInvoicesList = ref([]);
const loadingPropietariosInvoices = ref(false);
const searchPropietarioInvoices = ref('');

const agregarGastoNoComunDialog = ref(false);
const selectedInvoiceForGastoNc = ref(null);
const selectedConceptoNoComunItem = ref(null);
const gastoNoComunForm = ref({
    concepto: '',
    monto_usd: 15.00,
});
const savingGastoNc = ref(false);

const conceptosNoComunesOptions = computed(() => {
    return conceptosList.value
        .filter(c => c.tipo === 'no_comun')
        .map(c => ({
            label: `${c.concepto} - $${Number(c.monto_base).toFixed(2)}`,
            concepto: c.concepto,
            monto_base: Number(c.monto_base),
            id: c.id
        }));
});

const propSortBy = ref('apartamento.numero');
const propSortDesc = ref(false);

const sortPropInvoices = (key) => {
    if (propSortBy.value === key) {
        propSortDesc.value = !propSortDesc.value;
    } else {
        propSortBy.value = key;
        propSortDesc.value = false;
    }
};

const filteredPropietariosInvoices = computed(() => {
    let list = Array.isArray(propietariosInvoicesList.value) ? [...propietariosInvoicesList.value] : [];
    const term = (searchPropietarioInvoices.value || '').toLowerCase().trim();
    if (term) {
        list = list.filter(inv => {
            const aptoNum = String(inv.apartamento?.numero || '').toLowerCase();
            const propName = String(inv.apartamento?.propietarios?.[0]?.nombre_completo || inv.apartamento?.propietarios?.[0]?.name || '').toLowerCase();
            const ced = String(inv.apartamento?.propietarios?.[0]?.cedula || '').toLowerCase();
            return aptoNum.includes(term) || propName.includes(term) || ced.includes(term);
        });
    }

    if (propSortBy.value) {
        list.sort((a, b) => {
            let valA, valB;
            switch (propSortBy.value) {
                case 'apartamento.numero':
                    valA = a.apartamento?.numero || '';
                    valB = b.apartamento?.numero || '';
                    break;
                case 'propietario':
                    valA = a.apartamento?.propietarios?.[0]?.nombre_completo || a.apartamento?.propietarios?.[0]?.name || '';
                    valB = b.apartamento?.propietarios?.[0]?.nombre_completo || b.apartamento?.propietarios?.[0]?.name || '';
                    break;
                case 'alicuota':
                    valA = Number(a.apartamento?.alicuota || 0);
                    valB = Number(b.apartamento?.alicuota || 0);
                    return propSortDesc.value ? valB - valA : valA - valB;
                case 'cuota_comun':
                    valA = Number(a.monto_alicuota_usd || 0);
                    valB = Number(b.monto_alicuota_usd || 0);
                    return propSortDesc.value ? valB - valA : valA - valB;
                case 'gastos_no_comunes':
                    valA = getGastosNoComunesTotal(a);
                    valB = getGastosNoComunesTotal(b);
                    return propSortDesc.value ? valB - valA : valA - valB;
                case 'total':
                    valA = Number(a.monto_total_usd || (a.monto_total / (a.tasa_cambio || 36.5)));
                    valB = Number(b.monto_total_usd || (b.monto_total / (b.tasa_cambio || 36.5)));
                    return propSortDesc.value ? valB - valA : valA - valB;
                case 'estado':
                    valA = a.estado === 'pagado' ? 'solventado' : (a.estado_certificacion || 'borrador');
                    valB = b.estado === 'pagado' ? 'solventado' : (b.estado_certificacion || 'borrador');
                    break;
                default:
                    valA = a[propSortBy.value] || '';
                    valB = b[propSortBy.value] || '';
            }

            if (valA === null || valA === undefined || valA === '') return 1;
            if (valB === null || valB === undefined || valB === '') return -1;

            const numA = Number(valA);
            const numB = Number(valB);
            if (!isNaN(numA) && !isNaN(numB)) {
                return propSortDesc.value ? numB - numA : numA - numB;
            }

            const cmp = String(valA).localeCompare(String(valB), 'es', { numeric: true, sensitivity: 'base' });
            return propSortDesc.value ? -cmp : cmp;
        });
    }

    return list;
});

const openPropietariosModal = async (item) => {
    selectedPeriodoForPropietarios.value = item;
    recibosPropietariosDialog.value = true;
    loadingPropietariosInvoices.value = true;
    searchPropietarioInvoices.value = '';
    try {
        const res = await axios.get('/invoices', { params: { periodo: item.periodo, condominio_id: item.condominio_id, per_page: 500 } });
        if (res.data.success) {
            propietariosInvoicesList.value = res.data.data.data || res.data.data;
        }
    } catch (e) {
        authStore.notify('Error al cargar los recibos por propietario', 'error');
    } finally {
        loadingPropietariosInvoices.value = false;
    }
};

const parseDetallesGastos = (gastos) => {
    if (!gastos) return [];
    if (typeof gastos === 'string') {
        try {
            return JSON.parse(gastos);
        } catch (e) {
            return [];
        }
    }
    return Array.isArray(gastos) ? gastos : [];
};

const getGastosNoComunes = (inv) => {
    const list = parseDetallesGastos(inv.detalles_gastos);
    return list.filter(g => g.es_no_comun || g.ali === 'no_comun');
};

const getGastosNoComunesTotal = (inv) => {
    return getGastosNoComunes(inv).reduce((acc, g) => acc + Number(g.alicu || g.monto || 0), 0);
};

const ncOriginalIndex = (inv, nc) => {
    const list = parseDetallesGastos(inv.detalles_gastos);
    return list.findIndex(g => (g.es_no_comun || g.ali === 'no_comun') && g.concepto === nc.concepto);
};

const openAgregarGastoNoComunModal = (inv) => {
    selectedInvoiceForGastoNc.value = inv;
    selectedConceptoNoComunItem.value = null;
    const firstNc = conceptosNoComunesOptions.value[0];
    if (firstNc) {
        selectedConceptoNoComunItem.value = firstNc;
        gastoNoComunForm.value = {
            concepto: firstNc.concepto,
            monto_usd: firstNc.monto_base,
        };
    } else {
        gastoNoComunForm.value = {
            concepto: '',
            monto_usd: 15.00,
        };
    }
    agregarGastoNoComunDialog.value = true;
};

const onConceptoNoComunSelectChange = (val) => {
    if (val) {
        gastoNoComunForm.value.concepto = val.concepto || '';
        gastoNoComunForm.value.monto_usd = val.monto_base || 15.00;
    }
};

const saveGastoNoComun = async () => {
    if (!gastoNoComunForm.value.concepto || !gastoNoComunForm.value.monto_usd) {
        authStore.notify('Debes ingresar un concepto y un monto válido', 'warning');
        return;
    }

    savingGastoNc.value = true;
    try {
        const invId = selectedInvoiceForGastoNc.value.id;
        const res = await axios.post(`/invoices/${invId}/agregar-gasto-no-comun`, {
            concepto: gastoNoComunForm.value.concepto,
            monto_usd: Number(gastoNoComunForm.value.monto_usd),
        });
        if (res.data.success) {
            authStore.notify(res.data.message || 'Gasto no común agregado con éxito');
            agregarGastoNoComunDialog.value = false;
            
            // Recargar lista de recibos por propietario del período
            if (selectedPeriodoForPropietarios.value) {
                const resInvoices = await axios.get('/invoices', { params: { periodo: selectedPeriodoForPropietarios.value.periodo, per_page: 500 } });
                if (resInvoices.data.success) {
                    propietariosInvoicesList.value = resInvoices.data.data.data || resInvoices.data.data;
                }
            }
            fetchData();
        }
    } catch (e) {
        authStore.notify(e.response?.data?.message || 'Error al agregar gasto no común', 'error');
    } finally {
        savingGastoNc.value = false;
    }
};

const confirmarEliminarGastoNc = async (inv, index) => {
    if (index < 0) {
        authStore.notify('No se pudo encontrar la ubicación de este gasto no común', 'error');
        return;
    }

    const result = await Swal.fire({
        title: '¿Eliminar Gasto No Común?',
        text: 'Este cargo se removerá del recibo de este propietario.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const res = await axios.delete(`/invoices/${inv.id}/gasto-no-comun/${index}`);
            if (res.data.success) {
                authStore.notify('Gasto no común eliminado del recibo');
                if (selectedPeriodoForPropietarios.value) {
                    const resInvoices = await axios.get('/invoices', { params: { periodo: selectedPeriodoForPropietarios.value.periodo, per_page: 500 } });
                    if (resInvoices.data.success) {
                        propietariosInvoicesList.value = resInvoices.data.data.data || resInvoices.data.data;
                    }
                }
                fetchData();
            }
        } catch (e) {
            authStore.notify(e.response?.data?.message || 'Error al eliminar gasto no común', 'error');
        }
    }
};

const mostrarInfoDesbloqueo = (item) => {
    selectedPeriodoObj.value = item;
    desbloqueoInfoDialog.value = true;
};

// ===== ENVÍO DE RECIBOS POR CORREO =====
const enviarRecibosDialog = ref(false);
const sendingEmails = ref(false);
const targetInvoicesToSend = ref([]);

const openEnviarRecibosModal = () => {
    targetInvoicesToSend.value = propietariosInvoicesList.value;
    enviarRecibosDialog.value = true;
};

const enviarReciboIndividual = (inv) => {
    targetInvoicesToSend.value = [inv];
    enviarRecibosDialog.value = true;
};

const confirmarEnvioRecibos = async () => {
    if (!selectedPeriodoForPropietarios.value) return;
    
    sendingEmails.value = true;
    try {
        const payload = {
            periodo: selectedPeriodoForPropietarios.value.periodo,
            condominio_id: selectedPeriodoForPropietarios.value.condominio_id,
            invoice_ids: targetInvoicesToSend.value.map(i => i.id),
        };

        const res = await axios.post('/invoices/enviar-recibos-periodo', payload);
        enviarRecibosDialog.value = false;

        await Swal.fire({
            icon: 'success',
            title: '¡Recibos Enviados con Éxito!',
            html: `
                <div class="text-sm text-slate-600">
                    <p class="mb-2">Se han despachado <strong>${res.data.data?.enviados ?? targetInvoicesToSend.value.length}</strong> correos electrónicos con los recibos oficiales del período <strong>${selectedPeriodoForPropietarios.value.periodo}</strong>.</p>
                    <p class="text-xs text-slate-500">Los propietarios recibirán la notificación con los datos bancarios y el enlace para reportar su pago directamente en el sistema.</p>
                </div>
            `,
            confirmButtonColor: '#0f172a',
            confirmButtonText: 'Excelente',
            customClass: {
                popup: 'rounded-3xl shadow-2xl p-6',
                confirmButton: 'px-6 py-2.5 rounded-xl font-bold',
            },
        });
    } catch (e) {
        const msg = e.response?.data?.message || 'Error al enviar los recibos por correo.';
        authStore.notify(msg, 'error');
    } finally {
        sendingEmails.value = false;
    }
};

watch(() => authStore.activeCondominioId, () => {
    fetchData();
});

onMounted(fetchData);
</script>

