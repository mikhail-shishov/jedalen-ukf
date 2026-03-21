<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';

type PaymentHistoryItem = {
	id: number;
	created_at: string;
	status: string;
	method: string;
	amount: number;
	note: string | null;
};

const auth = useAuthStore();
const items = ref<PaymentHistoryItem[]>([]);
const total = ref(0);
const isLoading = ref(false);
const LIMIT = 10;

const balanceText = computed(() => `${Number(auth.user?.credit_balance ?? 0).toFixed(2)} €`);
const canLoadMore = computed(() => items.value.length < total.value);

const formatDateTime = (value: string): string => {
	const date = new Date(value);
	if (Number.isNaN(date.getTime())) return value;
	return new Intl.DateTimeFormat('sk-SK', {
		day: '2-digit',
		month: '2-digit',
		year: 'numeric',
		hour: '2-digit',
		minute: '2-digit',
	}).format(date);
};

const formatAmount = (value: number): string => `${Number(value).toFixed(2)} €`;

const loadHistory = async () => {
	if (isLoading.value) return;
	isLoading.value = true;
	try {
		const response = await axios.get('/api/payments/history', {
			params: {
				offset: items.value.length,
				limit: LIMIT,
			},
		});

		const payloadItems = Array.isArray(response.data?.items) ? response.data.items : [];
		items.value.push(...payloadItems);
		total.value = Number(response.data?.total ?? items.value.length);
	} finally {
		isLoading.value = false;
	}
};

onMounted(async () => {
	await auth.fetchUser();
	await loadHistory();
});
</script>

<template>
	<div class="container history-page">
		<section class="history-card">
			<div class="history-head">
				<div class="history-title-wrap">
					<span class="history-icon">📋</span>
					<h1 class="history-title">História platieb</h1>
				</div>
				<div class="history-top-right">
					<a href="/payment" class="btn btn--green-fill">Pridať peniazi</a>
					<p class="history-balance">Na účte je <span>{{ balanceText }}</span></p>
				</div>
			</div>

			<div class="history-table-wrap">
				<table class="history-table">
					<thead>
						<tr>
							<th>Dátum a čas</th>
							<th>Stav</th>
							<th>Metóda</th>
							<th>Suma</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="item in items" :key="item.id">
							<td>{{ formatDateTime(item.created_at) }}</td>
							<td>{{ item.status }}</td>
							<td>{{ item.method }}</td>
							<td class="history-table__amount">{{ formatAmount(item.amount) }}</td>
						</tr>
						<tr v-if="!isLoading && items.length === 0">
							<td colspan="4" class="history-table__empty">Žiadne platby zatiaľ neboli zaznamenané.</td>
						</tr>
					</tbody>
				</table>
			</div>

			<div v-if="canLoadMore" class="history-load-more">
				<button class="btn btn--blue-fill" type="button" :disabled="isLoading" @click="loadHistory">
					{{ isLoading ? 'Načítavam...' : 'Načítať viac' }}
				</button>
			</div>
		</section>
	</div>
</template>

<style scoped lang="scss">
.history-page {
	padding: 24px 0;
}

.history-card {
	background: #f4f4f4;
	border-radius: 10px;
	padding: 24px;
}

.history-head {
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: 16px;
	margin-bottom: 24px;
}

.history-title-wrap {
	display: flex;
	align-items: center;
	gap: 12px;
}

.history-icon {
	font-size: 32px;
}

.history-title {
	margin: 0;
	font-size: 42px;
	color: #333;
}

.history-top-right {
	display: flex;
	align-items: center;
	gap: 16px;
}

.history-balance {
	margin: 0;
	font-size: 38px;
	font-weight: 700;
	color: #333;
}

.history-balance span {
	color: #35b8d7;
}

.history-table-wrap {
	overflow-x: auto;
}

.history-table {
	width: 100%;
	border-collapse: separate;
	border-spacing: 0 8px;
}

.history-table thead th {
	text-align: left;
	font-size: 20px;
	color: #9b9b9b;
	font-weight: 500;
	padding: 0 18px 6px;
}

.history-table tbody tr {
	background: #ececec;
}

.history-table tbody td {
	padding: 16px 18px;
	font-size: 30px;
	color: #1f1f1f;
}

.history-table__amount {
	text-align: right;
	font-weight: 700;
}

.history-table__empty {
	text-align: center;
	color: #8a8a8a;
}

.history-load-more {
	display: flex;
	justify-content: center;
	margin-top: 20px;
}

@media (max-width: 1200px) {
	.history-title {
		font-size: 30px;
	}

	.history-balance {
		font-size: 24px;
	}

	.history-table thead th,
	.history-table tbody td {
		font-size: 18px;
	}

	.history-head {
		flex-direction: column;
		align-items: flex-start;
	}
}
</style>
