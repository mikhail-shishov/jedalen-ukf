<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { useCanteenStore } from '@/stores/canteen';
import BasicDropdown from './BasicDropdown.vue';

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

const fetchMenu = async () => {
  try {
    isLoading.value = true;
    const response = await axios.get('/api/menu');

    menuData.value = Object.entries(response.data).map(([date, meals]) => ({
      date,
      meals: meals as Meal[]
    }));
  } catch (error) {
    console.error('Chyba pri načítaní menu:', error);
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  fetchMenu();
  window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown);
});

// const menuData: DayMenu[] = [
//   {
//     date: 'pondelok 14. októbra 2024',
//     meals: [
//       {
//         id: 1,
//         badge: 'Obed M1',
//         allergens: '1,3,9',
//         price: '4,30',
//         name_sk: 'Brav.rezeň na rošte, zemiaky, šalát 1,10 pol.hov.vývar s cestovinou 1,3,9',
//         name_en: 'Schnitzel on the grill, potatoes, salad 1,10 beef broth 1,3,9',
//         name_ua: 'Свинячий шніцель на грилі, картопля, салат 1,10...',
//         name_ru: 'Свиной шницель на гриле, картофель, салат 1,10...'
//       }
//     ]
//   }
// ];
</script>

<template>
  <div class="container">
    <div class="menu">
      <div class="menu__head">
        <span class="menu__title">Vyberte si jedaleň:</span>
        <BasicDropdown class="menu-header__dropdown">
          <template #trigger="{ isOpen }">
            <button class="dropdown-btn" :class="{ 'dropdown-btn--active': isOpen }">
              <span class="dropdown-btn__current">{{ canteenStore.currentCanteen }}</span>
              <i class="dropdown-btn__icon" :class="{ 'dropdown-btn__icon--rotated': isOpen }">▼</i>
            </button>
          </template>

          <template #content>
            <div class="dropdown-list">
              <button v-for="canteen in canteenStore.canteens" :key="canteen" class="dropdown-list__item"
                :class="{ 'dropdown-list__item--selected': canteen === canteenStore.currentCanteen }"
                @click="canteenStore.setCanteen(canteen)">
                {{ canteen }}
              </button>
            </div>
          </template>
        </BasicDropdown>
        <a href="" class="btn btn--white-fill">Burza jedal (0)</a>
        <span>Ak nevidíte niektoré položky, skontrolujte si voľbu alergenov v Nastaveniach.</span>
      </div>

      <div class="menu__body">
        <div v-for="day in menuData" :key="day.date" class="menu__row">
          <div class="menu__col">
            <h2 class="menu__date">{{ day.date }}</h2>

            <div v-for="meal in day.meals" :key="meal.id" class="menu-card">
              <span class="menu-card__badge">{{ meal.badge }}</span>

              <p class="menu-card__name">
                <span>{{ meal.id }}.</span>
                <span>{{ meal[`name_${locale}`] || meal.name_sk }}</span>
                <small v-if="meal.allergens">({{ meal.allergens }})</small>
              </p>

              <div class="menu-card__info">
                <a href="#" class="menu-card__link">{{ t('menu.more') }}</a>
                <span class="menu-card__price">{{ meal.price }} €</span>
              </div>
            </div>
          </div>
        </div>
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
      color: $grey1;
      text-decoration: none;
    }
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