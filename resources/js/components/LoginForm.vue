<script setup lang="ts">
import { ref, computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import TextInput from './TextInput.vue';

const emit = defineEmits<{ (e: 'logged-in', isAdmin: boolean): void }>();

const auth = useAuthStore();
const loginId = ref('');
const password = ref('');
const error = ref('');
const isLoading = ref(false);

const isFilled = computed(() => loginId.value.trim() !== '' && password.value !== '');

const submit = async () => {
  if (!isFilled.value) return;

  error.value = '';
  isLoading.value = true;

  const result = await auth.login(loginId.value, password.value);

  isLoading.value = false;

  if (result.ok && auth.user) {
    loginId.value = '';
    password.value = '';
    emit('logged-in', auth.user.is_admin);
  } else {
    error.value = result.message ?? 'Nesprávne prihlasovacie údaje.';
  }
};
</script>

<template>
  <form class="login-form" @submit.prevent="submit">
    <TextInput
      v-model="loginId"
      label="ID používateľa"
      autocomplete="username"
    />
    <TextInput
      v-model="password"
      type="password"
      label="Heslo"
      autocomplete="current-password"
    />
    <button
      class="btn"
      :class="isFilled ? 'btn--green-fill' : 'btn--blue-fill'"
      type="submit"
    >
      Prihlásiť
    </button>
    <transition name="fade">
      <span v-if="error" class="login-form__error">{{ error }}</span>
    </transition>
  </form>
</template>

<style lang="scss" scoped>
.login-form {
  display: flex;
  align-items: center;
  gap: 12px;
  position: relative;

  &__error {
    position: absolute;
    bottom: -20px;
    left: 0;
    font-size: 12px;
    color: $red1;
  }
}
</style>

