import 'vuetify/styles';
import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';
import { aliases, mdi } from 'vuetify/iconsets/mdi';

const customLightTheme = {
    dark: false,
    colors: {
        background: '#F8FAFC',
        surface: '#FFFFFF',
        'surface-bright': '#FFFFFF',
        'surface-light': '#F1F5F9',
        'surface-variant': '#E2E8F0',
        'on-background': '#0F172A',
        'on-surface': '#0F172A',
        primary: '#1E40AF',
        'primary-darken-1': '#1E3A8A',
        'on-primary': '#FFFFFF',
        secondary: '#475569',
        'secondary-darken-1': '#334155',
        error: '#EF4444',
        info: '#0288D1',
        success: '#10B981',
        warning: '#F59E0B',
    },
};

const customDarkTheme = {
    dark: true,
    colors: {
        background: '#0F172A',
        surface: '#1E293B',
        'surface-bright': '#334155',
        'surface-light': '#1E293B',
        'surface-variant': '#0F172A',
        'on-background': '#F8FAFC',
        'on-surface': '#F8FAFC',
        primary: '#2563EB',
        'primary-darken-1': '#1D4ED8',
        'on-primary': '#FFFFFF',
        secondary: '#94A3B8',
        'secondary-darken-1': '#64748B',
        error: '#F87171',
        info: '#38BDF8',
        success: '#34D399',
        warning: '#FBBF24',
    },
};

// Leer tema inicial guardado en localStorage (por defecto oscuro si no existe o es dark)
const savedTheme = localStorage.getItem('appTheme') === 'light' ? 'customLightTheme' : 'customDarkTheme';

export default createVuetify({
    components,
    directives,
    icons: {
        defaultSet: 'mdi',
        aliases,
        sets: {
            mdi,
        },
    },
    theme: {
        defaultTheme: savedTheme,
        themes: {
            customLightTheme,
            customDarkTheme,
        },
    },
    defaults: {
        VCard: {
            elevation: 1,
            rounded: 'lg',
        },
        VBtn: {
            rounded: 'lg',
            variant: 'flat',
        },
        VTextField: {
            variant: 'outlined',
            density: 'comfortable',
        },
        VSelect: {
            variant: 'outlined',
            density: 'comfortable',
        },
        VDataTable: {
            hover: true,
        },
    },
});
