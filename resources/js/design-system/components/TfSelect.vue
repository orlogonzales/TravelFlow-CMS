<template>
  <div class="mb-3">
    <label v-if="label" :for="id" class="form-label fw-semibold">
      {{ label }}
      <span v-if="required" class="text-danger" aria-hidden="true">*</span>
    </label>

    <div :class="{ 'input-group': !!prefixIcon }">
      <span v-if="prefixIcon" class="input-group-text bg-body border-end-0 text-muted">
        <i :class="prefixIcon" aria-hidden="true"></i>
      </span>

      <select
        :id="id"
        :value="modelValue"
        class="form-select"
        :class="[
          { 'is-invalid': !!errorMessage },
          { 'border-start-0': !!prefixIcon }
        ]"
        :required="required"
        :disabled="disabled"
        :aria-invalid="!!errorMessage"
        :aria-describedby="errorMessage ? `${id}-error` : undefined"
        @change="onChange"
        @blur="$emit('blur')"
      >
        <option v-if="placeholder" value="" disabled :selected="modelValue === ''">
          {{ placeholder }}
        </option>
        <option
          v-for="opt in options"
          :key="String(opt.value)"
          :value="opt.value"
          :disabled="opt.disabled"
        >
          {{ opt.label }}
        </option>
      </select>
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
export interface SelectOption {
  value: string | number;
  label: string;
  disabled?: boolean;
}

interface Props {
  id: string;
  modelValue: string | number;
  label?: string;
  options: SelectOption[];
  placeholder?: string;
  errorMessage?: string;
  helpText?: string;
  required?: boolean;
  disabled?: boolean;
  prefixIcon?: string;
}

withDefaults(defineProps<Props>(), {
  placeholder: '',
  required: false,
  disabled: false,
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void;
  (e: 'change', value: string): void;
  (e: 'blur'): void;
}>();

function onChange(event: Event) {
  const target = event.target as HTMLSelectElement;
  emit('update:modelValue', target.value);
  emit('change', target.value);
}
</script>
