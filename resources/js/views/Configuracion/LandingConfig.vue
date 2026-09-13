<template>
  <div>
    <!-- Header Page -->
    <div class="d-flex flex-column flex-sm-row justify-space-between align-start align-sm-center gap-3 mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold text-slate-900">Administración de Landing Page PWA</h1>
        <p class="text-caption text-slate-500">Gestione los contenidos de marketing, banners, catálogo de productos y la visibilidad de cada sección de la página principal</p>
      </div>
      <div class="d-flex gap-2">
        <v-btn
          color="primary"
          variant="flat"
          prepend-icon="mdi-content-save"
          :loading="saving"
          class="font-weight-bold"
          @click="saveSettings"
        >
          Guardar Cambios
        </v-btn>
        <v-btn
          color="secondary"
          variant="tonal"
          prepend-icon="mdi-open-in-new"
          href="/"
          target="_blank"
        >
          Ver Landing
        </v-btn>
      </div>
    </div>

    <!-- Tabs Navigation -->
    <v-card class="bg-white border border-slate-100 mb-6">
      <v-tabs v-model="activeTab" color="primary" align-tabs="start">
        <v-tab value="sections" prepend-icon="mdi-eye-outline">Secciones Visibles</v-tab>
        <v-tab value="hero" prepend-icon="mdi-bullhorn-outline">Hero & Banner</v-tab>
        <v-tab value="features" prepend-icon="mdi-star-outline">Módulos del Sistema</v-tab>
        <v-tab value="products" prepend-icon="mdi-cube-outline">Otros Productos & Servicios</v-tab>
        <v-tab value="stats" prepend-icon="mdi-chart-timeline-variant">Métricas & Estadísticas</v-tab>
        <v-tab value="contact" prepend-icon="mdi-phone-outline">Contacto & WhatsApp</v-tab>
        <v-tab value="testimonials" prepend-icon="mdi-format-quote-open">Testimonios</v-tab>
        <v-tab value="how_it_works" prepend-icon="mdi-numeric-3-box-outline">Cómo Funciona</v-tab>
      </v-tabs>
    </v-card>

    <v-form ref="formRef" @submit.prevent="saveSettings">
      <v-window v-model="activeTab">

        <!-- ===== Tab 0: SECCIONES VISIBLES ===== -->
        <v-window-item value="sections">
          <v-card class="pa-6 bg-white border border-slate-100">
            <div class="d-flex align-center gap-2 mb-2">
              <v-icon icon="mdi-eye-outline" color="primary" />
              <h2 class="text-subtitle-1 font-weight-bold text-slate-900">Control de Visibilidad de Secciones</h2>
            </div>
            <p class="text-body-2 text-slate-500 mb-6">
              Active o desactive secciones de la landing page con un solo clic. Los cambios se aplican de inmediato al guardar.
            </p>

            <v-row>
              <v-col
                v-for="section in sectionControls"
                :key="section.key"
                cols="12"
                sm="6"
                md="4"
              >
                <div
                  :class="[
                    'section-toggle-card pa-4 rounded-xl border transition-all',
                    form.sections[section.key]
                      ? 'border-blue-300 bg-blue-50'
                      : 'border-slate-200 bg-slate-50'
                  ]"
                >
                  <div class="d-flex justify-space-between align-start">
                    <div class="d-flex align-center gap-2 mb-2">
                      <div
                        :class="[
                          'w-9 h-9 rounded-lg d-flex align-center justify-center',
                          form.sections[section.key] ? 'bg-blue-100' : 'bg-slate-200'
                        ]"
                      >
                        <v-icon :icon="section.icon" :color="form.sections[section.key] ? 'blue-darken-1' : 'grey'" size="20" />
                      </div>
                    </div>
                    <v-switch
                      v-model="form.sections[section.key]"
                      :disabled="section.fixed"
                      color="primary"
                      density="compact"
                      hide-details
                      inset
                    />
                  </div>
                  <div class="text-body-2 font-weight-bold text-slate-800">{{ section.label }}</div>
                  <div class="text-caption text-slate-500 mt-1">{{ section.description }}</div>
                  <v-chip
                    v-if="section.fixed"
                    color="grey"
                    size="x-small"
                    variant="tonal"
                    class="mt-2 font-weight-bold"
                  >
                    Siempre activo
                  </v-chip>
                  <v-chip
                    v-else-if="form.sections[section.key]"
                    color="blue"
                    size="x-small"
                    variant="tonal"
                    class="mt-2 font-weight-bold"
                  >
                    Visible
                  </v-chip>
                  <v-chip
                    v-else
                    color="grey"
                    size="x-small"
                    variant="tonal"
                    class="mt-2 font-weight-bold"
                  >
                    Oculto
                  </v-chip>
                </div>
              </v-col>
            </v-row>

            <v-alert
              type="info"
              variant="tonal"
              class="mt-6"
              icon="mdi-information-outline"
            >
              <strong>Consejo:</strong> Las secciones desactivadas no aparecen en la landing page pública, pero su contenido se conserva. Puede reactivarlas en cualquier momento sin perder datos.
            </v-alert>
          </v-card>
        </v-window-item>

        <!-- Tab 1: Hero & Banner -->
        <v-window-item value="hero">
          <v-card class="pa-6 bg-white border border-slate-100">
            <h2 class="text-subtitle-1 font-weight-bold text-slate-900 mb-4">Sección Principal (Hero Banner)</h2>
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="form.hero.badge"
                  label="Badge Superior (Pill)"
                  hint="Ej: Solución Tecnológica de Vanguardia ⚡"
                  persistent-hint
                  variant="outlined"
                  density="comfortable"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="form.hero.title"
                  label="Título Principal"
                  hint="Nombre de la aplicación o titular de la landing"
                  persistent-hint
                  variant="outlined"
                  density="comfortable"
                  required
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="form.hero.subtitle"
                  label="Subtítulo / Descripción Resumida"
                  rows="3"
                  variant="outlined"
                  density="comfortable"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="form.hero.primary_cta_text"
                  label="Texto Botón Principal (CTA)"
                  variant="outlined"
                  density="comfortable"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="form.hero.secondary_cta_text"
                  label="Texto Botón Secundario"
                  variant="outlined"
                  density="comfortable"
                />
              </v-col>
            </v-row>
          </v-card>
        </v-window-item>

        <!-- Tab 2: App Features -->
        <v-window-item value="features">
          <v-card class="pa-6 bg-white border border-slate-100">
            <div class="d-flex justify-space-between align-center mb-4">
              <h2 class="text-subtitle-1 font-weight-bold text-slate-900">Características del Sistema AZPRO</h2>
              <v-btn size="small" color="primary" prepend-icon="mdi-plus" variant="tonal" @click="addFeature">
                Agregar Característica
              </v-btn>
            </div>

            <v-row v-for="(feat, idx) in form.app_features" :key="idx" class="mb-2 align-center border-b border-slate-100 pb-4">
              <v-col cols="12" md="3">
                <v-text-field
                  v-model="feat.icon"
                  label="Ícono MDI"
                  hint="Ej: mdi-whatsapp"
                  persistent-hint
                  variant="outlined"
                  density="compact"
                  prepend-inner-icon="mdi-emoticon-outline"
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="feat.title"
                  label="Título de la Característica"
                  variant="outlined"
                  density="compact"
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="feat.description"
                  label="Descripción"
                  variant="outlined"
                  density="compact"
                />
              </v-col>
              <v-col cols="12" md="1" class="text-right">
                <v-btn icon="mdi-delete" color="error" variant="text" size="small" @click="removeFeature(idx)" />
              </v-col>
            </v-row>
          </v-card>
        </v-window-item>

        <!-- Tab 3: Extra Products -->
        <v-window-item value="products">
          <v-card class="pa-6 bg-white border border-slate-100">
            <div class="d-flex justify-space-between align-center mb-4">
              <h2 class="text-subtitle-1 font-weight-bold text-slate-900">Catálogo de Otros Productos & Servicios</h2>
              <v-btn size="small" color="teal" prepend-icon="mdi-plus" variant="tonal" @click="addProduct">
                Agregar Producto/Servicio
              </v-btn>
            </div>

            <div v-for="(prod, pIdx) in form.extra_products" :key="pIdx" class="p-4 rounded-xl bg-slate-50 border border-slate-200 mb-4">
              <div class="d-flex justify-space-between align-center mb-2">
                <span class="font-weight-bold text-slate-700">Producto / Servicio #{{ pIdx + 1 }}</span>
                <v-btn icon="mdi-delete" color="error" variant="text" size="small" @click="removeProduct(pIdx)" />
              </div>

              <v-row>
                <v-col cols="12" md="3">
                  <v-text-field v-model="prod.badge" label="Badge Categoría" variant="outlined" density="compact" />
                </v-col>
                <v-col cols="12" md="3">
                  <v-text-field v-model="prod.icon" label="Ícono MDI" variant="outlined" density="compact" />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model="prod.title" label="Nombre del Producto" variant="outlined" density="compact" />
                </v-col>
                <v-col cols="12">
                  <v-textarea v-model="prod.description" label="Descripción Detallada" rows="2" variant="outlined" density="compact" />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model="prod.cta_text" label="Texto del Botón de Acción" variant="outlined" density="compact" />
                </v-col>
              </v-row>
            </div>
          </v-card>
        </v-window-item>

        <!-- Tab 4: Stats -->
        <v-window-item value="stats">
          <v-card class="pa-6 bg-white border border-slate-100">
            <h2 class="text-subtitle-1 font-weight-bold text-slate-900 mb-4">Métricas Públicas de Impacto</h2>
            <v-row>
              <v-col cols="12" sm="6" md="3">
                <v-text-field v-model="form.stats.condominios" label="Total Condominios" hint="Ej: +50" persistent-hint variant="outlined" density="comfortable" />
              </v-col>
              <v-col cols="12" sm="6" md="3">
                <v-text-field v-model="form.stats.propietarios" label="Total Propietarios" hint="Ej: +12,000" persistent-hint variant="outlined" density="comfortable" />
              </v-col>
              <v-col cols="12" sm="6" md="3">
                <v-text-field v-model="form.stats.recibos_procesados" label="Recibos Procesados" hint="Ej: +150,000" persistent-hint variant="outlined" density="comfortable" />
              </v-col>
              <v-col cols="12" sm="6" md="3">
                <v-text-field v-model="form.stats.satisfaccion" label="Porcentaje Satisfacción" hint="Ej: 99.4%" persistent-hint variant="outlined" density="comfortable" />
              </v-col>
            </v-row>
          </v-card>
        </v-window-item>

        <!-- Tab 5: Contact -->
        <v-window-item value="contact">
          <v-card class="pa-6 bg-white border border-slate-100">
            <h2 class="text-subtitle-1 font-weight-bold text-slate-900 mb-4">Datos de Contacto & Canales Directos</h2>
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.contact.phone" label="Teléfono de Atención" variant="outlined" density="comfortable" prepend-inner-icon="mdi-phone" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.contact.whatsapp" label="Número de WhatsApp" hint="Incluir código de país, ej: +58 412-0000000" persistent-hint variant="outlined" density="comfortable" prepend-inner-icon="mdi-whatsapp" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.contact.email" label="Correo Electrónico" variant="outlined" density="comfortable" prepend-inner-icon="mdi-email" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.contact.address" label="Ubicación / Ciudad" variant="outlined" density="comfortable" prepend-inner-icon="mdi-map-marker" />
              </v-col>
              <v-col cols="12">
                <v-textarea v-model="form.contact.whatsapp_message" label="Mensaje Predeterminado de WhatsApp" hint="Mensaje inicial con el que el cliente contacta por WhatsApp" persistent-hint rows="2" variant="outlined" density="comfortable" />
              </v-col>
            </v-row>
          </v-card>
        </v-window-item>

        <!-- Tab 6: Testimonios -->
        <v-window-item value="testimonials">
          <v-card class="pa-6 bg-white border border-slate-100">
            <div class="d-flex justify-space-between align-center mb-2">
              <h2 class="text-subtitle-1 font-weight-bold text-slate-900">Testimonios de Clientes</h2>
              <v-btn size="small" color="primary" prepend-icon="mdi-plus" variant="tonal" @click="addTestimonial">
                Agregar Testimonio
              </v-btn>
            </div>
            <v-alert type="info" variant="tonal" class="mb-4" density="compact">
              Active la sección "Testimonios" en la pestaña <strong>Secciones Visibles</strong> para que aparezca en la landing page.
            </v-alert>

            <div v-for="(test, tIdx) in form.testimonials" :key="tIdx" class="p-4 rounded-xl bg-slate-50 border border-slate-200 mb-4">
              <div class="d-flex justify-space-between align-center mb-3">
                <div class="d-flex align-center gap-2">
                  <v-avatar :color="test.color || 'blue'" size="32">
                    <span class="text-white font-weight-black text-caption">{{ test.inicial || '?' }}</span>
                  </v-avatar>
                  <span class="font-weight-bold text-slate-700">Testimonio #{{ tIdx + 1 }}</span>
                </div>
                <v-btn icon="mdi-delete" color="error" variant="text" size="small" @click="removeTestimonial(tIdx)" />
              </div>

              <v-row>
                <v-col cols="12" md="4">
                  <v-text-field v-model="test.nombre" label="Nombre Completo" variant="outlined" density="compact" />
                </v-col>
                <v-col cols="12" md="4">
                  <v-text-field v-model="test.cargo" label="Cargo / Rol" variant="outlined" density="compact" />
                </v-col>
                <v-col cols="12" md="4">
                  <v-text-field v-model="test.condominio" label="Nombre del Condominio" variant="outlined" density="compact" />
                </v-col>
                <v-col cols="12">
                  <v-textarea v-model="test.cita" label="Cita / Testimonio" rows="2" variant="outlined" density="compact" />
                </v-col>
                <v-col cols="12" md="3">
                  <v-text-field v-model="test.plan" label="Plan Activo" variant="outlined" density="compact" hint="Ej: Básico, Profesional, Enterprise" persistent-hint />
                </v-col>
                <v-col cols="12" md="2">
                  <v-text-field v-model="test.inicial" label="Inicial del Avatar" variant="outlined" density="compact" maxlength="1" />
                </v-col>
                <v-col cols="12" md="3">
                  <v-select
                    v-model="test.color"
                    label="Color del Avatar"
                    :items="['blue', 'indigo', 'teal', 'green', 'purple', 'orange']"
                    variant="outlined"
                    density="compact"
                  />
                </v-col>
                <v-col cols="12" md="4">
                  <v-rating v-model="test.calificacion" color="amber-darken-1" size="24" />
                  <div class="text-caption text-slate-500">Calificación: {{ test.calificacion }}/5</div>
                </v-col>
              </v-row>
            </div>

            <div v-if="!form.testimonials?.length" class="text-center py-8 text-slate-400">
              <v-icon icon="mdi-format-quote-open" size="48" class="mb-2" />
              <p>No hay testimonios agregados. Haga clic en "Agregar Testimonio" para comenzar.</p>
            </div>
          </v-card>
        </v-window-item>

        <!-- Tab 7: Cómo Funciona -->
        <v-window-item value="how_it_works">
          <v-card class="pa-6 bg-white border border-slate-100">
            <div class="d-flex justify-space-between align-center mb-2">
              <h2 class="text-subtitle-1 font-weight-bold text-slate-900">Sección "Cómo Funciona" (3 Pasos)</h2>
              <v-btn size="small" color="indigo" prepend-icon="mdi-plus" variant="tonal" @click="addStep">
                Agregar Paso
              </v-btn>
            </div>
            <v-alert type="info" variant="tonal" class="mb-4" density="compact">
              Active la sección "Cómo Funciona" en la pestaña <strong>Secciones Visibles</strong> para que aparezca en la landing page.
            </v-alert>

            <div v-for="(step, sIdx) in form.how_it_works" :key="sIdx" class="p-4 rounded-xl bg-slate-50 border border-slate-200 mb-4">
              <div class="d-flex justify-space-between align-center mb-3">
                <div class="d-flex align-center gap-2">
                  <div class="w-8 h-8 rounded-full bg-blue-600 d-flex align-center justify-center">
                    <span class="text-white text-xs font-weight-black">{{ sIdx + 1 }}</span>
                  </div>
                  <span class="font-weight-bold text-slate-700">Paso {{ sIdx + 1 }}</span>
                </div>
                <v-btn icon="mdi-delete" color="error" variant="text" size="small" @click="removeStep(sIdx)" />
              </div>

              <v-row>
                <v-col cols="12" md="4">
                  <v-text-field v-model="step.titulo" label="Título del Paso" variant="outlined" density="compact" />
                </v-col>
                <v-col cols="12" md="4">
                  <v-text-field v-model="step.icon" label="Ícono MDI" variant="outlined" density="compact" hint="Ej: mdi-office-building-plus-outline" persistent-hint />
                </v-col>
                <v-col cols="12" md="4">
                  <v-text-field v-model="step.numero" label="Número del Paso" variant="outlined" density="compact" hint="Ej: 01, 02, 03" persistent-hint />
                </v-col>
                <v-col cols="12">
                  <v-textarea v-model="step.descripcion" label="Descripción del Paso" rows="2" variant="outlined" density="compact" />
                </v-col>
              </v-row>
            </div>
          </v-card>
        </v-window-item>

      </v-window>
    </v-form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../../store/auth';

