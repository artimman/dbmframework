# Command console - Konsola poleceń - `application/console.php`

## Omówienie

**Konsola DbM Framework** zapewnia prosty sposób uruchamiania zadań w tle lub zadań konserwacyjnych bezpośrednio z wiersza poleceń.
Imituje ona zachowanie konsoli Artisan (Laravel) lub Symfony, ale z lekką i niezależną implementacją.

Polecenia konsoli są wykonywane za pośrednictwem pliku `console.php` znajdującego się w katalogu `/application`.

---

## Podstawowe użycie

Uruchom polecenie z terminala:

```bash
php application/console.php Example
```

To polecenie spróbuje zlokalizować i wykonać następującą klasę:

```
/src/Command/ExampleCommand.php
```

z pełną nazwą:

```
App\Command\ExampleCommand
```

Jeśli klasa istnieje i implementuje `CommandInterface`, jej metoda `execute()` zostanie wykonana.

---

## Struktura polecenia

Każde polecenie powinno implementować interfejs `Dbm\Interfaces\CommandInterface`.

### Przykład polecenia

```php
<?php

declare(strict_types=1);

namespace App\Command;

use Dbm\Interfaces\CommandInterface;

/**
* Przykład prostego polecenia konsoli.
*
* Użycie: php application/console.php Example
*/
class ExampleCommand implements CommandInterface
{
    public function execute(): void
    {
        echo $this->exampleMethod();
    }

    private function exampleMethod(): string
    {
        return "\033[42mOK!\033[0m \n";
    }
}
```

### Oczekiwany wynik

Pomyślne wykonanie spowoduje wyświetlenie zielonego komunikatu „OK!”.
Błędy lub błędy mogą być wyświetlane na czerwono za pomocą kodów kolorów ANSI.

---

## Konwencja lokalizacji plików

Wszystkie polecenia konsoli są przechowywane w:

```
src/Command/
```

Każdy plik musi być zgodny z następującą konwencją nazewnictwa:

```
<CommandName>Command.php
```

Na przykład, polecenie wykonane za pomocą:

```bash
php application/console.php Cleanup
```

musi odpowiadać plikowi:

```
src/Command/CleanupCommand.php
```

i nazwie klasy:

```php
App\Command\CleanupCommand
```

---

## Obsługa błędów

Jeśli polecenie nie zostanie znalezione, konsola wyświetli podświetlone ostrzeżenie:

```
Nie znaleziono klasy "Example"
```

Jeśli nie zostanie przekazany żaden argument, pojawi się komunikat pomocy:

```
INFO! Podaj parametry wywołania. Przykład: php console.php Example
```

---

## Wskazówki i najlepsze praktyki

- Zachowaj atomowość poleceń - jeden plik na polecenie.
- Używaj opisowych nazw, takich jak `BackupDatabaseCommand` lub `ClearCacheCommand`.
- Dostęp do zmiennych środowiskowych można uzyskać za pomocą klasy `DotEnv` załadowanej w `console.php`.
- Zwracaj zrozumiałe dane wyjściowe z kodami kolorów, aby poprawić czytelność.

---

## Podsumowanie

**Konsola DbM** to przyjazne dla programistów narzędzie do uruchamiania niestandardowych skryptów, operacji konserwacyjnych lub zadań automatyzacji.
Jest w pełni autonomiczna - nie wymaga zewnętrznych bibliotek ani zależności od Composera.
