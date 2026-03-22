<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';

type OrderItem = {
  id: number;
  serve_date: string;
  created_at: string | null;
  status: string;
  price: number;
  canteen_name: string;
  name_sk: string;
  name_en: string;
  name_ua: string;
  name_ru: string;
};

const { t, locale } = useI18n();
const currentItems = ref<OrderItem[]>([]);
const historyItems = ref<OrderItem[]>([]);
const total = ref(0);
const isLoading = ref(false);
const isLoadingCurrent = ref(false);
const LIMIT = 10;

const canLoadMore = computed(() => historyItems.value.length < total.value);

const localeMap: Record<string, string> = {
  sk: 'sk-SK',
  en: 'en-GB',
  ua: 'uk-UA',
  ru: 'ru-RU',
};

const mealName = (item: OrderItem): string => {
  if (locale.value === 'en') return item.name_en || item.name_sk;
  if (locale.value === 'ua') return item.name_ua || item.name_sk;
  if (locale.value === 'ru') return item.name_ru || item.name_sk;
  return item.name_sk;
};

const formatDate = (value: string | null): string => {
  if (!value) return '-';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return new Intl.DateTimeFormat(localeMap[locale.value] ?? 'sk-SK', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  }).format(date);
};

const formatDateTime = (value: string | null): string => {
  if (!value) return '-';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return new Intl.DateTimeFormat(localeMap[locale.value] ?? 'sk-SK', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date);
};

const formatPrice = (value: number): string => `${Number(value).toFixed(2)} €`;

const statusLabel = (status: string): string => {
  const key = String(status || '').toLowerCase();
  const path = `ordersPage.statuses.${key}`;
  return t(path) === path ? status : t(path);
};

const loadOrders = async (reset = false) => {
  if (isLoading.value) {
    return;
  }

  if (reset) {
    isLoadingCurrent.value = true;
    historyItems.value = [];
  }

  isLoading.value = true;

  try {
    const response = await axios.get('/api/orders/history', {
      params: {
        offset: historyItems.value.length,
        limit: LIMIT,
      },
    });

    const payloadCurrent = Array.isArray(response.data?.current_items) ? response.data.current_items : [];
    const payloadHistory = Array.isArray(response.data?.history_items) ? response.data.history_items : [];

    currentItems.value = payloadCurrent;
    historyItems.value.push(...payloadHistory);
    total.value = Number(response.data?.total ?? historyItems.value.length);
  } finally {
    isLoading.value = false;
    isLoadingCurrent.value = false;
  }
};

onMounted(async () => {
  await loadOrders(true);
});
</script>

<template>
  <div class="container orders-page">
    <section class="orders-card">
      <div class="orders-head">
        <div class="orders-title-wrap">
          <span class="orders-icon">🧾</span>
          <h1 class="orders-title">{{ t('ordersPage.title') }}</h1>
        </div>
      </div>

      <h2 class="orders-section-title">{{ t('ordersPage.currentTitle') }}</h2>
      <div class="orders-table-wrap">
        <table class="orders-table">
          <thead>
            <tr>
              <th>{{ t('ordersPage.date') }}</th>
              <th>{{ t('ordersPage.meal') }}</th>
              <th>{{ t('ordersPage.canteen') }}</th>
              <th>{{ t('ordersPage.status') }}</th>
              <th>{{ t('ordersPage.price') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in currentItems" :key="`current-${item.id}`">
              <td>{{ formatDate(item.serve_date) }}</td>
              <td>{{ mealName(item) }}</td>
              <td>{{ item.canteen_name }}</td>
              <td>{{ statusLabel(item.status) }}</td>
              <td class="orders-table__price">{{ formatPrice(item.price) }}</td>
            </tr>
            <tr v-if="!isLoadingCurrent && currentItems.length === 0">
              <td colspan="5" class="orders-table__empty">{{ t('ordersPage.emptyCurrent') }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <h2 class="orders-section-title orders-section-title--history">{{ t('ordersPage.historyTitle') }}</h2>
      <div class="orders-table-wrap">
        <table class="orders-table">
          <thead>
            <tr>
              <th>{{ t('ordersPage.orderedAt') }}</th>
              <th>{{ t('ordersPage.date') }}</th>
              <th>{{ t('ordersPage.meal') }}</th>
              <th>{{ t('ordersPage.canteen') }}</th>
              <th>{{ t('ordersPage.status') }}</th>
              <th>{{ t('ordersPage.price') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in historyItems" :key="`history-${item.id}`">
              <td>{{ formatDateTime(item.created_at) }}</td>
              <td>{{ formatDate(item.serve_date) }}</td>
              <td>{{ mealName(item) }}</td>
              <td>{{ item.canteen_name }}</td>
              <td>{{ statusLabel(item.status) }}</td>
              <td class="orders-table__price">{{ formatPrice(item.price) }}</td>
            </tr>
            <tr v-if="!isLoading && historyItems.length === 0">
              <td colspan="6" class="orders-table__empty">{{ t('ordersPage.emptyHistory') }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="canLoadMore" class="orders-load-more">
        <button class="btn btn--blue-fill" type="button" :disabled="isLoading" @click="loadOrders()">
          {{ isLoading ? t('ordersPage.loading') : t('ordersPage.loadMore') }}
        </button>
      </div>
    </section>
  </div>
</template>

<style scoped lang="scss">
.orders-page {
  padding: 24px 0;
}

.orders-card {
  background: #f4f4f4;
  border-radius: 10px;
  padding: 24px;
}

.orders-head {
  margin-bottom: 18px;
}

.orders-title-wrap {
  display: flex;
  align-items: center;
  gap: 12px;
}

.orders-icon {
  font-size: 32px;
}

.orders-title {
  margin: 0;
  font-size: 42px;
  color: #333;
}

.orders-section-title {
  margin: 12px 0;
  font-size: 28px;
  color: #2d2d2d;
}

.orders-section-title--history {
  margin-top: 30px;
}

.orders-table-wrap {
  overflow-x: auto;
}

.orders-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0 8px;
}

.orders-table thead th {
  text-align: left;
  font-size: 18px;
  color: #7f7f7f;
  font-weight: 600;
  padding: 0 12px 4px;
}

.orders-table tbody tr {
  background: #ececec;
}

.orders-table tbody td {
  padding: 14px 12px;
  font-size: 20px;
  color: #1f1f1f;
}

.orders-table__price {
  text-align: right;
  font-weight: 700;
  white-space: nowrap;
}

.orders-table__empty {
  text-align: center;
  color: #8a8a8a;
}

.orders-load-more {
  display: flex;
  justify-content: center;
  margin-top: 20px;
}

@media (max-width: 1200px) {
  .orders-title {
    font-size: 30px;
  }

  .orders-section-title {
    font-size: 22px;
  }

  .orders-table thead th,
  .orders-table tbody td {
    font-size: 16px;
  }
}
</style>
