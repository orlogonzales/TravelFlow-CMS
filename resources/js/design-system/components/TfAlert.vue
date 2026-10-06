<template>
  <div
    class="alert d-flex align-items-center gap-2 mb-3"
    :class="[
      `alert-${variant}`,
      { 'alert-dismissible fade show': dismissible }
    ]"
    role="alert"
  >
    <i :class="alertIcon" class="fs-5 flex-shrink-0" aria-hidden="true"></i>
    <div class="flex-grow-1">
      <strong v-if="title" class="d-block">{{ title }}</strong>
      <slot>{{ message }}</slot>
    </div>
    <button
      v-if="dismissible"
      type="button"
      class="btn-close"
      aria-label="Cerrar"
      @click="$emit('dismiss')"
    ></button>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  variant?: 'primary' | 'secondary' | 'success' | 'danger' | 'warning' | 'info' | 'light' | 'dark';
  title?: string;
  message?: string;
  icon?: string;
  dismissible?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'danger',
  dismissible: false,
});

defineEmits<{
  (e: 'dismiss'): void;
}>();

const alertIcon = computed(() => {
  if (props.icon) return props.icon;
  switch (props.variant) {
    case 'danger':
      return 'fa-solid fa-circle-exclamation';
    case 'warning':
      return 'fa-solid fa-triangle-exclamation';
    case 'success':
      return 'fa-solid fa-circle-check';
    case 'info':
    case 'primary':
      return 'fa-solid fa-circle-info';
    default:
      return 'fa-solid fa-bell';
  }
});
</script>
