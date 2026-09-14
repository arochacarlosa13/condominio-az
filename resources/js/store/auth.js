import { defineStore } from 'pinia';
import axios from 'axios';

// Storage Helpers
const getStorageItem = (key) => {
    try {
        return sessionStorage.getItem(key) ?? localStorage.getItem(key);
    } catch (e) {
        return null;
    }
};

const getParsedStorageItem = (key, defaultValue = null) => {
    const item = getStorageItem(key);
    if (!item) return defaultValue;
    try {
        return JSON.parse(item);
    } catch {
        return defaultValue;
    }
};

const isPersistentSession = () => {
    try {
        return localStorage.getItem('remember_session') === 'true';
    } catch {
        return false;
    }
};

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: getParsedStorageItem('user', null),
        token: getStorageItem('token') || null,
        menus: [],
        currency: getStorageItem('currency') || 'VES', // 'VES' | 'USD'
        tasaCambio: 36.50,
        tasaCambioCentral: getStorageItem('tasaCambioCentral') ? parseFloat(getStorageItem('tasaCambioCentral')) : 36.50,
        activeCondominioId: getStorageItem('activeCondominioId') ? Number(getStorageItem('activeCondominioId')) : null,
        assignedCondos: getParsedStorageItem('assignedCondos', []),
        snackbar: {
            show: false,
            text: '',
            color: 'success',
        },
    }),

    getters: {
        isAuthenticated: (state) => !!state.token,
        isMaster: (state) => state.user?.rol === 'master' || state.user?.rol === 'Super Admin' || state.user?.role?.slug === 'super-admin',
        isAdmin: (state) => state.user?.rol === 'admin' || state.user?.rol === 'Admin de Condominio' || state.user?.role?.slug === 'admin-condominio',
        isSupervisor: (state) => state.user?.rol === 'Supervisor' || state.user?.role?.slug === 'supervisor',
        isAnalista: (state) => state.user?.rol === 'Analista del Sistema' || state.user?.role?.slug === 'analista',
        isPropietario: (state) => state.user?.rol === 'propietario' || state.user?.rol === 'Propietario/Residente' || state.user?.role?.slug === 'propietario',
        activeCondominio: (state) => {
            if (state.user?.rol === 'master' || state.user?.rol === 'Super Admin' || state.user?.role?.slug === 'master') {
                return null;
            }
            if (!state.activeCondominioId && state.user?.condominio) {
                return state.user.condominio;
            }
            return state.assignedCondos.find(c => c.id === state.activeCondominioId) || state.user?.condominio || null;
        },
    },

    actions: {
        setToken(token, remember = false) {
            this.token = token;
            if (remember) {
                localStorage.setItem('token', token);
                localStorage.setItem('remember_session', 'true');
                sessionStorage.removeItem('token');
            } else {
                sessionStorage.setItem('token', token);
                localStorage.removeItem('token');
                localStorage.removeItem('remember_session');
            }

            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
            if (this.activeCondominioId) {
                axios.defaults.headers.common['X-Condominio-Id'] = this.activeCondominioId;
            }
        },

        setUser(user, remember = null) {
            this.user = user;
            const usePersistent = remember !== null ? remember : isPersistentSession();

            if (usePersistent) {
                localStorage.setItem('user', JSON.stringify(user));
                sessionStorage.removeItem('user');
            } else {
                sessionStorage.setItem('user', JSON.stringify(user));
                localStorage.removeItem('user');
            }

            // Si es Super Admin, no opera a nivel de torre específica sino a nivel de plataforma global SaaS
            if (user?.rol === 'master' || user?.rol === 'Super Admin' || user?.role?.slug === 'master') {
                this.assignedCondos = [];
                this.activeCondominioId = null;
                localStorage.removeItem('assignedCondos');
                localStorage.removeItem('activeCondominioId');
                sessionStorage.removeItem('assignedCondos');
                sessionStorage.removeItem('activeCondominioId');
                delete axios.defaults.headers.common['X-Condominio-Id'];
                if (this.tasaCambioCentral) {
                    this.tasaCambio = parseFloat(this.tasaCambioCentral);
                }
                return;
            }

            // Procesar lista de condominios y torres accesibles para Administradores de Condominio
            const list = [];
            if (Array.isArray(user?.condominios_accesibles) && user.condominios_accesibles.length > 0) {
                user.condominios_accesibles.forEach(c => {
                    if (!list.some(item => item.id === c.id)) {
                        list.push(c);
                    }
                });
            } else {
                if (user?.condominio) {
                    list.push(user.condominio);
                    if (Array.isArray(user.condominio.torres)) {
                        user.condominio.torres.forEach(t => {
                            if (!list.some(item => item.id === t.id)) list.push(t);
                        });
                    }
                }
                if (Array.isArray(user?.condominios)) {
                    user.condominios.forEach(c => {
                        if (!list.some(item => item.id === c.id)) list.push(c);
                        if (Array.isArray(c.torres)) {
                            c.torres.forEach(t => {
                                if (!list.some(item => item.id === t.id)) list.push(t);
                            });
                        }
                    });
                }
            }

            this.assignedCondos = list;
            if (usePersistent) {
                localStorage.setItem('assignedCondos', JSON.stringify(list));
                sessionStorage.removeItem('assignedCondos');
            } else {
                sessionStorage.setItem('assignedCondos', JSON.stringify(list));
                localStorage.removeItem('assignedCondos');
            }

            // Seleccionar condominio activo
            if (!this.activeCondominioId || !list.some(c => c.id === this.activeCondominioId)) {
                if (list.length > 0) {
                    this.switchCondominio(list[0].id, false);
                }
            } else {
                axios.defaults.headers.common['X-Condominio-Id'] = this.activeCondominioId;
            }

            if (this.activeCondominio?.tasa_cambio) {
                this.tasaCambio = parseFloat(this.activeCondominio.tasa_cambio);
            }
        },

        switchCondominio(condominioId, reload = true) {
            this.activeCondominioId = Number(condominioId);
            const usePersistent = isPersistentSession();

            if (usePersistent) {
                localStorage.setItem('activeCondominioId', this.activeCondominioId);
                sessionStorage.removeItem('activeCondominioId');
            } else {
                sessionStorage.setItem('activeCondominioId', this.activeCondominioId);
                localStorage.removeItem('activeCondominioId');
            }

            axios.defaults.headers.common['X-Condominio-Id'] = this.activeCondominioId;

            const selected = this.assignedCondos.find(c => c.id === this.activeCondominioId);
            if (selected?.tasa_cambio) {
                this.tasaCambio = parseFloat(selected.tasa_cambio);
            }

            if (reload && typeof window !== 'undefined') {
                window.location.reload();
            }
        },

        toggleCurrency() {
            this.currency = this.currency === 'VES' ? 'USD' : 'VES';
            localStorage.setItem('currency', this.currency);
        },

        formatMoney(amountBs) {
            const num = parseFloat(amountBs) || 0;
            if (this.currency === 'USD') {
                const usd = num / (this.tasaCambio || 36.50);
                return `$ ${usd.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            }
            return `Bs. ${num.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        },

        notify(text, color = 'success') {
            this.snackbar = {
                show: true,
                text,
                color,
            };
        },

        async fetchTasaCentral() {
            try {
                const { data } = await axios.get('/configuracion/tasa-cambio');
                if (data.success && data.data?.tasa_cambio_central) {
                    this.tasaCambioCentral = parseFloat(data.data.tasa_cambio_central);
                    localStorage.setItem('tasaCambioCentral', this.tasaCambioCentral);
                }
            } catch (e) {}
        },

        async updateTasaCentral(tasa, syncAll = false) {
            const { data } = await axios.post('/configuracion/tasa-cambio', {
                tasa_cambio: tasa,
                actualizar_todas_las_torres: syncAll,
            });
            if (data.success) {
                this.tasaCambioCentral = parseFloat(tasa);
                localStorage.setItem('tasaCambioCentral', this.tasaCambioCentral);
                if (syncAll) {
                    this.tasaCambio = parseFloat(tasa);
                }
                this.notify(data.message || 'Tasa Oficial Central actualizada');
                return data;
            }
            throw new Error(data.message || 'Error al actualizar tasa');
        },

        async consultarBcvEnVivo() {
            const { data } = await axios.get('/configuracion/consultar-bcv');
            if (data.success) {
                return data.data;
            }
            throw new Error(data.message || 'No se pudo consultar el BCV');
        },

        async fetchUser() {
            const currentToken = this.token || getStorageItem('token');
            if (!currentToken) return;
            try {
                axios.defaults.headers.common['Authorization'] = `Bearer ${currentToken}`;
                if (this.activeCondominioId) {
                    axios.defaults.headers.common['X-Condominio-Id'] = this.activeCondominioId;
                }
                const { data } = await axios.get('/me');
                if (data.success) {
                    this.setUser(data.data.user);
                    this.menus = data.data.menus || [];
                    if (data.data.tasa_cambio_central) {
                        this.tasaCambioCentral = parseFloat(data.data.tasa_cambio_central);
                        localStorage.setItem('tasaCambioCentral', this.tasaCambioCentral);
                    }
                }
            } catch (error) {
                if (error.response?.status === 401) {
                    this.logout();
                }
            }
        },

        async login(email, password, remember = false) {
            const { data } = await axios.post('/login', { email, password });
            if (data.requires_2fa) {
                return data;
            }
            if (data.success) {
                this.setToken(data.data.token, remember);
                this.setUser(data.data.user, remember);
                if (data.data.tasa_cambio_central) {
                    this.tasaCambioCentral = parseFloat(data.data.tasa_cambio_central);
                    localStorage.setItem('tasaCambioCentral', this.tasaCambioCentral);
                }
                return data.data;
            }
            throw new Error(data.message || 'Error en autenticación');
        },

        async verificar2FA(email, code, temp_token, remember = false) {
            const { data } = await axios.post('/verificar-2fa', { email, code, temp_token });
            if (data.success) {
                this.setToken(data.data.token, remember);
                this.setUser(data.data.user, remember);
                if (data.data.tasa_cambio_central) {
                    this.tasaCambioCentral = parseFloat(data.data.tasa_cambio_central);
                    localStorage.setItem('tasaCambioCentral', this.tasaCambioCentral);
                }
                return data.data;
            }
            throw new Error(data.message || 'Código de seguridad incorrecto');
        },

        async logout() {
            try {
                if (this.token) {
                    await axios.post('/logout');
                }
            } catch (e) {
                // ignore
            } finally {
                this.token = null;
                this.user = null;
                this.menus = [];
                this.assignedCondos = [];
                this.activeCondominioId = null;
                
                // Clear both session and local storage
                sessionStorage.removeItem('token');
                sessionStorage.removeItem('user');
                sessionStorage.removeItem('assignedCondos');
                sessionStorage.removeItem('activeCondominioId');

                localStorage.removeItem('token');
                localStorage.removeItem('user');
                localStorage.removeItem('remember_session');
                localStorage.removeItem('assignedCondos');
                localStorage.removeItem('activeCondominioId');

                delete axios.defaults.headers.common['Authorization'];
                delete axios.defaults.headers.common['X-Condominio-Id'];
            }
        },
    },
});
