<template>
  <div :class="['min-h-screen transition-colors', isDark ? 'bg-slate-950 text-slate-100' : 'bg-slate-50 text-slate-900']">
    <!-- Sidebar / Navigation Drawer -->
    <v-navigation-drawer
      v-model="drawer"
      app
      :color="isDark ? '#0F172A' : '#FFFFFF'"
      elevation="1"
      :width="280"
      :temporary="isMobile"
      :class="['border-r transition-colors', isDark ? 'border-slate-800' : 'border-slate-200']"
    >
      <div class="pa-4 d-flex align-center justify-space-between border-b border-slate-100">
        <div class="d-flex align-center overflow-hidden">
          <v-avatar color="primary" size="38" class="mr-3 text-white font-weight-bold flex-shrink-0">
            <v-icon icon="mdi-office-building-cog" color="white" size="22" />
          </v-avatar>
          <div class="overflow-hidden">
            <div :class="['text-subtitle-1 font-weight-bold', isDark ? 'text-white' : 'text-slate-900']" style="line-height: 1.2;">
              AZPRO
            </div>
            <div :class="['text-caption text-truncate', isDark ? 'text-slate-300' : 'text-slate-500']" :title="authStore.isMaster ? 'Plataforma Global SaaS' : condoDisplayText">
              {{ authStore.isMaster ? 'Plataforma Global' : condoDisplayText }}
            </div>
          </div>
        </div>
        <!-- Close button on mobile -->
        <v-btn
          v-if="isMobile"
          icon="mdi-close"
          variant="text"
          size="small"
          :color="isDark ? 'white' : 'slate-500'"
          @click="drawer = false"
        />
      </div>

      <!-- User Profile Badge -->
      <div :class="['pa-3 mx-3 my-2 rounded-lg transition-colors', isDark ? 'bg-slate-800/80 border border-slate-700' : 'bg-slate-100']">
        <div class="d-flex align-center">
          <v-avatar size="32" color="secondary" class="mr-2 text-white flex-shrink-0">
            {{ authStore.user?.name?.charAt(0) || 'U' }}
          </v-avatar>
          <div class="overflow-hidden" style="max-width: 170px;">
            <div :class="['text-body-2 font-weight-bold text-truncate', isDark ? 'text-white' : 'text-slate-800']">
              {{ authStore.user?.name }}
            </div>
            <v-chip size="x-small" color="primary" variant="flat" class="font-weight-medium text-uppercase">
              {{ authStore.user?.rol || 'Usuario' }}
            </v-chip>
          </div>
        </div>
        <v-btn
          v-if="!authStore.isMaster && authStore.assignedCondos.length > 1"
          block
          size="x-small"
          variant="tonal"
          color="teal"
          prepend-icon="mdi-swap-horizontal"
          class="mt-2 font-weight-bold"
          to="/seleccionar-condominio"
          @click="onNavClick"
        >
          Cambiar Torre / Edificio
        </v-btn>
      </div>

      <v-divider class="my-1" />

      <!-- Navigation List -->
      <v-list density="comfortable" nav class="px-2">
        <!-- Master Admin Items -->
        <template v-if="authStore.isMaster">
          <v-list-subheader class="font-weight-bold text-uppercase text-caption text-slate-400">
            Super Admin (Plataforma)
          </v-list-subheader>
          <v-list-item prepend-icon="mdi-view-dashboard" title="Dashboard Global" to="/dashboard/master" rounded="lg" color="primary" @click="onNavClick" />
          <v-list-item prepend-icon="mdi-office-building" title="Condominios y Torres" to="/condominios" rounded="lg" color="primary" @click="onNavClick" />
          <v-list-item prepend-icon="mdi-cash-register" title="Cobranza SaaS (Plataforma)" to="/contabilidad/super-admin" rounded="lg" color="primary" @click="onNavClick" />
          <v-list-item prepend-icon="mdi-account-multiple-outline" title="Gestión de Usuarios" to="/usuarios" rounded="lg" color="primary" @click="onNavClick" />
          <v-list-item prepend-icon="mdi-package-variant-closed" title="Planes y Suscripción" to="/suscripciones/planes" rounded="lg" color="primary" @click="onNavClick" />
          <v-list-item prepend-icon="mdi-tune" title="Campos Select" to="/configuracion/select-options" rounded="lg" color="primary" @click="onNavClick" />
          <v-list-item prepend-icon="mdi-shield-account" title="Roles y Permisos" to="/seguridad/roles" rounded="lg" color="primary" @click="onNavClick" />
          <v-list-item prepend-icon="mdi-menu" title="Menús Dinámicos" to="/seguridad/menus" rounded="lg" color="primary" @click="onNavClick" />
          <v-list-item prepend-icon="mdi-bullhorn-outline" title="Landing Page PWA" to="/configuracion/landing" rounded="lg" color="primary" @click="onNavClick" />
          <v-list-item prepend-icon="mdi-history" title="Auditoría Global" to="/auditoria" rounded="lg" color="primary" @click="onNavClick" />
          <v-list-item prepend-icon="mdi-vote" title="Asambleas y Votaciones" to="/asambleas" rounded="lg" color="primary" @click="onNavClick" />
        </template>

        <!-- Admin Condominio / Supervisor Items -->
        <template v-if="authStore.isAdmin || authStore.isSupervisor || authStore.isAnalista">
          <v-list-subheader class="font-weight-bold text-uppercase text-caption text-slate-400">
            Gestión Condominio
          </v-list-subheader>
          <v-list-item prepend-icon="mdi-view-dashboard" title="Dashboard" to="/dashboard/admin" rounded="lg" color="primary" @click="onNavClick" />
          <v-list-item prepend-icon="mdi-home-city" title="Apartamentos" to="/apartamentos" rounded="lg" color="primary" @click="onNavClick" />
          <v-list-item prepend-icon="mdi-account-group" title="Propietarios" to="/usuarios" rounded="lg" color="primary" @click="onNavClick" />
          <v-list-item prepend-icon="mdi-calculator" title="Contabilidad y Gastos" to="/contabilidad/admin" rounded="lg" color="primary" @click="onNavClick" />
          <v-list-item prepend-icon="mdi-receipt-text" title="Pagos y Recibos" to="/pagos" rounded="lg" color="primary" @click="onNavClick" />
          <v-list-item prepend-icon="mdi-vote" title="Asambleas y Votaciones" to="/asambleas" rounded="lg" color="primary" @click="onNavClick" />
          <v-list-item prepend-icon="mdi-bank-cog" title="Configuración del Edificio" to="/condominios" rounded="lg" color="primary" @click="onNavClick" />
          <v-list-item prepend-icon="mdi-calendar-multiselect" title="Áreas Comunes" to="/reservas" rounded="lg" color="primary" @click="onNavClick" />
          <v-list-item prepend-icon="mdi-badge-account" title="Control Visitantes" to="/visitantes" rounded="lg" color="primary" @click="onNavClick" />
          <v-list-item prepend-icon="mdi-alert-circle" title="Incidencias" to="/incidencias" rounded="lg" color="primary" @click="onNavClick" />
          <v-list-item prepend-icon="mdi-bullhorn" title="Comunicados" to="/comunicados" rounded="lg" color="primary" @click="onNavClick" />
          <v-list-item prepend-icon="mdi-whatsapp" title="Notificaciones" to="/notificaciones" rounded="lg" color="primary" @click="onNavClick" />
        </template>

        <!-- Owner Items -->
        <template v-if="authStore.isPropietario">
          <v-list-subheader class="font-weight-bold text-uppercase text-caption text-slate-400">
            Residente
          </v-list-subheader>
          <v-list-item prepend-icon="mdi-view-dashboard" title="Mi Resumen" to="/dashboard/owner" rounded="lg" color="primary" @click="onNavClick" />
          <v-list-item prepend-icon="mdi-credit-card-outline" title="Mis Facturas y Pagos" to="/pagos" rounded="lg" color="primary" @click="onNavClick" />
          <v-list-item prepend-icon="mdi-vote-outline" title="Votaciones y Asambleas" to="/asambleas" rounded="lg" color="primary" @click="onNavClick" />
          <v-list-item prepend-icon="mdi-calendar-check" title="Reservar Áreas" to="/reservas" rounded="lg" color="primary" @click="onNavClick" />
          <v-list-item prepend-icon="mdi-alert-circle-outline" title="Reportar Incidencia" to="/incidencias" rounded="lg" color="primary" @click="onNavClick" />
          <v-list-item prepend-icon="mdi-bullhorn-outline" title="Cartelera" to="/comunicados" rounded="lg" color="primary" @click="onNavClick" />
        </template>
      </v-list>

      <template #append>
        <div class="pa-3 border-t border-slate-100">
          <v-btn
            block
            color="error"
            variant="tonal"
            prepend-icon="mdi-logout"
            @click="handleLogout"
          >
            Cerrar Sesión
          </v-btn>
        </div>
      </template>
    </v-navigation-drawer>

    <!-- Header / App Bar -->
    <v-app-bar
      app
      :color="isDark ? '#0F172A' : '#FFFFFF'"
      elevation="0"
      :class="['border-b px-2 glass-nav transition-colors', isDark ? 'border-slate-800' : 'border-slate-200']"
      height="60"
    >
      <v-app-bar-nav-icon :color="isDark ? 'white' : 'slate-700'" @click="drawer = !drawer" />

      <!-- Brand / Active Condo -->
      <div class="d-flex align-center overflow-hidden mr-2">
        <div :class="['d-none d-sm-block font-weight-bold text-subtitle-1 text-truncate', isDark ? 'text-white' : 'text-slate-800']">
          AZPRO
        </div>
        <v-chip
          v-if="authStore.isMaster"
          size="small"
          color="indigo-darken-3"
          variant="tonal"
          class="font-weight-bold ml-sm-2 text-truncate"
          prepend-icon="mdi-shield-crown-outline"
        >
          <span class="d-none d-sm-inline">Vista Global SaaS</span>
          <span class="d-inline d-sm-none">Global</span>
        </v-chip>
        <v-chip
          v-else-if="authStore.activeCondominio"
          size="small"
          :color="authStore.activeCondominio.parent ? 'teal' : 'primary'"
          variant="flat"
          class="font-weight-bold ml-sm-2 text-truncate"
          style="max-width: 180px;"
          :title="condoDisplayText"
        >
          <v-icon start size="14" :icon="authStore.activeCondominio.parent ? 'mdi-office-building-marker' : 'mdi-office-building'" />
          <span class="text-truncate">{{ condoDisplayText }}</span>
        </v-chip>
      </div>

      <v-spacer />

      <!-- Desktop Header Actions (>= md) -->
      <div class="d-none d-md-flex align-center gap-2">
        <!-- Switch Tower Button -->
        <v-btn
          v-if="!authStore.isMaster && authStore.assignedCondos.length > 1"
          size="small"
          variant="tonal"
          color="indigo"
          prepend-icon="mdi-swap-horizontal"
          class="font-weight-bold text-capitalize"
          to="/seleccionar-condominio"
        >
          Cambiar Torre
        </v-btn>
        
        <!-- BCV Rate Button (Only Super Admin can edit, others view as readonly chip) -->
        <v-btn
          v-if="authStore.isMaster"
          size="small"
          class="font-weight-bold text-capitalize"
          color="teal-darken-2"
          variant="tonal"
          rounded="pill"
          prepend-icon="mdi-bank-outline"
          title="Tasa Oficial BCV Central del Sistema (Clic para configurar)"
          @click="openTasaCentralModal"
        >
          BCV: Bs. {{ Number(authStore.tasaCambioCentral || 36.50).toFixed(2) }}
          <v-icon end size="13" icon="mdi-pencil" />
        </v-btn>
        <v-chip
          v-else
          size="small"
          class="font-weight-bold text-capitalize"
          color="teal-darken-2"
          variant="tonal"
          prepend-icon="mdi-bank-outline"
          title="Tasa Oficial BCV activa en el sistema"
        >
          BCV: Bs. {{ Number(authStore.tasaCambioCentral || authStore.tasaCambio || 36.50).toFixed(2) }}
        </v-chip>

        <!-- Guided Tour Action Button (Fase 2.3) -->
        <v-btn
          v-if="!authStore.isPropietario"
          size="small"
          variant="tonal"
          color="indigo-darken-1"
          prepend-icon="mdi-compass-outline"
          class="font-weight-bold text-capitalize"
          rounded="pill"
          title="Tour Interactivo Guiado de la Plataforma"
          @click="openTourDialog"
        >
          Tour Guiado
        </v-btn>

        <!-- Currency Toggle Button -->
        <v-btn
          size="small"
          class="font-weight-bold text-capitalize"
          :color="authStore.currency === 'USD' ? 'success' : 'primary'"
          variant="tonal"
          rounded="pill"
          prepend-icon="mdi-currency-usd"
          @click="authStore.toggleCurrency"
        >
          {{ authStore.currency }}
        </v-btn>

        <!-- Dark / Light Mode Switcher -->
        <v-tooltip :text="isDark ? 'Cambiar a Modo Claro' : 'Cambiar a Modo Oscuro'" location="bottom">
          <template #activator="{ props }">
            <v-btn
              v-bind="props"
              :icon="isDark ? 'mdi-weather-sunny' : 'mdi-weather-night'"
              variant="tonal"
              :color="isDark ? 'amber-accent-2' : 'indigo-darken-2'"
              size="small"
              class="rounded-lg font-weight-bold"
              @click="toggleTheme"
            />
          </template>
        </v-tooltip>

        <!-- Logout Button -->
        <v-btn icon="mdi-logout" color="slate-600" variant="text" size="small" @click="handleLogout" />
      </div>

      <!-- Mobile Top Bar Quick Actions (< md) -->
      <div class="d-flex d-md-none align-center gap-1">
        <!-- Currency Toggle Mobile -->
        <v-btn
          size="small"
          variant="tonal"
          :color="authStore.currency === 'USD' ? 'success' : 'primary'"
          class="px-2 font-weight-bold text-caption"
          rounded="pill"
          @click="authStore.toggleCurrency"
        >
          {{ authStore.currency }}
        </v-btn>

        <!-- BCV Rate Quick Action in Mobile -->
        <v-btn
          v-if="authStore.isMaster"
          icon="mdi-bank-outline"
          size="small"
          variant="tonal"
          color="teal-darken-2"
          title="Configurar Tasa BCV"
          @click="openTasaCentralModal"
        />
        <v-chip
          v-else
          size="x-small"
          variant="tonal"
          color="teal-darken-2"
          class="font-weight-bold"
          title="Tasa Oficial BCV"
        >
          Bs. {{ Number(authStore.tasaCambioCentral || authStore.tasaCambio || 36.50).toFixed(1) }}
        </v-chip>

        <!-- Mobile Menu Dropdown for more options -->
        <v-menu location="bottom end">
          <template #activator="{ props }">
            <v-btn icon="mdi-dots-vertical" variant="text" size="small" color="slate-700" v-bind="props" />
          </template>
          <v-list density="compact" class="rounded-lg elevation-4">
            <v-list-item
              v-if="!authStore.isMaster && authStore.assignedCondos.length > 1"
              prepend-icon="mdi-swap-horizontal"
              title="Cambiar Torre"
              to="/seleccionar-condominio"
            />
            <v-list-item
              v-if="authStore.isMaster"
              prepend-icon="mdi-bank-outline"
              :title="`Tasa BCV: Bs. ${Number(authStore.tasaCambioCentral || 36.50).toFixed(2)}`"
              subtitle="Configurar Tasa Central"
              @click="openTasaCentralModal"
            />
            <v-list-item
              v-else
              prepend-icon="mdi-bank-outline"
              :title="`Tasa BCV: Bs. ${Number(authStore.tasaCambioCentral || authStore.tasaCambio || 36.50).toFixed(2)}`"
              subtitle="Tasa Oficial Activa"
            />
            <v-divider class="my-1" />
            <v-list-item
              prepend-icon="mdi-logout"
              title="Cerrar Sesión"
              color="error"
              @click="handleLogout"
            />
          </v-list>
        </v-menu>
      </div>
    </v-app-bar>

    <!-- Main Content View with Mobile Safe Area -->
    <v-main :class="['transition-colors', isDark ? 'bg-slate-950 text-slate-100' : 'bg-slate-50 text-slate-900']">
      <v-container fluid class="pa-3 pa-sm-5 pa-md-6" :class="{ 'pb-24': isMobile }">
        
        <!-- 7-Day Advance Subscription Alert Banner -->
        <v-alert
          v-if="subAlert && subAlert.necesita_alerta_7dias"
          type="warning"
          variant="tonal"
          border="start"
          class="mb-4 rounded-xl shadow-sm"
          closable
        >
          <div class="d-flex flex-column flex-sm-row align-start align-sm-center justify-space-between gap-3">
            <div>
              <strong>⚠️ Recordatorio de Pago SaaS:</strong> La mensualidad del plan <strong>{{ subAlert.plan_nombre }}</strong> para {{ subAlert.condominio_nombre }} vence el <strong>{{ subAlert.fecha_vencimiento }}</strong> (Faltan {{ subAlert.dias_restantes }} días).
            </div>
            <v-btn color="warning" size="small" class="font-weight-bold text-capitalize" @click="saasOverdueModal = true">
              Notificar Pago (${{ Number(subAlert.monto_usd).toFixed(2) }})
            </v-btn>
          </div>
        </v-alert>

        <slot />
      </v-container>
    </v-main>

    <!-- Mobile Bottom Navigation Bar (App Bar Inferior para Móviles) -->
    <v-bottom-navigation
      v-if="isMobile"
      app
      grow
      elevation="4"
      color="primary"
      active-color="primary"
      class="border-t border-slate-200 glass-nav pb-safe"
      style="position: fixed; bottom: 0; left: 0; right: 0; z-index: 100;"
    >
      <!-- Propietario / Residente Bottom Nav -->
      <template v-if="authStore.isPropietario">
        <v-btn to="/dashboard/owner" value="inicio" class="text-caption font-weight-medium">
          <v-icon size="20">mdi-home-outline</v-icon>
          <span>Inicio</span>
        </v-btn>
        <v-btn to="/pagos" value="pagos" class="text-caption font-weight-medium">
          <v-icon size="20">mdi-credit-card-outline</v-icon>
          <span>Pagos</span>
        </v-btn>
        <v-btn to="/reservas" value="reservas" class="text-caption font-weight-medium">
          <v-icon size="20">mdi-calendar-check-outline</v-icon>
          <span>Reservas</span>
        </v-btn>
        <v-btn to="/comunicados" value="cartelera" class="text-caption font-weight-medium">
          <v-icon size="20">mdi-bullhorn-outline</v-icon>
          <span>Cartelera</span>
        </v-btn>
        <v-btn value="menu" class="text-caption font-weight-medium" @click="drawer = true">
          <v-icon size="20">mdi-menu</v-icon>
          <span>Más</span>
        </v-btn>
      </template>

      <!-- Admin Condominio / Supervisor Bottom Nav -->
      <template v-else-if="authStore.isAdmin || authStore.isSupervisor || authStore.isAnalista">
        <v-btn to="/dashboard/admin" value="inicio" class="text-caption font-weight-medium">
          <v-icon size="20">mdi-view-dashboard-outline</v-icon>
          <span>Inicio</span>
        </v-btn>
        <v-btn to="/apartamentos" value="apartamentos" class="text-caption font-weight-medium">
          <v-icon size="20">mdi-home-city-outline</v-icon>
          <span>Aptos</span>
        </v-btn>
        <v-btn to="/contabilidad/admin" value="contabilidad" class="text-caption font-weight-medium">
          <v-icon size="20">mdi-calculator</v-icon>
          <span>Finanzas</span>
        </v-btn>
        <v-btn to="/pagos" value="pagos" class="text-caption font-weight-medium">
          <v-icon size="20">mdi-receipt-text-outline</v-icon>
          <span>Pagos</span>
        </v-btn>
        <v-btn value="menu" class="text-caption font-weight-medium" @click="drawer = true">
          <v-icon size="20">mdi-menu</v-icon>
          <span>Más</span>
        </v-btn>
      </template>

      <!-- Super Admin Bottom Nav -->
      <template v-else-if="authStore.isMaster">
        <v-btn to="/dashboard/master" value="global" class="text-caption font-weight-medium">
          <v-icon size="20">mdi-earth</v-icon>
          <span>Global</span>
        </v-btn>
        <v-btn to="/condominios" value="condominios" class="text-caption font-weight-medium">
          <v-icon size="20">mdi-office-building</v-icon>
          <span>Condos</span>
        </v-btn>
        <v-btn to="/contabilidad/super-admin" value="cobranza" class="text-caption font-weight-medium">
          <v-icon size="20">mdi-cash-register</v-icon>
          <span>Cobranza</span>
        </v-btn>
        <v-btn to="/usuarios" value="usuarios" class="text-caption font-weight-medium">
          <v-icon size="20">mdi-account-multiple-outline</v-icon>
          <span>Usuarios</span>
        </v-btn>
        <v-btn value="menu" class="text-caption font-weight-medium" @click="drawer = true">
          <v-icon size="20">mdi-menu</v-icon>
          <span>Más</span>
        </v-btn>
      </template>
    </v-bottom-navigation>

    <!-- Dialog for Central Exchange Rate (Tasa BCV Central) -->
    <v-dialog v-model="tasaDialog" max-width="520" :fullscreen="isMobile">
      <v-card class="pa-4 rounded-xl">
        <v-card-title class="font-weight-bold text-slate-900 d-flex align-center justify-space-between">
          <div class="d-flex align-center">
            <v-avatar color="teal-lighten-5" size="36" class="mr-3">
              <v-icon icon="mdi-bank-outline" color="teal-darken-2" />
            </v-avatar>
            <div>
              <div class="text-subtitle-1 font-weight-bold">Tasa Oficial BCV Central</div>
              <div class="text-caption text-slate-500 font-weight-regular">Configuración de tasa oficial</div>
            </div>
          </div>
          <v-btn v-if="isMobile" icon="mdi-close" variant="text" size="small" @click="tasaDialog = false" />
        </v-card-title>
        
        <v-card-text class="pt-2">
          <!-- Live BCV Fetch Banner -->
          <div class="mb-3 pa-3 rounded-lg bg-teal-50 border border-teal-200 d-flex align-center justify-space-between flex-wrap gap-2">
            <div>
              <div class="text-caption font-weight-bold text-teal-950 d-flex align-center">
                <v-icon icon="mdi-clock-outline" size="16" color="teal-darken-2" class="mr-1" />
                Actualización Automática
              </div>
              <div class="text-caption text-teal-800" style="font-size: 11px;">
                Se actualiza automáticamente una vez al día a las 8:00 PM (Hora VE)
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
              Consultar BCV
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
              label="Sincronizar todas las torres a esta nueva tasa"
              class="text-caption"
            />
          </v-form>
        </v-card-text>

        <v-card-actions class="justify-end gap-2 pa-3">
          <v-btn variant="text" color="slate-600" @click="tasaDialog = false">Cancelar</v-btn>
          <v-btn color="primary" :loading="guardandoTasa" class="font-weight-bold" @click="guardarTasaCentral">
            Guardar Tasa Central
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Cutoff Date Overdue Subscription Modal -->
    <v-dialog v-model="saasOverdueModal" persistent max-width="540">
      <v-card :class="['pa-6 rounded-3xl border elevation-24', isDark ? 'bg-slate-900 border-rose-500/50 text-white' : 'bg-white border-rose-400']">
        <div class="text-center mb-6">
          <v-avatar color="rose-500" size="56" class="mb-3 text-white elevation-6">
            <v-icon icon="mdi-alert-decagram" size="36" />
          </v-avatar>
          <h2 class="text-h5 font-weight-black text-rose-500 mb-1">
            Mensualidad de Suscripción Pendiente
          </h2>
          <p :class="['text-caption', isDark ? 'text-slate-400' : 'text-slate-600']">
            Estimado Administrador, la cuota del plan de su condominio <strong>{{ subAlert?.condominio_nombre }}</strong> ha llegado a su fecha de corte. Por favor notifique su pago a continuación para mantener el servicio activo sin interrupciones.
          </p>
        </div>

        <div :class="['p-4 rounded-2xl border mb-6 text-center', isDark ? 'bg-rose-950/40 border-rose-900/60' : 'bg-rose-50 border-rose-200']">
          <div class="text-caption font-weight-medium text-rose-500">Monto del Plan {{ subAlert?.plan_nombre }}</div>
          <div class="text-h4 font-weight-black text-rose-500 my-1">${{ Number(subAlert?.monto_usd || 25).toFixed(2) }} USD</div>
          <div :class="['text-caption font-weight-bold', isDark ? 'text-slate-300' : 'text-slate-700']">
            Bs. {{ Number((subAlert?.monto_usd || 25) * (authStore.tasaCambioCentral || 36.50)).toFixed(2) }}
          </div>
        </div>

        <!-- Master Bank Details -->
        <div :class="['p-4 rounded-2xl border mb-6 text-caption space-y-1.5', isDark ? 'bg-slate-950 border-slate-800' : 'bg-slate-50 border-slate-200']">
          <div class="font-weight-bold text-blue-500 mb-1">💳 Datos Oficiales de Pago Plataforma AZPRO:</div>
          <div>• <strong>Banco:</strong> {{ subAlert?.datos_pago_master?.banco || 'Banesco (0134)' }}</div>
          <div>• <strong>Teléfono / Pago Móvil:</strong> {{ subAlert?.datos_pago_master?.telefono || '0412-0000000' }}</div>
          <div>• <strong>Cédula / RIF:</strong> {{ subAlert?.datos_pago_master?.cedula_rif || 'J-123456789' }}</div>
          <div>• <strong>Zelle:</strong> {{ subAlert?.datos_pago_master?.zelle || 'pagos@azpro.com' }}</div>
        </div>

        <!-- Report Payment Form -->
        <v-form @submit.prevent="submitSaasPayment">
          <v-select
            v-model="paymentForm.metodo_pago"
            label="Método de Pago"
            :items="[
              { title: 'Pago Móvil', value: 'pago_movil' },
              { title: 'Transferencia Bancaria', value: 'transferencia' },
              { title: 'Zelle', value: 'zelle' }
            ]"
            item-title="title"
            item-value="value"
            class="mb-3"
          />
          <v-text-field
            v-model="paymentForm.referencia"
            label="Número de Referencia"
            placeholder="Ej. 12345678"
            required
            class="mb-3"
          />
          <v-text-field
            v-model="paymentForm.banco"
            label="Banco Emisor"
            placeholder="Ej. Mercantil"
            required
            class="mb-4"
          />

          <v-btn
            type="submit"
            block
            size="x-large"
            color="rose-darken-1"
            class="font-weight-black text-none rounded-xl text-white shadow-xl bg-gradient-to-r from-rose-600 to-rose-700"
            :loading="submittingPayment"
          >
            Notificar Pago y Continuar
          </v-btn>
        </v-form>
      </v-card>
    </v-dialog>

    <!-- Global Notification Snackbar -->
    <v-snackbar
      v-model="authStore.snackbar.show"
      :color="authStore.snackbar.color"
      location="top right"
      timeout="4000"
    >
      {{ authStore.snackbar.text }}
      <template #actions>
        <v-btn variant="text" @click="authStore.snackbar.show = false">Cerrar</v-btn>
      </template>
    </v-snackbar>

    <!-- Modal de Tour Guiado de Bienvenida (Fase 2.3) -->
    <GuidedTourDialog ref="tourDialogRef" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../store/auth';
