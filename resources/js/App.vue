<script lang="ts">
import { defineComponent } from 'vue';
import TheHeader from '@/components/TheHeader.vue';
import TheFooter from '@/components/TheFooter.vue';

export default defineComponent({
  name: 'App',
  components: {
    TheHeader,
    TheFooter
  }
});
</script>

<template>
  <div>
    <TheHeader />

    <RouterView />

    <TheFooter />
  </div>
</template>