const authStore = useAuthStore();
const activeTab = ref('sections');
const saving = ref(false);

// ===== SECTION CONTROLS CONFIG =====
const sectionControls = [
  {
    key: 'hero',
    label: 'Hero / Banner Principal',
    description: 'Sección de bienvenida con título, subtítulo y CTA.',
    icon: 'mdi-bullhorn-outline',
    fixed: true,
  },
  {
    key: 'trust_logos',
    label: 'Logos de Confianza',
    description: 'Marquee infinito con nombres de condominios clientes.',
    icon: 'mdi-handshake-outline',
    fixed: false,
  },
  {
    key: 'how_it_works',
    label: 'Cómo Funciona',
    description: 'Pasos de onboarding con imagen e ícono.',
    icon: 'mdi-numeric-3-box-outline',
    fixed: false,
  },
  {
    key: 'features',
    label: 'Características del Sistema',
    description: 'Grilla de módulos y funcionalidades del sistema.',
    icon: 'mdi-star-outline',
    fixed: false,
  },
  {
    key: 'testimonials',
    label: 'Testimonios de Clientes',
    description: 'Opiniones de administradores que usan AZPRO.',
    icon: 'mdi-format-quote-open',
    fixed: false,
  },
  {
    key: 'stats',
    label: 'Estadísticas & Métricas',
    description: 'Contadores animados de impacto (condominios, residentes, etc.).',
    icon: 'mdi-chart-timeline-variant',
    fixed: false,
  },
  {
    key: 'products',
    label: 'Otros Productos & Servicios',
    description: 'Catálogo de productos y servicios AZPRO adicionales.',
    icon: 'mdi-cube-outline',
    fixed: false,
  },
  {
    key: 'planes',
    label: 'Planes SaaS',
    description: 'Tabla de precios y comparación de planes de suscripción.',
    icon: 'mdi-credit-card-outline',
    fixed: false,
  },
  {
    key: 'whatsapp_float',
    label: 'Botón WhatsApp Flotante',
    description: 'Botón de contacto fijo en dispositivos móviles.',
    icon: 'mdi-whatsapp',
    fixed: false,
  },
];

