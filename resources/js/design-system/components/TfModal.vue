<template>
  <Teleport to="body">
    <div v-if="modelValue">
      <!-- Backdrop translúcido de Bootstrap -->
      <div
        class="modal-backdrop fade show"
        aria-hidden="true"
        @click="handleBackdropClick"
      ></div>

      <!-- Contenedor del Modal -->
      <div
        class="modal fade show d-block"
        tabindex="-1"
        role="dialog"
        :aria-labelledby="title ? modalTitleId : undefined"
        aria-modal="true"
        @click.self="handleBackdropClick"
      >
        <div
          class="modal-dialog"
          :class="[
            size && size !== 'md' ? `modal-${size}` : '',
            { 'modal-dialog-centered': centered },
            { 'modal-dialog-scrollable': scrollable }
          ]"
        >
          <div class="modal-content shadow-lg border-0">
            <!-- Modal Header -->
            <div class="modal-header">
              <slot name="header">
                <h5 :id="modalTitleId" class="modal-title fw-bold">
                  {{ title }}
                </h5>
                <button
                  type="button"
                  class="btn-close"
                  aria-label="Cerrar"
                  :disabled="loading"
                  @click="close"
                ></button>
              </slot>
            </div>

            <!-- Modal Body -->
            <div class="modal-body">
              <slot />
            </div>

            <!-- Modal Footer (opcional) -->
            <div v-if="$slots.footer" class="modal-footer">
              <slot name="footer" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { watch, onBeforeUnmount, ref } from 'vue';

interface Props {
  modelValue: boolean;
  title?: string;
  size?: 'sm' | 'md' | 'lg' | 'xl';
  centered?: boolean;
  scrollable?: boolean;
  staticBackdrop?: boolean;
  loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  title: '',
  centered: true,
  scrollable: false,
  staticBackdrop: false,
  loading: false,
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'close'): void;
}>();

const modalTitleId = ref(`tf-modal-title-${Math.random().toString(36).substring(2, 9)}`);

function close() {
  if (props.loading) return;
  emit('update:modelValue', false);
  emit('close');
}

function handleBackdropClick() {
  if (!props.staticBackdrop) {
    close();
  }
}

function handleKeyDown(event: KeyboardEvent) {
  if (event.key === 'Escape' && props.modelValue) {
    close();
  }
}

watch(
  () => props.modelValue,
  (isOpen) => {
    if (typeof document === 'undefined') return;

    if (isOpen) {
      document.body.classList.add('modal-open');
      window.addEventListener('keydown', handleKeyDown);
    } else {
      document.body.classList.remove('modal-open');
      window.removeEventListener('keydown', handleKeyDown);
    }
  },
  { immediate: true }
);

onBeforeUnmount(() => {
  if (typeof document !== 'undefined') {
    document.body.classList.remove('modal-open');
    window.removeEventListener('keydown', handleKeyDown);
  }
});
</script>

<style scoped>
.modal-backdrop {
  z-index: 1050;
}
.modal {
  z-index: 1055;
}
</style>
