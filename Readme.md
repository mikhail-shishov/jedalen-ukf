# Jedáleň UKF – lokálne spustenie projektu

## Použité technológie

- frontend: Vue 3 + TypeScript + Vite
- backend: Laravel
- databáza: MariaDB

## 1. Požiadavky

Pred spustením musia byť nainštalované lokalne:
- PHP 8.2+
- Composer 2+
- Node.js 20+, npm
- databáza MySQL/MariaDB (napr. cez XAMPP alebo MAMP)

## 2. Inštalácia projektu

V koreňovom priečinku projektu je treba spustiť v terminalu nasledujúce príkazy:

```bash
composer install
npm install
```

## 3. Konfigurácia `.env`

Ak ešte nemáte `.env` s nastaveniami projektu:

```bash
cp .env.example .env
php artisan key:generate
```

Zároveň je nutné nastaviť databázu.

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=meno_databazy_napriklad_jedalen_ukf
DB_USERNAME=root
DB_PASSWORD=
```

## 4. Migrácie, seedy, storage link

Nasledne je treba spustiť migrácie:

```bash
php artisan migrate
```

Naplnenie demo dátami:

```bash
php artisan db:seed
```

Pre obrázky:

```bash
php artisan storage:link
```

## 5. Spustenie projektu

```bash
composer run dev
```

V prehliadači otvoriť `http://localhost:8000`.

## 6. Prihlásenie do demo účtov

Po seedovaní je možné použiť:
- admin: `000000`
- študent: `100001`
- kuchár: `300001`

V projekte nie je možnosť registrácii, keďže používateľov registruje systém UKF. Spoločné heslo pre demo účty je A1bcdefg!

## 7. Kľúče API

Na testovanie systému je treba mať kľuče od API Stripe, Gemini a Pollinations. Ich je možno buď vytvoriť samostatne (netreba nič platiť), alebo požiadať správcu Github repozitára. Kľuče nie sú v projekte pre zvyšenie bezpečnosti.

```env
VITE_STRIPE_PUBLISHABLE_KEY=
STRIPE_SECRET_KEY=
# STRIPE_WEBHOOK_SECRET=

GEMINI_API_KEY=
POLLINATIONS_API_KEY=
```

## 8. Časté problémy a riešenia

### CSRF token mismatch

Najčastejšia príčina je iný port medzi frontendom a backendom alebo staré cookies.

Kroky:
- skontrolujte, že používate rovnakú URL ako v `.env` (`APP_URL=http://localhost:8000`), a že aplikáciu otvárate na tej istej adrese (`http://localhost:8000`)
- skuste hard reload stránky (`(CTRL+SHIFT+R)`)
- vyčistite cache pomocou príkazu (`php artisan optimize:clear`)
- ak nič nepomahá, je treba celý projekt vypnuť a zapnuť znovu

### Nefungujú obrázky zo storage

```bash
php artisan storage:link
```

### Chyba 500 pri prvom otvorení projektu

Ak sa po prvom spustení zobrazí chyba servera, môže isť o chýbajúci APP key, starý cache alebo neaplikované migrácie.

Spustite postupne:

```bash
php artisan key:generate
php artisan optimize:clear
php artisan migrate --seed
```

### AI preklady/generovanie obrázkov vracajú 502

Ak UI sa objaví 502, ale v storage/logs/laravel.log je chyba typu:
- cURL error 77: error adding trust anchors from file ...

To pravdepodobne nie je problém v kóde, ale problém SSL certifikátov (CA bundle) alebo zlej cesty v php.ini.

Diagnostika:
```bash
which php
php -v
php --ini
php -i | grep -Ei "Loaded Configuration File|curl.cainfo|openssl.cafile|openssl.capath|OpenSSL"
```

CLI PHP a webové PHP (Apache/XAMPP) môžu byť iné. Ak v termináli vidíte Homebrew PHP, ale aplikácia beží cez XAMPP, kontrolujte aj XAMPP PHP.

Odporúčané riešenie (prenositeľné):
1. Nastavte platný CA bundle v aktívnom php.ini:

```bash
curl.cainfo=/etc/ssl/cert.pem
openssl.cafile=/etc/ssl/cert.pem
```

Pre XAMPP môže byť platná aj cesta:

```bash
openssl.cafile=/Applications/XAMPP/xamppfiles/share/curl/curl-ca-bundle.crt
```

2. Reštartujte web server/PHP.
3. Vyčistite Laravel cache:

```bash
php artisan config:clear
php artisan cache:clear
php artisan optimize:clear
```

Táto oprava sa robí iba v konfigurácii prostredia, v php.ini.

Voliteľné záložné riešenie v kóde (iba ak oprava cez php.ini nepomôže):

1. Súbor [config/services.php](config/services.php)
Pridať sekciu ai:

```
  'ai' => [
    'ssl_verify' => env('AI_SSL_VERIFY', true),
    'ca_bundle' => env('AI_CA_BUNDLE'),
  ],
```

2. Súbor [app/Services/AiService.php](app/Services/AiService.php)
Pridať:
- use Illuminate\Http\Client\PendingRequest;
- property: protected string|bool $sslVerify;
- v __construct(): $this->sslVerify = $this->resolveSslVerifyOption();
- metódy httpClient() a resolveSslVerifyOption()

V metóde resolveSslVerifyOption() použiť poradie kandidátov na CA bundle:
- config('services.ai.ca_bundle')
- ini_get('curl.cainfo')
- ini_get('openssl.cafile')
- /etc/ssl/cert.pem
- /etc/ssl/certs/ca-certificates.crt
- /etc/pki/tls/certs/ca-bundle.crt

A následne nahradiť pri HTTP volaniach:
- Http::timeout(30)->post(...) na $this->httpClient(30)->post(...)
- Http::timeout(45)->...->get(...) na $this->httpClient(45)->...->get(...)

3. Súbor .env
Pridať:

  AI_SSL_VERIFY=true
  AI_CA_BUNDLE=/etc/ssl/cert.pem

4. Vyčistiť cache:

```bash
  php artisan config:clear
  php artisan cache:clear
  php artisan optimize:clear
```

Koniec záložného patcha.

## 9. Rýchly checklist príkazov na záver

1. `composer install`
2. `npm install`
3. `cp .env.example .env`
4. nastaviť DB a API v `.env`
5. `php artisan key:generate`
6. `php artisan migrate --seed`
7. `php artisan storage:link`
8. `composer run dev`
9. otvoriť `http://localhost:8000`