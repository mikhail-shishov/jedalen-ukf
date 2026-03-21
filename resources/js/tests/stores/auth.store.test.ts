import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';

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

describe('auth store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('fetchUser stores normalized user and cache on success', async () => {
    mockedAxios.get.mockResolvedValueOnce({
      data: {
        id: 1,
        name: 'Test User',
        email: 'test@example.com',
        is_admin: false,
        credit_balance: '12.50',
      },
    });

    const store = useAuthStore();
    await store.fetchUser();

    expect(store.isLoggedIn).toBe(true);
    expect(store.user?.credit_balance).toBe(12.5);
    expect(localStorage.getItem('auth_user')).toContain('"credit_balance":12.5');
  });

  it('fetchUser clears auth state on 401', async () => {
    localStorage.setItem('auth_user', JSON.stringify({ id: 9, name: 'Cached' }));

    mockedAxios.get.mockRejectedValueOnce({ response: { status: 401 } });

    const store = useAuthStore();
    store.user = {
      id: 9,
      name: 'Cached',
      email: 'cached@example.com',
      is_admin: false,
      credit_balance: 5,
    };
    store.isLoggedIn = true;

    await store.fetchUser();

    expect(store.user).toBeNull();
    expect(store.isLoggedIn).toBe(false);
    expect(localStorage.getItem('auth_user')).toBeNull();
  });

  it('login returns backend message on error', async () => {
    mockedAxios.get.mockResolvedValueOnce({});
    mockedAxios.post.mockRejectedValueOnce({
      response: { data: { message: 'Too many attempts' } },
    });

    const store = useAuthStore();
    const result = await store.login('user01', 'bad-password');

    expect(result).toEqual({ ok: false, message: 'Too many attempts' });
  });

  it('initializeAuth restores cached user then refreshes from API', async () => {
    localStorage.setItem(
      'auth_user',
      JSON.stringify({
        id: 3,
        name: 'Cached User',
        email: 'cached@example.com',
        is_admin: false,
        credit_balance: '7.00',
      }),
    );

    mockedAxios.get
      .mockResolvedValueOnce({})
      .mockResolvedValueOnce({
        data: {
          id: 3,
          name: 'Fresh User',
          email: 'fresh@example.com',
          is_admin: false,
          credit_balance: '8.25',
        },
      });

    const store = useAuthStore();
    await store.initializeAuth();

    expect(store.isLoggedIn).toBe(true);
    expect(store.user?.name).toBe('Fresh User');
    expect(store.user?.credit_balance).toBe(8.25);
    expect(store.isLoading).toBe(false);
  });
});
