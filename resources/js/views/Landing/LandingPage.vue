<template>
  <div :class="['landing-container font-sans min-h-screen transition-colors duration-300', isDark ? 'bg-slate-950 text-slate-100' : 'bg-slate-50 text-slate-900']">

    <!-- PWA Install Banner Top Notice -->
    <div
      v-if="deferredPrompt"
      class="bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-600 text-white px-4 py-2 text-sm text-center d-flex align-center justify-center gap-3 shadow-lg z-50 relative font-weight-bold"
    >
      <v-icon icon="mdi-cellphone-arrow-down" color="white" size="20" />
      <span>Instala el <strong>Sistema AZ PRO</strong> como aplicación nativa en tu dispositivo para un acceso más rápido.</span>
      <v-btn
        size="x-small"
        color="white"
        variant="flat"
        class="text-blue-950 font-weight-black ml-2 rounded-pill px-3 shadow-sm"
        @click="installPWA"
      >
        Instalar Ahora
      </v-btn>
      <v-btn
        icon="mdi-close"
        size="x-small"
        variant="text"
        color="white"
        @click="deferredPrompt = null"
      />
    </div>

    <!-- Header Navigation -->
    <header :class="['sticky-header border-b backdrop-blur-md sticky top-0 z-40 transition-colors', isDark ? 'border-blue-900/40 bg-slate-950/90' : 'border-slate-200 bg-white/90']">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 d-flex align-center justify-space-between">

        <!-- Logo -->
        <div class="d-flex align-center gap-3 cursor-pointer" @click="scrollTo('hero')">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 via-blue-500 to-indigo-500 d-flex align-center justify-center elevation-4 shadow-blue-500/30 shadow-lg">
            <v-icon icon="mdi-office-building-cog" color="white" size="24" />
          </div>
          <div>
            <div :class="['text-h6 font-weight-black tracking-wide', isDark ? 'text-white' : 'text-slate-900']" style="line-height: 1.1;">
              Sistema <span class="text-blue-600">AZPRO</span>
            </div>
            <div :class="['text-caption font-weight-semibold', isDark ? 'text-blue-300' : 'text-blue-700']">
              Gestión Inteligente de Condominios
            </div>
          </div>
        </div>

        <!-- Desktop Nav Links -->
        <nav class="d-none d-md-flex align-center gap-6">
          <a href="#caracteristicas" :class="['nav-link transition-colors text-body-2 font-weight-medium', isDark ? 'text-slate-300 hover:text-blue-400' : 'text-slate-700 hover:text-blue-600']">Características</a>
          <a href="#como-funciona" v-if="sec.how_it_works" :class="['nav-link transition-colors text-body-2 font-weight-medium', isDark ? 'text-slate-300 hover:text-blue-400' : 'text-slate-700 hover:text-blue-600']">Cómo Funciona</a>
          <a href="#productos" :class="['nav-link transition-colors text-body-2 font-weight-medium', isDark ? 'text-slate-300 hover:text-blue-400' : 'text-slate-700 hover:text-blue-600']">Otros Productos</a>
          <a href="#planes" :class="['nav-link transition-colors text-body-2 font-weight-medium', isDark ? 'text-slate-300 hover:text-blue-400' : 'text-slate-700 hover:text-blue-600']">Planes</a>
          <a href="#contacto" :class="['nav-link transition-colors text-body-2 font-weight-medium', isDark ? 'text-slate-300 hover:text-blue-400' : 'text-slate-700 hover:text-blue-600']">Contacto</a>
        </nav>

        <!-- CTA Buttons & Dark/Light Mode -->
        <div class="d-flex align-center gap-3">
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

          <v-btn
            v-if="deferredPrompt"
            variant="flat"
            color="blue-darken-2"
            size="small"
            class="d-none d-sm-inline-flex font-weight-bold rounded-lg text-white shadow-sm"
            prepend-icon="mdi-download"
            @click="installPWA"
          >
            Instalar App
          </v-btn>

          <v-btn
            v-if="authStore.token"
            color="blue-darken-1"
            variant="flat"
            size="medium"
            class="rounded-lg font-weight-bold text-none px-5 shadow-lg bg-gradient-to-r from-blue-600 to-indigo-600 text-white"
            :to="getDashboardRoute()"
            prepend-icon="mdi-view-dashboard-outline"
          >
            Ir a Mi Panel
          </v-btn>

          <v-btn
            v-else
            color="blue-darken-1"
            variant="flat"
            size="medium"
            class="rounded-lg font-weight-bold text-none px-5 shadow-lg bg-gradient-to-r from-blue-600 to-indigo-600 text-white"
            to="/login"
            prepend-icon="mdi-login"
          >
            Iniciar Sesión
          </v-btn>
        </div>
      </div>
    </header>

    <!-- ===== HERO SECTION — INTERACTIVE BACKGROUND ===== -->
    <section
      id="hero"
      ref="heroSection"
      class="hero-section relative overflow-hidden pt-12 pb-20 lg:pt-20 lg:pb-32"
      @mousemove="onHeroMouseMove"
      @mouseleave="onHeroMouseLeave"
    >
      <!-- ── BG Image (parallax layer 0) ── -->
      <div
        class="hero-bg-image absolute inset-0 pointer-events-none"
        :style="parallaxStyle(0.018)"
      >
        <img
          src="/landing/hero-bg.jpg"
          alt=""
          class="w-full h-full object-cover"
          :class="isDark ? 'opacity-30' : 'opacity-[0.24]'"
          draggable="false"
        />
        <!-- gradient overlay to fade into section bg -->
        <div
          class="absolute inset-0"
          :class="isDark
            ? 'bg-gradient-to-t from-slate-950 via-slate-950/70 to-slate-900/40'
            : 'bg-gradient-to-t from-slate-50 via-slate-50/80 to-blue-50/60'"
        ></div>
      </div>

      <!-- ── Floating Orbs (parallax layer 1 — slow) ── -->
      <div class="absolute inset-0 pointer-events-none overflow-hidden">
        <!-- Orb 1: top-left -->
        <div
          class="hero-orb hero-orb-1 absolute rounded-full"
          :class="isDark ? 'bg-blue-600/20' : 'bg-blue-400/15'"
          :style="parallaxStyle(0.03)"
        ></div>
        <!-- Orb 2: top-right -->
        <div
          class="hero-orb hero-orb-2 absolute rounded-full"
          :class="isDark ? 'bg-indigo-500/15' : 'bg-indigo-300/15'"
          :style="parallaxStyle(0.025)"
        ></div>
        <!-- Orb 3: bottom-center -->
        <div
          class="hero-orb hero-orb-3 absolute rounded-full"
          :class="isDark ? 'bg-sky-500/10' : 'bg-sky-300/10'"
          :style="parallaxStyle(0.04)"
        ></div>
      </div>

      <!-- ── Floating Geometric Shapes (parallax layer 2 — faster) ── -->
      <div class="absolute inset-0 pointer-events-none overflow-hidden">
        <!-- Shape: floating square rotated -->
        <div
          class="hero-shape hero-shape-sq-1 absolute border-2 rounded-2xl"
          :class="isDark ? 'border-blue-500/20' : 'border-blue-400/20'"
          :style="parallaxStyle(0.055)"
        ></div>
        <!-- Shape: floating circle ring -->
        <div
          class="hero-shape hero-shape-ring absolute border-2 rounded-full"
          :class="isDark ? 'border-indigo-400/20' : 'border-indigo-300/25'"
          :style="parallaxStyle(0.07)"
        ></div>
        <!-- Shape: small square dot -->
        <div
          class="hero-shape hero-shape-sq-2 absolute rounded-lg"
          :class="isDark ? 'bg-blue-400/10' : 'bg-blue-500/10'"
          :style="parallaxStyle(0.06)"
        ></div>
        <!-- Shape: triangle-ish -->
        <div
          class="hero-shape hero-shape-tri absolute rounded-full"
          :class="isDark ? 'bg-indigo-500/10' : 'bg-indigo-400/10'"
          :style="parallaxStyle(0.08)"
        ></div>
      </div>

      <!-- ── Grid dot pattern overlay ── -->
      <div
        class="absolute inset-0 pointer-events-none hero-dot-grid"
        :class="isDark ? 'opacity-[0.06]' : 'opacity-[0.04]'"
      ></div>

      <!-- ── Main Content ── -->
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 align-center">

          <!-- Text Content -->
          <div class="lg:col-span-7 reveal-on-scroll">
            <div :class="['d-inline-flex align-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-weight-bold mb-6 shadow-lg', isDark ? 'bg-blue-950/90 border border-blue-500/40 text-blue-300 shadow-blue-500/10' : 'bg-blue-100 border border-blue-300 text-blue-800 shadow-blue-500/5']">
              <v-icon icon="mdi-shield-check" :color="isDark ? 'blue-lighten-2' : 'blue-darken-2'" size="16" />
              <span>{{ landingData.hero?.badge || 'Solución Tecnológica de Vanguardia ⚡' }}</span>
            </div>

            <h1 :class="['text-h4 text-sm-h3 text-lg-h2 font-weight-black tracking-tight mb-6 leading-tight', isDark ? 'text-white' : 'text-slate-900']">
              {{ landingData.hero?.title || 'Sistema Inteligente de Gestión de Condominios AZPRO' }}
            </h1>

            <p :class="['text-subtitle-1 text-sm-h6 font-weight-regular mb-8 max-w-2xl leading-relaxed', isDark ? 'text-slate-300' : 'text-slate-600']">
              {{ landingData.hero?.subtitle || 'La plataforma SaaS de mayor innovación para la administración eficiente, transparente y automatizada de condominios, torres residenciales y complejos comerciales.' }}
            </p>

            <div class="d-flex flex-wrap gap-4 align-center">
              <v-btn
                size="x-large"
                color="blue-darken-1"
                class="rounded-xl font-weight-black text-none px-8 shadow-xl bg-gradient-to-r from-blue-600 via-blue-500 to-indigo-600 text-white hero-cta-pulse"
                @click="scrollTo('caracteristicas')"
                prepend-icon="mdi-rocket-launch-outline"
              >
                {{ landingData.hero?.primary_cta_text || 'Conocer Características' }}
              </v-btn>

              <v-btn
                size="x-large"
                variant="outlined"
                color="blue"
                :class="['rounded-xl font-weight-bold text-none px-8 border-blue-500', isDark ? 'text-white hover:bg-blue-600/30' : 'text-blue-700 hover:bg-blue-50']"
                to="/login"
                prepend-icon="mdi-login"
              >
                {{ landingData.hero?.secondary_cta_text || 'Iniciar Sesión' }}
              </v-btn>
            </div>

            <!-- Key Feature Highlights -->
            <div :class="['mt-10 pt-8 border-t grid grid-cols-2 sm:grid-cols-3 gap-4', isDark ? 'border-slate-800/80' : 'border-slate-200']">
              <div class="d-flex align-center gap-2">
                <v-icon icon="mdi-check-circle" :color="isDark ? 'blue-lighten-2' : 'blue-darken-1'" size="20" />
                <span :class="['text-xs font-weight-medium', isDark ? 'text-slate-300' : 'text-slate-700']">Tasa BCV Automática</span>
              </div>
              <div class="d-flex align-center gap-2">
                <v-icon icon="mdi-check-circle" :color="isDark ? 'blue-lighten-2' : 'blue-darken-1'" size="20" />
                <span :class="['text-xs font-weight-medium', isDark ? 'text-slate-300' : 'text-slate-700']">Recibos PDF con QR</span>
              </div>
              <div class="d-flex align-center gap-2">
                <v-icon icon="mdi-check-circle" :color="isDark ? 'blue-lighten-2' : 'blue-darken-1'" size="20" />
                <span :class="['text-xs font-weight-medium', isDark ? 'text-slate-300' : 'text-slate-700']">Avisos por WhatsApp</span>
              </div>
            </div>
          </div>

          <!-- Visual Showcase Card — floats with parallax -->
          <div
            class="lg:col-span-5 reveal-on-scroll"
            style="--reveal-delay: 150ms"
            :style="{ ...parallaxStyle(0.04), transition: 'transform 0.12s ease-out, opacity 0.55s ease' }"
          >
            <div :class="['hero-preview-card p-6 rounded-3xl border shadow-2xl relative', isDark ? 'bg-gradient-to-b from-slate-900/95 via-slate-900/95 to-slate-950/95 border-blue-900/40 shadow-blue-500/20' : 'bg-white/95 border-slate-200 shadow-slate-200']">
              <div :class="['d-flex align-center justify-space-between mb-4 pb-3 border-b', isDark ? 'border-slate-800' : 'border-slate-100']">
                <div class="d-flex align-center gap-2">
                  <div class="w-3 h-3 rounded-full bg-rose-500"></div>
                  <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                  <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                  <span :class="['text-xs font-mono ml-2', isDark ? 'text-slate-400' : 'text-slate-500']">app.azpro.com</span>
                </div>
                <v-chip color="blue" size="x-small" variant="flat" class="font-weight-bold text-white">
                  BCV: Live Sync
                </v-chip>
              </div>

              <!-- Mockup Content -->
              <div class="space-y-4">
                <div :class="['p-4 rounded-xl border d-flex align-center justify-space-between', isDark ? 'bg-slate-800/60 border-slate-700/60' : 'bg-slate-50 border-slate-200']">
                  <div>
                    <div :class="['text-xs', isDark ? 'text-slate-400' : 'text-slate-500']">Torre Residencial Principal</div>
                    <div :class="['text-subtitle-2 font-weight-bold', isDark ? 'text-white' : 'text-slate-900']">Residencias Los Aviadores</div>
                  </div>
                  <v-chip color="blue" size="small" variant="tonal" class="font-weight-bold">
                    Solvente
                  </v-chip>
                </div>

                <div class="grid grid-cols-2 gap-3">
                  <div :class="['p-3 rounded-xl border', isDark ? 'bg-slate-800/40 border-slate-700/40' : 'bg-slate-50 border-slate-200']">
                    <div :class="['text-caption', isDark ? 'text-slate-400' : 'text-slate-500']">Cobranza del Mes</div>
                    <div class="text-body-1 font-weight-bold text-blue-600">94.8%</div>
                  </div>
                  <div :class="['p-3 rounded-xl border', isDark ? 'bg-slate-800/40 border-slate-700/40' : 'bg-slate-50 border-slate-200']">
                    <div :class="['text-caption', isDark ? 'text-slate-400' : 'text-slate-500']">Apartamentos</div>
                    <div class="text-body-1 font-weight-bold text-indigo-600">128 Unidades</div>
                  </div>
                </div>

                <!-- Animated progress bar -->
                <div :class="['p-4 rounded-xl border', isDark ? 'bg-slate-800/40 border-slate-700/40' : 'bg-slate-50 border-slate-200']">
                  <div class="d-flex justify-space-between mb-2">
                    <span :class="['text-caption font-weight-medium', isDark ? 'text-slate-300' : 'text-slate-700']">Recaudación Agosto</span>
                    <span class="text-caption font-weight-bold text-blue-500">$3,840 / $4,050</span>
                  </div>
                  <div :class="['rounded-full h-2 overflow-hidden', isDark ? 'bg-slate-700' : 'bg-slate-200']">
                    <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-indigo-500 hero-progress-bar"></div>
                  </div>
                </div>

                <div :class="['p-4 rounded-xl border d-flex align-center gap-3', isDark ? 'bg-blue-950/70 border-blue-800/70' : 'bg-blue-50 border-blue-200']">
                  <v-avatar color="blue-600" size="36">
                    <v-icon icon="mdi-qrcode-scan" color="white" size="20" />
                  </v-avatar>
                  <div>
                    <div :class="['text-caption font-weight-medium', isDark ? 'text-blue-200' : 'text-blue-900']">Seguridad & Visitantes</div>
                    <div :class="['text-xs', isDark ? 'text-slate-300' : 'text-slate-600']">Validación instantánea por código QR y registro digital de placas</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- ===== TRUST LOGOS MARQUEE ===== -->
    <section
      v-if="sec.trust_logos"
      :class="['py-10 border-t border-b overflow-hidden relative transition-colors', isDark ? 'bg-slate-900 border-slate-800' : 'bg-slate-50/80 border-slate-200']"
    >
      <!-- Section Background -->
      <div class="section-bg absolute inset-0 pointer-events-none z-0">
        <img src="/landing/bg-trust-logos.jpg" alt="" class="w-full h-full object-cover" draggable="false" />
        <div class="absolute inset-0" :class="isDark ? 'bg-slate-900/70' : 'bg-white/70'"></div>
      </div>
      <div class="relative z-10 max-w-7xl mx-auto px-4 mb-5 text-center">
        <p :class="['text-caption font-weight-bold tracking-widest uppercase', isDark ? 'text-slate-500' : 'text-slate-500']">
          Condominios y empresas que confían en AZPRO
        </p>
      </div>
      <div class="trust-marquee-track relative z-10">
        <div class="trust-marquee-content">
          <div
            v-for="(logo, i) in [...trustLogosList, ...trustLogosList]"
            :key="i"
            :class="['trust-logo-chip d-inline-flex align-center gap-2 mx-4 px-5 py-2.5 rounded-full border font-weight-bold text-sm whitespace-nowrap', isDark ? 'bg-slate-800 border-slate-700 text-slate-200 shadow-md' : 'bg-white border-slate-200 text-slate-800 shadow-sm']"
          >
            <v-icon icon="mdi-office-building" :color="isDark ? 'blue-lighten-3' : 'blue-darken-1'" size="16" />
            {{ logo.nombre }}
          </div>
        </div>
      </div>
    </section>

    <!-- ===== CÓMO FUNCIONA ===== -->
    <section
      v-if="sec.how_it_works"
      id="como-funciona"
      :class="['py-20 transition-colors relative overflow-hidden', isDark ? 'bg-slate-950' : 'bg-slate-50/80']"
    >
      <!-- Section Background -->
      <div class="section-bg absolute inset-0 pointer-events-none z-0">
        <img src="/landing/bg-how-it-works.jpg" alt="" class="w-full h-full object-cover" draggable="false" />
        <div class="absolute inset-0" :class="isDark ? 'bg-slate-950/70' : 'bg-slate-50/70'"></div>
      </div>
      <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16 reveal-on-scroll">
          <v-chip color="indigo" variant="tonal" class="font-weight-bold mb-3 px-4">
            ONBOARDING RÁPIDO
          </v-chip>
          <h2 :class="['text-h4 text-sm-h3 font-weight-bold tracking-tight mb-4', isDark ? 'text-white' : 'text-slate-900']">
            ¿Cómo Funciona AZPRO?
          </h2>
          <p :class="['text-body-1', isDark ? 'text-slate-400' : 'text-slate-600']">
            Comience a gestionar su condominio en tres simples pasos. Sin instalaciones complejas ni configuraciones técnicas.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div
            v-for="(step, sIdx) in howItWorksList"
            :key="sIdx"
            :class="['how-it-works-card p-6 rounded-3xl border text-center relative reveal-on-scroll', isDark ? 'bg-slate-900 border-slate-800 shadow-xl' : 'bg-white border-slate-200/90 shadow-md shadow-slate-200/60 hover:shadow-xl']"
            :style="`--reveal-delay: ${sIdx * 120}ms`"
          >
            <!-- Step number -->
            <div class="absolute -top-4 left-1/2 -translate-x-1/2">
              <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-600 to-indigo-600 d-flex align-center justify-center shadow-lg">
                <span class="text-white text-xs font-weight-black">{{ sIdx + 1 }}</span>
              </div>
            </div>

            <!-- Image -->
            <div class="mt-4 mb-5 rounded-2xl overflow-hidden border border-slate-100 dark:border-slate-800" style="aspect-ratio: 4/3;">
              <img
                :src="howItWorksImages[sIdx]"
                :alt="step.titulo"
                class="w-full h-full object-cover"
                loading="lazy"
              />
            </div>

            <!-- Icon -->
            <div :class="['w-12 h-12 rounded-xl border d-flex align-center justify-center mb-4 mx-auto', isDark ? 'bg-blue-950 border-blue-800' : 'bg-blue-50 border-blue-200']">
              <v-icon :icon="step.icon || 'mdi-star-outline'" :color="isDark ? 'blue-lighten-2' : 'blue-darken-1'" size="24" />
            </div>

            <h3 :class="['text-subtitle-1 font-weight-bold mb-2', isDark ? 'text-white' : 'text-slate-900']">
              {{ step.titulo }}
            </h3>
            <p :class="['text-body-2 leading-relaxed', isDark ? 'text-slate-400' : 'text-slate-600']">
              {{ step.descripcion }}
            </p>

            <!-- Connector arrow (not on last) -->
            <div v-if="sIdx < 2" class="d-none d-md-block absolute -right-4 top-1/2 -translate-y-1/2 z-10">
              <div :class="['w-8 h-8 rounded-full border d-flex align-center justify-center shadow-sm', isDark ? 'bg-slate-800 border-slate-700' : 'bg-white border-slate-200 text-slate-700']">
                <v-icon icon="mdi-arrow-right" :color="isDark ? 'blue-lighten-2' : 'blue-darken-1'" size="16" />
              </div>
            </div>
          </div>
        </div>

        <!-- CTA below steps -->
        <div class="text-center mt-12 reveal-on-scroll">
          <v-btn
            size="large"
            color="blue-darken-1"
            class="rounded-xl font-weight-bold text-none px-8 shadow-lg bg-gradient-to-r from-blue-600 to-indigo-600 text-white"
            @click="openWhatsAppContact('Demo')"
            prepend-icon="mdi-play-circle-outline"
          >
            Solicitar Demostración Gratuita
          </v-btn>
        </div>
      </div>
    </section>

    <!-- ===== CARACTERÍSTICAS DEL SISTEMA ===== -->
    <section v-if="sec.features" id="caracteristicas" :class="['py-20 border-t border-b transition-colors relative overflow-hidden', isDark ? 'bg-slate-900 border-slate-800/80' : 'bg-slate-50/50 border-slate-200']">
      <!-- Section Background -->
      <div class="section-bg absolute inset-0 pointer-events-none z-0">
        <img src="/landing/bg-features.jpg" alt="" class="w-full h-full object-cover" draggable="false" />
        <div class="absolute inset-0" :class="isDark ? 'bg-slate-900/70' : 'bg-white/70'"></div>
      </div>
      <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center max-w-3xl mx-auto mb-16 reveal-on-scroll">
          <v-chip color="blue" variant="tonal" class="font-weight-bold mb-3 px-4">
            POTENCIA & AUTOMATIZACIÓN
          </v-chip>
          <h2 :class="['text-h4 text-sm-h3 font-weight-bold tracking-tight mb-4', isDark ? 'text-white' : 'text-slate-900']">
            Diseñado para la gestión moderna de condominios
          </h2>
          <p :class="['text-body-1', isDark ? 'text-slate-400' : 'text-slate-600']">
            Descubra las herramientas que simplifican el trabajo administrativo, garantizan la transparencia contable y mejoran la convivencia en su comunidad.
          </p>
        </div>

        <!-- Features Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <div
            v-for="(feature, index) in featuresList"
            :key="index"
            :class="['feature-card p-6 rounded-2xl border transition-all duration-300 hover:shadow-xl group reveal-on-scroll', isDark ? 'bg-slate-950 border-slate-800 hover:border-blue-500/50 hover:shadow-blue-500/10' : 'bg-white border-slate-200/90 shadow-md shadow-slate-200/50 hover:border-blue-500 hover:shadow-xl']"
            :style="`--reveal-delay: ${(index % 3) * 100}ms`"
          >
            <div :class="['w-12 h-12 rounded-xl border d-flex align-center justify-center mb-5 group-hover:scale-110 transition-transform', isDark ? 'bg-blue-950 border-blue-800/80' : 'bg-blue-50 border-blue-200']">
              <v-icon :icon="feature.icon || 'mdi-star-outline'" :color="isDark ? 'blue-lighten-2' : 'blue-darken-1'" size="26" />
            </div>
            <h3 :class="['text-subtitle-1 font-weight-bold mb-2 group-hover:text-blue-500 transition-colors', isDark ? 'text-white' : 'text-slate-900']">
              {{ feature.title }}
            </h3>
            <p :class="['text-body-2 leading-relaxed', isDark ? 'text-slate-400' : 'text-slate-600']">
              {{ feature.description }}
            </p>
          </div>
        </div>

      </div>
    </section>

    <!-- ===== TESTIMONIOS ===== -->
    <section
      v-if="sec.testimonials"
      :class="['py-20 transition-colors relative overflow-hidden', isDark ? 'bg-slate-950' : 'bg-slate-50/80']"
    >
      <!-- Section Background -->
      <div class="section-bg absolute inset-0 pointer-events-none z-0">
        <img src="/landing/bg-testimonials.jpg" alt="" class="w-full h-full object-cover" draggable="false" />
        <div class="absolute inset-0" :class="isDark ? 'bg-slate-950/70' : 'bg-slate-50/70'"></div>
      </div>
      <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16 reveal-on-scroll">
          <v-chip color="blue" variant="tonal" class="font-weight-bold mb-3 px-4">
            CLIENTES SATISFECHOS
          </v-chip>
          <h2 :class="['text-h4 text-sm-h3 font-weight-bold tracking-tight mb-4', isDark ? 'text-white' : 'text-slate-900']">
            Lo que dicen nuestros clientes
          </h2>
          <p :class="['text-body-1', isDark ? 'text-slate-400' : 'text-slate-600']">
            Administradores y juntas de condominio en todo el país confían en AZPRO para gestionar sus comunidades.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div
            v-for="(test, tIdx) in testimonialsList"
            :key="tIdx"
            :class="['testimonial-card p-6 rounded-2xl border relative reveal-on-scroll', isDark ? 'bg-slate-900 border-slate-800 shadow-xl' : 'bg-white border-slate-200/90 shadow-md shadow-slate-200/50 hover:shadow-xl']"
            :style="`--reveal-delay: ${tIdx * 120}ms`"
          >
            <!-- Quote icon -->
            <div :class="['absolute -top-3 left-6 w-7 h-7 rounded-full d-flex align-center justify-center shadow', isDark ? 'bg-blue-600' : 'bg-blue-500']">
              <v-icon icon="mdi-format-quote-open" color="white" size="14" />
            </div>

            <!-- Stars -->
            <div class="d-flex gap-1 mb-4 mt-2">
              <v-icon
                v-for="s in (test.calificacion || 5)"
                :key="s"
                icon="mdi-star"
                color="amber-darken-1"
                size="16"
              />
            </div>

            <!-- Quote text -->
            <p :class="['text-body-2 leading-relaxed mb-5 italic', isDark ? 'text-slate-300' : 'text-slate-700']">
              "{{ test.cita }}"
            </p>

            <!-- Author -->
            <div class="d-flex align-center gap-3">
              <v-avatar :color="test.color || 'blue'" size="40">
                <span class="text-white font-weight-black text-subtitle-2">{{ test.inicial }}</span>
              </v-avatar>
              <div>
                <div :class="['text-body-2 font-weight-bold', isDark ? 'text-white' : 'text-slate-900']">{{ test.nombre }}</div>
                <div :class="['text-caption', isDark ? 'text-slate-400' : 'text-slate-500']">{{ test.cargo }}</div>
                <div class="text-caption text-blue-500 font-weight-medium">{{ test.condominio }}</div>
              </div>
            </div>

            <!-- Plan chip -->
            <div class="mt-4">
              <v-chip color="blue" size="x-small" variant="tonal" class="font-weight-bold">
                Plan {{ test.plan }}
              </v-chip>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== ESTADÍSTICAS ===== -->
    <section
      v-if="sec.stats"
      ref="statsSection"
      :class="['py-16 border-t border-b relative overflow-hidden transition-colors', isDark ? 'bg-gradient-to-r from-blue-950 via-slate-900 to-indigo-950 border-slate-800' : 'bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 border-blue-800 text-white']"
    >
      <!-- Section Background -->
      <div class="section-bg absolute inset-0 pointer-events-none z-0">
        <img src="/landing/bg-stats.jpg" alt="" class="w-full h-full object-cover" draggable="false" />
        <div class="absolute inset-0 bg-gradient-to-r from-blue-950/70 via-slate-900/70 to-indigo-950/70"></div>
      </div>
      <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
          <div class="reveal-on-scroll" v-for="(stat, stIdx) in animatedStats" :key="stIdx" :style="`--reveal-delay: ${stIdx * 100}ms`">
            <div class="text-h4 text-sm-h3 font-weight-black text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-sky-300">
              {{ stat.prefix }}{{ stat.current }}{{ stat.suffix }}
            </div>
            <div class="text-caption text-sm-body-2 text-slate-300 font-weight-medium mt-1">{{ stat.label }}</div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== OTROS PRODUCTOS ===== -->
    <section v-if="sec.products" id="productos" :class="['py-20 transition-colors relative overflow-hidden', isDark ? 'bg-slate-950' : 'bg-slate-50/80']">
      <!-- Section Background -->
      <div class="section-bg absolute inset-0 pointer-events-none z-0">
        <img src="/landing/bg-products.jpg" alt="" class="w-full h-full object-cover" draggable="false" />
        <div class="absolute inset-0" :class="isDark ? 'bg-slate-950/70' : 'bg-slate-50/70'"></div>
      </div>
      <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center max-w-3xl mx-auto mb-16 reveal-on-scroll">
          <v-chip color="blue-darken-1" variant="tonal" class="font-weight-bold mb-3 px-4">
            SERVICIOS TECNOLÓGICOS INTEGRALES
          </v-chip>
          <h2 :class="['text-h4 text-sm-h3 font-weight-bold tracking-tight mb-4', isDark ? 'text-white' : 'text-slate-900']">
            Catálogo de Productos y Soluciones AZPRO
          </h2>
          <p :class="['text-body-1', isDark ? 'text-slate-400' : 'text-slate-600']">
            Ofrecemos desarrollos tecnológicos a medida, sistemas de control de acceso IoT y soporte profesional para llevar su empresa o inmueble al siguiente nivel.
          </p>
        </div>

        <!-- Products Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <div
            v-for="(product, idx) in productsList"
            :key="idx"
            :class="['product-card p-8 rounded-3xl border transition-all duration-300 reveal-on-scroll', isDark ? 'bg-gradient-to-br from-slate-900 to-slate-900/80 border-slate-800 hover:border-blue-500/50' : 'bg-white border-slate-200/90 shadow-md shadow-slate-200/50 hover:border-blue-500 hover:shadow-xl']"
            :style="`--reveal-delay: ${(idx % 2) * 120}ms`"
          >
            <div class="d-flex justify-space-between align-start mb-6">
              <div :class="['w-14 h-14 rounded-2xl border d-flex align-center justify-center', isDark ? 'bg-blue-950 border-blue-800/80' : 'bg-blue-50 border-blue-200']">
                <v-icon :icon="product.icon || 'mdi-cube-outline'" :color="isDark ? 'blue-lighten-2' : 'blue-darken-1'" size="30" />
              </div>
              <v-chip color="blue" size="small" variant="flat" class="font-weight-bold text-uppercase text-white">
                {{ product.badge || 'Solución AZPRO' }}
              </v-chip>
            </div>

            <h3 :class="['text-h5 font-weight-bold mb-3', isDark ? 'text-white' : 'text-slate-900']">
              {{ product.title }}
            </h3>

            <p :class="['text-body-1 mb-6 leading-relaxed', isDark ? 'text-slate-400' : 'text-slate-600']">
              {{ product.description }}
            </p>

            <v-btn
              color="blue"
              variant="flat"
              class="rounded-xl font-weight-bold text-none px-6 text-white bg-blue-600 hover:bg-blue-500 shadow-md"
              append-icon="mdi-arrow-right"
              @click="openWhatsAppContact(product.title)"
            >
              {{ product.cta_text || 'Solicitar Cotización' }}
            </v-btn>
          </div>
        </div>

      </div>
    </section>

    <!-- ===== PLANES ===== -->
    <section v-if="sec.planes" id="planes" :class="['py-20 transition-colors relative overflow-hidden', isDark ? 'bg-slate-900' : 'bg-slate-50/60']">
      <!-- Section Background -->
      <div class="section-bg absolute inset-0 pointer-events-none z-0">
        <img src="/landing/bg-planes.jpg" alt="" class="w-full h-full object-cover" draggable="false" />
        <div class="absolute inset-0" :class="isDark ? 'bg-slate-900/70' : 'bg-white/70'"></div>
      </div>
      <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16 reveal-on-scroll">
          <v-chip color="blue" variant="tonal" class="font-weight-bold mb-3 px-4">
            SUSCRIPCIÓN AZ PRO
          </v-chip>
          <h2 :class="['text-h4 text-sm-h3 font-weight-bold tracking-tight mb-4', isDark ? 'text-white' : 'text-slate-900']">
            Planes adaptados a la escala de su condominio
          </h2>
          <p :class="['text-body-1', isDark ? 'text-slate-400' : 'text-slate-600']">
            Sin costos ocultos ni permanencia obligatoria. Elija la alternativa que mejor responda a las dimensiones de su comunidad.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div
            v-for="(plan, pIdx) in (landingData.planes && landingData.planes.length ? landingData.planes : fallbackPlanes)"
            :key="plan.id || pIdx"
            :class="[
              'plan-card p-8 rounded-3xl border relative d-flex flex-column justify-space-between transition-all duration-300 reveal-on-scroll',
              plan.destacado
                ? (isDark ? 'bg-gradient-to-b from-blue-950/90 to-slate-950 border-2 border-blue-500 shadow-2xl shadow-blue-500/20' : 'bg-gradient-to-b from-blue-50/80 to-white border-2 border-blue-500 shadow-xl shadow-blue-500/10')
                : (isDark ? 'bg-slate-950 border-slate-800 shadow-xl' : 'bg-white border-slate-200/90 shadow-md shadow-slate-200/50 hover:shadow-xl')
            ]"
            :style="`--reveal-delay: ${pIdx * 120}ms`"
          >
            <!-- Badge Destacado -->
            <div v-if="plan.badge" class="absolute -top-4 left-1/2 -translate-x-1/2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-xs font-weight-black px-4 py-1 rounded-full uppercase tracking-wider shadow">
              {{ plan.badge }}
            </div>

            <div>
              <div class="text-subtitle-1 font-weight-bold text-blue-600 mb-1" :class="{ 'mt-2': plan.badge }">
                {{ (plan.nombre || 'PLAN').toUpperCase() }}
              </div>
              <div :class="['text-h5 font-weight-bold mb-2', isDark ? 'text-white' : 'text-slate-900']">
                {{ plan.max_apartamentos ? `Hasta ${plan.max_apartamentos} Aptos` : (plan.subtitulo || plan.nombre) }}
              </div>

              <div v-if="plan.precio_mensual" class="text-h4 font-weight-black text-blue-500 mb-4">
                ${{ Number(plan.precio_mensual).toFixed(2) }} <span class="text-caption text-slate-400 font-weight-regular">/ mes</span>
              </div>

              <div :class="['text-body-2 mb-6', isDark ? 'text-slate-400' : 'text-slate-600']">
                {{ plan.descripcion }}
              </div>

              <ul :class="['space-y-3 mb-8 text-sm', isDark ? 'text-slate-300' : 'text-slate-700']">
                <li v-for="(feat, fIdx) in (plan.caracteristicas || [])" :key="fIdx" class="d-flex align-center gap-2">
                  <v-icon icon="mdi-check" color="blue-darken-1" size="18" />
                  <span>{{ feat }}</span>
                </li>
              </ul>
            </div>

            <v-btn
              block
              :variant="plan.destacado ? 'flat' : 'outlined'"
              color="blue"
              :class="[
                'rounded-xl font-weight-bold',
                plan.destacado
                  ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg'
                  : (isDark ? 'text-white border-blue-500 hover:bg-blue-600/30' : 'text-blue-700 border-blue-500 hover:bg-blue-50')
              ]"
              @click="openWhatsAppContact(`Plan ${plan.nombre}`)"
            >
              {{ plan.destacado ? 'Solicitar Demostración' : 'Consultar Plan' }}
            </v-btn>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== FOOTER / CONTACTO ===== -->
    <footer id="contacto" :class="['py-16 border-t transition-colors relative overflow-hidden', isDark ? 'bg-slate-950 border-slate-800' : 'bg-slate-900 border-slate-800 text-white']">
      <!-- Section Background -->
      <div class="section-bg absolute inset-0 pointer-events-none z-0">
        <img src="/landing/bg-footer.jpg" alt="" class="w-full h-full object-cover" draggable="false" />
        <div class="absolute inset-0" :class="isDark ? 'bg-slate-950/70' : 'bg-slate-900/70'"></div>
      </div>
      <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-12 mb-12">

          <div class="md:col-span-5">
            <div class="d-flex align-center gap-3 mb-4">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 d-flex align-center justify-center">
                <v-icon icon="mdi-office-building-cog" color="white" size="24" />
              </div>
              <div class="text-h6 font-weight-bold text-white">
                Sistema <span class="text-blue-400">AZPRO</span>
              </div>
            </div>
            <p class="text-body-2 text-slate-400 mb-6 leading-relaxed max-w-md">
              Sistema Inteligente de Gestión de Condominios AZPRO. Innovación, transparencia y eficiencia tecnológica al servicio de administradores y residentes.
            </p>
            <div class="d-flex gap-3">
              <v-btn icon="mdi-whatsapp" variant="flat" color="blue-darken-2" size="small" class="text-white shadow-sm" @click="openWhatsAppContact('General')" />
              <v-btn icon="mdi-email-outline" variant="flat" color="blue-darken-2" size="small" class="text-white shadow-sm" :href="'mailto:' + (landingData.contact?.email || 'contacto@azpro.com')" />
              <v-btn icon="mdi-phone-outline" variant="flat" color="indigo-darken-2" size="small" class="text-white shadow-sm" :href="'tel:' + (landingData.contact?.phone || '+584120000000')" />
            </div>
          </div>

          <div class="md:col-span-3">
            <div class="text-subtitle-2 font-weight-bold text-white mb-4 uppercase tracking-wider">Enlaces Rápidos</div>
            <ul class="space-y-2 text-body-2 text-slate-400">
              <li><a href="#hero" class="hover:text-blue-400 transition-colors">Inicio</a></li>
              <li v-if="sec.how_it_works"><a href="#como-funciona" class="hover:text-blue-400 transition-colors">Cómo Funciona</a></li>
              <li v-if="sec.features"><a href="#caracteristicas" class="hover:text-blue-400 transition-colors">Características del Sistema</a></li>
              <li v-if="sec.products"><a href="#productos" class="hover:text-blue-400 transition-colors">Otros Productos y Servicios</a></li>
              <li v-if="sec.planes"><a href="#planes" class="hover:text-blue-400 transition-colors">Planes SaaS</a></li>
              <li><router-link to="/login" class="hover:text-blue-400 transition-colors">Acceso de Usuarios</router-link></li>
            </ul>
          </div>

          <div class="md:col-span-4">
            <div class="text-subtitle-2 font-weight-bold text-white mb-4 uppercase tracking-wider">Contacto & Atención</div>
            <div class="space-y-3 text-body-2 text-slate-400">
              <div class="d-flex align-center gap-3">
                <v-icon icon="mdi-whatsapp" color="blue-lighten-2" size="20" />
                <span>{{ landingData.contact?.whatsapp || '+58 412-0000000' }}</span>
              </div>
              <div class="d-flex align-center gap-3">
                <v-icon icon="mdi-email-outline" color="blue-lighten-2" size="20" />
                <span>{{ landingData.contact?.email || 'contacto@azpro.com' }}</span>
              </div>
              <div class="d-flex align-center gap-3">
                <v-icon icon="mdi-map-marker-outline" color="indigo-lighten-2" size="20" />
                <span>{{ landingData.contact?.address || 'Caracas, Venezuela' }}</span>
              </div>
            </div>
          </div>

        </div>

        <div class="pt-8 border-t border-slate-800 d-flex flex-column flex-sm-row justify-space-between align-center text-caption text-slate-500 gap-4">
          <div>
            © {{ new Date().getFullYear() }} Sistema Inteligente de Gestión de Condominios AZPRO. Todos los derechos reservados.
          </div>
          <div class="d-flex align-center gap-2">
            <v-badge dot color="blue-lighten-2">
              <span class="text-slate-400">PWA Ready</span>
            </v-badge>
          </div>
        </div>
      </div>
    </footer>

    <!-- ===== BOTÓN FLOTANTE WHATSAPP (solo mobile) ===== -->
    <transition name="fab-slide">
      <div
        v-if="sec.whatsapp_float && showFab"
        class="whatsapp-fab d-flex d-md-none"
        @click="openWhatsAppContact('Botón Flotante')"
        role="button"
        aria-label="Contactar por WhatsApp"
      >
        <v-icon icon="mdi-whatsapp" color="white" size="28" />
        <span class="whatsapp-fab-pulse"></span>
      </div>
    </transition>

  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../../store/auth';
