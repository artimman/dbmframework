# Instalator DbM CMS (path: `install`, file: `_Documents/install.zip`)

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

### Pomoc i wsparcie

Jeśli napotkasz problemy:

- Sprawdź sekcję "Instalacja i konfiguracja" w dokumentacji DbM Framework.
- Zajrzyj do logów aplikacji.
- Skontaktuj się z autorem lub zespołem wsparcia.

---

DbM Framework & DbM CMS - szybki start, elastyczność i pełna kontrola nad projektem.
