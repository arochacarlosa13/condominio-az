import '../css/app.css';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import vuetify from './plugins/vuetify';
import axios from 'axios';

// Configurar URL base de Axios para el API versionada v1
axios.defaults.baseURL = '/api/v1';

// Interceptor para inyectar token de Sanctum y Condominio Activo automáticamente
axios.interceptors.request.use((config) => {
    const token = sessionStorage.getItem('token') || localStorage.getItem('token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    const activeCondoId = sessionStorage.getItem('activeCondominioId') || localStorage.getItem('activeCondominioId');
    if (activeCondoId) {
        config.headers['X-Condominio-Id'] = activeCondoId;
    }

    return config;
});

// Interceptor para redirección en 401
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response && error.response.status === 401) {
            sessionStorage.removeItem('token');
            sessionStorage.removeItem('user');
            sessionStorage.removeItem('assignedCondos');
            sessionStorage.removeItem('activeCondominioId');
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            localStorage.removeItem('remember_session');
            localStorage.removeItem('assignedCondos');
            localStorage.removeItem('activeCondominioId');
            if (window.location.pathname !== '/login' && window.location.pathname !== '/') {
                window.location.href = '/login';
            }
        }
        return Promise.reject(error);
    }
);

const pinia = createPinia();
const app = createApp(App);

app.use(pinia);
app.use(router);
app.use(vuetify);

app.mount('#app');

// Registro de Service Worker para PWA
if ('serviceWorker' in navigator && window.location.protocol === 'https:' || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then((reg) => {
                console.log('PWA Service Worker registrado con éxito:', reg.scope);
            })
            .catch((err) => {
                console.log('Error al registrar PWA Service Worker:', err);
            });
    });
}

