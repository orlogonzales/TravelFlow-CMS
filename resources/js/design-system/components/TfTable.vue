<template>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" :class="{ 'card-table': inCard }">
      <!-- Encabezados -->
      <thead class="table-light">
        <tr>
          <th
            v-for="h in headers"
            :key="h.key"
            scope="col"
            :style="h.width ? { width: h.width } : undefined"
            :class="[
              h.align ? `text-${h.align}` : '',
              { 'cursor-pointer user-select-none': h.sortable }
            ]"
            @click="h.sortable ? handleSort(h.key) : undefined"
          >
            <div
              class="d-inline-flex align-items-center gap-1"
              :class="h.align === 'end' ? 'justify-content-end' : h.align === 'center' ? 'justify-content-center' : ''"
            >
              <span>{{ h.label }}</span>
              <span v-if="h.sortable" class="text-muted small">
                <i
                  v-if="currentSort === h.key"
                  :class="currentDirection === 'asc' ? 'fa-solid fa-sort-up text-primary' : 'fa-solid fa-sort-down text-primary'"
                  aria-hidden="true"
                ></i>
                <i v-else class="fa-solid fa-sort text-muted-subtle" aria-hidden="true"></i>
              </span>
            </div>
          </th>
          <th v-if="$slots.actions" scope="col" class="text-end" style="width: 150px;">
            Acciones
          </th>
        </tr>
      </thead>

      <!-- Cuerpo de la Tabla -->
      <tbody>
        <!-- Estado de Carga con TfSkeleton -->
        <tr v-if="loading" v-for="n in skeletonRows" :key="`skeleton-${n}`">
          <td v-for="h in headers" :key="`sk-col-${h.key}`">
            <TfSkeleton height="18px" :width="h.width || 'col-8'" />
          </td>
          <td v-if="$slots.actions" class="text-end">
            <TfSkeleton height="28px" width="70px" container-class="d-inline-block" />
          </td>
        </tr>

        <!-- Filas de Datos -->
        <tr v-else-if="items.length > 0" v-for="(item, index) in items" :key="item.id || index">
          <td
            v-for="h in headers"
            :key="`row-${h.key}`"
            :class="h.align ? `text-${h.align}` : ''"
          >
            <slot :name="`cell-${h.key}`" :item="item" :value="item[h.key]">
              {{ item[h.key] ?? '—' }}
            </slot>
          </td>
          <td v-if="$slots.actions" class="text-end text-nowrap">
            <slot name="actions" :item="item" />
          </td>
        </tr>

        <!-- Estado Vacío -->
        <tr v-else>
          <td :colspan="headers.length + ($slots.actions ? 1 : 0)" class="p-0 border-0">
            <slot name="empty">
              <TfEmptyState
                :title="emptyTitle"
                :description="emptyDescription"
              />
            </slot>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import TfSkeleton from './TfSkeleton.vue';
import TfEmptyState from './TfEmptyState.vue';

export interface TableHeader {
  key: string;
  label: string;
  width?: string;
  align?: 'start' | 'center' | 'end';
  sortable?: boolean;
}

interface Props {
  headers: TableHeader[];
  items: any[];
  loading?: boolean;
  skeletonRows?: number;
  emptyTitle?: string;
  emptyDescription?: string;
  currentSort?: string;
  currentDirection?: 'asc' | 'desc';
  inCard?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  skeletonRows: 5,
  emptyTitle: 'No hay registros disponibles',
  emptyDescription: 'No se encontraron elementos para mostrar.',
  currentDirection: 'desc',
  inCard: true,
});

const emit = defineEmits<{
  (e: 'sort', key: string): void;
}>();

function handleSort(key: string) {
  emit('sort', key);
}
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}
.text-muted-subtle {
  opacity: 0.4;
}
</style>
