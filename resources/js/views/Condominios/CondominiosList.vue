<template>
  <div>
    <!-- Top Header -->
    <div class="d-flex flex-column flex-sm-row justify-space-between align-start align-sm-center mb-4 mb-sm-6 gap-3">
      <div>
        <h1 class="text-h5 font-weight-bold text-slate-900">Gestión de Condominios y Torres</h1>
        <p class="text-caption text-slate-500">
          Administración de Conjuntos Residenciales, Torres independientes, asignación de administradores y datos fiscales
        </p>
      </div>
      <div class="d-flex align-center gap-2 flex-wrap w-100 w-sm-auto">
        <v-btn
          v-if="authStore.isSuperAdmin"
          color="teal-darken-2"
          variant="tonal"
          prepend-icon="mdi-bank-outline"
          class="font-weight-bold flex-grow-1 flex-sm-grow-0"
          title="Tasa Oficial BCV Central del Sistema (Clic para configurar o sincronizar todas las torres)"
          @click="openTasaCentralModal"
        >
          🏛️ Tasa: Bs. {{ Number(authStore.tasaCambioCentral || 36.50).toFixed(2) }}
        </v-btn>
        <v-chip
          v-else
          color="teal-darken-2"
          variant="tonal"
          prepend-icon="mdi-bank-outline"
          class="font-weight-bold flex-grow-1 flex-sm-grow-0 py-3 px-4"
          title="Tasa Oficial BCV activa en el sistema (Gestionada por el Super Administrador)"
        >
          🏛️ Tasa BCV: Bs. {{ Number(authStore.tasaCambioCentral || authStore.tasaCambio || 36.50).toFixed(2) }}
        </v-chip>
        <v-btn
          v-if="authStore.isSuperAdmin"
          color="primary"
          prepend-icon="mdi-home-plus"
          class="font-weight-bold flex-grow-1 flex-sm-grow-0"
          @click="openCreateDialog"
        >
          Nuevo Inmueble
        </v-btn>
      </div>
    </div>

    <!-- Financial & Property KPI Summary Cards -->
    <v-row class="mb-4 mb-sm-6">
      <v-col cols="12" sm="6" md="3">
        <v-card class="pa-4 bg-white border border-slate-100 rounded-xl elevation-0" height="100%">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-slate-500 font-weight-medium">Inmuebles Registrados</div>
              <div class="text-h5 font-weight-bold text-slate-900 mt-1">{{ kpiTotalInmuebles }}</div>
              <div class="text-caption text-primary mt-1 font-weight-medium">
                {{ kpiConjuntosCount }} complejos • {{ kpiEdificiosUnicosCount }} edif. únicos
              </div>
            </div>
            <v-avatar color="blue-lighten-5" size="44" rounded="lg">
              <v-icon icon="mdi-office-building-cog" color="primary" size="24" />
            </v-avatar>
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card class="pa-4 bg-white border border-slate-100 rounded-xl elevation-0" height="100%">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-slate-500 font-weight-medium">Torres Administradas</div>
              <div class="text-h5 font-weight-bold text-teal-darken-2 mt-1">{{ kpiTotalTorres }}</div>
              <div class="text-caption text-teal-darken-1 mt-1 font-weight-medium">
                Subdivisiones operativas
              </div>
            </div>
            <v-avatar color="teal-lighten-5" size="44" rounded="lg">
              <v-icon icon="mdi-home-city-outline" color="teal-darken-1" size="24" />
            </v-avatar>
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card class="pa-4 bg-white border border-slate-100 rounded-xl elevation-0" height="100%">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-slate-500 font-weight-medium">Total Apartamentos</div>
              <div class="text-h5 font-weight-bold text-indigo-darken-2 mt-1">{{ kpiTotalApartamentos }}</div>
              <div class="text-caption text-indigo mt-1 font-weight-medium">
                Unidades bajo gestión SaaS
              </div>
            </div>
            <v-avatar color="indigo-lighten-5" size="44" rounded="lg">
              <v-icon icon="mdi-door-sliding" color="indigo" size="24" />
            </v-avatar>
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card class="pa-4 bg-white border border-slate-100 rounded-xl elevation-0" height="100%">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-slate-500 font-weight-medium">Administradores</div>
              <div class="text-h5 font-weight-bold text-purple-darken-2 mt-1">{{ adminsList.length }}</div>
              <div class="text-caption text-purple mt-1 font-weight-medium">
                Usuarios con rol activo
              </div>
            </div>
            <v-avatar color="purple-lighten-5" size="44" rounded="lg">
              <v-icon icon="mdi-account-tie" color="purple" size="24" />
            </v-avatar>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <!-- Table List with DataTable controls -->
    <v-card class="bg-white border border-slate-100">
      <DataTableHeader
        v-model:search="search"
        v-model:per-page="perPage"
        :per-page-options="perPageOptions"
        placeholder="Buscar por nombre, RIF, torre o administrador..."
      >
        <template #actions>
          <div class="d-flex align-center gap-2 flex-wrap">
            <v-btn
              size="small"
              variant="tonal"
              color="slate-700"
              prepend-icon="mdi-arrow-expand-vertical"
              class="text-capitalize font-weight-medium"
              @click="expandAll"
            >
              Expandir
            </v-btn>
            <v-btn
              size="small"
              variant="tonal"
              color="slate-700"
              prepend-icon="mdi-arrow-collapse-vertical"
              class="text-capitalize font-weight-medium"
              @click="collapseAll"
            >
              Colapsar
            </v-btn>
            <v-select
              v-model="tipoFiltro"
              :items="[
                { title: 'Todos los Inmuebles', value: '' },
                { title: '🏘️ Conjuntos Matrices', value: 'conjunto_residencial' },
                { title: '🏢 Edificios Únicos', value: 'edificio_independiente' },
              ]"
              item-title="title"
              item-value="value"
              density="compact"
              variant="outlined"
              hide-details
              style="min-width: 170px;"
              class="bg-white rounded"
            />
          </div>
        </template>
      </DataTableHeader>

      <div class="table-responsive-container">
        <v-table density="comfortable" hover style="min-width: 880px;">
          <thead>
            <tr class="bg-slate-50 text-slate-700">
              <th style="width: 44px;"></th>
              <SortHeader col-key="nombre" width="200px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
                Inmueble / Condominio
              </SortHeader>
              <SortHeader col-key="tipo_entidad" width="140px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
                Tipo y Estructura
              </SortHeader>
              <SortHeader col-key="administrador" width="160px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
                Administrador Responsable
              </SortHeader>
              <SortHeader col-key="banco" width="140px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
                Datos Bancarios
              </SortHeader>
              <SortHeader col-key="fondo_reserva" width="110px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
                Fondo Reserva
              </SortHeader>
              <SortHeader col-key="tasa_bcv" width="95px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
                Tasa BCV
              </SortHeader>
              <SortHeader col-key="activo" width="85px" :sort-by="sortBy" :sort-desc="sortDesc" @sort="sort">
                Estado
              </SortHeader>
              <th class="text-right" style="width: 120px;">Acciones</th>
            </tr>
          </thead>
          <tbody>
          <template v-for="item in paginatedItems" :key="item.id">
            <!-- Fila Principal: Conjunto Residencial o Edificio Único -->
            <tr
              :class="{
                'bg-purple-50/50 font-weight-medium': item.tipo_entidad === 'conjunto_residencial' || (item.torres && item.torres.length > 0)
              }"
            >
              <!-- Botón expandir/colapsar si es conjunto -->
              <td class="text-center pa-1">
                <v-btn
                  v-if="item.tipo_entidad === 'conjunto_residencial' || (item.torres && item.torres.length)"
                  icon
                  size="x-small"
                  variant="text"
                  :color="isExpanded(item.id) ? 'purple-darken-2' : 'slate-600'"
                  :title="isExpanded(item.id) ? 'Contraer torres' : 'Desplegar torres de este complejo'"
                  @click="toggleExpand(item.id)"
                >
                  <v-icon :icon="isExpanded(item.id) ? 'mdi-chevron-down' : 'mdi-chevron-right'" size="20" />
                </v-btn>
              </td>

              <!-- Nombre y Detalles -->
              <td>
                <div class="font-weight-bold text-slate-900 text-body-2">{{ item.nombre }}</div>
                <div class="text-caption text-slate-500">
                  RIF: {{ item.rif }} • <strong>{{ getTotalAptos(item) }}</strong> unidades totales
                </div>
              </td>

              <!-- Chip Tipo de Estructura -->
              <td>
                <v-chip
                  v-if="item.tipo_entidad === 'conjunto_residencial' || (item.torres && item.torres.length)"
                  color="purple"
                  size="small"
                  variant="flat"
                  class="font-weight-bold cursor-pointer"
                  @click="toggleExpand(item.id)"
                >
                  🏘️ Complejo ({{ item.torres?.length || 0 }} Torres)
                  <v-icon end :icon="isExpanded(item.id) ? 'mdi-chevron-up' : 'mdi-chevron-down'" size="16" />
                </v-chip>
                <v-chip
                  v-else
                  color="primary"
                  size="small"
                  variant="tonal"
                  class="font-weight-bold"
                >
                  🏢 Edificio Único
                </v-chip>
              </td>

              <!-- Administrador Asignado -->
              <td>
                <div v-if="item.tipo_entidad === 'conjunto_residencial' || (item.torres && item.torres.length)">
                  <v-chip size="x-small" color="purple" variant="tonal" class="font-weight-medium">
                    🏘️ Gestionado por Torres
                  </v-chip>
                </div>
                <div v-else-if="item.administradores && item.administradores.length" class="d-flex flex-wrap gap-1">
                  <v-chip
                    v-for="adm in item.administradores"
                    :key="adm.id"
                    size="x-small"
                    color="indigo"
                    variant="flat"
                    class="font-weight-medium"
                  >
                    👤 {{ adm.name }}
                  </v-chip>
                </div>
                <div v-else-if="item.users && item.users.length" class="d-flex flex-wrap gap-1">
                  <v-chip
                    v-for="adm in item.users"
                    :key="adm.id"
                    size="x-small"
                    color="indigo"
                    variant="flat"
                    class="font-weight-medium"
                  >
                    👤 {{ adm.name }}
                  </v-chip>
                </div>
                <span v-else class="text-caption text-slate-400 italic">Sin administrador</span>
              </td>

              <!-- Cuentas Bancarias -->
              <td>
                <div class="text-caption font-weight-bold text-slate-800">
                  {{ item.banco_nombre || 'BNC' }}: {{ item.cuenta_bancaria_bs || 'Principal' }}
                </div>
                <div v-if="item.cuenta_bancaria_usd" class="text-caption text-slate-500">
                  USD: {{ item.cuenta_bancaria_usd }}
                </div>
              </td>

              <!-- Fondo de Reserva -->
              <td>
                <div class="font-weight-medium text-slate-800 text-caption">
                  ${{ Number(item.fondo_reserva_acumulado || 0).toFixed(2) }}
                </div>
                <div class="text-caption text-slate-500">
                  Reserva: {{ item.fondo_reserva_porcentaje || 10 }}%
                </div>
              </td>

              <!-- Tasa BCV -->
              <td>
                <v-chip size="x-small" color="primary" variant="outlined" class="font-weight-bold">
                  Bs. {{ Number(item.tasa_cambio || authStore.tasaCambio || 36.50).toFixed(2) }}
                </v-chip>
              </td>

              <!-- Estado -->
              <td>
                <v-chip :color="item.activo ? 'success' : 'error'" size="x-small" variant="flat">
                  {{ item.activo ? 'Activo' : 'Inactivo' }}
                </v-chip>
              </td>

              <!-- Acciones de Fila Principal -->
              <td class="text-right text-no-wrap">
                <!-- Botón + Torre si es Conjunto -->
                <v-btn
                  v-if="item.tipo_entidad === 'conjunto_residencial' || (item.torres && item.torres.length)"
                  size="small"
                  color="teal"
                  variant="flat"
                  prepend-icon="mdi-plus"
                  class="mr-2 text-capitalize font-weight-bold"
                  title="Agregar nueva torre a este conjunto"
                  @click="openAddTorreToConjuntoDialog(item)"
                >
                  + Torre
                </v-btn>

                <!-- Gestionar Cuentas y Métodos de Cobro (Pago Móvil, Bancos, Divisas) -->
                <v-btn
                  icon="mdi-credit-card-outline"
                  size="small"
                  variant="tonal"
                  color="teal-darken-1"
                  class="mr-1"
                  title="Configurar Cuentas Bancarias, Pago Móvil y Canales de Cobro"
                  @click="openCuentasDialog(item)"
                />

                <!-- Reasignar Admin si es edificio único -->
                <v-btn
                  v-if="item.tipo_entidad !== 'conjunto_residencial' && (!item.torres || !item.torres.length)"
                  icon="mdi-account-switch"
                  size="small"
                  variant="tonal"
                  color="indigo"
                  class="mr-1"
                  title="Asignar / Reasignar Administrador"
                  @click="openAssignDialog(item)"
                />

                <!-- Auditoría si es edificio único -->
                <v-btn
                  v-if="authStore.isMaster && item.tipo_entidad !== 'conjunto_residencial' && (!item.torres || !item.torres.length)"
                  icon="mdi-receipt-text-clock"
                  size="small"
                  variant="tonal"
                  color="amber-darken-3"
                  class="mr-1"
                  title="Auditoría y Recibos Mensuales"
                  @click="openRecibosCondoDialog(item)"
                />

                <v-btn icon="mdi-pencil" size="small" variant="text" color="primary" title="Editar datos" @click="editCondominio(item)" />
                <v-btn icon="mdi-delete" size="small" variant="text" color="error" title="Eliminar" @click="deleteCondominio(item.id)" />
              </td>
            </tr>

            <!-- Sub-sección Acordeón Desplegable con las Torres del Conjunto -->
            <tr
              v-if="(item.tipo_entidad === 'conjunto_residencial' || (item.torres && item.torres.length)) && isExpanded(item.id)"
              :key="'expanded-' + item.id"
              class="bg-slate-50 border-b border-purple-100"
            >
              <td colspan="9" class="pa-3 pl-8">
                <v-card variant="outlined" class="bg-white border-purple-200 rounded-lg pa-3">
                  <div class="d-flex justify-space-between align-center mb-3">
                    <div class="d-flex align-center gap-2">
                      <span class="text-subtitle-2 font-weight-bold text-purple-900">
                        🗼 Torres registradas en {{ item.nombre }}
                      </span>
                      <v-chip size="x-small" color="purple" variant="tonal" class="font-weight-bold">
                        {{ item.torres?.length || 0 }} torre(s)
                      </v-chip>
                    </div>
                    <v-btn
                      size="x-small"
                      color="teal"
                      variant="flat"
                      prepend-icon="mdi-plus"
                      class="text-capitalize font-weight-bold"
                      @click="openAddTorreToConjuntoDialog(item)"
                    >
                      Añadir Torre
                    </v-btn>
                  </div>

                  <!-- Tabla anidada de torres -->
                  <v-table v-if="item.torres && item.torres.length" density="compact" hover class="border border-slate-100 rounded">
                    <thead>
                      <tr class="bg-slate-50 text-slate-600 text-caption">
                        <th>Identificador / Torre</th>
                        <th>Unidades</th>
                        <th>Administrador Asignado</th>
                        <th>Cuenta Bancaria / Cuota Base</th>
                        <th>Tasa BCV</th>
                        <th>Estado</th>
                        <th class="text-right">Acciones de Torre</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="torre in item.torres" :key="'torre-' + torre.id">
                        <td>
                          <div class="font-weight-bold text-slate-900 text-caption">
                            🗼 {{ torre.torre_bloque || torre.nombre }}
                          </div>
                          <div class="text-caption text-slate-500">RIF: {{ torre.rif }}</div>
                        </td>
                        <td>
                          <v-chip size="x-small" color="teal" variant="tonal" class="font-weight-bold">
                            {{ torre.numero_apartamentos }} aptos
                          </v-chip>
                        </td>
                        <td>
                          <div v-if="torre.administradores && torre.administradores.length" class="d-flex flex-wrap gap-1">
                            <v-chip
                              v-for="adm in torre.administradores"
                              :key="adm.id"
                              size="x-small"
                              color="indigo"
                              variant="flat"
                            >
                              👤 {{ adm.name }}
                            </v-chip>
                          </div>
                          <span v-else class="text-caption text-slate-400 italic">Mismo admin matriz</span>
                        </td>
                        <td>
                          <div class="text-caption font-weight-bold text-slate-800">
                            ${{ Number(torre.cuota_mantenimiento_base || 0).toFixed(2) }} base
                          </div>
                          <div class="text-caption text-slate-500">
                            {{ torre.cuenta_bancaria_bs || 'Cuenta Matriz' }}
                          </div>
                        </td>
                        <td>
                          <span class="text-caption text-slate-600">Bs. {{ Number(torre.tasa_cambio || authStore.tasaCambio || 36.50).toFixed(2) }}</span>
                        </td>
                        <td>
                          <v-chip :color="torre.activo ? 'success' : 'error'" size="x-small" variant="flat">
                            {{ torre.activo ? 'Activo' : 'Inactivo' }}
                          </v-chip>
                        </td>
                        <td class="text-right text-no-wrap">
                          <!-- Auditoría Recibos Torre -->
                          <v-btn
                            v-if="authStore.isMaster"
                            icon="mdi-receipt-text-clock"
                            size="x-small"
                            variant="tonal"
                            color="amber-darken-3"
                            class="mr-1"
                            title="Auditoría y Recibos Mensuales"
                            @click="openRecibosCondoDialog(torre)"
                          />
                          <!-- Asignar Admin Torre -->
                          <v-btn
                            icon="mdi-account-switch"
                            size="x-small"
                            variant="tonal"
                            color="indigo"
                            class="mr-1"
                            title="Cambiar Administrador de esta Torre"
                            @click="openAssignDialog(torre)"
                          />
                          <!-- Configurar Cuentas y Canales Torre -->
                          <v-btn
                            icon="mdi-credit-card-outline"
                            size="x-small"
                            variant="tonal"
                            color="teal-darken-1"
                            class="mr-1"
                            title="Configurar Cuentas Bancarias y Canales de Cobro de esta Torre"
                            @click="openCuentasDialog(torre)"
                          />
                          <v-btn icon="mdi-pencil" size="x-small" variant="text" color="primary" title="Editar Torre" @click="editCondominio(torre)" />
                          <v-btn icon="mdi-delete" size="x-small" variant="text" color="error" title="Eliminar Torre" @click="deleteCondominio(torre.id)" />
                        </td>
                      </tr>
                    </tbody>
                  </v-table>

                  <div v-else class="text-center py-4 text-purple-700 text-caption">
                    Este complejo residencial aún no tiene torres registradas. Haz clic en "Añadir Torre" para crear la primera.
                  </div>
                </v-card>
              </td>
            </tr>
          </template>

          <tr v-if="!paginatedItems.length">
            <td colspan="9" class="text-center py-8 text-slate-400">
              {{ search ? 'No se encontraron inmuebles que coincidan con la búsqueda.' : 'No hay condominios ni conjuntos registrados actualmente.' }}
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

    <!-- Dialog for Create / Edit Condominio / Conjunto -->
    <v-dialog v-model="dialog" max-width="850" :fullscreen="$vuetify.display.xs" scrollable>
      <v-card class="pa-4">
        <v-card-title class="font-weight-bold d-flex align-center justify-space-between flex-wrap gap-2">
          <span>
            {{
              isAddingTorreToSpecificConjunto
                ? `Registrar Nueva Torre en: ${parentConjuntoLock?.nombre}`
                : (isEditing ? 'Editar Configuración de Inmueble' : 'Registrar Nuevo Condominio o Conjunto')
            }}
          </span>
          <div class="d-flex align-center gap-2">
            <v-chip v-if="isAddingTorreToSpecificConjunto" color="teal" size="small" variant="flat" class="font-weight-bold">
              🗼 Añadiendo Torre a Complejo
            </v-chip>
            <v-btn v-if="$vuetify.display.xs" icon="mdi-close" variant="text" size="small" @click="dialog = false" />
          </div>
        </v-card-title>
        <v-card-text>
          <v-form @submit.prevent="saveCondominio">
            <!-- 1. Tipo de Estructura -->
            <div class="text-caption font-weight-bold text-primary mb-2">1. TIPO DE ESTRUCTURA Y JERARQUÍA</div>
            
            <!-- Modo Fijo: Cuando se añade una torre a un conjunto específico desde la tabla -->
            <div v-if="isAddingTorreToSpecificConjunto" class="mb-4">
              <v-alert color="teal-lighten-5" variant="flat" class="border border-teal-200 text-teal-900 pa-3 rounded-lg">
                <div class="d-flex align-center justify-space-between flex-wrap gap-2">
                  <div class="d-flex align-center">
                    <v-avatar color="teal" size="38" class="mr-3 text-white font-weight-bold">
                      <v-icon icon="mdi-office-building-plus" size="22" />
                    </v-avatar>
                    <div>
                      <div class="text-caption font-weight-bold text-teal-800">CONJUNTO RESIDENCIAL MATRIZ (PRE-CARGADO):</div>
                      <div class="text-subtitle-2 font-weight-bold text-teal-950">{{ parentConjuntoLock?.nombre }}</div>
                      <div class="text-caption text-teal-700">RIF: {{ parentConjuntoLock?.rif }} • Esta torre se vinculará de forma exclusiva a este complejo.</div>
                    </div>
                  </div>
                  <v-chip color="teal" size="small" variant="flat" class="font-weight-bold">
                    🔒 Complejo Fijo
                  </v-chip>
                </div>
              </v-alert>

              <v-row class="mt-1">
                <v-col cols="12" sm="6">
                  <v-text-field
                    v-model="form.torre_bloque"
                    label="Identificador de Torre / Bloque"
                    placeholder="ej. Torre A, Torre 1, Edificio Norte"
                    prepend-inner-icon="mdi-office-building-marker"
                    required
                  />
                </v-col>
                <v-col cols="12" sm="6">
                  <v-text-field
                    :model-value="parentConjuntoLock?.nombre"
                    label="Conjunto Padre Vinculado"
                    disabled
                    readonly
                    prepend-inner-icon="mdi-lock"
                    hint="No modificable al registrar desde la fila del conjunto"
                    persistent-hint
                  />
                </v-col>
              </v-row>
            </div>

            <!-- Modo Libre: Creación general o edición -->
            <v-row v-else>
              <v-col cols="12" :sm="form.tipo_entidad === 'torre_edificio' ? 4 : 12">
                <v-select
                  v-model="form.tipo_entidad"
                  label="Tipo de Condominio"
                  :items="[
                    { title: '🏘️ Conjunto Residencial Matriz (Complejo)', value: 'conjunto_residencial' },
                    { title: '🏢 Edificio / Condominio Único Independiente', value: 'edificio_independiente' },
                    { title: '🗼 Torre / Edificio dentro de un Conjunto', value: 'torre_edificio' },
                  ]"
                  item-title="title"
                  item-value="value"
                  :disabled="!authStore.isSuperAdmin"
                  :hint="!authStore.isSuperAdmin ? '🔒 Solo el Super Administrador puede modificar la estructura y tipo de condominio' : ''"
                  :persistent-hint="!authStore.isSuperAdmin"
                  required
                />
              </v-col>

              <!-- Si es torre de un conjunto, seleccionar conjunto padre -->
              <v-col v-if="form.tipo_entidad === 'torre_edificio'" cols="12" sm="4">
                <v-select
                  v-model="form.parent_id"
                  label="Conjunto Residencial Matriz"
                  :items="conjuntosList"
                  item-title="nombre"
                  item-value="id"
                  placeholder="Selecciona el Conjunto Padre"
                  required
                  @update:model-value="onParentConjuntoSelected"
                />
              </v-col>

              <v-col v-if="form.tipo_entidad === 'torre_edificio'" cols="12" sm="4">
                <v-text-field
                  v-model="form.torre_bloque"
                  label="Identificador de Torre / Bloque"
                  placeholder="ej. Torre A, Torre 1, Edificio Norte"
                  required
                />
              </v-col>
            </v-row>

            <v-divider class="my-3" />
            <!-- 2. Datos Fiscales -->
            <div class="text-caption font-weight-bold text-primary mb-2">2. DATOS FISCALES Y UBICACIÓN</div>
            <v-row>
              <v-col cols="12" sm="8">
                <v-text-field
                  v-model="form.nombre"
                  label="Nombre Oficial"
                  placeholder="ej. CONJUNTO RESIDENCIAL LAS TRINITARIAS"
                  required
                />
              </v-col>
              <v-col cols="12" sm="4">
                <v-text-field
                  v-model="form.rif"
                  label="RIF"
                  placeholder="ej. J-300576531"
                  required
                />
              </v-col>
              <v-col cols="12">
                <v-text-field
                  v-model="form.direccion"
                  label="Dirección Completa (aparecerá en el encabezado del recibo)"
                  placeholder="ej. Av. Principal Las Trinitarias, Caracas"
                  required
                />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="form.telefono"
                  label="Teléfono de Contacto"
                  placeholder="ej. 0412-123-45-67"
                />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="form.email"
                  label="Correo Electrónico Oficial"
                  placeholder="ej. administracion@condominio.com"
                />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="form.numero_apartamentos"
                  label="Cantidad de Apartamentos / Unidades"
                  type="number"
                  placeholder="ej. 60"
                  required
                />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="form.cuota_mantenimiento_base"
                  label="Cuota Base de Mantenimiento ($)"
                  type="number"
                  step="0.01"
                  placeholder="ej. 50.00"
                />
              </v-col>
            </v-row>

            <!-- Sección Especial: Gestionar Múltiples Torres en Conjuntos Residenciales (Creación y Edición) -->
            <template v-if="form.tipo_entidad === 'conjunto_residencial'">
              <v-divider class="my-4" />
              <v-card class="pa-4 bg-purple-50 border border-purple-200">
                <div class="d-flex justify-space-between align-center mb-3">
                  <div>
                    <div class="text-subtitle-2 font-weight-bold text-purple-900">
                      🏙️ Torres del Conjunto Residencial
                    </div>
                    <div class="text-caption text-purple-700">
                      Puedes agregar, editar o remover las torres de este complejo y definir el administrador responsable de cada una.
                    </div>
                  </div>
                  <v-btn size="small" color="purple" prepend-icon="mdi-plus" variant="flat" @click="agregarFilaTorre">
                    Agregar Otra Torre
                  </v-btn>
                </div>

                <div v-for="(torre, tIdx) in form.torres_hijas" :key="tIdx" class="pa-3 bg-white rounded border border-purple-100 mb-2">
                  <div class="d-flex justify-space-between align-center mb-2">
                    <span class="text-caption font-weight-bold text-slate-800">Torre #{{ tIdx + 1 }}</span>
                    <v-btn
                      v-if="form.torres_hijas.length > 1"
                      icon="mdi-trash-can-outline"
                      size="x-small"
                      color="error"
                      variant="text"
                      title="Remover esta torre"
                      @click="removerFilaTorre(tIdx)"
                    />
                  </div>
                  <v-row dense>
                    <v-col cols="12" sm="4">
                      <v-text-field v-model="torre.torre_bloque" label="Identificador" placeholder="Torre A" density="compact" hide-details />
                    </v-col>
                    <v-col cols="12" sm="4">
                      <v-text-field v-model="torre.numero_apartamentos" label="N° Apartamentos" type="number" placeholder="ej. 20" density="compact" hide-details />
                    </v-col>
                    <v-col cols="12" sm="4">
                      <v-select
                        v-model="torre.admin_user_id"
                        label="Administrador Asignado"
                        :items="adminsList"
                        item-title="label"
                        item-value="id"
                        density="compact"
                        hide-details
                        clearable
                        placeholder="Mismo del conjunto"
                      />
                    </v-col>
                  </v-row>
                </div>
                <div v-if="!form.torres_hijas.length" class="text-center py-4 text-purple-600 text-caption">
                  No hay torres agregadas a este conjunto. Haz clic en "Agregar Otra Torre" para añadir una.
                </div>
              </v-card>
            </template>

            <v-divider class="my-3" />
            <!-- 3. Cuentas Bancarias y BCV -->
            <div class="text-caption font-weight-bold text-primary mb-2">3. CUENTAS BANCARIAS Y TASA OFICIAL BCV</div>
            <v-row>
              <v-col cols="12" sm="4">
                <v-text-field
                  v-model="form.banco_nombre"
                  label="Nombre del Banco"
                  placeholder="ej. BNC, Banesco, Mercantil"
                />
              </v-col>
              <v-col cols="12" sm="4">
                <v-text-field
                  v-model="form.cuenta_bancaria_bs"
                  label="N° Cuenta Corriente en Bs."
                  placeholder="ej. 0191 0514 8221 0001 8351"
                />
              </v-col>
              <v-col cols="12" sm="4">
                <v-text-field
                  v-model="form.cuenta_bancaria_usd"
                  label="N° Cuenta en Divisas ($ USD)"
                  placeholder="ej. 0191 0012 0223 1202 5152"
                />
              </v-col>

              <!-- Datos Destino para Pago Móvil -->
              <v-col cols="12" sm="4">
                <v-text-field
                  v-model="form.pago_movil_banco"
                  label="Banco Destino Pago Móvil"
                  placeholder="ej. BNC / Banesco"
                />
              </v-col>
              <v-col cols="12" sm="4">
                <v-text-field
                  v-model="form.pago_movil_cedula"
                  label="Cédula / RIF Pago Móvil"
                  placeholder="ej. J-300576531"
                />
              </v-col>
              <v-col cols="12" sm="4">
                <v-text-field
                  v-model="form.pago_movil_telefono"
                  label="Teléfono Pago Móvil"
                  placeholder="ej. 0414-1234567"
                />
              </v-col>
              <v-col cols="12" sm="4">
                <v-text-field
                  v-model="form.tasa_cambio"
                  label="Tasa Oficial BCV (Bs./$)"
                  type="number"
                  step="0.01"
                  readonly
                  prepend-inner-icon="mdi-lock"
                  hint="🔒 Tasa Oficial centralizada (fija en el formulario y sincronizada por el Super Admin)"
                  persistent-hint
                  class="bg-slate-50"
                >
                  <template #append-inner v-if="authStore.isMaster">
                    <v-btn
                      size="x-small"
                      color="teal-darken-1"
                      variant="tonal"
                      title="Sincronizar con Tasa Central Activa"
                      @click="form.tasa_cambio = authStore.tasaCambioCentral || 36.50"
                    >
                      ⚡ Central
                    </v-btn>
                  </template>
                </v-text-field>
              </v-col>
              <v-col cols="12" sm="4">
                <v-text-field
                  v-model="form.fondo_reserva_porcentaje"
                  label="% Fondo de Reserva"
                  type="number"
                  step="0.01"
                  placeholder="10"
                />
              </v-col>
              <v-col cols="12" sm="4">
                <v-text-field
                  v-model="form.fondo_reserva_acumulado"
                  label="Fondo Reserva Acumulado ($)"
                  type="number"
                  step="0.01"
                  placeholder="0.00"
                />
              </v-col>

              <!-- Toggle Regla de 5 Días Tasa de Emisión Fija -->
              <v-col cols="12">
                <div class="pa-3 bg-teal-50 border border-teal-200 rounded-lg">
                  <v-checkbox
                    v-model="form.mantener_tasa_emision_5_dias"
                    color="teal-darken-2"
                    hide-details
                    density="compact"
                  >
                    <template #label>
                      <div>
                        <div class="text-body-2 font-weight-bold text-teal-950 d-flex align-center">
                          <v-icon icon="mdi-shield-lock" size="18" color="teal-darken-2" class="mr-1" />
                          Mantener fija la tasa de emisión durante los primeros 5 días
                        </div>
                        <div class="text-caption text-teal-800" style="font-size: 11px;">
                          Los recibos de pago emitidos mantendrán por 5 días la tasa oficial que estaba activa cuando se generó el recibo. A partir del 6to día en adelante, el recibo se actualizará automáticamente según la tasa oficial del día correspondiente.
                        </div>
                      </div>
                    </template>
                  </v-checkbox>
                </div>
              </v-col>
            </v-row>

            <v-divider class="my-3" />
            <!-- 4. Notas del Recibo -->
            <div class="text-caption font-weight-bold text-primary mb-2">4. CLÁUSULAS Y NOTAS AL PIE DEL RECIBO</div>
            <v-textarea
              v-model="form.notas_recibo"
              label="Notas y Cláusulas Legales al pie del recibo"
              rows="2"
              placeholder="ej. VENCIMIENTO A LOS 5 DÍAS DE SU EMISIÓN. APLICA TASA DE CAMBIO BCV..."
            />

            <!-- 5. Asignación de Administrador -->
            <v-divider class="my-3" />
            <div class="text-caption font-weight-bold text-primary mb-2">
              {{ form.tipo_entidad === 'conjunto_residencial' ? '5. ADMINISTRADOR GENERAL DEL COMPLEJO RESIDENCIAL' : '5. ASIGNACIÓN DE ADMINISTRADOR RESPONSABLE DE ESTA TORRE / EDIFICIO' }}
            </div>
            <v-radio-group v-model="adminMode" inline class="mb-2">
              <v-radio label="Asignar Administrador Existente" value="existente" />
              <v-radio v-if="!isEditing" label="Registrar Nuevo Administrador" value="nuevo" />
            </v-radio-group>

            <v-row v-if="adminMode === 'existente'">
              <v-col cols="12">
                <v-autocomplete
                  v-model="form.admin_user_id"
                  label="Seleccionar Administrador del Sistema"
                  :items="adminsList"
                  item-title="label"
                  item-value="id"
                  clearable
                  no-data-text="No se encontraron administradores disponibles"
                  :placeholder="form.tipo_entidad === 'conjunto_residencial' ? 'Selecciona el administrador general del complejo' : 'Selecciona el administrador responsable de esta torre'"
                />
                <div class="text-caption text-slate-500">
                  {{ form.tipo_entidad === 'conjunto_residencial'
                    ? 'ℹ️ Administrador principal del complejo (se aplicará por defecto a cualquier torre que no tenga administrador individual).'
                    : 'ℹ️ Este administrador podrá gestionar esta torre desde su cuenta y alternar entre sus condominios asignados.'
                  }}
                </div>
              </v-col>
            </v-row>

            <v-row v-else-if="adminMode === 'nuevo' && !isEditing">
              <v-col cols="12" sm="6">
                <v-text-field v-model="form.admin_name" label="Nombre y Apellido" required />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field v-model="form.admin_email" label="Correo Electrónico" required />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field v-model="form.admin_cedula" label="Cédula de Identidad" />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field v-model="form.admin_password" label="Contraseña Inicial" type="password" />
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn variant="text" @click="dialog = false">Cancelar</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveCondominio">
            {{
              isAddingTorreToSpecificConjunto
                ? 'Guardar Torre en este Complejo'
                : (isEditing ? 'Guardar Cambios' : 'Guardar Condominio / Conjunto')
            }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dialog for Central Exchange Rate (Tasa BCV Central) -->
    <v-dialog v-model="tasaDialog" max-width="520">
      <v-card class="pa-4 rounded-xl">
        <v-card-title class="font-weight-bold text-slate-900 d-flex align-center">
          <v-avatar color="teal-lighten-5" size="36" class="mr-3">
            <v-icon icon="mdi-bank-outline" color="teal-darken-2" />
          </v-avatar>
          <div>
            <div class="text-subtitle-1 font-weight-bold">Tasa Oficial BCV Central</div>
            <div class="text-caption text-slate-500 font-weight-regular">Configuración global de tasa de cambio oficial</div>
          </div>
        </v-card-title>
        
        <v-card-text class="pt-2">
          <!-- Live BCV Fetch Banner -->
          <div class="mb-3 pa-3 rounded-lg bg-teal-50 border border-teal-200 d-flex align-center justify-space-between flex-wrap gap-2">
            <div>
              <div class="text-caption font-weight-bold text-teal-950 d-flex align-center">
                <v-icon icon="mdi-clock-outline" size="16" color="teal-darken-2" class="mr-1" />
                Actualización Automática Programada
              </div>
              <div class="text-caption text-teal-800" style="font-size: 11px;">
                Se ejecuta automáticamente una vez al día: <strong>8:00 PM</strong> (Hora VE)
              </div>
            </div>
            <v-btn
              color="teal-darken-2"
              variant="elevated"
              size="small"
              prepend-icon="mdi-web-sync"
              class="text-capitalize font-weight-bold"
              :loading="consultandoBcv"
              @click="consultarTasaBcv"
            >
              Consultar BCV en Vivo
            </v-btn>
          </div>

          <v-alert
            v-if="bcvInfo"
            color="success"
            variant="tonal"
            density="compact"
            class="mb-3 text-caption"
            closable
            @click:close="bcvInfo = null"
          >
            ✅ <strong>{{ bcvInfo.fuente }}:</strong> Tasa obtenida <strong>Bs. {{ Number(bcvInfo.tasa).toFixed(2) }}</strong> (Fecha Valor: {{ bcvInfo.fecha_valor }})
          </v-alert>

          <v-form @submit.prevent="guardarTasaCentral">
            <v-text-field
              v-model="nuevaTasaInput"
              label="Tasa de Cambio Oficial (Bs. por 1 USD)"
              prefix="Bs."
              type="number"
              step="0.0001"
              variant="outlined"
              placeholder="ej. 36.50"
              class="mb-2"
              autofocus
              required
            />

            <v-checkbox
              v-if="authStore.isMaster"
              v-model="sincronizarTodasLasTorres"
              color="primary"
              hide-details
              label="Sincronizar y actualizar inmediatamente todas las torres y condominios a esta nueva tasa"
              class="text-caption"
            />
          </v-form>
        </v-card-text>

        <v-card-actions class="justify-end gap-2">
          <v-btn variant="text" color="slate-600" @click="tasaDialog = false">Cancelar</v-btn>
          <v-btn color="primary" :loading="guardandoTasa" class="font-weight-bold" @click="guardarTasaCentral">
            Guardar Tasa Central
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dialog for Quick Admin Assignment / Transfer -->
    <v-dialog v-model="assignDialog" max-width="550">
      <v-card class="pa-4">
        <v-card-title class="font-weight-bold">
          Asignar / Transferir Administración
        </v-card-title>
        <v-card-text>
          <p class="text-caption text-slate-600 mb-3">
            Inmueble: <strong>{{ targetCondo?.nombre }}</strong>
          </p>
          <v-alert color="info" variant="tonal" class="mb-4 text-caption">
            💡 <strong>Sin pérdida de datos:</strong> Toda la información histórica, propietarios, facturas y pagos registrados en esta torre se conservan intactos y serán visualizados de inmediato por el nuevo administrador asignado.
          </v-alert>

          <v-select
            v-model="assignForm.user_id"
            label="Administrador a Asignar"
            :items="adminsList"
            item-title="label"
            item-value="id"
            required
            class="mb-3"
          />

          <v-checkbox
            v-model="assignForm.reemplazar_anteriores"
            label="Reemplazar administrador anterior (Transferencia exclusiva)"
            color="primary"
            hide-details
          />
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn variant="text" @click="assignDialog = false">Cancelar</v-btn>
          <v-btn color="indigo" :loading="saving" @click="submitAssignment">Guardar Asignación</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Modal de Auditoría y Desbloqueo de Recibos para Super Admin -->
    <v-dialog v-model="recibosDialog" max-width="950">
      <v-card class="pa-4">
        <div class="d-flex justify-space-between align-center mb-3">
          <div class="d-flex align-center">
            <v-avatar color="amber-lighten-4" size="44" class="mr-3">
              <v-icon icon="mdi-receipt-text-clock" color="amber-darken-4" size="26" />
            </v-avatar>
            <div>
              <v-card-title class="font-weight-bold text-slate-900 pa-0" style="line-height: 1.2;">
                Auditoría y Recibos Mensuales: {{ selectedCondoForRecibos?.nombre }}
              </v-card-title>
              <div class="text-caption text-slate-500">
                RIF: {{ selectedCondoForRecibos?.rif }} • Control y Desbloqueo de Recibos por el Super Admin
              </div>
            </div>
          </div>
          <v-chip color="amber-darken-3" variant="tonal" class="font-weight-bold">
            🛡️ Máx. 2 Reaperturas por Período
          </v-chip>
        </div>

        <v-alert color="info" variant="tonal" density="compact" class="mb-4 text-caption">
          ℹ️ Como Super Admin / Dueño del Sistema, desde aquí puedes supervisar todos los períodos de cobro de esta torre. Si un recibo fue certificado y requiere correcciones, puedes <strong>devolverlo a borrador</strong> para que el administrador pueda editar los montos y conceptos (máx. 2 reaperturas).
        </v-alert>

        <v-card-text class="pa-0">
          <div v-if="loadingRecibos" class="text-center py-8">
            <v-progress-circular indeterminate color="primary" />
            <div class="text-caption text-slate-500 mt-2">Cargando histórico de recibos...</div>
          </div>

          <v-table v-else-if="condoPeriodosList.length" density="comfortable" hover class="border border-slate-100 rounded-lg">
            <thead>
              <tr class="bg-slate-50 text-slate-700">
                <th>Período</th>
                <th>Unidades</th>
                <th>Monto Facturado</th>
                <th>Estado</th>
                <th>Certificado Por / Fecha</th>
                <th>Reaperturas</th>
                <th class="text-right">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in condoPeriodosList" :key="item.periodo">
                <td class="font-weight-bold text-slate-800">
                  📅 {{ item.periodo }}
                </td>
                <td>
                  <v-chip size="x-small" color="primary" variant="tonal">
                    {{ item.total_facturas }} aptos
                  </v-chip>
                </td>
                <td>
                  <div class="font-weight-bold text-slate-900">
                    ${{ Number(item.monto_total_usd).toFixed(2) }} USD
                  </div>
                  <div class="text-caption text-slate-500">
                    Bs. {{ Number(item.monto_total_bs).toFixed(2) }}
                  </div>
                </td>
                <td>
                  <v-chip
                    v-if="item.estado_certificacion === 'certificado'"
                    color="success"
                    size="small"
                    variant="flat"
                    class="font-weight-bold"
                  >
                    🔒 Certificado
                  </v-chip>
                  <v-chip
                    v-else
                    color="warning"
                    size="small"
                    variant="flat"
                    class="font-weight-bold"
                  >
                    ✏️ Borrador Editable
                  </v-chip>
                </td>
                <td>
                  <div v-if="item.estado_certificacion === 'certificado' && item.fecha_certificacion" class="text-caption">
                    <strong>{{ item.certificado_por_nombre || 'Administrador' }}</strong>
                    <div class="text-slate-500">{{ item.fecha_certificacion }}</div>
                  </div>
                  <span v-else class="text-caption text-slate-400 italic">Pendiente por certificar</span>
                </td>
                <td>
                  <v-chip
                    :color="item.veces_reabierto >= 2 ? 'error' : (item.veces_reabierto > 0 ? 'amber-darken-3' : 'grey-lighten-1')"
                    size="small"
                    variant="tonal"
                    class="font-weight-bold"
                  >
                    {{ item.veces_reabierto || 0 }} de 2 utilizadas
                  </v-chip>
                </td>
                <td class="text-right">
                  <!-- Ver / Descargar PDF -->
                  <v-btn
                    icon="mdi-file-pdf-box"
                    size="small"
                    variant="text"
                    :color="item.estado_certificacion === 'borrador' ? 'amber-darken-3' : 'primary'"
                    class="mr-1"
                    :title="item.estado_certificacion === 'borrador' ? 'Ver PDF Borrador (con marca de agua)' : 'Descargar PDF Oficial Certificado'"
                    :href="'/api/v1/reportes/recibo-periodo?periodo=' + encodeURIComponent(item.periodo) + '&condominio_id=' + item.condominio_id"
                    target="_blank"
                  />

                  <!-- Devolver a Borrador (Solo si está certificado) -->
                  <v-btn
                    v-if="item.estado_certificacion === 'certificado'"
                    size="small"
                    variant="flat"
                    :color="item.veces_reabierto >= 2 ? 'grey' : 'amber-darken-3'"
                    :disabled="item.veces_reabierto >= 2"
                    prepend-icon="mdi-lock-reset"
                    class="text-capitalize font-weight-bold"
                    @click="ejecutarReaperturaRecibo(item)"
                  >
                    {{ item.veces_reabierto >= 2 ? 'Límite (2/2)' : 'Devolver a Borrador' }}
                  </v-btn>
                </td>
              </tr>
            </tbody>
          </v-table>

          <div v-else class="text-center py-10 border border-dashed rounded-lg bg-slate-50">
            <v-icon icon="mdi-receipt-text-outline" size="48" color="slate-400" class="mb-2" />
            <div class="text-subtitle-1 font-weight-bold text-slate-700">No hay recibos generados aún</div>
            <div class="text-caption text-slate-500">
              El administrador de esta torre todavía no ha emitido avisos de cobro mensuales.
            </div>
          </div>
        </v-card-text>

        <v-card-actions class="justify-end mt-4 pa-0">
          <v-btn variant="text" color="slate-700" @click="recibosDialog = false">Cerrar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Modal de Configuración Integral de Cuentas Bancarias y Canales de Pago -->
    <v-dialog v-model="cuentasDialog" max-width="920" scrollable>
      <v-card class="rounded-xl overflow-hidden border border-slate-100 shadow-xl d-flex flex-column" style="max-height: 90vh;">
        <!-- Header con estilo elegante -->
        <div class="bg-slate-900 text-white pa-4 pa-sm-5 d-flex align-center justify-space-between flex-shrink-0">
          <div class="d-flex align-center gap-3">
            <v-avatar color="teal-darken-2" size="42" class="elevation-2">
              <v-icon icon="mdi-bank-transfer" color="white" size="24" />
            </v-avatar>
            <div>
              <div class="text-subtitle-1 font-weight-bold leading-tight">
                Cuentas y Métodos de Cobro: {{ selectedCondoForCuentas?.nombre }}
              </div>
              <div class="text-caption text-slate-300">
                RIF: {{ selectedCondoForCuentas?.rif }} • Canales bancarios, Pago Móvil, Cajas de Efectivo y Zelle
              </div>
            </div>
          </div>
          <v-btn icon="mdi-close" variant="text" color="white" density="comfortable" @click="cuentasDialog = false" />
        </div>

        <v-card-text class="pa-4 pa-sm-5 overflow-y-auto" style="max-height: 72vh;">
          <!-- Barra superior de acciones -->
          <div class="d-flex flex-column flex-sm-row justify-space-between align-start align-sm-center gap-2 mb-4">
            <div>
              <div class="text-subtitle-2 font-weight-bold text-slate-800">Canales de Pago Disponibles para Propietarios</div>
              <div class="text-caption text-slate-500">Los canales activos se mostrarán automáticamente al notificar pagos.</div>
            </div>
            <v-btn
              v-if="!showCuentaForm"
              color="teal-darken-1"
              variant="flat"
              prepend-icon="mdi-plus-circle"
              class="font-weight-bold text-capitalize"
              @click="openCreateCuentaForm"
            >
              + Nuevo Canal de Pago
            </v-btn>
          </div>

          <!-- Formulario para Crear / Editar Cuenta o Método -->
          <v-expand-transition>
            <v-card v-if="showCuentaForm" variant="outlined" class="pa-4 mb-5 border-teal-300 bg-teal-50/30 rounded-xl">
              <div class="d-flex justify-space-between align-center mb-3">
                <div class="d-flex align-center gap-2">
                  <v-icon icon="mdi-form-select" color="teal-darken-2" />
                  <span class="text-subtitle-2 font-weight-bold text-teal-900">
                    {{ isEditingCuenta ? 'Editar Canal de Cobro' : 'Nuevo Canal de Cobro / Cuenta' }}
                  </span>
                </div>
                <v-btn size="small" variant="text" color="slate-600" prepend-icon="mdi-close" @click="showCuentaForm = false">
                  Cerrar Formulario
                </v-btn>
              </div>

              <v-form @submit.prevent="submitCuentaForm">
                <v-row density="compact">
                  <!-- Tipo de Canal -->
                  <v-col cols="12" sm="6">
                    <label class="text-caption font-weight-bold text-slate-700 text-uppercase d-block mb-1">
                      Tipo de Método / Canal *
                    </label>
                    <v-select
                      v-model="cuentaForm.tipo_cuenta"
                      :items="tiposCanalOptions"
                      item-title="title"
                      item-value="value"
                      variant="outlined"
                      density="comfortable"
                      hide-details
                      color="teal-darken-1"
                      class="bg-white rounded"
                      @update:model-value="onTipoCuentaChange"
                    />
                  </v-col>

                  <!-- Moneda -->
                  <v-col cols="12" sm="6">
                    <label class="text-caption font-weight-bold text-slate-700 text-uppercase d-block mb-1">
                      Moneda del Canal *
                    </label>
                    <v-select
                      v-model="cuentaForm.moneda"
                      :items="[
                        { title: '🇻🇪 Bolívares (VES)', value: 'VES' },
                        { title: '💵 Dólares ($ USD)', value: 'USD' },
                        { title: '💶 Euros (€ EUR)', value: 'EUR' }
                      ]"
                      item-title="title"
                      item-value="value"
                      variant="outlined"
                      density="comfortable"
                      hide-details
                      color="teal-darken-1"
                      class="bg-white rounded"
                    />
                  </v-col>

                  <!-- Banco / Entidad -->
                  <v-col cols="12" sm="6" v-if="!['efectivo_usd', 'efectivo_ves'].includes(cuentaForm.tipo_cuenta)">
                    <label class="text-caption font-weight-bold text-slate-700 text-uppercase d-block mb-1">
                      Banco / Entidad Financiera *
                    </label>
                    <v-combobox
                      v-model="cuentaForm.banco_nombre"
                      :items="bancosCatalogoList"
                      placeholder="Selecciona o escribe el banco..."
                      variant="outlined"
                      density="comfortable"
                      hide-details
                      color="teal-darken-1"
                      class="bg-white rounded"
                    />
                  </v-col>

                  <!-- Responsable / Ubicación para Efectivo -->
                  <v-col cols="12" sm="6" v-else>
                    <label class="text-caption font-weight-bold text-slate-700 text-uppercase d-block mb-1">
                      Lugar de Recepción / Responsable *
                    </label>
                    <v-text-field
                      v-model="cuentaForm.banco_nombre"
                      placeholder="Ej: Oficina de Administración / Conserjería"
                      variant="outlined"
                      density="comfortable"
                      hide-details
                      color="teal-darken-1"
                      class="bg-white rounded"
                    />
                  </v-col>

                  <!-- Número de Cuenta (Para transferencias) -->
                  <v-col cols="12" sm="6" v-if="['corriente', 'ahorro', 'custodia_usd'].includes(cuentaForm.tipo_cuenta)">
                    <label class="text-caption font-weight-bold text-slate-700 text-uppercase d-block mb-1">
                      Número de Cuenta (20 Dígitos)
                    </label>
                    <v-text-field
                      v-model="cuentaForm.numero_cuenta"
                      placeholder="0102 0123 45 6789012345"
                      variant="outlined"
                      density="comfortable"
                      hide-details
                      color="teal-darken-1"
                      class="bg-white rounded font-mono"
                    />
                  </v-col>

                  <!-- Teléfono Pago Móvil -->
                  <v-col cols="12" sm="6" v-if="cuentaForm.tipo_cuenta === 'pago_movil'">
                    <label class="text-caption font-weight-bold text-slate-700 text-uppercase d-block mb-1">
                      Teléfono Celular Pago Móvil *
                    </label>
                    <v-text-field
                      v-model="cuentaForm.telefono_pago_movil"
                      placeholder="Ej: 0414-1234567"
                      variant="outlined"
                      density="comfortable"
                      hide-details
                      color="teal-darken-1"
                      class="bg-white rounded font-mono"
                    />
                  </v-col>

                  <!-- Correo Zelle -->
                  <v-col cols="12" sm="6" v-if="cuentaForm.tipo_cuenta === 'zelle'">
                    <label class="text-caption font-weight-bold text-slate-700 text-uppercase d-block mb-1">
                      Correo Electrónico Zelle *
                    </label>
                    <v-text-field
                      v-model="cuentaForm.numero_cuenta"
                      placeholder="ejemplo@correo.com"
                      type="email"
                      variant="outlined"
                      density="comfortable"
                      hide-details
                      color="teal-darken-1"
                      class="bg-white rounded"
                    />
                  </v-col>

                  <!-- RIF / Cédula del Titular -->
                  <v-col cols="12" sm="6" v-if="!['efectivo_usd', 'efectivo_ves'].includes(cuentaForm.tipo_cuenta)">
                    <label class="text-caption font-weight-bold text-slate-700 text-uppercase d-block mb-1">
                      Cédula / RIF del Titular
                    </label>
                    <v-text-field
                      v-model="cuentaForm.titular_identificacion"
                      placeholder="Ej: J-12345678-0 o V-12345678"
                      variant="outlined"
                      density="comfortable"
                      hide-details
                      color="teal-darken-1"
                      class="bg-white rounded font-mono"
                    />
                  </v-col>

                  <!-- Nombre del Titular -->
                  <v-col cols="12" sm="6" v-if="!['efectivo_usd', 'efectivo_ves'].includes(cuentaForm.tipo_cuenta)">
                    <label class="text-caption font-weight-bold text-slate-700 text-uppercase d-block mb-1">
                      Nombre / Razón Social del Titular
                    </label>
                    <v-text-field
                      v-model="cuentaForm.titular_nombre"
                      placeholder="Ej: Condominio Residencias El Ávila"
                      variant="outlined"
                      density="comfortable"
                      hide-details
                      color="teal-darken-1"
                      class="bg-white rounded"
                    />
                  </v-col>

                  <!-- Instrucciones para el copropietario -->
                  <v-col cols="12">
                    <label class="text-caption font-weight-bold text-slate-700 text-uppercase d-block mb-1">
                      Instrucciones u Horarios para el Copropietario (Opcional)
                    </label>
                    <v-textarea
                      v-model="cuentaForm.instrucciones"
                      placeholder="Ej: Indicar número de apartamento en el concepto. Horario de recepción en conserjería de 8am a 12pm."
                      rows="2"
                      variant="outlined"
                      density="comfortable"
                      hide-details
                      color="teal-darken-1"
                      class="bg-white rounded"
                    />
                  </v-col>

                  <!-- Switch Activo -->
                  <v-col cols="12" class="d-flex align-center justify-space-between pt-2">
                    <v-switch
                      v-model="cuentaForm.activo"
                      color="success"
                      label="Canal Activo (Visible para pagos de copropietarios)"
                      density="compact"
                      hide-details
                    />
                    <div class="d-flex gap-2">
                      <v-btn variant="text" color="slate-600" @click="showCuentaForm = false">Cancelar</v-btn>
                      <v-btn color="teal-darken-1" variant="flat" class="font-weight-bold" :loading="savingCuenta" type="submit">
                        {{ isEditingCuenta ? 'Guardar Cambios' : 'Registrar Canal' }}
                      </v-btn>
                    </div>
                  </v-col>
                </v-row>
              </v-form>
            </v-card>
          </v-expand-transition>

          <!-- Listado de Cuentas y Canales Registrados -->
          <div v-if="loadingCuentas" class="text-center py-8">
            <v-progress-circular indeterminate color="teal-darken-1" />
            <div class="text-caption text-slate-500 mt-2">Cargando canales de pago...</div>
          </div>

          <div v-else-if="condoCuentasList.length" class="d-flex flex-column gap-3">
            <v-card
              v-for="cuenta in condoCuentasList"
              :key="cuenta.id"
              variant="outlined"
              class="pa-3 pa-sm-4 rounded-xl transition-all"
              :class="cuenta.activo ? 'border-slate-200 bg-white hover:border-teal-300' : 'border-slate-200 bg-slate-50 opacity-75'"
            >
              <div class="d-flex flex-column flex-sm-row justify-space-between align-start align-sm-center gap-3">
                <div class="d-flex align-start gap-3">
                  <!-- Icono según tipo -->
                  <v-avatar
                    :color="getCanalColor(cuenta.tipo_cuenta)"
                    size="44"
                    rounded="lg"
                    class="elevation-1 flex-shrink-0"
                  >
                    <v-icon :icon="getCanalIcon(cuenta.tipo_cuenta)" color="white" size="24" />
                  </v-avatar>

                  <div>
                    <div class="d-flex align-center gap-2 flex-wrap">
                      <span class="text-subtitle-2 font-weight-bold text-slate-900">
                        {{ cuenta.banco_nombre }}
                      </span>
                      <v-chip size="x-small" :color="getCanalColor(cuenta.tipo_cuenta)" variant="flat" class="font-weight-bold text-uppercase">
                        {{ getCanalTitle(cuenta.tipo_cuenta) }}
                      </v-chip>
                      <v-chip size="x-small" color="slate-700" variant="tonal" class="font-weight-bold font-mono">
                        {{ cuenta.moneda }}
                      </v-chip>
                      <v-chip v-if="cuenta.activo" size="x-small" color="success" variant="flat" class="font-weight-bold">
                        ✓ Activo
                      </v-chip>
                      <v-chip v-else size="x-small" color="grey" variant="flat" class="font-weight-bold">
                        Inactivo
                      </v-chip>
                    </div>

                    <!-- Datos Bancarios / Teléfono / Correo -->
                    <div class="text-caption text-slate-600 mt-1 d-flex flex-column gap-0.5">
                      <div v-if="cuenta.numero_cuenta" class="font-mono">
                        <strong>{{ cuenta.tipo_cuenta === 'zelle' ? 'Correo:' : 'N° Cuenta:' }}</strong> {{ cuenta.numero_cuenta }}
                      </div>
                      <div v-if="cuenta.telefono_pago_movil" class="font-mono">
                        <strong>Teléfono Pago Móvil:</strong> {{ cuenta.telefono_pago_movil }}
                      </div>
                      <div v-if="cuenta.titular_nombre || cuenta.titular_identificacion">
                        <strong>Titular:</strong> {{ cuenta.titular_nombre || 'N/A' }}
                        <span v-if="cuenta.titular_identificacion" class="font-mono">({{ cuenta.titular_identificacion }})</span>
                      </div>
                      <div v-if="cuenta.instrucciones" class="text-slate-500 italic mt-0.5">
                        💬 {{ cuenta.instrucciones }}
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Acciones de la Cuenta -->
                <div class="d-flex align-center gap-1 align-self-end align-self-sm-center">
                  <v-switch
                    :model-value="cuenta.activo"
                    color="success"
                    density="compact"
                    hide-details
                    class="mr-2"
                    title="Alternar estado activo/inactivo"
                    @update:model-value="toggleActivoCuenta(cuenta)"
                  />
                  <v-btn icon="mdi-pencil" size="small" variant="tonal" color="primary" title="Editar cuenta" @click="openEditCuentaForm(cuenta)" />
                  <v-btn icon="mdi-delete" size="small" variant="tonal" color="error" title="Eliminar cuenta" @click="deleteCuenta(cuenta)" />
                </div>
              </div>
            </v-card>
          </div>

          <div v-else class="text-center py-10 border border-dashed rounded-xl bg-slate-50">
            <v-icon icon="mdi-credit-card-plus-outline" size="48" color="slate-400" class="mb-2" />
            <div class="text-subtitle-1 font-weight-bold text-slate-700">No hay canales de cobro configurados</div>
            <div class="text-caption text-slate-500 mb-3">
              Añade las cuentas bancarias, Pago Móvil o datos de efectivo para que los residentes puedan reportar sus pagos fácilmente.
            </div>
            <v-btn color="teal-darken-1" variant="flat" size="small" prepend-icon="mdi-plus" class="font-weight-bold" @click="openCreateCuentaForm">
              Registrar Primer Canal
            </v-btn>
          </div>
        </v-card-text>

        <v-card-actions class="pa-4 bg-slate-50 d-flex justify-end border-t border-slate-100">
          <v-btn variant="flat" color="slate-700" class="font-weight-bold px-4" @click="cuentasDialog = false">
            Listo / Cerrar
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import { useAuthStore } from '../../store/auth';
import { usePagination } from '../../composables/usePagination';
import DataTableHeader from '../../components/DataTableHeader.vue';
import DataTableFooter from '../../components/DataTableFooter.vue';
import SortHeader from '../../components/SortHeader.vue';

