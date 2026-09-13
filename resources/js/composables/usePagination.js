import { ref, computed, watch } from 'vue';

/**
 * Composable reutilizable para búsqueda instantánea, paginación y ordenamiento estilo DataTable.
 * 
 * @param {import('vue').Ref<Array>} itemsRef - Ref reactiva con la lista completa de elementos
 * @param {Object} options - Opciones de configuración (perPage inicial, perPageOptions, customFilter, initialSortBy, initialSortDesc, customGetters)
 */
export function usePagination(itemsRef, options = {}) {
    const search = ref('');
    const currentPage = ref(1);
    const perPage = ref(options.perPage || 10);
    const perPageOptions = options.perPageOptions || [5, 10, 25, 50, 100];
    const sortBy = ref(options.initialSortBy || null);
    const sortDesc = ref(options.initialSortDesc ?? false);
    const customGetters = options.customGetters || {};

    // Alternar ordenamiento por columna
    const sort = (key, customGetter = null) => {
        if (customGetter) {
            customGetters[key] = customGetter;
        }
        if (sortBy.value === key) {
            sortDesc.value = !sortDesc.value;
        } else {
            sortBy.value = key;
            sortDesc.value = false;
        }
        currentPage.value = 1;
    };

    // Extraer valor anidado soportando 'apartamento.numero'
    const getNestedValue = (obj, path) => {
        if (!obj || !path) return '';
        if (typeof customGetters[path] === 'function') {
            return customGetters[path](obj);
        }
        return path.split('.').reduce((acc, part) => (acc && acc[part] !== undefined ? acc[part] : ''), obj);
    };

    // Comparador flexible (numérico, fechas, strings con orden natural)
    const compareValues = (a, b) => {
        const valA = getNestedValue(a, sortBy.value);
        const valB = getNestedValue(b, sortBy.value);

        if ((valA === null || valA === undefined || valA === '') && (valB === null || valB === undefined || valB === '')) return 0;
        if (valA === null || valA === undefined || valA === '') return 1;
        if (valB === null || valB === undefined || valB === '') return -1;

        // Numérico
        const numA = Number(valA);
        const numB = Number(valB);
        if (!isNaN(numA) && !isNaN(numB) && typeof valA !== 'boolean' && typeof valB !== 'boolean') {
            return numA - numB;
        }

        // Fechas
        const isDateA = typeof valA === 'string' && /^\d{4}-\d{2}-\d{2}/.test(valA);
        const isDateB = typeof valB === 'string' && /^\d{4}-\d{2}-\d{2}/.test(valB);
        if (isDateA && isDateB) {
            return new Date(valA).getTime() - new Date(valB).getTime();
        }

        // String con soporte natural (Apto 2 antes de Apto 10)
        return String(valA).localeCompare(String(valB), 'es', { numeric: true, sensitivity: 'base' });
    };

    // Búsqueda recursiva en propiedades de objetos (incluyendo arrays y relaciones)
    const matchValue = (val, term) => {
        if (val === null || val === undefined) return false;
        if (typeof val === 'string' || typeof val === 'number') {
            return String(val).toLowerCase().includes(term);
        }
        if (typeof val === 'boolean') {
            return false;
        }
        if (Array.isArray(val)) {
            return val.some(subItem => matchValue(subItem, term));
        }
        if (typeof val === 'object') {
            return Object.values(val).some(subVal => matchValue(subVal, term));
        }
        return false;
    };

    // Elementos filtrados y ordenados
    const filteredItems = computed(() => {
        const list = Array.isArray(itemsRef.value) ? [...itemsRef.value] : [];
        const term = (search.value || '').trim().toLowerCase();

        let result = list;
        if (term) {
            result = list.filter(item => {
                if (typeof options.customFilter === 'function') {
                    return options.customFilter(item, term);
                }
                return matchValue(item, term);
            });
        }

        if (sortBy.value) {
            result.sort((a, b) => {
                const diff = compareValues(a, b);
                return sortDesc.value ? -diff : diff;
            });
        }

        return result;
    });

    // Total original sin filtro
    const originalTotal = computed(() => {
        return Array.isArray(itemsRef.value) ? itemsRef.value.length : 0;
    });

    // Total de elementos filtrados y total de páginas
    const totalItems = computed(() => filteredItems.value.length);
    const totalPages = computed(() => Math.max(1, Math.ceil(totalItems.value / perPage.value)));

    // Elementos paginados para la vista actual
    const paginatedItems = computed(() => {
        const start = (currentPage.value - 1) * perPage.value;
        return filteredItems.value.slice(start, start + perPage.value);
    });

    // Índices para pie de tabla
    const startIndex = computed(() => (totalItems.value === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1));
    const endIndex = computed(() => Math.min(currentPage.value * perPage.value, totalItems.value));

    // Reiniciar a página 1 cuando cambia la búsqueda o los registros
    watch([search, perPage, () => (itemsRef.value ? itemsRef.value.length : 0)], () => {
        currentPage.value = 1;
    });

    return {
        search,
        currentPage,
        perPage,
        perPageOptions,
        sortBy,
        sortDesc,
        sort,
        filteredItems,
        paginatedItems,
        originalTotal,
        totalItems,
        totalPages,
        startIndex,
        endIndex,
    };
}
