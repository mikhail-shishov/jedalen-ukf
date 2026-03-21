<script setup lang="ts">
import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';

const auth = useAuthStore();
const { t } = useI18n();
const balanceText = computed(() => `${Number(auth.user?.credit_balance ?? 0).toFixed(2)} €`);
</script>

<template>
  <div class="container thanks-page">
    <section class="thanks-card">
      <div class="thanks-icon-wrap">
        <div class="thanks-icon">✓</div>
      </div>
      <h1 class="thanks-title">{{ t('paymentThankYou.title') }}</h1>
      <p class="thanks-subtitle">{{ t('paymentThankYou.subtitlePrefix') }} <a href="/history">{{ t('paymentThankYou.subtitleLink') }}</a>.</p>
      <p class="thanks-balance">{{ t('paymentThankYou.balanceLabel') }} <strong>{{ balanceText }}</strong></p>
      <div class="thanks-actions">
        <a href="/" class="btn btn--blue-fill">{{ t('paymentThankYou.backHome') }}</a>
      </div>
    </section>
  </div>
</template>

<style lang="scss" scoped>
.thanks-page {
  padding: 24px 0;
}

.thanks-card {
  background: #f4f4f4;
  border-radius: 10px;
  min-height: 650px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 32px;
}

.thanks-icon-wrap {
  margin-bottom: 24px;
}

.thanks-icon {
  width: 160px;
  height: 160px;
  border: 8px solid #25c481;
  border-radius: 50%;
  color: #25c481;
  font-size: 88px;
  line-height: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.thanks-title {
  margin: 0;
  font-size: 56px;
  font-weight: 700;
  color: #333;
}

.thanks-subtitle {
  margin: 18px 0 0;
  font-size: 34px;
  color: #4a4a4a;
}

.thanks-subtitle a {
  color: #3f3f3f;
}

.thanks-balance {
  margin: 16px 0 0;
  font-size: 28px;
  color: #2f2f2f;
}

.thanks-actions {
  margin-top: 36px;
}

@media (max-width: 1200px) {
  .thanks-card {
    min-height: 480px;
    padding: 24px;
  }

  .thanks-icon {
    width: 120px;
    height: 120px;
    border-width: 6px;
    font-size: 60px;
  }

  .thanks-title {
    font-size: 34px;
  }

  .thanks-subtitle {
    font-size: 24px;
  }

  .thanks-balance {
    font-size: 20px;
  }
}
</style>