const authStore = useAuthStore();
const condominiosList = ref([]);
const adminsList = ref([]);
const dialog = ref(false);
const assignDialog = ref(false);
const targetCondo = ref(null);
const isEditing = ref(false);
const isAddingTorreToSpecificConjunto = ref(false);
const parentConjuntoLock = ref(null);
const saving = ref(false);
const adminMode = ref('existente');
const tipoFiltro = ref('');

// Estado para Cuentas Bancarias y Métodos de Cobro
const cuentasDialog = ref(false);
const selectedCondoForCuentas = ref(null);
const condoCuentasList = ref([]);
const loadingCuentas = ref(false);
const savingCuenta = ref(false);
const showCuentaForm = ref(false);
const isEditingCuenta = ref(false);
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

const tiposCanalOptions = [
    { title: '📱 Pago Móvil (Bs. VES)', value: 'pago_movil' },
    { title: '🏦 Transferencia - Cuenta Corriente', value: 'corriente' },
    { title: '🏦 Transferencia - Cuenta de Ahorro', value: 'ahorro' },
    { title: '🏦 Transferencia - Custodia Divisas ($ USD)', value: 'custodia_usd' },
    { title: '💵 Caja Efectivo Divisas ($ USD / EUR)', value: 'efectivo_usd' },
    { title: '🇻🇪 Caja Efectivo Bolívares (Bs. VES)', value: 'efectivo_ves' },
    { title: '🌐 Zelle / Cuenta Internacional', value: 'zelle' },
    { title: '⚙️ Otro Método Personalizado', value: 'otro' },
];

