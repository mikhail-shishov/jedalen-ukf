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
  account_balance?: number;
}

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
        this.user = response.data;
        this.isLoggedIn = true;
        console.log('[Auth] User fetched:', this.user);
        localStorage.setItem('auth_user', JSON.stringify(this.user));
      } catch (error) {
        console.log('[Auth] Fetch user failed, checking localStorage:', error);
        const cached = localStorage.getItem('auth_user');
        if (cached) {
          try {
            this.user = JSON.parse(cached);
            this.isLoggedIn = true;
            console.log('[Auth] Restored from localStorage');
          } catch (parseError) {
            this.user = null;
            this.isLoggedIn = false;
            localStorage.removeItem('auth_user');
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
        this.user = response.data.user as User;
        this.isLoggedIn = true;
        localStorage.setItem('auth_user', JSON.stringify(this.user));
        console.log('[Auth] User logged in:', this.user);
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
        localStorage.removeItem('auth_user');
        console.log('[Auth] User logged out');
        window.location.href = '/';
      } catch (error) {
        console.error('[Auth] Logout error:', error);
        this.user = null;
        this.isLoggedIn = false;
        localStorage.removeItem('auth_user');
        window.location.href = '/';
      }
    },

    initializeAuth() {
      const cached = localStorage.getItem('auth_user');
      if (cached) {
        try {
          this.user = JSON.parse(cached);
          this.isLoggedIn = true;
          console.log('[Auth] Initialized from cache');
        } catch (parseError) {
          localStorage.removeItem('auth_user');
        }
      }
      this.fetchUser();
    }
  }
});
