<template>
  <li class="nav-item dropdown">
    <button
      id="bd-theme"
      class="nav-link dropdown-toggle btn btn-text-secondary rounded-pill btn-icon dropdown-toggle-hide-arrow d-flex align-items-center justify-content-center"
      type="button"
      data-bs-toggle="dropdown"
      aria-expanded="false"
      aria-label="Seleccionar tema visual (Claro, Oscuro, Semi-oscuro o Sistema)"
    >
      <i :class="currentIcon" aria-hidden="true"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="bd-theme">
      <li>
        <button
          type="button"
          class="dropdown-item d-flex align-items-center gap-2"
          :class="{ active: themeMode === 'light' }"
          @click="setTheme('light')"
        >
          <i class="fa-solid fa-sun text-warning w-20 text-center" aria-hidden="true"></i>
          <span>Claro (Light)</span>
          <i v-if="themeMode === 'light'" class="fa-solid fa-check ms-auto" aria-hidden="true"></i>
        </button>
      </li>
      <li>
        <button
          type="button"
          class="dropdown-item d-flex align-items-center gap-2"
          :class="{ active: themeMode === 'dark' }"
          @click="setTheme('dark')"
        >
          <i class="fa-solid fa-moon text-info w-20 text-center" aria-hidden="true"></i>
          <span>Oscuro (Dark)</span>
          <i v-if="themeMode === 'dark'" class="fa-solid fa-check ms-auto" aria-hidden="true"></i>
        </button>
      </li>
      <li>
        <button
          type="button"
          class="dropdown-item d-flex align-items-center gap-2"
          :class="{ active: themeMode === 'semi-dark' }"
          @click="setTheme('semi-dark')"
        >
          <i class="fa-solid fa-circle-half-stroke text-primary w-20 text-center" aria-hidden="true"></i>
          <span>Semi-oscuro (Sidebar Dark)</span>
          <i v-if="themeMode === 'semi-dark'" class="fa-solid fa-check ms-auto" aria-hidden="true"></i>
        </button>
      </li>
      <li>
        <button
          type="button"
          class="dropdown-item d-flex align-items-center gap-2"
          :class="{ active: themeMode === 'system' }"
          @click="setTheme('system')"
        >
          <i class="fa-solid fa-desktop text-secondary w-20 text-center" aria-hidden="true"></i>
          <span>Sistema (Auto)</span>
          <i v-if="themeMode === 'system'" class="fa-solid fa-check ms-auto" aria-hidden="true"></i>
        </button>
      </li>
    </ul>
  </li>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useTheme } from '@/composables/useTheme';

const { themeMode, setTheme } = useTheme();

const currentIcon = computed(() => {
  if (themeMode.value === 'light') return 'fa-solid fa-sun text-warning';
  if (themeMode.value === 'dark') return 'fa-solid fa-moon text-info';
  if (themeMode.value === 'semi-dark') return 'fa-solid fa-circle-half-stroke text-primary';
  return 'fa-solid fa-desktop text-secondary';
});
</script>

<style scoped>
.w-20 {
  width: 20px;
}
</style>
