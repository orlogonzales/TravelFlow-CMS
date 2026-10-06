<template>
  <span
    class="badge d-inline-flex align-items-center gap-1"
    :class="[
      subtle
        ? `bg-${variant}-subtle text-${variant}-emphasis border border-${variant}-subtle`
        : `bg-${variant} text-white`,
      { 'rounded-pill': pill }
    ]"
  >
    <i v-if="icon" :class="icon" aria-hidden="true"></i>
    <span
      v-if="dot"
      class="rounded-circle me-1"
      :class="`bg-${variant}`"
      style="width: 6px; height: 6px; display: inline-block;"
      aria-hidden="true"
    ></span>
    <slot />
  </span>
</template>

<script setup lang="ts">
interface Props {
  variant?: 'primary' | 'secondary' | 'success' | 'danger' | 'warning' | 'info' | 'light' | 'dark';
  subtle?: boolean;
  pill?: boolean;
  dot?: boolean;
  icon?: string;
}

withDefaults(defineProps<Props>(), {
  variant: 'primary',
  subtle: true,
  pill: false,
  dot: false,
});
</script>
