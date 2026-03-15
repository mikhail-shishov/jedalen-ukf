import { createI18n } from 'vue-i18n';

const LOCALE_STORAGE_KEY = 'preferred_locale';

const resolveInitialLocale = () => {
  const supported = ['sk', 'en', 'ua', 'ru'];
  const saved = localStorage.getItem(LOCALE_STORAGE_KEY);
  if (saved && supported.includes(saved)) {
    return saved;
  }
  return 'sk';
};

const messages = {
  sk: {
    auth: { login: 'Prihlásiť', logout: 'Odhlásiť' },
    menu: { title: 'Vyberte si jedáleň:', notice: 'Ak nevidíte niektoré položky, skontrolujte si voľbu alergénov.', more: 'Viac informácií', allergens: 'Alergény', loading: 'Načítavam menu...', empty: 'Pre tento týždeň nie je naplánované žiadne menu.' }
  },
  en: {
    auth: { login: 'Login', logout: 'Logout' },
    menu: { title: 'Choose a canteen:', notice: 'If you don\'t see some items, check your allergen settings.', more: 'More info', allergens: 'Allergens', loading: 'Loading menu...', empty: 'No menu planned for this week.' }
  },
  ua: {
    auth: { login: 'Увійти', logout: 'Вийти' },
    menu: { title: 'Оберіть їдальню:', notice: 'Якщо ви не бачите деяких страв, перевірте налаштування алергенів.', more: 'Детальніше', allergens: 'Алергени', loading: 'Завантаження меню...', empty: 'На цей тиждень меню не заплановане.' }
  },
  ru: {
    auth: { login: 'Войти', logout: 'Выйти' },
    menu: { title: 'Выберите столовую:', notice: 'Если вы не видите некоторые позиции, проверьте выбор аллергенов.', more: 'Подробнее', allergens: 'Аллергены', loading: 'Загрузка меню...', empty: 'На эту неделю меню не запланировано.' }
  }
};

export const i18n = createI18n({
  legacy: false,
  locale: resolveInitialLocale(),
  fallbackLocale: 'en',
  messages,
});
