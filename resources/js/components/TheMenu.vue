<script setup lang="ts">
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import { useCanteenStore } from '@/stores/canteen';
import { useAuthStore } from '@/stores/auth';
import BasicDropdown from './BasicDropdown.vue';
import { fetchUserPreferences } from '@/services/userPreferences';

const DAY_NAMES: Record<string, string[]> = {
  sk: ['Nedeľa', 'Pondelok', 'Utorok', 'Streda', 'Štvrtok', 'Piatok', 'Sobota'],
  en: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
  ua: ['Неділя', 'Понеділок', 'Вівторок', 'Середа', 'Четвер', 'Пʼятниця', 'Субота'],
  ru: ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'],
};

interface Meal {
  id: number;
  badge: string;
  allergens: string;
  allergen_numbers?: number[];
  price: string;
  name_sk: string;
  name_en: string;
  name_ua: string;
  name_ru: string;
  image_url?: string;
}

interface DayMenu {
  date: string;
  meals: Meal[];
}

const { locale, t } = useI18n();
const router = useRouter();
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
  return `${days[date.getDay()]} ${String(d).padStart(2, '0')}.${String(m).padStart(2, '0')}.${y}`;
};

const localizedMealName = (meal: Meal): string => {
  const key = localeNameFieldMap[locale.value as keyof typeof localeNameFieldMap] ?? 'name_sk';
  return String(meal[key] ?? meal.name_sk ?? '');
};

const allergenLabel = (number: number): string => {
  const key = `settings.allergenNames.${number}`;
  return t(key);
};

const selectedMealAllergens = computed(() => {
  if (!selectedMeal.value) {
    return [] as number[];
  }

  const numbers = selectedMeal.value.allergen_numbers;
  if (Array.isArray(numbers) && numbers.length) {
    return numbers
      .map((value) => Number(value))
      .filter((value) => Number.isInteger(value) && value >= 0);
  }

  return parseMealAllergens(selectedMeal.value.allergens ?? '');
});

const isModalOpen = ref(false);
const selectedMeal = ref<Meal | null>(null);

const openMealDetails = (meal: Meal) => {
  selectedMeal.value = meal;
  isModalOpen.value = true;
  document.body.style.overflow = 'hidden';
};

const closeModal = () => {
  isModalOpen.value = false;
  selectedMeal.value = null;
  document.body.style.overflow = 'auto';
};

const handleKeyDown = (e: KeyboardEvent) => {
  if (e.key === 'Escape' && isModalOpen.value) {
    closeModal();
  }
};

const menuData = ref<DayMenu[]>([]);
const isLoading = ref(true);
const loadError = ref(false);
const initialized = ref(false);
const blockedAllergenNumbers = ref<number[]>([]);
const orderByMenuItemId = ref<Record<number, number>>({});
const orderLoadingByMenuItemId = ref<Record<number, boolean>>({});
const exchangeCountByCanteen = ref<Record<number, number>>({});

const parseMealAllergens = (allergens: string): number[] => {
  return allergens
    .split(',')
    .map((value) => Number(value.trim()))
    .filter((value) => Number.isInteger(value) && value >= 0);
};

const isMealAllowed = (meal: Meal): boolean => {
  if (!blockedAllergenNumbers.value.length) {
    return true;
  }

  const mealAllergens = parseMealAllergens(meal.allergens ?? '');
  return !mealAllergens.some((value) => blockedAllergenNumbers.value.includes(value));
};

const loadUserPreferences = async () => {
  if (!isAuthenticated.value) {
    blockedAllergenNumbers.value = [];
    return;
  }

  try {
    const preferences = await fetchUserPreferences();
    blockedAllergenNumbers.value = preferences.blocked_allergens;
  } catch {
    blockedAllergenNumbers.value = [];
  }
};

const groupedMenuData = computed<DayMenu[][]>(() => {
  const rows: DayMenu[][] = [];
  for (let i = 0; i < menuData.value.length; i += 2) {
    rows.push(menuData.value.slice(i, i + 2));
  }
  return rows;
});

