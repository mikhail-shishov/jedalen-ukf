<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

const isOpen = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);

const toggle = () => (isOpen.value = !isOpen.value);
const close = () => (isOpen.value = false);

const handleClickOutside = (event: MouseEvent) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
    close();
  }
};

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));
</script>

<template>
  <div class="dropdown" ref="dropdownRef">
    <div class="dropdown__trigger" @click="toggle">
      <slot name="trigger" :isOpen="isOpen">
        <button class="btn">Open Dropdown</button>
      </slot>
    </div>

    <transition name="dropdown-fade">
      <div v-if="isOpen" class="dropdown__menu" @click="close">
        <slot name="content"></slot>
      </div>
    </transition>
  </div>
</template>

<style scoped lang="scss">
.dropdown {
  position: relative;
  display: inline-block;
  min-width: 200px;

  &__trigger {
    cursor: pointer;
  }

  &__menu {
    position: absolute;
    top: 100%;
    left: 0;
    background: #fff;
    border: 1px solid $grey6;
    border-radius: 0 0 4px 4px;
    z-index: 100;
    min-width: 200px;
    overflow: hidden;
  }
}

:slotted(.basic-dropdown__trigger) {
  min-width: var(--dropdown-width, 200px);
  border: 1px solid var(--dropdown-border, $grey6);
  border-radius: var(--dropdown-radius, 4px);
  background: var(--dropdown-bg, white);
  padding: var(--dropdown-padding, 12px 44px 12px 16px);
  font-size: var(--dropdown-font-size, 16px);
  color: var(--dropdown-text, $grey1);
  text-align: left;
  position: relative;
  line-height: 1.2;
  transition: .3s;
  outline: none;
  cursor: pointer;
}

:slotted(.basic-dropdown__arrow) {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--dropdown-arrow, $lightblue1);
  font-size: 18px;
  pointer-events: none;
  transition: .3s;
  height: 20px;
  width: 20px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transform-origin: center;
}

:slotted(.basic-dropdown__trigger--open) {
  .basic-dropdown-arrow {
    transform: translateY(-50%) rotate(180deg);
  }
}

:slotted(.basic-dropdown__menu) {
  min-width: var(--dropdown-width, 200px);
  display: flex;
  flex-direction: column;
  background: #fff;
  border-radius: var(--dropdown-radius, 0 0 4px 4px);
  overflow: hidden;
  padding: 6px 0;
}

:slotted(.basic-dropdown__item) {
  background: #fff;
  border: 0;
  padding: 6px 16px;
  text-align: left;
  font-size: var(--dropdown-item-font-size, 16px);
  color: $grey2;
  transition: .3s;
}

:slotted(.basic-dropdown__item:hover) {
  color: $blue1;
  cursor: pointer;
}

:slotted(.basic-dropdown__item--active) {
  color: $lightblue1;
}

.dropdown-fade-enter-active,
.dropdown-fade-leave-active {
  transition: .3s
}

.dropdown-fade-enter-from,
.dropdown-fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
