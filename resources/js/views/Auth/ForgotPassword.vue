<template>
  <div :class="['min-h-screen d-flex align-center justify-center relative overflow-hidden pa-4 sm:pa-6 transition-colors duration-300', isDark ? 'bg-slate-950 text-slate-100' : 'bg-slate-100 text-slate-900']">
    <!-- Ambient Light Meshes in Background -->
    <div v-if="isDark" class="absolute -top-40 -left-40 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
    <div v-if="isDark" class="absolute -bottom-40 -right-40 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>

    <v-card
      width="100%"
      max-width="480"
      :class="['pa-6 sm:pa-8 rounded-3xl border shadow-2xl relative z-10 transition-colors', isDark ? 'bg-slate-900/90 border-blue-900/40 shadow-blue-500/10' : 'bg-white border-slate-200 shadow-slate-300']"
      :theme="isDark ? 'dark' : 'light'"
    >
      <div class="d-flex justify-space-between align-center mb-6">
        <router-link to="/login" :class="['d-inline-flex align-center gap-1 text-xs font-weight-bold text-decoration-none', isDark ? 'text-blue-400' : 'text-blue-700']">
          <v-icon icon="mdi-arrow-left" size="16" />
          <span>Volver al Login</span>
        </router-link>

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
      </div>

      <div class="text-center mb-8">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 d-flex align-center justify-center mx-auto mb-4 elevation-6 shadow-blue-500/30">
          <v-icon icon="mdi-lock-reset" size="30" color="white" />
        </div>
        <h1 :class="['text-h5 font-weight-black tracking-wide', isDark ? 'text-white' : 'text-slate-900']">
          Recuperar Contraseña
        </h1>
        <p :class="['text-caption mt-1', isDark ? 'text-slate-300' : 'text-slate-600']">
          Ingresa tu correo para recibir las instrucciones de autogestión de clave.
        </p>
      </div>

      <v-alert
        v-if="message"
        :type="isSuccess ? 'success' : 'error'"
        variant="tonal"
        density="compact"
        class="mb-6 rounded-xl"
      >
        {{ message }}
      </v-alert>

      <v-form @submit.prevent="handleRecover">
        <v-text-field
          v-model="email"
          label="Correo Registrado"
          placeholder="usuario@condominio.com"
          prepend-inner-icon="mdi-email-outline"
          color="blue"
          variant="outlined"
          density="comfortable"
          :rules="[v => !!v || 'El correo es obligatorio']"
          required
          class="mb-6"
        />

        <v-btn
          type="submit"
          block
          size="x-large"
          color="blue-darken-1"
          variant="flat"
          class="font-weight-black text-none rounded-xl py-3 shadow-xl bg-gradient-to-r from-blue-600 via-blue-500 to-indigo-600 text-white mb-4"
          :loading="loading"
        >
          Enviar Instrucciones
        </v-btn>
      </v-form>
    </v-card>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { useTheme } from '../../composables/useTheme';

const email = ref('');
const loading = ref(false);
const message = ref('');
const isSuccess = ref(false);
const { isDark, toggleTheme } = useTheme();

const handleRecover = async () => {
    loading.value = true;
    message.value = '';
    try {
        const { data } = await axios.post('/recuperar-password', { email: email.value });
        isSuccess.value = true;
        message.value = data.message;
    } catch (e) {
        isSuccess.value = false;
        message.value = e.response?.data?.message || 'Error al procesar la solicitud.';
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
</style>
