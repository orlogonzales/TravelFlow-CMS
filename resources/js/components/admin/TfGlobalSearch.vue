<template>
  <div class="tf-global-search">
    <!-- Botón Toggler en el Navbar Oficial Materialize -->
    <a
      class="nav-item nav-link search-toggler d-flex align-items-center gap-2 px-0 cursor-pointer"
      href="javascript:void(0);"
      role="button"
      aria-label="Buscar en el sistema (Ctrl + K)"
      @click="openSearch"
    >
      <i class="fa-solid fa-magnifying-glass text-muted fs-5" aria-hidden="true"></i>
      <span class="d-none d-md-inline-block text-body-secondary fw-normal">
        Buscar
        <span class="search-shortcut text-muted ms-1">[CTRL + K]</span>
      </span>
    </a>

    <!-- Modal / Overlay de Búsqueda Global Materialize -->
    <teleport to="body">
      <div
        v-if="isOpen"
        class="tf-search-overlay"
        role="dialog"
        aria-modal="true"
        aria-label="Búsqueda global del sistema"
        @keydown.esc="closeSearch"
        @keydown.down.prevent="navigateDown"
        @keydown.up.prevent="navigateUp"
        @keydown.enter.prevent="selectCurrent"
      >
        <!-- Backdrop -->
        <div class="tf-search-backdrop" @click="closeSearch"></div>

        <!-- Panel de Búsqueda -->
        <div class="tf-search-panel card shadow-lg border-0">
          <!-- Cabecera / Input -->
          <div class="tf-search-header d-flex align-items-center p-3 border-bottom">
            <i class="fa-solid fa-magnifying-glass text-muted fs-5 me-3" aria-hidden="true"></i>
            <input
              ref="searchInputRef"
              v-model="query"
              type="text"
              class="form-control form-control-lg border-0 shadow-none p-0 text-heading bg-transparent"
              placeholder="Escribe para buscar páginas, módulos o acciones..."
              autocomplete="off"
              spellcheck="false"
            />
            <div class="d-flex align-items-center gap-2 ms-2">
              <button
                v-if="query"
                type="button"
                class="btn btn-sm btn-icon btn-text-secondary rounded-pill"
                aria-label="Limpiar búsqueda"
                @click="query = ''"
              >
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
              </button>
              <kbd class="badge bg-label-secondary font-monospace small px-2 py-1 cursor-pointer" @click="closeSearch">
                ESC
              </kbd>
            </div>
          </div>

          <!-- Cuerpo de Resultados -->
          <div class="tf-search-body p-3 overflow-auto" style="max-height: 420px;">
            <!-- Estado: Sin resultados -->
            <div v-if="filteredResults.length === 0" class="text-center py-5 text-muted">
              <i class="fa-solid fa-circle-question fs-2 mb-2 text-secondary" aria-hidden="true"></i>
              <p class="mb-1 fw-semibold">No se encontraron resultados para "{{ query }}"</p>
              <small>Verifica los términos de búsqueda o tus permisos asignados.</small>
            </div>

            <!-- Listado agrupado -->
            <div v-else>
              <!-- Sección: Páginas y Módulos -->
              <div v-if="groupedPages.length > 0" class="mb-3">
                <small class="text-uppercase text-muted fw-bold px-2 mb-2 d-block letter-spacing-1">
                  Páginas y Módulos
                </small>
                <div class="list-group list-group-flush rounded-2 overflow-hidden">
                  <a
                    v-for="(item, index) in groupedPages"
                    :key="item.id"
                    href="javascript:void(0);"
                    class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-2 rounded border-0"
                    :class="{ 'active bg-label-primary text-primary': activeIndex === getGlobalIndex(item) }"
                    @mouseenter="activeIndex = getGlobalIndex(item)"
                    @click="executeItem(item)"
                  >
                    <div class="d-flex align-items-center gap-3">
                      <div
                        class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                        :class="item.iconClass || 'bg-label-primary text-primary'"
                        style="width: 36px; height: 36px;"
                      >
                        <i :class="item.icon" aria-hidden="true"></i>
                      </div>
                      <div>
                        <div class="fw-semibold text-body">{{ item.title }}</div>
                        <small class="text-muted">{{ item.description }}</small>
                      </div>
                    </div>
                    <i class="fa-solid fa-chevron-right text-muted small" aria-hidden="true"></i>
                  </a>
                </div>
              </div>

              <!-- Sección: Acciones Rápidas -->
              <div v-if="groupedActions.length > 0">
                <small class="text-uppercase text-muted fw-bold px-2 mb-2 d-block letter-spacing-1">
                  Acciones Rápidas
                </small>
                <div class="list-group list-group-flush rounded-2 overflow-hidden">
                  <a
                    v-for="(item, index) in groupedActions"
                    :key="item.id"
                    href="javascript:void(0);"
                    class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-2 rounded border-0"
                    :class="{ 'active bg-label-primary text-primary': activeIndex === getGlobalIndex(item) }"
                    @mouseenter="activeIndex = getGlobalIndex(item)"
                    @click="executeItem(item)"
                  >
                    <div class="d-flex align-items-center gap-3">
                      <div
                        class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                        :class="item.iconClass || 'bg-label-info text-info'"
                        style="width: 36px; height: 36px;"
                      >
                        <i :class="item.icon" aria-hidden="true"></i>
                      </div>
                      <div>
                        <div class="fw-semibold text-body">{{ item.title }}</div>
                        <small class="text-muted">{{ item.description }}</small>
                      </div>
                    </div>
                    <span class="badge bg-label-secondary small font-monospace">Acción</span>
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Pie del Modal con Instrucciones de Teclado -->
          <div class="tf-search-footer p-2 px-3 border-top bg-light small d-flex flex-wrap align-items-center justify-content-between text-muted">
            <div class="d-flex align-items-center gap-3">
              <span><kbd class="badge bg-secondary-subtle text-body border me-1">↑</kbd><kbd class="badge bg-secondary-subtle text-body border me-1">↓</kbd> Navegar</span>
              <span><kbd class="badge bg-secondary-subtle text-body border me-1">↵</kbd> Seleccionar</span>
              <span><kbd class="badge bg-secondary-subtle text-body border me-1">ESC</kbd> Cerrar</span>
            </div>
            <div class="fw-semibold small">TravelFlow CMS</div>
          </div>
        </div>
      </div>
    </teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { usePreferencesStore } from '@/stores/preferences';