import { useTheme } from '../../composables/useTheme';

const authStore = useAuthStore();
const { isDark, toggleTheme } = useTheme();

// ===== DATA =====
const fallbackPlanes = ref([
  { id: 1, nombre: 'Plan Básico', max_apartamentos: 30, precio_mensual: 15.00, descripcion: 'Ideal para edificios pequeños y juntas de condominio residenciales.', caracteristicas: ['Hasta 30 Apartamentos', 'Tasa BCV diaria automatizada', 'Recibos en PDF con QR', 'Notificaciones por correo'] },
  { id: 2, nombre: 'Plan Profesional', max_apartamentos: 120, precio_mensual: 35.00, badge: 'MÁS POPULAR', destacado: true, descripcion: 'Para torres de mediano tamaño y administradoras profesionales.', caracteristicas: ['31 a 120 Apartamentos', 'Todo lo del Plan Básico', 'Avisos masivos por WhatsApp', 'Control de acceso de visitantes', 'Reserva de áreas comunes'] },
  { id: 3, nombre: 'Plan Enterprise', max_apartamentos: 999, precio_mensual: 75.00, badge: 'CORPORATIVO', descripcion: 'Para grandes complejos residenciales, centros comerciales y empresas administradoras.', caracteristicas: ['Multi-Torres & Ilimitado', 'Multi-edificio y roles avanzados', 'Integración IoT para portones', 'Soporte dedicado 24/7'] },
]);

