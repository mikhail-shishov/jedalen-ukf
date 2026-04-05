<script setup lang="ts">
import { computed, ref, watch, onMounted, onUnmounted, nextTick } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { useCanteenStore } from '@/stores/canteen';
import { useAuthStore } from '@/stores/auth';
import BasicDropdown from './BasicDropdown.vue';

const DAY_NAMES: Record<string, string[]> = {
  sk: ['pondelok', 'utorok', 'streda', 'štvrtok', 'piatok', 'sobota', 'nedeľa'],
  en: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
  ua: ['понеділок', 'вівторок', 'середа', 'четвер', 'пʼятниця', 'субота', 'неділя'],
  ru: ['понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота', 'воскресенье'],
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
const isAuthenticated = computed(() => authStore.isLoggedIn && Boolean(authStore.user));

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
  const mondayFirstIndex = (date.getDay() + 6) % 7;
  return `${days[mondayFirstIndex]} ${String(d).padStart(2, '0')}.${String(m).padStart(2, '0')}.${y}`;
};

const localizedMealName = (meal: ExchangeListing): string => {
  const key = localeNameFieldMap[locale.value as keyof typeof localeNameFieldMap] ?? 'name_sk';
  return String(meal[key] ?? meal.name_sk ?? '');
};

const menuBadgeLabel = (badge: string): string => {
  const normalizedBadge = Number(String(badge).trim());

  if (!Number.isInteger(normalizedBadge) || normalizedBadge <= 0) {
    return String(badge ?? '');
  }

  return t('menu.badgeTemplate', { number: normalizedBadge });
};

const selectedMeal = ref<ExchangeListing | null>(null);
const isModalOpen = ref(false);
const modalRef = ref<HTMLElement | null>(null);
const lastFocusedElement = ref<HTMLElement | null>(null);
const exchangeData = ref<DayExchange[]>([]);
const isLoading = ref(true);
const loadError = ref(false);
const initialized = ref(false);
const purchaseLoadingByExchangeId = ref<Record<number, boolean>>({});

const getFocusableElements = (container: HTMLElement | null): HTMLElement[] => {
  if (!container) {
    return [];
  }

  const selector = [
    'a[href]',
    'button:not([disabled])',
    'textarea:not([disabled])',
    'input:not([disabled])',
    'select:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
  ].join(',');

  return Array.from(container.querySelectorAll<HTMLElement>(selector))
    .filter((el) => !el.hasAttribute('disabled') && el.getAttribute('aria-hidden') !== 'true');
};

const focusFirstModalElement = async () => {
  await nextTick();
  const focusable = getFocusableElements(modalRef.value);
  focusable[0]?.focus();
};

const restoreFocus = () => {
  lastFocusedElement.value?.focus();
  lastFocusedElement.value = null;
};

const openMealDetails = (meal: ExchangeListing) => {
  lastFocusedElement.value = document.activeElement instanceof HTMLElement ? document.activeElement : null;
  selectedMeal.value = meal;
  isModalOpen.value = true;
  document.body.style.overflow = 'hidden';
  void focusFirstModalElement();
};

const closeModal = () => {
  isModalOpen.value = false;
  selectedMeal.value = null;
  document.body.style.overflow = 'auto';
  restoreFocus();
};

