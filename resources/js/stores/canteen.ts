import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export interface Canteen {
  id: number;
  name: string;
  address: string;
}

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
        };
      }

      if (item && typeof item === 'object') {
        const record = item as Record<string, unknown>;
        const id = Number(record.id);
        const name = String(record.name ?? '').trim();
        const address = String(record.address ?? '').trim();

        if (Number.isFinite(id) && id > 0 && name.length > 0) {
          return { id, name, address };
        }
      }

      return null;
    })
    .filter((item): item is Canteen => item !== null);
};

export const useCanteenStore = defineStore('canteen', () => {
  const canteens = ref<Canteen[]>([]);
  const currentCanteenId = ref<number | null>(null);

  const currentCanteen = computed<Canteen | null>(
    () => canteens.value.find((c) => c.id === currentCanteenId.value) ?? null
  );

  const fetchCanteens = async () => {
    if (canteens.value.length) return;

    try {
      const { data } = await axios.get('/api/canteens');
      const normalized = normalizeCanteens(data);

      canteens.value = normalized;

      if (currentCanteenId.value !== null) {
        currentCanteenId.value = Number(currentCanteenId.value);
      }

      if (!normalized.some((c) => c.id === currentCanteenId.value)) {
        currentCanteenId.value = normalized.length ? normalized[0].id : null;
      }
    } catch (error) {
      console.error('Failed to load canteens:', error);
      canteens.value = [];
      currentCanteenId.value = null;
    }
  };

  const setCanteen = (id: number | string) => {
    const normalizedId = Number(id);
    if (!Number.isFinite(normalizedId) || normalizedId <= 0) {
      return;
    }

    currentCanteenId.value = normalizedId;
  };

  return { canteens, currentCanteenId, currentCanteen, fetchCanteens, setCanteen };
});
