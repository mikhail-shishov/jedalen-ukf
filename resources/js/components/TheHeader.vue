<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch, nextTick } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';
import LoginForm from './LoginForm.vue';

const { locale, t } = useI18n();
const isLangVisible = ref(false);
const isLangModalOpen = ref(false);
const isLoginModalOpen = ref(false);
const isMobileViewport = ref(false);
const now = ref(new Date());
const auth = useAuthStore();
const langModalRef = ref<HTMLElement | null>(null);
const loginModalRef = ref<HTMLElement | null>(null);
const lastFocusedElement = ref<HTMLElement | null>(null);
const LOCALE_STORAGE_KEY = 'preferred_locale';
const MOBILE_BREAKPOINT = 992;

const flagSk = new URL('../../assets/img/icons/sk.png', import.meta.url).href;
const flagEn = new URL('../../assets/img/icons/uk.png', import.meta.url).href;
const flagUa = new URL('../../assets/img/icons/ua.png', import.meta.url).href;
const flagRu = new URL('../../assets/img/icons/ru.png', import.meta.url).href;

const localeLabels: Record<string, string> = {
  sk: 'Slovenský',
  en: 'English',
  ua: 'Українська',
  ru: 'Русский',
};

const languages = [
  { code: 'sk', img: flagSk, label: localeLabels.sk },
  { code: 'en', img: flagEn, label: localeLabels.en },
  { code: 'ua', img: flagUa, label: localeLabels.ua },
  { code: 'ru', img: flagRu, label: localeLabels.ru }
];

const intlLocaleMap: Record<string, string> = {
  sk: 'sk-SK',
  en: 'en-GB',
  ua: 'uk-UA',
  ru: 'ru-RU',
};

const closeAllPopups = () => {
  isLangVisible.value = false;
  isLangModalOpen.value = false;
  isLoginModalOpen.value = false;
  lastFocusedElement.value?.focus();
  lastFocusedElement.value = null;
};

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

const getActiveModal = () => {
  if (isLoginModalOpen.value) {
    return loginModalRef.value;
  }

  if (isLangModalOpen.value) {
    return langModalRef.value;
  }

  return null;
};

const focusFirstModalElement = async () => {
  await nextTick();
  const modal = getActiveModal();
  const focusable = getFocusableElements(modal);
  focusable[0]?.focus();
};

const closeMobileDialogs = () => {
  isLangModalOpen.value = false;
  isLoginModalOpen.value = false;
  lastFocusedElement.value?.focus();
  lastFocusedElement.value = null;
};

const handleKeyDown = (event: KeyboardEvent) => {
  if (!isMobileViewport.value || (!isLangModalOpen.value && !isLoginModalOpen.value)) {
    return;
  }

  if (event.key === 'Escape') {
    closeMobileDialogs();
    return;
  }

  if (event.key !== 'Tab') {
    return;
  }

  const focusable = getFocusableElements(getActiveModal());
  if (!focusable.length) {
    return;
  }

  const first = focusable[0];
  const last = focusable[focusable.length - 1];
  const active = document.activeElement as HTMLElement | null;

  if (event.shiftKey && active === first) {
    event.preventDefault();
    last.focus();
  } else if (!event.shiftKey && active === last) {
    event.preventDefault();
    first.focus();
  }
};

const toggleLang = () => {
  if (isMobileViewport.value) {
    if (!isLangModalOpen.value) {
      lastFocusedElement.value = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    }

    isLangModalOpen.value = !isLangModalOpen.value;
    if (isLangModalOpen.value) {
      isLoginModalOpen.value = false;
      void focusFirstModalElement();
    } else {
      lastFocusedElement.value?.focus();
      lastFocusedElement.value = null;
    }
    return;
  }

  isLangVisible.value = !isLangVisible.value;
};

const openLoginModal = () => {
  lastFocusedElement.value = document.activeElement instanceof HTMLElement ? document.activeElement : null;
  isLoginModalOpen.value = true;
  isLangModalOpen.value = false;
  isLangVisible.value = false;
  void focusFirstModalElement();
};

const closeLoginModal = () => {
  isLoginModalOpen.value = false;
  lastFocusedElement.value?.focus();
  lastFocusedElement.value = null;
};