const handleKeyDown = (e: KeyboardEvent) => {
  if (!isModalOpen.value) {
    return;
  }

  if (e.key === 'Escape') {
    closeModal();
    return;
  }

  if (e.key === 'Tab') {
    const focusable = getFocusableElements(modalRef.value);
    if (!focusable.length) {
      return;
    }

    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    const active = document.activeElement as HTMLElement | null;

    if (e.shiftKey && active === first) {
      e.preventDefault();
      last.focus();
    } else if (!e.shiftKey && active === last) {
      e.preventDefault();
      first.focus();
    }
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
    console.error(error);
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
    console.error(error);
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
        <span class="menu__title">{{ t('menu.title') }}</span>
        <BasicDropdown class="menu-header__dropdown canteen-dropdown">
          <template #trigger="{ isOpen, toggle, menuId, triggerId }">
            <button
              class="basic-dropdown__trigger"
              :class="{ 'basic-dropdown__trigger--open': isOpen }"
              type="button"
              :id="triggerId"
              :aria-expanded="isOpen"
              aria-haspopup="menu"
              :aria-controls="menuId"
              @click="toggle"
            >
              {{ canteenStore.currentCanteen?.name || '-' }}
              <span class="basic-dropdown__arrow">▾</span>
            </button>
          </template>

          <template #content>
            <div class="basic-dropdown__menu">
              <button v-for="canteen in canteenStore.canteens" :key="canteen.id" class="basic-dropdown__item"
                :class="{ 'basic-dropdown__item--active': canteen.id === canteenStore.currentCanteenId }" type="button"
                role="menuitemradio" :aria-checked="canteen.id === canteenStore.currentCanteenId"
                @click="canteenStore.setCanteen(canteen.id)">
                {{ canteen.name }}
              </button>
            </div>
          </template>
        </BasicDropdown>
      </div>

      <div class="exchange__body">
        <div v-if="!isLoading && loadError" class="exchange__empty" role="alert">{{ t('menu.exchangeLoadError') }}</div>
        <div v-else-if="!isLoading && !exchangeData.length" class="exchange__empty" role="status" aria-live="polite">{{ t('menu.exchangeEmpty') }}</div>
        <template v-else-if="!isLoading">
          <div v-for="(row, rowIndex) in groupedExchangeData" :key="`row-${rowIndex}`" class="exchange__row">
            <div v-for="day in row" :key="day.date" class="exchange__col">
              <h2 class="exchange__date">{{ formatDate(day.date) }}</h2>

              <div v-for="listing in day.listings" :key="listing.id" class="exchange-card">
                <span class="exchange-card__badge">{{ menuBadgeLabel(listing.badge) }}</span>

                <p class="exchange-card__name">
                  <span>{{ localizedMealName(listing) }}</span>
                </p>

                <div class="exchange-card__info">
                  <button class="exchange-card__link" type="button" @click="openMealDetails(listing)">{{ t('menu.more') }}</button>
                  <span class="exchange-card__price">{{ listing.price }} €</span>
                  <template v-if="isAuthenticated && isListingAffordable(listing)">
                    <button type="button" class="exchange-card__purchase-link" :disabled="isPurchaseLoading(listing.id)"
                      @click="purchaseListing(listing)">
                      {{ t('menu.buy') }}
                    </button>
                  </template>
                  <template v-else-if="isAuthenticated">
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
    <div v-if="isModalOpen && selectedMeal" class="modal__overlay" @click.self="closeModal">
      <div ref="modalRef" class="modal__content" role="dialog" aria-modal="true" aria-labelledby="exchange-meal-modal-title">

        <div class="modal__head">
          <h2 id="exchange-meal-modal-title" class="h2">{{ localizedMealName(selectedMeal) }}</h2>
          <button class="modal__close" type="button" aria-label="Close dialog" @click="closeModal">✕</button>
        </div>

        <div class="modal__body">
          <div class="modal__image-container">
            <img v-if="selectedMeal.image_url" :src="String(selectedMeal.image_url)"
              :alt="localizedMealName(selectedMeal)" class="modal__img">
          </div>

          <div class="modal__info">
            <span class="modal__badge">{{ menuBadgeLabel(selectedMeal.badge) }}</span>

            <div class="modal__details">
              <p class="modal__price">{{ selectedMeal.price }} €</p>
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
      transition: .3s;

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

.modal {
  &__body {
    display: flex;
    min-height: 260px;

    @media (max-width: 768px) {
      flex-direction: column;
    }
  }

  &__info {
    flex: 1;
    padding: 28px 30px;
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  &__image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;

    &-container {
      flex: 0 0 68%;
      background-color: white;
    }

    &__placeholder {
      width: 100%;
      height: 100%;
      min-height: 300px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: $grey2;
      font-size: 18px;
      padding: 24px;
      text-align: center;
    }
  }

  &__overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 20px;
  }

  &__content {
    position: relative;
    width: min(1100px, 100%);
    max-height: calc(100vh - 40px);
    overflow: auto;
    background: white;
    border-radius: 8px;
  }

  &__close {
    position: absolute;
    top: 14px;
    right: 14px;
    width: 40px;
    height: 40px;
    border: none;
    background: transparent;
    color: $grey1;
    font-size: 24px;
    cursor: pointer;
    z-index: 2;
  }

  &__badge {
    font-size: 14px;
    color: $grey2;
  }

  &__details {
    margin-top: 8px;
  }

  &__contains {
    margin: 0 0 8px;
  }

  &__allergens-list {
    margin: 0;
    padding-left: 18px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    color: $grey1;
  }

  &__price {
    margin-top: 16px;
    color: $green1;
    font-size: 26px;
    font-weight: 700;
  }
}

.fade-enter-active,
.fade-leave-active,
.fade-enter-active .modal-content,
.fade-leave-active .modal-content {
  transition: .3s;
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
