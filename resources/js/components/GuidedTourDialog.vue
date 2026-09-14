<template>
  <v-dialog v-model="visible" max-width="680" persistent>
    <v-card class="rounded-2xl overflow-hidden shadow-2xl border border-slate-100">
      <!-- Header Banner with Gradient and Step Indicators -->
      <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white pa-6">
        <div class="d-flex justify-space-between align-center mb-4">
          <div class="d-flex align-center gap-2">
            <v-chip color="emerald" size="small" variant="flat" class="font-weight-bold text-white">
              <v-icon icon="mdi-compass-outline" start size="14" /> Tour de Bienvenida
            </v-chip>
            <span class="text-caption text-slate-300">Paso {{ step + 1 }} de {{ steps.length }}</span>
          </div>
          <v-btn icon="mdi-close" variant="text" color="white" size="small" @click="closeTour" />
        </div>

        <h2 class="text-h6 font-weight-bold mb-1">
          {{ currentStep.title }}
        </h2>
        <p class="text-caption text-slate-300 mb-0">
          {{ currentStep.subtitle }}
        </p>

        <!-- Progress Dots -->
        <div class="d-flex gap-2 mt-4">
          <div
            v-for="(_, i) in steps"
            :key="i"
            class="transition-all duration-300 rounded-pill"
            :style="{
              height: '5px',
              width: i === step ? '28px' : '10px',
              backgroundColor: i === step ? '#10b981' : 'rgba(255,255,255,0.25)'
            }"
          />
        </div>
      </div>

      <!-- Step Content Body -->
      <v-card-text class="pa-6">
        <div class="d-flex flex-column flex-sm-row align-start gap-4 mb-4">
          <v-avatar :color="currentStep.color" variant="tonal" size="64" class="flex-shrink-0">
            <v-icon :icon="currentStep.icon" :color="currentStep.color" size="36" />
          </v-avatar>
          <div>
            <h3 class="text-subtitle-1 font-weight-bold text-slate-900 mb-1">
              {{ currentStep.headline }}
            </h3>
            <p class="text-body-2 text-slate-600 leading-relaxed mb-0">
              {{ currentStep.description }}
            </p>
          </div>
        </div>

        <!-- Highlight Feature Box -->
        <div class="pa-4 bg-slate-50 border border-slate-200 rounded-xl">
          <div class="text-caption font-weight-bold text-slate-700 text-uppercase tracking-wider mb-2 d-flex align-center gap-1.5">
            <v-icon icon="mdi-star-four-points" size="14" color="amber-darken-3" />
            Beneficio de Excelencia Operativa:
          </div>
          <ul class="text-caption text-slate-600 pl-4 space-y-1 mb-0">
            <li v-for="(tip, idx) in currentStep.tips" :key="idx">
              {{ tip }}
            </li>
          </ul>
        </div>
      </v-card-text>

      <!-- Footer Buttons -->
      <v-card-actions class="pa-4 px-6 bg-slate-50 d-flex justify-space-between border-t border-slate-100">
        <v-btn
          variant="text"
          color="slate-500"
          class="font-weight-medium"
          @click="dismissForever"
        >
          No volver a mostrar
        </v-btn>

        <div class="d-flex gap-2">
          <v-btn
            v-if="step > 0"
            variant="outlined"
            color="slate-600"
            class="font-weight-bold"
            @click="prevStep"
          >
            Anterior
          </v-btn>

          <v-btn
            v-if="step < steps.length - 1"
            color="primary"
            variant="flat"
            class="font-weight-bold px-5"
            @click="nextStep"
          >
            Siguiente
          </v-btn>

          <v-btn
            v-else
            color="emerald"
            variant="flat"
            class="font-weight-bold px-6 text-white"
            @click="finishTour"
          >
            ¡Empezar a Usar AZPRO!
          </v-btn>
        </div>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const visible = ref(false);
const step = ref(0);

