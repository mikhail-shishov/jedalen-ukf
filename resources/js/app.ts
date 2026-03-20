import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { i18n } from './i18n'
import axios from 'axios';

import App from './App.vue'
import router from './router'
import '../assets/styles/style.scss';

axios.defaults.withCredentials = true;

// Add CSRF token from cookie to request headers
axios.interceptors.request.use(config => {
  // Get CSRF token from cookie (set by /sanctum/csrf-cookie)
  const csrfToken = document.cookie
    .split('; ')
    .find(row => row.startsWith('XSRF-TOKEN='))
    ?.split('=')[1];

  if (csrfToken) {
    config.headers['X-CSRF-TOKEN'] = decodeURIComponent(csrfToken);
  }

  return config;
});

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.use(i18n)
app.mount('#app')
