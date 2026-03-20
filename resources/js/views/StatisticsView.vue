<script setup lang="ts">
import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import TheHeader from '@/components/TheHeader.vue';
import TheFooter from '@/components/TheFooter.vue';

const auth = useAuthStore();

const userName = computed(() => {
  if (!auth.user) return '';
  return auth.user.first_name && auth.user.last_name
    ? `${auth.user.first_name} ${auth.user.last_name}`
    : auth.user.name || '';
});

const statistics = {
  mostFrequent: {
    title: 'Mám najčastejšie...',
    value: 'Palačinky Frutti poliate čokoládou, ovocie, 1,3,7'
  },
  hungriest: {
    title: 'Citím hlad najhorsie v...',
    value: 'pondelok'
  },
  visits: {
    title: 'Bol/a som v jedálni...',
    value: '123 krát'
  }
};
</script>

<template>
  <div class="statistics-page">

    <main class="statistics-main">
      <div class="container">
        <div class="statistics-header">
          <div class="statistics-user">
            <div class="statistics-user__icon">👤</div>
            <h1 class="statistics-user__name">{{ userName }}</h1>
          </div>
          <a href="/payment" class="btn btn--green-fill">
            Pridať peniazi
          </a>
        </div>
        <div class="statistics-card">
          <div class="statistics-card__item">
            <div class="statistics-card__icon">🏆</div>
            <div class="statistics-card__content">
              <p class="statistics-card__label">{{ statistics.mostFrequent.title }}</p>
              <p class="statistics-card__value">{{ statistics.mostFrequent.value }}</p>
            </div>
          </div>

          <div class="statistics-card__item">
            <div class="statistics-card__content">
              <p class="statistics-card__label">{{ statistics.hungriest.title }}</p>
              <p class="statistics-card__value">{{ statistics.hungriest.value }}</p>
            </div>
          </div>

          <div class="statistics-card__item">
            <div class="statistics-card__content">
              <p class="statistics-card__label">{{ statistics.visits.title }}</p>
              <p class="statistics-card__value">{{ statistics.visits.value }}</p>
            </div>
          </div>
        </div>
      </div>
    </main>

  </div>
</template>

<style lang="scss" scoped>
.statistics-page {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.statistics-main {
  flex: 1;
  padding: 40px 0;
}

.statistics-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
}

.statistics-user {
  display: flex;
  align-items: center;
  gap: 16px;

  &__icon {
    font-size: 48px;
    flex-shrink: 0;
  }

  &__name {
    font-size: 32px;
    font-weight: 600;
    color: #333;
    margin: 0;
  }
}

.statistics-card {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 32px;
  padding: 40px;
  background: linear-gradient(135deg, #1e5c96 0%, #2c5aa0 100%);
  border-radius: 12px;
  color: white;

  &__item {
    display: flex;
    flex-direction: column;
    gap: 12px;
    align-items: flex-start;
  }

  &__icon {
    font-size: 56px;
    margin-bottom: 8px;
  }

  &__content {
    width: 100%;
  }

  &__label {
    font-size: 14px;
    font-weight: 500;
    opacity: 0.9;
    margin: 0 0 8px 0;
    line-height: 1.4;
  }

  &__value {
    font-size: 20px;
    font-weight: 600;
    margin: 0;
    line-height: 1.4;
  }
}

@media (max-width: 768px) {
  .statistics-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 24px;
  }

  .statistics-card {
    grid-template-columns: 1fr;
    gap: 24px;
    padding: 24px;

    &__value {
      font-size: 18px;
    }
  }
}
</style>