const isMealOrdered = (meal: Meal): boolean => {
  return Boolean(orderByMenuItemId.value[meal.id]);
};

const isMealOrderLoading = (meal: Meal): boolean => {
  return Boolean(orderLoadingByMenuItemId.value[meal.id]);
};

const setMealOrderLoading = (mealId: number, isLoadingValue: boolean) => {
  orderLoadingByMenuItemId.value = {
    ...orderLoadingByMenuItemId.value,
    [mealId]: isLoadingValue,
  };
};

const isMealAffordable = (meal: Meal): boolean => {
  if (!authStore.user) {
    return false;
  }

  const mealPrice = parseFloat(String(meal.price ?? '0').replace(',', '.'));
  const userBalance = parseFloat(String(authStore.user.credit_balance ?? '0'));

  return !isNaN(mealPrice) && !isNaN(userBalance) && userBalance >= mealPrice;
};

const loadExchangeCount = async () => {
  if (!canteenStore.currentCanteenId) {
    return;
  }

  try {
    const response = await axios.get('/api/exchange', {
      params: { canteen_id: canteenStore.currentCanteenId },
    });

    const items = Array.isArray(response.data?.items) ? response.data.items : [];
    exchangeCountByCanteen.value = {
      ...exchangeCountByCanteen.value,
      [canteenStore.currentCanteenId]: items.length,
    };
  } catch (error) {
    console.error(error);
    exchangeCountByCanteen.value = {
      ...exchangeCountByCanteen.value,
      [canteenStore.currentCanteenId]: 0,
    };
  }
};

const goToExchange = () => {
  router.push('/exchange');
};

const getExchangeCount = (): number => {
  if (canteenStore.currentCanteenId === null) {
    return 0;
  }
  return exchangeCountByCanteen.value[canteenStore.currentCanteenId] ?? 0;
};

const loadActiveOrders = async () => {
  if (!isAuthenticated.value) {
    orderByMenuItemId.value = {};
    return;
  }

  if (!canteenStore.currentCanteenId) {
    orderByMenuItemId.value = {};
    return;
  }

  try {
    const response = await axios.get('/api/orders/active', {
      params: { canteen_id: canteenStore.currentCanteenId },
    });

    const items = Array.isArray(response.data?.items) ? response.data.items : [];
    const nextMap: Record<number, number> = {};

    items.forEach((item: { id?: number; menu_item_id?: number }) => {
      const orderId = Number(item.id);
      const menuItemId = Number(item.menu_item_id);
      if (Number.isInteger(orderId) && orderId > 0 && Number.isInteger(menuItemId) && menuItemId > 0) {
        nextMap[menuItemId] = orderId;
      }
    });

    orderByMenuItemId.value = nextMap;
  } catch (error) {
    console.error('Chyba pri načítaní objednávok:', error);
    orderByMenuItemId.value = {};
  }
};

const orderMeal = async (meal: Meal) => {
  if (isMealOrderLoading(meal)) {
    return;
  }

  setMealOrderLoading(meal.id, true);

  try {
    const response = await axios.post('/api/orders', {
      menu_item_id: meal.id,
    });

    const orderId = Number(response.data?.order?.id);
    if (Number.isInteger(orderId) && orderId > 0) {
      orderByMenuItemId.value = {
        ...orderByMenuItemId.value,
        [meal.id]: orderId,
      };
      await authStore.fetchUser();
    } else {
      await loadActiveOrders();
    }
  } catch (error) {
    console.error('Chyba pri vytvorení objednávky:', error);
  } finally {
    setMealOrderLoading(meal.id, false);
  }
};