import { useTheme } from '../composables/useTheme';
import GuidedTourDialog from '../components/GuidedTourDialog.vue';

const drawer = ref(window.innerWidth >= 960);
const windowWidth = ref(window.innerWidth);
const isMobile = computed(() => windowWidth.value < 960);
const tourDialogRef = ref(null);

const openTourDialog = () => {
    tourDialogRef.value?.openTour();
};

const authStore = useAuthStore();
const router = useRouter();
const { isDark, toggleTheme } = useTheme();

const subAlert = ref(null);
const saasOverdueModal = ref(false);
const submittingPayment = ref(false);
const paymentForm = ref({
    metodo_pago: 'pago_movil',
    referencia: '',
    banco: '',
});

const tasaDialog = ref(false);
const nuevaTasaInput = ref(authStore.tasaCambioCentral || 36.50);
const sincronizarTodasLasTorres = ref(true);
const guardandoTasa = ref(false);
const consultandoBcv = ref(false);
const bcvInfo = ref(null);

const handleResize = () => {
    windowWidth.value = window.innerWidth;
    if (window.innerWidth < 960) {
        drawer.value = false;
    } else {
        drawer.value = true;
    }
};

const onNavClick = () => {
    if (isMobile.value) {
        drawer.value = false;
    }
};

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
    } catch (e) {
        authStore.notify(e.response?.data?.message || 'Error al actualizar tasa central', 'error');
    } finally {
        guardandoTasa.value = false;
    }
};

