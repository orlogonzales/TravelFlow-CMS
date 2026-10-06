import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';

/**
 * Rutas Técnicas de Infraestructura (Fase 0D)
 *
 * NOTA DE GOBERNANZA:
 * No define rutas administrativas definitivas ni asume prefijos funcionales.
 * Sirve exclusivamente para verificar la integración técnica de Vue Router con Vue 3 y Vite.
 */
const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'technical-infrastructure',
    component: () => import('@/views/TechnicalStatusView.vue'),
  },
];

export const router = createRouter({
  history: createWebHistory(),
  routes,
});
