<template>
  <div class="tf-admin-root">
    <!-- Estado de carga inicial con Skeleton Loader -->
    <div v-if="!authStore.initialized" class="container py-5">
      <div class="card shadow-sm border-0 p-4">
        <div class="d-flex align-items-center gap-3 mb-4">
          <TfSkeleton height="48px" width="48px" :circle="true" />
          <div class="flex-grow-1">
            <TfSkeleton height="20px" width="col-4" class="mb-2" />
            <TfSkeleton height="14px" width="col-6" />
          </div>
        </div>
        <TfSkeleton :lines="4" class="mb-3" />
        <TfSkeleton height="36px" width="col-3" />
      </div>
    </div>

    <!-- Shell Administrativo Oficial AdminLTE 4 Vue -->
    <LteDashboardLayout
      v-else
      brand-text="TravelFlow CMS"
      logo-href="/admin"
      :link-component="RouterLink"
      :current-path="route.path"
      :menu-items="menuItems"
      :user="topbarUser"
      :color-mode-toggle="false"
      :sidebar-mini="true"
      :layout-fixed="true"
      :enable-sidebar-persistence="true"
      @logout="handleLogout"
    >
      <!-- Brand Logo con Font Awesome -->
      <template #sidebar-brand>
        <router-link to="/admin" class="brand-link d-flex align-items-center gap-2 text-decoration-none px-3 py-2">
          <i class="fa-solid fa-compass text-primary fs-4" aria-hidden="true"></i>
          <span class="brand-text fw-bold text-body">TravelFlow <span class="fw-normal text-primary">CMS</span></span>
        </router-link>
      </template>

      <!-- Extremo derecho del Topbar: Selector de Tema + Menú de Usuario -->
      <template #topbar-end>
        <!-- Selector Accesible de Tema (Light / Dark / Auto con Font Awesome) -->
        <TfThemeToggle />
      </template>

      <!-- Menú de Usuario Desplegable Personalizado -->
      <template #user-menu="{ user }">
        <li class="user-header bg-primary text-white text-center p-3">
          <div class="tf-avatar-circle mx-auto mb-2 rounded-circle bg-white text-primary d-flex align-items-center justify-content-center fw-bold fs-4 shadow-sm" style="width: 64px; height: 64px;">
            {{ userInitials }}
          </div>
          <p class="mb-0 fw-semibold">{{ user.name }}</p>
          <small class="d-block text-white-50">{{ authStore.user?.email }}</small>
          <div class="mt-2">
            <span class="badge bg-white text-primary fw-semibold px-2 py-1">
              <i class="fa-solid fa-user-shield me-1" aria-hidden="true"></i>{{ user.role }}
            </span>
          </div>
        </li>

        <li class="user-footer d-flex justify-content-between p-3 border-top">
          <span class="text-muted small align-self-center">Sesión segura</span>
          <button
            type="button"
            class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1"
            @click="handleLogout"
          >
            <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
            <span>Cerrar sesión</span>
          </button>
        </li>
      </template>

      <!-- Contenido Principal de las Vistas -->
      <template #default>
        <div class="app-content-body p-3 p-md-4">
          <router-view />
        </div>
      </template>

      <!-- Pie de Página -->
      <template #footer>
        <strong>TravelFlow CMS &copy; {{ new Date().getFullYear() }}</strong> &mdash; Sistema de Gestión de Contenidos Turísticos.
      </template>

      <template #footer-right>
        <span class="text-muted small">v1.0.0-alpha (Fase 1B.1)</span>
      </template>
    </LteDashboardLayout>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { LteDashboardLayout, type MenuNode, type TopbarUser } from '@adminlte/vue';
import Swal from 'sweetalert2';
import { useAuthStore } from '@/stores/auth';
import { TfSkeleton } from '@/design-system';
import TfThemeToggle from '@/components/admin/TfThemeToggle.vue';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

/**
 * Menú estructural de navegación de la administración.
 * REGLA VINCULANTE: Solo contiene opciones reales y autorizadas (sin placeholders internos ni disabled ficticios).
 */
const menuItems: MenuNode[] = [
  {
    type: 'header',
    text: 'MENÚ PRINCIPAL',
  },
  {
    type: 'item',
    text: 'Dashboard',
    href: '/admin',
    icon: 'fa-solid fa-gauge-high',
  },
];

const topbarUser = computed<TopbarUser>(() => ({
  name: authStore.displayName || 'Usuario',
  image: "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Ccircle cx='32' cy='32' r='32' fill='%230d6efd'/%3E%3Cpath d='M32 16a10 10 0 100 20 10 10 0 000-20zm0 24c-11.05 0-20 6.72-20 15v1h40v-1c0-8.28-8.95-15-20-15z' fill='%23ffffff'/%3E%3C/svg%3E",
  role: authStore.userRoles.length > 0 ? authStore.userRoles[0].toUpperCase() : 'USUARIO',
  memberSince: 'Activo',
}));

const userInitials = computed(() => {
  const name = authStore.displayName || 'Usuario';
  const parts = name.trim().split(' ');
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return name.substring(0, 2).toUpperCase();
});

async function handleLogout() {
  const result = await Swal.fire({
    title: '¿Cerrar sesión?',
    text: 'Se finalizará su sesión segura en TravelFlow CMS.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#0d6efd',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Sí, cerrar sesión',
    cancelButtonText: 'Cancelar',
    reverseButtons: true,
  });

  if (result.isConfirmed) {
    await authStore.logout();
    router.push('/login');

    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'Sesión cerrada correctamente',
      showConfirmButton: false,
      timer: 2500,
      timerProgressBar: true,
    });
  }
}
</script>

<style scoped>
.tf-avatar-circle {
  display: flex;
  align-items: center;
  justify-content: center;
}

.brand-link {
  height: 56px;
}
</style>
