import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../store/auth';

const routes = [
    {
        path: '/',
        name: 'LandingPage',
        component: () => import('../views/Landing/LandingPage.vue'),
        meta: { public: true },
    },
    {
        path: '/login',
        name: 'Login',
        component: () => import('../views/Auth/Login.vue'),
        meta: { guestOnly: true },
    },
    {
        path: '/configuracion/landing',
        name: 'LandingConfig',
        component: () => import('../views/Configuracion/LandingConfig.vue'),
        meta: { requiresAuth: true, roles: ['master', 'Super Admin'] },
    },
    {
        path: '/recuperar-password',
        name: 'ForgotPassword',
        component: () => import('../views/Auth/ForgotPassword.vue'),
        meta: { guestOnly: true },
    },
    {
        path: '/seleccionar-condominio',
        name: 'SeleccionarCondominio',
        component: () => import('../views/Auth/SeleccionarCondominio.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/dashboard/master',
        name: 'SuperAdminDashboard',
        component: () => import('../views/Dashboards/SuperAdminDashboard.vue'),
        meta: { requiresAuth: true, roles: ['master', 'Super Admin'] },
    },
    {
        path: '/dashboard/admin',
        name: 'AdminDashboard',
        component: () => import('../views/Dashboards/AdminDashboard.vue'),
        meta: { requiresAuth: true, roles: ['admin', 'Admin de Condominio', 'Supervisor', 'Analista del Sistema', 'master', 'Super Admin'] },
    },
    {
        path: '/dashboard/owner',
        name: 'OwnerDashboard',
        component: () => import('../views/Dashboards/OwnerDashboard.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/configuracion/select-options',
        name: 'SelectOptions',
        component: () => import('../views/Configuracion/SelectOptions.vue'),
        meta: { requiresAuth: true, roles: ['master', 'Super Admin'] },
    },
    {
        path: '/seguridad/roles',
        name: 'RolesList',
        component: () => import('../views/RolesPermisos/RolesList.vue'),
        meta: { requiresAuth: true, roles: ['master', 'Super Admin'] },
    },
    {
        path: '/seguridad/menus',
        name: 'MenuList',
        component: () => import('../views/Menus/MenuList.vue'),
        meta: { requiresAuth: true, roles: ['master', 'Super Admin'] },
    },
    {
        path: '/condominios',
        name: 'CondominiosList',
        component: () => import('../views/Condominios/CondominiosList.vue'),
        meta: { requiresAuth: true, roles: ['master', 'Super Admin', 'admin', 'Admin de Condominio'] },
    },
    {
        path: '/suscripciones/planes',
        name: 'PlanesList',
        component: () => import('../views/Suscripciones/PlanesList.vue'),
        meta: { requiresAuth: true, roles: ['master', 'Super Admin'] },
    },
    {
        path: '/usuarios',
        name: 'UsuariosList',
        component: () => import('../views/Usuarios/UsuariosList.vue'),
        meta: { requiresAuth: true, roles: ['master', 'Super Admin', 'admin', 'Admin de Condominio'] },
    },
    {
        path: '/apartamentos',
        name: 'ApartamentosList',
        component: () => import('../views/Apartamentos/ApartamentosList.vue'),
        meta: { requiresAuth: true, roles: ['master', 'Super Admin', 'admin', 'Admin de Condominio'] },
    },
    {
        path: '/contabilidad/super-admin',
        name: 'SuperAdminContabilidad',
        component: () => import('../views/Contabilidad/SuperAdminContabilidad.vue'),
        meta: { requiresAuth: true, roles: ['master', 'Super Admin'] },
    },
    {
        path: '/contabilidad/admin',
        name: 'AdminContabilidad',
        component: () => import('../views/Contabilidad/AdminContabilidad.vue'),
        meta: { requiresAuth: true, roles: ['master', 'Super Admin', 'admin', 'Admin de Condominio', 'Supervisor', 'Analista del Sistema'] },
    },
    {
        path: '/pagos',
        name: 'PagosList',
        component: () => import('../views/Pagos/PagosList.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/visitantes',
        name: 'VisitantesList',
        component: () => import('../views/Visitantes/VisitantesList.vue'),
        meta: { requiresAuth: true, roles: ['master', 'Super Admin', 'admin', 'Admin de Condominio', 'Supervisor'] },
    },
    {
        path: '/reservas',
        name: 'ReservasCalendar',
        component: () => import('../views/Reservas/ReservasCalendar.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/incidencias',
        name: 'IncidenciasList',
        component: () => import('../views/Incidencias/IncidenciasList.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/comunicados',
        name: 'ComunicadosList',
        component: () => import('../views/Comunicados/ComunicadosList.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/notificaciones',
        name: 'NotificacionesList',
        component: () => import('../views/Notificaciones/NotificacionesList.vue'),
        meta: { requiresAuth: true, roles: ['master', 'Super Admin', 'admin', 'Admin de Condominio'] },
    },
    {
        path: '/auditoria',
        name: 'AuditoriaLogs',
        component: () => import('../views/Auditoria/AuditoriaLogs.vue'),
        meta: { requiresAuth: true, roles: ['master', 'Super Admin'] },
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: '/',
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

const getDefaultDashboard = (authStore) => {
    if (authStore.isMaster) return '/dashboard/master';
    if (authStore.isAdmin || authStore.isSupervisor || authStore.isAnalista) return '/dashboard/admin';
    return '/dashboard/owner';
};

const checkRoleAccess = (authStore, allowedRoles) => {
    if (!allowedRoles || allowedRoles.length === 0) return true;
    if (authStore.isMaster) return true;

    const userRol = (authStore.user?.rol || authStore.user?.role?.nombre || authStore.user?.role?.slug || '').toLowerCase();

    return allowedRoles.some((role) => {
        const r = role.toLowerCase();
        if (r === userRol) return true;
        if ((r === 'admin' || r === 'admin de condominio') && authStore.isAdmin) return true;
        if (r === 'supervisor' && authStore.isSupervisor) return true;
        if ((r === 'analista' || r === 'analista del sistema') && authStore.isAnalista) return true;
        if (r === 'propietario' && authStore.isPropietario) return true;
        return false;
    });
};

router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();
    const token = authStore.token || sessionStorage.getItem('token') || localStorage.getItem('token');

    // Si el usuario ya está autenticado e intenta ir a la Landing Page o rutas guestOnly (login, recuperar)
    if (token && (to.path === '/' || to.name === 'LandingPage' || to.meta.guestOnly)) {
        if (!authStore.user) {
            await authStore.fetchUser();
        }
        return next(getDefaultDashboard(authStore));
    }

    // Si la ruta requiere autenticación y no hay token
    if (to.meta.requiresAuth && !token) {
        return next('/login');
    }

    // Si la ruta requiere roles específicos, validar que el usuario tenga permiso
    if (to.meta.requiresAuth && token && to.meta.roles) {
        if (!authStore.user) {
            await authStore.fetchUser();
        }

        if (!checkRoleAccess(authStore, to.meta.roles)) {
            authStore.notify('No tiene permisos para acceder a esta sección.', 'error');
            return next(getDefaultDashboard(authStore));
        }
    }

    next();
});

export default router;
