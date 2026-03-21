<script setup lang="ts">
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { useCanteenStore } from '@/stores/canteen';
import { useAuthStore } from '@/stores/auth';
import BasicDropdown from './BasicDropdown.vue';

const DAY_NAMES: Record<string, string[]> = {
  sk: ['Nedeľa', 'Pondelok', 'Utorok', 'Streda', 'Štvrtok', 'Piatok', 'Sobota'],
  en: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
  ua: ['Неділя', 'Понеділок', 'Вівторок', 'Середа', 'Четвер', 'Пʼятниця', 'Субота'],
  ru: ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'],
};

interface ExchangeListing {
  id: number;
  order_id: number;
  seller_id: number;
  menu_item_id: number;
  date: string;
  name_sk: string;
  name_en: string;
  name_ua: string;
  name_ru: string;
  price: string;
  image_url?: string;
  badge: string;
}

interface DayExchange {
  date: string;
  listings: ExchangeListing[];
}

const { locale, t } = useI18n();
const canteenStore = useCanteenStore();
const authStore = useAuthStore();

const localeNameFieldMap = {
  sk: 'name_sk',
  en: 'name_en',
  ua: 'name_ua',
  ru: 'name_ru',
} as const;

const formatDate = (dateStr: string) => {
  const [y, m, d] = dateStr.split('-').map(Number);
  const date = new Date(y, m - 1, d);
  const days = DAY_NAMES[locale.value] ?? DAY_NAMES.sk;
  return `${days[date.getDay()]} ${String(d).padStart(2, '0')}.${String(m).padStart(2, '0')}.${y}`;
};

const localizedName = (listing: ExchangeListing): string => {
  const key = localeNameFieldMap[locale.value as keyof typeof localeNameFieldMap] ?? 'name_sk';
  return String(listing[key] ?? listing.name_sk ?? '');
};

const selectedListing = ref<ExchangeListing | null>(null);
const isModalOpen = ref(false);
const exchangeData = ref<DayExchange[]>([]);
const isLoading = ref(true);
const loadError = ref(false);
const initialized = ref(false);
const purchaseLoadingByExchangeId = ref<Record<number, boolean>>({});

const openListingDetails = (listing: ExchangeListing) => {
  selectedListing.value = listing;
  isModalOpen.value = true;
  document.body.style.overflow = 'hidden';
};

const closeModal = () => {
  isModalOpen.value = false;
  selectedListing.value = null;
  document.body.style.overflow = 'auto';
};

const handleKeyDown = (e: KeyboardEvent) => {
  if (e.key === 'Escape' && isModalOpen.value) {
    closeModal();
  }
};

const groupedExchangeData = computed<DayExchange[][]>(() => {
  const rows: DayExchange[][] = [];
  for (let i = 0; i < exchangeData.value.length; i += 2) {
    rows.push(exchangeData.value.slice(i, i + 2));
  }
  return rows;
});

const isPurchaseLoading = (exchangeId: number): boolean => {
  return Boolean(purchaseLoadingByExchangeId.value[exchangeId]);
};

const setPurchaseLoading = (exchangeId: number, isLoadingValue: boolean) => {
  purchaseLoadingByExchangeId.value = {
    ...purchaseLoadingByExchangeId.value,
    [exchangeId]: isLoadingValue,
  };
};

const isListingAffordable = (listing: ExchangeListing): boolean => {
  if (!authStore.user) {
    return false;
  }

  const listingPrice = parseFloat(String(listing.price ?? '0').replace(',', '.'));
  const userBalance = parseFloat(String(authStore.user.credit_balance ?? '0'));

  return !isNaN(listingPrice) && !isNaN(userBalance) && userBalance >= listingPrice;
};

const fetchExchange = async () => {
  loadError.value = false;

  if (!canteenStore.currentCanteenId) {
    exchangeData.value = [];
    isLoading.value = false;
    return;
  }

  try {
    isLoading.value = true;
    const response = await axios.get('/api/exchange', {
      params: { canteen_id: canteenStore.currentCanteenId },
    });

    const items = Array.isArray(response.data?.items) ? response.data.items : [];

    const grouped = new Map<string, ExchangeListing[]>();
    items.forEach((item: ExchangeListing) => {
      if (!grouped.has(item.date)) {
        grouped.set(item.date, []);
      }
      grouped.get(item.date)!.push(item);
    });

    exchangeData.value = Array.from(grouped.entries())
      .map(([date, listings]) => ({ date, listings }))
      .sort((a, b) => a.date.localeCompare(b.date));
  } catch (error) {
    console.error('Chyba pri načítaní biržu:', error);
    loadError.value = true;
    exchangeData.value = [];
  } finally {
    isLoading.value = false;
  }
};

