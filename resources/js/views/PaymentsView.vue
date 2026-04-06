<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { loadStripe } from '@stripe/stripe-js';
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';
import TextInput from '@/components/TextInput.vue';
import { formatMoney } from '@/utils/formatMoney';

const auth = useAuthStore();
const { t } = useI18n();
const amount = ref('');
const isProcessing = ref(false);
const stripeError = ref('');
const stripeSuccess = ref('');
const stripeReady = ref(false);
const MAX_AMOUNT_EUR = 1000;

type BankDetails = {
	client_name: string;
	account_name: string;
	account_number: string;
	iban: string;
	bank_name: string;
	refund_email: string;
};

const defaultBankDetails: BankDetails = {
	client_name: 'Mirko Petrík GASTROMIR',
	account_name: 'Mirko Petrík GASTROMIR',
	account_number: '51 9273 1010/0900',
	iban: 'SK52 0900 0000 0051 9273 1010',
	bank_name: 'Slovenskej sporiteľni, a. s.',
	refund_email: 'kreditukf@gmail.com',
};

const bankDetails = ref<BankDetails>({ ...defaultBankDetails });

const stripePublicKey = import.meta.env.VITE_STRIPE_PUBLISHABLE_KEY as string | undefined;
let stripe: Awaited<ReturnType<typeof loadStripe>> | null = null;
let elements: any = null;
let cardElement: any = null;

const balanceText = computed(() => formatMoney(auth.user?.credit_balance ?? 0));

const mapStripeErrorCode = (code: string | undefined, message: string | undefined): string => {
	if (!code) {
		if (message?.toLowerCase().includes('incomplete')) return t('payments.incompleteCardData');
		if (message?.toLowerCase().includes('expired')) return t('payments.expiredCard');
		if (message?.toLowerCase().includes('cvc')) return t('payments.incorrectCvc');
		return t('payments.unknownStripeError');
	}

	const errorMap: { [key: string]: string } = {
		'card_declined': 'cardDeclined',
		'expired_card': 'expiredCard',
		'incorrect_cvc': 'incorrectCvc',
		'incomplete_cvc': 'incompleteCvc',
		'incomplete_expiry': 'incompleteExpiry',
		'incomplete_zip': 'incompleteZip',
		'invalid_zip': 'incompleteZip',
		'processing_error': 'approvalFailed',
		'invalid_expiry_month': 'expiredCard',
		'invalid_expiry_year': 'expiredCard',
		'invalid_cvc': 'incorrectCvc',
	};

	const translationKey = errorMap[code] || 'unknownStripeError';
	return t(`payments.${translationKey}`);
};

const mapServerErrorCode = (message: string | undefined): string => {
	if (!message) return t('payments.unknownStripeError');

	if (message.includes('Unauthenticated')) {
		return t('payments.incompleteCardData');
	}
	if (message.includes('invalid_request_error')) {
		return t('payments.invalidAmount');
	}

	return t('payments.unknownStripeError');
};

const mapHttpStatusError = (statusCode: number | undefined): string | null => {
	if (statusCode === 401) return t('payments.authRequired');
	if (statusCode === 419) return t('payments.sessionExpired');
	if (statusCode === 500) return t('payments.serverError');
	return null;
};

const amountModel = computed({
	get: () => amount.value,
	set: (raw: string) => {
		if (!raw) {
			amount.value = '';
			return;
		}

		const normalized = raw.replace(/,/g, '.').replace(/[^\d.]/g, '');
		const firstDot = normalized.indexOf('.');
		const noExtraDots = firstDot === -1
			? normalized
			: `${normalized.slice(0, firstDot + 1)}${normalized.slice(firstDot + 1).replace(/\./g, '')}`;

		const [intPartRaw = '', decPartRaw = ''] = noExtraDots.split('.');
		const intPart = intPartRaw === '' ? '' : String(Number(intPartRaw));
		const decPart = decPartRaw.slice(0, 2);

		const integerValue = Number(intPart || 0);
		if (integerValue > MAX_AMOUNT_EUR) {
			amount.value = String(MAX_AMOUNT_EUR);
			return;
		}

		amount.value = noExtraDots.includes('.')
			? `${intPart}.${decPart}`
			: intPart;
	}
});

