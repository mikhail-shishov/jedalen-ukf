<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { Swiper, SwiperSlide } from 'swiper/vue';
import 'swiper/css';

const props = withDefaults(defineProps<{ excludeSlug?: string }>(), {
  excludeSlug: '',
});

interface Article {
  id: number;
  slug: string;
  title_sk?: string | null;
  title_en?: string | null;
  title_ua?: string | null;
  title_ru?: string | null;
  content_sk?: string | null;
  content_en?: string | null;
  content_ua?: string | null;
  content_ru?: string | null;
}

const { locale } = useI18n();
const articles = ref<Article[]>([]);
const isLoading = ref(false);

const normalizedLocale = computed(() => {
  const value = String(locale.value || 'sk').toLowerCase();
  return ['sk', 'en', 'ua', 'ru'].includes(value) ? value : 'sk';
});

const getLocalized = (article: Article, field: 'title' | 'content') => {
  const key = `${field}_${normalizedLocale.value}` as keyof Article;
  const fallbackKey = `${field}_sk` as keyof Article;
  return String(article[key] || article[fallbackKey] || '').trim();
};

const stripHtml = (text: string) => text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();

const truncateToWords = (text: string, limit = 10) => {
  const words = stripHtml(text).split(/\s+/).filter(Boolean);
  if (!words.length) {
    return '';
  }
  return `${words.slice(0, limit).join(' ')}...`;
};

const visibleArticles = computed(() => {
  if (!props.excludeSlug) {
    return articles.value;
  }
  return articles.value.filter((article) => article.slug !== props.excludeSlug);
});

const articleLink = (article: Article) => `/articles/${article.slug}`;

const loadArticles = async () => {
  isLoading.value = true;
  try {
    const response = await axios.get<Article[]>('/api/articles');
    articles.value = Array.isArray(response.data) ? response.data : [];
  } catch (error) {
    console.error('Failed to load articles:', error);
    articles.value = [];
  } finally {
    isLoading.value = false;
  }
};

const onSwiper = (_swiper: unknown) => {};

const onSlideChange = () => {
};

onMounted(loadArticles);
</script>

<template>
  <div class="container">
    <div v-if="isLoading" class="article-list article-list--placeholder">Načítavam články...</div>
    <div v-else-if="!visibleArticles.length" class="article-list article-list--placeholder">Články zatiaľ nie sú dostupné.</div>
    <swiper
      v-else
      :slides-per-view="3"
      :space-between="20"
      @swiper="onSwiper"
      @slideChange="onSlideChange"
      class="article-list"
    >
      <swiper-slide v-for="article in visibleArticles" :key="article.id" class="article-list__item">
        <div class="article-list__heading">{{ getLocalized(article, 'title') }}</div>
        <p class="article-list__text">{{ truncateToWords(getLocalized(article, 'content'), 10) }}</p>
        <a :href="articleLink(article)" class="link">Dozvedieť sa viac</a>
      </swiper-slide>
    </swiper>
  </div>
</template>

<style scoped lang="scss">
.container:first-of-type .article-list {
  margin-top: 0;
}

.article-list {
  margin: 40px 0;

  &--placeholder {
    background-color: white;
    border-radius: 8px;
    padding: 20px 24px;
    color: $grey1;
  }

  &__heading {
    font-size: 20px;
    font-weight: 700;
  }

  &__text {
    color: $grey1;
    margin: 12px 0 18px;
  }

  &__item {
    background-color: white;
    border-radius: 8px;
    padding: 20px 24px;
    box-shadow: 4px 4px 20px rgba(0,0,0,0.05);
  }
}
</style>