interface SearchItem {
  id: string;
  category: 'page' | 'action';
  title: string;
  description: string;
  icon: string;
  iconClass?: string;
  route?: string;
  action?: () => void;
  permission?: string;
}

const router = useRouter();
const authStore = useAuthStore();
const preferencesStore = usePreferencesStore();

const isOpen = ref<boolean>(false);
const query = ref<string>('');
const activeIndex = ref<number>(0);
const searchInputRef = ref<HTMLInputElement | null>(null);

/**
 * Catálogo soberano y extensible de elementos indexados para búsqueda global.
 * REGLA VINCULANTE: Solo elementos reales con verificación estricta de permisos.
 */
const allSearchItems = computed<SearchItem[]>(() => [
  {
    id: 'page-dashboard',
    category: 'page',
    title: 'Dashboard Principal',
    description: 'Resumen ejecutivo, métricas del CMS y accesos directos',
    icon: 'fa-solid fa-gauge-high',
    iconClass: 'bg-label-primary text-primary',
    route: '/admin',
  },
  {
    id: 'page-personas',
    category: 'page',
    title: 'Módulo Personas',
    description: 'Gestión de identidad humana, documentos y registros civiles',
    icon: 'fa-solid fa-address-card',
    iconClass: 'bg-label-info text-info',
    route: '/admin/personas',
    permission: 'personas.ver',
  },
  {
    id: 'page-usuarios',
    category: 'page',
    title: 'Módulo Usuarios',
    description: 'Cuentas del sistema, vinculación con personas y credenciales',
    icon: 'fa-solid fa-users-gear',
    iconClass: 'bg-label-primary text-primary',
    route: '/admin/usuarios',
    permission: 'usuarios.ver',
  },
  {
    id: 'page-roles',
    category: 'page',
    title: 'Roles y Permisos',
    description: 'Matriz de privilegios, control de acceso RBAC y perfiles',
    icon: 'fa-solid fa-user-shield',
    iconClass: 'bg-label-warning text-warning',
    route: '/admin/roles',
    permission: 'roles.ver',
  },
  {
    id: 'action-nueva-persona',
    category: 'action',
    title: 'Registrar nueva Persona',
    description: 'Crear una ficha de persona física en el registro central',
    icon: 'fa-solid fa-user-plus',
    iconClass: 'bg-label-success text-success',
    route: '/admin/personas?action=create',
    permission: 'personas.crear',
  },
  {
    id: 'action-nuevo-usuario',
    category: 'action',
    title: 'Crear nueva Cuenta de Usuario',
    description: 'Vincular una persona elegible a una credencial de acceso',
    icon: 'fa-solid fa-user-check',
    iconClass: 'bg-label-primary text-primary',
    route: '/admin/usuarios?action=create',
    permission: 'usuarios.crear',
  },
  {
    id: 'action-nuevo-rol',
    category: 'action',
    title: 'Crear nuevo Rol RBAC',
    description: 'Definir un perfil de permisos personalizado para el CMS',
    icon: 'fa-solid fa-shield-plus',
    iconClass: 'bg-label-warning text-warning',
    route: '/admin/roles?action=create',
    permission: 'roles.crear',
  },
  {
    id: 'action-preferencias',
    category: 'action',
    title: 'Preferencias de Interfaz',
    description: 'Configurar tema visual, layout compacto/ancho y sidebar',
    icon: 'fa-solid fa-sliders',
    iconClass: 'bg-label-secondary text-secondary',
    action: () => {
      preferencesStore.openCustomizer();
    },
  },
]);

