<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import BasicDropdown from '@/components/BasicDropdown.vue';
import {
  emptyUserPreferences,
  fetchAllergenOptions,
  fetchUserPreferences,
  saveUserPreferences,
  type AllergenOption,
  type UserPreferences,
} from '@/services/userPreferences';
import {
  initPushScheduler,
  requestPushPermission,
  setPushSchedulerEnabled,
} from '@/services/pushNotifications';
import { getAllergenIconUrl } from '@/constants/allergenIcons';

const { locale, t, te } = useI18n();
const selectedLanguage = ref(locale.value);
const blockedIds = ref<number[]>([]);
const allergens = ref<AllergenOption[]>([]);
const statusMessage = ref('');
const isSaving = ref(false);
const preferences = ref<UserPreferences>(emptyUserPreferences());

const localeOptions = [
  { value: 'sk', label: 'Slovenský' },
  { value: 'en', label: 'English' },
  { value: 'ua', label: 'Українська' },
  { value: 'ru', label: 'Русский' },
];

const selectedLanguageOption = computed(() => {
  return localeOptions.find((option) => option.value === selectedLanguage.value) ?? localeOptions[0];
});

const displayedAllergens = computed(() => {
  return allergens.value.map((item) => {
    const allergenKey = `settings.allergenNames.${item.number}`;
    return {
      id: item.id,
      number: item.number,
      text: te(allergenKey) ? t(allergenKey) : item.name,
      iconUrl: getAllergenIconUrl(item.number),
    };
  });
});

const persistPreferences = async () => {
  isSaving.value = true;
  try {
    preferences.value = await saveUserPreferences({
      blocked_allergens: blockedIds.value,
      push_enabled: preferences.value.push_enabled,
      push_locale: selectedLanguage.value as UserPreferences['push_locale'],
    });
  } finally {
    isSaving.value = false;
  }
};

const onLanguageChange = (nextLocale: UserPreferences['push_locale']) => {
  selectedLanguage.value = nextLocale;
  locale.value = nextLocale;
  localStorage.setItem('preferred_locale', nextLocale);
  preferences.value.push_locale = nextLocale;
  void persistPreferences();
};

watch(
  () => locale.value,
  (nextLocale) => {
    selectedLanguage.value = nextLocale as UserPreferences['push_locale'];
  }
);

const toggleBlocked = (id: number) => {
  if (blockedIds.value.includes(id)) {
    blockedIds.value = blockedIds.value.filter(item => item !== id);
    void persistPreferences();
    return;
  }
  blockedIds.value = [...blockedIds.value, id];
  void persistPreferences();
};

const enablePushNotifications = async () => {
  statusMessage.value = '';
  const permission = await requestPushPermission();

  if (permission === 'unsupported') {
    statusMessage.value = t('settings.pushUnsupported');
    return;
  }

  if (permission === 'granted') {
    preferences.value.push_enabled = true;
    setPushSchedulerEnabled(true);
    initPushScheduler();
    await persistPreferences();
    statusMessage.value = t('settings.pushEnabled');
    return;
  }

  preferences.value.push_enabled = false;
  setPushSchedulerEnabled(false);
  await persistPreferences();
  statusMessage.value = t('settings.pushDenied');
};

onMounted(async () => {
  try {
    const [allergenList, loadedPreferences] = await Promise.all([
      fetchAllergenOptions(),
      fetchUserPreferences(),
    ]);

    allergens.value = allergenList;
    preferences.value = loadedPreferences;
    blockedIds.value = [...loadedPreferences.blocked_allergens];
    selectedLanguage.value = locale.value as UserPreferences['push_locale'];
  } catch {
    allergens.value = [];
    blockedIds.value = [];
    preferences.value = emptyUserPreferences();
  }
});
</script>

