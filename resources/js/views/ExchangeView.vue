<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import TheExchange from '@/components/TheExchange.vue';
import ArticlesList from "@/components/ArticlesList.vue";

const router = useRouter();
const { t } = useI18n();
</script>

<template>
  <TheExchange></TheExchange>
  <div class="container">
    <ArticlesList></ArticlesList>
  </div>
</template>