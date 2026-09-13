import { defineStore } from 'pinia';
import axios from 'axios';

export const useOptionsStore = defineStore('options', {
    state: () => ({
        options: [],
        bancos: [],
        statuses: [],
        tiposArea: [],
    }),

    actions: {
        async fetchOptions(tipo = null) {
            try {
                const params = tipo ? { tipo } : {};
                const { data } = await axios.get('/select-options', { params });
                if (data.success) {
                    this.options = data.data;
                    this.bancos = data.data.filter(o => o.tipo === 'bancos');
                    this.statuses = data.data.filter(o => o.tipo === 'status_usuario');
                    this.tiposArea = data.data.filter(o => o.tipo === 'tipos_area_comun');
                }
            } catch (e) {
                console.error('Error fetching select options', e);
            }
        },
    },
});
