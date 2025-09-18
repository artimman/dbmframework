# TemplateFeature

Klasa `TemplateFeature` udostępnia zestaw metod pomocniczych, z których można korzystać bezpośrednio w szablonach `.phtml`. Jej głównym celem jest uproszczenie pracy z widokami i umożliwienie stosowania gotowych funkcji – takich jak generowanie ścieżek, zasobów, danych meta czy formularzy.

Lokalizacja: `application/Classes/TemplateFeature.php`.

Można ją dowolnie rozbudowywać o własne metody, które będą dostępne automatycznie w szablonach jako `$this`.

---

## Dostęp do metod

Wszystkie metody `TemplateFeature` są dostępne w szablonach poprzez `$this`.

Przykładowe użycie:

```php
{{ $this->path('home') }}
```

### Lista metod

#### Metoda path()

```php
path(string $name, array $params = []): string
```

Zwraca ścieżkę URL przypisaną do trasy o nazwie $name, opcjonalnie z dynamicznymi parametrami.
{{ $this->path('start') }}

#### Metoda asset()

```php
asset(string $path): string
```

Zwraca ścieżkę do zasobu (np. CSS, JS) względem katalogu publicznego.

```html
<link rel="stylesheet" href="{{ $this->asset('css/style.css') }}">
```

#### Metoda trans()

```php
trans(string $key): string
```

Zwraca tłumaczenie dla danego klucza, jeśli ustawiona została obsługa języków (APP_LANGUAGES w .env).

```html
{{ $this->trans('site.title') }}
```

#### Metoda meta()

```php
meta(string $key): string
```

Zwraca dane meta (np. title, description, keywords) ustawione w kontrolerze lub serwisie.

#### Metoda truncate()

```php
truncate(string $text, int $limit = 120): string
```

Skraca tekst do podanej liczby znaków i dodaje ... jeśli przekracza limit.

#### metoda counterVisits()

```php
counterVisits(): int
```

Zwraca liczbę odsłon strony (na podstawie prostej mechaniki licznika).

#### Metoda constConfig()

```php
constConfig(string $constName): mixed
```

Zwraca wartość stałej konfiguracyjnej zdefiniowanej w src/Config/ConstantConfig.php.

#### Metoda linkCanonical()

```php
linkCanonical(): string
```

Zwraca pełny adres URL do aktualnej podstrony - przydatny dla SEO w tagu:

```html
<link href="{{ $this->linkCanonical() }}" rel="canonical">
```

#### Metoda isActive() i pomocnicza extractParameter()

```php
isActive(string $name): bool
```

Sprawdza, czy aktualnie wyświetlana trasa ma nazwę $name i dodaje klasę.

```html
<a href="#" class="{{ $this->isActive('index') }}">Link</a>
or with options
<a href="#" class="{{ $this->isActive('index', 'active', '') }}">Link</a>
```

```php
extractParameter(string $source, string $key): string
```

Wydobywa wartość danego parametru GET lub z łańcucha tekstowego.

```html
{% $parameter = $this->extractParameter(); %}
{% ($parameter == $blogData->aid) ? $isActiveArticle = $this->isActive('blog_article') : $isActiveArticle = ''; %}
<a href="#" class="{{ $isActiveArticle }}">Link</a>
```

#### Metoda replaceContent()

```php
replaceContent(string $content): string
```

Podmienia znaczniki $searchReplace w treści na przekazane dane z formatowaniem URL'a.

#### Metoda isPath()

```php
isPath(string $routeName): bool
```

Sprawdza, czy aktualna ścieżka pasuje do podanej nazwy trasy.

#### Metoda htmlCreateSelect()
```php
htmlCreateSelect(string $name, array $options, string $selected = ''): string
```

Generuje tag `select` z opcjami.

```html
{{ $this->htmlCreateSelect('category', ['1' => 'News', '2' => 'Blog'], '2') }}
```

#### Metoda htmlLanguage()

```php
htmlLanguage(): string
```

Generuje przełącznik języków i tłumaczeń

```html
{{ $this->htmlLanguage() }}
```

## Podsumowanie

TemplateFeature to centrum narzędzi pomocniczych dla warstwy widoków. Zapewnia porządek w szablonach i pozwala zachować logikę poza HTML-em.
