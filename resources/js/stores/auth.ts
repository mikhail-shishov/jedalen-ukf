import { defineStore } from 'pinia';
import axios from 'axios';

interface User {
  id: number;
  name: string;
  first_name?: string;
  last_name?: string;
  email: string;
  is_admin: boolean;
  role_id?: number;
  credit_balance?: number;
}

const normalizeUser = (rawUser: any): User => ({
  ...rawUser,
  credit_balance: Number(rawUser?.credit_balance ?? 0),
});

const AUTH_USER_STORAGE_KEY = 'auth_user';

const saveAuthUser = (user: User) => {
  localStorage.setItem(AUTH_USER_STORAGE_KEY, JSON.stringify(user));
};

const clearAuthUser = () => {
  localStorage.removeItem(AUTH_USER_STORAGE_KEY);
};

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null as User | null,
    isLoggedIn: false,
    isLoading: true
  }),

  actions: {
    async fetchUser() {
      this.isLoading = true;
      try {
        const response = await axios.get('/api/user');
        this.user = normalizeUser(response.data);
        this.isLoggedIn = true;
        console.log('[Auth] User fetched:', this.user);
        saveAuthUser(this.user);
      } catch (error: any) {
        const statusCode = error?.response?.status;

        if (statusCode === 401) {
          this.user = null;
          this.isLoggedIn = false;
          clearAuthUser();
          return;
        }

        console.log('[Auth] Fetch user failed, trying local cache:', error);
        const cached = localStorage.getItem('auth_user');
        if (cached) {
          try {
            this.user = normalizeUser(JSON.parse(cached));
            this.isLoggedIn = true;
            console.log('[Auth] Restored from localStorage');
          } catch (parseError) {
            this.user = null;
            this.isLoggedIn = false;
            clearAuthUser();
          }
        } else {
          this.user = null;
          this.isLoggedIn = false;
        }
      } finally {
        this.isLoading = false;
      }
    },

    async login(loginId: string, password: string): Promise<{ ok: boolean; message?: string }> {
      try {
        await axios.get('/sanctum/csrf-cookie');
        const response = await axios.post('/auth/login', {
          login_id: loginId,
          password,
        }, {
          headers: { Accept: 'application/json' },
        });
        this.user = normalizeUser(response.data.user);
        this.isLoggedIn = true;
        saveAuthUser(this.user);
        console.log('[Auth] User logged in:', this.user);
        await axios.get('/sanctum/csrf-cookie');
        await this.fetchUser();
        return { ok: true };
      } catch (error: any) {
        const message = error.response?.data?.message || 'Chyba servera.';
        console.log('[Auth] Login failed:', message);
        return { ok: false, message };
      }
    },

    async logout() {
      try {
        await axios.post('/logout');
        this.user = null;
        this.isLoggedIn = false;
        clearAuthUser();
        console.log('[Auth] User logged out');
        window.location.href = '/';
      } catch (error) {
        console.error('[Auth] Logout error:', error);
        this.user = null;
        this.isLoggedIn = false;
        clearAuthUser();
        window.location.href = '/';
      }
    },

    async initializeAuth() {
      try {
        await axios.get('/sanctum/csrf-cookie');
      } catch (error) {
        console.log('[Auth] CSRF cookie init failed, continuing...');
      }

      try {
        const response = await axios.get('/api/user');
        this.user = normalizeUser(response.data);
        this.isLoggedIn = true;
        this.isLoading = false;
        saveAuthUser(this.user);
        return;
      } catch (error: any) {
        const statusCode = error?.response?.status;

        if (statusCode === 401) {
          clearAuthUser();
          this.user = null;
          this.isLoggedIn = false;
          this.isLoading = false;
          return;
        }

        console.log('[Auth] Server auth check failed, trying local cache...');
      }

      const cached = localStorage.getItem('auth_user');
      if (!cached) {
        this.user = null;
        this.isLoggedIn = false;
        this.isLoading = false;
        return;
      }

      try {
        this.user = normalizeUser(JSON.parse(cached));
        this.isLoggedIn = true;
        console.log('[Auth] Initialized from cache');
      } catch (parseError) {
        clearAuthUser();
        this.user = null;
        this.isLoggedIn = false;
        this.isLoading = false;
        return;
      }

      this.isLoading = false;
    }
  }
});
