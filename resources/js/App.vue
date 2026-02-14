<script lang="ts">
import { defineComponent } from 'vue';
import TheHeader from './components/TheHeader.vue';
import TheFooter from './components/TheFooter.vue';

export default defineComponent({
  name: 'App',
  components: {
    TheHeader,
    TheFooter
  }
});
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
}
</style>