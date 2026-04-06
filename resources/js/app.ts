import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { i18n } from './i18n'
import axios from 'axios';

import App from './App.vue'
import router from './router'
import '../assets/styles/style.scss';
import { initPushScheduler } from '@/services/pushNotifications';

axios.defaults.withCredentials = true;
axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';

axios.get('/sanctum/csrf-cookie').catch(error => {
  console.warn('[CSRF] Failed to initialize CSRF cookie:', error);
});

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.use(i18n)
app.mount('#app')

initPushScheduler();
