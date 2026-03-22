<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import BasicDropdown from '@/components/BasicDropdown.vue';
import {
	emptyUserPreferences,
	fetchAllergenOptions,
	fetchUserPreferences,
	saveUserPreferences,
	type AllergenOption,
	type UserPreferences,
} from '@/services/userPreferences';
import {
	initPushScheduler,
	requestPushPermission,
	setPushSchedulerEnabled,
} from '@/services/pushNotifications';

const { locale, t, te } = useI18n();
const selectedLanguage = ref(locale.value);
const blockedIds = ref<number[]>([]);
const allergens = ref<AllergenOption[]>([]);
const statusMessage = ref('');
const isSaving = ref(false);
const preferences = ref<UserPreferences>(emptyUserPreferences());

const localeOptions = [
	{ value: 'sk', label: 'Slovenský' },
	{ value: 'en', label: 'English' },
	{ value: 'ua', label: 'Українська' },
	{ value: 'ru', label: 'Русский' },
];

const selectedLanguageOption = computed(() => {
	return localeOptions.find((option) => option.value === selectedLanguage.value) ?? localeOptions[0];
});

const allergenVisualMap: Record<number, { icon: string; color: string }> = {
	0: { icon: 'M', color: '#f2d1c9' },
	1: { icon: 'G', color: '#f2debf' },
	2: { icon: 'C', color: '#ff8e72' },
	3: { icon: 'E', color: '#f2e8b0' },
	4: { icon: 'F', color: '#7fc9ff' },
	5: { icon: 'P', color: '#d8a25a' },
	6: { icon: 'S', color: '#a8d73e' },
	7: { icon: 'D', color: '#9fe2e8' },
	8: { icon: 'N', color: '#d6a06a' },
	9: { icon: 'L', color: '#74c933' },
	10: { icon: 'H', color: '#f3d206' },
	11: { icon: 'Z', color: '#d9cf8c' },
	12: { icon: 'O', color: '#cfcfcf' },
	13: { icon: 'B', color: '#b88cff' },
	14: { icon: 'M', color: '#efc4c4' },
};

const displayedAllergens = computed(() => {
	return allergens.value.map((item) => {
		const visual = allergenVisualMap[item.number] ?? { icon: '#', color: '#d7d7d7' };
		const allergenKey = `settings.allergenNames.${item.number}`;
		return {
			id: item.id,
			number: item.number,
			text: te(allergenKey) ? t(allergenKey) : item.name,
			icon: visual.icon,
			color: visual.color,
		};
	});
});

const persistPreferences = async () => {
	isSaving.value = true;
	try {
		preferences.value = await saveUserPreferences({
			blocked_allergens: blockedIds.value,
			push_enabled: preferences.value.push_enabled,
			push_locale: selectedLanguage.value as UserPreferences['push_locale'],
		});
	} finally {
		isSaving.value = false;
	}
};

const onLanguageChange = (nextLocale: UserPreferences['push_locale']) => {
	selectedLanguage.value = nextLocale;
	locale.value = nextLocale;
	localStorage.setItem('preferred_locale', nextLocale);
	preferences.value.push_locale = nextLocale;
	void persistPreferences();
};

watch(
	() => locale.value,
	(nextLocale) => {
		selectedLanguage.value = nextLocale as UserPreferences['push_locale'];
	}
);

const toggleBlocked = (id: number) => {
	if (blockedIds.value.includes(id)) {
		blockedIds.value = blockedIds.value.filter(item => item !== id);
		void persistPreferences();
		return;
	}
	blockedIds.value = [...blockedIds.value, id];
	void persistPreferences();
};

const enablePushNotifications = async () => {
	statusMessage.value = '';
	const permission = await requestPushPermission();

	if (permission === 'unsupported') {
		statusMessage.value = t('settings.pushUnsupported');
		return;
	}

	if (permission === 'granted') {
		preferences.value.push_enabled = true;
		setPushSchedulerEnabled(true);
		initPushScheduler();
		await persistPreferences();
		statusMessage.value = t('settings.pushEnabled');
		return;
	}

	preferences.value.push_enabled = false;
	setPushSchedulerEnabled(false);
	await persistPreferences();
	statusMessage.value = t('settings.pushDenied');
};

onMounted(async () => {
	try {
		const [allergenList, loadedPreferences] = await Promise.all([
			fetchAllergenOptions(),
			fetchUserPreferences(),
		]);

		allergens.value = allergenList;
		preferences.value = loadedPreferences;
		blockedIds.value = [...loadedPreferences.blocked_allergens];
		selectedLanguage.value = locale.value as UserPreferences['push_locale'];
	} catch {
		allergens.value = [];
		blockedIds.value = [];
		preferences.value = emptyUserPreferences();
	}
});
</script>

