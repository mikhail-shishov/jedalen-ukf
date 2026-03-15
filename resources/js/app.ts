import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { i18n } from './i18n'
import axios from 'axios';

import App from './App.vue'
import router from './router'
import '../assets/styles/style.scss';

axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.use(i18n)
app.mount('#app')
