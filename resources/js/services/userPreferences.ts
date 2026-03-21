import axios from 'axios';

export interface UserPreferences {
  blocked_allergens: number[];
  push_enabled: boolean;
  push_locale: 'sk' | 'en' | 'ua' | 'ru';
}

export interface AllergenOption {
  id: number;
  number: number;
  name: string;
}

const defaultPreferences: UserPreferences = {
  blocked_allergens: [],
  push_enabled: false,
  push_locale: 'sk',
};

const normalizeLocale = (value: unknown): UserPreferences['push_locale'] => {
  if (value === 'en' || value === 'ua' || value === 'ru' || value === 'sk') {
    return value;
  }
  return 'sk';
};

const toIntegerList = (value: unknown): number[] => {
  if (!Array.isArray(value)) {
    return [];
  }

  return value
    .map((entry) => Number(entry))
    .filter((entry) => Number.isInteger(entry) && entry >= 0);
};

export const fetchUserPreferences = async (): Promise<UserPreferences> => {
  const { data } = await axios.get('/api/settings/preferences');

  return {
    blocked_allergens: toIntegerList(data?.blocked_allergens),
    push_enabled: Boolean(data?.push_enabled),
    push_locale: normalizeLocale(data?.push_locale),
  };
};

export const saveUserPreferences = async (preferences: UserPreferences): Promise<UserPreferences> => {
  const payload = {
    blocked_allergens: toIntegerList(preferences.blocked_allergens),
    push_enabled: Boolean(preferences.push_enabled),
    push_locale: normalizeLocale(preferences.push_locale),
  };

  const { data } = await axios.post('/api/settings/preferences', payload);
  const result = data?.preferences ?? payload;

  return {
    blocked_allergens: toIntegerList(result.blocked_allergens),
    push_enabled: Boolean(result.push_enabled),
    push_locale: normalizeLocale(result.push_locale),
  };
};

export const fetchAllergenOptions = async (): Promise<AllergenOption[]> => {
  const { data } = await axios.get('/api/settings/allergens');

  if (!Array.isArray(data)) {
    return [];
  }

  return data
    .map((entry) => ({
      id: Number(entry?.id),
      number: Number(entry?.number),
      name: String(entry?.name ?? ''),
    }))
    .filter((entry) => Number.isInteger(entry.id) && Number.isFinite(entry.number) && entry.name.length > 0)
    .sort((a, b) => a.number - b.number);
};

export const emptyUserPreferences = (): UserPreferences => ({ ...defaultPreferences });
