<template>
  <div :class="['min-h-screen d-flex align-center justify-center relative overflow-hidden pa-4 sm:pa-6 lg:pa-8 transition-colors duration-300', isDark ? 'bg-slate-950 text-slate-100' : 'bg-slate-100 text-slate-900']">
    <!-- Ambient Sapphire Blue Mesh Backgrounds (Variantes Difuminadas) -->
    <div v-if="isDark" class="absolute -top-40 -left-40 w-[550px] h-[550px] bg-blue-600/25 rounded-full blur-[140px] pointer-events-none"></div>
    <div v-if="isDark" class="absolute -bottom-40 -right-40 w-[550px] h-[550px] bg-indigo-600/20 rounded-full blur-[140px] pointer-events-none"></div>
    <div v-if="isDark" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-sky-500/10 rounded-full blur-[160px] pointer-events-none"></div>

    <!-- Main Split Container -->
    <v-card
      width="100%"
      max-width="1100"
      :class="['rounded-3xl border shadow-2xl overflow-hidden relative z-10 my-auto transition-colors', isDark ? 'bg-slate-900/90 border-blue-900/40 shadow-blue-500/10' : 'bg-white border-slate-200 shadow-slate-300']"
      :theme="isDark ? 'dark' : 'light'"
    >
      <div class="grid grid-cols-1 lg:grid-cols-12 min-h-[640px]">
        
        <!-- Left Side: Login Form -->
        <div :class="['lg:col-span-6 pa-6 sm:pa-10 d-flex flex-column justify-space-between transition-colors', isDark ? 'bg-slate-900/80' : 'bg-white']">
          <div>
            <!-- Header Links & Brand & Theme Switcher -->
            <div class="d-flex justify-space-between align-center mb-8">
              <router-link
                to="/"
                :class="['d-inline-flex align-center gap-2 text-xs font-weight-bold text-decoration-none transition-colors', isDark ? 'text-blue-400 hover:text-blue-300' : 'text-blue-700 hover:text-blue-800']"
              >
                <v-icon icon="mdi-arrow-left" size="16" :color="isDark ? 'blue-lighten-2' : 'blue-darken-2'" />
                <span>Volver a la Landing Page</span>
              </router-link>

              <div class="d-flex align-center gap-2">
                <!-- Theme Switcher Button -->
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

                <v-chip color="blue" size="x-small" variant="tonal" class="font-weight-bold">
                  PWA App
                </v-chip>
              </div>
            </div>

            <!-- Title & Subtitle -->
            <div class="mb-8">
              <div class="d-flex align-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 via-blue-500 to-indigo-500 d-flex align-center justify-center shadow-md shadow-blue-500/30">
                  <v-icon icon="mdi-office-building-cog" size="22" color="white" />
                </div>
                <span :class="['text-h6 font-weight-black', isDark ? 'text-white' : 'text-slate-900']">Sistema <span class="text-blue-600">AZPRO</span></span>
              </div>
              <h1 :class="['text-h4 font-weight-black tracking-tight mb-1', isDark ? 'text-white' : 'text-slate-900']">
                ¡Bienvenido de nuevo!
              </h1>
              <p :class="['text-body-2', isDark ? 'text-slate-300' : 'text-slate-600']">
                Ingresa tus credenciales para acceder a la administración del condominio.
              </p>
            </div>

            <!-- Alert Notification -->
            <v-alert
              v-if="errorMessage"
              type="error"
              variant="tonal"
              density="compact"
              class="mb-6 rounded-xl"
              closable
              @click:close="errorMessage = ''"
            >
              {{ errorMessage }}
            </v-alert>

            <!-- Form -->
            <v-form ref="formRef" @submit.prevent="handleLogin">
              <div class="mb-4">
                <label :class="['text-caption font-weight-bold mb-1 d-block', isDark ? 'text-slate-200' : 'text-slate-700']">Correo Electrónico</label>
                <v-text-field
                  v-model="email"
                  placeholder="usuario@condominio.com"
                  prepend-inner-icon="mdi-email-outline"
                  color="blue"
                  variant="outlined"
                  density="comfortable"
                  class="rounded-xl"
                  :rules="[v => !!v || 'El correo es obligatorio', v => /.+@.+\..+/.test(v) || 'Correo no válido']"
                  required
                />
              </div>

              <div class="mb-4">
                <label :class="['text-caption font-weight-bold mb-1 d-block', isDark ? 'text-slate-200' : 'text-slate-700']">Contraseña</label>
                <v-text-field
                  v-model="password"
                  placeholder="••••••••"
                  prepend-inner-icon="mdi-lock-outline"
                  :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                  :type="showPassword ? 'text' : 'password'"
                  color="blue"
                  variant="outlined"
                  density="comfortable"
                  class="rounded-xl"
                  :rules="[v => !!v || 'La contraseña es obligatoria', v => (v && v.length >= 6) || 'Mínimo 6 caracteres']"
                  required
                  @click:append-inner="showPassword = !showPassword"
                />
              </div>

              <div class="d-flex justify-space-between align-center mb-6">
                <v-checkbox
                  v-model="remember"
                  label="Recordarme"
                  density="compact"
                  hide-details
                  color="blue"
                  :class="isDark ? 'text-slate-300' : 'text-slate-700'"
                />
                <router-link to="/recuperar-password" :class="['text-caption font-weight-bold text-decoration-none', isDark ? 'text-blue-400 hover:text-blue-300' : 'text-blue-700 hover:text-blue-800']">
                  ¿Olvidaste tu contraseña?
                </router-link>
              </div>

              <v-btn
                type="submit"
                block
                size="x-large"
                color="blue-darken-1"
                variant="flat"
                class="font-weight-black text-none rounded-xl py-3 shadow-xl bg-gradient-to-r from-blue-600 via-blue-500 to-indigo-600 text-white mb-2"
                :loading="loading"
                prepend-icon="mdi-login"
              >
                Ingresar al Sistema
              </v-btn>
            </v-form>
          </div>

          <!-- Footer Text -->
          <div :class="['pt-6 border-t text-center text-caption', isDark ? 'border-slate-800/80 text-slate-400' : 'border-slate-200 text-slate-500']">
            Sistema Inteligente de Gestión de Condominios AZPRO • Multitenant SaaS
          </div>
        </div>

        <!-- Right Side: Showcase Panel (Visible on Desktop) -->
        <div :class="['d-none d-lg-flex lg:col-span-6 border-l pa-10 flex-column justify-space-between relative overflow-hidden transition-colors', isDark ? 'bg-gradient-to-br from-slate-900 via-blue-950/60 to-indigo-950/70 border-blue-900/40' : 'bg-gradient-to-br from-blue-900 via-blue-800 to-indigo-900 border-slate-200 text-white']">
          <!-- Background Decorative Glow -->
          <div v-if="isDark" class="absolute -top-20 -right-20 w-80 h-80 bg-blue-500/25 rounded-full blur-3xl pointer-events-none"></div>

          <!-- Top Badge Showcase -->
          <div class="d-flex align-center justify-space-between">
            <v-chip color="blue-lighten-4" variant="flat" size="small" class="font-weight-bold text-blue-950">
              SOLUCIÓN CORPORATIVA ⚡
            </v-chip>
            <span class="text-caption text-slate-300 font-mono">v2.5 SaaS Live</span>
          </div>

          <!-- Central Feature Message -->
          <div class="my-auto py-6">
            <h2 class="text-h4 font-weight-black text-white tracking-tight mb-4 leading-tight">
              Administración Transparente y Eficiente para tu Torre
            </h2>
            <p class="text-body-1 text-slate-200 mb-8 leading-relaxed">
              Gestiona cobros en Bolívares y Dólares a tasa BCV diaria, emite recibos de pago con verificación QR pública y supervisa el control de acceso en tiempo real.
            </p>

            <div class="space-y-4">
              <div :class="['p-3.5 rounded-2xl border d-flex align-center gap-3', isDark ? 'bg-slate-900/80 border-slate-800' : 'bg-white/10 border-white/20']">
                <v-avatar color="blue" variant="tonal" size="36">
                  <v-icon icon="mdi-sync" color="blue-lighten-2" size="20" />
                </v-avatar>
                <div>
                  <div class="text-subtitle-2 font-weight-bold text-white">Sincronización BCV en Vivo</div>
                  <div class="text-caption text-slate-300">Conversión automática e historial diario de tasas oficiales.</div>
                </div>
              </div>

              <div :class="['p-3.5 rounded-2xl border d-flex align-center gap-3', isDark ? 'bg-slate-900/80 border-slate-800' : 'bg-white/10 border-white/20']">
                <v-avatar color="indigo" variant="tonal" size="36">
                  <v-icon icon="mdi-whatsapp" color="indigo-lighten-2" size="20" />
                </v-avatar>
                <div>
                  <div class="text-subtitle-2 font-weight-bold text-white">Notificaciones Masivas por WhatsApp</div>
                  <div class="text-caption text-slate-300">Envío instantáneo de avisos de cobro y recordatorios.</div>
                </div>
              </div>

              <div :class="['p-3.5 rounded-2xl border d-flex align-center gap-3', isDark ? 'bg-slate-900/80 border-slate-800' : 'bg-white/10 border-white/20']">
                <v-avatar color="sky" variant="tonal" size="36">
                  <v-icon icon="mdi-qrcode-scan" color="sky-lighten-2" size="20" />
                </v-avatar>
                <div>
                  <div class="text-subtitle-2 font-weight-bold text-white">Control de Acceso & Visitantes</div>
                  <div class="text-caption text-slate-300">Escaneo de QR, fotografías de vehículos y cédulas en garita.</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Bottom Stats Footer -->
          <div :class="['p-4 rounded-2xl border d-flex align-center justify-space-between', isDark ? 'bg-slate-950/80 border-slate-800' : 'bg-white/10 border-white/20']">
            <div class="d-flex align-center gap-2">
              <v-icon icon="mdi-shield-check" color="blue-lighten-2" size="20" />
              <span class="text-xs font-weight-bold text-white">+50 Condominios Activos</span>
            </div>
            <span class="text-xs text-blue-300 font-weight-bold">+150k Recibos Procesados</span>
          </div>

        </div>

      </div>
    </v-card>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../store/auth';
import { useTheme } from '../../composables/useTheme';

const email = ref('master@condominio.com');
const password = ref('123456');
const showPassword = ref(false);
const remember = ref(false);
const loading = ref(false);
const errorMessage = ref('');

const authStore = useAuthStore();
const router = useRouter();
const { isDark, toggleTheme } = useTheme();

const handleLogin = async () => {
    errorMessage.value = '';
    loading.value = true;
    try {
        const data = await authStore.login(email.value, password.value, remember.value);
        authStore.notify('Bienvenido al sistema', 'success');
        router.push(data.redirect_to || '/dashboard/admin');
    } catch (error) {
        errorMessage.value = error.response?.data?.message || error.message || 'Error al iniciar sesión';
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
</style>
