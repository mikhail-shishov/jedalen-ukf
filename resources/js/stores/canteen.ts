import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export interface Canteen {
  id: number;
  name: string;
  address: string;
}

export const useCanteenStore = defineStore('canteen', () => {
  const canteens = ref<Canteen[]>([]);
  const currentCanteenId = ref<number | null>(null);

  const currentCanteen = computed<Canteen | null>(
    () => canteens.value.find((c) => c.id === currentCanteenId.value) ?? null
  );

  const fetchCanteens = async () => {
    if (canteens.value.length) return;
    const { data } = await axios.get<Canteen[]>('/api/canteens');
    canteens.value = data;
    if (data.length && currentCanteenId.value === null) {
      currentCanteenId.value = data[0].id;
    }
  };

  const setCanteen = (id: number) => {
    currentCanteenId.value = id;
  };

  return { canteens, currentCanteenId, currentCanteen, fetchCanteens, setCanteen };
});
