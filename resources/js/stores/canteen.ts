import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';
import { refreshPushScheduler } from '@/services/pushNotifications';

const SELECTED_CANTEEN_STORAGE_KEY = 'selected_canteen_id';
const CANTEEN_DATA_STORAGE_KEY = 'canteens_data';
const CANTEEN_DATA_CACHE_TTL_MS = 5 * 60 * 1000;
const SELECTED_CANTEEN_COOKIE_KEY = 'selected_canteen_id';

type PersistedCanteenCache = {
  items: Canteen[];
  updatedAt: number;
};

let canteensInFlightRequest: Promise<Canteen[]> | null = null;

export interface CanteenClosure {
  date: string;
  is_closed: boolean;
  open_time: string | null;
  close_time: string | null;
  reason: string | null;
}

export interface CanteenScheduleDay {
  open_time: string | null;
  close_time: string | null;
}

export interface CanteenSchedule {
  mon: CanteenScheduleDay;
  tue: CanteenScheduleDay;
  wed: CanteenScheduleDay;
  thu: CanteenScheduleDay;
  fri: CanteenScheduleDay;
  sat: CanteenScheduleDay;
  sun: CanteenScheduleDay;
}

export interface Canteen {
  id: number;
  name: string;
  address: string;
  timezone: string;
  is_active: boolean;
  notifications_enabled: boolean;
  notify_open_offset_min: number;
  notify_close_offset_min: number;
  schedule: CanteenSchedule;
  closures: CanteenClosure[];
}

const defaultScheduleDay = (): CanteenScheduleDay => ({ open_time: null, close_time: null });

const defaultSchedule = (): CanteenSchedule => ({
  mon: defaultScheduleDay(),
  tue: defaultScheduleDay(),
  wed: defaultScheduleDay(),
  thu: defaultScheduleDay(),
  fri: defaultScheduleDay(),
  sat: defaultScheduleDay(),
  sun: defaultScheduleDay(),
});

const normalizeSchedule = (value: unknown): CanteenSchedule => {
  const fallback = defaultSchedule();
  if (!value || typeof value !== 'object') {
    return fallback;
  }

  const record = value as Record<string, unknown>;
  const days: Array<keyof CanteenSchedule> = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
  days.forEach((day) => {
    const source = record[day];
    if (!source || typeof source !== 'object') {
      return;
    }

    const dayRecord = source as Record<string, unknown>;
    fallback[day] = {
      open_time: dayRecord.open_time ? String(dayRecord.open_time) : null,
      close_time: dayRecord.close_time ? String(dayRecord.close_time) : null,
    };
  });

  return fallback;
};

const normalizeClosures = (value: unknown): CanteenClosure[] => {
  if (!Array.isArray(value)) {
    return [];
  }

  return value
    .map((item) => {
      if (!item || typeof item !== 'object') {
        return null;
      }

      const record = item as Record<string, unknown>;
      const date = String(record.date ?? '');
      if (!date) {
        return null;
      }

      return {
        date,
        is_closed: Boolean(record.is_closed),
        open_time: record.open_time ? String(record.open_time) : null,
        close_time: record.close_time ? String(record.close_time) : null,
        reason: record.reason ? String(record.reason) : null,
      };
    })
    .filter((item): item is CanteenClosure => item !== null);
};

const normalizeBoolean = (value: unknown, fallback = false): boolean => {
  if (value === undefined || value === null) {
    return fallback;
  }

  if (value === true || value === 1 || value === '1') {
    return true;
  }

  if (value === false || value === 0 || value === '0' || value === 'false') {
    return false;
  }

  return Boolean(value);
};

const setSelectedCanteenCookie = (id: number) => {
  if (typeof document === 'undefined') {
    return;
  }

  document.cookie = `${SELECTED_CANTEEN_COOKIE_KEY}=${id}; path=/; max-age=31536000; samesite=lax`;
};

const clearSelectedCanteenCookie = () => {
  if (typeof document === 'undefined') {
    return;
  }

  document.cookie = `${SELECTED_CANTEEN_COOKIE_KEY}=; path=/; max-age=0; samesite=lax`;
};

const readSelectedCanteenCookie = (): number | null => {
  if (typeof document === 'undefined') {
    return null;
  }

  const match = document.cookie
    .split('; ')
    .find((chunk) => chunk.startsWith(`${SELECTED_CANTEEN_COOKIE_KEY}=`));

  if (!match) {
    return null;
  }

  const value = Number(match.split('=')[1]);
  return Number.isFinite(value) && value > 0 ? value : null;
};

const readSavedCanteenId = (): number | null => {
  if (typeof localStorage !== 'undefined') {
    const raw = localStorage.getItem(SELECTED_CANTEEN_STORAGE_KEY);
    const value = Number(raw);
    if (Number.isFinite(value) && value > 0) {
      return value;
    }
  }

  return readSelectedCanteenCookie();
};

const isCanteenCacheFresh = (updatedAt: number): boolean => {
  return updatedAt > 0 && (Date.now() - updatedAt) <= CANTEEN_DATA_CACHE_TTL_MS;
};

const readPersistedCanteenCache = (): PersistedCanteenCache | null => {
  if (typeof localStorage === 'undefined') {
    return null;
  }

  try {
    const raw = localStorage.getItem(CANTEEN_DATA_STORAGE_KEY);
    if (!raw) {
      return null;
    }

    const parsed = JSON.parse(raw) as unknown;

    if (Array.isArray(parsed)) {
      return {
        items: normalizeCanteens(parsed),
        updatedAt: 0,
      };
    }

    if (!parsed || typeof parsed !== 'object') {
      return null;
    }

    const record = parsed as Record<string, unknown>;
    const items = normalizeCanteens(record.items);
    const updatedAt = Number(record.updatedAt ?? 0);

    return {
      items,
      updatedAt,
    };
  } catch {
    return null;
  }
};

