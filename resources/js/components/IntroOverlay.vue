<script setup lang="ts">
import { computed, ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';

import intro1 from '@assets/img/intro-1.svg';
import intro2 from '@assets/img/intro-2.svg';
import intro3 from '@assets/img/intro-3.svg';

const emit = defineEmits(['toggle-view']);
const isVisible = ref(false);
const currentStep = ref(1);
const { t } = useI18n();

const stepAssets = [
  intro1,
  intro2,
  intro3,
];

const steps = computed(() => [
  {
    title: t('intro.step1'),
    img: intro1
  },
  {
    title: t('intro.step2'),
    img: intro2
  },
  {
    title: t('intro.step3'),
    img: intro3
  }
]);

const totalSteps = computed(() => stepAssets.length);

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
  <div v-if="isVisible" class="container">
    <div class="intro">
      <button class="close" type="button" :aria-label="t('intro.close')" @click="finishIntro">{{ t('intro.close') }}</button>

      <div class="intro__content">
        <transition name="fade" mode="out-in">
          <div :key="currentStep" class="intro__step">
            <h2 class="intro__title">{{ steps[currentStep - 1].title }}</h2>
            <img :src="steps[currentStep - 1].img" class="intro__img" :loading="currentStep === 1 ? 'eager' : 'lazy'" :alt="t('intro.illustrationAlt')" height="400">
          </div>
        </transition>
      </div>

      <div class="intro__footer">
        <div class="intro__pagination">
          <span v-for="n in totalSteps" :key="n" class="intro__dot"
            :class="{ 'intro__dot--active': currentStep === n }"></span>
        </div>

        <div class="intro__controls">
          <button v-if="currentStep > 1" @click="currentStep--" class="btn btn--blue">{{ t('intro.back') }}</button>

          <button v-if="currentStep < totalSteps" @click="currentStep++" class="btn btn--blue-fill">{{ t('intro.next') }}</button>

          <button v-else @click="finishIntro" class="btn btn--blue-fill">{{ t('intro.start') }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
.intro {
  background: #fff;
  z-index: 9998;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  position: relative;
  padding: 45px 65px;

  @media (max-width: 992px) {
    padding: 36px 30px 30px;
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
