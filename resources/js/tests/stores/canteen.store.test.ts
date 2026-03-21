import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import axios from 'axios';
import { useCanteenStore } from '@/stores/canteen';

vi.mock('axios', () => ({
  default: {
    get: vi.fn(),
  },
}));

const mockedAxios = axios as unknown as {
  get: ReturnType<typeof vi.fn>;
};

describe('canteen store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('fetchCanteens normalizes payload and selects default canteen', async () => {
    mockedAxios.get.mockResolvedValueOnce({
      data: [
        'Jedalen A',
        { id: 2, name: 'Jedalen B', address: 'Street 2' },
        { id: 0, name: '' },
      ],
    });

    const store = useCanteenStore();
    await store.fetchCanteens();

    expect(store.canteens).toHaveLength(2);
    expect(store.canteens[0]).toMatchObject({ id: 1, name: 'Jedalen A' });
    expect(store.currentCanteenId).toBe(1);
    expect(store.currentCanteen?.name).toBe('Jedalen A');
  });

  it('setCanteen ignores invalid values', () => {
    const store = useCanteenStore();

    store.setCanteen(-10);
    expect(store.currentCanteenId).toBeNull();

    store.setCanteen('abc');
    expect(store.currentCanteenId).toBeNull();

    store.setCanteen('5');
    expect(store.currentCanteenId).toBe(5);
  });

  it('fetchCanteens resets state on request failure', async () => {
    mockedAxios.get.mockRejectedValueOnce(new Error('network'));

    const store = useCanteenStore();
    store.currentCanteenId = 7;

    await store.fetchCanteens();

    expect(store.canteens).toEqual([]);
    expect(store.currentCanteenId).toBeNull();
  });
});
