# DbM Framework

DbM Framework PHP MVC + DbM CMS, Version 3  
Wszystkie prawa autorskie zastrzeżone przez Design by Malina (DbM)  
Strona WWW: [www.dbm.org.pl](http://www.dbm.org.pl)  
Wymagania aplikacji: [PHP](http://php.net) (>= 8.1), [MySQL](https://www.mysql.com)  

## O frameworku

DbM Framework to jedno z najszybszych rozwiązań PHP opartych na wzorcu MVC, łączące lekkość, elastyczność i wydajność z nowoczesnymi możliwościami rozbudowy. Pozwala na łatwe dodawanie funkcji bez ingerencji w rdzeń, a przemyślana architektura zapewnia stabilność i bezpieczeństwo. To idealny wybór dla programistów ceniących pełną kontrolę nad kodem i swobodę w tworzeniu zaawansowanych aplikacji webowych.  

DbM CMS to oparte na frameworku gotowe rozwiązanie dla tych, którzy chcą szybko uruchomić stronę lub aplikację bez konieczności kodowania. Obsługuje zarówno proste strony, jak i rozbudowane projekty oparte na bazie danych. Jeśli nie masz czasu na tworzenie własnych modułów, możesz skorzystać z gotowych narzędzi do zarządzania treścią, SEO i strukturą witryny. Skuteczne rozwiązanie, które przyspiesza rozwój projektów bez rezygnacji z elastyczności frameworka.  

## Struktura Frameworka

- `application/` – rdzeń frameworka: klasy, interfejsy, biblioteki
- `config/` – pliki configuracji (opcjonalne, np. php.ini, moduły CMS)
- `public/` – pliki publiczne (root domeny)
- `src/` – logika aplikacji: kontrolery, serwisy, modele, usługi
- `templates/` – szablony widoków
- `tests/` – testy jednostkowe
- `translations/` – pliki tłumaczeń (opcjonalny)
- `var/` – cache i logi (tworzone automatycznie, wymagane prawa do zapisu)
- `vendor/` – biblioteki zainstalowane przez Composera

## Dodatkowa struktura w przypadku instalacji CMS

- `data/` – dane i pliki (wymagane prawa do zapisu)
- `libraries/` – zewnętrzne biblioteki ([PHPMailer](https://github.com/PHPMailer/PHPMailer) - Biblioteka do wysyłania e-maili)
- `modules/` - moduły systemy zarządzania treścią

## Instalacja manualna

1. Skieruj domenę na katalog `public/`. W pliku `public/.htaccess` ustaw odpowiedni `RewriteBase`.
2. Jeśli korzystasz z localhosta, skopiuj plik `.htaccess` z katalogu `_Documents` do katalogu głównego i dostosuj `RewriteBase`.
3. Skonfiguruj plik `.env.example`, następnie zmień jego nazwę na `.env`.

W konfiguracji podstawowej uzupełnij sekcję **General settings**:

```env
APP_URL="http://localhost/"
APP_NAME="Application Name"
APP_EMAIL="email@domain.com"
```

Następnie skonfiguruj: Cache settings, Database settings, Mailer settings.

**Uwaga:** Po uruchomieniu aplikacji należy ustawić CACHE_ENABLED=true, aby włączyć buforowanie i przyspieszyć działanie strony.

## Autoloading

Instalacja manualna czyni framework niezależnym od innych narzędzi, wyposażonym w własny autoloading. Wykonanie polecenia `composer install` zautomatyzuje framework, utworzy autoloading Composera i zainstaluje wybrane pakiety, np. do wysyłania wiadomości e-mail oraz pakiety deweloperskie. Po wykonaniu komendy framework będzie współpracował z Composerem.  

## Instalacja przez Composera

Jeśli preferujesz instalację za pomocą Composera lub projekt wymaga dodatkowych pakietów:

```bash
git clone https://github.com/designbymalina/dbmframework.git
```

Jeśli chcesz korzystać z zewnętrznych bibliotek, możesz użyć Composera:

```bash
composer install
```

Instalacja przez Composera utworzy autoloading oraz pobierze wszystkie zależności. 

## Routing

Routing definiujesz w pliku: `application/routes.php`:

Przykład:

```shell
$router->addRoute('path', [NameController::class, 'methodName'], 'route_name');
```

## Dependency Injection

Wszystkie zależności aplikacji rejestrujesz ręcznie w pliku: `application/services.php`. Zarejestrowane klasy mogą być automatycznie wstrzykiwane do kontrolerów przez konstruktor lub parametry metod.

## Silnik szablonów

Framework domyślnie korzysta z wbudowanego silnika szablonów. Można go dowolnie zastąpić przez np. Twig.

Szablony znajdują się w katalogu `templates/`.

## Informacja dodatkowa

W środowisku produkcyjnym kieruj domenę na katalog public/. W przypadku uruchamiania aplikacji w środowisku produkcyjnym (na serwerze zdalnym), **należy skierować domenę na katalog `/public/`**, ponieważ to właśnie on pełni rolę katalogu głównego (document root).

Upewnij się, że open_basedir nie blokuje dostępu do katalogów. Dodatkowo, w zależności od konfiguracji serwera, **może być konieczne wyłączenie ograniczenia `open_basedir`** w ustawieniach PHP. To zabezpieczenie, znane jako "separacja stron", może blokować dostęp do niektórych katalogów i plików spoza katalogu głównego domeny, co uniemożliwi otworzenie aplikacji w domenie.

Po uruchomieniu aplikacji włącz cache (`CACHE_ENABLED=true`) co przyspiesza działanie strony.

Jeśli używasz CMS, zadbaj o prawa zapisu w data/, modules/.

**WAŻNE!** Korzystając z DbM Framework dodaj na stronie (np. w stopce): "Created with <a href="https://dbm.org.pl/" title="DbM">DbM Framework</a>". Link powinien pozostać nienaruszony. Dziękujemy za wsparcie rozwoju projektu!

Dokumentacja:

[Environment configuration (.env)](_Documents/Docs/env.md)  
[Routing (routes.php)](_Documents/Docs/routing.md)  
[Dependency Injection (services.php)](_Documents/Docs/dependency-injection.md)  
[TemplateFeature](_Documents/Docs/template-feature.md)  
[Templates](_Documents/Docs/templates.md)  