const cancelMealOrder = async (meal: Meal) => {
  if (isMealOrderLoading(meal)) {
    return;
  }

  const orderId = orderByMenuItemId.value[meal.id];
  if (!orderId) {
    return;
  }

  setMealOrderLoading(meal.id, true);

  try {
    const response = await axios.delete(`/api/orders/${orderId}`);
    const nextMap = { ...orderByMenuItemId.value };
    delete nextMap[meal.id];
    orderByMenuItemId.value = nextMap;

    // Обновить счетчик биржи если заказ был отправлен туда
    if (response.data?.message === 'Objednávka bola umiestnená na burzu.') {
      await loadExchangeCount();
    }

    await authStore.fetchUser();
  } catch (error) {
    console.error('Chyba pri zrušení objednávky:', error);
  } finally {
    setMealOrderLoading(meal.id, false);
  }
};

const toggleMealOrder = async (meal: Meal) => {
  if (isMealOrdered(meal)) {
    await cancelMealOrder(meal);
    return;
  }

  await orderMeal(meal);
};

const fetchMenu = async () => {
  loadError.value = false;

  if (!canteenStore.currentCanteenId) {
    menuData.value = [];
    isLoading.value = false;
    return;
  }

  try {
    isLoading.value = true;
    const response = await axios.get('/api/menu', {
      params: { canteen_id: canteenStore.currentCanteenId },
    });

    const menuPayload = response.data && typeof response.data === 'object' ? response.data : {};

    menuData.value = Object.entries(menuPayload).map(([date, meals]) => ({
      date,
      meals: Array.isArray(meals) ? (meals as Meal[]).filter(isMealAllowed) : [],
    })).filter((day) => day.meals.length > 0);
  } catch (error) {
    console.error('Chyba pri načítaní menu:', error);
    loadError.value = true;
    menuData.value = [];
  } finally {
    isLoading.value = false;
  }
};

watch(
  () => canteenStore.currentCanteenId,
  async () => {
    if (!initialized.value) return;
    await fetchMenu();
    await loadActiveOrders();
    await loadExchangeCount();
  }
);

watch(
  () => authStore.isLoggedIn,
  async () => {
    if (!initialized.value) {
      return;
    }

    await loadUserPreferences();
    await fetchMenu();
    await loadActiveOrders();
  }
);

