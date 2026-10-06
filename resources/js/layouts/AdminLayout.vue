<template>
  <div class="tf-admin-shell min-vh-100 d-flex flex-column bg-light">
    <!-- Estado de Carga Inicial con Skeleton Loader -->
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

    <!-- Shell Administrativo Operativo -->
    <div v-else class="d-flex flex-grow-1">
      <!-- Sidebar Estructural -->
      <aside
        class="tf-sidebar bg-slate-900 text-white d-flex flex-column flex-shrink-0 transition-all"
        :class="{ 'tf-sidebar-collapsed': isSidebarCollapsed, 'tf-sidebar-mobile-open': isMobileMenuOpen }"
      >
        <!-- Cabecera Sidebar -->
        <div class="tf-sidebar-header p-3 d-flex align-items-center justify-content-between border-bottom border-slate-700">
          <div class="d-flex align-items-center gap-2 overflow-hidden text-nowrap">
            <i class="fa-solid fa-compass text-primary fs-4" aria-hidden="true"></i>
            <span class="fw-bold fs-5 tracking-wide text-white">TravelFlow</span>
          </div>
          <button
            type="button"
            class="btn btn-sm btn-link text-slate-400 d-md-none p-1"
            aria-label="Cerrar menú"
            @click="isMobileMenuOpen = false"
          >
            <i class="fa-solid fa-xmark fs-5" aria-hidden="true"></i>
          </button>
        </div>

        <!-- Navegación Estructural -->
        <nav class="tf-sidebar-nav flex-grow-1 py-3 px-2">
          <div class="text-uppercase text-slate-400 fw-bold px-3 mb-2" style="font-size: 0.75rem;">
            Navegación Base
          </div>

          <ul class="nav nav-pills flex-column gap-1">
            <li class="nav-item">
              <router-link
                to="/admin"
                class="nav-link text-white-50 d-flex align-items-center gap-3 px-3 py-2 rounded-2"
                active-class="active text-white bg-primary"
              >
                <i class="fa-solid fa-house w-20 text-center" aria-hidden="true"></i>
                <span>Inicio</span>
              </router-link>
            </li>
          </ul>

          <div class="text-uppercase text-slate-400 fw-bold px-3 mt-4 mb-2" style="font-size: 0.75rem;">
            Infraestructura
          </div>

          <ul class="nav nav-pills flex-column gap-1">
            <li class="nav-item">
              <span class="nav-link text-slate-500 d-flex align-items-center justify-content-between px-3 py-2 cursor-not-allowed">
                <span class="d-flex align-items-center gap-3">
                  <i class="fa-solid fa-users w-20 text-center" aria-hidden="true"></i>
                  <span>Usuarios</span>
                </span>
                <span class="badge bg-slate-800 text-slate-400 border border-slate-700" style="font-size: 0.65rem;">Fase 1C</span>
              </span>
            </li>
            <li class="nav-item">
              <span class="nav-link text-slate-500 d-flex align-items-center justify-content-between px-3 py-2 cursor-not-allowed">
                <span class="d-flex align-items-center gap-3">
                  <i class="fa-solid fa-shield-halved w-20 text-center" aria-hidden="true"></i>
                  <span>Roles & RBAC</span>
                </span>
                <span class="badge bg-slate-800 text-slate-400 border border-slate-700" style="font-size: 0.65rem;">Backend Activo</span>
              </span>
            </li>
          </ul>
        </nav>

        <!-- Pie Sidebar: Estado de Conexión -->
        <div class="tf-sidebar-footer p-3 border-top border-slate-700 text-slate-400 small">
          <div class="d-flex align-items-center gap-2">
            <span class="tf-status-dot bg-success rounded-circle d-inline-block" style="width: 8px; height: 8px;"></span>
            <span style="font-size: 0.8rem;">Sesión MySQL Activa</span>
          </div>
        </div>
      </aside>

      <!-- Backdrop Móvil -->
      <div
        v-if="isMobileMenuOpen"
        class="tf-sidebar-backdrop d-md-none"
        @click="isMobileMenuOpen = false"
      ></div>

      <!-- Área de Contenido Principal -->
      <div class="tf-main-wrapper d-flex flex-column flex-grow-1 overflow-hidden">
        <!-- Header / Navbar Superior -->
        <header class="tf-header bg-white border-bottom border-slate-200 px-3 py-2 d-flex align-items-center justify-content-between sticky-top">
          <!-- Botón Toggle Sidebar -->
          <div class="d-flex align-items-center gap-3">
            <button
              type="button"
              class="btn btn-outline-secondary btn-sm border-0 text-slate-600 p-2"
              aria-label="Alternar barra lateral"
              @click="toggleSidebar"
            >
              <i class="fa-solid fa-bars fs-5" aria-hidden="true"></i>
            </button>
            <span class="fw-semibold text-slate-800 d-none d-sm-inline">TravelFlow CMS &mdash; Shell Administrativo</span>
          </div>

          <!-- Menú de Usuario -->
          <div class="d-flex align-items-center gap-3">
            <div class="dropdown">
              <button
                id="userDropdown"
                type="button"
                class="btn btn-light d-flex align-items-center gap-2 p-1 pe-2 rounded-pill border"
                data-bs-toggle="dropdown"
                aria-expanded="false"
              >
                <!-- Avatar o Iniciales -->
                <div class="tf-avatar rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 0.85rem;">
                  {{ userInitials }}
                </div>
                <div class="text-start d-none d-md-inline-block pe-1">
                  <div class="fw-semibold text-truncate text-slate-800" style="max-width: 150px; font-size: 0.85rem;">
                    {{ authStore.displayName }}
                  </div>
                  <div class="text-muted" style="font-size: 0.75rem;">
                    {{ primaryRole }}
                  </div>
                </div>
                <i class="fa-solid fa-chevron-down text-muted ms-1" style="font-size: 0.75rem;" aria-hidden="true"></i>
              </button>

              <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-2 mt-2" aria-labelledby="userDropdown" style="min-width: 220px;">
                <li class="px-3 py-2 border-bottom">
                  <span class="d-block fw-bold text-slate-800">{{ authStore.displayName }}</span>
                  <small class="text-muted d-block text-truncate">{{ authStore.user?.email }}</small>
                  <div class="mt-1 d-flex flex-wrap gap-1">
                    <span
                      v-for="role in authStore.userRoles"
                      :key="role"
                      class="badge bg-primary-subtle text-primary border border-primary-subtle"
                      style="font-size: 0.7rem;"
                    >
                      {{ role }}
                    </span>
                  </div>
                </li>
                <li>
                  <button
                    type="button"
                    class="dropdown-item text-danger d-flex align-items-center gap-2 py-2 mt-1"
                    @click="handleLogout"
                  >
                    <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                    <span>Cerrar sesión</span>
                  </button>
                </li>
              </ul>
            </div>
          </div>
        </header>

        <!-- Contenido de las Vistas -->
        <main class="tf-content-area flex-grow-1 p-3 p-md-4 overflow-auto">
          <router-view />
        </main>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import Swal from 'sweetalert2';
