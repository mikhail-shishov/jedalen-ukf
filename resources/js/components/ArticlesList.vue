<script setup lang="ts">
import { Swiper, SwiperSlide } from 'swiper/vue';
import 'swiper/css';

const onSwiper = (swiper: any) => {
};

const onSlideChange = () => {
};
</script>

<template>
  <div class="container">
    <swiper :slides-per-view="3" :space-between="20" @swiper="onSwiper" @slideChange="onSlideChange"
      class="article-list">
      <swiper-slide class="article-list__item">
        <div class="article-list__heading">Prihlasenie do systemu</div>
        <p class="article-list__text">Prihlasiť sa je možne pomocou svojich údajov pre AIS...</p>

        <a href="" class="link">Dozvedieť sa viac</a>
      </swiper-slide>
      <swiper-slide class="article-list__item">
        <div class="article-list__heading">Objednavanie a možnosti platby</div>
        <p class="article-list__text">V tomto članku môžete prečítat o tom, ako a kedy môžete si objednať jedlo...</p>

        <a href="" class="link">Dozvedieť sa viac</a>
      </swiper-slide>
      <swiper-slide class="article-list__item">
        <div class="article-list__heading">Zoznam alergénov</div>
        <p class="article-list__text">Pozor, niektoré jedla môžú obsahovať alergeny. Viac o tom najdete nižšie....</p>

        <a href="" class="link">Dozvedieť sa viac</a>
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