/**
 * Filtrado dinámico por permisos del usuario y coincidencia de búsqueda.
 */
const authorizedItems = computed(() => {
  return allSearchItems.value.filter(item => {
    if (!item.permission) return true;
    return authStore.hasPermission(item.permission);
  });
});

const filteredResults = computed(() => {
  const clean = query.value.trim().toLowerCase();
  if (!clean) return authorizedItems.value;

  return authorizedItems.value.filter(item => {
    return (
      item.title.toLowerCase().includes(clean) ||
      item.description.toLowerCase().includes(clean)
    );
  });
});

const groupedPages = computed(() => {
  return filteredResults.value.filter(item => item.category === 'page');
});

const groupedActions = computed(() => {
  return filteredResults.value.filter(item => item.category === 'action');
});

function getGlobalIndex(targetItem: SearchItem): number {
  return filteredResults.value.findIndex(item => item.id === targetItem.id);
}

watch(query, () => {
  activeIndex.value = 0;
});

function openSearch() {
  isOpen.value = true;
  query.value = '';
  activeIndex.value = 0;
  nextTick(() => {
    searchInputRef.value?.focus();
  });
}

function closeSearch() {
  isOpen.value = false;
  query.value = '';
}

function navigateDown() {
  if (filteredResults.value.length === 0) return;
  activeIndex.value = (activeIndex.value + 1) % filteredResults.value.length;
}

function navigateUp() {
  if (filteredResults.value.length === 0) return;
  activeIndex.value =
    (activeIndex.value - 1 + filteredResults.value.length) % filteredResults.value.length;
}

function selectCurrent() {
  const current = filteredResults.value[activeIndex.value];
  if (current) {
    executeItem(current);
  }
}

function executeItem(item: SearchItem) {
  closeSearch();
  if (item.action) {
    item.action();
  } else if (item.route) {
    router.push(item.route);
  }
}

// Global shortcut listener (CTRL + K / CMD + K)
function handleGlobalKeydown(event: KeyboardEvent) {
  if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
    event.preventDefault();
    if (isOpen.value) {
      closeSearch();
    } else {
      openSearch();
    }
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleGlobalKeydown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleGlobalKeydown);
});
</script>

<style scoped>
.tf-search-overlay {
  position: fixed;
  inset: 0;
  z-index: 1090;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding-top: 5rem;
}

.tf-search-backdrop {
  position: absolute;
  inset: 0;
  background-color: rgba(34, 38, 54, 0.6);
  backdrop-filter: blur(3px);
}

.tf-search-panel {
  position: relative;
  z-index: 1091;
  width: 100%;
  max-width: 640px;
  background: var(--bs-body-bg, #ffffff);
  border-radius: 0.75rem;
  overflow: hidden;
}

.cursor-pointer {
  cursor: pointer;
}

.letter-spacing-1 {
  letter-spacing: 0.05rem;
}

.search-shortcut {
  font-size: 0.75rem;
  opacity: 0.75;
}
</style>