const initializeStripe = async () => {
	if (!stripePublicKey) {
		stripeError.value = t('payments.stripeNotConfigured');
		return;
	}

	stripe = await loadStripe(stripePublicKey);
	if (!stripe) {
		stripeError.value = t('payments.stripeInitFailed');
		return;
	}

	elements = stripe.elements();
	cardElement = elements.create('card', {
		style: {
			base: {
				fontSize: '16px',
				color: '#2f2f2f',
				'::placeholder': { color: '#9aa1a9' }
			}
		}
	});
	cardElement.mount('#stripe-card-element');
	stripeReady.value = true;
};

const loadBankDetails = async () => {
	try {
		const response = await axios.get<Partial<BankDetails>>('/api/payments/bank-details');
		const payload = response.data ?? {};

		bankDetails.value = {
			client_name: String(payload.client_name || defaultBankDetails.client_name),
			account_name: String(payload.account_name || defaultBankDetails.account_name),
			account_number: String(payload.account_number || defaultBankDetails.account_number),
			iban: String(payload.iban || defaultBankDetails.iban),
			bank_name: String(payload.bank_name || defaultBankDetails.bank_name),
			refund_email: String(payload.refund_email || defaultBankDetails.refund_email),
		};
	} catch {
		bankDetails.value = { ...defaultBankDetails };
	}
};

const payWithStripe = async () => {
	stripeError.value = '';
	stripeSuccess.value = '';

	const parsedAmount = Number(amount.value.replace(',', '.'));
	const validAmountFormat = /^\d+(?:\.\d{1,2})?$/.test(amount.value.replace(',', '.'));
	if (!parsedAmount || parsedAmount <= 0) {
		stripeError.value = t('payments.invalidAmount');
		return;
	}

	if (!validAmountFormat) {
		stripeError.value = t('payments.invalidPrecision');
		return;
	}

	if (parsedAmount < 0.5) {
		stripeError.value = t('payments.amountTooSmall');
		return;
	}

	if (parsedAmount > MAX_AMOUNT_EUR) {
		stripeError.value = t('payments.amountTooLarge');
		return;
	}

	if (!stripe || !cardElement) {
		stripeError.value = t('payments.notConnected');
		return;
	}

	isProcessing.value = true;

	try {
		const amountInCents = Math.round(parsedAmount * 100);
		const intentResponse = await axios.post('/api/payments/create-intent', {
			amount: amountInCents,
			currency: 'eur'
		});

		const clientSecret = intentResponse.data?.client_secret;
		if (!clientSecret) {
			stripeError.value = t('payments.paymentCreationFailed');
			return;
		}

		const result = await stripe.confirmCardPayment(clientSecret, {
			payment_method: {
				card: cardElement,
			},
		});

		if (result.error) {
			if (import.meta.env.DEV) console.error('[Payments] Stripe confirm error', result.error);
			stripeError.value = mapStripeErrorCode(result.error.code, result.error.message);
			return;
		}

		if (result.paymentIntent?.status === 'succeeded') {
			await axios.post('/api/payments/confirm', {
				payment_intent_id: result.paymentIntent.id,
			});

			stripeSuccess.value = t('payments.paymentSuccess');
			amount.value = '';
			cardElement.clear();
			await auth.fetchUser();
			window.location.href = '/payment/thank-you';
			return;
		}

		stripeError.value = t('payments.paymentIncomplete');
	} catch (error: any) {
		const serverMessage = error?.response?.data?.message;
		const serverCode = error?.response?.data?.code;
		const serverType = error?.response?.data?.type;
		const statusCode = error?.response?.status;
		const statusMessage = mapHttpStatusError(statusCode);

		if (import.meta.env.DEV) {
			console.error('[Payments] payment flow failed', {
				statusCode,
				serverCode,
				serverType,
				serverMessage,
				responseData: error?.response?.data,
			});
		}

		if (statusMessage) {
			stripeError.value = statusMessage;
			return;
		}

		if (statusCode === 422) {
			stripeError.value = mapStripeErrorCode(serverCode, serverMessage) || mapServerErrorCode(serverMessage);
			return;
		}

		stripeError.value = t('payments.serverError');
	} finally {
		isProcessing.value = false;
	}
};

onMounted(async () => {
	await Promise.all([initializeStripe(), loadBankDetails()]);
});
</script>

