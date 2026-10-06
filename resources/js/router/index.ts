import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

/**
 * Rutas Oficiales de TravelFlow CMS (Fase 1B)
 *
 * Superficies iniciales:
 * - /login: Pantalla de inicio de sesión con flujo Sanctum stateful.
 * - /admin: Shell administrativo protegido mediante Navigation Guards y backend RBAC.
 */
const routes: RouteRecordRaw[] = [
  {
    path: '/',
    redirect: '/admin',
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/auth/LoginView.vue'),
    meta: {
      guestOnly: true,
      title: 'Iniciar Sesión — TravelFlow CMS',
    },
  },
  {
    path: '/admin',
    component: () => import('@/layouts/AdminLayout.vue'),
    meta: {
      requiresAuth: true,
    },
    children: [
      {
        path: '',
        name: 'admin-dashboard',
        component: () => import('@/views/admin/DashboardView.vue'),
        meta: {
          requiresAuth: true,
          title: 'Panel Administrativo — TravelFlow CMS',
        },
      },
      {
        path: 'personas',
        name: 'admin-personas',
        component: () => import('@/views/admin/personas/PersonasView.vue'),
        meta: {
          requiresAuth: true,
          title: 'Personas — TravelFlow CMS',
        },
      },
      {
        path: 'usuarios',
        name: 'admin-usuarios',
        component: () => import('@/views/admin/usuarios/UsuariosView.vue'),
        meta: {
          requiresAuth: true,
          title: 'Usuarios — TravelFlow CMS',
        },
      },
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/admin',
  },
];

export const router = createRouter({
  history: createWebHistory(),
  routes,
});

/**
 * Navigation Guards (Router Guards Frontend)
 *
 * NOTA DE GOBERNANZA:
 * El Router Guard optimiza la experiencia de usuario (UX) redirigiendo antes del renderizado,
 * pero la seguridad real permanece soberanamente en los Middleware y Policies de Laravel.
 */
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();

  // Si la sesión aún no ha sido inicializada en memoria, consultar al backend una sola vez
  if (!authStore.initialized) {
    await authStore.fetchCurrentUser();
  }

  // Actualizar título de la página
  if (to.meta.title) {
    document.title = String(to.meta.title);
  }

  // Comprobar rutas protegidas
  if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!authStore.isAuthenticated) {
      return next({
        path: '/login',
        query: { redirect: to.fullPath },
      });
    }
  }

  // Comprobar rutas exclusivas para invitados (login)
  if (to.matched.some(record => record.meta.guestOnly)) {
    if (authStore.isAuthenticated) {
      return next({ path: '/admin' });
    }
  }

  return next();
});
