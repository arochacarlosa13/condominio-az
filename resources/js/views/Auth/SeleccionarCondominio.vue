<template>
  <div class="min-h-screen bg-slate-900 d-flex align-center justify-center pa-4" style="min-height: 100vh;">
    <v-container style="max-width: 950px;">
      <!-- Header Banner -->
      <div class="text-center mb-8">
        <v-avatar color="primary" size="64" class="mb-4 elevation-4">
          <v-icon icon="mdi-office-building-cog" size="36" color="white" />
        </v-avatar>
        <h1 class="text-h4 font-weight-bold text-white mb-2">
          Portal de Selección de Torre y Condominio
        </h1>
        <p class="text-subtitle-1 text-slate-400">
          Hola <strong class="text-white">{{ authStore.user?.name }}</strong>, selecciona la Torre o Edificio que deseas administrar en esta sesión:
        </p>
      </div>

      <!-- Towers / Condominios Grid -->
      <v-row>
        <v-col
          v-for="condo in authStore.assignedCondos"
          :key="condo.id"
          cols="12"
          sm="6"
          md="4"
        >
          <v-card
            hover
            class="pa-5 rounded-xl transition-all h-100 d-flex flex-column justify-space-between"
            :class="condo.id === authStore.activeCondominioId ? 'border-2 border-primary bg-slate-800' : 'bg-slate-800 border border-slate-700'"
            style="cursor: pointer;"
            @click="selectCondo(condo.id)"
          >
            <div>
              <!-- Badge & Icon -->
              <div class="d-flex justify-space-between align-center mb-4">
                <v-avatar
                  size="48"
                  :color="condo.parent ? 'teal' : 'primary'"
                  variant="tonal"
                >
                  <v-icon :icon="condo.parent ? 'mdi-office-building-marker' : 'mdi-office-building'" size="28" />
                </v-avatar>
                <v-chip
                  v-if="condo.id === authStore.activeCondominioId"
                  color="success"
                  size="small"
                  variant="flat"
                  class="font-weight-bold"
                >
                  Activa actualmente
                </v-chip>
                <v-chip
                  v-else-if="condo.torre_bloque"
                  color="teal"
                  size="small"
                  variant="tonal"
                  class="font-weight-bold"
                >
                  {{ condo.torre_bloque }}
                </v-chip>
                <v-chip
                  v-else
                  color="primary"
                  size="small"
                  variant="tonal"
                  class="font-weight-bold"
                >
                  Edificio Único
                </v-chip>
              </div>

              <!-- Title & Subtitle -->
              <div class="text-h6 font-weight-bold text-white mb-1">
                {{ condo.torre_bloque ? `${condo.torre_bloque} - ` : '' }}{{ condo.nombre }}
              </div>
              <div v-if="condo.parent" class="text-caption font-weight-medium text-teal-300 mb-2">
                🏘️ Complejo: {{ condo.parent.nombre }}
              </div>
              <div class="text-caption text-slate-400 mb-4">
                {{ condo.direccion || 'Dirección registrada en el sistema' }}
              </div>
            </div>

            <div>
              <v-divider class="my-3 border-slate-700" />
              <!-- Stats summary -->
              <div class="d-flex justify-space-between align-center text-caption text-slate-400 mb-4">
                <div>
                  <v-icon icon="mdi-home" size="16" class="mr-1 text-slate-500" />
                  <strong>{{ condo.numero_apartamentos || 15 }}</strong> aptos
                </div>
                <div>
                  <v-icon icon="mdi-card-account-details" size="16" class="mr-1 text-slate-500" />
                  RIF: {{ condo.rif || 'N/A' }}
                </div>
              </div>

              <!-- Select Button -->
              <v-btn
                block
                :color="condo.parent ? 'teal' : 'primary'"
                variant="flat"
                prepend-icon="mdi-login"
                class="font-weight-bold text-capitalize"
                @click.stop="selectCondo(condo.id)"
              >
                Administrar esta Torre
              </v-btn>
            </div>
          </v-card>
        </v-col>
      </v-row>

      <!-- Logout option -->
      <div class="text-center mt-8">
        <v-btn
          variant="text"
          color="slate-400"
          prepend-icon="mdi-logout"
          @click="logout"
        >
          Cerrar Sesión
        </v-btn>
      </div>
    </v-container>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../store/auth';

const authStore = useAuthStore();
const router = useRouter();

const selectCondo = (condoId) => {
    authStore.switchCondominio(condoId, false);
    authStore.notify('Has ingresado a la torre seleccionada', 'success');
    router.push('/dashboard/admin');
};

const logout = async () => {
    await authStore.logout();
    router.push('/login');
};

onMounted(async () => {
    if (authStore.isAuthenticated && !authStore.user) {
        await authStore.fetchUser();
    }
    if (authStore.isMaster) {
        router.replace('/dashboard/master');
    }
});
</script>

<style scoped>
.transition-all {
    transition: all 0.2s ease-in-out;
}
.transition-all:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px -10px rgba(0, 0, 0, 0.5);
}
</style>
