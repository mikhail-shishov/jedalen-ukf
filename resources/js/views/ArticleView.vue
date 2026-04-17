<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import ArticlesList from '@/components/ArticlesList.vue';

interface Article {
  id: number;
  slug: string;
  image_path?: string | null;
  title_sk?: string | null;
  title_en?: string | null;
  title_ua?: string | null;
  title_ru?: string | null;
  content_sk?: string | null;
  content_en?: string | null;
  content_ua?: string | null;
  content_ru?: string | null;
  created_at?: string | null;
}

const route = useRoute();
const { locale } = useI18n();

const article = ref<Article | null>(null);
const loadError = ref(false);

const setMetaTag = (selector: string, attrName: 'property' | 'name', attrValue: string, content: string) => {
  const node = document.head.querySelector(selector) as HTMLMetaElement | null;
  if (node) {
    node.setAttribute('content', content);
    return;
  }

  const meta = document.createElement('meta');
  meta.setAttribute(attrName, attrValue);
  meta.setAttribute('content', content);
  document.head.appendChild(meta);
};

const updateSocialMeta = (title: string, imageUrl: string | null) => {
  setMetaTag('meta[property="og:title"]', 'property', 'og:title', title || 'Jedáleň UKF');
  setMetaTag('meta[name="twitter:title"]', 'name', 'twitter:title', title || 'Jedáleň UKF');

  if (imageUrl) {
    setMetaTag('meta[property="og:image"]', 'property', 'og:image', imageUrl);
    setMetaTag('meta[name="twitter:image"]', 'name', 'twitter:image', imageUrl);
  }
};

const resetSocialMeta = () => {
  const fallbackImage = `${window.location.origin}/favicon.ico`;
  setMetaTag('meta[property="og:title"]', 'property', 'og:title', 'Jedáleň UKF');
  setMetaTag('meta[name="twitter:title"]', 'name', 'twitter:title', 'Jedáleň UKF');
  setMetaTag('meta[property="og:image"]', 'property', 'og:image', fallbackImage);
  setMetaTag('meta[name="twitter:image"]', 'name', 'twitter:image', fallbackImage);
};

const normalizedLocale = computed(() => {
  const value = String(locale.value || 'sk').toLowerCase();
  return ['sk', 'en', 'ua', 'ru'].includes(value) ? value : 'sk';
});

const currentSlug = computed(() => String(route.params.slug || ''));

const localizedTitle = computed(() => {
  if (!article.value) return '';
  const key = `title_${normalizedLocale.value}` as keyof Article;
  const fallback = 'title_sk' as keyof Article;
  return String(article.value[key] || article.value[fallback] || '').trim();
});

const localizedContent = computed(() => {
  if (!article.value) return '';
  const key = `content_${normalizedLocale.value}` as keyof Article;
  const fallback = 'content_sk' as keyof Article;
  return String(article.value[key] || article.value[fallback] || '').trim();
});

const loadArticle = async () => {
  if (!currentSlug.value) {
    loadError.value = true;
    return;
  }

  loadError.value = false;

  try {
    const response = await axios.get<Article>(`/api/articles/${encodeURIComponent(currentSlug.value)}`);
    article.value = response.data;
  } catch (error) {
    console.error('Failed to load article:', error);
    article.value = null;
    loadError.value = true;
  }
};

onMounted(loadArticle);

watch(
  () => localizedTitle.value,
  (title) => {
    if (title) {
      document.title = `${title} | Jedáleň UKF`;
    }

    const imageUrl = article.value?.image_path ? String(article.value.image_path) : null;
    updateSocialMeta(title, imageUrl);
  },
  { immediate: true }
);

onBeforeUnmount(() => {
  resetSocialMeta();
});
</script>

<template>
  <div class="container article-page">
    <article v-if="article" class="article-card">
      <h1 class="h1">{{ localizedTitle }}</h1>
      <div class="article-card__content" v-html="localizedContent"></div>
    </article>
    <div v-else-if="loadError" class="article-page__placeholder">
      Článok sa nepodarilo načítať. <a href="/">Späť na menu</a>
    </div>

    <section class="article-page__more">
      <h2 class="h1">Ine články</h2>
      <ArticlesList :exclude-slug="currentSlug" />
    </section>
  </div>
</template>

<style scoped lang="scss">
.article-page {
  &__placeholder {
    background: white;
    border-radius: 10px;
    padding: 24px;
    color: $grey1;
  }

  &__more {
    margin-top: 34px;
  }
}

.article-card {
  background: #fff;
  border-radius: 10px;
  padding: 40px 50px;

  @media (max-width: 992px) {
    padding: 30px 20px;
  }

  &__image {
    width: 100%;
    border-radius: 8px;
    display: block;
  }

  &__content {
    color: $grey1;
    line-height: 1.4;

    :deep(p) {
      margin: 0 0 15px;
    }

    :deep(h2),
    :deep(h3) {
      margin: 30px 0 20px;
    }

    :deep(img) {
      max-width: 100%;
      height: auto;
      margin: 28px 0;
    }

    :deep(a[class=""]),
    :deep(a:not([class])) {
      color: $blue1;
      font-weight: 700;
    }
  }
}
</style>
