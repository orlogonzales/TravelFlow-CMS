<template>
  <div
    class="layout-wrapper layout-content-navbar"
    :class="{
      'layout-menu-collapsed': preferencesStore.sidebarCollapsed,
      'layout-menu-expanded': isMobileExpanded
    }"
  >
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

    <!-- Shell Administrativo Oficial Materialize v13.11.1 -->
    <div v-else class="layout-container">
      <!-- Menú Lateral Vertical (Sidebar) -->
      <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
        <!-- Brand / Logotipo -->
        <div class="app-brand demo">
          <router-link to="/admin" class="app-brand-link d-flex align-items-center gap-2 text-decoration-none">
            <span class="app-brand-logo demo">
              <i class="fa-solid fa-compass text-primary fs-3" aria-hidden="true"></i>
            </span>
            <span class="app-brand-text demo menu-text fw-bold text-body">
              TravelFlow <span class="fw-normal text-primary">CMS</span>
            </span>
          </router-link>

          <!-- Toggle Sidebar Collapsed (Desktop) -->
          <button
            type="button"
            class="layout-menu-toggle menu-link text-large ms-auto btn btn-link p-0 border-0 d-none d-xl-flex align-items-center justify-content-center"
            title="Alternar menú lateral"
            aria-label="Alternar menú lateral"
            @click="preferencesStore.toggleSidebarCollapse"
          >
            <i
              class="fs-5 text-primary"
              :class="preferencesStore.sidebarCollapsed ? 'fa-regular fa-circle' : 'fa-solid fa-circle-dot'"
              aria-hidden="true"
            ></i>
          </button>
        </div>

        <div class="menu-inner-shadow"></div>

        <!-- Lista de Navegación Vertical -->
        <ul class="menu-inner py-1">
          <template v-for="(item, index) in navigationItems" :key="index">
            <!-- Encabezado de Sección -->
            <li v-if="item.type === 'header'" class="menu-header small text-uppercase">
              <span class="menu-header-text">{{ item.label }}</span>
            </li>

            <!-- Elemento de Menú Navegable -->
            <li
              v-else
              class="menu-item"
              :class="{ active: isRouteActive(item.to) }"
            >
              <router-link
                :to="item.to"
                class="menu-link"
                @click="closeMobileMenu"
              >
                <i :class="[item.icon, 'menu-icon tf-icons']" aria-hidden="true"></i>
                <div class="menu-text">{{ item.label }}</div>
              </router-link>
            </li>
          </template>
        </ul>
      </aside>

      <!-- Layout Page (Navbar + Content + Footer) -->
      <div class="layout-page">
        <!-- Navbar Superior Desacoplada Materialize -->
        <nav
          id="layout-navbar"
          class="layout-navbar navbar navbar-expand-xl align-items-center bg-navbar-theme"
          :class="[
            preferencesStore.contentLayout === 'wide' ? 'container-fluid px-4' : 'container-xxl',
            { 'navbar-detached': preferencesStore.navbarType === 'sticky' }
          ]"
          aria-label="Barra de herramientas superior"
        >
          <!-- Botón de Menú Móvil -->
          <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
            <button
              type="button"
              class="nav-item nav-link px-0 me-xl-4 btn btn-link border-0 text-heading"
              title="Abrir menú de navegación"
              aria-label="Abrir menú de navegación"
              @click="toggleMobileMenu"
            >
              <i class="fa-solid fa-bars fs-4" aria-hidden="true"></i>
            </button>
          </div>

          <div class="navbar-nav-right d-flex align-items-center justify-content-between flex-grow-1" id="navbar-collapse">
            <!-- Indicador contextual del entorno -->
            <div class="d-none d-md-flex align-items-center text-muted small">
              <i class="fa-solid fa-shield-halved me-2 text-primary" aria-hidden="true"></i>
              <span>Panel Administrativo Seguro</span>
            </div>

            <!-- Controles a la Derecha: Selector de Tema + Dropdown de Usuario -->
            <ul class="navbar-nav flex-row align-items-center ms-auto gap-2">
              <!-- Selector Accesible de Tema Rápido -->
              <li class="nav-item">
                <TfThemeToggle />
              </li>

              <!-- Dropdown de Usuario Oficial Materialize -->
              <li class="nav-item navbar-dropdown dropdown-user dropdown position-relative">
                <button
                  type="button"
                  class="nav-link dropdown-toggle hide-arrow p-0 border-0 bg-transparent"
                  :aria-expanded="isUserMenuOpen"
                  aria-label="Menú de usuario"
                  @click="toggleUserMenu"
                >
                  <div class="avatar avatar-online">
                    <div class="avatar-initial rounded-circle bg-primary text-white fw-bold shadow-sm d-flex align-items-center justify-content-center">
                      {{ userInitials }}
                    </div>
                  </div>
                </button>

                <!-- Menú Desplegable -->
                <ul
                  class="dropdown-menu dropdown-menu-end mt-3 py-2 shadow border-0"
                  :class="{ show: isUserMenuOpen }"
                  style="min-width: 250px;"
                >
                  <!-- Cabecera de Usuario -->
                  <li class="px-3 py-2">
                    <div class="d-flex align-items-center gap-2">
                      <div class="avatar avatar-online flex-shrink-0">
                        <div class="avatar-initial rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center">
                          {{ userInitials }}
                        </div>
                      </div>
                      <div class="flex-grow-1 text-truncate">
                        <h6 class="mb-0 fw-semibold text-truncate">{{ authStore.displayName || 'Usuario' }}</h6>
                        <small class="text-muted d-block text-truncate">{{ authStore.user?.email }}</small>
                      </div>
                    </div>
                    <div class="mt-2">
                      <span class="badge bg-label-primary fw-semibold">
                        <i class="fa-solid fa-user-shield me-1" aria-hidden="true"></i>
                        {{ currentRoleLabel }}
                      </span>
                    </div>
                  </li>

                  <li>
                    <hr class="dropdown-divider my-2" />
                  </li>

                  <!-- Acceso 2: Preferencias de Interfaz (Customizer oficial) -->
                  <li>
                    <button
                      type="button"
                      class="dropdown-item d-flex align-items-center gap-2"
                      @click="openPreferences"
                    >
                      <i class="fa-solid fa-sliders text-muted" aria-hidden="true"></i>
                      <span>Preferencias de interfaz</span>
                    </button>
                  </li>

                  <!-- Estado Técnico / Sistema -->
                  <li>
                    <router-link
                      to="/admin/status"
                      class="dropdown-item d-flex align-items-center gap-2"
                      @click="isUserMenuOpen = false"
                    >
                      <i class="fa-solid fa-server text-muted" aria-hidden="true"></i>
                      <span>Estado Técnico</span>
                    </router-link>
                  </li>

                  <li>
                    <hr class="dropdown-divider my-2" />
                  </li>

                  <!-- Botón Cerrar Sesión -->
                  <li class="px-3 pt-1 pb-1">
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-danger w-100 d-flex align-items-center justify-content-center gap-2"
                      @click="handleLogout"
                    >
                      <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                      <span>Cerrar sesión</span>
                    </button>
                  </li>
                </ul>
              </li>
            </ul>
          </div>
        </nav>

        <!-- Contenedor Principal de Vistas -->
        <div class="content-wrapper">
          <main
            class="flex-grow-1 container-p-y"
            :class="preferencesStore.contentLayout === 'wide' ? 'container-fluid px-4' : 'container-xxl'"
          >
            <router-view />
          </main>

          <!-- Pie de Página Oficial Materialize -->
          <footer class="content-footer footer bg-footer-theme">
            <div :class="preferencesStore.contentLayout === 'wide' ? 'container-fluid px-4' : 'container-xxl'">
              <div class="footer-container d-flex align-items-center justify-content-between py-3 flex-md-row flex-column small">
                <div>
                  <strong>TravelFlow CMS &copy; {{ currentYear }}</strong> &mdash; Sistema de Gestión de Contenidos Turísticos.
                </div>
                <div class="text-muted mt-2 mt-md-0">
                  <span class="badge bg-label-secondary">v1.0.0-materialize</span>
                </div>
              </div>
            </div>
          </footer>

          <div class="content-backdrop fade"></div>
        </div>
      </div>
    </div>

    <!-- Backdrop de Menú Móvil -->
    <div
      v-if="isMobileExpanded"
      class="layout-overlay layout-menu-toggle"
      @click="closeMobileMenu"
    ></div>

    <!-- Template Customizer Oficial Materialize (Acceso Flotante + Drawer) -->
    <TfCustomizer />
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Swal from 'sweetalert2';
import { useAuthStore } from '@/stores/auth';
import { usePreferencesStore } from '@/stores/preferences';
import { TfSkeleton } from '@/design-system';
import TfThemeToggle from '@/components/admin/TfThemeToggle.vue';
import TfCustomizer from '@/components/admin/TfCustomizer.vue';

