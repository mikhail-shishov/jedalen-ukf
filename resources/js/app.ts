import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { i18n } from './i18n'
import axios from 'axios';

import App from './App.vue'
import router from './router'
import '../assets/styles/style.scss';
import { initPushScheduler } from '@/services/pushNotifications';

axios.defaults.withCredentials = true;

const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
if (csrfTokenElement) {
  const csrfToken = csrfTokenElement.getAttribute('content');
  if (csrfToken) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
  }
}

axios.interceptors.request.use(config => {
  if (!axios.defaults.headers.common['X-CSRF-TOKEN']) {
    const csrfToken = document.cookie
      .split('; ')
      .find(row => row.startsWith('XSRF-TOKEN='))
      ?.split('=')[1];

    if (csrfToken) {
      config.headers['X-CSRF-TOKEN'] = decodeURIComponent(csrfToken);
    }
  }

  return config;
});

axios.get('/sanctum/csrf-cookie').catch(error => {
  console.warn('[CSRF] Failed to initialize CSRF cookie:', error);
});

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.use(i18n)
app.mount('#app')

initPushScheduler();
