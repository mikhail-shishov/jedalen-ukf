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

const allergenVisualMap: Record<number, { icon: string; color: string }> = {
  0: { icon: 'M', color: '#FF6969' },
  1: { icon: 'G', color: '#F3CB96' },
  2: { icon: 'C', color: '#FD836D' },
  3: { icon: 'E', color: '#FFFACD' },
  4: { icon: 'F', color: '#78C0FC' },
  5: { icon: 'P', color: '#D69958' },
  6: { icon: 'S', color: '#ABDD45' },
  7: { icon: 'D', color: '#C8FFFF' },
  8: { icon: 'N', color: '#DCAB7B' },
  9: { icon: 'L', color: '#7ACD2A' },
  10: { icon: 'H', color: '#FFD700' },
  11: { icon: 'Z', color: '#EEE8AA' },
  12: { icon: 'O', color: '#DCDCDC' },
  13: { icon: 'B', color: '#B693FF' },
  14: { icon: 'M', color: '#FFD3D3' },
};

const displayedAllergens = computed(() => {
  return allergens.value.map((item) => {
    const visual = allergenVisualMap[item.number] ?? { icon: '#', color: '#d7d7d7' };
    const allergenKey = `settings.allergenNames.${item.number}`;
    return {
      id: item.id,
      number: item.number,
      text: te(allergenKey) ? t(allergenKey) : item.name,
      icon: visual.icon,
      color: visual.color,
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

      <div class="settings__head">
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
          :disabled="isSaving" :class="{ 'settings__allergen-card--blocked': blockedIds.includes(item.number) }"
          :aria-pressed="blockedIds.includes(item.number)"
          @click="toggleBlocked(item.number)">
          <div v-if="blockedIds.includes(item.number)" class="settings__allergen-badge">
            {{ t('settings.blockedHint') }}
          </div>
          <div class="settings__allergen-icon" :style="{ backgroundColor: item.color }">
            {{ item.icon }}
          </div>
          <p class="settings__allergen-text"><span v-if="item.number !== 0">{{ item.number }}. </span>{{ item.text }}</p>
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

  &__head {
    display: flex;
    justify-content: space-between;
    align-items: center;
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
    &-badge {
      position: absolute;
      top: 8px;
      left: 8px;
      right: 8px;
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
      width: 44px;
      height: 44px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
    }

    &-text {
      margin: 0;
      font-size: 14px;
      color: $grey1;
      font-weight: 600;
    }

    &-card {
      border: 1px solid #d3d3d3;
      border-radius: 4px;
      background: transparent;
      min-height: 210px;
      padding: 16px 14px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      gap: 10px;
      position: relative;
      cursor: pointer;

      &--blocked {
        border-color: $red1;
      }
    }
  }
}
</style>
