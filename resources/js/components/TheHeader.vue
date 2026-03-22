<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';
import LoginForm from './LoginForm.vue';

const { locale, t } = useI18n();
const isLangVisible = ref(false);
const now = ref(new Date());
const auth = useAuthStore();
const LOCALE_STORAGE_KEY = 'preferred_locale';

const flagSk = new URL('../../assets/img/icons/sk.png', import.meta.url).href;
const flagEn = new URL('../../assets/img/icons/uk.png', import.meta.url).href;
const flagUa = new URL('../../assets/img/icons/ua.png', import.meta.url).href;
const flagRu = new URL('../../assets/img/icons/ru.png', import.meta.url).href;

const languages = [
  { code: 'sk', img: flagSk },
  { code: 'en', img: flagEn },
  { code: 'ua', img: flagUa },
  { code: 'ru', img: flagRu }
];

const intlLocaleMap: Record<string, string> = {
  sk: 'sk-SK',
  en: 'en-GB',
  ua: 'uk-UA',
  ru: 'ru-RU',
};

const toggleLang = () => { isLangVisible.value = !isLangVisible.value; };

const setLang = (code: string) => {
  locale.value = code;
  localStorage.setItem(LOCALE_STORAGE_KEY, code);
  isLangVisible.value = false;
};

const headerDate = (date: Date) => {
  return new Intl.DateTimeFormat(intlLocaleMap[locale.value] ?? 'sk-SK', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
  }).format(date);
};

const headerTime = (date: Date) => {
  return new Intl.DateTimeFormat(intlLocaleMap[locale.value] ?? 'sk-SK', {
    hour: '2-digit', minute: '2-digit', second: '2-digit',
  }).format(date);
};

const userName = computed(() => {
  if (!auth.user) return '';
  return auth.user.first_name && auth.user.last_name
    ? `${auth.user.first_name} ${auth.user.last_name}`
    : auth.user.name || '';
});

const userBalanceText = computed(() => {
  const numericBalance = Number(auth.user?.credit_balance ?? 0);
  return numericBalance.toFixed(2);
});

const isAdmin = () => {
  return auth.user && (auth.user.is_admin || auth.user.role_id === 4);
};

const handleLogout = async () => {
  await auth.logout();
};

let timer: ReturnType<typeof setInterval>;

const onLogin = (isAdmin: boolean) => {
  if (isAdmin) {
    window.location.href = '/admin';
    return;
  }

  window.location.href = '/';
};

onMounted(async () => {
  const savedLocale = localStorage.getItem(LOCALE_STORAGE_KEY);
  if (savedLocale && ['sk', 'en', 'ua', 'ru'].includes(savedLocale)) {
    locale.value = savedLocale;
  }

  timer = setInterval(() => { now.value = new Date(); }, 1000);
  await auth.initializeAuth();
});
onUnmounted(() => clearInterval(timer));
</script>

