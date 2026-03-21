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
    menu: { title: 'Vyberte si jedáleň:', notice: 'Ak nevidíte niektoré položky, skontrolujte si voľbu alergénov.', more: 'Viac informácií', allergens: 'Alergény', loading: 'Načítavam menu...', empty: 'Pre tento týždeň nie je naplánované žiadne menu.' },
    payments: {
      amountTooSmall: 'Minimálna suma je 0,50 €',
      amountTooLarge: 'Maximálna suma je 1000,00 €',
      invalidAmount: 'Zadajte platnú sumu',
      invalidPrecision: 'Zadajte celé číslo alebo číslo s maximálne 2 desatinnými miestami',
      authRequired: 'Najprv sa prihláste a skúste to znova',
      sessionExpired: 'Relácia vypršala. Prihláste sa prosím znova',
      incompleteCardData: 'Vyplňte všetky požadované údaje karty',
      incompleteExpiry: 'Doplňte dátum platnosti karty',
      incompleteCvc: 'Doplňte bezpečnostný kód karty (CVC)',
      incompleteZip: 'Doplňte poštové smerovacie číslo (ZIP/PSČ)',
      cardDeclined: 'Karta bola zamietnutá. Prosím, overujte údaje alebo kontaktujte svoju banku',
      expiredCard: 'Karta vypršala. Prosím, overujte dátum expirácie',
      incorrectCvc: 'Nesprávny bezpečnostný kód (CVC)',
      approvalFailed: 'Vaša banka nepovolila túto transakciu',
      authenticationFailed: 'Autentifikácia neúspešná. Prosím, skúste znova',
      notConnected: 'Stripe nie je pripravený na spracovanie platby',
      stripeNotConfigured: 'Stripe sandbox nie je nakonfigurovaný',
      stripeInitFailed: 'Stripe sa nepodarilo inicializovať',
      paymentCreationFailed: 'Nepodarilo sa vytvoriť platbu',
      paymentIncomplete: 'Platba nebola dokončená',
      unknownStripeError: 'Pri spracovaní platby došlo k chybe',
      serverError: 'Chyba servera. Prosím, skúste neskôr',
      paymentSuccess: 'Platba bola úspešne odoslaná v sandbox režime'
    }
  },
  en: {
    auth: { login: 'Login', logout: 'Logout' },
    menu: { title: 'Choose a canteen:', notice: 'If you don\'t see some items, check your allergen settings.', more: 'More info', allergens: 'Allergens', loading: 'Loading menu...', empty: 'No menu planned for this week.' },
    payments: {
      amountTooSmall: 'Minimum amount is €0.50',
      amountTooLarge: 'Maximum amount is €1000.00',
      invalidAmount: 'Enter a valid amount',
      invalidPrecision: 'Enter a whole number or a number with at most 2 decimal places',
      authRequired: 'Please sign in first and try again',
      sessionExpired: 'Your session expired. Please sign in again',
      incompleteCardData: 'Please fill in all required card details',
      incompleteExpiry: 'Please complete your card expiry date',
      incompleteCvc: 'Please complete your card security code (CVC)',
      incompleteZip: 'Please complete your postal code (ZIP)',
      cardDeclined: 'Your card was declined. Please verify your details or contact your bank',
      expiredCard: 'Your card has expired. Please check the expiration date',
      incorrectCvc: 'Incorrect security code (CVC)',
      approvalFailed: 'Your bank did not approve this transaction',
      authenticationFailed: 'Authentication failed. Please try again',
      notConnected: 'Stripe is not ready to process payments',
      stripeNotConfigured: 'Stripe sandbox is not configured',
      stripeInitFailed: 'Failed to initialize Stripe',
      paymentCreationFailed: 'Failed to create payment',
      paymentIncomplete: 'Payment was not completed',
      unknownStripeError: 'An error occurred while processing the payment',
      serverError: 'Server error. Please try again later',
      paymentSuccess: 'Payment was successfully sent in sandbox mode'
    }
  },
  ua: {
    auth: { login: 'Увійти', logout: 'Вийти' },
    menu: { title: 'Оберіть їдальню:', notice: 'Якщо ви не бачите деяких страв, перевірте налаштування алергенів.', more: 'Детальніше', allergens: 'Алергени', loading: 'Завантаження меню...', empty: 'На цей тиждень меню не заплановане.' },
    payments: {
      amountTooSmall: 'Мінімальна сума - 0,50 €',
      amountTooLarge: 'Максимальна сума - 1000,00 €',
      invalidAmount: 'Введіть дійсну суму',
      invalidPrecision: 'Введіть ціле число або число максимум з 2 знаками після коми',
      authRequired: 'Спочатку увійдіть у систему і спробуйте ще раз',
      sessionExpired: 'Сесію завершено. Будь ласка, увійдіть знову',
      incompleteCardData: 'Заповніть усі необхідні дані карти',
      incompleteExpiry: 'Заповніть термін дії картки',
      incompleteCvc: 'Заповніть код безпеки картки (CVC)',
      incompleteZip: 'Заповніть поштовий індекс (ZIP)',
      cardDeclined: 'Карта відхилена. Будь ласка, перевірте дані або зв\'яжіться з вашим банком',
      expiredCard: 'Ваша карта закінчилась. Будь ласка, перевірте дату закінчення',
      incorrectCvc: 'Неправильний код безпеки (CVC)',
      approvalFailed: 'Ваш банк не схвалив цю транзакцію',
      authenticationFailed: 'Автентифікація не вдалась. Будь ласка, спробуйте ще раз',
      notConnected: 'Stripe готовий до обробки платежів',
      stripeNotConfigured: 'Stripe sandbox не налаштований',
      stripeInitFailed: 'Не вдалося ініціалізувати Stripe',
      paymentCreationFailed: 'Не вдалося створити платіж',
      paymentIncomplete: 'Платіж не завершено',
      unknownStripeError: 'Помилка при обробці платежу',
      serverError: 'Помилка сервера. Спробуйте пізніше',
      paymentSuccess: 'Платіж успішно відправлено в режимі sandbox'
    }
  },
  ru: {
    auth: { login: 'Войти', logout: 'Выйти' },
    menu: { title: 'Выберите столовую:', notice: 'Если вы не видите некоторые позиции, проверьте выбор аллергенов.', more: 'Подробнее', allergens: 'Аллергены', loading: 'Загрузка меню...', empty: 'На эту неделю меню не запланировано.' },
    payments: {
      amountTooSmall: 'Минимальная сумма - 0,50 €',
      amountTooLarge: 'Максимальная сумма - 1000,00 €',
      invalidAmount: 'Введите корректную сумму',
      invalidPrecision: 'Введите целое число или число максимум с 2 знаками после запятой',
      authRequired: 'Сначала войдите в систему и попробуйте снова',
      sessionExpired: 'Сессия истекла. Пожалуйста, войдите снова',
      incompleteCardData: 'Заполните все необходимые данные карты',
      incompleteExpiry: 'Заполните срок действия карты',
      incompleteCvc: 'Заполните код безопасности карты (CVC)',
      incompleteZip: 'Заполните почтовый индекс (ZIP)',
      cardDeclined: 'Карта отклонена. Пожалуйста, проверьте данные или свяжитесь с вашим банком',
      expiredCard: 'Ваша карта истекла. Пожалуйста, проверьте срок действия',
      incorrectCvc: 'Неверный код безопасности (CVC)',
      approvalFailed: 'Ваш банк не одобрил эту транзакцию',
      authenticationFailed: 'Ошибка аутентификации. Пожалуйста, попробуйте снова',
      notConnected: 'Stripe не готов к обработке платежей',
      stripeNotConfigured: 'Stripe sandbox не настроен',
      stripeInitFailed: 'Не удалось инициализировать Stripe',
      paymentCreationFailed: 'Не удалось создать платёж',
      paymentIncomplete: 'Платёж не завершён',
      unknownStripeError: 'Ошибка при обработке платежа',
      serverError: 'Ошибка сервера. Пожалуйста, попробуйте позже',
      paymentSuccess: 'Платёж успешно отправлен в режиме песочницы'
    }
  }
};

export const i18n = createI18n({
  legacy: false,
  locale: resolveInitialLocale(),
  fallbackLocale: 'en',
  messages,
});
