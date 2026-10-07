import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { useApi } from '@/composables/useApi';

export interface UiPreferences {
  theme: 'light' | 'dark' | 'system';
  semi_dark: boolean;
  sidebar_collapsed: boolean;
  content_layout: 'compact' | 'wide';
  navbar_type: 'sticky' | 'static';
}

export const SOVEREIGN_DEFAULTS: UiPreferences = {
  theme: 'system',
  semi_dark: true,
  sidebar_collapsed: false,
  content_layout: 'compact',
  navbar_type: 'sticky',
};

const STORAGE_KEY = 'tf-ui-preferences';

export const usePreferencesStore = defineStore('preferences', () => {
  const api = useApi();

  const preferences = ref<UiPreferences>({ ...SOVEREIGN_DEFAULTS });
  const isCustomizerOpen = ref<boolean>(false);
  const isSaving = ref<boolean>(false);
  const isInitialized = ref<boolean>(false);

  // Sync debounce timer
  let syncTimer: ReturnType<typeof setTimeout> | null = null;
  let mediaQueryListenerAttached = false;

  // Getters computados
  const theme = computed(() => preferences.value.theme);
  const semiDark = computed(() => preferences.value.semi_dark);
  const sidebarCollapsed = computed(() => preferences.value.sidebar_collapsed);
  const contentLayout = computed(() => preferences.value.content_layout);
  const navbarType = computed(() => preferences.value.navbar_type);

  // Resuelve si visualmente el modo activo es dark
  const isDark = computed(() => {
    if (preferences.value.theme === 'dark') return true;
    if (preferences.value.theme === 'light') return false;
    return typeof window !== 'undefined' && window.matchMedia('(prefers-color-scheme: dark)').matches;
  });

  /**
   * Aplica las preferencias visuales al DOM de forma reactiva e inmediata.
   */
  function applyToDom(): void {
    if (typeof document === 'undefined') return;

    const root = document.documentElement;
    const menu = document.getElementById('layout-menu');
    const layoutWrapper = document.querySelector('.layout-wrapper');

    // 1. Aplicar Theme a <html>
    const effectiveTheme = isDark.value ? 'dark' : 'light';
    root.setAttribute('data-bs-theme', effectiveTheme);

    // 2. Aplicar Semi-Dark al Menú Lateral
    if (menu) {
      if (!isDark.value && preferences.value.semi_dark) {
        menu.setAttribute('data-bs-theme', 'dark');
      } else {
        menu.removeAttribute('data-bs-theme');
      }
    }

    // 3. Aplicar estado Collapsed al Sidebar en html y layoutWrapper
    if (preferences.value.sidebar_collapsed) {
      root.classList.add('layout-menu-collapsed');
      if (layoutWrapper) {
        layoutWrapper.classList.add('layout-menu-collapsed');
      }
    } else {
      root.classList.remove('layout-menu-collapsed');
      root.classList.remove('layout-menu-hover');
      if (layoutWrapper) {
        layoutWrapper.classList.remove('layout-menu-collapsed');
      }
    }

    // 4. Aplicar Navbar Type (fixed/sticky vs static)
    if (preferences.value.navbar_type === 'sticky') {
      root.classList.add('layout-navbar-fixed');
    } else {
      root.classList.remove('layout-navbar-fixed');
    }

    // 5. Guardar en localStorage como caché inmediata
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(preferences.value));
      // Sincronizar clave heredada para el sidebar
      localStorage.setItem('tf-sidebar-collapsed', String(preferences.value.sidebar_collapsed));
    } catch {
      // Manejo seguro de cuotas o restricciones de storage
    }
  }

  /**
   * Sincroniza las preferencias con el backend de manera segura y debounced.
   */
  function triggerBackendSync(): void {
    if (syncTimer) {
      clearTimeout(syncTimer);
    }

    syncTimer = setTimeout(async () => {
      try {
        isSaving.value = true;
        await api.patch('/api/auth/preferences', preferences.value);
      } catch (error) {
        // En caso de estar offline o no autenticado, localStorage retiene los cambios locales
      } finally {
        isSaving.value = false;
      }
    }, 400);
  }

  /**
   * Inicializa las preferencias visuales combinando:
   * 1. Defaults soberanos
   * 2. Caché local en localStorage (aplicación ultra-rápida anti-parpadeo)
   * 3. Preferencias del usuario en backend (si está autenticado)
   */
  function initPreferences(userBackendPreferences?: Partial<UiPreferences>): void {
    // 1. Cargar desde localStorage si existe
    try {
      const cached = localStorage.getItem(STORAGE_KEY);
      if (cached) {
        const parsed = JSON.parse(cached);
        preferences.value = { ...SOVEREIGN_DEFAULTS, ...parsed };
      }
    } catch {
      preferences.value = { ...SOVEREIGN_DEFAULTS };
    }

    // 2. Si vienen preferencias del backend, tienen prioridad soberana para el usuario
    if (userBackendPreferences && Object.keys(userBackendPreferences).length > 0) {
      preferences.value = { ...preferences.value, ...userBackendPreferences };
    }

    applyToDom();

    // 3. Registrar listener dinámico para 'prefers-color-scheme' si el usuario seleccionó 'system'
    if (typeof window !== 'undefined' && !mediaQueryListenerAttached) {
      mediaQueryListenerAttached = true;
      const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
      mediaQuery.addEventListener('change', () => {
        if (preferences.value.theme === 'system') {
          applyToDom();
        }
      });
    }

    isInitialized.value = true;
  }

  // Métodos de mutación visual
  function setTheme(newTheme: 'light' | 'dark' | 'system'): void {
    preferences.value.theme = newTheme;
    applyToDom();
    triggerBackendSync();
  }

  function setSemiDark(enabled: boolean): void {
    preferences.value.semi_dark = enabled;
    applyToDom();
    triggerBackendSync();
  }

  function setSidebarCollapsed(collapsed: boolean): void {
    preferences.value.sidebar_collapsed = collapsed;
    applyToDom();
    triggerBackendSync();
  }

  function toggleSidebarCollapse(): void {
    setSidebarCollapsed(!preferences.value.sidebar_collapsed);
  }

  function setSidebarHover(hovering: boolean): void {
    if (typeof document === 'undefined') return;
    if (preferences.value.sidebar_collapsed) {
      if (hovering) {
        document.documentElement.classList.add('layout-menu-hover');
      } else {
        document.documentElement.classList.remove('layout-menu-hover');
      }
    }
  }

  function setContentLayout(layout: 'compact' | 'wide'): void {
    preferences.value.content_layout = layout;
    applyToDom();
    triggerBackendSync();
  }

  function setNavbarType(type: 'sticky' | 'static'): void {
    preferences.value.navbar_type = type;
    applyToDom();
    triggerBackendSync();
  }

  /**
   * Restablece todas las preferencias visuales a los valores oficiales predeterminados.
   */
  async function resetPreferences(): Promise<void> {
    preferences.value = { ...SOVEREIGN_DEFAULTS };
    applyToDom();

    try {
      isSaving.value = true;
      await api.post('/api/auth/preferences/reset');
    } catch {
      // Ignorar si no está autenticado
    } finally {
      isSaving.value = false;
    }
  }

  // Control del drawer/offcanvas
  function openCustomizer(): void {
    isCustomizerOpen.value = true;
  }

  function closeCustomizer(): void {
    isCustomizerOpen.value = false;
  }

  function toggleCustomizer(): void {
    isCustomizerOpen.value = !isCustomizerOpen.value;
  }

  return {
    preferences,
    isCustomizerOpen,
    isSaving,
    isInitialized,
    theme,
    semiDark,
    sidebarCollapsed,
    contentLayout,
    navbarType,
    isDark,
    initPreferences,
    applyToDom,
    setTheme,
    setSemiDark,
    setSidebarCollapsed,
    toggleSidebarCollapse,
    setSidebarHover,
    setContentLayout,
    setNavbarType,
    resetPreferences,
    openCustomizer,
    closeCustomizer,
    toggleCustomizer,
  };
});
