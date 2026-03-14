<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import TheHeader from './components/TheHeader.vue';
import TheFooter from './components/TheFooter.vue';

const route = useRoute();
</script>

<template>
    <TheHeader />

    <main class="main">
      <RouterView />
    </main>

    <TheFooter />
</template>

<style lang="scss">
.main {
  margin: 35px 0 85px;

  &--plain {
    margin: 0;
  }
}
</style>