const condoDisplayText = computed(() => {
    const active = authStore.activeCondominio;
    if (!active) return 'Plataforma Multi-Tenant';
    if (active.parent) {
        return `${active.nombre} (${active.parent.nombre})`;
    }
    if (active.torre_bloque) {
        return `${active.nombre} - ${active.torre_bloque}`;
    }
    return active.nombre;
});

const checkSubscription = async () => {
    if (authStore.isMaster) return;
    try {
        const { data } = await axios.get('/saas/check-subscription');
        if (data.success) {
            subAlert.value = data.data;
            if (subAlert.value.necesita_popup_corte) {
                saasOverdueModal.value = true;
            }
        }
    } catch (e) {
        console.error('Error al verificar suscripción SaaS:', e);
    }
};

const submitSaasPayment = async () => {
    if (!subAlert.value) return;
    submittingPayment.value = true;
    try {
        const payload = {
            condominio_id: subAlert.value.condominio_id,
            fecha_pago: new Date().toISOString().split('T')[0],
            metodo_pago: paymentForm.value.metodo_pago,
            referencia: paymentForm.value.referencia,
            banco: paymentForm.value.banco,
            monto_usd: subAlert.value.monto_usd,
            tasa_cambio: authStore.tasaCambioCentral || 36.50,
            notas: 'Notificación de pago de mensualidad SaaS desde popup de corte'
        };

        const { data } = await axios.post('/saas/payments', payload);
        if (data.success) {
            authStore.notify('Pago notificado exitosamente. Suscripción renovada.', 'success');
            saasOverdueModal.value = false;
            checkSubscription();
        }
    } catch (e) {
        authStore.notify(e.response?.data?.message || 'Error al notificar pago', 'error');
    } finally {
        submittingPayment.value = false;
    }
};

onMounted(async () => {
    window.addEventListener('resize', handleResize);
    if (authStore.isAuthenticated && !authStore.user) {
        await authStore.fetchUser();
    }
    await authStore.fetchTasaCentral();
    await checkSubscription();

    if (!localStorage.getItem('azpro_tour_completed') && (authStore.isAdmin || authStore.isMaster)) {
        setTimeout(() => {
            tourDialogRef.value?.openTour();
        }, 1200);
    }
});

onUnmounted(() => {
    window.removeEventListener('resize', handleResize);
});

const handleLogout = async () => {
    await authStore.logout();
    router.push('/login');
};
</script>

<style scoped>
</style>