const getEmptyCuentaForm = () => ({
    id: null,
    tipo_cuenta: 'pago_movil',
    moneda: 'VES',
    banco_nombre: 'Banco de Venezuela',
    numero_cuenta: '',
    telefono_pago_movil: '',
    titular_identificacion: selectedCondoForCuentas.value?.rif || '',
    titular_nombre: selectedCondoForCuentas.value?.nombre || '',
    es_pago_movil: true,
    instrucciones: '',
    activo: true,
});

const cuentaForm = ref(getEmptyCuentaForm());

const onTipoCuentaChange = (val) => {
    if (val === 'pago_movil') {
        cuentaForm.value.moneda = 'VES';
        cuentaForm.value.es_pago_movil = true;
    } else if (val === 'efectivo_usd') {
        cuentaForm.value.moneda = 'USD';
        cuentaForm.value.es_pago_movil = false;
        if (!cuentaForm.value.banco_nombre || bancosCatalogoList.value.includes(cuentaForm.value.banco_nombre)) {
            cuentaForm.value.banco_nombre = 'Oficina de Administración';
        }
    } else if (val === 'efectivo_ves') {
        cuentaForm.value.moneda = 'VES';
        cuentaForm.value.es_pago_movil = false;
        if (!cuentaForm.value.banco_nombre || bancosCatalogoList.value.includes(cuentaForm.value.banco_nombre)) {
            cuentaForm.value.banco_nombre = 'Conserjería / Caja';
        }
    } else if (val === 'custodia_usd' || val === 'zelle') {
        cuentaForm.value.moneda = 'USD';
        cuentaForm.value.es_pago_movil = false;
    } else {
        cuentaForm.value.es_pago_movil = false;
    }
};

