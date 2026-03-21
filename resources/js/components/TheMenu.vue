<script setup lang="ts">
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { useCanteenStore } from '@/stores/canteen';
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
  price: string;
  name_sk: string;
  name_en: string;
  name_ua: string;
  name_ru: string;
  image_url?: string;
  [key: string]: string | number | undefined;
}

interface DayMenu {
  date: string;
  meals: Meal[];
}

const { locale, t } = useI18n();
const canteenStore = useCanteenStore();

const formatDate = (dateStr: string) => {
  const [y, m, d] = dateStr.split('-').map(Number);
  const date = new Date(y, m - 1, d);
  const days = DAY_NAMES[locale.value] ?? DAY_NAMES.sk;
  return `${days[date.getDay()]} ${String(d).padStart(2, '0')}.${String(m).padStart(2, '0')}.${y}`;
};

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
  () => { if (initialized.value) fetchMenu(); }
);

onMounted(async () => {
  isLoading.value = true;
  try {
    await loadUserPreferences();
    await canteenStore.fetchCanteens();
    await fetchMenu();
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
        <BasicDropdown class="menu-header__dropdown">
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
        <a href="" class="btn btn--white-fill">Burza jedal (0)</a>
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
                  <span>{{ meal[`name_${locale}`] || meal.name_sk }}</span>
                  <small v-if="meal.allergens">({{ meal.allergens }})</small>
                </p>

                <div class="menu-card__info">
                  <button class="menu-card__link" @click="openMealDetails(meal)">{{ t('menu.more') }}</button>
                  <span class="menu-card__price">{{ meal.price }} €</span>
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
        <button class="modal-close" @click="closeModal">✕</button>

        <div class="modal-body">
          <div v-if="selectedMeal.image_url" class="modal-image">
            <img :src="String(selectedMeal.image_url)" :alt="String(selectedMeal[`name_${locale}`])" class="modal-image__img">
          </div>

          <div class="modal-info">
            <span class="modal-badge">{{ selectedMeal.badge }}</span>
            <h2 class="modal-title">{{ selectedMeal[`name_${locale}`] || selectedMeal.name_sk }}</h2>

            <div class="modal-details">
              <p><strong>{{ t('menu.allergens') }}:</strong> {{ selectedMeal.allergens }}</p>
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
  min-height: 200px;

  @media (max-width: 768px) {
    flex-direction: column;
  }
}

.modal-image {
  flex: 1;
  max-width: 50%;
  background-color: #f0f0f0;

  &__img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }

  @media (max-width: 768px) {
    max-width: 100%;
    height: 250px;
  }
}

.modal-info {
  flex: 1;
  padding: 40px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}
</style>