const defaultFeatures = [
  { icon: 'mdi-currency-usd-circle-outline', title: 'Facturación Multimoneda & Tasa BCV', description: 'Cálculos automáticos en Bolívares y Dólares con sincronización en tiempo real de la tasa oficial BCV, congelamiento de tasa por mora e intereses.' },
  { icon: 'mdi-calculator-variant-outline', title: 'Gestión de Alícuotas Dinámicas', description: 'Distribución equitativa o por metros cuadrados en hasta 12 tipos de alícuotas personalizables por apartamento y torre.' },
  { icon: 'mdi-shield-account-outline', title: 'Control de Visitantes & Acceso', description: 'Registro digital ultrarrápido con códigos QR, captura de placas de vehículos, fotografías de cédula e historial de accesos en tiempo real.' },
  { icon: 'mdi-file-document-check-outline', title: 'Recibos & Reportes PDF con QR', description: 'Generación instantánea de avisos de cobro, comprobantes con QR de verificación pública de autenticidad y contabilidad transparente.' },
  { icon: 'mdi-whatsapp', title: 'Notificaciones & Cartelera Digital', description: 'Envío automático de recordatorios de pago por WhatsApp, comunicados oficiales por correo masivo y encuestas comunitarias.' },
  { icon: 'mdi-calendar-multiselect', title: 'Reserva de Áreas Comunes', description: 'Gestión inteligente para alquiler de salones, canchas y parilleras con validación automatizada de solvencia de propietarios.' },
];

