<script setup lang="ts">
import { computed, ref, watch, onMounted, onUnmounted, nextTick } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import { useCanteenStore } from '@/stores/canteen';
import { useAuthStore } from '@/stores/auth';
import BasicDropdown from './BasicDropdown.vue';
import { fetchUserPreferences } from '@/services/userPreferences';
import { getAllergenIconUrl } from '@/constants/allergenIcons';
import modalPlaceholderImageUrl from '@assets/img/placeholder.jpg';
import { formatMoney } from '@/utils/formatMoney';

const DAY_NAMES: Record<string, string[]> = {
  sk: ['pondelok', 'utorok', 'streda', 'štvrtok', 'piatok', 'sobota', 'nedeľa'],
  en: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
  ua: ['понеділок', 'вівторок', 'середа', 'четвер', 'пʼятниця', 'субота', 'неділя'],
  ru: ['понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота', 'воскресенье'],
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

type MenuApiPayload = Record<string, Meal[]>;

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

const DEFAULT_ORDER_TIME_ZONE = 'Europe/Bratislava';
const ORDER_DEADLINE_HOUR = 14;
const ORDER_DEADLINE_MINUTE = 0;
const MENU_CACHE_STORAGE_KEY = 'menu:cache:v1';
const MENU_CACHE_TTL_MS = 5 * 60 * 1000;

type MenuCacheEntry = {
  payload: MenuApiPayload;
  updatedAt: number;
};

let menuCacheHydrated = false;
let menuInMemoryCacheByCanteen: Record<number, MenuCacheEntry> = {};
const menuInFlightRequestsByCanteen = new Map<number, Promise<MenuApiPayload>>();

const formatDate = (dateStr: string) => {
  const [y, m, d] = dateStr.split('-').map(Number);
  const date = new Date(y, m - 1, d);
  const days = DAY_NAMES[locale.value] ?? DAY_NAMES.sk;
  const mondayFirstIndex = (date.getDay() + 6) % 7;
  return `${days[mondayFirstIndex]} ${String(d).padStart(2, '0')}.${String(m).padStart(2, '0')}.${y}`;
};

const parseCalendarDate = (dateStr: string): Date | null => {
  const [year, month, day] = dateStr.split('-').map(Number);
  if (!Number.isInteger(year) || !Number.isInteger(month) || !Number.isInteger(day)) {
    return null;
  }

  const parsed = new Date(Date.UTC(year, month - 1, day));
  return Number.isNaN(parsed.getTime()) ? null : parsed;
};

const getTimeZoneOffsetMinutes = (date: Date, timeZone: string): number => {
  const parts = new Intl.DateTimeFormat('en-US', {
    timeZone,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hourCycle: 'h23',
  }).formatToParts(date);

  const values: Record<string, string> = {};
  parts.forEach((part) => {
    if (part.type !== 'literal') {
      values[part.type] = part.value;
    }
  });

  const zonedTimestamp = Date.UTC(
    Number(values.year),
    Number(values.month) - 1,
    Number(values.day),
    Number(values.hour),
    Number(values.minute),
    Number(values.second),
  );

  return (zonedTimestamp - date.getTime()) / 60000;
};

const createZonedDateTime = (
  date: Date,
  hour: number,
  minute: number,
  second: number,
  timeZone: string,
): Date | null => {
  if (Number.isNaN(date.getTime())) {
    return null;
  }

  const utcGuess = Date.UTC(
    date.getUTCFullYear(),
    date.getUTCMonth(),
    date.getUTCDate(),
    hour,
    minute,
    second,
  );
  const guessedDate = new Date(utcGuess);
  const offsetMinutes = getTimeZoneOffsetMinutes(guessedDate, timeZone);

  return new Date(utcGuess - (offsetMinutes * 60 * 1000));
};

const getOrderDeadlineForDate = (dateStr: string): Date | null => {
  const serveDate = parseCalendarDate(dateStr);
  if (!serveDate) {
    return null;
  }

  const deadlineDate = new Date(serveDate);
  const isMonday = serveDate.getUTCDay() === 1;

  // Monday meals must be ordered by Friday 14:00.
  deadlineDate.setUTCDate(deadlineDate.getUTCDate() - (isMonday ? 3 : 1));

  return createZonedDateTime(
    deadlineDate,
    ORDER_DEADLINE_HOUR,
    ORDER_DEADLINE_MINUTE,
    0,
    canteenStore.currentCanteen?.timezone || DEFAULT_ORDER_TIME_ZONE,
  );
};

const currentTimestamp = ref(Date.now());
let currentTimestampTimer: any = null;

const canCreateOrderForDate = (dateStr: string): boolean => {
  const deadline = getOrderDeadlineForDate(dateStr);
  if (!deadline) {
    return false;
  }

  return currentTimestamp.value <= deadline.getTime();
};

const orderClosedText = computed(() => {
  return t('menu.orderClosed');
});

const menuBadgeLabel = (badge: string): string => {
  const normalizedBadge = Number(String(badge).trim());

  if (!Number.isInteger(normalizedBadge) || normalizedBadge <= 0) {
    return String(badge ?? '');
  }

  return t('menu.badgeTemplate', { number: normalizedBadge });
};

const localizedMealName = (meal: Meal): string => {
  const key = localeNameFieldMap[locale.value as keyof typeof localeNameFieldMap] ?? 'name_sk';
  return String(meal[key] ?? meal.name_sk ?? '');
};

const allergenLabel = (number: number): string => {
  const key = `settings.allergenNames.${number}`;
  return t(key);
};

const allergenIconUrl = (number: number): string | null => {
  return getAllergenIconUrl(number);
};

const modalImageUrl = computed(() => {
  const rawUrl = String(selectedMeal.value?.image_url ?? '').trim();
  const normalizedUrl = rawUrl.toLowerCase();

  if (!rawUrl || normalizedUrl === 'null' || normalizedUrl === 'undefined') {
    return modalPlaceholderImageUrl;
  }

  return rawUrl;
});

const handleModalImageError = (event: Event) => {
  const target = event.target as HTMLImageElement | null;
  if (!target || target.dataset.fallbackApplied === '1') {
    return;
  }

  target.dataset.fallbackApplied = '1';
  target.src = modalPlaceholderImageUrl;
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

const selectedMealServeDate = computed(() => {
  if (!selectedMeal.value) {
    return null;
  }

  return findMealServeDate(selectedMeal.value.id);
});

const selectedMealCanOrder = computed(() => {
  if (!selectedMeal.value || !selectedMealServeDate.value) {
    return false;
  }

  return canCreateOrderForDate(selectedMealServeDate.value) && isMealAffordable(selectedMeal.value);
});

const selectedMealIsOrdered = computed(() => {
  if (!selectedMeal.value) {
    return false;
  }

  return isMealOrdered(selectedMeal.value);
});

const isModalOpen = ref(false);
const selectedMeal = ref<Meal | null>(null);
const modalRef = ref<HTMLElement | null>(null);
const lastFocusedElement = ref<HTMLElement | null>(null);

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

const openMealDetails = (meal: Meal) => {
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

const orderSelectedMeal = () => {
  if (!selectedMeal.value) {
    return;
  }

  const meal = selectedMeal.value;
  closeModal();
  void orderMeal(meal);
};

const cancelSelectedMealOrder = () => {
  if (!selectedMeal.value) {
    return;
  }

  const meal = selectedMeal.value;
  closeModal();
  void cancelMealOrder(meal);
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

const menuData = ref<DayMenu[]>([]);
const isLoading = ref(true);
const loadError = ref(false);
const orderErrorMessage = ref('');
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

const isMenuCacheFresh = (updatedAt: number): boolean => {
  return updatedAt > 0 && (Date.now() - updatedAt) <= MENU_CACHE_TTL_MS;
};

const loadMenuCacheFromStorage = (): Record<number, MenuCacheEntry> => {
  try {
    const raw = sessionStorage.getItem(MENU_CACHE_STORAGE_KEY);
    if (!raw) {
      return {};
    }

    const parsed = JSON.parse(raw) as Record<string, MenuCacheEntry>;
    if (!parsed || typeof parsed !== 'object') {
      return {};
    }

    const entries = Object.entries(parsed)
      .map(([key, value]) => {
        const canteenId = Number(key);
        if (!Number.isInteger(canteenId) || canteenId <= 0 || !value || typeof value !== 'object') {
          return null;
        }

        const payload = value.payload && typeof value.payload === 'object'
          ? (value.payload as MenuApiPayload)
          : {};
        const updatedAt = Number(value.updatedAt ?? 0);

        if (!isMenuCacheFresh(updatedAt)) {
          return null;
        }

        return [canteenId, { payload, updatedAt }] as const;
      })
      .filter((item): item is readonly [number, MenuCacheEntry] => item !== null);

    return Object.fromEntries(entries);
  } catch {
    return {};
  }
};

const persistMenuCacheToStorage = () => {
  try {
    sessionStorage.setItem(MENU_CACHE_STORAGE_KEY, JSON.stringify(menuInMemoryCacheByCanteen));
  } catch {
    // Ignore session storage availability/quota errors.
  }
};

const getCachedMenuPayload = (canteenId: number): MenuApiPayload | null => {
  if (!menuCacheHydrated) {
    menuInMemoryCacheByCanteen = loadMenuCacheFromStorage();
    menuCacheHydrated = true;
  }

  const cacheEntry = menuInMemoryCacheByCanteen[canteenId];
  if (!cacheEntry || !isMenuCacheFresh(cacheEntry.updatedAt)) {
    return null;
  }

  return cacheEntry.payload;
};

const setCachedMenuPayload = (canteenId: number, payload: MenuApiPayload) => {
  menuInMemoryCacheByCanteen = {
    ...menuInMemoryCacheByCanteen,
    [canteenId]: {
      payload,
      updatedAt: Date.now(),
    },
  };

  persistMenuCacheToStorage();
};

const mapMenuPayloadToDays = (menuPayload: unknown): DayMenu[] => {
  const payload = menuPayload && typeof menuPayload === 'object'
    ? (menuPayload as Record<string, unknown>)
    : {};

  return Object.entries(payload).map(([date, meals]) => ({
    date,
    meals: Array.isArray(meals) ? (meals as Meal[]).filter(isMealAllowed) : [],
  })).filter((day) => day.meals.length > 0);
};

const fetchMenuPayload = async (canteenId: number): Promise<MenuApiPayload> => {
  const existingRequest = menuInFlightRequestsByCanteen.get(canteenId);
  if (existingRequest) {
    return existingRequest;
  }

  const request = axios.get('/api/menu', {
    params: { canteen_id: canteenId },
  })
    .then((response) => {
      const payload = response.data;
      return payload && typeof payload === 'object' ? (payload as MenuApiPayload) : {};
    })
    .finally(() => {
      menuInFlightRequestsByCanteen.delete(canteenId);
    });

  menuInFlightRequestsByCanteen.set(canteenId, request);
  return request;
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
  const visibleDays = menuData.value.filter((day) => {
    return day.meals.some((meal) => isMealOrdered(meal) || canCreateOrderForDate(day.date));
  });

  const rows: DayMenu[][] = [];
  for (let i = 0; i < visibleDays.length; i += 2) {
    rows.push(visibleDays.slice(i, i + 2));
  }
  return rows;
});

const hasVisibleMenu = computed(() => groupedMenuData.value.length > 0);

const findMealServeDate = (mealId: number): string | null => {
  for (const day of menuData.value) {
    if (day.meals.some((meal) => meal.id === mealId)) {
      return day.date;
    }
  }

  return null;
};

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

  const serveDate = findMealServeDate(meal.id);
  if (!serveDate || !canCreateOrderForDate(serveDate)) {
    orderErrorMessage.value = orderClosedText.value;
    return;
  }

  orderErrorMessage.value = '';

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
    if (axios.isAxiosError(error)) {
      orderErrorMessage.value = String(error.response?.data?.message || t('payments.serverError'));
    } else {
      orderErrorMessage.value = t('payments.serverError');
    }
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

  const canteenId = canteenStore.currentCanteenId;

  if (!canteenId) {
    menuData.value = [];
    isLoading.value = false;
    return;
  }

  const cachedPayload = getCachedMenuPayload(canteenId);
  if (cachedPayload) {
    menuData.value = mapMenuPayloadToDays(cachedPayload);
    isLoading.value = false;
    return;
  }

  try {
    isLoading.value = true;
    const menuPayload = await fetchMenuPayload(canteenId);
    setCachedMenuPayload(canteenId, menuPayload);
    menuData.value = mapMenuPayloadToDays(menuPayload);
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
  currentTimestamp.value = Date.now();
  currentTimestampTimer = window.setInterval(() => {
    currentTimestamp.value = Date.now();
  }, 60_000);

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

  if (currentTimestampTimer !== null) {
    window.clearInterval(currentTimestampTimer);
    currentTimestampTimer = null;
  }
});
</script>

<template>
  <div class="container">
    <div class="menu">
      <div class="menu__head">
        <span class="menu__title">{{ t('menu.title') }}</span>
        <BasicDropdown class="menu-header__dropdown canteen-dropdown">
          <template #trigger="{ isOpen, toggle, menuId, triggerId }">
            <button class="basic-dropdown__trigger" :class="{ 'basic-dropdown__trigger--open': isOpen }" type="button"
              :id="triggerId" :aria-expanded="isOpen" aria-haspopup="menu" :aria-controls="menuId" @click="toggle">
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
        <button @click="goToExchange" class="btn btn--white-fill" type="button">
          {{ t('menu.exchange') }} ({{ getExchangeCount() }})
        </button>
        <span class="menu__head-notice">{{ t('menu.notice') }}</span>
      </div>

      <div class="menu__body">
        <div v-if="orderErrorMessage" class="menu__error" role="alert">{{ orderErrorMessage }}</div>
        <div v-if="!isLoading && loadError" class="menu__empty" role="alert">{{ t('menu.empty') }}</div>
        <div v-else-if="!isLoading && !hasVisibleMenu" class="menu__empty" role="status" aria-live="polite">{{
          t('menu.empty')
          }}
        </div>
        <template v-else-if="!isLoading">
          <div v-for="(row, rowIndex) in groupedMenuData" :key="`row-${rowIndex}`" class="menu__row">
            <div v-for="day in row" :key="day.date" class="menu__col">
              <h2 class="menu__date">{{ formatDate(day.date) }}</h2>

              <div v-for="meal in day.meals" :key="meal.id" class="menu-card">
                <span class="menu-card__badge">{{ menuBadgeLabel(meal.badge) }}</span>

                <p class="menu-card__name">
                  <span>{{ localizedMealName(meal) }}</span>
                  <!-- <small v-if="meal.allergens">({{ meal.allergens }})</small> -->
                </p>

                <div class="menu-card__info">
                  <button class="menu-card__link" type="button" @click="openMealDetails(meal)">{{ t('menu.more')
                  }}</button>
                  <span class="menu-card__price">{{ formatMoney(meal.price) }}</span>
                  <template v-if="isAuthenticated && isMealOrdered(meal)">
                    <button type="button" class="menu-card__order-link menu-card__order-link--cancel"
                      :disabled="isMealOrderLoading(meal)" @click="toggleMealOrder(meal)">
                      {{ t('menu.cancelOrder') }}
                    </button>
                  </template>
                  <template v-else-if="isAuthenticated && canCreateOrderForDate(day.date) && isMealAffordable(meal)">
                    <button type="button" class="menu-card__order-link" :disabled="isMealOrderLoading(meal)"
                      @click="toggleMealOrder(meal)">
                      {{ t('menu.order') }}
                    </button>
                  </template>
                  <template v-else-if="isAuthenticated && !canCreateOrderForDate(day.date)">
                    <span class="menu-card__order-link menu-card__order-link--disabled">
                      {{ orderClosedText }}
                    </span>
                  </template>
                  <template v-else-if="isAuthenticated">
                    <span class="menu-card__order-link menu-card__order-link--disabled">
                      {{ t('menu.insufficientBalance') }}
                    </span>
                  </template>
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>

  <transition name="fade">
    <div v-if="isModalOpen && selectedMeal" class="modal__overlay" @click.self="closeModal">
      <div ref="modalRef" class="modal__content" role="dialog" aria-modal="true"
        aria-labelledby="menu-meal-modal-title">

        <div class="modal__head">
          <h2 id="menu-meal-modal-title" class="h2">{{ localizedMealName(selectedMeal) }}</h2>
          <button class="close" type="button" :aria-label="t('intro.close')" @click="closeModal">{{ t('intro.close')
            }}</button>
        </div>

        <div class="modal__body">
          <div class="modal__image-container">
            <img :src="modalImageUrl" :alt="localizedMealName(selectedMeal)" class="modal__img"
              @error="handleModalImageError">
          </div>

          <div class="modal__info">
            <div class="modal__details">
              <p class="h3">{{ t('menu.contains') }}:</p>
              <ul class="modal__allergens-list">
                <li v-for="allergenNumber in selectedMealAllergens" :key="allergenNumber" class="modal__allergen-item">
                  <img v-if="allergenIconUrl(allergenNumber)" :src="allergenIconUrl(allergenNumber) ?? ''" :alt="''"
                    aria-hidden="true" class="modal__allergen-icon">
                  <span>{{ allergenNumber }}. {{ allergenLabel(allergenNumber) }}</span>
                </li>
                <li v-if="!selectedMealAllergens.length">{{ t('menu.allergens') }}: -</li>
              </ul>
              <div class="modal__price-row">
                <p class="modal__price">{{ formatMoney(selectedMeal.price) }}</p>
                <button v-if="isAuthenticated && !selectedMealIsOrdered && selectedMealCanOrder" type="button"
                  class="btn btn--green-fill modal__order-btn" @click="orderSelectedMeal">
                  {{ t('menu.order') }}
                </button>
                <button v-else-if="isAuthenticated && selectedMealIsOrdered" type="button"
                  class="menu-card__order-link menu-card__order-link--cancel modal__order-link-button"
                  @click="cancelSelectedMealOrder">
                  {{ t('menu.cancelOrder') }}
                </button>
                <span v-else-if="isAuthenticated && !selectedMealCanOrder"
                  class="modal__order-state modal__order-state--disabled">
                  {{ orderClosedText }}
                </span>
              </div>
            </div>
            <div class="modal__ai-note">
              {{ t('menu.aiNote') }}
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
    padding: 20px 36px;
    display: flex;
    align-items: center;

    .h2 {
      margin-top: 0;
      padding-right: 10px;
    }

    @media (max-width: 992px) {
      flex-direction: column;
      align-items: flex-start;
      padding: 16px;
    }

    &-notice {
      margin-left: auto;
      color: white;

      @media (max-width: 1280px) {
        font-size: 12px;
      }

      @media (max-width: 992px) {
        margin-top: 8px;
        margin-left: 0;
      }
    }

    .menu-header__dropdown {
      margin: 0 16px;

      @media (max-width: 992px) {
        margin: 8px 0;
      }
    }
  }

  &__body {
    background-color: white;
    padding: 26px 0 30px;
  }

  &__date {
    margin: 0 0 24px 36px;
    color: $grey1;
    font-size: 24px;
    font-weight: 400;

    @media (max-width: 992px) {
      margin: 0 0 16px 16px;
    }

    b {
      font-weight: 600;
    }
  }

  &__title {
    color: white;
    font-size: 18px;
  }

  &-card {
    padding: 16px 16px 16px 36px;
    position: relative;

    @media (max-width: 992px) {
      padding: 16px;
    }

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
      position: absolute;
      color: $lightblue1;
      font-weight: 700;
      margin-left: auto;
      top: 16px;
      right: 16px;
    }

    &__info {
      display: flex;
      align-items: center;
    }

    &__order-link {
      margin-left: 16px;
      background: none;
      border: none;
      color: $green1;
      font-size: 18px;
      font-weight: 700;
      cursor: pointer;
      padding: 0;
      text-decoration: none;
      transition: .3s;
      display: inline-flex;
      align-items: center;
      gap: 6px;

      @media (max-width: 576px) {
        font-size: 16px;
        margin-left: auto;
      }

      &:hover {
        color: $green2;
      }

      &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
      }

      &:before {
        content: "";
        background-image: url(../../assets/img/icons/cart.svg);
        background-size: contain;
        background-repeat: no-repeat;
        background-position: center;
        width: 19px;
        height: 19px;
        display: inline-block;
      }

      &--cancel {
        color: $red1;

        &:before {
          display: none;
        }

        &:hover {
          color: $red2;
        }
      }

      &--disabled {
        color: $grey5;
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
      outline: none;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-size: 13px;
      font-weight: 600;

      &:before {
        content: "";
        background-image: url(../../assets/img/icons/info-333.svg);
        background-size: contain;
        background-repeat: no-repeat;
        background-position: center;
        width: 16px;
        height: 16px;
        display: inline-block;
      }
    }
  }

  &__loading,
  &__error,
  &__empty {
    padding: 40px 35px;
    color: $grey1;
    font-size: 16px;
    text-align: center;
  }

  &__error {
    color: $red1;
    padding-bottom: 0;
  }

  &__row {
    display: flex;

    +.menu__row {
      margin-top: 40px;
    }

    @media (max-width: 992px) {
      flex-direction: column;
    }
  }

  &__col {
    flex-basis: 50%;
    flex-grow: 1;
    max-width: 50%;

    @media (max-width: 992px) {
      max-width: 100%;
    }

    +.menu__col {
      @media (min-width: 992px) {
        .menu-card {
          padding: 16px 26px 16px 26px;
          border-left: 1px solid $grey6;
        }

        .menu__date {
          margin: 0 0 24px 26px;
        }
      }

      @media (max-width: 992px) {
        padding-top: 26px;
        margin-top: 16px;
        border-top: 1px solid $grey6;
      }
    }
  }
}