const steps = [
  {
    title: '1. Cuentas Bancarias y Tasa Oficial BCV',
    subtitle: 'Base financiera de la plataforma',
    icon: 'mdi-bank-check',
    color: 'indigo',
    headline: 'Configuración de Datos de Pago y Bimoneda',
    description: 'Registra las cuentas bancarias de la residencia y los datos de Pago Móvil. El sistema sincroniza automáticamente la tasa oficial del BCV todos los días a las 8:00 PM, congelando la tasa al emitir recibos.',
    tips: [
      'Visualización bimoneda dual instantánea USD y Bolívares en todo el sistema.',
      'Días de gracia y porcentaje de mora configurables por condominio.',
    ],
    route: '/condominios',
  },
  {
    title: '2. Emisión Mensual en 3 Pasos con Redondeo al Céntimo',
    subtitle: 'Cálculo automatizado con rigor milimétrico',
    icon: 'mdi-shield-check',
    color: 'emerald',
    headline: 'Emisión Guiada y Certificación de Recibos',
    description: 'Genera las expensas mensuales con el Asistente Guiado en 3 pasos. Nuestro algoritmo compensa automáticamente los céntimos de redondeo para garantizar cuadre exacto al 100.0000% del presupuesto.',
    tips: [
      'Reconocimiento automático de notas de crédito y excedentes a favor.',
      'Certificación criptográfica que bloquea los recibos contra alteraciones.',
    ],
    route: '/contabilidad/admin',
  },
  {
    title: '3. Conciliación Bancaria Inteligente',
    subtitle: 'Aprobación de pagos en segundos con 0% error',
    icon: 'mdi-file-excel-outline',
    color: 'teal',
    headline: 'Auto-Matching por Referencia y Monto',
    description: 'Carga el extracto digital de tu banco (Banesco, Mercantil, BDV, Provincial, etc.). El sistema contrastará cada crédito contra las notificaciones de los propietarios para que apruebes decenas de pagos con un solo clic.',
    tips: [
      'Detección automática de referencias bancarias y montos idénticos.',
      'Emisión y despacho de Recibos RP y notas de crédito por excedente por email.',
    ],
    route: '/pagos',
  },
  {
    title: '4. Libro de Flujo de Caja y Tesorería en Tiempo Real',
    subtitle: 'Control estricto de liquidez y proveedores',
    icon: 'mdi-cash-sync',
    color: 'primary',
    headline: 'Saldo Bancario Disponible en Vivo',
    description: 'Consulta en cualquier momento el balance real de tesorería: Saldo Disponible = Cobrado en Banco − Facturas Pagadas a Contratistas, con desglose detallado por cuenta bancaria.',
    tips: [
      'Histórico evolutivo de ingresos vs egresos de los últimos 6 meses.',
      'Superávit o déficit operativo mensual calculado en tiempo real.',
    ],
    route: '/contabilidad/admin',
  },
  {
    title: '5. Cobranza Preventiva y Asambleas Digitales',
    subtitle: 'Cero fricción con los residentes',
    icon: 'mdi-vote-outline',
    color: 'purple',
    headline: 'Automatización y Democracia Comunitaria',
    description: 'Reduce la morosidad con recordatorios preventivos automáticos 3 días antes del vencimiento. Realiza consultas o asambleas extraordinarias con cómputo instantáneo de quórum ponderado por alícuota.',
    tips: [
      'Disparo de avisos de cortesía automáticos programados.',
      'Votación digital transparente ponderada por el porcentaje de alícuota.',
    ],
    route: '/notificaciones',
  },
];

const currentStep = computed(() => steps[step.value] || steps[0]);

const openTour = () => {
  step.value = 0;
  visible.value = true;
};

const closeTour = () => {
  visible.value = false;
};

const nextStep = () => {
  if (step.value < steps.length - 1) {
    step.value++;
  }
};

const prevStep = () => {
  if (step.value > 0) {
    step.value--;
  }
};

const finishTour = () => {
  localStorage.setItem('azpro_tour_completed', 'true');
  visible.value = false;
  if (currentStep.value?.route) {
    router.push(currentStep.value.route);
  }
};

const dismissForever = () => {
  localStorage.setItem('azpro_tour_completed', 'true');
  visible.value = false;
};

defineExpose({
  openTour,
});
</script>