const defaultProducts = [
  { badge: 'Desarrollo de Software', icon: 'mdi-laptop-code', title: 'Software a Medida & Portales Web', description: 'Diseñamos y desarrollamos sistemas web empresariales, plataformas SaaS y aplicaciones móviles personalizadas a la medida de su organización.', cta_text: 'Solicitar Cotización' },
  { badge: 'Seguridad & IoT', icon: 'mdi-door-sliding-lock', title: 'Sistemas de Control de Acceso IoT', description: 'Integración de portones automatizados, talanqueras inteligentes, tarjetas RFID, biometría y reconocimiento facial para condominios.', cta_text: 'Más Información' },
  { badge: 'Asesoría Profesional', icon: 'mdi-scale-balance', title: 'Consultoría Legal y Contable Condominial', description: 'Asesoría especializada en leyes de propiedad horizontal, cobranza extrajudicial de morosidad y auditorías financieras independientes.', cta_text: 'Consultar Expertos' },
  { badge: 'Infraestructura', icon: 'mdi-headset', title: 'Soporte Técnico & Mantenimiento 24/7', description: 'Planes de asistencia técnica integral para la infraestructura tecnológica, redes, cámaras CCTV e interconectividad de sus edificios.', cta_text: 'Contratar Soporte' },
];

