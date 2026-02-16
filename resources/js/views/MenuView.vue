<script setup lang="ts">
import { ref } from "vue";
import IntroOverlay from "@/components/IntroOverlay.vue";
import TheMenu from "@/components/TheMenu.vue";

const isIntroActive = ref(false);

const handleToggle = (status: boolean) => {
  isIntroActive.value = status;
};
</script>

<template>
  <IntroOverlay @toggle-view="handleToggle"></IntroOverlay>
  <TheMenu v-if="!isIntroActive"></TheMenu>
</template>