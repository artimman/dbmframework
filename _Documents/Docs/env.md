# Konfiguracja środowiska – plik `.env`

Plik `.env` zawiera najważniejsze ustawienia konfiguracyjne aplikacji DbM Framework.  
Znajduje się w katalogu głównym aplikacji:


Zmienne środowiskowe są wykorzystywane w wielu miejscach frameworka, np. w routingu, cache, tłumaczeniach, połączeniu z bazą danych i wysyłce e-maili.

---

## Uwaga dotycząca `.htaccess`

Przed skonfigurowaniem aplikacji **upewnij się**, że plik `.htaccess` w katalogu `public/` zawiera poprawną wartość `RewriteBase`.  
Jeśli serwer tego wymaga wytnij/wklej plik `.htaccess` z `_Documents` do katalogu głównego i również sprawdź wartość `RewriteBase`.

---

## Ogólne ustawienia

| Zmienna        | Opis |
|----------------|------|
| `APP_URL`      | Adres aplikacji (np. `https://domain.com`, `http://localhost/dbmframework/`) |
| `APP_NAME`     | Nazwa projektu lub właściciela |
| `APP_EMAIL`    | Adres e-mail właściciela/administratora |
| `APP_LANGUAGES`| Dostępne języki rozdzielone `|`, np. `PL|EN`. Pierwszy to język domyślny. Pozostaw puste, jeśli nie używasz tłumaczeń. |
| `APP_ENV`      | Środowisko aplikacji: `production` (domyślnie) lub `development` |
| `APP_SESSION_KEY` | Unikalny klucz sesji użytkownika (ważny dla bezpieczeństwa) |
| `APP_PANEL`    | Nazwa ścieżki dostępu do panelu administracyjnego, np. `panel0101` |

---

## Cache

| Zmienna         | Opis |
|------------------|------|
| `CACHE_ENABLED`  | Włączenie cache: `true` lub `false`. <br> **Domyślnie `false`. Po zakończeniu instalacji zaleca się ustawienie `true`**, co znacznie przyspiesza działanie strony dzięki buforowaniu widoków. |

---

## Baza danych

Jeśli aplikacja korzysta z bazy danych, należy uzupełnić poniższe zmienne:

| Zmienna      | Opis |
|--------------|------|
| `DB_HOST`    | Adres hosta bazy danych (np. `127.0.0.1`) |
| `DB_NAME`    | Nazwa bazy danych |
| `DB_USER`    | Użytkownik bazy danych |
| `DB_PASSWORD`| Hasło użytkownika bazy danych |
| `DB_CHARSET` | Kodowanie, domyślnie `utf8mb4` |
| `DB_PORT`    | Port połączenia, domyślnie `3306` dla MySQL |

---

## Wysyłka e-maili

| Zmienna             | Opis |
|----------------------|------|
| `MAIL_FROM_NAME`     | Nazwa nadawcy (domyślnie wartość `APP_NAME`) |
| `MAIL_FROM_EMAIL`    | Adres nadawcy (domyślnie `APP_EMAIL`) |
| `MAIL_SMTP`          | Włączenie SMTP: `true` lub `false` |
| `MAIL_HOST`          | Adres serwera SMTP (np. `smtp.gmail.com`) |
| `MAIL_USERNAME`      | Nazwa użytkownika SMTP |
| `MAIL_PASSWORD`      | Hasło SMTP |
| `MAIL_SECURE`        | Szyfrowanie SMTP: `tls`, `ssl` lub puste |
| `MAIL_PORT`          | Port SMTP (np. `587` lub `465`) |

---

## Google reCAPTCHA v3

Jeśli chcesz chronić formularze za pomocą reCAPTCHA, dodaj klucze:

| Zmienna                | Opis |
|-------------------------|------|
| `RECAPTCHA_SITE_KEY`    | Klucz publiczny reCAPTCHA |
| `RECAPTCHA_SECRET_KEY`  | Klucz prywatny reCAPTCHA |

---

## Przykładowy plik `.env`

```dotenv
APP_URL="http://localhost/"
APP_NAME="Design by Malina"
APP_EMAIL="kontakt@domain.com"
APP_LANGUAGES=PL|EN
APP_ENV=production
APP_SESSION_KEY=dbmUserSecureKey987
APP_PANEL=panel9876

CACHE_ENABLED=true

DB_HOST=127.0.0.1
DB_NAME=my_database
DB_USER=my_user
DB_PASSWORD=secret
DB_CHARSET=utf8mb4
DB_PORT=3306

MAIL_FROM_NAME="${APP_NAME}"
MAIL_FROM_EMAIL="${APP_EMAIL}"
MAIL_SMTP=true
MAIL_HOST=smtp.example.com
MAIL_USERNAME=smtp_user
MAIL_PASSWORD=smtp_pass
MAIL_SECURE=tls
MAIL_PORT=587

RECAPTCHA_SITE_KEY=your_site_key
RECAPTCHA_SECRET_KEY=your_secret_key
```

## Podsumowanie

Plik .env zawiera krytyczne ustawienia aplikacji.

Upewnij się, że:
- nie udostępniasz go publicznie
- zmieniasz wartości domyślne (szczególnie APP_SESSION_KEY i APP_PANEL)
- po uruchomieniu aplikacji ustawiasz CACHE_ENABLED=true dla lepszej wydajności.