const defaultTestimonials = [
  { nombre: 'Lic. María Rodríguez', cargo: 'Administradora de Condominio', condominio: 'Torres El Pinar', plan: 'Profesional', cita: 'Con AZPRO automatizamos la cobranza y redujimos la morosidad en un 35%. El sistema es intuitivo y los residentes lo adoran.', calificacion: 5, inicial: 'M', color: 'blue' },
  { nombre: 'Ing. Carlos Méndez', cargo: 'Jefe de Administración', condominio: 'Residencias Los Aviadores', plan: 'Enterprise', cita: 'El módulo de control de visitantes con QR es espectacular. Eliminamos los registros manuales y ahorramos horas al mes.', calificacion: 5, inicial: 'C', color: 'indigo' },
  { nombre: 'Sr. José Pérez', cargo: 'Administrador Propietario', condominio: 'Edificio Parque Central', plan: 'Básico', cita: 'La tasa BCV automática nos ahorra horas de trabajo cada semana. Ya no hay errores de cálculo ni reclamos de propietarios.', calificacion: 5, inicial: 'J', color: 'teal' },
];

const defaultTrustLogos = [
  { nombre: 'Torres El Pinar' },
  { nombre: 'Res. Los Aviadores' },
  { nombre: 'Edificio Parque Central' },
  { nombre: 'Torres Las Mercedes' },
  { nombre: 'Res. La Castellana' },
  { nombre: 'Conjunto Los Chorros' },
  { nombre: 'Torres Caribe' },
  { nombre: 'Res. Altamira' },
];

