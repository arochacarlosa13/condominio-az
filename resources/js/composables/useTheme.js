import { ref, computed } from 'vue';
import { useTheme as useVuetifyTheme } from 'vuetify';

// Estado reactivo global persistente
const saved = localStorage.getItem('appTheme');
const isDark = ref(saved ? saved === 'dark' : true);

export function useTheme() {
    let vuetifyTheme = null;
    try {
        vuetifyTheme = useVuetifyTheme();
    } catch (e) {
        // En caso de ejecutarse fuera de provisión de vuetify
    }

    const themeName = computed(() => (isDark.value ? 'customDarkTheme' : 'customLightTheme'));

    const toggleTheme = () => {
        isDark.value = !isDark.value;
        const mode = isDark.value ? 'dark' : 'light';
        localStorage.setItem('appTheme', mode);

        if (vuetifyTheme && vuetifyTheme.global) {
            vuetifyTheme.global.name.value = isDark.value ? 'customDarkTheme' : 'customLightTheme';
        }
    };

    const syncTheme = () => {
        const currentSaved = localStorage.getItem('appTheme');
        isDark.value = currentSaved ? currentSaved === 'dark' : true;
        if (vuetifyTheme && vuetifyTheme.global) {
            vuetifyTheme.global.name.value = isDark.value ? 'customDarkTheme' : 'customLightTheme';
        }
    };

    return {
        isDark,
        themeName,
        toggleTheme,
        syncTheme,
    };
}