type NavItem =
  | { type: 'header'; label: string }
  | { type: 'link'; label: string; to: string; icon: string };

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const preferencesStore = usePreferencesStore();

const currentYear = new Date().getFullYear();

// Inicializar tema y preferencias de interfaz
onMounted(() => {
  preferencesStore.initPreferences(authStore.user?.ui_preferences);
  document.addEventListener('click', handleOutsideClick);
});

onUnmounted(() => {
  document.removeEventListener('click', handleOutsideClick);
});

// Estado de Menú Expandido (Mobile)
const isMobileExpanded = ref<boolean>(false);

function toggleMobileMenu() {
  isMobileExpanded.value = !isMobileExpanded.value;
}

function closeMobileMenu() {
  isMobileExpanded.value = false;
}

// Estado del Dropdown de Usuario
const isUserMenuOpen = ref<boolean>(false);

function toggleUserMenu(event: MouseEvent) {
  event.stopPropagation();
  isUserMenuOpen.value = !isUserMenuOpen.value;
}

function openPreferences() {
  isUserMenuOpen.value = false;
  preferencesStore.openCustomizer();
}

function handleOutsideClick(event: MouseEvent) {
  const target = event.target as HTMLElement | null;
  if (!target?.closest('.dropdown-user')) {
    isUserMenuOpen.value = false;
  }
}