const form = ref({
  hero: {
    badge: '',
    title: '',
    subtitle: '',
    primary_cta_text: '',
    secondary_cta_text: '',
    banner_url: '',
  },
  app_features: [],
  extra_products: [],
  stats: {
    condominios: '',
    propietarios: '',
    recibos_procesados: '',
    satisfaccion: '',
  },
  contact: {
    phone: '',
    whatsapp: '',
    email: '',
    address: '',
    whatsapp_message: '',
  },
  sections: {
    hero: true,
    trust_logos: false,
    how_it_works: false,
    features: true,
    testimonials: false,
    stats: true,
    products: true,
    planes: true,
    whatsapp_float: true,
  },
  testimonials: [],
  how_it_works: [],
  trust_logos: [],
});

// ===== API =====
const loadSettings = async () => {
  try {
    const { data } = await axios.get('/landing-content');
    if (data.success) {
      // Merge loaded data preserving all defaults
      const loaded = data.data;
      form.value.hero         = loaded.hero         ?? form.value.hero;
      form.value.app_features = loaded.app_features ?? [];
      form.value.extra_products = loaded.extra_products ?? [];
      form.value.stats        = loaded.stats         ?? form.value.stats;
      form.value.contact      = loaded.contact       ?? form.value.contact;
      form.value.testimonials = loaded.testimonials  ?? [];
      form.value.how_it_works = loaded.how_it_works  ?? [];
      form.value.trust_logos  = loaded.trust_logos   ?? [];
      // Merge sections carefully, preserving defaults for new keys
      form.value.sections = {
        ...form.value.sections,
        ...(loaded.sections ?? {}),
      };
    }
  } catch (e) {
    authStore.notify('Error al obtener la configuración de la landing page', 'error');
  }
};

