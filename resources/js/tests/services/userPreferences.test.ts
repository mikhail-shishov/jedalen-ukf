import { describe, expect, it, vi } from 'vitest';
import axios from 'axios';
import {
  emptyUserPreferences,
  fetchAllergenOptions,
  fetchUserPreferences,
  saveUserPreferences,
} from '@/services/userPreferences';

vi.mock('axios', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
  },
}));

const mockedAxios = axios as unknown as {
  get: ReturnType<typeof vi.fn>;
  post: ReturnType<typeof vi.fn>;
};

describe('userPreferences service', () => {
  it('fetchUserPreferences normalizes response shape', async () => {
    mockedAxios.get.mockResolvedValueOnce({
      data: {
        blocked_allergens: ['1', 2, -5, 'x'],
        push_enabled: 1,
        push_locale: 'ru',
      },
    });

    const result = await fetchUserPreferences();

    expect(result).toEqual({
      blocked_allergens: [1, 2],
      push_enabled: true,
      push_locale: 'ru',
    });
  });

  it('saveUserPreferences sanitizes payload and response', async () => {
    mockedAxios.post.mockResolvedValueOnce({
      data: {
        preferences: {
          blocked_allergens: [3, '4', 'bad'],
          push_enabled: 'truthy',
          push_locale: 'unknown',
        },
      },
    });

    const result = await saveUserPreferences({
      blocked_allergens: [3, 4, -1],
      push_enabled: true,
      push_locale: 'en',
    });

    expect(result).toEqual({
      blocked_allergens: [3, 4],
      push_enabled: true,
      push_locale: 'sk',
    });
  });

  it('fetchAllergenOptions filters invalid options and sorts by number', async () => {
    mockedAxios.get.mockResolvedValueOnce({
      data: [
        { id: 10, number: 3, name: 'Fish' },
        { id: 'x', number: 1, name: 'Bad' },
        { id: 11, number: 1, name: 'Milk' },
      ],
    });

    const result = await fetchAllergenOptions();

    expect(result).toEqual([
      { id: 11, number: 1, name: 'Milk' },
      { id: 10, number: 3, name: 'Fish' },
    ]);
  });

  it('emptyUserPreferences returns a safe default object', () => {
    expect(emptyUserPreferences()).toEqual({
      blocked_allergens: [],
      push_enabled: false,
      push_locale: 'sk',
    });
  });
});