const getCanalColor = (tipo) => {
    switch (tipo) {
        case 'pago_movil': return 'deep-purple-darken-1';
        case 'corriente':
        case 'ahorro': return 'blue-darken-2';
        case 'custodia_usd': return 'teal-darken-2';
        case 'efectivo_usd': return 'emerald-darken-2';
        case 'efectivo_ves': return 'amber-darken-3';
        case 'zelle': return 'purple-darken-2';
        default: return 'slate-700';
    }
};

const getCanalIcon = (tipo) => {
    switch (tipo) {
        case 'pago_movil': return 'mdi-cellphone-check';
        case 'corriente':
        case 'ahorro': return 'mdi-bank-outline';
        case 'custodia_usd': return 'mdi-cash-multiple';
        case 'efectivo_usd': return 'mdi-currency-usd';
        case 'efectivo_ves': return 'mdi-cash';
        case 'zelle': return 'mdi-send-circle';
        default: return 'mdi-credit-card-outline';
    }
};

const getCanalTitle = (tipo) => {
    const found = tiposCanalOptions.find(o => o.value === tipo);
    return found ? found.title.split('(')[0].trim() : tipo;
};

const openCuentasDialog = async (condo) => {
    selectedCondoForCuentas.value = condo;
    cuentasDialog.value = true;
    showCuentaForm.value = false;
    await fetchBancosCatalogo();
    await fetchCuentasCondominio(condo.id);
};