.modal {
  &__body {
    display: flex;
    min-height: 260px;

    @media (max-width: 992px) {
      flex-direction: column;
    }
  }

  &__head {
    .h2 {
      margin-top: 0;
    }
  }

  &__info {
    flex: 1;
    padding: 0 0 0 30px;
    display: flex;
    flex-direction: column;
    gap: 12px;

    @media (max-width: 992px) {
      padding: 0;
    }

    .h3 {
      margin-top: 0;
    }
  }

  &__img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  &__image {
    &-container {
      flex: 0 0 700px;
      width: 700px;
      height: 470px;
      background-color: white;

      @media (max-width: 1280px) {
        flex: 0 0 400px;
        width: 100%;
        height: 400px;
      }

      @media (max-width: 992px) {
        margin: 10px 0 20px;
      }

      @media (max-width: 768px) {
        flex: 0 0 300px;
        height: 300px;
      }

      @media (max-width: 480px) {
        flex: 0 0 200px;
        height: 200px;
        margin: 0 0 10px;
      }
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
    background: white;
    border-radius: 8px;
    padding: 30px 35px 40px;
    overflow-y: auto;

    @media (max-width: 768px) {
      padding: 20px 25px 30px;
    }
  }

  &__close {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 40px;
    height: 40px;
    border: none;
    background: transparent;
    color: $grey1;
    font-size: 24px;
    cursor: pointer;
    z-index: 2;

    @media (max-width: 768px) {
      top: 10px;
      right: 10px;
    }
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
    padding-left: 0;
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 10px;
    color: $grey1;
  }

  &__allergen-item {
    display: flex;
    align-items: center;
    gap: 14px;
  }

  &__allergen-icon {
    width: 36px;
    height: 36px;
    object-fit: contain;
    display: block;
    flex-shrink: 0;
  }

  &__price {
    margin: 0;
    color: $green1;
    font-size: 18px;
    font-weight: 700;

    &-row {
      margin-top: 16px;
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;

      .btn {
        margin-left: auto;
      }
    }
  }

  &__order-btn {
    white-space: nowrap;
  }

  &__order-link-button {
    background: none;
    border: none;
    padding: 0;
    margin-left: 16px;
    cursor: pointer;
    text-decoration: none;
    transition: .3s;

    &:hover {
      color: $red2;
    }
  }

  &__order-state {
    font-size: 16px;
    font-weight: 700;

    &--disabled {
      color: $grey5;
    }
  }

  &__ai-note {
    margin-top: auto;
    font-size: 12px;
    color: $grey2;
    display: inline-flex;
    gap: 4px;
    align-items: flex-start;

    &:before {
      content: "";
      background-image: url(../../assets/img/icons/info-666.svg);
      background-size: contain;
      background-repeat: no-repeat;
      background-position: center;
      min-width: 12px;
      width: 12px;
      height: 12px;
      margin-top: 2px;
      display: inline-block;
    }
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
