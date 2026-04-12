<script setup lang="ts">
import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';
import { formatMoney } from '@/utils/formatMoney';

const auth = useAuthStore();
const { t } = useI18n();
const balanceText = computed(() => formatMoney(auth.user?.credit_balance ?? 0));
</script>

<template>
  <div class="container">
    <section class="payment-thank-you">
      <div class="payment-thank-you__icon-wrap">
        <img src="@assets/img/icons/check.svg" class="payment-thank-you__icon" width="44" height="44" alt=""
          aria-hidden="true">
      </div>
      <h1 class="h1">{{ t('paymentThankYou.title') }}</h1>
      <p>{{ t('paymentThankYou.subtitlePrefix') }} <RouterLink to="/history" class="link">{{
        t('paymentThankYou.subtitleLink') }}</RouterLink>.</p>
      <p>{{ t('paymentThankYou.balanceLabel') }} <strong>{{ balanceText }}</strong>.</p>
      <div class="payment-thank-you__actions">
        <RouterLink to="/" class="btn btn--blue-fill">{{ t('paymentThankYou.backHome') }}</RouterLink>
      </div>
    </section>
  </div>
</template>

<style lang="scss" scoped>
.payment-thank-you {
  background: white;
  border-radius: 8px;
  min-height: 70vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 100px 32px;

  @media (max-width: 992px) {
    padding: 100px 20px;
    min-height: unset;
  }
}

.payment-thank-you__icon {
  width: 150px;
  height: 150px;
  border-radius: 50%;

  p {
    margin-bottom: 0;
  }

  &-wrap {
    margin-bottom: 24px;

    @media (max-width: 992px) {
      margin-bottom: 0;
    }
  }

  @media (max-width: 992px) {
    width: 80px;
    height: 80px;
  }
}

.payment-thank-you__actions {
  margin-top: 36px;
}
</style>
