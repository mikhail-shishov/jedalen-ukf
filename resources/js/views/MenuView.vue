<script setup lang="ts">
import { computed, ref } from "vue";
import IntroOverlay from "@/components/IntroOverlay.vue";
import TheMenu from "@/components/TheMenu.vue";
import ArticlesList from "@/components/ArticlesList.vue";
import { useAuthStore } from "@/stores/auth";

const authStore = useAuthStore();
const isAuthenticated = computed(() => authStore.isLoggedIn && Boolean(authStore.user));

const isIntroActive = ref(false);

const handleToggle = (status: boolean) => {
  isIntroActive.value = status;
};
</script>

<template>
  <IntroOverlay @toggle-view="handleToggle"></IntroOverlay>
  <div v-if="!isAuthenticated" class="container">
    <ArticlesList></ArticlesList>
  </div>
  <TheMenu v-if="!isIntroActive"></TheMenu>
  <div v-if="isAuthenticated" class="container">
    <ArticlesList></ArticlesList>
  </div>
</template>