<template>
  <header class="header">
    <div class="container">
      <nav class="navbar">
        <RouterLink to="/" class="navbar__logo">
          <img src="@assets/img/logo.png" width="205" height="76" alt="UKF" />
        </RouterLink>

        <div class="navbar__time">
          <div class="navbar__time-date">{{ headerDate(now) }}</div>
          <div>{{ headerTime(now) }}</div>
        </div>

        <div class="navbar__right">
          <template v-if="!auth.isLoggedIn">
            <div class="navbar__lang-switch">
              <button class="navbar__lang-btn" @click="toggleLang">{{ t('header.languages') }}</button>
              <transition name="slide-fade">
                <div v-if="isLangVisible" class="lang-panel">
                  <div class="lang-panel__list">
                    <button v-for="lang in languages" :key="lang.code" @click="setLang(lang.code)"
                      class="lang-panel__item">
                      <img :src="lang.img" :alt="lang.code" />
                    </button>
                  </div>
                </div>
              </transition>
            </div>

            <LoginForm @logged-in="onLogin" />
          </template>

          <template v-else-if="auth.isLoggedIn && auth.user">
            <div class="user-panel__section user-panel__section--profile">
              <div class="user-panel__icon">
                <img src="@assets/img/icons/account.svg" width="24" height="24" alt="">
              </div>
              <div class="user-panel__content">
                <a href="/statistics" class="user-panel__item">{{ userName }}</a>
                <a href="/orders" class="user-panel__link">{{ t('header.orders') }}</a>
              </div>
            </div>

            <div class="user-panel__section user-panel__section--account">
              <div class="user-panel__icon">
                <img src="@assets/img/icons/billing.svg" width="24" height="24" alt="">
              </div>
              <div class="user-panel__content">
                <div class="user-panel__item">{{ t('header.accountBalance') }}: {{ userBalanceText }} €</div>
                <a href="/payment" class="user-panel__link user-panel__link--payment">{{ t('header.addMoney') }}</a>
              </div>
            </div>

            <div class="user-panel__section user-panel__section--settings">
              <div class="user-panel__icon">
                <img src="@assets/img/icons/options.svg" width="24" height="24" alt="">
              </div>
              <div class="user-panel__content">
                <a href="/settings" class="user-panel__link">{{ t('header.settings') }}</a>
              </div>
              <button @click="handleLogout" class="user-panel__logout-btn" :title="t('header.logout')">
                <img src="@assets/img/icons/logout.svg" alt="">
              </button>
            </div>
          </template>
        </div>
      </nav>
    </div>
  </header>
</template>

<style lang="scss">
.header {
  background-color: white;
  padding: 12px 0;
}

.lang-panel {
  background: white;
  padding: 10px;
  margin-top: 10px;
  display: inline-block;
  border-radius: 4px;
  box-shadow: 4px 4px 20px 0px rgba(0, 0, 0, .1);
  position: absolute;
  top: 30px;
  right: -30px;
  z-index: 100;

  &__list {
    display: flex;
    gap: 6px;
    align-items: center;
  }

  &__item {
    cursor: pointer;
    transition: .3s;
    background-color: transparent;
    border: 0;
    padding: 0;

    img {
      width: 30px;
      height: auto;
      display: block;
    }
  }
}

.user-panel {
  &__section {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  &__section--profile {
    flex: 0 1 auto;
  }

  &__section--account {
    flex: 0 1 auto;
    margin: 0 12px;
  }

  &__section--settings {
    flex: 0 1 auto;
    position: relative;
  }

  &__icon {
    font-size: 0;
    flex-shrink: 0;
  }

  &__content {
    display: flex;
    flex-direction: column;
    gap: 2px;
  }

  &__item {
    font-weight: 700;
    color: $grey1;
    font-size: 16px;
    text-decoration: none;
    transition: .3s;
  }

  &__link {
    color: $grey2;
    text-decoration: none;
    font-size: 14px;
    font-weight: 700;
    transition: .3s;

    &:hover {
      color: $blue1;
    }
  }

  &__logout-btn {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    padding: 4px 8px;
    transition: .3s;
    margin-left: 8px;

    &:hover {
      transform: scale(1.2);
      opacity: 0.8;
    }

    &:active {
      transform: scale(0.95);
    }
  }
}

a.user-panel__item {
  &:hover {
    color: $blue1;
  }
}

.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: .3s;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.navbar {
  display: flex;
  align-items: center;

  &__right {
    margin: 0 0 0 auto;
    display: flex;
    align-items: center;
    gap: 20px;
  }

  &__lang {
    &-switch {
      position: relative;
    }
    &-btn {
      background-color: transparent;
      background-image: url(../../assets/img/icons/lang.svg);
      background-size: contain;
      background-repeat: no-repeat;
      background-position: center;
      border: 0;
      font-size: 0;
      width: 30px;
      height: 30px;
      cursor: pointer;
      outline: none;
    }
  }

  &__logo {
    font-size: 0;
    outline: none;

    img {
      width: 205px;
    }
  }

  &__time {
    margin-left: 20px;

    &-date {
      color: $grey2;
      margin-bottom: 5px;
    }
  }
}
</style>