onMounted(async () => {
  isLoading.value = true;
  try {
    await loadUserPreferences();
    await canteenStore.fetchCanteens();
    await fetchMenu();
    await loadActiveOrders();
    await loadExchangeCount();
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
    <div class="menu">
      <div class="menu__head">
        <span class="menu__title">{{ t('menu.title') }}</span>
        <BasicDropdown class="menu-header__dropdown canteen-dropdown">
          <template #trigger="{ isOpen }">
            <button class="basic-dropdown-trigger" :class="{ 'basic-dropdown-trigger--open': isOpen }" type="button">
              {{ canteenStore.currentCanteen?.name || '-' }}
              <span class="basic-dropdown-arrow">▾</span>
            </button>
          </template>

          <template #content>
            <div class="basic-dropdown-menu">
              <button
                v-for="canteen in canteenStore.canteens"
                :key="canteen.id"
                class="basic-dropdown-item"
                :class="{ 'basic-dropdown-item--active': canteen.id === canteenStore.currentCanteenId }"
                type="button"
                @click="canteenStore.setCanteen(canteen.id)">
                {{ canteen.name }}
              </button>
            </div>
          </template>
        </BasicDropdown>
        <button @click="goToExchange" class="btn btn--white-fill">
          {{ t('menu.exchange') }} ({{ getExchangeCount() }})
        </button>
        <span class="menu__head-notice">{{ t('menu.notice') }}</span>
      </div>

      <div class="menu__body">
        <div v-if="isLoading" class="menu__loading">{{ t('menu.loading') }}</div>
        <div v-else-if="loadError" class="menu__empty">{{ t('menu.empty') }}</div>
        <div v-else-if="!menuData.length" class="menu__empty">{{ t('menu.empty') }}</div>
        <template v-else>
          <div v-for="(row, rowIndex) in groupedMenuData" :key="`row-${rowIndex}`" class="menu__row">
            <div v-for="day in row" :key="day.date" class="menu__col">
              <h2 class="menu__date">{{ formatDate(day.date) }}</h2>

              <div v-for="meal in day.meals" :key="meal.id" class="menu-card">
                <span class="menu-card__badge">{{ meal.badge }}</span>

                <p class="menu-card__name">
                  <span>{{ localizedMealName(meal) }}</span>
                  <small v-if="meal.allergens">({{ meal.allergens }})</small>
                </p>

                <div class="menu-card__info">
                  <button class="menu-card__link" @click="openMealDetails(meal)">{{ t('menu.more') }}</button>
                  <span class="menu-card__price">{{ meal.price }} €</span>
                  <template v-if="isAuthenticated && isMealOrdered(meal)">
                    <button
                      type="button"
                      class="menu-card__order-link menu-card__order-link--cancel"
                      :disabled="isMealOrderLoading(meal)"
                      @click="toggleMealOrder(meal)">
                      {{ t('menu.cancelOrder') }}
                    </button>
                  </template>
                  <template v-else-if="isAuthenticated && isMealAffordable(meal)">
                    <button
                      type="button"
                      class="menu-card__order-link"
                      :disabled="isMealOrderLoading(meal)"
                      @click="toggleMealOrder(meal)">
                      {{ t('menu.order') }}
                    </button>
                  </template>
                  <template v-else-if="isAuthenticated">
                    <span class="menu-card__order-link menu-card__order-link--disabled">
                      {{ t('menu.insufficientBalance') }}
                    </span>
                  </template>
                </div>
              </div>
            </div>
            <div v-if="row.length === 1" class="menu__col"></div>
          </div>
        </template>
      </div>
    </div>
  </div>

  <transition name="fade">
    <div v-if="isModalOpen && selectedMeal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content">
        <button class="modal-close" type="button" @click="closeModal">✕</button>

        <div class="modal-body">
          <div class="modal-image">
            <img
              v-if="selectedMeal.image_url"
              :src="String(selectedMeal.image_url)"
              :alt="localizedMealName(selectedMeal)"
              class="modal-image__img"
            >
            <div v-else class="modal-image__placeholder">{{ localizedMealName(selectedMeal) }}</div>
          </div>

          <div class="modal-info">
            <span class="modal-badge">{{ selectedMeal.badge }}</span>
            <h2 class="modal-title">{{ localizedMealName(selectedMeal) }}</h2>

            <div class="modal-details">
              <p class="modal-contains"><strong>{{ t('menu.contains') }}:</strong></p>
              <ul class="modal-allergens-list">
                <li v-for="allergenNumber in selectedMealAllergens" :key="allergenNumber">
                  {{ allergenNumber }}. {{ allergenLabel(allergenNumber) }}
                </li>
                <li v-if="!selectedMealAllergens.length">{{ t('menu.allergens') }}: -</li>
              </ul>
              <p class="modal-price">{{ selectedMeal.price }} €</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<style lang="scss">
.menu {
  &__head {
    background-color: $lightblue2;
    border-radius: 8px 8px 0 0;
    padding: 20px 35px;

    &-notice {
      margin-left: auto;
      color: white;
    }
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

    &__order-link {
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

      &--cancel {
        color: #d9480f;

        &:hover {
          color: #b93a09;
        }
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

.canteen-dropdown {
  --dropdown-width: 360px;
  --dropdown-font-size: 30px;
  --dropdown-item-font-size: 20px;
  --dropdown-radius: 5px;
  --dropdown-border: #cbcbcb;
  --dropdown-bg: #f7f7f7;
}

@media (max-width: 1200px) {
  .canteen-dropdown {
    --dropdown-width: 240px;
    --dropdown-font-size: 20px;
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

.modal-contains {
  margin: 0 0 8px;
}

.modal-allergens-list {
  margin: 0;
  padding-left: 18px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  color: #2f2f2f;
}

.modal-price {
  margin-top: 16px;
  color: $green1;
  font-size: 26px;
  font-weight: 700;
}

.fade-enter-active,
.fade-leave-active, .fade-enter-active .modal-content,
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
