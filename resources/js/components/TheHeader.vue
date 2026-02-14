<script lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

export default {
  name: "TheHeader",
  setup() {
    const now = ref(new Date());

    let timer: ReturnType<typeof setInterval>;

    onMounted(() => {
      timer = setInterval(() => {
        now.value = new Date();
      }, 1000);
    });

    onUnmounted(() => {
      clearInterval(timer);
    });

    const headerDate = (date: Date) => {
      return new Intl.DateTimeFormat('sk-SK', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
      }).format(date);
    };

    const headerTime = (date: Date) => {
      return new Intl.DateTimeFormat('sk-SK', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
      }).format(date);
    };

    return {
      now,
      headerDate,
      headerTime
    };
  }
}
</script>

<template>
  <header class="header">
    <div class="container">
      <nav class="navbar">
        <RouterLink to="/" class="navbar__logo">
          <img src="@assets/img/logo.png" width="205" height="76" alt="UKF" />
        </RouterLink>
        <div class="navbar__time">
          <div>{{ headerDate(now) }}</div>
          <div>{{ headerTime(now) }}</div>
        </div>
        <ul class="navbar__right">
          <button class="navbar__lang-switch">Jazyky</button>
          <a href="" class="btn btn--blue-fill">Prihlasiť</a>
        </ul>
      </nav>

    </div>
  </header>
</template>

<style lang="scss">
.header {
  background-color: white;
  padding: 12px 0;
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

    div:first-of-type {
      color: $grey2;
      margin-bottom: 5px;
    }
  }
}
</style>