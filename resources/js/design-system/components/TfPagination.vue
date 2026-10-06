<template>
  <div
    v-if="total > 0"
    class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 pt-3"
  >
    <!-- Metadata de Rango -->
    <div class="text-muted small">
      Mostrando del <span class="fw-semibold text-body">{{ from }}</span> al
      <span class="fw-semibold text-body">{{ to }}</span> de
      <span class="fw-semibold text-body">{{ total }}</span> registros
    </div>

    <!-- Navegación por páginas -->
    <nav v-if="lastPage > 1" aria-label="Navegación de registros">
      <ul class="pagination pagination-sm mb-0">
        <!-- Botón Anterior -->
        <li class="page-item" :class="{ disabled: currentPage <= 1 || disabled }">
          <button
            type="button"
            class="page-link"
            aria-label="Página anterior"
            :disabled="currentPage <= 1 || disabled"
            @click="changePage(currentPage - 1)"
          >
            <i class="fa-solid fa-chevron-left small" aria-hidden="true"></i>
          </button>
        </li>

        <!-- Números de Página con Elipsis -->
        <li
          v-for="(page, idx) in visiblePages"
          :key="idx"
          class="page-item"
          :class="{
            active: page === currentPage,
            disabled: page === '...' || disabled
          }"
        >
          <span v-if="page === '...'" class="page-link">...</span>
          <button
            v-else
            type="button"
            class="page-link"
            :aria-current="page === currentPage ? 'page' : undefined"
            :disabled="disabled"
            @click="changePage(Number(page))"
          >
            {{ page }}
          </button>
        </li>

        <!-- Botón Siguiente -->
        <li class="page-item" :class="{ disabled: currentPage >= lastPage || disabled }">
          <button
            type="button"
            class="page-link"
            aria-label="Página siguiente"
            :disabled="currentPage >= lastPage || disabled"
            @click="changePage(currentPage + 1)"
          >
            <i class="fa-solid fa-chevron-right small" aria-hidden="true"></i>
          </button>
        </li>
      </ul>
    </nav>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  currentPage: number;
  lastPage: number;
  total: number;
  perPage: number;
  disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  disabled: false,
});

const emit = defineEmits<{
  (e: 'pageChange', page: number): void;
}>();

const from = computed(() => {
  if (props.total === 0) return 0;
  return (props.currentPage - 1) * props.perPage + 1;
});

const to = computed(() => {
  return Math.min(props.currentPage * props.perPage, props.total);
});

const visiblePages = computed<(number | string)[]>(() => {
  const current = props.currentPage;
  const last = props.lastPage;
  const delta = 1;

  if (last <= 7) {
    const pages: number[] = [];
    for (let i = 1; i <= last; i++) pages.push(i);
    return pages;
  }

  const range: number[] = [];
  for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
    range.push(i);
  }

  if (current - delta > 2) {
    range.unshift(-1); // Representa elipsis izquierda
  }
  if (current + delta < last - 1) {
    range.push(-2); // Representa elipsis derecha
  }

  range.unshift(1);
  if (last > 1) {
    range.push(last);
  }

  return range.map(p => (p < 0 ? '...' : p));
});

function changePage(page: number) {
  if (page >= 1 && page <= props.lastPage && page !== props.currentPage && !props.disabled) {
    emit('pageChange', page);
  }
}
</script>
