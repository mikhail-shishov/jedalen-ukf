<script setup lang="ts">
import { ref, onMounted } from 'vue';

import intro1 from '@assets/img/intro-1.svg';
import intro2 from '@assets/img/intro-2.svg';
import intro3 from '@assets/img/intro-3.svg';

const emit = defineEmits(['toggle-view']);
const isVisible = ref(false);
const currentStep = ref(1);
const totalSteps = 3;

const steps = [
  {
    title: "Na prihlásenie použi svoje prihlasovacie údaje, ktoré sú rovnaké ako v AiS.",
    img: intro1
  },
  {
    title: "Objednaj si jedlo minimálne deň vopred a príď si ho vyzdvihnúť v čase, keď je jedáleň otvorená. Ak si ho nemôžeš vyzdvihnúť, môžeš ho presunúť do burzy a možno ho niekto iný vykúpi.",
    img: intro2
  },
  {
    title: "Históriu objednávok a svoje štatistiky môžeš sledovať vo svojom profile. Zároveň si tam môžeš nastaviť svoje chuťové preferencie a alergény. Viac informácii si najdeš v naších člankach.",
    img: intro3
  }
];

onMounted(() => {
  const hasSeenIntro = localStorage.getItem('hasSeenIntro');
  if (!hasSeenIntro) {
    isVisible.value = true;
    emit('toggle-view', true);
  }
});

const finishIntro = () => {
  isVisible.value = false;
  localStorage.setItem('hasSeenIntro', 'true');
  emit('toggle-view', false);
};
</script>

<template>
  <div class="container">
    <div v-if="isVisible" class="intro">
      <button class="close" @click="finishIntro">Zatvoriť</button>

      <div class="intro__content">
        <transition name="fade" mode="out-in">
          <div :key="currentStep" class="intro__step">
            <h2 class="intro__title">{{ steps[currentStep - 1].title }}</h2>
            <img :src="steps[currentStep - 1].img" class="intro__img" :loading="currentStep === 1 ? 'eager' : 'lazy'" alt="Illustration" height="400">
          </div>
        </transition>
      </div>

      <div class="intro__footer">
        <div class="intro__pagination">
          <span v-for="n in totalSteps" :key="n" class="intro__dot"
            :class="{ 'intro__dot--active': currentStep === n }"></span>
        </div>

        <div class="intro__controls">
          <button v-if="currentStep > 1" @click="currentStep--" class="btn btn--blue">Späť</button>

          <button v-if="currentStep < totalSteps" @click="currentStep++" class="btn btn--blue-fill">Ďalej</button>

          <button v-else @click="finishIntro" class="btn btn--blue-fill">Do toho!</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
.intro {
  background: #fff;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  position: relative;
  padding: 45px 65px;

  @media (max-width: 992px) {
    padding: 30px;
  }

  &__step {
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  &__title {
    color: $grey1;
    margin: 0 auto 50px;
    font-size: 28px;
    text-align: center;
    max-width: 940px;
    min-height: 100px;

    @media (max-width: 992px) {
      font-size: 20px;
      margin-bottom: 20px;
    }
  }

  &__img {
    max-width: 100%;
    max-height: 400px;

    @media (max-width: 992px) {
      max-height: 250px;
    }
  }

  &__footer {
    display: flex;
    align-items: center;
    margin-top: 40px;
    width: 100%;
    position: relative;

    @media (max-width: 992px) {
      flex-direction: column;
      margin-top: 20px;
    }
  }

  &__pagination {
    display: flex;
    gap: 16px;
    position: absolute;
    margin: 0 auto;
    left: 50%;
    transform: translateX(-50%);

    @media (max-width: 992px) {
      gap: 12px;
    }
  }

  &__dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: $grey5;
    transition: .3s;

    &--active {
      background: $blue1;
    }

    @media (max-width: 992px) {
      width: 8px;
      height: 8px;
    }
  }

  &__controls {
    display: flex;
    width: 100%;
    justify-content: flex-end;
    margin-left: auto;
    gap: 8px;

    @media (max-width: 992px) {
      justify-content: space-between;
      margin-top: 20px;

      .btn:last-child {
        margin-left: auto;
      }
    }
  }

  &__btn {
    padding: 0.8rem 2rem;
    border: none;
    border-radius: 8px;
    font-weight: 700;
    cursor: pointer;
    transition: opacity 0.2s;

    &:hover {
      opacity: 0.9;
    }

    &--back {
      background: #f0f0f0;
      color: #333;
    }

    &--next {
      background: #003366;
      color: #fff;
    }

    &--finish {
      background: #28a745;
      color: #fff;
    }
  }

  &__skip {
    background: none;
    border: none;
    color: #888;
    text-decoration: underline;
    cursor: pointer;
    font-size: 0.9rem;
  }
}

.fade-enter-active,
.fade-leave-active {
  transition: .3s;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>