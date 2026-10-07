<template>
  <div class="tf-customizer-wrapper">
    <!-- 1. Botón Flotante Oficial Materialize (Acceso Rápido) -->
    <button
      type="button"
      class="template-customizer-open-btn"
      title="Preferencias de interfaz"
      aria-label="Abrir preferencias de interfaz"
      @click="preferencesStore.openCustomizer"
    >
      <i class="fa-solid fa-gear" aria-hidden="true"></i>
    </button>

    <!-- 2. Backdrop Semi-transparente -->
    <div
      v-if="preferencesStore.isCustomizerOpen"
      class="template-customizer-backdrop"
      aria-hidden="true"
      @click="preferencesStore.closeCustomizer"
    ></div>

    <!-- 3. Panel Deslizable (Offcanvas / Drawer) -->
    <aside
      id="template-customizer"
      class="card rounded-0"
      :class="{ 'template-customizer-open': preferencesStore.isCustomizerOpen }"
      role="dialog"
      aria-modal="true"
      aria-labelledby="customizer-title"
    >
      <!-- Cabecera del Customizer -->
      <div class="template-customizer-header p-4 d-flex align-items-center justify-content-between">
        <div>
          <h5 id="customizer-title" class="mb-0 fw-bold text-heading">
            Preferencias de Interfaz
          </h5>
          <small class="text-muted">Personaliza la apariencia a tu medida</small>
        </div>

        <div class="d-flex align-items-center gap-2">
          <!-- Botón de Reset Rápido -->
          <button
            type="button"
            class="btn btn-sm btn-icon btn-text-secondary rounded-pill text-heading"
            title="Restablecer valores predeterminados"
            aria-label="Restablecer valores predeterminados"
            :disabled="preferencesStore.isSaving"
            @click="confirmReset"
          >
            <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
          </button>

          <!-- Botón de Cierre -->
          <button
            type="button"
            class="btn btn-sm btn-icon btn-text-secondary rounded-pill text-heading"
            title="Cerrar panel"
            aria-label="Cerrar panel"
            @click="preferencesStore.closeCustomizer"
          >
            <i class="fa-solid fa-xmark fs-5" aria-hidden="true"></i>
          </button>
        </div>
      </div>

      <!-- Cuerpo Scrolleable de Opciones -->
      <div class="template-customizer-body p-4">
        <!-- Indicador de Guardado Discreto -->
        <div v-if="preferencesStore.isSaving" class="alert alert-primary py-2 px-3 mb-4 small d-flex align-items-center gap-2">
          <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
          <span>Guardando preferencias...</span>
        </div>

        <!-- ================= SECCIÓN 1: TEMATIZACIÓN ================= -->
        <div class="mb-4">
          <div class="d-flex align-items-center gap-2 mb-3">
            <span class="badge bg-label-primary text-uppercase px-2 py-1">Tematización</span>
          </div>

          <!-- Selector de Modo de Color (Claro / Oscuro / Sistema) -->
          <div class="mb-3">
            <label class="form-label d-block fw-semibold mb-2">Tema visual</label>
            <div class="row g-2">
              <!-- Opción Claro -->
              <div class="col-4">
                <div
                  class="customizer-opt-card"
                  :class="{ active: preferencesStore.theme === 'light' }"
                  tabindex="0"
                  role="button"
                  aria-label="Tema claro"
                  @click="preferencesStore.setTheme('light')"
                  @keydown.enter="preferencesStore.setTheme('light')"
                  @keydown.space.prevent="preferencesStore.setTheme('light')"
                >
                  <div class="opt-icon">
                    <i class="fa-regular fa-sun" aria-hidden="true"></i>
                  </div>
                  <div class="opt-label">Claro</div>
                </div>
              </div>

              <!-- Opción Oscuro -->
              <div class="col-4">
                <div
                  class="customizer-opt-card"
                  :class="{ active: preferencesStore.theme === 'dark' }"
                  tabindex="0"
                  role="button"
                  aria-label="Tema oscuro"
                  @click="preferencesStore.setTheme('dark')"
                  @keydown.enter="preferencesStore.setTheme('dark')"
                  @keydown.space.prevent="preferencesStore.setTheme('dark')"
                >
                  <div class="opt-icon">
                    <i class="fa-solid fa-moon" aria-hidden="true"></i>
                  </div>
                  <div class="opt-label">Oscuro</div>
                </div>
              </div>

              <!-- Opción Sistema -->
              <div class="col-4">
                <div
                  class="customizer-opt-card"
                  :class="{ active: preferencesStore.theme === 'system' }"
                  tabindex="0"
                  role="button"
                  aria-label="Tema del sistema"
                  @click="preferencesStore.setTheme('system')"
                  @keydown.enter="preferencesStore.setTheme('system')"
                  @keydown.space.prevent="preferencesStore.setTheme('system')"
                >
                  <div class="opt-icon">
                    <i class="fa-solid fa-desktop" aria-hidden="true"></i>
                  </div>
                  <div class="opt-label">Sistema</div>
                </div>
              </div>
            </div>
            <div class="form-text small text-muted mt-1">
              "Sistema" adapta el contraste automáticamente según tu sistema operativo.
            </div>
          </div>

          <!-- Switch Semi-Dark (Menú lateral oscuro con contenido claro) -->
          <div v-if="!preferencesStore.isDark" class="p-3 bg-light rounded-2 border mt-3">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <label for="switch-semi-dark" class="form-label mb-0 fw-semibold cursor-pointer">
                  Menú lateral semi-oscuro
                </label>
                <small class="text-muted d-block">
                  Mantiene el menú vertical en modo oscuro para mayor contraste.
                </small>
              </div>
              <div class="form-check form-switch mb-0">
                <input
                  id="switch-semi-dark"
                  class="form-check-input"
                  type="checkbox"
                  role="switch"
                  :checked="preferencesStore.semiDark"
                  @change="handleSemiDarkToggle"
                />
              </div>
            </div>
          </div>
        </div>

        <hr class="my-4" />

        <!-- ================= SECCIÓN 2: DISPOSICIÓN & LAYOUT ================= -->
        <div class="mb-4">
          <div class="d-flex align-items-center gap-2 mb-3">
            <span class="badge bg-label-primary text-uppercase px-2 py-1">Disposición & Layout</span>
          </div>

          <!-- Menú Lateral: Expandido vs Contraído -->
          <div class="mb-3">
            <label class="form-label d-block fw-semibold mb-2">Menú lateral (Sidebar)</label>
            <div class="row g-2">
              <div class="col-6">
                <div
                  class="customizer-opt-card"
                  :class="{ active: !preferencesStore.sidebarCollapsed }"
                  tabindex="0"
                  role="button"
                  aria-label="Menú lateral expandido"
                  @click="preferencesStore.setSidebarCollapsed(false)"
                  @keydown.enter="preferencesStore.setSidebarCollapsed(false)"
                  @keydown.space.prevent="preferencesStore.setSidebarCollapsed(false)"
                >
                  <div class="opt-icon">
                    <i class="fa-solid fa-bars" aria-hidden="true"></i>
                  </div>
                  <div class="opt-label">Expandido</div>
                </div>
              </div>

              <div class="col-6">
                <div
                  class="customizer-opt-card"
                  :class="{ active: preferencesStore.sidebarCollapsed }"
                  tabindex="0"
                  role="button"
                  aria-label="Menú lateral contraído"
                  @click="preferencesStore.setSidebarCollapsed(true)"
                  @keydown.enter="preferencesStore.setSidebarCollapsed(true)"
                  @keydown.space.prevent="preferencesStore.setSidebarCollapsed(true)"
                >
                  <div class="opt-icon">
                    <i class="fa-solid fa-bars-staggered" aria-hidden="true"></i>
                  </div>
                  <div class="opt-label">Contraído</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Ancho de Contenido: Compacto vs Amplio -->
          <div class="mb-3">
            <label class="form-label d-block fw-semibold mb-2">Ancho del contenido</label>
            <div class="row g-2">
              <div class="col-6">
                <div
                  class="customizer-opt-card"
                  :class="{ active: preferencesStore.contentLayout === 'compact' }"
                  tabindex="0"
                  role="button"
                  aria-label="Ancho compacto"
                  @click="preferencesStore.setContentLayout('compact')"
                  @keydown.enter="preferencesStore.setContentLayout('compact')"
                  @keydown.space.prevent="preferencesStore.setContentLayout('compact')"
                >
                  <div class="opt-icon">
                    <i class="fa-solid fa-table-columns" aria-hidden="true"></i>
                  </div>
                  <div class="opt-label">Compacto (1200px)</div>
                </div>
              </div>

              <div class="col-6">
                <div
                  class="customizer-opt-card"
                  :class="{ active: preferencesStore.contentLayout === 'wide' }"
                  tabindex="0"
                  role="button"
                  aria-label="Ancho fluido completo"
                  @click="preferencesStore.setContentLayout('wide')"
                  @keydown.enter="preferencesStore.setContentLayout('wide')"
                  @keydown.space.prevent="preferencesStore.setContentLayout('wide')"
                >
                  <div class="opt-icon">
                    <i class="fa-solid fa-maximize" aria-hidden="true"></i>
                  </div>
                  <div class="opt-label">Amplio (Fluido)</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Barra de Navegación: Fija vs Estática -->
          <div class="mb-3">
            <label class="form-label d-block fw-semibold mb-2">Barra superior (Navbar)</label>
            <div class="row g-2">
              <div class="col-6">
                <div
                  class="customizer-opt-card"
                  :class="{ active: preferencesStore.navbarType === 'sticky' }"
                  tabindex="0"
                  role="button"
                  aria-label="Navbar flotante fija"
                  @click="preferencesStore.setNavbarType('sticky')"
                  @keydown.enter="preferencesStore.setNavbarType('sticky')"
                  @keydown.space.prevent="preferencesStore.setNavbarType('sticky')"
                >
                  <div class="opt-icon">
                    <i class="fa-solid fa-thumbtack" aria-hidden="true"></i>
                  </div>
                  <div class="opt-label">Fija (Sticky)</div>
                </div>
              </div>

              <div class="col-6">
                <div
                  class="customizer-opt-card"
                  :class="{ active: preferencesStore.navbarType === 'static' }"
                  tabindex="0"
                  role="button"
                  aria-label="Navbar estática con scroll"
                  @click="preferencesStore.setNavbarType('static')"
                  @keydown.enter="preferencesStore.setNavbarType('static')"
                  @keydown.space.prevent="preferencesStore.setNavbarType('static')"
                >
                  <div class="opt-icon">
                    <i class="fa-solid fa-arrows-up-down" aria-hidden="true"></i>
                  </div>
                  <div class="opt-label">Estática</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <hr class="my-4" />

        <!-- Botón de Reset Principal con Confirmación -->
        <div class="d-grid">
          <button
            type="button"
            class="btn btn-outline-danger d-flex align-items-center justify-content-center gap-2"
            :disabled="preferencesStore.isSaving"
            @click="confirmReset"
          >
            <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
            <span>Restablecer valores predeterminados</span>
          </button>
        </div>
      </div>
    </aside>
  </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue';
import Swal from 'sweetalert2';
import { usePreferencesStore } from '@/stores/preferences';

const preferencesStore = usePreferencesStore();

function handleSemiDarkToggle(event: Event) {
  const target = event.target as HTMLInputElement;
  preferencesStore.setSemiDark(target.checked);
}

async function confirmReset() {
  const result = await Swal.fire({
    title: '¿Restablecer preferencias visuales?',
    text: 'Se restaurará la configuración oficial predeterminada de la interfaz para tu cuenta.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#685dd8',
    cancelButtonColor: '#808390',
    confirmButtonText: 'Sí, restablecer',
    cancelButtonText: 'Cancelar',
    reverseButtons: true,
  });

  if (result.isConfirmed) {
    await preferencesStore.resetPreferences();

    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'Preferencias restablecidas correctamente',
      showConfirmButton: false,
      timer: 2500,
      timerProgressBar: true,
    });
  }
}

function handleKeyDown(event: KeyboardEvent) {
  if (event.key === 'Escape' && preferencesStore.isCustomizerOpen) {
    preferencesStore.closeCustomizer();
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown);
});
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}
</style>
