<template>
  <div class="text-center py-5 px-3">
    <div
      class="d-inline-flex align-items-center justify-content-center rounded-circle bg-body-secondary mb-3 text-muted"
      style="width: 72px; height: 72px;"
    >
      <i :class="[icon, 'fs-2']" aria-hidden="true"></i>
    </div>
    <h5 class="fw-bold mb-1 text-body">{{ title }}</h5>
    <p v-if="description" class="text-muted small mb-3 mx-auto" style="max-width: 420px;">
      {{ description }}
    </p>
    <div v-if="$slots.actions" class="d-flex justify-content-center gap-2 mt-2">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  icon?: string;
  title: string;
  description?: string;
}

withDefaults(defineProps<Props>(), {
  icon: 'fa-solid fa-folder-open',
  description: '',
});
</script>
