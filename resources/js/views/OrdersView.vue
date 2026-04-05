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
  <div class="container">
    <section class="orders">
      <div class="title-head">
        <div class="title-wrap">
          <span class="title-icon">
            <img src="@assets/img/icons/account.svg" width="36" height="36" alt="" aria-hidden="true">
          </span>
          <h1 class="h1">{{ t('ordersPage.title') }}</h1>
        </div>

        <div class="orders__top-right" v-if="canLoadMore">
          <button class="btn btn--blue-fill" type="button" :disabled="isLoading" @click="loadOrders()">
            {{ isLoading ? t('ordersPage.loading') : t('ordersPage.loadMore') }}
          </button>
        </div>
      </div>

      <div class="orders__block">
        <h2 class="h2">{{ t('ordersPage.currentTitle') }}</h2>
        <div class="orders__table-wrap">
          <table class="orders__table">
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
                <td class="orders__amount">{{ formatPrice(item.price) }}</td>
              </tr>
              <tr v-if="!isLoadingCurrent && currentItems.length === 0">
                <td colspan="5" class="orders__empty">{{ t('ordersPage.emptyCurrent') }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="orders__block orders__block--history">
        <h2 class="h2">{{ t('ordersPage.historyTitle') }}</h2>
        <div class="orders__table-wrap">
          <table class="orders__table">
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
                <td class="orders__amount">{{ formatPrice(item.price) }}</td>
              </tr>
              <tr v-if="!isLoading && historyItems.length === 0">
                <td colspan="6" class="orders__empty">{{ t('ordersPage.emptyHistory') }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-if="canLoadMore" class="orders__load-more">
        <button class="btn btn--blue-fill" type="button" :disabled="isLoading" @click="loadOrders()">
          {{ isLoading ? t('ordersPage.loading') : t('ordersPage.loadMore') }}
        </button>
      </div>
    </section>
  </div>
</template>

<style scoped lang="scss">
.orders {
  background: white;
  border-radius: 8px;
  padding: 40px 50px 44px;

  @media (max-width: 992px) {
    padding: 20px;
  }
}

.orders__top-right {
  display: flex;
  align-items: center;
  gap: 18px;
}

.orders__block {
  margin-top: 30px;

  &--history {
    margin-top: 36px;
  }
}

.orders__table-wrap {
  overflow-x: auto;
  margin-top: 16px;
}

.orders__table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0 10px;
}

.orders__table thead th {
  text-align: left;
  font-size: 16px;
  color: $grey5;
  font-weight: 700;
  padding: 0 18px 6px 24px;
  white-space: nowrap;

  &:last-child {
    text-align: right;
    padding: 0 24px 6px 18px;
  }
}

.orders__table tbody tr {
  &:nth-child(odd) {
    background: $grey4;
  }
}

.orders__table tbody td {
  padding: 22px 24px;
  font-size: 18px;
  color: $grey1;
}

.orders__amount {
  text-align: right;
  font-weight: 700;
  white-space: nowrap;
}

.orders__empty {
  text-align: center;
  color: $grey2;
}

.orders__load-more {
  display: flex;
  justify-content: center;
  margin-top: 16px;
}

@media (max-width: 992px) {
  .orders__table thead th,
  .orders__table tbody td {
    font-size: 16px;
    min-width: 120px;
    padding-left: 6px;
    padding-right: 6px;
  }

  .orders__table thead th:last-child {
    padding: 0 6px 6px 6px;
  }

  .orders__table tbody td {
    padding: 16px 6px;
  }
}
</style>
