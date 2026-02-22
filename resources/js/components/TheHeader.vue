<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';

const { locale } = useI18n();
const isLangVisible = ref(false);
const now = ref(new Date());
const auth = useAuthStore();

const languages = [
  { code: 'sk', img: 'sk.svg' },
  { code: 'en', img: 'en.svg' },
  { code: 'ua', img: 'ua.svg' },
  { code: 'ru', img: 'ru.svg' }
];

const toggleLang = () => { isLangVisible.value = !isLangVisible.value; };
const setLang = (code: string) => {
  locale.value = code;
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

onMounted(() => {
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
          <button class="navbar__lang-switch" @click="toggleLang">Jazyky</button>
          <!-- <a href="" class="btn btn--blue-fill">Prihlasiť</a> -->

          <template v-if="auth.isLoggedIn && auth.user">
            <span class="navbar__user-name">{{ auth.user.name }}</span>
            <button @click="auth.logout" class="btn btn--blue-fill">Odhlásiť</button>
          </template>

          <RouterLink v-else to="/login" class="btn btn--blue-fill">
            Prihlásiť
          </RouterLink>
        </div>
        <transition name="slide-fade">
          <div v-if="isLangVisible" class="lang-panel">
            <div class="lang-panel__list">
              <button v-for="lang in languages" :key="lang.code" @click="setLang(lang.code)" class="lang-panel__item">
                <img :src="`/assets/img/icons/flags/${lang.img}`" :alt="lang.code" />
              </button>
            </div>
          </div>
        </transition>
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
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
  padding: 15px 30px;
  margin-top: 10px;
  display: inline-block;

  &__list {
    display: flex;
    gap: 20px;
    align-items: center;
  }

  &__item {
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
    transition: transform 0.2s;

    &:hover {
      transform: scale(1.1);
    }

    img {
      width: 60px;
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
      background-color: transparent;
      background-image: url(../../assets/img/icons/lang.svg);
      background-size: contain;
      background-repeat: no-repeat;
      background-position: center;
      border: 0;
      font-size: 0;
      width: 36px;
      height: 36px;
      cursor: pointer;
    }
  }

  &__logo {
    font-size: 0;

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