const defaultHowItWorks = [
  { numero: '01', titulo: 'Registre su Condominio', descripcion: 'Configure su torre, apartamentos, propietarios y áreas comunes en minutos. Nuestro asistente de onboarding lo guía paso a paso.', icon: 'mdi-office-building-plus-outline' },
  { numero: '02', titulo: 'Personalice y Configure', descripcion: 'Defina alícuotas, tipos de cobro, bancos receptores de pago, plantillas de recibos y configure las notificaciones automáticas por WhatsApp.', icon: 'mdi-tune-vertical' },
  { numero: '03', titulo: 'Gestione sin Complicaciones', descripcion: 'Emita recibos PDF con QR, registre pagos, controle visitantes, gestione reservas y acceda a reportes financieros en tiempo real desde cualquier dispositivo.', icon: 'mdi-view-dashboard-outline' },
];

const howItWorksImages = [
  '/landing/step-1-registro.jpg',
  '/landing/step-2-configuracion.jpg',
  '/landing/step-3-gestion.jpg',
];

const landingData = ref({
  hero: {},
  app_features: [],
  extra_products: [],
  stats: {},
  contact: {},
  sections: {},
  testimonials: [],
  how_it_works: [],
  trust_logos: [],
});

// Computed Lists with Fallback Protection
const featuresList = computed(() => {
  return (landingData.value.app_features && landingData.value.app_features.length > 0)
    ? landingData.value.app_features
    : defaultFeatures;
});