const closeLangModal = () => {
  isLangModalOpen.value = false;
  lastFocusedElement.value?.focus();
  lastFocusedElement.value = null;
};

const updateViewportState = () => {
  const mobile = window.innerWidth < MOBILE_BREAKPOINT;
  isMobileViewport.value = mobile;

  if (mobile) {
    isLangVisible.value = false;
    return;
  }

  isLangModalOpen.value = false;
  isLoginModalOpen.value = false;
};

const setLang = (code: string) => {
  locale.value = code;
  localStorage.setItem(LOCALE_STORAGE_KEY, code);
  closeAllPopups();
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
  closeAllPopups();

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

  updateViewportState();
  window.addEventListener('resize', updateViewportState);
  window.addEventListener('keydown', handleKeyDown);
  timer = setInterval(() => { now.value = new Date(); }, 1000);
  await auth.initializeAuth();
});

onUnmounted(() => {
  clearInterval(timer);
  window.removeEventListener('resize', updateViewportState);
  window.removeEventListener('keydown', handleKeyDown);
});

watch([isLangModalOpen, isLoginModalOpen], ([langOpen, loginOpen]) => {
  if (langOpen || loginOpen) {
    void focusFirstModalElement();
  }
});
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
          <template v-if="auth.isLoading">
            <div class="navbar__auth-placeholder" aria-hidden="true"></div>
          </template>

          <template v-else-if="!auth.isLoggedIn">
            <div class="navbar__auth-controls">
              <div class="navbar__lang-switch">
                <button
                  class="navbar__lang-btn"
                  type="button"
                  aria-haspopup="menu"
                  :aria-expanded="!isMobileViewport && isLangVisible"
                  aria-controls="header-lang-panel"
                  @click="toggleLang"
                >
                  {{ t('header.languages') }}
                </button>
                <transition name="slide-fade">
                  <div v-if="!isMobileViewport && isLangVisible" id="header-lang-panel" class="lang-panel" role="menu">
                    <div class="lang-panel__list">
                      <button v-for="lang in languages" :key="lang.code" @click="setLang(lang.code)" type="button"
                        class="lang-panel__item" role="menuitemradio" :aria-checked="locale === lang.code"
                        :aria-label="lang.label">
                        <img :src="lang.img" :alt="''" aria-hidden="true" />
                      </button>
                    </div>
                  </div>
                </transition>
              </div>

              <button v-if="isMobileViewport" class="btn btn--blue-fill navbar__login-btn" type="button" @click="openLoginModal">
                {{ t('auth.login') }}
              </button>

              <LoginForm v-else @logged-in="onLogin" />
            </div>
          </template>

          <template v-else-if="auth.isLoggedIn && auth.user">
            <div class="user-panel__section user-panel__section--profile">
              <div class="user-panel__icon">
                <img src="@assets/img/icons/account.svg" width="24" height="24" alt="" aria-hidden="true">
              </div>
              <div class="user-panel__content">
                <a href="/statistics" class="user-panel__item">{{ userName }}</a>
                <a href="/orders" class="user-panel__link">{{ t('header.orders') }}</a>
              </div>
            </div>

            <div class="user-panel__section user-panel__section--account">
              <div class="user-panel__icon">
                <img src="@assets/img/icons/billing.svg" width="24" height="24" alt="" aria-hidden="true">
              </div>
              <div class="user-panel__content">
                <div class="user-panel__item">{{ t('header.accountBalance') }}: {{ userBalanceText }} €</div>
                <a href="/payment" class="user-panel__link user-panel__link--payment">{{ t('header.addMoney') }}</a>
              </div>
            </div>

            <div class="user-panel__section user-panel__section--settings">
              <div class="user-panel__icon">
                <img src="@assets/img/icons/options.svg" width="24" height="24" alt="" aria-hidden="true">
              </div>
              <div class="user-panel__content">
                <a href="/settings" class="user-panel__link">{{ t('header.settings') }}</a>
              </div>
              <button @click="handleLogout" class="user-panel__logout-btn" type="button" :title="t('header.logout')" :aria-label="t('header.logout')">
                <img src="@assets/img/icons/logout.svg" alt="" aria-hidden="true">
              </button>
            </div>
          </template>
        </div>
      </nav>
    </div>

    <transition name="fade">
      <div
        v-if="!auth.isLoading && !auth.isLoggedIn && isMobileViewport && isLangModalOpen"
        class="header-modal"
        @click.self="closeLangModal"
      >
        <div ref="langModalRef" class="header-modal__content" role="dialog" aria-modal="true" aria-labelledby="header-lang-dialog-title">
          <button class="close" @click="closeLangModal" type="button" aria-label="Close dialog">Close</button>
          <h2 id="header-lang-dialog-title" class="header-modal__title">Vybrať si jazyk</h2>

          <div class="header-modal__lang-list">
            <button
              v-for="lang in languages"
              :key="lang.code"
              type="button"
              class="header-modal__lang-item"
              role="radio"
              :aria-checked="locale === lang.code"
              @click="setLang(lang.code)"
            >
              <img :src="lang.img" :alt="''" aria-hidden="true" />
              <span>{{ lang.label }}</span>
              <span
                class="header-modal__lang-check"
                :class="{ 'header-modal__lang-check--active': locale === lang.code }"
                aria-hidden="true"
              ></span>
            </button>
          </div>
        </div>
      </div>
    </transition>

    <transition name="fade">
      <div
        v-if="!auth.isLoading && !auth.isLoggedIn && isMobileViewport && isLoginModalOpen"
        class="header-modal"
        @click.self="closeLoginModal"
      >
        <div ref="loginModalRef" class="header-modal__content header-modal__content--login" role="dialog" aria-modal="true" aria-labelledby="header-login-dialog-title">
          <button class="close" @click="closeLoginModal" type="button" aria-label="Close dialog">Close</button>
          <h2 id="header-login-dialog-title" class="header-modal__title">{{ t('auth.login') }}</h2>
          <LoginForm @logged-in="onLogin" />
        </div>
      </div>
    </transition>
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

