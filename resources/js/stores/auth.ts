import { defineStore } from 'pinia';
import axios from 'axios';

interface User {
  id: number;
  name: string;
  email: string;
  is_admin: boolean;
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
      } catch (error) {
        this.user = null;
        this.isLoggedIn = false;
      } finally {
        this.isLoading = false;
      }
    },

    async logout() {
      try {
        await axios.post('/logout');
        this.user = null;
        this.isLoggedIn = false;
        window.location.href = '/';
      } catch (error) {
        console.error('Logout failed:', error);
      }
    }
  }
});