const purchaseListing = async (listing: ExchangeListing) => {
  if (isPurchaseLoading(listing.id)) {
    return;
  }

  setPurchaseLoading(listing.id, true);

  try {
    const response = await axios.post(`/api/exchange/${listing.id}/purchase`);

    if (response.data?.ok) {
      exchangeData.value = exchangeData.value
        .map((day) => ({
          ...day,
          listings: day.listings.filter((l) => l.id !== listing.id),
        }))
        .filter((day) => day.listings.length > 0);

      await authStore.fetchUser();
    }
  } catch (error) {
    console.error('Chyba pri nákupe z biržy:', error);
  } finally {
    setPurchaseLoading(listing.id, false);
  }
};

watch(
  () => canteenStore.currentCanteenId,
  async () => {
    if (!initialized.value) return;
    await fetchExchange();
  }
);

onMounted(async () => {
  isLoading.value = true;
  try {
    await canteenStore.fetchCanteens();
    await fetchExchange();
  } finally {
    initialized.value = true;
    if (!canteenStore.currentCanteenId) {
      isLoading.value = false;
    }
  }

  window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown);
});
</script>

<template>
  <div class="container">
    <div class="exchange">
      <div class="exchange__head">
        <span class="exchange__title">{{ t('menu.exchange') }}</span>
        <BasicDropdown class="exchange-header__dropdown">
          <template #trigger="{ isOpen }">
            <button class="dropdown-btn" :class="{ 'dropdown-btn--active': isOpen }">
              <span class="dropdown-btn__current">{{ canteenStore.currentCanteen?.name || '-' }}</span>
              <i class="dropdown-btn__icon" :class="{ 'dropdown-btn__icon--rotated': isOpen }">▼</i>
            </button>
          </template>

          <template #content>
            <div class="dropdown-list">
              <button
                v-for="canteen in canteenStore.canteens"
                :key="canteen.id"
                class="dropdown-list__item"
                :class="{ 'dropdown-list__item--selected': canteen.id === canteenStore.currentCanteenId }"
                @click="canteenStore.setCanteen(canteen.id)">
                {{ canteen.name }}
              </button>
            </div>
          </template>
        </BasicDropdown>
      </div>

      <div class="exchange__body">
        <div v-if="isLoading" class="exchange__loading">{{ t('menu.loading') }}</div>
        <div v-else-if="loadError" class="exchange__empty">{{ t('menu.exchangeLoadError') }}</div>
        <div v-else-if="!exchangeData.length" class="exchange__empty">{{ t('menu.exchangeEmpty') }}</div>
        <template v-else>
          <div v-for="(row, rowIndex) in groupedExchangeData" :key="`row-${rowIndex}`" class="exchange__row">
            <div v-for="day in row" :key="day.date" class="exchange__col">
              <h2 class="exchange__date">{{ formatDate(day.date) }}</h2>

              <div v-for="listing in day.listings" :key="listing.id" class="exchange-card">
                <span class="exchange-card__badge">{{ listing.badge }}</span>

                <p class="exchange-card__name">
                  <span>{{ localizedName(listing) }}</span>
                </p>

                <div class="exchange-card__info">
                  <button class="exchange-card__link" @click="openListingDetails(listing)">{{ t('menu.more') }}</button>
                  <span class="exchange-card__price">{{ listing.price }} €</span>
                  <template v-if="isListingAffordable(listing)">
                    <button
                      type="button"
                      class="exchange-card__purchase-link"
                      :disabled="isPurchaseLoading(listing.id)"
                      @click="purchaseListing(listing)">
                      {{ t('menu.buy') }}
                    </button>
                  </template>
                  <template v-else>
                    <span class="exchange-card__purchase-link exchange-card__purchase-link--disabled">
                      {{ t('menu.insufficientBalance') }}
                    </span>
                  </template>
                </div>
              </div>
            </div>
            <div v-if="row.length === 1" class="exchange__col"></div>
          </div>
        </template>
      </div>
    </div>
  </div>

  <transition name="fade">
    <div v-if="isModalOpen && selectedListing" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content">
        <button class="modal-close" type="button" @click="closeModal">✕</button>

        <div class="modal-body">
          <div class="modal-image">
            <img
              v-if="selectedListing.image_url"
              :src="String(selectedListing.image_url)"
              :alt="localizedName(selectedListing)"
              class="modal-image__img"
            >
            <div v-else class="modal-image__placeholder">{{ localizedName(selectedListing) }}</div>
          </div>

          <div class="modal-info">
            <span class="modal-badge">{{ selectedListing.badge }}</span>
            <h2 class="modal-title">{{ localizedName(selectedListing) }}</h2>

            <div class="modal-details">
              <p class="modal-price">{{ selectedListing.price }} €</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<style lang="scss">