.header-modal {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 12px;
  z-index: 1200;

  &__content {
    position: relative;
    width: min(540px, 100%);
    background-color: white;
    border-radius: 8px;
    padding: 42px 22px 22px;
    box-shadow: 4px 4px 20px 0 rgba(0, 0, 0, 0.1);
  }

  &__content--login {
    width: min(680px, 100%);
  }

  &__title {
    margin: 0 0 18px;
    font-size: 38px;
    font-weight: 700;
    line-height: 1.1;
    color: $grey1;
  }

  &__lang-list {
    display: flex;
    flex-direction: column;
  }

  &__lang-item {
    display: flex;
    align-items: center;
    gap: 14px;
    width: 100%;
    background: transparent;
    border: 0;
    border-radius: 4px;
    padding: 10px 0;
    font-size: 16px;
    color: $grey1;
    text-align: left;
    cursor: pointer;
  }

  &__lang-check {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: 2px solid $blue1;
    margin-left: auto;
    position: relative;

    &--active::after {
      content: '';
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background-color: $blue1;
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
    }
  }

  img {
    width: 38px;
    flex-shrink: 0;
  }
}

.header-modal__content--login :deep(.login-form) {
  display: flex;
  align-items: stretch;
  flex-direction: column;
  gap: 12px;
}

.header-modal__content--login :deep(.base-input) {
  min-width: 0;
}

.header-modal__content--login :deep(.btn) {
  align-self: flex-end;
}

.header-modal__content--login :deep(.login-form__error) {
  position: static;
}

.navbar {
  display: flex;
  align-items: center;

  &__right {
    margin: 0 0 0 auto;
    display: flex;
    align-items: center;
    gap: 36px;
  }

  &__auth-controls {
    display: flex;
    align-items: center;
    gap: 18px;
  }

  &__auth-placeholder {
    min-width: 320px;
    min-height: 46px;
  }

  &__login-btn {
    padding: 8px 20px;
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

@media (max-width: 992px) {
  .navbar {
    &__right {
      gap: 16px;
    }

    &__auth-controls {
      gap: 10px;
    }

    &__auth-placeholder {
      min-width: 120px;
      min-height: 38px;
    }

    &__time {
      display: none;
    }

    &__logo img {
      width: 146px;
      height: auto;
    }
  }

  .header-modal {
    &__title {
      font-size: 24px;
    }

    &__content {
      padding: 38px 16px 16px;
    }

    &__content--login :deep(.btn) {
      align-self: stretch;
    }
  }
}
</style>