const fetchBancosCatalogo = async () => {
    try {
        const { data } = await axios.get('/bancos');
        if (data.success && data.data?.length) {
            bancosCatalogoList.value = data.data.map(b => b.nombre);
        }
    } catch (e) {}
};

const fetchCuentasCondominio = async (condoId) => {
    loadingCuentas.value = true;
    try {
        const { data } = await axios.get(`/condominios/${condoId}/cuentas-bancarias`);
        if (data.success) {
            condoCuentasList.value = data.data || [];
        }
    } catch (e) {
        authStore.notify('Error al cargar cuentas del condominio', 'error');
    } finally {
        loadingCuentas.value = false;
    }
};

const openCreateCuentaForm = () => {
    isEditingCuenta.value = false;
    cuentaForm.value = getEmptyCuentaForm();
    showCuentaForm.value = true;
};

const openEditCuentaForm = (cuenta) => {
    isEditingCuenta.value = true;
    cuentaForm.value = {
        id: cuenta.id,
        tipo_cuenta: cuenta.tipo_cuenta || 'pago_movil',
        moneda: cuenta.moneda || 'VES',
        banco_nombre: cuenta.banco_nombre,
        numero_cuenta: cuenta.numero_cuenta || '',
        telefono_pago_movil: cuenta.telefono_pago_movil || '',
        titular_identificacion: cuenta.titular_identificacion || '',
        titular_nombre: cuenta.titular_nombre || '',
        es_pago_movil: Boolean(cuenta.es_pago_movil || cuenta.tipo_cuenta === 'pago_movil'),
        instrucciones: cuenta.instrucciones || '',
        activo: Boolean(cuenta.activo),
    };
    showCuentaForm.value = true;
};

