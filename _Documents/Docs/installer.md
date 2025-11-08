# Instalator DbM CMS

Witaj w instalatorze DbM CMS! Twoje lekkie i elastyczne środowisko do tworzenia nowoczesnych aplikacji internetowych.

DbM CMS to szybki i nowoczesny system zarządzania treścią, zbudowany na bazie DbM Framework. Został zaprojektowany, aby umożliwić szybkie uruchomienie witryny lub aplikacji bez konieczności pisania kodu. Wystarczy kilka minut, by zyskać kompletny panel administracyjny, system logowania i gotowe moduły treści.

---

## Strona startowa aplikacji

Po uruchomieniu projektu zobaczysz stronę startową z dwiema opcjami:

### Rozpocznij tworzenie nowego projektu

- Przejdź do pliku src/Controller/IndexController.php i utwórz pierwszą stronę swojego projektu w metodzie index().
- Jeśli nie planujesz używać instalatora, możesz usunąć lub zakomentować ścieżkę installer w pliku application/routes.php.
- Zapoznaj się z dokumentacją DbM Framework, aby lepiej poznać strukturę projektu.

Przycisk: Przejdź do dokumentacji

---

### Uruchom instalator DbM CMS

Jeśli chcesz od razu zainstalować gotowy system zarządzania treścią - wybierz tę opcję.
Zainstalujesz kompletny CMS z panelem administracyjnym, logowaniem i obsługą modułów.

Przycisk: Uruchom instalator

---

### Konfiguracja środowiska

Zanim rozpoczniesz korzystanie z aplikacji, wykonaj poniższe kroki:

- Skonfiguruj plik .env - ustaw połączenie z bazą danych i parametry środowiska.
- Upewnij się, że plik .htaccess jest aktywny i poprawnie przekierowuje ruch na index.php.
- Po zakończeniu konfiguracji uruchom instalator lub rozpocznij tworzenie projektu.

Więcej informacji znajdziesz w dokumentacji DbM Framework w sekcji "Instalacja i konfiguracja".

---

### Dane logowania (Uwierzytelnianie)

Podczas instalacji system automatycznie tworzy trzech użytkowników w bazie danych:

| Login | Hasło | Rola |
|-------|-------|------|
| Admin | Admin123 | Administrator |
| John | Test123 | Użytkownik |
| Lucy | Test123 | Użytkownik |

Po zakończonej instalacji możesz zalogować się jako Administrator używając loginu lub adresu e-mail.

#### Zalecenie bezpieczeństwa

Zmień dane logowania wszystkich domyślnych użytkowników w panelu administracyjnym.

Możesz również usunąć konta testowe, jeśli nie będą potrzebne.  

#### Role i uwierzytelnianie użytkowników

Starter posiada **minimalistyczny system ról i uwierzytelniania** - domyślnie każdy nowo utworzony użytkownik otrzymuje rolę USER.  
Aby nadać użytkownikowi pełne uprawnienia administracyjne należy ręcznie zmienić wartość kolumny `roles` w tabeli `dbm_users` na `ADMIN`.  

System rozpoznaje dwie role:

- `USER` - dostęp ograniczony do podstawowych funkcji użytkownika,  
- `ADMIN` - pełny dostęp do wszystkich modułów i ustawień w panelu administracyjnym.

---

### Tłumaczenia (wielojęzyczność)

Aplikacja posiada wbudowany system tłumaczeń, który możesz wykorzystywać do tworzenia interfejsu w wielu językach.  
Aktualnie system nie zawiera natywnego systemu stron wielojęzycznych, jednak umożliwia dynamiczne przełączanie języka interfejsu i treści.

Po instalacji w menu aplikacji pojawi się lista wyboru języka.  
Dostępne języki są konfigurowane w pliku .env w zmiennej:

```env
APP_LANGUAGES="PL|EN|DE"
```

Pierwszy język w liście (PL) jest domyślny. Pozostawienie pola `APP_LANGUAGES` puste powoduje wyłączenie systemu tłumaczeń.

Zmiana języka odbywa się przez dodanie parametru do adresu URL, np.: ?lang=PL lub ?lang=EN.  
Aby wyczyścić sesję języka i powrócić do języka domyślnego, użyj: ?lang=OFF.

---

### Struktura pliku module.json

Każdy moduł wymaga pliku `module.json` w katalogu głównym modułu, który definiuje strukturę instalacji modułu. Plik ten zawiera informacje o plikach źródłowych oraz miejscach docelowych, do których mają zostać skopiowane podczas instalacji.