/**
 * Menú estructural de navegación Materialize.
 * REGLA VINCULANTE: Solo opciones reales y autorizadas (sin placeholders ficticios).
 */
const navigationItems = computed<NavItem[]>(() => {
  const items: NavItem[] = [
    {
      type: 'header',
      label: 'Menú Principal',
    },
    {
      type: 'link',
      label: 'Dashboard',
      to: '/admin',
      icon: 'fa-solid fa-gauge-high',
    },
  ];

  const hasAdminHeader = authStore.hasPermission('personas.ver') || authStore.hasPermission('usuarios.ver');

  if (hasAdminHeader) {
    items.push({
      type: 'header',
      label: 'Administración',
    });

    if (authStore.hasPermission('personas.ver')) {
      items.push({
        type: 'link',
        label: 'Personas',
        to: '/admin/personas',
        icon: 'fa-solid fa-address-card',
      });
    }

    if (authStore.hasPermission('usuarios.ver')) {
      items.push({
        type: 'link',
        label: 'Usuarios',
        to: '/admin/usuarios',
        icon: 'fa-solid fa-users-gear',
      });
    }
  }

  return items;
});

function isRouteActive(to?: string): boolean {
  if (!to) return false;
  if (to === '/admin') {
    return route.path === '/admin' || route.path === '/admin/';
  }
  return route.path.startsWith(to);
}

const currentRoleLabel = computed(() => {
  if (authStore.userRoles.length > 0) {
    return authStore.userRoles[0].toUpperCase();
  }
  return 'USUARIO';
});

const userInitials = computed(() => {
  const name = authStore.displayName || 'Usuario';
  const parts = name.trim().split(' ');
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return name.substring(0, 2).toUpperCase();
});

async function handleLogout() {
  isUserMenuOpen.value = false;

  const result = await Swal.fire({
    title: '¿Cerrar sesión?',
    text: 'Se finalizará su sesión segura en TravelFlow CMS.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#685dd8',
    cancelButtonColor: '#808390',
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
.app-brand {
  height: 64px;
  display: flex;
  align-items: center;
  padding: 0 1.5rem;
}

.avatar-initial {
  width: 38px;
  height: 38px;
  font-size: 0.9rem;
}

.menu-vertical .menu-inner > .menu-item .menu-link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.dropdown-user .dropdown-menu {
  z-index: 1080;
}
</style>
