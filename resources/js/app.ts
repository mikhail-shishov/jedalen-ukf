import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { i18n } from './i18n'
import axios from 'axios';

import App from './App.vue'
import router from './router'
import '../assets/styles/style.scss';
import { initPushScheduler } from '@/services/pushNotifications';

type RetriableAxiosConfig = {
  _csrfRetried?: boolean;
  url?: string;
};

axios.defaults.withCredentials = true;
axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';

axios.interceptors.response.use(
  (response) => response,
  async (error) => {
    const status = error?.response?.status;
    const config = (error?.config ?? {}) as RetriableAxiosConfig;

    if (status === 401) {
      localStorage.removeItem('auth_user');
    }

    if (status === 419 && !config._csrfRetried && config.url !== '/sanctum/csrf-cookie') {
      config._csrfRetried = true;
      try {
        await axios.get('/sanctum/csrf-cookie');
        return axios(config);
      } catch {
        // Let the original request error bubble up if CSRF refresh fails.
      }
    }

    return Promise.reject(error);
  }
);

axios.get('/sanctum/csrf-cookie').catch(error => {
  console.warn('[CSRF] Failed to initialize CSRF cookie:', error);
});

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.use(i18n)
app.mount('#app')

initPushScheduler();
