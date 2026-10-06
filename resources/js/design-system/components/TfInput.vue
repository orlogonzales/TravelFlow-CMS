<template>
  <div class="mb-3">
    <label v-if="label" :for="id" class="form-label fw-semibold">
      {{ label }}
      <span v-if="required" class="text-danger" aria-hidden="true">*</span>
    </label>

    <div :class="{ 'input-group': hasGroup }">
      <span v-if="prefixIcon" class="input-group-text bg-white border-end-0 text-muted">
        <i :class="prefixIcon" aria-hidden="true"></i>
      </span>

      <input
        :id="id"
        :type="actualType"
        :value="modelValue"
        class="form-control"
        :class="[
          { 'is-invalid': !!errorMessage },
          { 'border-start-0': !!prefixIcon },
          { 'border-end-0': type === 'password' && allowTogglePassword }
        ]"
        :placeholder="placeholder"
        :autocomplete="autocomplete"
        :required="required"
        :disabled="disabled"
        :aria-invalid="!!errorMessage"
        :aria-describedby="errorMessage ? `${id}-error` : undefined"
        @input="onInput"
        @blur="$emit('blur')"
      />

      <button
        v-if="type === 'password' && allowTogglePassword"
        type="button"
        class="btn btn-outline-secondary border-start-0 bg-white"
        :class="{ 'is-invalid border-danger': !!errorMessage }"
        :aria-label="showPassword ? 'Ocultar contraseña' : 'Ver contraseña'"
        :title="showPassword ? 'Ocultar contraseña' : 'Ver contraseña'"
        tabindex="-1"
        @click="togglePasswordVisibility"
      >
        <i
          :class="showPassword ? 'fa-solid fa-eye-slash text-muted' : 'fa-solid fa-eye text-muted'"
          aria-hidden="true"
        ></i>
      </button>
    </div>

    <div v-if="errorMessage" :id="`${id}-error`" class="invalid-feedback d-block" role="alert">
      {{ errorMessage }}
    </div>
    <div v-else-if="helpText" class="form-text text-muted">
      {{ helpText }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';

interface Props {
  id: string;
  modelValue: string | number;
  label?: string;
  type?: string;
  placeholder?: string;
  errorMessage?: string;
  helpText?: string;
  required?: boolean;
  disabled?: boolean;
  autocomplete?: string;
  prefixIcon?: string;
  allowTogglePassword?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  type: 'text',
  placeholder: '',
  required: false,
  disabled: false,
  allowTogglePassword: true,
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void;
  (e: 'blur'): void;
}>();

const showPassword = ref(false);

const actualType = computed(() => {
  if (props.type === 'password') {
    return showPassword.value ? 'text' : 'password';
  }
  return props.type;
});

const hasGroup = computed(() => {
  return !!props.prefixIcon || (props.type === 'password' && props.allowTogglePassword);
});

function togglePasswordVisibility() {
  showPassword.value = !showPassword.value;
}

function onInput(event: Event) {
  const target = event.target as HTMLInputElement;
  emit('update:modelValue', target.value);
}
</script>