const saveSettings = async () => {
  saving.value = true;
  try {
    const { data } = await axios.put('/landing-content', form.value);
    if (data.success) {
      authStore.notify('Landing Page actualizada con éxito', 'success');
    }
  } catch (e) {
    authStore.notify('Error al guardar los cambios de la landing page', 'error');
  } finally {
    saving.value = false;
  }
};

// ===== CRUD Features =====
const addFeature = () => {
  form.value.app_features.push({ icon: 'mdi-star-outline', title: 'Nueva Característica', description: 'Descripción de la característica...' });
};
const removeFeature = (index) => form.value.app_features.splice(index, 1);

// ===== CRUD Products =====
const addProduct = () => {
  form.value.extra_products.push({ badge: 'Nuevo Servicio', icon: 'mdi-cube-outline', title: 'Nuevo Producto / Servicio', description: 'Detalle de la oferta o servicio...', cta_text: 'Solicitar Información' });
};
const removeProduct = (index) => form.value.extra_products.splice(index, 1);

// ===== CRUD Testimonios =====
const addTestimonial = () => {
  form.value.testimonials.push({ nombre: '', cargo: '', condominio: '', cita: '', plan: 'Básico', inicial: 'A', color: 'blue', calificacion: 5 });
};
const removeTestimonial = (index) => form.value.testimonials.splice(index, 1);

// ===== CRUD Pasos =====
const addStep = () => {
  const num = String(form.value.how_it_works.length + 1).padStart(2, '0');
  form.value.how_it_works.push({ numero: num, titulo: 'Nuevo Paso', descripcion: 'Descripción del paso...', icon: 'mdi-star-outline' });
};
const removeStep = (index) => form.value.how_it_works.splice(index, 1);

onMounted(loadSettings);
</script>

<style scoped>
.section-toggle-card {
  transition: all 0.2s ease;
  cursor: default;
}
</style>
