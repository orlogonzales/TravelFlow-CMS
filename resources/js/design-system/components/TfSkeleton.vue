<template>
  <div class="placeholder-glow" :class="containerClass" aria-hidden="true">
    <div
      v-for="index in lines"
      :key="index"
      class="placeholder"
      :class="[
        `bg-${variant}`,
        size ? `placeholder-${size}` : '',
        animation ? `placeholder-${animation}` : '',
        getLineWidth(index)
      ]"
      :style="customStyle"
    ></div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  lines?: number;
  width?: string; // e.g. 'col-12', 'w-75'
  height?: string; // e.g. '20px'
  size?: 'xs' | 'sm' | 'lg';
  variant?: 'secondary' | 'light' | 'primary';
  animation?: 'glow' | 'wave';
  containerClass?: string;
  circle?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  lines: 1,
  width: 'col-12',
  variant: 'secondary',
  animation: 'glow',
  circle: false,
});

const customStyle = computed(() => {
  const styles: Record<string, string> = {};
  if (props.height) {
    styles.height = props.height;
  }
  if (props.circle) {
    styles.borderRadius = '50%';
    if (props.height && !props.width) {
      styles.width = props.height;
    }
  }
  return styles;
});

function getLineWidth(index: number): string {
  if (props.lines === 1) return props.width;
  if (index === props.lines) return 'col-8';
  if (index % 2 === 0) return 'col-10';
  return 'col-12';
}
</script>
