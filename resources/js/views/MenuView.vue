<script setup lang="ts">
import { ref } from "vue";
import IntroOverlay from "@/components/IntroOverlay.vue";
import TheMenu from "@/components/TheMenu.vue";
import ArticlesList from "@/components/ArticlesList.vue";

const isIntroActive = ref(false);

const handleToggle = (status: boolean) => {
  isIntroActive.value = status;
};
</script>

<template>
  <IntroOverlay @toggle-view="handleToggle"></IntroOverlay>
  <div class="container">
    <ArticlesList></ArticlesList>
  </div>
  <TheMenu v-if="!isIntroActive"></TheMenu>
</template>
