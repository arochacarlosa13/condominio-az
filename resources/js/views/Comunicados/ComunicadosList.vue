<template>
  <div>
    <!-- Top Header -->
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold text-slate-900">Cartelera Informativa y Comunicados</h1>
        <p class="text-caption text-slate-500">Avisos oficiales del condominio, asambleas de copropietarios y circulares</p>
      </div>
      <v-btn v-if="!authStore.isPropietario" color="primary" prepend-icon="mdi-bullhorn" @click="openDialog">
        Publicar Comunicado
      </v-btn>
    </div>

    <!-- Announcements Cards Grid -->
    <v-row>
      <v-col v-for="com in comunicadosList" :key="com.id" cols="12" md="6">
        <v-card class="pa-5 bg-white border border-slate-100" height="100%">
          <div class="d-flex justify-space-between align-center mb-2">
            <span class="text-h6 font-weight-bold text-slate-900">{{ com.titulo }}</span>
            <v-chip size="x-small" color="primary" variant="tonal">
              {{ com.fecha_publicacion }}
            </v-chip>
          </div>

          <p class="text-body-2 text-slate-700 my-3" style="white-space: pre-line;">
            {{ com.contenido }}
          </p>

          <v-divider class="my-3" />

          <div class="d-flex justify-space-between align-center">
            <div class="text-caption text-slate-400">
              <v-icon icon="mdi-whatsapp" color="success" size="14" /> Notificación enviada
            </div>
            <v-btn
              v-if="!authStore.isPropietario"
              size="small"
              variant="text"
              color="error"
              icon="mdi-delete"
              @click="deleteComunicado(com.id)"
            />
          </div>
        </v-card>
      </v-col>

      <v-col v-if="!comunicadosList.length" cols="12">
        <v-card class="pa-8 text-center bg-white border border-slate-100 text-slate-400">
          No hay comunicados publicados en este momento.
        </v-card>
      </v-col>
    </v-row>

    <!-- Dialog for Publishing Announcement -->
    <v-dialog v-model="dialog" max-width="600">
      <v-card class="pa-4">
        <v-card-title class="font-weight-bold">Nuevo Comunicado Oficial</v-card-title>
        <v-card-text>
          <v-form @submit.prevent="saveComunicado">
            <v-text-field v-model="form.titulo" label="Título del Comunicado" required class="mb-3" />
            <v-textarea v-model="form.contenido" label="Contenido del Mensaje" rows="4" required class="mb-3" />
            <v-text-field v-model="form.fecha_publicacion" label="Fecha de Publicación" type="date" required class="mb-3" />
            
            <div class="d-flex gap-4 mb-2">
              <v-checkbox v-model="form.enviar_email" label="Notificar por Correo" density="compact" hide-details color="primary" />
              <v-checkbox v-model="form.enviar_whatsapp" label="Notificar por WhatsApp" density="compact" hide-details color="success" />
            </div>
          </v-form>
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn variant="text" @click="dialog = false">Cancelar</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveComunicado">Publicar y Notificar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../../store/auth';

const authStore = useAuthStore();
const comunicadosList = ref([]);
const dialog = ref(false);
const saving = ref(false);

const form = ref({
    titulo: '',
    contenido: '',
    fecha_publicacion: new Date().toISOString().substring(0, 10),
    enviar_email: true,
    enviar_whatsapp: true,
});

const fetchComunicados = async () => {
    try {
        const { data } = await axios.get('/comunicados');
        if (data.success) comunicadosList.value = data.data.data || data.data;
    } catch (e) {
        authStore.notify('Error al cargar cartelera', 'error');
    }
};

const openDialog = () => {
    form.value = {
        titulo: '',
        contenido: '',
        fecha_publicacion: new Date().toISOString().substring(0, 10),
        enviar_email: true,
        enviar_whatsapp: true,
    };
    dialog.value = true;
};

const saveComunicado = async () => {
    saving.value = true;
    try {
        await axios.post('/comunicados', form.value);
        authStore.notify('Comunicado publicado y notificaciones despachadas');
        dialog.value = false;
        fetchComunicados();
    } catch (e) {
        authStore.notify('Error al publicar comunicado', 'error');
    } finally {
        saving.value = false;
    }
};

const deleteComunicado = async (id) => {
    if (!confirm('¿Desea retirar este comunicado de la cartelera?')) return;
    try {
        await axios.delete(`/comunicados/${id}`);
        authStore.notify('Comunicado retirado');
        fetchComunicados();
    } catch (e) {
        authStore.notify('Error al retirar comunicado', 'error');
    }
};

onMounted(fetchComunicados);
</script>