const persistCanteenData = (items: Canteen[]) => {
  if (typeof localStorage === 'undefined') {
    return;
  }

  const payload: PersistedCanteenCache = {
    items,
    updatedAt: Date.now(),
  };

  localStorage.setItem(CANTEEN_DATA_STORAGE_KEY, JSON.stringify(payload));
};

const normalizeCanteens = (payload: unknown): Canteen[] => {
  const rawItems = Array.isArray(payload)
    ? payload
    : payload && typeof payload === 'object'
      ? Object.values(payload as Record<string, unknown>)
      : [];

  return rawItems
    .map((item, index) => {
      if (typeof item === 'string') {
        return {
          id: index + 1,
          name: item,
          address: '',
          timezone: 'Europe/Bratislava',
          is_active: true,
          notifications_enabled: true,
          notify_open_offset_min: 30,
          notify_close_offset_min: 30,
          schedule: defaultSchedule(),
          closures: [],
        };
      }

      if (item && typeof item === 'object') {
        const record = item as Record<string, unknown>;
        const id = Number(record.id);
        const name = String(record.name ?? '').trim();
        const address = String(record.address ?? '').trim();

        if (Number.isFinite(id) && id > 0 && name.length > 0) {
          return {
            id,
            name,
            address,
            timezone: String(record.timezone ?? 'Europe/Bratislava'),
            is_active: normalizeBoolean(record.is_active, true),
            notifications_enabled: record.notifications_enabled === undefined ? true : Boolean(record.notifications_enabled),
            notify_open_offset_min: Number(record.notify_open_offset_min ?? 30),
            notify_close_offset_min: Number(record.notify_close_offset_min ?? 30),
            schedule: normalizeSchedule(record.schedule),
            closures: normalizeClosures(record.closures),
          };
        }
      }

      return null;
    })
    .filter((item): item is Canteen => item !== null && item.is_active);
};

export const useCanteenStore = defineStore('canteen', () => {
  const persistedCache = readPersistedCanteenCache();
  const canteens = ref<Canteen[]>(persistedCache?.items ?? []);
  const currentCanteenId = ref<number | null>(readSavedCanteenId());

  const syncSelectedCanteen = () => {
    if (currentCanteenId.value !== null) {
      currentCanteenId.value = Number(currentCanteenId.value);
    }

    if (!canteens.value.some((c) => c.id === currentCanteenId.value)) {
      currentCanteenId.value = canteens.value.length ? canteens.value[0].id : null;
    }

    if (currentCanteenId.value !== null) {
      if (typeof localStorage !== 'undefined') {
        localStorage.setItem(SELECTED_CANTEEN_STORAGE_KEY, String(currentCanteenId.value));
      }
      setSelectedCanteenCookie(currentCanteenId.value);
    }
  };

  const currentCanteen = computed<Canteen | null>(
    () => canteens.value.find((c) => c.id === currentCanteenId.value) ?? null
  );

  const fetchCanteens = async () => {
    if (canteens.value.length) {
      syncSelectedCanteen();
      refreshPushScheduler();
      return;
    }

    const cache = readPersistedCanteenCache();
    if (cache?.items.length && isCanteenCacheFresh(cache.updatedAt)) {
      canteens.value = cache.items;
      syncSelectedCanteen();
      refreshPushScheduler();
      return;
    }

    if (canteensInFlightRequest) {
      canteens.value = await canteensInFlightRequest;
      syncSelectedCanteen();
      refreshPushScheduler();
      return;
    }

    try {
      canteensInFlightRequest = axios.get('/api/canteens')
        .then(({ data }) => normalizeCanteens(data))
        .finally(() => {
          canteensInFlightRequest = null;
        });

      const normalized = await canteensInFlightRequest;

      canteens.value = normalized;
      persistCanteenData(normalized);

      syncSelectedCanteen();

      refreshPushScheduler();
    } catch (error) {
      console.error('Failed to load canteens:', error);
      canteens.value = [];
      currentCanteenId.value = null;
      refreshPushScheduler();
    }
  };

  const setCanteen = (id: number | string) => {
    const normalizedId = Number(id);
    if (!Number.isFinite(normalizedId) || normalizedId <= 0) {
      currentCanteenId.value = null;
      if (typeof localStorage !== 'undefined') {
        localStorage.removeItem(SELECTED_CANTEEN_STORAGE_KEY);
      }
      clearSelectedCanteenCookie();
      refreshPushScheduler();
      return;
    }

    if (!canteens.value.some((canteen) => canteen.id === normalizedId && canteen.is_active)) {
      currentCanteenId.value = null;
      if (typeof localStorage !== 'undefined') {
        localStorage.removeItem(SELECTED_CANTEEN_STORAGE_KEY);
      }
      clearSelectedCanteenCookie();
      refreshPushScheduler();
      return;
    }

    currentCanteenId.value = normalizedId;

    if (typeof localStorage !== 'undefined') {
      localStorage.setItem(SELECTED_CANTEEN_STORAGE_KEY, String(normalizedId));
    }
    setSelectedCanteenCookie(normalizedId);
    refreshPushScheduler();
  };

  return { canteens, currentCanteenId, currentCanteen, fetchCanteens, setCanteen };
});