.exchange {
  &__head {
    background-color: $lightblue2;
    border-radius: 8px 8px 0 0;
    padding: 20px 35px;
  }

  &__body {
    background-color: white;
    padding: 26px 0 30px;
  }

  &__date {
    margin: 0 0 25px 35px;
    color: $grey1;
    font-size: 24px;
    font-weight: 400;

    b {
      font-weight: 600;
    }
  }

  &__title {
    color: white;
    font-size: 18px;
  }

  &-card {
    padding: 16px 16px 16px 35px;
    position: relative;

    &:nth-child(even) {
      background-color: $grey4;
    }

    &__badge {
      color: $grey5;
      font-weight: 700;
      font-size: 12px;
    }

    &__name {
      font-size: 18px;
      margin: 10px 0;
      display: flex;
      align-items: flex-start;
      gap: 6px;
    }

    &__price {
      display: block;
      color: $green1;
      font-weight: 700;
    }

    &__info {
      display: flex;
      align-items: center;
    }

    &__price {
      margin-left: auto;
    }

    &__purchase-link {
      margin-left: 16px;
      background: none;
      border: none;
      color: #22b573;
      font-size: 18px;
      font-weight: 700;
      cursor: pointer;
      padding: 0;
      text-decoration: none;
      transition: color 0.2s ease, opacity 0.2s ease;

      &:hover {
        color: #18905a;
      }

      &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
      }

      &--disabled {
        color: #999;
        font-weight: 700;
        font-size: 16px;
        cursor: default;
        margin-left: 16px;
      }
    }

    &__link {
      background: none;
      border: none;
      padding: 0;
      cursor: pointer;
      color: $grey1;
      text-decoration: none;
    }
  }

  &__loading,
  &__empty {
    padding: 40px 35px;
    color: $grey1;
    font-size: 16px;
    text-align: center;
  }

  &__row {
    display: flex;
  }

  &__col {
    flex-basis: 50%;
    flex-grow: 1;
  }
}

.modal-body {
  display: flex;
  min-height: 260px;

  @media (max-width: 768px) {
    flex-direction: column;
  }
}

.modal-image {
  flex: 0 0 68%;
  background-color: #f0f0f0;

  &__img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }

  &__placeholder {
    width: 100%;
    height: 100%;
    min-height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6f6f6f;
    font-size: 18px;
    padding: 24px;
    text-align: center;
  }

  @media (max-width: 768px) {
    flex-basis: auto;
    height: 250px;
  }
}

.modal-info {
  flex: 1;
  padding: 28px 30px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-content {
  position: relative;
  width: min(1280px, 100%);
  max-height: calc(100vh - 40px);
  overflow: auto;
  background: #f4f4f4;
  border-radius: 10px;
}

.modal-close {
  position: absolute;
  top: 14px;
  right: 14px;
  width: 40px;
  height: 40px;
  border: none;
  background: transparent;
  color: #333;
  font-size: 24px;
  cursor: pointer;
  z-index: 2;
}

.modal-badge {
  font-size: 15px;
  color: #8f8f8f;
}

.modal-title {
  margin: 0;
  font-size: 32px;
  line-height: 1.2;
}

.modal-details {
  margin-top: 8px;
}

.modal-price {
  margin-top: 16px;
  color: $green1;
  font-size: 26px;
  font-weight: 700;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.28s ease;
}

.fade-enter-active .modal-content,
.fade-leave-active .modal-content {
  transition: transform 0.34s cubic-bezier(0.2, 0.8, 0.2, 1), opacity 0.28s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.fade-enter-from .modal-content,
.fade-leave-to .modal-content {
  transform: translateY(18px) scale(0.98);
  opacity: 0;
}
</style>