import { useAuthStore } from '@/stores/auth';
import { TfSkeleton } from '@/design-system';

const router = useRouter();
const authStore = useAuthStore();

const isSidebarCollapsed = ref(false);
const isMobileMenuOpen = ref(false);

const userInitials = computed(() => {
  const name = authStore.displayName || 'Usuario';
  const parts = name.trim().split(' ');
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return name.substring(0, 2).toUpperCase();
});

const primaryRole = computed(() => {
  if (authStore.userRoles.length > 0) {
    return authStore.userRoles[0].toUpperCase();
  }
  return 'USUARIO';
});

function toggleSidebar() {
  if (window.innerWidth < 768) {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
  } else {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
  }
}

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
.tf-sidebar {
  width: 250px;
  background-color: #0f172a;
}

.tf-sidebar-collapsed {
  width: 70px;
}

.tf-sidebar-collapsed .tf-sidebar-header span,
.tf-sidebar-collapsed .tf-sidebar-nav span,
.tf-sidebar-collapsed .tf-sidebar-nav .badge,
.tf-sidebar-collapsed .tf-sidebar-footer span {
  display: none !important;
}

.w-20 {
  width: 20px;
}

.bg-slate-900 {
  background-color: #0f172a;
}

.bg-slate-800 {
  background-color: #1e293b;
}

.border-slate-700 {
  border-color: #334155 !important;
}

.border-slate-200 {
  border-color: #e2e8f0 !important;
}

.text-slate-400 {
  color: #94a3b8;
}

.text-slate-500 {
  color: #64748b;
}

.text-slate-600 {
  color: #475569;
}

.text-slate-800 {
  color: #1e293b;
}

.cursor-not-allowed {
  cursor: not-allowed;
}

.transition-all {
  transition: all 0.2s ease-in-out;
}

@media (max-width: 767.98px) {
  .tf-sidebar {
    position: fixed;
    top: 0;
    left: -260px;
    bottom: 0;
    z-index: 1045;
  }

  .tf-sidebar-mobile-open {
    left: 0;
  }

  .tf-sidebar-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(15, 23, 42, 0.6);
    z-index: 1040;
  }
}
</style>
