<template>
  <div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 px-4 py-12 relative overflow-hidden">
    <!-- Círculos de luz flotantes para estética premium (Glow Effects) -->
    <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-indigo-600/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-purple-600/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="relative w-full max-w-lg bg-slate-900/40 backdrop-blur-xl border border-slate-800/80 p-8 rounded-2xl shadow-2xl space-y-8">
      <!-- Logo o Título -->
      <div class="text-center">
        <div class="inline-flex items-center justify-center p-3 bg-indigo-500/10 rounded-2xl border border-indigo-500/20 mb-3">
          <span class="text-3xl">🏢</span>
        </div>
        <h1 class="text-3xl font-black tracking-tight bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">
          Gestión Inteligente de Condominios AZPRO
        </h1>
      </div>

      <!-- Formulario de Login -->
      <form @submit.prevent="submit" class="space-y-5">
        <div>
          <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">
            Correo Electrónico
          </label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            required
            placeholder="ejemplo@correo.com"
            class="w-full bg-slate-950/60 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white rounded-xl px-4 py-3 outline-none transition-all placeholder:text-slate-650"
            :class="{ 'border-rose-500/80': form.errors.email }"
          />
          <span v-if="form.errors.email" class="text-rose-400 text-xs mt-1.5 block font-medium">
            {{ form.errors.email }}
          </span>
        </div>

        <div>
          <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">
            Contraseña
          </label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            required
            placeholder="••••••••"
            class="w-full bg-slate-950/60 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white rounded-xl px-4 py-3 outline-none transition-all placeholder:text-slate-650"
            :class="{ 'border-rose-500/80': form.errors.password }"
          />
          <span v-if="form.errors.password" class="text-rose-400 text-xs mt-1.5 block font-medium">
            {{ form.errors.password }}
          </span>
        </div>

        <button
          type="submit"
          :disabled="form.processing"
          class="w-full bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 hover:from-indigo-600 hover:via-purple-600 hover:to-pink-600 text-white font-bold py-3.5 px-4 rounded-xl shadow-lg hover:shadow-indigo-500/20 active:scale-[0.98] transition-all disabled:opacity-50 disabled:pointer-events-none"
        >
          {{ form.processing ? 'Verificando credenciales...' : 'Ingresar al Portal' }}
        </button>
      </form>

    </div>
  </div>
</template>

<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import Swal from 'sweetalert2';

const page = usePage();

// Watcher de mensajes flash
watch(() => page.props.flash, (flash) => {
  if (flash && flash.error) {
    Swal.fire({
      icon: 'error',
      title: '¡Atención!',
      text: flash.error,
      confirmButtonColor: '#ef4444'
    });
  }
}, { deep: true, immediate: true });

const form = useForm({
  email: '',
  password: '',
});

const submit = () => {
  form.post('/login', {
    onFinish: () => form.reset('password'),
    onError: () => {
      Swal.fire({
        icon: 'error',
        title: 'Error de autenticación',
        text: 'Por favor, revise que su correo y contraseña sean válidos.',
        confirmButtonColor: '#6366f1'
      });
    }
  });
};
</script>
