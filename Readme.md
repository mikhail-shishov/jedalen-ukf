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

V koreňovom priečinku projektu je treba spustiť v terminaku nasledujúce príkazy:

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
- skuste hard reload stránky (CTRL+SHIFT+R)
- vyčistite cache pomocou príkazu php artisan optimize:clear
- ak nič nepomahá, je treba celý projekt vypnuť a zapnuť znovu

### Nefungujú obrázky zo storage

```bash
php artisan storage:link
```

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