<template>
	<div class="container">
		<section class="payments">
			<div class="title-head">
				<div class="title-wrap">
					<span class="title-icon"><img src="@assets/img/icons/billing.svg" width="44" height="44" alt="" aria-hidden="true"></span>
					<h1 class="h1">{{ t('payments.title') }}</h1>
				</div>
				<div class="payments__top-right">
					<a href="/history" class="btn btn--blue-fill">{{ t('payments.historyButton') }}</a>
					<p class="payments__balance">{{ t('payments.balanceLabel') }} <span>{{ balanceText }}</span></p>
				</div>
			</div>

			<div class="payments__grid">
				<div class="payments__left" aria-labelledby="payments-card-title">
					<h2 class="h2" id="payments-card-title">{{ t('payments.cardPaymentTitle') }}</h2>
					<TextInput
						v-model="amountModel"
						:label="t('payments.amountLabel')"
						type="text"
						inputmode="decimal"
					/>
					<div id="stripe-card-element" class="payments__stripe-element" role="group" aria-label="Card details"></div>
					<button
						class="btn btn--green-fill"
						type="button"
						:disabled="isProcessing || !stripeReady"
						:aria-busy="isProcessing"
						@click="payWithStripe"
					>
						{{ isProcessing ? t('payments.processing') : t('payments.payButton') }}
					</button>
					<p v-if="stripeError" class="payments__message payments__message--error" role="alert" aria-live="assertive">{{ stripeError }}</p>
					<p v-if="stripeSuccess" class="payments__message payments__message--success" role="status" aria-live="polite">{{ stripeSuccess }}</p>
				</div>

				<div class="payments__right" aria-labelledby="payments-bank-title">
					<h2 class="h2" id="payments-bank-title">{{ t('payments.bankTransferTitle') }}</h2>
					<p>{{ t('payments.clientLine', { clientName: bankDetails.client_name }) }}</p>
					<p>{{ t('payments.accountNameLine', { accountName: bankDetails.account_name }) }}</p>
					<p>{{ t('payments.accountLine', { accountNumber: bankDetails.account_number, bankName: bankDetails.bank_name }) }}</p>
					<p>{{ t('payments.ibanLine', { iban: bankDetails.iban }) }}</p>

					<h3 class="h3">{{ t('payments.variableSymbolTitle') }}</h3>

					<h4 class="h4">{{ t('payments.employeesTitle') }}</h4>
					<p>{{ t('payments.employeesVsHint') }}</p>

					<h4 class="h4">{{ t('payments.studentsTitle') }}</h4>
					<p>{{ t('payments.studentsVsHint') }}</p>
					<p class="payments__note">{{ t('payments.transferNotice') }}</p>
				</div>
			</div>

			<p class="payments__bottom-info">
        <img src="@assets/img/icons/info-666.svg" class="payments__bottom-icon" alt="">
				{{ t('payments.bottomInfoLine1', { refundEmail: bankDetails.refund_email }) }}
				{{ t('payments.bottomInfoLine2') }}
			</p>
		</section>
	</div>
</template>

<style lang="scss" scoped>
.payments {
  padding: 40px 50px 44px;
  background-color: white;
  border-radius: 8px;

  @media (max-width: 992px) {
    padding: 20px;
  }
}

.payments-title-wrap {
	display: flex;
	align-items: center;
	gap: 10px;
}

.payments-title-icon {
	font-size: 28px;
}

.payments-title {
	margin: 0;
	font-size: 40px;
	line-height: 1;
}

.payments__top-right {
	display: flex;
	align-items: center;
	gap: 18px;
}

.payments__balance {
	margin: 0;
	font-size: 24px;
	font-weight: 700;
	color: $grey1;

  span {
	  color: $lightblue1;
    @media (max-width: 992px) {
      text-wrap: nowrap;
    }
  }

  @media (max-width: 992px) {
    font-size: 18px;
  }
}

.payments__grid {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 30px;
	padding-top: 60px;

  @media (max-width: 992px) {
    padding-top: 30px;
  }
}

.payments__left {
	padding-right: 40px;
	border-right: 1px solid $grey6;
}

.payments__stripe-element {
	border: 1px solid $grey6;
	border-radius: 4px;
	background: #fff;
	padding: 12px 16px;
	margin: 16px 0;
}

.payments__message {
	margin: 12px 0 0;
	font-size: 22px;
}

.payments__message--error {
	color: $red1;
}

.payments__message--success {
	color: $green1;
}

.payments__note {
	font-weight: 600;
}

.payments__bottom {
  &-info {
    display: flex;
    gap: 8px;
    align-items: flex-start;
  }
  &-icon {
    margin-top: 2px;
  }
}

@media (max-width: 1200px) {
	.payments__balance {
		font-size: 18px;
	}

	.payments__grid {
		grid-template-columns: 1fr;
	}

	.payments__left {
		border-right: 0;
		padding-right: 0;
		border-bottom: 1px solid $grey6;
		padding-bottom: 30px;
	}
}
</style>