<template>
	<div class="container settings-page">
		<section class="settings-card">
			<div class="settings-head">
				<div class="settings-title-wrap">
					<span class="settings-icon">⚙️</span>
					<h1 class="settings-title">{{ t('settings.title') }}</h1>
				</div>

				<button class="btn btn--blue-fill" type="button" :disabled="isSaving" @click="enablePushNotifications">{{ t('settings.push') }}</button>
			</div>

			<div class="settings-language-row">
				<label class="settings-language-label">{{ t('settings.language') }}</label>
				<BasicDropdown class="settings-language-dropdown">
					<template #trigger="{ isOpen }">
						<button class="basic-dropdown-trigger" :class="{ 'basic-dropdown-trigger--open': isOpen }" type="button">
							{{ selectedLanguageOption.label }}
							<span class="basic-dropdown-arrow">▾</span>
						</button>
					</template>

					<template #content>
						<div class="basic-dropdown-menu">
							<button
								v-for="option in localeOptions"
								:key="option.value"
								class="basic-dropdown-item"
								:class="{ 'basic-dropdown-item--active': option.value === selectedLanguage }"
								type="button"
								@click="onLanguageChange(option.value as UserPreferences['push_locale'])"
							>
								{{ option.label }}
							</button>
						</div>
					</template>
				</BasicDropdown>
			</div>

			<p v-if="statusMessage" class="settings-status-message">{{ statusMessage }}</p>

			<h2 class="settings-section-title">{{ t('settings.allergensTitle') }}</h2>
			<p class="settings-section-subtitle">{{ t('settings.allergensSubtitle') }}</p>

			<div class="settings-allergens-grid">
				<button
					v-for="item in displayedAllergens"
					:key="item.id"
					class="settings-allergen-card"
					type="button"
					:disabled="isSaving"
					:class="{ 'settings-allergen-card--blocked': blockedIds.includes(item.number) }"
					@click="toggleBlocked(item.number)"
				>
					<div v-if="blockedIds.includes(item.number)" class="settings-allergen-badge">
						{{ t('settings.blockedHint') }}
					</div>
					<span v-if="item.number !== 0" class="settings-allergen-id">{{ item.number }}.</span>
					<div class="settings-allergen-icon" :style="{ backgroundColor: item.color }">{{ item.icon }}</div>
					<p class="settings-allergen-text">{{ item.text }}</p>
				</button>
			</div>
		</section>
	</div>
</template>

<style scoped lang="scss">
.settings-page {
	padding: 24px 0;
}

.settings-card {
	background: #f4f4f4;
	border-radius: 10px;
	padding: 26px;
}

.settings-head {
	display: flex;
	justify-content: space-between;
	align-items: center;
}

.settings-title-wrap {
	display: flex;
	align-items: center;
	gap: 12px;
}

.settings-icon {
	font-size: 44px;
}

.settings-title {
	margin: 0;
	font-size: 48px;
	color: #2f2f2f;
}

.settings-language-row {
	margin-top: 26px;
	display: flex;
	align-items: center;
	gap: 24px;
}

.settings-language-label {
	font-size: 38px;
	color: #333;
}

.settings-language-dropdown {
	position: relative;
}

.settings-section-title {
	margin: 42px 0 10px;
	font-size: 44px;
	color: #2f2f2f;
}

.settings-status-message {
	margin: 16px 0 0;
	font-size: 18px;
	color: #1b5e73;
}

.settings-section-subtitle {
	margin: 0 0 24px;
	font-size: 30px;
	color: #4a4a4a;
}

.settings-allergens-grid {
	display: grid;
	grid-template-columns: repeat(5, minmax(0, 1fr));
	gap: 16px;
}

.settings-allergen-card {
	border: 1px solid #d3d3d3;
	border-radius: 4px;
	background: transparent;
	min-height: 210px;
	padding: 16px 14px;
	display: flex;
	flex-direction: column;
	align-items: center;
	text-align: center;
	gap: 10px;
	position: relative;
}

.settings-allergen-card--blocked {
	border-color: #ef8b8b;
}

.settings-allergen-badge {
	position: absolute;
	top: 8px;
	left: 8px;
	right: 8px;
	font-size: 11px;
	color: #e05d5d;
	text-align: left;
}

.settings-allergen-id {
	align-self: flex-start;
	font-size: 24px;
	color: #4c4c4c;
}

.settings-allergen-icon {
	width: 44px;
	height: 44px;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 20px;
}

.settings-allergen-text {
	margin: 0;
	font-size: 18px;
	color: #363636;
	line-height: 1.25;
}

@media (max-width: 1200px) {
	.settings-title {
		font-size: 34px;
	}

	.settings-section-title {
		font-size: 30px;
	}

	.settings-section-subtitle {
		font-size: 18px;
	}

	.settings-allergens-grid {
		grid-template-columns: repeat(2, minmax(0, 1fr));
	}
}
</style>
