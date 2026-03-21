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
    header: {
      languages: 'Jazyky',
      statistics: 'Statistiky',
      accountBalance: 'Stav uctu',
      addMoney: 'Pridat peniaze',
      settings: 'Nastavenia',
      logout: 'Odhlasit sa',
    },
    menu: { title: 'Vyberte si jedáleň:', notice: 'Ak nevidíte niektoré položky, skontrolujte si voľbu alergénov.', more: 'Viac informácií', allergens: 'Alergény', contains: 'Obsahuje', loading: 'Načítavam menu...', empty: 'Pre tento týždeň nie je naplánované žiadne menu.' },
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
    },
    paymentThankYou: {
      title: 'Platba bola prijatá. Ďakujeme!',
      subtitlePrefix: 'Stav je možné sledovať na podstránke s',
      subtitleLink: 'históriou platieb',
      balanceLabel: 'Aktuálny stav účtu:',
      backHome: 'Späť na hlavnú'
    },
    history: {
      title: 'História platieb',
      addMoney: 'Pridať peniazi',
      balanceLabel: 'Na účte je',
      dateTime: 'Dátum a čas',
      status: 'Stav',
      method: 'Metóda',
      amount: 'Suma',
      empty: 'Žiadne platby zatiaľ neboli zaznamenané.',
      loadMore: 'Načítať viac',
      loading: 'Načítavam...',
      statuses: {
        completed: 'Zaplatená',
        pending: 'Čakajúca',
        failed: 'Neúspešná'
      },
      methods: {
        creditCard: 'Platobná karta',
        adminManual: 'Manuálna úprava',
        bankTransfer: 'Bankový prevod',
        unknown: 'Neznáma metóda'
      }
    },
    settings: {
      title: 'Nastavenia',
      language: 'Jazyk',
      push: 'Push-notifications',
      pushEnabled: 'Push upozornenia sú povolene. Pre odvolanie zmeňte povolenie v nastaveniach svojho prehliadače',
      pushDenied: 'Povolenie upozorneni bolo zamietnute v prehliadači',
      pushUnsupported: 'Tento prehliadač nepodporuje push upozornenia',
      allergensTitle: 'Alergeny a preferencie',
      allergensSubtitle: 'Vyberte si, ktoré jedlo nemáte radi, a nebude sa zobrazovať v zozname jedál.',
      blockedHint: 'Zakaz zobrazovania, pre odvolanie stlac znovu',
      allergenNames: {
        0: 'Maso',
        1: 'Obilniny obsahujuce lepok',
        2: 'Korovce a vyrobky z nich',
        3: 'Vajcia a vyrobky z nich',
        4: 'Ryby a vyrobky z nich',
        5: 'Arasidy a vyrobky z nich',
        6: 'Sojove zrná a vyrobky z nich',
        7: 'Mlieko a vyrobky z neho',
        8: 'Orechy a vyrobky z nich',
        9: 'Zeler a vyrobky z neho',
        10: 'Horcica a vyrobky z nej',
        11: 'Sezamove semena a vyrobky z nich',
        12: 'Oxid siricity a siricitany',
        13: 'Vlci bob a vyrobky z neho',
        14: 'Makkyse a vyrobky z nich'
      }
    },
    notifications: {
      open: {
        title: 'Jedalen je otvorena',
        body: 'Je 11:30, jedalen je otvorena.'
      },
      closing: {
        title: 'Jedalen sa skoro zatvori',
        body: 'Je 13:00, jedalen sa skoro zatvori.'
      }
    },
  },
  en: {
    auth: { login: 'Login', logout: 'Logout' },
    header: {
      languages: 'Languages',
      statistics: 'Statistics',
      accountBalance: 'Balance',
      addMoney: 'Add money',
      settings: 'Settings',
      logout: 'Log out',
    },
    menu: { title: 'Choose a canteen:', notice: 'If you don\'t see some items, check your allergen settings.', more: 'More info', allergens: 'Allergens', contains: 'Contains', loading: 'Loading menu...', empty: 'No menu planned for this week.' },
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
    },
    paymentThankYou: {
      title: 'Payment was accepted. Thank you!',
      subtitlePrefix: 'You can track the status on the page with',
      subtitleLink: 'payment history',
      balanceLabel: 'Current account balance:',
      backHome: 'Back to homepage'
    },
    history: {
      title: 'Payment history',
      addMoney: 'Add money',
      balanceLabel: 'Balance is',
      dateTime: 'Date and time',
      status: 'Status',
      method: 'Method',
      amount: 'Amount',
      empty: 'No payments have been recorded yet.',
      loadMore: 'Load more',
      loading: 'Loading...',
      statuses: {
        completed: 'Completed',
        pending: 'Pending',
        failed: 'Failed'
      },
      methods: {
        creditCard: 'Credit Card',
        adminManual: 'Admin Manual',
        bankTransfer: 'Bank Transfer',
        unknown: 'Unknown method'
      }
    },
    settings: {
      title: 'Settings',
      language: 'Language',
      push: 'Push notifications',
      pushEnabled: 'Push notifications are enabled. If you ever want to disable them, you can do so in your browser settings',
      pushDenied: 'Browser permission for notifications was denied',
      pushUnsupported: 'This browser does not support push notifications',
      allergensTitle: 'Allergens and preferences',
      allergensSubtitle: 'Select foods you dislike and they will be hidden from the menu list.',
      blockedHint: 'Display blocked, tap again to allow',
      allergenNames: {
        0: 'Meat',
        1: 'Cereals containing gluten',
        2: 'Crustaceans and products thereof',
        3: 'Eggs and products thereof',
        4: 'Fish and products thereof',
        5: 'Peanuts and products thereof',
        6: 'Soybeans and products thereof',
        7: 'Milk and products thereof',
        8: 'Nuts and products thereof',
        9: 'Celery and products thereof',
        10: 'Mustard and products thereof',
        11: 'Sesame seeds and products thereof',
        12: 'Sulphur dioxide and sulphites',
        13: 'Lupin and products thereof',
        14: 'Molluscs and products thereof'
      }
    },
    notifications: {
      open: {
        title: 'Canteen is open',
        body: 'It is 11:30, the canteen is open now.'
      },
      closing: {
        title: 'Canteen will close soon',
        body: 'It is 13:00, the canteen will close soon.'
      }
    },
  },
  ua: {
    auth: { login: 'Увійти', logout: 'Вийти' },
    header: {
      languages: 'Мови',
      statistics: 'Статистика',
      accountBalance: 'Баланс рахунку',
      addMoney: 'Додати гроші',
      settings: 'Налаштування',
      logout: 'Вийти',
    },
    menu: { title: 'Оберіть їдальню:', notice: 'Якщо ви не бачите деяких страв, перевірте налаштування алергенів.', more: 'Детальніше', allergens: 'Алергени', contains: 'Містить', loading: 'Завантаження меню...', empty: 'На цей тиждень меню не заплановане.' },
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
    },
    paymentThankYou: {
      title: 'Платіж прийнято. Дякуємо!',
      subtitlePrefix: 'Статус можна відстежувати на сторінці з',
      subtitleLink: 'історією платежів',
      balanceLabel: 'Поточний баланс рахунку:',
      backHome: 'Повернутися на головну'
    },
    history: {
      title: 'Історія платежів',
      addMoney: 'Додати гроші',
      balanceLabel: 'На рахунку',
      dateTime: 'Дата та час',
      status: 'Статус',
      method: 'Метод',
      amount: 'Сума',
      empty: 'Платежі ще не зафіксовані.',
      loadMore: 'Завантажити ще',
      loading: 'Завантаження...',
      statuses: {
        completed: 'Оплачено',
        pending: 'Очікується',
        failed: 'Неуспішно'
      },
      methods: {
        creditCard: 'Банківська картка',
        adminManual: 'Ручне коригування',
        bankTransfer: 'Банківський переказ',
        unknown: 'Невідомий метод'
      }
    },
    settings: {
      title: 'Налаштування',
      language: 'Мова',
      push: 'Push-сповіщення',
      pushEnabled: 'Push-сповіщення увімкнено. Якщо ви захочете їх вимкнути, це можна зробити в налаштуваннях вашого браузера',
      pushDenied: 'Дозвіл на сповіщення було відхилено у браузері',
      pushUnsupported: 'Цей браузер не підтримує push-сповіщення',
      allergensTitle: 'Алергени та вподобання',
      allergensSubtitle: 'Оберіть страви, які вам не підходять, і вони не будуть показуватися у меню.',
      blockedHint: 'Показ заборонено, натисніть ще раз для скасування',
      allergenNames: {
        0: 'Мʼясо',
        1: 'Злаки, що містять глютен',
        2: 'Ракоподібні та продукти з них',
        3: 'Яйця та продукти з них',
        4: 'Риба та продукти з неї',
        5: 'Арахіс та продукти з нього',
        6: 'Соя та продукти з неї',
        7: 'Молоко та продукти з нього',
        8: 'Горіхи та продукти з них',
        9: 'Селера та продукти з неї',
        10: 'Гірчиця та продукти з неї',
        11: 'Кунжут та продукти з нього',
        12: 'Діоксид сірки та сульфіти',
        13: 'Люпин та продукти з нього',
        14: 'Молюски та продукти з них'
      }
    },
    notifications: {
      open: {
        title: 'Їдальня відкрита',
        body: 'Зараз 11:30, їдальня відкрита.'
      },
      closing: {
        title: 'Їдальня скоро закриється',
        body: 'Зараз 13:00, їдальня скоро закриється.'
      }
    },
  },
  ru: {
    auth: { login: 'Войти', logout: 'Выйти' },
    header: {
      languages: 'Языки',
      statistics: 'Статистика',
      accountBalance: 'Баланс счета',
      addMoney: 'Добавить деньги',
      settings: 'Настройки',
      logout: 'Выйти',
    },
    menu: { title: 'Выберите столовую:', notice: 'Если вы не видите некоторые позиции, проверьте выбор аллергенов.', more: 'Подробнее', allergens: 'Аллергены', contains: 'Содержит', loading: 'Загрузка меню...', empty: 'На эту неделю меню не запланировано.' },
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
    },
    paymentThankYou: {
      title: 'Платеж принят. Спасибо!',
      subtitlePrefix: 'Статус можно отслеживать на странице с',
      subtitleLink: 'историей платежей',
      balanceLabel: 'Текущий баланс счета:',
      backHome: 'Назад на главную'
    },
    history: {
      title: 'История платежей',
      addMoney: 'Добавить деньги',
      balanceLabel: 'На счете',
      dateTime: 'Дата и время',
      status: 'Статус',
      method: 'Метод',
      amount: 'Сумма',
      empty: 'Платежи пока не зафиксированы.',
      loadMore: 'Показать еще',
      loading: 'Загрузка...',
      statuses: {
        completed: 'Оплачено',
        pending: 'Ожидает',
        failed: 'Неуспешно'
      },
      methods: {
        creditCard: 'Банковская карта',
        adminManual: 'Ручное изменение',
        bankTransfer: 'Банковский перевод',
        unknown: 'Неизвестный метод'
      }
    },
    settings: {
      title: 'Настройки',
      language: 'Язык',
      push: 'Push-уведомления',
      pushEnabled: 'Push-уведомления включены. Если вы захотите их выключить, это можно сделать в настройках вашего браузера',
      pushDenied: 'Разрешение на уведомления отклонено в браузере',
      pushUnsupported: 'Этот браузер не поддерживает push-уведомления',
      allergensTitle: 'Аллергены и предпочтения',
      allergensSubtitle: 'Выберите блюда, которые вам не подходят, и они не будут отображаться в списке меню.',
      blockedHint: 'Показ запрещен, нажмите снова для отмены',
      allergenNames: {
        0: 'Мясо',
        1: 'Злаки, содержащие глютен',
        2: 'Ракообразные и продукты из них',
        3: 'Яйца и продукты из них',
        4: 'Рыба и продукты из нее',
        5: 'Арахис и продукты из него',
        6: 'Соя и продукты из нее',
        7: 'Молоко и продукты из него',
        8: 'Орехи и продукты из них',
        9: 'Сельдерей и продукты из него',
        10: 'Горчица и продукты из нее',
        11: 'Кунжут и продукты из него',
        12: 'Диоксид серы и сульфиты',
        13: 'Люпин и продукты из него',
        14: 'Моллюски и продукты из них'
      }
    },
    notifications: {
      open: {
        title: 'Столовая открыта',
        body: 'Сейчас 11:30, столовая открыта.'
      },
      closing: {
        title: 'Столовая скоро закроется',
        body: 'Сейчас 13:00, столовая скоро закроется.'
      }
    },
  }
};

export const i18n = createI18n({
  legacy: false,
  locale: resolveInitialLocale(),
  fallbackLocale: 'en',
  messages,
});