const submitCuentaForm = async () => {
    if (!cuentaForm.value.banco_nombre) {
        authStore.notify('Debe especificar el banco o entidad responsable', 'warning');
        return;
    }

    savingCuenta.value = true;
    try {
        const payload = {
            ...cuentaForm.value,
            es_pago_movil: cuentaForm.value.tipo_cuenta === 'pago_movil' || cuentaForm.value.es_pago_movil,
        };

        if (isEditingCuenta.value && cuentaForm.value.id) {
            const { data } = await axios.put(`/cuentas-bancarias/${cuentaForm.value.id}`, payload);
            if (data.success) {
                authStore.notify('Canal de pago actualizado correctamente', 'success');
            }
        } else {
            const { data } = await axios.post(`/condominios/${selectedCondoForCuentas.value.id}/cuentas-bancarias`, payload);
            if (data.success) {
                authStore.notify('Nuevo canal de pago registrado exitosamente', 'success');
            }
        }

        showCuentaForm.value = false;
        await fetchCuentasCondominio(selectedCondoForCuentas.value.id);
    } catch (e) {
        authStore.notify(e.response?.data?.message || 'Error al guardar canal de pago', 'error');
    } finally {
        savingCuenta.value = false;
    }
};

const toggleActivoCuenta = async (cuenta) => {
    try {
        const payload = {
            banco_nombre: cuenta.banco_nombre,
            tipo_cuenta: cuenta.tipo_cuenta,
            moneda: cuenta.moneda,
            numero_cuenta: cuenta.numero_cuenta,
            titular_nombre: cuenta.titular_nombre,
            titular_identificacion: cuenta.titular_identificacion,
            telefono_pago_movil: cuenta.telefono_pago_movil,
            es_pago_movil: Boolean(cuenta.es_pago_movil),
            instrucciones: cuenta.instrucciones,
            activo: !cuenta.activo,
        };
        const { data } = await axios.put(`/cuentas-bancarias/${cuenta.id}`, payload);
        if (data.success) {
            cuenta.activo = !cuenta.activo;
            authStore.notify(`Canal ${cuenta.activo ? 'activado' : 'desactivado'} para copropietarios`, 'info');
        }
    } catch (e) {
        authStore.notify('Error al actualizar estado del canal', 'error');
    }
};

