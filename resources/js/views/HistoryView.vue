<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';

type PaymentHistoryItem = {
  id: number;
  created_at: string;
  status: string;
  method: string;
  amount: number;
  note: string | null;
};

const auth = useAuthStore();
const { t, locale } = useI18n();
const items = ref<PaymentHistoryItem[]>([]);
const total = ref(0);
const isLoading = ref(false);
const LIMIT = 10;

const balanceText = computed(() => `${Number(auth.user?.credit_balance ?? 0).toFixed(2)} €`);
const canLoadMore = computed(() => items.value.length < total.value);

const formatDateTime = (value: string): string => {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  const localeMap: Record<string, string> = {
    sk: 'sk-SK',
    en: 'en-GB',
    ua: 'uk-UA',
    ru: 'ru-RU',
  };
  const activeLocale = localeMap[locale.value] ?? 'sk-SK';
  return new Intl.DateTimeFormat(activeLocale, {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date);
};

const formatAmount = (value: number): string => `${Number(value).toFixed(2)} €`;

const statusLabel = (status: string): string => {
  const key = status.toLowerCase();
  if (key === 'completed') return t('history.statuses.completed');
  if (key === 'pending') return t('history.statuses.pending');
  if (key === 'failed') return t('history.statuses.failed');
  return status;
};

const methodLabel = (method: string): string => {
  const key = method.toLowerCase();
  if (key === 'credit card') return t('history.methods.creditCard');
  if (key === 'admin manual') return t('history.methods.adminManual');
  if (key === 'bank transfer') return t('history.methods.bankTransfer');
  if (!method) return t('history.methods.unknown');
  return method;
};

const loadHistory = async () => {
  if (isLoading.value) return;
  isLoading.value = true;
  try {
    const response = await axios.get('/api/payments/history', {
      params: {
        offset: items.value.length,
        limit: LIMIT,
      },
    });

    const payloadItems = Array.isArray(response.data?.items) ? response.data.items : [];
    items.value.push(...payloadItems);
    total.value = Number(response.data?.total ?? items.value.length);
  } finally {
    isLoading.value = false;
  }
};

onMounted(async () => {
  await auth.fetchUser();
  await loadHistory();
});
</script>

<template>
  <div class="container">
    <section class="history">
      <div class="title-head">
        <div class="title-wrap">
          <span class="title-icon">
            <img src="@assets/img/icons/billing.svg" width="36" height="36" alt="" aria-hidden="true">
          </span>
          <h1 class="h1">{{ t('history.title') }}</h1>
        </div>
        <div class="history__top-right">
          <a href="/payment" class="btn btn--green-fill">{{ t('history.addMoney') }}</a>
          <p class="history__balance">{{ t('history.balanceLabel') }} <span>{{ balanceText }}</span></p>
        </div>
      </div>

      <div class="history__table-wrap">
        <table class="history__table">
          <thead>
            <tr>
              <th>{{ t('history.dateTime') }}</th>
              <th>{{ t('history.status') }}</th>
              <th>{{ t('history.method') }}</th>
              <th>{{ t('history.amount') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in items" :key="item.id">
              <td>{{ formatDateTime(item.created_at) }}</td>
              <td>{{ statusLabel(item.status) }}</td>
              <td>{{ methodLabel(item.method) }}</td>
              <td class="history__amount">{{ formatAmount(item.amount) }}</td>
            </tr>
            <tr v-if="!isLoading && items.length === 0">
              <td colspan="4" class="history__empty">{{ t('history.empty') }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="canLoadMore" class="history__load-more">
        <button class="btn btn--blue-fill" type="button" :disabled="isLoading" @click="loadHistory">
          {{ isLoading ? t('history.loading') : t('history.loadMore') }}
        </button>
      </div>
    </section>
  </div>
</template>

<style scoped lang="scss">
.history {
  background: white;
  border-radius: 8px;
  padding: 40px 50px 44px;

  @media (max-width: 992px) {
    padding: 20px;
  }
}

.history__head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 18px;
  margin-bottom: 22px;

  @media (max-width: 1200px) {
    flex-direction: column;
    align-items: flex-start;
  }
}

.history__top-right {
  display: flex;
  align-items: center;
  gap: 18px;
}

.history__balance {
  margin: 0;
  font-size: 24px;
  font-weight: 700;
  color: $grey1;

  span {
    color: $lightblue1;
    text-wrap: nowrap;
  }

  @media (max-width: 992px) {
    font-size: 18px;
  }
}

.history__table-wrap {
  overflow-x: auto;
  margin-top: 30px;
}

.history__table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0 10px;
}

.history__table thead th {
  text-align: left;
  font-size: 16px;
  color: $grey5;
  font-weight: 700;
  padding: 0 18px 6px 36px;

  &:last-child {
    text-align: right;
    padding: 0 36px 6px 18px;

    @media (max-width: 992px) {
    padding: 0 6px 6px 6px
    }
  }
}

.history__table tbody tr {
  &:nth-child(odd) {
    background: $grey4;
  }
}

.history__table tbody td {
  padding: 22px 36px;
  font-size: 18px;
  color: $grey1;
}

.history__amount {
  text-align: right;
  font-weight: 700;
  white-space: nowrap;
}

.history__empty {
  text-align: center;
  color: $grey2;
}

.history__load-more {
  display: flex;
  justify-content: center;
  margin-top: 16px;
}

@media (max-width: 992px) {
  .history__table thead th,
  .history__table tbody td {
    font-size: 16px;
    min-width: 120px;
    padding-left: 6px;
    padding-right: 6px;
  }
  .history__table tbody td {
    padding: 16px 6px;
  }
}
</style>
