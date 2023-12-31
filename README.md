# DbM Framework

DbM Framework PHP MVC Simple CMS, Version 2.1  
Application tested on: PHP 7.4  
All code copyright Design by Malina (DbM)  
Web: www.dbm.org.pl  

# Język polski (PL - Polish language)

DbM Framework PHP MVC Simple CMS umożliwia tworzenie prostych i pięknych stron internetowych typu wizytówka, landingpage itp. gdzie zawartość jest przechowywana w plikach tekstowych (nie potrzebujesz bazy danych), ale do edycji zawartości wskazana jest znajomość CSS i HTML. Framework umożliwia też tworzenie większych aplikacji wymagających bazy danych takich jak regularnie aktualizowany, profesjonalny blog oraz bardziej rozbudowanych systemów o charakterze indywidualnego projektu, gdzie wymagana jest znajomości programowania. Przykładowa aplikacja demonstruje opcję z Landingpage na plikach tekstowych oraz System CMS na bazie danych.

## Idea

Funkcjonalna wersja prostego i szybkiego frameworka opartego o wzorzec architektury Model-View-Controller (MVC), który oddziela aplikację na trzy główne grupy: modele, widoki i kontrolery co też oddziela warstwę programistyczną od produkcyjnej. Framework skupiony jest na naturalnym, czystym PHP co daje wręcz nieograniczone możliwości - uwarunkowane w bardziej złożonych, sztucznych frameworkach zaprojektowanych do tworzenia aplikacji internetowych.

## Warunki wstępne

Aby rozpocząć prace we frameworku, musisz mieć zainstalowane następujące komponenty: [PHP](http://php.net), [MySQL](https://www.mysql.com), [Apache](https://httpd.apache.org) lub skorzystać z pakietu serwera WWW dla PHP np.: [XAMPP](https://www.apachefriends.org/).

## Struktura

- application (classes, methods and code related to the application)
  - Controller
  - Model
  - View (template folders)
  - -and more-
  - Config
  - Service
- config
- data
- library / dbmframework
- public (public folders)
- translations
- var / log / mailer (empty folders)
- vendor (reserved for Composer)

## Instalacja i konfiguracja

Pobierz DbM Framework i przenieś zawartość na serwer. Po skopiowaniu plików na wstępie przejdź do pliku `config.php.dist` i zmień nazwę na `config.php`, następnie uzupełnij wymagane dane konfiguracji. W kolejnym kroku w katalogu głównym oraz katalogu `/public/` dla plików .htaccess ustaw prawidłową wartość argumentu RewriteBase.  
W przypadku instalacji frameworka na serwerze lokalnym (localhost) utwórz katalog np. dbmframework, w tym katalogu dla pliku .htaccess ustaw RewriteBase /dbmframework/, w katalogu /public/ w pliku .htaccess ustaw RewriteBase /dbmframework/public/. W przypadku instalacji na serwerze zdalnym w domenie - domenę należy skierować na adres katalogu /public/ w katalogu /public/ dla .htaccess ustawić RewriteBase / po czym plik .htaccess w katalogu głównym aplikacji można usunąć. Do usunięcia jest też plik w katalogu /var/log/mailer/_remove.txt. Katalog /vendor/ jest zastrzeżony dla systemu zarządzania pakietami `Composer`.  

### Composer

Użyj Composera do załadowania wymaganych pakietów, ich aktualizacji itp. (patrz do composer.json).  
Wykonaj polecenie, które utworzy "autoloading" oraz pobierze i zainstaluje pakiety w najnowszej dostępnej wersji:

```shell
$ composer install
```

Ostrożnie! Przed wykonaniem polecenie `composer install` usuń katalog /vendor/ i jego zwartość (autoloading) oraz plik composer.lock.  

Nie mam konieczności wykonania powyższego polecenia, zawsze jednak warto wykonać aktualizacje poleceniem, które sprawdzi jakie najnowsze wersje bibliotek są dostępne i uaktualni plik composer.lock:

```shell
$ composer update 
```

Jeżeli nie masz zainstalowanego Composera, a chcesz uruchomić aplikacje możesz skopiować katalog vendor oraz dodatkowe paczki z _Documents/Reserved/vendor do katalogu głównego.

### Data FlatFile .txt (dane w plikach tekstowych)

- `data/contents/` dane w plikach tekstowych, nadaj plikom prawa do zapisu
- `data/message/` szablon, pliki do wysyłania wiadomości e-mail

### Database (baza danych nie jest wymagana do uruchomienia aplikacji)

- _Documents/Database/dbm_cms.sql

Jeśli chcesz użyć bazy danych importuj ją na serwer i skonfiguruj połączenie w pliku konfiguracyjnym.

## Biblioteki

Wykorzystano następujące pakiety:

* [jQuery](https://jquery.com) - JavaScript Library.
* [Bootstrap](https://getbootstrap.com) - The most popular HTML, CSS, and JS library in the world.

UWAGA! Wolna licencja ma tylko jedno ograniczenie. Jeśli chcesz skorzystać z frameworka nie masz prawa usuwać linku do strony z nazwą autora ze stopki skryptu. Usuwając nazwę, używasz oprogramowania nielegalnie. Autor zapewnia wsparcie dla aplikacji, nie ponosi odpowiedzialności za jakiekolwiek szkody powstałe na skutek korzystania z oprogramowania. Jeśli masz pomysł na rozwój projektu napisz do mnie, porozmawiajmy...
