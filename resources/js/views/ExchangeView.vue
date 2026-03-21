<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import TheExchange from '@/components/TheExchange.vue';

const router = useRouter();
const { t } = useI18n();

const goBack = () => {
  // Обновить страницу при возвращении чтобы обновить счетчик
  router.push('/').then(() => {
    window.location.reload();
  });
};
</script>

<template>
  <div class="exchange-view">
    <button class="back-button" @click="goBack">
      {{ t('menu.backToCanteen') }}
    </button>
    <TheExchange></TheExchange>
  </div>
</template>

<style scoped lang="scss">
.exchange-view {
  padding: 20px;
}

.back-button {
  margin-bottom: 20px;
  padding: 10px 20px;
  background: #4a9fb5;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 16px;
  cursor: pointer;
  transition: background 0.2s ease;

  &:hover {
    background: #3a8fa5;
  }
}
</style>