const productsList = computed(() => {
  return (landingData.value.extra_products && landingData.value.extra_products.length > 0)
    ? landingData.value.extra_products
    : defaultProducts;
});

const testimonialsList = computed(() => {
  return (landingData.value.testimonials && landingData.value.testimonials.length > 0)
    ? landingData.value.testimonials
    : defaultTestimonials;
});

const howItWorksList = computed(() => {
  return (landingData.value.how_it_works && landingData.value.how_it_works.length > 0)
    ? landingData.value.how_it_works
    : defaultHowItWorks;
});

const trustLogosList = computed(() => {
  return (landingData.value.trust_logos && landingData.value.trust_logos.length > 0)
    ? landingData.value.trust_logos
    : defaultTrustLogos;
});

// ===== SECTION VISIBILITY =====
const sec = computed(() => {
  const s = landingData.value.sections || {};
  return {
    trust_logos:    s.trust_logos    ?? false,
    how_it_works:   s.how_it_works   ?? false,
    features:       s.features       ?? true,
    testimonials:   s.testimonials   ?? false,
    stats:          s.stats          ?? true,
    products:       s.products       ?? true,
    planes:         s.planes         ?? true,
    whatsapp_float: s.whatsapp_float ?? true,
  };
});

// ===== ANIMATED COUNTERS =====
const statsSection = ref(null);
const statsAnimated = ref(false);

const parseStatValue = (rawVal) => {
  const str = String(rawVal || '0');
  const prefix = str.startsWith('+') ? '+' : '';
  const suffix = str.endsWith('%') ? '%' : '';
  const cleaned = str.replace(/[^0-9.,]/g, '').replace(',', '');
  return { prefix, suffix, raw: parseFloat(cleaned) || 0, display: str };
};

const animatedStats = computed(() => {
  const statsData = landingData.value.stats || {};
  return [
    { ...parseStatValue(statsData.condominios || '+50'), label: 'Condominios y Torres' },
    { ...parseStatValue(statsData.propietarios || '+12000'), label: 'Residentes & Propietarios' },
    { ...parseStatValue(statsData.recibos_procesados || '+150000'), label: 'Recibos Procesados' },
    { ...parseStatValue(statsData.satisfaccion || '99.4%'), label: 'Satisfacción del Cliente' },
  ].map((s) => ({ ...s, current: statsAnimated.value ? formatNum(s.raw) : '0' }));
});

const formatNum = (n) => {
  if (n >= 1000) return (n / 1000).toFixed(n % 1000 === 0 ? 0 : 1) + 'K';
  return n.toFixed(n % 1 !== 0 ? 1 : 0);
};

const runCounters = () => {
  if (statsAnimated.value) return;
  const statsData = landingData.value.stats || {};
  const items = [
    { ...parseStatValue(statsData.condominios || '+50') },
    { ...parseStatValue(statsData.propietarios || '+12000') },
    { ...parseStatValue(statsData.recibos_procesados || '+150000') },
    { ...parseStatValue(statsData.satisfaccion || '99.4%') },
  ];

  const duration = 1800;
  const startTime = performance.now();

  const tick = (now) => {
    const elapsed = now - startTime;
    const progress = Math.min(elapsed / duration, 1);
    // Ease-out cubic
    const eased = 1 - Math.pow(1 - progress, 3);

    items.forEach((item, idx) => {
      const el = document.querySelector(`[data-counter="${idx}"]`);
      if (el) {
        el.textContent = item.prefix + formatNum(Math.round(item.raw * eased)) + item.suffix;
      }
    });

    if (progress < 1) {
      requestAnimationFrame(tick);
    } else {
      statsAnimated.value = true;
    }
  };

  requestAnimationFrame(tick);
};

// ===== PWA =====
const deferredPrompt = ref(null);
const showFab = ref(false);

// ===== HERO PARALLAX =====
const heroSection = ref(null);
const mouseX = ref(0);
const mouseY = ref(0);
const targetX = ref(0);
const targetY = ref(0);
let parallaxRaf = null;

const parallaxStyle = (strength) => ({
  transform: `translate(${mouseX.value * strength}px, ${mouseY.value * strength}px)`,
});

const onHeroMouseMove = (e) => {
  if (!heroSection.value) return;
  const rect = heroSection.value.getBoundingClientRect();
  targetX.value = e.clientX - rect.left - rect.width  / 2;
  targetY.value = e.clientY - rect.top  - rect.height / 2;
};

const onHeroMouseLeave = () => {
  targetX.value = 0;
  targetY.value = 0;
};

// Smooth lerp loop for parallax
const startParallaxLoop = () => {
  const lerp = (a, b, t) => a + (b - a) * t;
  const tick = () => {
    mouseX.value = lerp(mouseX.value, targetX.value, 0.07);
    mouseY.value = lerp(mouseY.value, targetY.value, 0.07);
    parallaxRaf = requestAnimationFrame(tick);
  };
  parallaxRaf = requestAnimationFrame(tick);
};

// ===== API =====
const fetchLandingData = async () => {
  try {
    const { data } = await axios.get('/landing-content');
    if (data.success) {
      landingData.value = data.data;
    }
  } catch (e) {
    console.error('Error al cargar datos de la landing page:', e);
  }
};

// ===== NAVIGATION =====
const getDashboardRoute = () => {
  if (authStore.isMaster) return '/dashboard/master';
  if (authStore.isAdmin || authStore.isSupervisor || authStore.isAnalista) return '/dashboard/admin';
  return '/dashboard/owner';
};

const scrollTo = (id) => {
  const el = document.getElementById(id);
  if (el) el.scrollIntoView({ behavior: 'smooth' });
};

const openWhatsAppContact = (subject = '') => {
  const rawNumber = (landingData.value.contact?.whatsapp || '584120000000').replace(/[^0-9]/g, '');
  const defaultMsg = landingData.value.contact?.whatsapp_message || 'Hola, me interesa solicitar información sobre el Sistema AZPRO.';
  const text = encodeURIComponent(`${defaultMsg} [Interés: ${subject}]`);
  window.open(`https://wa.me/${rawNumber}?text=${text}`, '_blank');
};