<template>
  <div class="container">
    <section class="settings">

      <div class="title-head">
        <div class="title-wrap">
          <span class="title-icon">
            <img src="@assets/img/icons/options.svg" width="44" height="44" alt="" aria-hidden="true">
          </span>
          <h1 class="h1">{{ t('settings.title') }}</h1>
        </div>

        <button class="btn btn--blue-fill" type="button" :disabled="isSaving" @click="enablePushNotifications">
          {{ t('settings.push') }}
        </button>
      </div>

      <div class="settings__language-row">
        <label class="settings__language-label">{{ t('settings.language') }}</label>

        <BasicDropdown class="settings__language-dropdown">
          <template #trigger="{ isOpen, toggle, menuId, triggerId }">
            <button class="basic-dropdown__trigger" :class="{ 'basic-dropdown__trigger--open': isOpen }" type="button"
              :id="triggerId" :aria-expanded="isOpen" aria-haspopup="menu" :aria-controls="menuId" @click="toggle">
              {{ selectedLanguageOption.label }}
              <span class="basic-dropdown__arrow">▾</span>
            </button>
          </template>

          <template #content>
            <div class="basic-dropdown__menu">
              <button v-for="option in localeOptions" :key="option.value" class="basic-dropdown__item"
                :class="{ 'basic-dropdown__item--active': option.value === selectedLanguage }" type="button"
                role="menuitemradio" :aria-checked="option.value === selectedLanguage"
                @click="onLanguageChange(option.value as UserPreferences['push_locale'])">
                {{ option.label }}
              </button>
            </div>
          </template>
        </BasicDropdown>
      </div>

      <p v-if="statusMessage" class="settings__status-message" role="status" aria-live="polite">{{ statusMessage }}</p>

      <h2 class="h2">{{ t('settings.allergensTitle') }}</h2>
      <p>{{ t('settings.allergensSubtitle') }}</p>

      <div class="settings__allergens-grid">
        <button v-for="item in displayedAllergens" :key="item.id" class="settings__allergen-card" type="button"
          :class="{ 'settings__allergen-card--blocked': blockedIds.includes(item.number) }"
          :aria-pressed="blockedIds.includes(item.number)" @click="toggleBlocked(item.number)">
          <div class="settings__allergen-head">
            <div class="settings__allergen-icon">
              <img v-if="item.iconUrl" :src="item.iconUrl" :alt="''" aria-hidden="true">
              <span v-else>{{ item.number }}</span>
            </div>
            <div v-if="blockedIds.includes(item.number)" class="settings__allergen-badge">
              {{ t('settings.blockedHint') }}
            </div>
          </div>
          <p class="settings__allergen-text"><span v-if="item.number !== 0">{{ item.number }}. </span>{{ item.text }}
          </p>
        </button>
      </div>

    </section>
  </div>
</template>

<style scoped lang="scss">
.settings {
  padding: 40px 50px 44px;
  background-color: white;
  border-radius: 8px;

  @media (max-width: 992px) {
    padding: 20px;
  }

  &__language {
    &-row {
      margin: 24px 0;
      display: flex;
      align-items: center;
      gap: 24px;
    }

    &-label {
      color: $grey1;
    }
  }

  &__allergens {
    &-grid {
      display: grid;
      grid-template-columns: repeat(5, minmax(0, 1fr));
      gap: 16px;
      margin-top: 30px;

      @media (max-width: 992px) {
        grid-template-columns: repeat(3, minmax(0, 1fr));
      }

      @media (max-width: 576px) {
        grid-template-columns: repeat(1, minmax(0, 1fr));
      }
    }
  }

  &__status-message {
    margin: 16px 0 0;
    font-size: 18px;
    color: $grey2;
  }

  &__allergen {
    &-head {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    &-badge {
      font-size: 11px;
      color: $red1;
      text-align: left;
    }

    &-id {
      align-self: flex-start;
      font-size: 18px;
      color: $grey2;
    }

    &-icon {
      display: flex;
      align-items: center;
      justify-content: center;

      img {
        width: 36px;
        height: 36px;
        object-fit: contain;
        display: block;
      }
    }

    &-text {
      margin: 0;
      font-size: 14px;
      color: $grey1;
      font-weight: 600;
    }

    &-card {
      border: 1px solid $grey6;
      border-radius: 4px;
      background: transparent;
      min-height: 210px;
      padding: 26px 20px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      gap: 8px;
      position: relative;
      cursor: pointer;
      transition: .3s;

      * {
        transition: .3s;
      }

      &:hover {
        .settings__allergen-text {
          color: $grey2;
        }
      }

      &--blocked {
        border-color: $red1;

        .settings__allergen-icon,
        .settings__allergen-text {
          opacity: .5;
        }
      }
    }
  }
}
</style>
