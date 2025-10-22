# DbM Template Engine

Lekki, szybki i bezpieczny silnik szablonów dla PHP — **DbM Template Engine** (namespace `Dbm\Views`).  
Zaprojektowany z myślą o czytelności, testowalności i prostocie integracji z aplikacjami MVC.

---

## Spis treści

- [Wprowadzenie](#wprowadzenie)
- [Cechy](#cechy)
- [Instalacja](#instalacja)
- [Szybki start](#szybki-start)
- [API — klasy i ich role](#api---klasy-i-ich-role)
- [Składnia szablonów](#składnia-szablonów)
- [Filtry domyślne i rozszerzenia](#filtry-domyślne-i-rozszerzenia)
- [Obsługa błędów i debugowanie](#obsługa-błędów-i-debugowanie)
- [Testowanie](#testowanie)
- [Dobre praktyki i wskazówki migracyjne](#dobre-praktyki-i-wskazówki-migracyjne)
- [Licencja](#licencja)

---

## Wprowadzenie

DbM Template Engine to prosty system szablonów, który konwertuje składnię podobną do Twig/Blade do czystego PHP, zapisuje skompilowany kod w cache i uruchamia go w bezpiecznym kontekście runtime.  
Celem było uzyskanie dobrego kompromisu pomiędzy **wydajnością** (cache), **bezpieczeństwem** (domyślne escapowanie wyjścia) oraz **elastycznością** (filtry, partiale, dziedziczenie layoutów).

---

## Cechy

- Kompilacja składni `{% ... %}` i `{{ ... }}` do PHP.
- Cache plikowy dla skompilowanych klas (`TemplateCache`).
- Runtime z obsługą bloków (`TemplateRuntime`) i delegacją do kontrolera.
- System filtrów (`TemplateFilters`) z możliwością rejestracji własnych filtrów i obsługą parametrów.
- Opcjonalne lintowanie wygenerowanego kodu PHP przed zapisaniem (tryb `enableLint`).
- Tryb debugujący zapisujący wersje pośrednie (opcja `enableDebugger`).

---

## Instalacja

1. Umieść kod pod namespace `Dbm\Views` w swoim projekcie (PSR-4).
2. Upewnij się, że masz zdefiniowane globalnie stałe (projekt przykładowy):
```php
define('DS', DIRECTORY_SEPARATOR);
define('BASE_DIRECTORY', dirname(__DIR__) . DS);
```
3. Ustaw katalog `templates` i `var/cache` z prawidłowymi uprawnieniami do zapisu.

---

## Szybki start

```php
use Dbm\Views\{
    TemplateEngine,
    TemplateCompiler,
    TemplateFilters,
    TemplateCache
};

$templatesDir = __DIR__ . '/templates';
$cacheDir = __DIR__ . '/var/cache';

$filters = new TemplateFilters();
$compiler = new TemplateCompiler($templatesDir, $filters);
$cache = new TemplateCache($cacheDir);

$engine = TemplateEngine::createFromComponents($compiler, $cache);
// lub: $engine = TemplateEngine::createFromPaths($templatesDir, $cacheDir);

echo $engine->render('home.phtml', ['title' => 'Witaj']);
```

> Sugerowane podejście: użyj fabryki `createFromComponents()` zamiast konstruktora. To czystsze i bardziej przewidywalne API.

---

## API — klasy i ich role

### TemplateEngine
Główna klasa integrująca kompilator, cache i runtime. Odpowiada za publiczne API: `render()`, `renderContent()` itp.  
Rekomendowane konstruktory/fabryki:
- `TemplateEngine::createFromComponents(TemplateCompiler $compiler, TemplateCache $cache)`

### TemplateCompiler
Konwertuje plik szablonu do PHP. Udostępnia metody pomocnicze (getter ścieżki i filters) dla integracji i testów:
- `compile(string $templateName): string`
- `getTemplatesPath(): string`
- `getFilters(): TemplateFilters`

### TemplateFilters
Rejestr i generator filtrów. Pozwala rejestrować filtry i generować wyrażenia PHP:
- `applyPhp(string $expr, array $filters): string`
- `register(string $name, callable $phpGenerator): void`

### TemplateCache
Zarządza zapisem i sprawdzaniem świeżości plików cache:
- `getCachePath(string $template): string`
- `isFresh(string $templatePath, string $cachePath): bool`
- `write(string $cachePath, string $code): void`
- `clear(): int`, `clearExpired(string $templatesDir): int`

### TemplateRuntime
Bazowa klasa wykonywana po załadowaniu skompilowanej klasy szablonu. Udostępnia:
- Bloki (`startBlock`, `endBlock`, `yieldBlock`),
- `renderPartial()`,
- delegację metod do kontrolera/engine,
- `getEngineInstance()`.

### TemplateException / RuntimeException
Dedykowane wyjątki dla problemów z kompilacją, zapisem cache itp.

---

## Składnia szablonów

Krótka ściąga:

- `{{ expr }}` — echo z escapowaniem: `htmlspecialchars((string)(expr ?? ""), ENT_QUOTES, "UTF-8")`
- `{{ expr|raw }}` — echo bez escapowania
- `{{ value|upper|escape }}` — łańcuch filtrów
- `{% if (cond) %} ... {% endif %}` — warunki
- `{% foreach (items as item) %} ... {% endforeach %}` — pętle
- `{% block name %} ... {% endblock %}` — bloki
- `{% yield name %}` — wstawienie bloku
- `{% extends 'parent.phtml' %}` — dziedziczenie layoutu
- `{% include 'partial.phtml' with { 'a': 1 } %}` — partial z lokalnymi danymi
- `{{ var|raw_allowed(['br','b']) }}` — filtr zezwalający tylko na podane tagi HTML

---

## Filtry domyślne i rozszerzanie

Przykładowe wbudowane filtry: `escape` (alias `e`), `raw` (alias `safe`), `upper`, `lower`, `trim`, `length`, `nl2br`, `strip`, `json`, `url`, `join`, `number`, `date`, `raw_allowed`.  
Możesz dodać własny filtr:

```php
$filters->register('truncate', function(string $expr, string $args = '50') {
    return "mb_substr({$expr}, 0, {$args}) . (mb_strlen({$expr}) > {$args} ? '...' : '')";
});
```

### Filtr `raw_allowed(['br','b'])`
Implementacja używa `strip_tags($value, '<br><b>')`. Upewnij się, że parametry są przekazywane jako tablica lub jako literal w filtrze (`raw_allowed(['br','br'])`).

---

## Obsługa błędów i debugowanie

- **Nie używamy operatora `@`** do tłumienia błędów (zamiast tego: jawne sprawdzanie i rzucanie wyjątków). Wyjątkiem może być `@unlink()` przy czyszczeniu cache, gdy operacja jest best-effort.  
- `enableDebugger` — przy włączeniu zapisuje kopie skompilowanych plików w katalogu debugowym oraz pozwala na bardziej przejrzyste komunikaty (np. HTML-komentarze w widoku podczas błędu partiala).  
- `enableLint` — (opcjonalne) uruchomienie `php -l` na pliku wygenerowanym przed przeniesieniem go do cache.

---

## Testowanie

Zalecany stack: PHPUnit. Przykłady testów:

- Test prostego renderu z filtrem (`{{ name|upper }}`).
- Test partiala i przekazywania danych (`include ... with {...}`).
- Test bloków i dziedziczenia (`extends` + `block` + `yield`).
- Testy integracyjne kończące się na wywołaniu `render()` i porównaniu stringów.

Przykład testu (szybka wersja):

```php
final class TemplateEngineTest extends TestCase
{
    protected function setUp(): void
    {
        $this->tplDir = __DIR__ . '/templates';
        $this->cacheDir = __DIR__ . '/cache';

        if (!is_dir($this->tplDir)) mkdir($this->tplDir, 0777, true);
        if (!is_dir($this->cacheDir)) mkdir($this->cacheDir, 0777, true);

        $filters = new TemplateFilters();
        $compiler = new TemplateCompiler($this->tplDir, $filters);
        $cache = new TemplateCache($this->cacheDir);

        $this->engine = TemplateEngine::createFromComponents($compiler, $cache);
    }

    public function testSimpleRender(): void
    {
        file_put_contents($this->tplDir . '/simple.phtml', 'Hello {{ name|upper }}');
        $result = $this->engine->render('simple.phtml', ['name' => 'world']);
        $this->assertStringContainsString('WORLD', $result);
    }
}
```

---

## Dobre praktyki i wskazówki migracyjne

- **Preferuj DI**: przekazuj gotowe obiekty (`TemplateCompiler`, `TemplateCache`) do `TemplateEngine` w celu lepszej testowalności. Alternatywnie, udostępnij fabryczne metody `createFromPaths()` dla wygody.
- **Nie używaj `@`**: nie tłumisz ostrzeżeń — radź sobie z nimi jawnie (throw/exceptions) lub obsłuż w logiczny sposób.
- **Zadbaj o uprawnienia**: katalog cache musi być zapisywalny przez proces PHP.
- **Filtry**: rejestruj nowe filtry w jednym miejscu (konfiguracja lub container).

---

## Licencja

Umieść tutaj swoją licencję (MIT, proprietary itp.). Domyślnie traktuj repo jako prywatne, a licencję dopisz według potrzeb.

---

> Jeśli chcesz, mogę:  
> - dopracować README na format Markdown GitHub (README.md) z przykładami plików i strukturą katalogów;  
> - wygenerować Javadoc-like API w pliku HTML;  
> - przygotować skrypt instalacyjny composer.json.  
