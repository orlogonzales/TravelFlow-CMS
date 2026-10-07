<template>
  <button
    :type="type"
    class="btn d-inline-flex align-items-center justify-content-center gap-2 waves-effect"
    :class="[
      `btn-${variant}`,
      size ? `btn-${size}` : '',
      { 'w-100': block },
      customClass
    ]"
    :disabled="disabled || loading"
    v-bind="$attrs"
  >
    <i v-if="loading" class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
    <i v-else-if="icon" :class="icon" aria-hidden="true"></i>
    <slot />
  </button>
</template>

<script setup lang="ts">
interface Props {
  variant?:
    | 'primary'
    | 'secondary'
    | 'success'
    | 'danger'
    | 'warning'
    | 'info'
    | 'light'
    | 'dark'
    | 'outline-primary'
    | 'outline-secondary'
    | 'outline-danger'
    | 'label-primary'
    | 'label-secondary'
    | 'label-success'
    | 'label-danger'
    | 'label-warning'
    | 'label-info';
  size?: 'sm' | 'lg';
  type?: 'button' | 'submit' | 'reset';
  disabled?: boolean;
  loading?: boolean;
  block?: boolean;
  icon?: string;
  customClass?: string;
}

withDefaults(defineProps<Props>(), {
  variant: 'primary',
  type: 'button',
  disabled: false,
  loading: false,
  block: false,
  customClass: '',
});
</script>
