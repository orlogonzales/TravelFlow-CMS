<template>
  <li class="nav-item dropdown">
    <button
      id="bd-theme"
      class="nav-link dropdown-toggle d-flex align-items-center gap-1"
      type="button"
      data-bs-toggle="dropdown"
      aria-expanded="false"
      aria-label="Seleccionar tema visual (Claro, Oscuro o Sistema)"
    >
      <i :class="currentIcon" aria-hidden="true"></i>
      <span class="d-none d-md-inline ms-1 small">{{ currentLabel }}</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="bd-theme">
      <li>
        <button
          type="button"
          class="dropdown-item d-flex align-items-center gap-2"
          :class="{ active: colorMode === 'light' }"
          @click="setColorMode('light')"
        >
          <i class="fa-solid fa-sun text-warning w-20 text-center" aria-hidden="true"></i>
          <span>Claro (Light)</span>
          <i v-if="colorMode === 'light'" class="fa-solid fa-check ms-auto" aria-hidden="true"></i>
        </button>
      </li>
      <li>
        <button
          type="button"
          class="dropdown-item d-flex align-items-center gap-2"
          :class="{ active: colorMode === 'dark' }"
          @click="setColorMode('dark')"
        >
          <i class="fa-solid fa-moon text-info w-20 text-center" aria-hidden="true"></i>
          <span>Oscuro (Dark)</span>
          <i v-if="colorMode === 'dark'" class="fa-solid fa-check ms-auto" aria-hidden="true"></i>
        </button>
      </li>
      <li>
        <button
          type="button"
          class="dropdown-item d-flex align-items-center gap-2"
          :class="{ active: colorMode === 'auto' }"
          @click="setColorMode('auto')"
        >
          <i class="fa-solid fa-circle-half-stroke text-secondary w-20 text-center" aria-hidden="true"></i>
          <span>Sistema (Auto)</span>
          <i v-if="colorMode === 'auto'" class="fa-solid fa-check ms-auto" aria-hidden="true"></i>
        </button>
      </li>
    </ul>
  </li>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useColorMode } from '@adminlte/vue';

const { colorMode, setColorMode } = useColorMode();

const currentIcon = computed(() => {
  if (colorMode.value === 'light') return 'fa-solid fa-sun text-warning';
  if (colorMode.value === 'dark') return 'fa-solid fa-moon text-info';
  return 'fa-solid fa-circle-half-stroke text-secondary';
});

const currentLabel = computed(() => {
  if (colorMode.value === 'light') return 'Claro';
  if (colorMode.value === 'dark') return 'Oscuro';
  return 'Auto';
});
</script>

<style scoped>
.w-20 {
  width: 20px;
}
</style>
