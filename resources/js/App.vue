<template>
  <v-app :theme="themeName">
    <component :is="isAppLayout ? DefaultLayout : 'div'">
      <router-view />
    </component>
  </v-app>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from './store/auth';
import DefaultLayout from './layouts/DefaultLayout.vue';
import { useTheme } from './composables/useTheme';

const route = useRoute();
const authStore = useAuthStore();
const { themeName, syncTheme } = useTheme();

const isAppLayout = computed(() => {
    if (!authStore.isAuthenticated) return false;
    if (route.meta?.public || route.meta?.guestOnly) return false;
    if (route.name === 'LandingPage' || route.path === '/') return false;
    return true;
});

onMounted(() => {
    syncTheme();
});
</script>
