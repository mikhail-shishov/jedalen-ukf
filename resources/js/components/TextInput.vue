<script setup lang="ts">
import { ref, computed } from 'vue';

type InputMode = 'none' | 'text' | 'tel' | 'url' | 'email' | 'numeric' | 'decimal' | 'search';
type InputType = 'text' | 'password' | 'email' | 'number' | 'search' | 'tel' | 'url';

const props = defineProps<{
  modelValue: string;
  label?: string;
  type?: InputType;
  inputmode?: InputMode;
  autocomplete?: string;
  disabled?: boolean;
}>();

const emit = defineEmits<{ (e: 'update:modelValue', value: string): void }>();

const isFocused = ref(false);
const isFloating = computed(() => isFocused.value || props.modelValue !== '');
</script>

<template>
  <div class="base-input-wrap">
    <input
      class="base-input"
      :type="type ?? 'text'"
      :inputmode="inputmode"
      :value="modelValue"
      :autocomplete="autocomplete"
      :disabled="disabled"
      @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
      @focus="isFocused = true"
      @blur="isFocused = false"
    />
    <label v-if="label" class="base-input__label" :class="{ 'base-input__label--floating': isFloating }">
      {{ label }}
    </label>
  </div>
</template>

<style lang="scss" scoped>
.base-input-wrap {
  position: relative;
}

.base-input {
  width: 100%;
  border: 1px solid $grey6;
  border-radius: 4px;
  padding: 12px 16px;
  font-size: 16px;
  color: $grey1;
  background: white;
  outline: none;
  transition: 0.3s;
  min-width: 200px;

  &:focus {
    border-color: $grey1;
  }

  &:disabled {
    opacity: 0.6;
  }
}

.base-input__label {
  position: absolute;
  left: 18px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 14px;
  color: $grey5;
  pointer-events: none;
  transition: 0.3s;

  &--floating {
    top: 3px;
    transform: translateY(0);
    font-size: 10px;
    font-weight: 700;
    color: $grey1;
  }
}
</style>