const installPWA = async () => {
  if (!deferredPrompt.value) return;
  deferredPrompt.value.prompt();
  const { outcome } = await deferredPrompt.value.userChoice;
  if (outcome === 'accepted') deferredPrompt.value = null;
};

// ===== SCROLL REVEAL (IntersectionObserver) =====
const initScrollReveal = () => {
  const elements = document.querySelectorAll('.reveal-on-scroll');
  if (!elements.length) return;

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const delay = entry.target.style.getPropertyValue('--reveal-delay') || '0ms';
          setTimeout(() => {
            entry.target.classList.add('revealed');
          }, parseInt(delay) || 0);
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.12 }
  );

  elements.forEach((el) => observer.observe(el));
};

// ===== STATS COUNTER (IntersectionObserver) =====
const initStatsObserver = () => {
  if (!statsSection.value) return;
  const observer = new IntersectionObserver(
    (entries) => {
      if (entries[0].isIntersecting) {
        runCounters();
        observer.disconnect();
      }
    },
    { threshold: 0.3 }
  );
  observer.observe(statsSection.value);
};

// ===== FAB VISIBILITY ON SCROLL =====
const initFabScroll = () => {
  const onScroll = () => {
    showFab.value = window.scrollY > 400;
  };
  window.addEventListener('scroll', onScroll, { passive: true });
};

onMounted(async () => {
  await fetchLandingData();

  // Small tick to let Vue render new sections before observing
  setTimeout(() => {
    initScrollReveal();
    initStatsObserver();
  }, 100);

  initFabScroll();
  startParallaxLoop();

  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt.value = e;
  });
});

onUnmounted(() => {
  if (parallaxRaf) cancelAnimationFrame(parallaxRaf);
});
</script>

<style scoped>
/* ===== BASE ===== */
.landing-container {
  scroll-behavior: smooth;
}
.sticky-header {
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
}

/* ===== SCROLL REVEAL (Safe & Guaranteed Visibility) ===== */
.reveal-on-scroll {
  opacity: 1;
  transform: translateY(0);
}

/* ===== HERO ANIMATIONS ===== */
.hero-cta-pulse {
  animation: ctaPulse 3s ease-in-out infinite;
}
@keyframes ctaPulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
  50%       { box-shadow: 0 0 0 12px rgba(59, 130, 246, 0); }
}

.hero-progress-bar {
  width: 0;
  animation: progressFill 1.8s ease-out 0.6s forwards;
}
@keyframes progressFill {
  to { width: 94.8%; }
}

/* ===== CARDS HOVER ===== */
.feature-card:hover {
  transform: translateY(-4px);
}
.product-card:hover {
  transform: translateY(-4px);
}
.how-it-works-card {
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.how-it-works-card:hover {
  transform: translateY(-4px);
}
.testimonial-card {
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.testimonial-card:hover {
  transform: translateY(-4px);
}
.plan-card {
  transition: all 0.3s ease;
}
.plan-card:hover {
  transform: translateY(-6px);
}

/* ===== TRUST LOGOS MARQUEE ===== */
.trust-marquee-track {
  overflow: hidden;
  width: 100%;
}
.trust-marquee-content {
  display: inline-flex;
  animation: marqueeScroll 28s linear infinite;
  white-space: nowrap;
}
.trust-marquee-track:hover .trust-marquee-content {
  animation-play-state: paused;
}
@keyframes marqueeScroll {
  0%   { transform: translateX(0); }
  100% { transform: translateX(-50%); }
}

/* ===== WHATSAPP FLOATING BUTTON ===== */
.whatsapp-fab {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 999;
  width: 58px;
  height: 58px;
  border-radius: 50%;
  background: linear-gradient(135deg, #25d366, #128c7e);
  box-shadow: 0 4px 20px rgba(37, 211, 102, 0.45);
  cursor: pointer;
  align-items: center;
  justify-content: center;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.whatsapp-fab:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 28px rgba(37, 211, 102, 0.6);
}
.whatsapp-fab-pulse {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  background: rgba(37, 211, 102, 0.4);
  animation: fabPulse 2.5s ease-out infinite;
}
@keyframes fabPulse {
  0%   { transform: scale(1);   opacity: 0.8; }
  70%  { transform: scale(1.5); opacity: 0; }
  100% { transform: scale(1.5); opacity: 0; }
}

/* ===== FAB TRANSITION ===== */
.fab-slide-enter-active,
.fab-slide-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.fab-slide-enter-from,
.fab-slide-leave-to {
  opacity: 0;
  transform: translateY(20px);
}

/* ===== SECTION BACKGROUND IMAGES ===== */
.section-bg {
  position: absolute;
  inset: 0;
  overflow: hidden;
  pointer-events: none;
  z-index: 0;
}
.section-bg img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* ===== HERO INTERACTIVE BACKGROUND ===== */
.hero-section {
  background: var(--hero-bg, #0f172a);
  min-height: 600px;
}

.hero-bg-image {
  will-change: transform;
  scale: 1.08; /* slight scale so parallax doesn't show edges */
}

/* ── Animated floating Orbs ── */
.hero-orb {
  filter: blur(80px);
  will-change: transform;
}
.hero-orb-1 {
  width: 520px; height: 520px;
  top: -120px; left: -120px;
  animation: orbFloat1 9s ease-in-out infinite;
}
.hero-orb-2 {
  width: 440px; height: 440px;
  top: 10%; right: -140px;
  animation: orbFloat2 11s ease-in-out infinite;
}
.hero-orb-3 {
  width: 360px; height: 360px;
  bottom: -80px; left: 35%;
  animation: orbFloat3 13s ease-in-out infinite;
}
@keyframes orbFloat1 {
  0%, 100% { transform: translate(0px, 0px) scale(1); }
  33%       { transform: translate(30px, -20px) scale(1.05); }
  66%       { transform: translate(-15px, 25px) scale(0.97); }
}
@keyframes orbFloat2 {
  0%, 100% { transform: translate(0px, 0px) scale(1); }
  40%       { transform: translate(-25px, 20px) scale(1.04); }
  70%       { transform: translate(20px, -15px) scale(0.98); }
}
@keyframes orbFloat3 {
  0%, 100% { transform: translate(0px, 0px) scale(1); }
  50%       { transform: translate(20px, -30px) scale(1.06); }
}

/* ── Floating Geometric Shapes ── */
.hero-shape {
  will-change: transform;
}
.hero-shape-sq-1 {
  width: 90px; height: 90px;
  top: 18%; right: 38%;
  transform: rotate(20deg);
  animation: shapeFloat1 7s ease-in-out infinite;
}
.hero-shape-ring {
  width: 140px; height: 140px;
  bottom: 22%; left: 12%;
  animation: shapeFloat2 8.5s ease-in-out infinite;
}
.hero-shape-sq-2 {
  width: 48px; height: 48px;
  top: 60%; right: 22%;
  transform: rotate(12deg);
  animation: shapeFloat3 6s ease-in-out infinite;
}
.hero-shape-tri {
  width: 64px; height: 64px;
  top: 30%; left: 8%;
  animation: shapeFloat4 10s ease-in-out infinite;
}
@keyframes shapeFloat1 {
  0%, 100% { transform: rotate(20deg) translateY(0);   }
  50%       { transform: rotate(28deg) translateY(-14px); }
}
@keyframes shapeFloat2 {
  0%, 100% { transform: translateY(0)   rotate(0deg);   }
  50%       { transform: translateY(-18px) rotate(8deg); }
}
@keyframes shapeFloat3 {
  0%, 100% { transform: rotate(12deg) translateY(0);    }
  50%       { transform: rotate(-4deg) translateY(-10px); }
}
@keyframes shapeFloat4 {
  0%, 100% { transform: translateY(0);   }
  50%       { transform: translateY(16px); }
}

/* ── Dot Grid Pattern ── */
.hero-dot-grid {
  background-image: radial-gradient(circle, #60a5fa 1px, transparent 1px);
  background-size: 36px 36px;
}
</style>
