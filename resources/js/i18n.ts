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
      profile: 'Profil',
      statistics: 'Statistiky',
      orders: 'Objednavky',
      accountBalance: 'Stav uctu',
      addMoney: 'Pridat peniaze',
      settings: 'Nastavenia',
      menu: 'Menu',
      logout: 'Odhlasit sa',
    },
    menu: { title: 'Vyberte si jedáleň:', notice: 'Ak nevidíte niektoré položky, skontrolujte si voľbu alergénov v nastaveniach.', more: 'Viac informácií', allergens: 'Alergény', contains: 'Obsahuje', loading: 'Načítavam menu...', empty: 'V najbližších dňoch nie je naplánované žiadne menu.', order: 'Objednať', cancelOrder: 'Zrušiť objednávku', insufficientBalance: 'Nie je dosť kreditu', orderClosed: 'Objednávanie uzavreté', badgeTemplate: 'Obed M{number}', aiNote: 'Obrázok môže byť generovaný umelou inteligenciou a môže zodpovedať realite len čiastočne alebo vôbec. Ilustrácia s červeným podnosom je uvedená iba v prípade, že nie je k dispozícii žiadny obrázok, a nezobrazuje skutočné jedlo.', exchange: 'Burza jedal', exchangeLoadError: 'Chyba pri načítaní burzy.', exchangeEmpty: 'Na burze momentálne žiadne ponuky nie sú.', buy: 'Kúpiť', backToCanteen: 'Späť do zoznámu' },
    payments: {
      title: 'Platby',
      historyButton: 'História platieb',
      balanceLabel: 'Na účte je',
      cardPaymentTitle: 'Online platba kartou',
      amountLabel: 'Suma v €',
      payButton: 'Zaplatiť',
      processing: 'Spracovanie...',
      bankTransferTitle: 'Bankový prevod',
      clientLine: 'Klient: {clientName}',
      accountNameLine: 'Názov účtu: {accountName}',
      accountLine: 'číslo účtu: {accountNumber}, banka {bankName}',
      ibanLine: 'IBAN: {iban}',
      variableSymbolTitle: 'Variabilný symbol (VS)',
      employeesTitle: 'Zamestnanci',
      employeesVsHint: 'VS zamestnanca je uvedený na stránke UKF v časti osobných údajov',
      studentsTitle: 'Študenti a doktorandi',
      studentsVsHint: 'ID z preukazu študenta (5-miestne alebo 6-miestne osobné číslo študenta).',
      transferNotice: 'Prevod medzi účtom platiteľa a účtom dodávateľa nie je realizovaný on-line, preto je potrebné počítať najmenej s 3 pracovnými dňami.',
      bottomInfoLine1: 'O vrátenie nevyčerpaného zostatku na kredite za stravovanie môžete požiadať e-mailom na adrese: {refundEmail}.',
      bottomInfoLine2: 'Nezabudnite, prosím, uviesť meno, priezvisko a číslo účtu v tvare IBAN, na ktorý požadujete sumu vrátiť.',
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
    ordersPage: {
      title: 'Objednavky',
      currentTitle: 'Aktualne objednavky',
      historyTitle: 'Historia objednavok',
      date: 'Na datum',
      orderedAt: 'Objednane',
      meal: 'Jedlo',
      canteen: 'Jedalen',
      status: 'Stav',
      price: 'Cena',
      emptyCurrent: 'Aktualne nemate ziadne aktivne objednavky.',
      emptyHistory: 'Historia objednavok je zatial prazdna.',
      loadMore: 'Nacitat viac',
      loading: 'Nacitavam...',
      statuses: {
        ordered: 'Objednane',
        in_exchange: 'Na burze',
        cancelled: 'Zrusene',
        sold: 'Predane'
      }
    },
    statistics: {
      addMoney: 'Pridať peniazi',
      empty: 'Zatiaľ nemáte žiadne údaje pre štatistiku. Urobte si svoju prvú objednávku a začnite to plniť!',
      mostOrderedLabel: 'Mám najčastejšie...',
      mostOrderedValue: '{meal}. Objednal som si to {userOrders} {userOrdersWord}.',
      peakDayLabel: 'Cítim hlad najhoršie v...',
      peakDayValue: '{day} ({orders} {ordersWord})',
      totalVisitsLabel: 'Bol/a som v jedálni...',
      totalVisitsValue: '{count} {visitsWord}'
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
        0: 'Mäso',
        1: 'Obilniny obsahujúce lepok (t.j. pšenica, raž, jačmeň, ovos, špalda, kamut alebo ich hybridne odrody)',
        2: 'Kôrovce a výrobky z nich',
        3: 'Vajcia a výrobky z nich',
        4: 'Ryby a výrobky z nich',
        5: 'Arašidy a výrobky z nich',
        6: 'Sójové zrná a výrobky z nich',
        7: 'Mlieko a výrobky z neho',
        8: 'Orechy, ktorými sú mandle, lieskové orechy, vlašské orechy, kešu, pekanové orechy, para orechy, pistácie, makadamové orechy a queenslandské orechy a výrobky z nich',
        9: 'Zeler a výrobky z neho',
        10: 'Horčica a výrobky z nej',
        11: 'Sezamové semená a výrobky z nich',
        12: 'Oxid siričitý a siričitany v koncentráciách vyšších ako 10 mg/kg alebo 10 mg/l',
        13: 'Vlčí bob a výrobky z neho',
        14: 'Mäkkýše a výrobky z nich'
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
    intro: {
      close: 'Zatvoriť',
      back: 'Späť',
      next: 'Ďalej',
      start: 'Do toho!',
      illustrationAlt: 'Ilustrácia',
      step1: 'Na prihlásenie použi svoje prihlasovacie údaje, ktoré sú rovnaké ako v AiS.',
      step2: 'Objednaj si jedlo minimálne deň vopred a príď si ho vyzdvihnúť v čase, keď je jedáleň otvorená. Ak si ho nemôžeš vyzdvihnúť, môžeš ho presunúť do burzy a možno ho niekto iný vykúpi.',
      step3: 'Históriu objednávok a svoje štatistiky môžeš sledovať vo svojom profile. Zároveň si tam môžeš nastaviť svoje chuťové preferencie a alergény. Viac informácií nájdeš v našich článkoch.'
    },
  },
  en: {
    auth: { login: 'Login', logout: 'Logout' },
    header: {
      languages: 'Languages',
      profile: 'Profile',
      statistics: 'Statistics',
      orders: 'Orders',
      accountBalance: 'Balance',
      addMoney: 'Add money',
      settings: 'Settings',
      menu: 'Menu',
      logout: 'Log out',
    },
    menu: { title: 'Choose a canteen:', notice: 'If you don\'t see some items, check your allergen settings.', more: 'More info', allergens: 'Allergens', contains: 'Contains', loading: 'Loading menu...', empty: 'No menu is planned for upcoming days.', order: 'Order', cancelOrder: 'Cancel order', insufficientBalance: 'Insufficient balance', orderClosed: 'Ordering closed', badgeTemplate: 'Lunch M{number}', aiNote: 'The image may be generated by artificial intelligence and may match reality only partially or not at all. The red tray illustration is shown only when no image is available and does not depict the actual meal.', exchange: 'Meal exchange', exchangeLoadError: 'Failed to load exchange.', exchangeEmpty: 'There are no listings on the exchange right now.', buy: 'Buy', backToCanteen: 'Back to the list' },
    payments: {
      title: 'Payments',
      historyButton: 'Payment history',
      balanceLabel: 'Balance is',
      cardPaymentTitle: 'Online card payment',
      amountLabel: 'Amount in €',
      payButton: 'Pay',
      processing: 'Processing...',
      bankTransferTitle: 'Bank transfer',
      clientLine: 'Client: {clientName}',
      accountNameLine: 'Account name: {accountName}',
      accountLine: 'Account number: {accountNumber}, held at {bankName}',
      ibanLine: 'IBAN: {iban}',
      variableSymbolTitle: 'Variable symbol (VS)',
      employeesTitle: 'Employees',
      employeesVsHint: 'Employee VS is available on the UKF website in personal data section.',
      studentsTitle: 'Students and PhD students',
      studentsVsHint: 'Student ID from student card (5-digit or 6-digit personal student number).',
      transferNotice: 'A transfer between payer and supplier accounts is not processed online, so please allow at least 3 working days.',
      bottomInfoLine1: 'To request a refund of the unused meal credit balance, please write to: {refundEmail}.',
      bottomInfoLine2: 'Please include your first name, last name, and IBAN account number where the refund should be sent.',
      amountTooSmall: 'Minimum amount is 0,50 €',
      amountTooLarge: 'Maximum amount is 1000,00 €',
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
    ordersPage: {
      title: 'Orders',
      currentTitle: 'Current orders',
      historyTitle: 'Order history',
      date: 'For',
      orderedAt: 'Ordered at',
      meal: 'Meal',
      canteen: 'Canteen',
      status: 'Status',
      price: 'Price',
      emptyCurrent: 'You do not have any active orders right now.',
      emptyHistory: 'No order history yet.',
      loadMore: 'Load more',
      loading: 'Loading...',
      statuses: {
        ordered: 'Ordered',
        in_exchange: 'In exchange',
        cancelled: 'Cancelled',
        sold: 'Sold'
      }
    },
    statistics: {
      addMoney: 'Add money',
      empty: 'No data available for statistics yet. Make your first order to start filling it up!',
      mostOrderedLabel: 'I most often...',
      mostOrderedValue: '{meal}. You ordered it {userOrders} {userOrdersWord}.',
      peakDayLabel: 'I feel hungriest on...',
      peakDayValue: '{day} ({orders} {ordersWord})',
      totalVisitsLabel: 'I have been in the canteen...',
      totalVisitsValue: '{count} {visitsWord}'
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
        1: 'Cereals containing gluten (i.e. wheat, rye, barley, oats, spelt, kamut or their hybrid strains)',
        2: 'Crustaceans and products thereof',
        3: 'Eggs and products thereof',
        4: 'Fish and products thereof',
        5: 'Peanuts and products thereof',
        6: 'Soybeans and products thereof',
        7: 'Milk and products thereof',
        8: 'Nuts, namely almonds, hazelnuts, walnuts, cashews, pecans, Brazil nuts, pistachios, macadamia nuts and Queensland nuts, and products thereof',
        9: 'Celery and products thereof',
        10: 'Mustard and products thereof',
        11: 'Sesame seeds and products thereof',
        12: 'Sulphur dioxide and sulphites at concentrations above 10 mg/kg or 10 mg/l',
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
    intro: {
      close: 'Close',
      back: 'Back',
      next: 'Next',
      start: "Let's go!",
      illustrationAlt: 'Illustration',
      step1: 'Use your AiS credentials to sign in.',
      step2: 'Order your meal at least one day in advance and pick it up while the canteen is open. If you cannot pick it up, you can move it to the exchange where another user may buy it.',
      step3: 'You can track order history and your statistics in your profile. You can also set taste preferences and allergens there. More details are available in our articles.'
    },
  },
  ua: {
    auth: { login: 'Увійти', logout: 'Вийти' },
    header: {
      languages: 'Мови',
      profile: 'Профіль',
      statistics: 'Статистика',
      orders: 'Замовлення',
      accountBalance: 'Баланс рахунку',
      addMoney: 'Додати гроші',
      settings: 'Налаштування',
      menu: 'Меню',
      logout: 'Вийти',
    },
    menu: { title: 'Оберіть їдальню:', notice: 'Якщо ви не бачите деяких страв, перевірте налаштування алергенів.', more: 'Детальніше', allergens: 'Алергени', contains: 'Містить', loading: 'Завантаження меню...', empty: 'На найближчі дні меню не заплановане.', order: 'Замовити', cancelOrder: 'Скасувати замовлення', insufficientBalance: 'Недостатньо кредиту', orderClosed: 'Замовлення закрито', badgeTemplate: 'Обід М{number}', aiNote: 'Зображення може бути згенеровано штучним інтелектом і може лише частково або зовсім не відповідати реальності. Ілюстрація з червоним підносом показана лише тоді, коли немає доступного зображення, і не відображає справжню страву.', exchange: 'Біржа обідів', exchangeLoadError: 'Помилка завантаження біржі.', exchangeEmpty: 'Зараз на біржі немає пропозицій.', buy: 'Купити', backToCanteen: 'Назад' },
    payments: {
      title: 'Платежі',
      historyButton: 'Історія платежів',
      balanceLabel: 'На рахунку',
      cardPaymentTitle: 'Онлайн-оплата карткою',
      amountLabel: 'Сума в €',
      payButton: 'Сплатити',
      processing: 'Обробка...',
      bankTransferTitle: 'Банківський переказ',
      clientLine: 'Клієнт: {clientName}',
      accountNameLine: 'Назва рахунку: {accountName}',
      accountLine: 'Номер рахунку: {accountNumber}, відкритий у {bankName}',
      ibanLine: 'IBAN: {iban}',
      variableSymbolTitle: 'Змінний символ (VS)',
      employeesTitle: 'Працівники',
      employeesVsHint: 'VS працівника вказаний на сайті UKF у розділі особистих даних.',
      studentsTitle: 'Студенти та докторанти',
      studentsVsHint: 'ID зі студентського посвідчення (5-значний або 6-значний особистий номер студента).',
      transferNotice: 'Переказ між рахунком платника та рахунком постачальника не виконується онлайн, тому потрібно врахувати щонайменше 3 робочі дні.',
      bottomInfoLine1: 'Щоб повернути невикористаний залишок кредиту на харчування, напишіть на адресу: {refundEmail}.',
      bottomInfoLine2: 'Будь ласка, вкажіть ім\'я, прізвище та номер рахунку у форматі IBAN, на який потрібно повернути кошти.',
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
    ordersPage: {
      title: 'Замовлення',
      currentTitle: 'Поточні замовлення',
      historyTitle: 'Історія замовлень',
      date: 'На дату',
      orderedAt: 'Замовлено',
      meal: 'Страва',
      canteen: 'Їдальня',
      status: 'Статус',
      price: 'Ціна',
      emptyCurrent: 'Зараз у вас немає активних замовлень.',
      emptyHistory: 'Історія замовлень поки порожня.',
      loadMore: 'Завантажити ще',
      loading: 'Завантаження...',
      statuses: {
        ordered: 'Замовлено',
        in_exchange: 'На біржі',
        cancelled: 'Скасовано',
        sold: 'Продано'
      }
    },
    statistics: {
      addMoney: 'Додати гроші',
      empty: 'Поки що немає даних для статистики. Зробіть своє перше замовлення, щоб розпочати її заповнювати!',
      mostOrderedLabel: 'Я найчастіше замовляю...',
      mostOrderedValue: '{meal}. Ви замовляли її {userOrders} {userOrdersWord}.',
      peakDayLabel: 'Я найбільше голодний/-на в...',
      peakDayValue: '{day} ({orders} {ordersWord})',
      totalVisitsLabel: 'Я був/-а в їдальні...',
      totalVisitsValue: '{count} {visitsWord}'
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
        1: 'Злаки, що містять глютен (пшениця, жито, ячмінь, овес, спельта, камут або їхні гібридні сорти)',
        2: 'Ракоподібні та продукти з них',
        3: 'Яйця та продукти з них',
        4: 'Риба та продукти з неї',
        5: 'Арахіс та продукти з нього',
        6: 'Соя та продукти з неї',
        7: 'Молоко та продукти з нього',
        8: 'Горіхи, а саме мигдаль, фундук, волоські горіхи, кешʼю, пекан, бразильські горіхи, фісташки, горіхи макадамії та квінслендські горіхи, і продукти з них',
        9: 'Селера та продукти з неї',
        10: 'Гірчиця та продукти з неї',
        11: 'Кунжут та продукти з нього',
        12: 'Діоксид сірки та сульфіти в концентраціях понад 10 мг/кг або 10 мг/л',
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
    intro: {
      close: 'Закрити',
      back: 'Назад',
      next: 'Далі',
      start: 'Поїхали!',
      illustrationAlt: 'Ілюстрація',
      step1: 'Для входу використовуйте ті самі облікові дані, що й в AiS.',
      step2: 'Замовляйте їжу щонайменше за день і приходьте за нею, коли їдальня відкрита. Якщо не можете забрати замовлення, перенесіть його на біржу, і, можливо, хтось інший його викупить.',
      step3: 'Історію замовлень і статистику можна переглядати у профілі. Там же можна налаштувати смакові вподобання та алергени. Більше інформації знайдете у наших статтях.'
    },
  },
  ru: {
    auth: { login: 'Войти', logout: 'Выйти' },
    header: {
      languages: 'Языки',
      profile: 'Профиль',
      statistics: 'Статистика',
      orders: 'Заказы',
      accountBalance: 'Баланс счета',
      addMoney: 'Добавить деньги',
      settings: 'Настройки',
      menu: 'Меню',
      logout: 'Выйти',
    },
    menu: { title: 'Выберите столовую:', notice: 'Если вы не видите некоторые позиции, проверьте выбор аллергенов в настройках.', more: 'Подробнее', allergens: 'Аллергены', contains: 'Содержит', loading: 'Загрузка меню...', empty: 'На ближайшие дни меню не запланировано.', order: 'Заказать', cancelOrder: 'Отменить заказ', insufficientBalance: 'Недостаточно кредита', orderClosed: 'Заказ закрыт', badgeTemplate: 'Обед М{number}', aiNote: 'Изображение может быть сгенерировано искусственным интеллектом и может лишь частично или вовсе не соответствовать реальности. Иллюстрация с красным подносом показывается только тогда, когда нет доступного изображения, и не отображает реальное блюдо.', exchange: 'Биржа обедов', exchangeLoadError: 'Ошибка загрузки биржи.', exchangeEmpty: 'Сейчас на бирже нет предложений.', buy: 'Купить', backToCanteen: 'Назад' },
    payments: {
      title: 'Платежи',
      historyButton: 'История платежей',
      balanceLabel: 'На счете',
      cardPaymentTitle: 'Онлайн-оплата картой',
      amountLabel: 'Сумма в €',
      payButton: 'Оплатить',
      processing: 'Обработка...',
      bankTransferTitle: 'Банковский перевод',
      clientLine: 'Клиент: {clientName}',
      accountNameLine: 'Название счёта: {accountName}',
      accountLine: 'Номер счёта: {accountNumber}, открыт в {bankName}',
      ibanLine: 'IBAN: {iban}',
      variableSymbolTitle: 'Переменный символ (VS)',
      employeesTitle: 'Сотрудники',
      employeesVsHint: 'VS сотрудника указан на сайте UKF в разделе личных данных.',
      studentsTitle: 'Студенты и докторанты',
      studentsVsHint: 'ID из студенческого удостоверения (5-значный или 6-значный личный номер студента).',
      transferNotice: 'Перевод между счётом плательщика и счётом поставщика не выполняется онлайн, поэтому необходимо учитывать минимум 3 рабочих дня.',
      bottomInfoLine1: 'Чтобы вернуть неиспользованный остаток кредита на питание, напишите на адрес: {refundEmail}.',
      bottomInfoLine2: 'Пожалуйста, укажите имя, фамилию и номер счёта в формате IBAN, на который нужно вернуть сумму.',
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
    ordersPage: {
      title: 'Заказы',
      currentTitle: 'Текущие заказы',
      historyTitle: 'История заказов',
      date: 'Дата выдачи',
      orderedAt: 'Заказано',
      meal: 'Блюдо',
      canteen: 'Столовая',
      status: 'Статус',
      price: 'Стоимость',
      emptyCurrent: 'Сейчас у вас нет активных заказов.',
      emptyHistory: 'История заказов пока пустая.',
      loadMore: 'Загрузить еще',
      loading: 'Загрузка...',
      statuses: {
        ordered: 'Заказано',
        in_exchange: 'На бирже',
        cancelled: 'Отменено',
        sold: 'Продано'
      }
    },
    statistics: {
      addMoney: 'Добавить деньги',
      empty: 'Пока нет данных для статистики. Сделайте свой первый заказ, чтобы начать её заполнять!',
      mostOrderedLabel: 'Я чаще всего заказываю...',
      mostOrderedValue: '{meal}. Вы заказывали его {userOrders} {userOrdersWord}.',
      peakDayLabel: 'Я сильнее всего голоден/-на в...',
      peakDayValue: '{day} ({orders} {ordersWord})',
      totalVisitsLabel: 'Я был/-а в столовой...',
      totalVisitsValue: '{count} {visitsWord}'
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
        1: 'Злаки, содержащие глютен (пшеница, рожь, ячмень, овес, спельта, камут или их гибридные сорта)',
        2: 'Ракообразные и продукты из них',
        3: 'Яйца и продукты из них',
        4: 'Рыба и продукты из нее',
        5: 'Арахис и продукты из него',
        6: 'Соя и продукты из нее',
        7: 'Молоко и продукты из него',
        8: 'Орехи, а именно миндаль, фундук, грецкие орехи, кешью, пекан, бразильские орехи, фисташки, орехи макадамия и квинслендские орехи, и продукты из них',
        9: 'Сельдерей и продукты из него',
        10: 'Горчица и продукты из нее',
        11: 'Кунжут и продукты из него',
        12: 'Диоксид серы и сульфиты в концентрациях выше 10 мг/кг или 10 мг/л',
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
    intro: {
      close: 'Закрыть',
      back: 'Назад',
      next: 'Далее',
      start: 'Поехали!',
      illustrationAlt: 'Иллюстрация',
      step1: 'Для входа используйте те же данные, что и в AiS.',
      step2: 'Заказывайте еду минимум за день и забирайте её, пока столовая открыта. Если не можете забрать заказ, перенесите его на биржу, и, возможно, его выкупит другой пользователь.',
      step3: 'Историю заказов и статистику можно смотреть в профиле. Там же можно настроить вкусовые предпочтения и аллергены. Больше информации вы найдёте в наших статьях.'
    },
  }
};

export const i18n = createI18n({
  legacy: false,
  locale: resolveInitialLocale(),
  fallbackLocale: 'en',
  messages,
});
