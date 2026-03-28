<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';

const auth = useAuthStore();
const { locale, t } = useI18n();

type StatisticsPayload = {
  most_ordered_meal: {
    name_sk: string;
    name_en: string;
    name_ua: string;
    name_ru: string;
    user_orders_count: number;
  } | null;
  peak_visit_day: {
    day_of_week: number;
    orders_count: number;
  } | null;
  total_visits: {
    count: number;
  } | null;
};

const stats = ref<StatisticsPayload | null>(null);
const isLoading = ref(true);

const weekdayByLocale: Record<string, string[]> = {
  sk: ['pondelok', 'utorok', 'streda', 'štvrtok', 'piatok', 'sobota', 'nedeľa'],
  en: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
  ua: ['понеділок', 'вівторок', 'середа', 'четвер', 'пʼятниця', 'субота', 'неділя'],
  ru: ['понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота', 'воскресенье'],
};

const userName = computed(() => {
  if (!auth.user) return '';
  return auth.user.first_name && auth.user.last_name
    ? `${auth.user.first_name} ${auth.user.last_name}`
    : auth.user.name || '';
});

const localizedMealName = computed(() => {
  if (!stats.value?.most_ordered_meal) {
    return '';
  }

  const meal = stats.value.most_ordered_meal;
  if (locale.value === 'en') return meal.name_en || meal.name_sk;
  if (locale.value === 'ua') return meal.name_ua || meal.name_sk;
  if (locale.value === 'ru') return meal.name_ru || meal.name_sk;
  return meal.name_sk;
});

const localizedPeakDay = computed(() => {
  const dayOfWeek = stats.value?.peak_visit_day?.day_of_week;
  if (!dayOfWeek || dayOfWeek < 1 || dayOfWeek > 7) {
    return '';
  }

  const days = weekdayByLocale[locale.value] ?? weekdayByLocale.sk;
  // MySQL DAYOFWEEK: 1 = Sunday ... 7 = Saturday.
  const mondayFirstIndex = (dayOfWeek + 5) % 7;
  return days[mondayFirstIndex] ?? '';
});

type PluralForms = {
  one: string;
  few?: string;
  many?: string;
  other: string;
};

const pluralLocaleMap: Record<string, string> = {
  sk: 'sk',
  en: 'en',
  ua: 'uk',
  ru: 'ru',
};

const pluralWords: Record<string, Record<string, PluralForms>> = {
  sk: {
    visits: { one: 'návšteva', few: 'návštevy', other: 'návštev' },
    orders: { one: 'objednávka', few: 'objednávky', other: 'objednávok' },
    times: { one: 'raz', few: 'razy', other: 'raz' },
  },
  en: {
    visits: { one: 'visit', other: 'visits' },
    orders: { one: 'order', other: 'orders' },
    times: { one: 'time', other: 'times' },
  },
  ua: {
    visits: { one: 'відвідування', few: 'відвідування', many: 'відвідувань', other: 'відвідування' },
    orders: { one: 'замовлення', few: 'замовлення', many: 'замовлень', other: 'замовлення' },
    times: { one: 'раз', few: 'рази', many: 'разів', other: 'разу' },
  },
  ru: {
    visits: { one: 'посещение', few: 'посещения', many: 'посещений', other: 'посещения' },
    orders: { one: 'заказ', few: 'заказа', many: 'заказов', other: 'заказа' },
    times: { one: 'раз', few: 'раза', many: 'раз', other: 'раза' },
  },
};

const pluralWord = (count: number, noun: 'visits' | 'orders' | 'times'): string => {
  const lang = pluralWords[locale.value] ? locale.value : 'en';
  const forms = pluralWords[lang][noun];
  const ruleLocale = pluralLocaleMap[lang] ?? 'en';
  const absoluteCount = Math.abs(Math.trunc(count));
  const category = new Intl.PluralRules(ruleLocale).select(absoluteCount) as keyof PluralForms;

  if (category === 'one') {
    return forms.one;
  }
  if (category === 'few' && forms.few) {
    return forms.few;
  }
  if (category === 'many' && forms.many) {
    return forms.many;
  }
  return forms.other;
};

const statCards = computed(() => {
  if (!stats.value) {
    return [];
  }

  const cards: Array<{ key: string; icon: string; label: string; value: string }> = [];

  if (stats.value.most_ordered_meal) {
    cards.push({
      key: 'most-ordered',
      icon: '🏆',
      label: t('statistics.mostOrderedLabel'),
      value: t('statistics.mostOrderedValue', {
        meal: localizedMealName.value,
        userOrders: stats.value.most_ordered_meal.user_orders_count,
        userOrdersWord: pluralWord(stats.value.most_ordered_meal.user_orders_count, 'times'),
      }),
    });
  }

  if (stats.value.peak_visit_day && localizedPeakDay.value) {
    cards.push({
      key: 'peak-day',
      icon: '🕒',
      label: t('statistics.peakDayLabel'),
      value: t('statistics.peakDayValue', {
        day: localizedPeakDay.value,
        orders: stats.value.peak_visit_day.orders_count,
        ordersWord: pluralWord(stats.value.peak_visit_day.orders_count, 'orders'),
      }),
    });
  }

  if (stats.value.total_visits) {
    cards.push({
      key: 'total-visits',
      icon: '🍽️',
      label: t('statistics.totalVisitsLabel'),
      value: t('statistics.totalVisitsValue', {
        count: stats.value.total_visits.count,
        visitsWord: pluralWord(stats.value.total_visits.count, 'visits'),
      }),
    });
  }

  return cards;
});

onMounted(async () => {
  isLoading.value = true;
  try {
    const { data } = await axios.get('/api/statistics');
    stats.value = data as StatisticsPayload;
  } catch {
    stats.value = {
      most_ordered_meal: null,
      peak_visit_day: null,
      total_visits: null,
    };
  } finally {
    isLoading.value = false;
  }
});
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
            {{ t('statistics.addMoney') }}
          </a>
        </div>

        <div v-if="isLoading" class="statistics-state">{{ t('statistics.loading') }}</div>
        <div v-else-if="!statCards.length" class="statistics-state">{{ t('statistics.empty') }}</div>

        <div v-else class="statistics-card" :class="{ 'statistics-card--compact': statCards.length === 1 }">
          <div v-for="card in statCards" :key="card.key" class="statistics-card__item">
            <div class="statistics-card__icon">{{ card.icon }}</div>
            <div class="statistics-card__content">
              <p class="statistics-card__label">{{ card.label }}</p>
              <p class="statistics-card__value">{{ card.value }}</p>
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

  &--compact {
    grid-template-columns: minmax(320px, 1fr);
  }
}

.statistics-state {
  padding: 28px;
  background: #f3f6f8;
  border-radius: 12px;
  color: #3f4b53;
  font-size: 17px;
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
