import { createI18n } from 'vue-i18n';

const messages = {
  sk: {
    auth: { login: 'Prihlásiť', logout: 'Odhlásiť' },
    menu: { title: 'Vyberte si jedáleň:', notice: 'Ak nevidíte niektoré položky, skontrolujte si voľbu alergénov.', more: 'Viac informácií' }
  },
  en: {
    auth: { login: 'Login', logout: 'Logout' },
    menu: { title: 'Choose a canteen:', notice: 'If you don\'t see some items, check your allergen settings.', more: 'More info' }
  },
  ua: {
    auth: { login: 'Увійти', logout: 'Вийти' },
    menu: { title: 'Оберіть їдальню:', notice: 'Якщо ви не бачите деяких страв, перевірте налаштування алергенів.', more: 'Детальніше' }
  },
  ru: {
    auth: { login: 'Войти', logout: 'Выйти' },
    menu: { title: 'Выберите столовую:', notice: 'Если вы не видите некоторые позиции, проверьте выбор аллергенов.', more: 'Подробнее' }
  }
};

export const i18n = createI18n({
  legacy: false,
  locale: 'sk',
  fallbackLocale: 'en',
  messages,
});