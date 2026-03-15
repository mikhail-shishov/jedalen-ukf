<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';
import LoginForm from './LoginForm.vue';

const { locale } = useI18n();
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

const toggleLang = () => { isLangVisible.value = !isLangVisible.value; };
const setLang = (code: string) => {
  locale.value = code;
  localStorage.setItem(LOCALE_STORAGE_KEY, code);
  isLangVisible.value = false;
};

const headerDate = (date: Date) => {
  return new Intl.DateTimeFormat('sk-SK', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
  }).format(date);
};

const headerTime = (date: Date) => {
  return new Intl.DateTimeFormat('sk-SK', {
    hour: '2-digit', minute: '2-digit', second: '2-digit',
  }).format(date);
};

let timer: ReturnType<typeof setInterval>;

const onLogin = (isAdmin: boolean) => {
  if (isAdmin) {
    window.location.href = '/admin';
  }
};

onMounted(() => {
  const savedLocale = localStorage.getItem(LOCALE_STORAGE_KEY);
  if (savedLocale && ['sk', 'en', 'ua', 'ru'].includes(savedLocale)) {
    locale.value = savedLocale;
  }

  timer = setInterval(() => { now.value = new Date(); }, 1000);
  auth.fetchUser();
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
          <div class="navbar__lang-switch">
            <button class="navbar__lang-btn" @click="toggleLang">Jazyky</button>
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

          <template v-if="auth.isLoggedIn && auth.user">
            <span class="navbar__user-name">{{ auth.user.name }}</span>
            <button @click="auth.logout" class="btn btn--blue-fill">Odhlásiť</button>
          </template>

          <LoginForm v-else @logged-in="onLogin" />
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

  &__list {
    display: flex;
    gap: 20px;
    align-items: center;
  }

  &__item {
    cursor: pointer;
    transition: 0.2s;
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

.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: all 0.3s ease;
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
    gap: 30px;
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