const deleteCuenta = async (cuenta) => {
    const result = await Swal.fire({
        title: '¿Eliminar este canal de pago?',
        text: `Se eliminará ${cuenta.banco_nombre} (${cuenta.tipo_cuenta}). Esta acción no afectará pagos pasados ya registrados.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    });

    if (result.isConfirmed) {
        try {
            await axios.delete(`/cuentas-bancarias/${cuenta.id}`);
            authStore.notify('Canal de pago eliminado', 'success');
            await fetchCuentasCondominio(selectedCondoForCuentas.value.id);
        } catch (e) {
            authStore.notify('Error al eliminar canal de pago', 'error');
        }
    }
};

const recibosDialog = ref(false);
const selectedCondoForRecibos = ref(null);
const condoPeriodosList = ref([]);
const loadingRecibos = ref(false);

const tasaDialog = ref(false);
const nuevaTasaInput = ref(authStore.tasaCambioCentral || 36.50);
const sincronizarTodasLasTorres = ref(true);
const guardandoTasa = ref(false);
const consultandoBcv = ref(false);
const bcvInfo = ref(null);

const openTasaCentralModal = () => {
    nuevaTasaInput.value = authStore.tasaCambioCentral || 36.50;
    sincronizarTodasLasTorres.value = true;
    bcvInfo.value = null;
    tasaDialog.value = true;
};

const consultarTasaBcv = async () => {
    consultandoBcv.value = true;
    try {
        const data = await authStore.consultarBcvEnVivo();
        if (data && data.tasa) {
            nuevaTasaInput.value = data.tasa;
            bcvInfo.value = data;
            authStore.notify(`Tasa BCV obtenida: Bs. ${Number(data.tasa).toFixed(2)} (${data.fuente})`, 'success');
        }
    } catch (e) {
        authStore.notify(e.message || 'Error al consultar la tasa del BCV', 'error');
    } finally {
        consultandoBcv.value = false;
    }
};

const guardarTasaCentral = async () => {
    if (!nuevaTasaInput.value || Number(nuevaTasaInput.value) <= 0) {
        authStore.notify('Ingrese un valor de tasa válido', 'warning');
        return;
    }
    guardandoTasa.value = true;
    try {
        await authStore.updateTasaCentral(nuevaTasaInput.value, sincronizarTodasLasTorres.value);
        tasaDialog.value = false;
        fetchCondominios();
    } catch (e) {
        authStore.notify(e.response?.data?.message || 'Error al actualizar tasa central', 'error');
    } finally {
        guardandoTasa.value = false;
    }
};

const assignForm = ref({
    user_id: null,
    reemplazar_anteriores: false,
});

const expandedIds = ref([]);

const toggleExpand = (id) => {
    const idx = expandedIds.value.indexOf(id);
    if (idx > -1) {
        expandedIds.value.splice(idx, 1);
    } else {
        expandedIds.value.push(id);
    }
};

const isExpanded = (id) => expandedIds.value.includes(id);

const expandAll = () => {
    expandedIds.value = condominiosList.value
        .filter(c => c.tipo_entidad === 'conjunto_residencial' || (c.torres && c.torres.length > 0))
        .map(c => c.id);
};

const collapseAll = () => {
    expandedIds.value = [];
};

const getTotalAptos = (c) => {
    if (c.torres && c.torres.length > 0) {
        return c.torres.reduce((acc, t) => acc + (Number(t.numero_apartamentos) || 0), 0);
    }
    return Number(c.numero_apartamentos) || 0;
};

const kpiTotalInmuebles = computed(() => {
    return condominiosList.value.filter(c => !c.parent_id).length;
});

const kpiConjuntosCount = computed(() => {
    return condominiosList.value.filter(c => c.tipo_entidad === 'conjunto_residencial' || (!c.parent_id && c.torres && c.torres.length > 0)).length;
});

const kpiEdificiosUnicosCount = computed(() => {
    return condominiosList.value.filter(c => c.tipo_entidad === 'edificio_independiente' && !c.parent_id && (!c.torres || c.torres.length === 0)).length;
});

const kpiTotalTorres = computed(() => {
    return condominiosList.value.filter(c => c.tipo_entidad === 'torre_edificio' || c.parent_id).length;
});

const kpiTotalApartamentos = computed(() => {
    return condominiosList.value.reduce((acc, c) => acc + (Number(c.numero_apartamentos) || 0), 0);
});

const rootCondominios = computed(() => {
    let list = condominiosList.value.filter(c => !c.parent_id);
    if (tipoFiltro.value) {
        if (tipoFiltro.value === 'conjunto_residencial') {
            list = list.filter(c => c.tipo_entidad === 'conjunto_residencial' || (c.torres && c.torres.length > 0));
        } else if (tipoFiltro.value === 'edificio_independiente') {
            list = list.filter(c => c.tipo_entidad === 'edificio_independiente' && (!c.torres || c.torres.length === 0));
        }
    }
    return list;
});

const customGettersCondo = {
    administrador: (c) => c.administradores?.[0]?.name || c.users?.[0]?.name || '',
    banco: (c) => c.cuentas_bancarias?.[0]?.banco || '',
    fondo_reserva: (c) => Number(c.fondo_reserva_porcentaje || 0),
    tasa_bcv: (c) => Number(c.tasa_cambio || 0),
    activo: (c) => (c.activo ? 'Activo' : 'Inactivo'),
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
} = usePagination(rootCondominios, {
    perPage: 10,
    initialSortBy: 'nombre',
    customGetters: customGettersCondo,
    customFilter: (item, term) => {
        const matchParent = 
            (item.nombre && item.nombre.toLowerCase().includes(term)) ||
            (item.rif && item.rif.toLowerCase().includes(term)) ||
            (item.direccion && item.direccion.toLowerCase().includes(term)) ||
            (item.administradores && item.administradores.some(a => a.name.toLowerCase().includes(term))) ||
            (item.users && item.users.some(a => a.name.toLowerCase().includes(term)));

        const matchChild = item.torres && item.torres.some(t => 
            (t.nombre && t.nombre.toLowerCase().includes(term)) ||
            (t.torre_bloque && t.torre_bloque.toLowerCase().includes(term)) ||
            (t.rif && t.rif.toLowerCase().includes(term)) ||
            (t.administradores && t.administradores.some(a => a.name.toLowerCase().includes(term)))
        );

        if (matchChild && !expandedIds.value.includes(item.id)) {
            expandedIds.value.push(item.id);
        }

        return Boolean(matchParent || matchChild);
    }
});

const conjuntosList = computed(() => {
    return condominiosList.value.filter(c => c.tipo_entidad === 'conjunto_residencial' || !c.parent_id);
});

const getEmptyForm = () => ({
    id: null,
    parent_id: null,
    tipo_entidad: 'conjunto_residencial',
    torre_bloque: '',
    nombre: '',
    rif: '',
    direccion: '',
    telefono: '',
    email: '',
    numero_apartamentos: null,
    cuota_mantenimiento_base: null,
    tasa_cambio: Number(authStore.tasaCambioCentral || authStore.tasaCambio || 36.50),
    mantener_tasa_emision_5_dias: true,
    dias_congelar_tasa: 5,
    banco_nombre: '',
    cuenta_bancaria_bs: '',
    cuenta_bancaria_usd: '',
    pago_movil_banco: '',
    pago_movil_cedula: '',
    pago_movil_telefono: '',
    fondo_reserva_porcentaje: 10,
    fondo_reserva_acumulado: 0,
    notas_recibo: '',
    admin_user_id: adminsList.value[0]?.id || null,
    admin_name: '',
    admin_email: '',
    admin_cedula: '',
    admin_password: '',
    torres_hijas: [
        { torre_bloque: 'Torre A', numero_apartamentos: null, cuota_mantenimiento_base: null, admin_user_id: null },
    ],
});

const form = ref(getEmptyForm());

watch(() => form.value.tipo_entidad, (val) => {
    if (val === 'conjunto_residencial' && !isEditing.value) {
        if (!form.value.torres_hijas || form.value.torres_hijas.length === 0) {
            form.value.torres_hijas = [
                { torre_bloque: 'Torre A', numero_apartamentos: null, cuota_mantenimiento_base: form.value.cuota_mantenimiento_base || null, admin_user_id: adminsList.value[0]?.id || null },
            ];
        }
    }
});

const agregarFilaTorre = () => {
    const nextIdx = (form.value.torres_hijas?.length || 0) + 1;
    const letras = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M'];
    const letra = letras[nextIdx - 1] || nextIdx;
    form.value.torres_hijas.push({
        torre_bloque: `Torre ${letra}`,
        numero_apartamentos: null,
        cuota_mantenimiento_base: form.value.cuota_mantenimiento_base || null,
        admin_user_id: form.value.admin_user_id || null,
    });
};

const removerFilaTorre = (idx) => {
    form.value.torres_hijas.splice(idx, 1);
};

const onParentConjuntoSelected = (parentId) => {
    const parent = condominiosList.value.find(c => c.id === parentId);
    if (parent) {
        if (!form.value.direccion) form.value.direccion = parent.direccion;
        if (!form.value.telefono) form.value.telefono = parent.telefono;
        if (!form.value.email) form.value.email = parent.email;
        if (!form.value.banco_nombre) form.value.banco_nombre = parent.banco_nombre;
        if (!form.value.cuenta_bancaria_bs) form.value.cuenta_bancaria_bs = parent.cuenta_bancaria_bs;
        if (!form.value.cuenta_bancaria_usd) form.value.cuenta_bancaria_usd = parent.cuenta_bancaria_usd;
        if (!form.value.rif || form.value.rif === 'J-300576531') {
            form.value.rif = `${parent.rif}-${(parent.torres?.length || 0) + 1}`;
        }
        form.value.nombre = `${parent.nombre} - ${form.value.torre_bloque || 'TORRE A'}`;
    }
};

const fetchCondominios = async () => {
    try {
        const { data } = await axios.get('/condominios');
        if (data.success) {
            condominiosList.value = data.data.data || data.data;
        }
    } catch (e) {
        authStore.notify('Error al cargar condominios', 'error');
    }
};

const fetchAdmins = async () => {
    try {
        const { data } = await axios.get('/users', {
            params: { solo_admins: 1, all: true }
        });
        if (data.success) {
            const list = data.data.data || data.data || [];
            adminsList.value = list.map(u => ({
                id: u.id,
                name: u.name,
                email: u.email,
                rol: u.rol || u.role?.name || 'Administrador',
                label: `${u.name} (${u.email}) • [${u.rol || u.role?.name || 'Administrador'}]`,
            }));
        }
    } catch (e) {}
};

const openAddTorreToConjuntoDialog = (conjunto) => {
    isEditing.value = false;
    isAddingTorreToSpecificConjunto.value = true;
    parentConjuntoLock.value = conjunto;
    adminMode.value = adminsList.value.length ? 'existente' : 'nuevo';
    const numTorres = (conjunto.torres?.length || 0) + 1;
    const letras = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M'];
    const letra = letras[numTorres - 1] || numTorres;

    form.value = {
        id: null,
        parent_id: conjunto.id,
        tipo_entidad: 'torre_edificio',
        torre_bloque: `Torre ${letra}`,
        nombre: `${conjunto.nombre} - Torre ${letra}`,
        rif: `${conjunto.rif}-${numTorres}`,
        direccion: conjunto.direccion || '',
        telefono: conjunto.telefono || '',
        email: conjunto.email || '',
        numero_apartamentos: null,
        cuota_mantenimiento_base: conjunto.cuota_mantenimiento_base || null,
        tasa_cambio: Number(conjunto.tasa_cambio || authStore.tasaCambioCentral || authStore.tasaCambio || 36.50),
        mantener_tasa_emision_5_dias: conjunto.mantener_tasa_emision_5_dias !== undefined ? Boolean(conjunto.mantener_tasa_emision_5_dias) : true,
        dias_congelar_tasa: conjunto.dias_congelar_tasa || 5,
        banco_nombre: conjunto.banco_nombre || '',
        cuenta_bancaria_bs: conjunto.cuenta_bancaria_bs || '',
        cuenta_bancaria_usd: conjunto.cuenta_bancaria_usd || '',
        fondo_reserva_porcentaje: conjunto.fondo_reserva_porcentaje ?? 10,
        fondo_reserva_acumulado: 0,
        notas_recibo: conjunto.notas_recibo || '',
        admin_user_id: conjunto.administradores?.[0]?.id || adminsList.value[0]?.id || null,
        admin_name: '',
        admin_email: '',
        admin_cedula: '',
        admin_password: '',
        torres_hijas: [],
    };
    dialog.value = true;
};

watch(() => form.value.torre_bloque, (newVal) => {
    if (isAddingTorreToSpecificConjunto.value && parentConjuntoLock.value && newVal) {
        form.value.nombre = `${parentConjuntoLock.value.nombre} - ${newVal}`;
    }
});

const openCreateDialog = () => {
    isEditing.value = false;
    isAddingTorreToSpecificConjunto.value = false;
    parentConjuntoLock.value = null;
    adminMode.value = adminsList.value.length ? 'existente' : 'nuevo';
    form.value = getEmptyForm();
    dialog.value = true;
};

const openCreateConjuntoDialog = openCreateDialog;

const editCondominio = (c) => {
    isEditing.value = true;
    isAddingTorreToSpecificConjunto.value = false;
    parentConjuntoLock.value = c.parent || null;
    adminMode.value = 'existente';
    
    // Cargar torres hijas si este es un conjunto residencial
    const loadedTorres = (c.torres && c.torres.length) 
        ? c.torres.map(t => ({
            id: t.id,
            torre_bloque: t.torre_bloque || t.nombre,
            nombre: t.nombre,
            numero_apartamentos: t.numero_apartamentos,
            cuota_mantenimiento_base: t.cuota_mantenimiento_base,
            admin_user_id: t.administradores?.[0]?.id || t.users?.[0]?.id || null,
        }))
        : [];

    form.value = {
        ...c,
        tipo_entidad: c.tipo_entidad || (c.parent_id ? 'torre_edificio' : 'edificio_independiente'),
        parent_id: c.parent_id || null,
        torre_bloque: c.torre_bloque || '',
        tasa_cambio: Number(c.tasa_cambio || authStore.tasaCambioCentral || authStore.tasaCambio || 36.50),
        mantener_tasa_emision_5_dias: c.mantener_tasa_emision_5_dias !== undefined ? Boolean(c.mantener_tasa_emision_5_dias) : true,
        dias_congelar_tasa: c.dias_congelar_tasa || 5,
        banco_nombre: c.banco_nombre || 'BNC',
        cuenta_bancaria_bs: c.cuenta_bancaria_bs || '0191 0514 8221 0001 8351',
        cuenta_bancaria_usd: c.cuenta_bancaria_usd || '0191 0012 0223 1202 5152',
        pago_movil_banco: c.pago_movil_banco || 'BNC',
        pago_movil_cedula: c.pago_movil_cedula || 'J-300576531',
        pago_movil_telefono: c.pago_movil_telefono || '0414-1234567',
        fondo_reserva_porcentaje: c.fondo_reserva_porcentaje || 10,
        fondo_reserva_acumulado: c.fondo_reserva_acumulado || 2997.06,
        admin_user_id: c.administradores?.[0]?.id || c.users?.[0]?.id || null,
        torres_hijas: loadedTorres,
    };
    dialog.value = true;
};

const openAssignDialog = (condo) => {
    targetCondo.value = condo;
    assignForm.value = {
        user_id: condo.administradores?.[0]?.id || condo.users?.[0]?.id || adminsList.value[0]?.id || null,
        reemplazar_anteriores: false,
    };
    assignDialog.value = true;
};

const submitAssignment = async () => {
    if (!assignForm.value.user_id) {
        authStore.notify('Seleccione un administrador', 'warning');
        return;
    }
    saving.value = true;
    try {
        await axios.put(`/condominios/${targetCondo.value.id}/asignar-administrador`, assignForm.value);
        authStore.notify('Administración asignada exitosamente');
        assignDialog.value = false;
        fetchCondominios();
    } catch (e) {
        authStore.notify('Error al asignar administrador', 'error');
    } finally {
        saving.value = false;
    }
};

const saveCondominio = async () => {
    saving.value = true;
    try {
        if (isEditing.value) {
            await axios.put(`/condominios/${form.value.id}`, form.value);
            authStore.notify('Condominio y datos actualizados exitosamente');
        } else {
            await axios.post('/condominios', form.value);
            authStore.notify('Condominio / Torre(s) registrado exitosamente');
            if (form.value.parent_id && !expandedIds.value.includes(form.value.parent_id)) {
                expandedIds.value.push(form.value.parent_id);
            }
        }
        dialog.value = false;
        fetchCondominios();
    } catch (e) {
        authStore.notify(e.response?.data?.message || 'Error al procesar el condominio', 'error');
    } finally {
        saving.value = false;
    }
};

const deleteCondominio = async (id) => {
    if (!confirm('¿Está seguro de eliminar lógicamente este condominio/torre?')) return;
    try {
        await axios.delete(`/condominios/${id}`);
        authStore.notify('Condominio eliminado lógicamente');
        fetchCondominios();
    } catch (e) {
        authStore.notify('Error al eliminar', 'error');
    }
};

const openRecibosCondoDialog = (condo) => {
    selectedCondoForRecibos.value = condo;
    recibosDialog.value = true;
    fetchRecibosForCondo(condo.id);
};

const fetchRecibosForCondo = async (condoId) => {
    loadingRecibos.value = true;
    try {
        const { data } = await axios.get('/invoices/periodos-resumen');
        if (data.success) {
            condoPeriodosList.value = data.data.filter(p => Number(p.condominio_id) === Number(condoId));
        }
    } catch (e) {
        authStore.notify('Error al consultar recibos de esta torre', 'error');
    } finally {
        loadingRecibos.value = false;
    }
};

const ejecutarReaperturaRecibo = async (periodoObj) => {
    if (periodoObj.veces_reabierto >= 2) {
        await Swal.fire({
            icon: 'error',
            title: 'Límite Máximo Alcanzado',
            text: 'Este período ya ha alcanzado el límite máximo de 2 reaperturas autorizadas y no puede volver a modificarse.',
            confirmButtonColor: '#0f172a',
            customClass: { popup: 'rounded-2xl' },
        });
        return;
    }

    const { value: motivo } = await Swal.fire({
        title: '<span class="text-slate-900 font-bold">Reabrir Recibo Mensual</span>',
        html: `
            <div class="text-left text-sm text-slate-600 mt-2">
                <p class="mb-2">Estás a punto de devolver a <strong>Borrador / Editable</strong> el recibo del período:</p>
                <div class="p-2.5 bg-slate-100 rounded-xl text-center font-bold text-base text-slate-800 mb-2">
                    📅 ${periodoObj.periodo}
                </div>
                <div class="p-2.5 bg-amber-50 border-l-4 border-amber-500 rounded-lg text-amber-900 text-xs mb-3">
                    ⚠️ <strong>Intento ${Number(periodoObj.veces_reabierto || 0) + 1} de 2 permitidos</strong>.<br>
                    Al reabrirlo, el administrador de <strong>${selectedCondoForRecibos.value?.nombre}</strong> podrá corregir los conceptos y montos para luego volver a certificarlo.
                </div>
            </div>
        `,
        input: 'textarea',
        inputPlaceholder: 'Ingresa el motivo o justificación de la reapertura (Requerido)...',
        inputAttributes: {
            'aria-label': 'Motivo de reapertura',
        },
        showCancelButton: true,
        confirmButtonColor: '#f59e0b',
        cancelButtonColor: '#64748b',
        confirmButtonText: '🔓 Desbloquear y Devolver a Borrador',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        inputValidator: (value) => {
            if (!value || !value.trim()) {
                return '¡Debes ingresar un motivo o justificación de auditoría!';
            }
        },
        customClass: {
            popup: 'rounded-3xl shadow-2xl p-6',
            confirmButton: 'px-5 py-2.5 rounded-xl font-bold shadow-md',
            cancelButton: 'px-5 py-2.5 rounded-xl font-medium',
        },
    });

    if (!motivo) return;

    try {
        const { data } = await axios.post('/invoices/reabrir-periodo', {
            condominio_id: selectedCondoForRecibos.value.id,
            periodo: periodoObj.periodo,
            motivo: motivo,
        });

        fetchRecibosForCondo(selectedCondoForRecibos.value.id);

        await Swal.fire({
            icon: 'success',
            title: '¡Recibo Devuelto a Borrador!',
            html: `
                <div class="text-sm text-slate-600">
                    ${data.message}<br>
                    <div class="mt-2 p-2 bg-amber-50 rounded-lg text-amber-900 font-bold">
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
            title: 'Error al reabrir',
            text: e.response?.data?.message || 'No se pudo reabrir el recibo.',
            confirmButtonColor: '#0f172a',
            customClass: { popup: 'rounded-2xl' },
        });
    }
};

onMounted(() => {
    fetchCondominios();
    fetchAdmins();
    authStore.fetchTasaCentral();
});
</script>

<style scoped>
.bg-purple-50 {
    background-color: #FAF5FF !important;
}
.border-purple-100 {
    border-color: #F3E8FF !important;
}
.border-purple-200 {
    border-color: #E9D5FF !important;
}
.text-purple-900 {
    color: #581C87 !important;
}
.text-purple-700 {
    color: #7E22CE !important;
}
</style>