#### Struktura pliku

```json
{
    "name": "Module Name",
    "version": "1.0.0",
    "description": "Module description",
    "files": {
        "key1": "path/to/source/file",
        "key2": "path/to/source/directory"
    },
    "target": {
        "key1": "target/path/to/file",
        "key2": "target/path/to/directory"
    }
}
```

#### Pola w module.json

- **`name`** - Nazwa modułu wyświetlana w systemie
- **`version`** - Wersja modułu (np. "1.0.0")
- **`description`** - Krótki opis funkcjonalności modułu
- **`files`** - Obiekt zawierający mapowanie kluczy na ścieżki źródłowe plików/katalogów modułu (względem katalogu głównego projektu)
- **`target`** - Obiekt zawierający mapowanie kluczy na ścieżki docelowe, gdzie pliki/katalogi mają zostać skopiowane (względem katalogu głównego projektu)

```

#### Ważne informacje dotyczące ścieżek

**Ścieżki dla instalatora:**

W sekcji `target` wystarczy podać względne ścieżki docelowe. Instalator skopiuje całą zawartość wskazanego katalogu, np.:
- `"translations": "translations"` - kopiuje całą zawartość katalogu `translations/` z modułu do `translations/` w projekcie
- `"templates": "templates"` - kopiuje całą zawartość katalogu `templates/` z modułu do `templates/` w projekcie

**Ścieżki dla BackupBaseFiles:**

Jeśli chcesz, aby pliki były automatycznie kopiowane do `BackupBaseFiles` przed instalacją (co umożliwia ich przywrócenie podczas dezinstalacji), musisz użyć **dokładnych ścieżek do katalogów** zarówno w sekcji `files`, jak i `target`.

System automatycznie tworzy kopie zapasowe tylko dla plików i katalogów, których ścieżki w sekcji `files` zawierają `/src/` lub `/templates/` w swojej ścieżce.

**Różnica między instalatorem a BackupBaseFiles:**

- **Dla instalatora** wystarczy ogólna ścieżka: `"templates": "templates"` - skopiuje cały katalog
- **Dla BackupBaseFiles** potrzebna jest dokładna ścieżka do konkretnego podkatalogu: `"tplInclude": "templates/_include"`, `"tplIndex": "templates/index"`

**Przykład:**

```json
{
    "files": {
        "installController": "_Documents/install/src/Controller/InstallController.php",
        "tplComponent": "_Documents/install/templates/_component",
        "tplInclude": "_Documents/install/templates/_include",
        "tplIndex": "_Documents/install/templates/index",
        "translations": "_Documents/install/translations"
    },
    "target": {
        "installController": "src/Controller/InstallController.php",
        "tplComponent": "templates/_component",
        "tplInclude": "templates/_include",
        "tplIndex": "templates/index",
        "translations": "translations"
    }
}
```

W powyższym przykładzie:
- **Instalator** skopiuje:
  - Wszystkie pliki z `_Documents/install/templates/` do `templates/` (ze wszystkich podkatalogów)
  - Wszystkie pliki z `_Documents/install/translations/` do `translations/`
- **BackupBaseFiles** utworzy kopie zapasowe tylko dla:
  - `installController` (zawiera `/src/` w ścieżce źródłowej)
  - `tplComponent`, `tplInclude`, `tplIndex` (zawierają `/templates/` w ścieżce źródłowej)
  - `templates` **nie** zostanie skopiowane do BackupBaseFiles (mimo że zawiera `/templates/`, ponieważ jest to ogólna ścieżka - system wymaga dokładnych ścieżek do podkatalogów)
  - `translations` **nie** zostanie skopiowane do BackupBaseFiles (nie zawiera `/src/` ani `/templates/`)

**Uwaga:** Klucze w sekcjach `files` i `target` muszą być identyczne - każdy klucz w `target` musi mieć odpowiadający mu klucz w `files`. Jeśli chcesz, aby pliki były kopiowane do BackupBaseFiles, użyj dokładnych ścieżek do konkretnych katalogów w obu sekcjach (np. `"tplInclude": "templates/_include"` zamiast tylko `"templates": "templates"`).

---

### Pomoc i wsparcie

Jeśli napotkasz problemy:

- Sprawdź sekcję "Instalacja i konfiguracja" w dokumentacji DbM Framework.
- Zajrzyj do logów aplikacji.
- Skontaktuj się z autorem lub zespołem wsparcia.

---

DbM Framework & DbM CMS - szybki start, elastyczność i pełna kontrola nad projektem.
