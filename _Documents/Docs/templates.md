# Templates

DbM Framework posiada wbudowany, lekki system szablonów umożliwiający pisanie czytelnych i dynamicznych widoków z użyciem uproszczonej składni inspirowanej m.in. Twigiem i Blade.

Szablony mają rozszerzenie `.phtml` i znajdują się w katalogu: `templates/`.

---

## Składnia

Szablony wspierają dwie główne składnie:

| Składnia         | Opis                                |
|------------------|-------------------------------------|
| `{{ ... }}`      | Wyświetlenie danych (odpowiednik `echo`) |
| `{% ... %}`      | Wykonanie kodu PHP (np. przypisania, warunki, pętle) |

### Przykłady

```php
<h1>{{ $this->get('title') }}</h1>
{% $year = date('Y') %}
<footer>&copy; {{ $year }}</footer>
```

## Include - dołączanie podszablonów

Możesz wstawiać inne szablony w dowolnym miejscu za pomocą:

```php
{% include '_include/start_footer.phtml' %}
```

Pliki najczęściej znajdują się w: `templates/_include/`.

## Yield - system bloków

Szablony mogą zawierać dynamiczne bloki (np. content, head, scripts) – np. w layoucie głównym:

```php
<!DOCTYPE html>
<html>
<head>
    {% yield head %}
</head>
<body>
    {% yield content %}
    {% yield scripts %}
</body>
</html>
```

W widoku podrzędnym można te bloki zdefiniować:

```php
{% block content %}
    <h1>Witamy!</h1>
    <p>To główna treść strony.</p>
{% endblock %}
```

## Dziedziczenie układu (layout)

Każdy widok może rozszerzać layout główny:

```php
{% extends 'base.phtml' %}
```

A następnie nadpisywać jego bloki poprzez block / endblock.

## Przekazywanie zmiennych do widoków

Zmiennie do szablonu przekazywane są z kontrolera:

```php
return $this->render('home/index.phtml', [
    'title' => 'Strona główna',
    'user' => $user,
]);
```

W szablonie używasz ich przez:

```php
<h1>{{ $title }}</h1>
```

lub sprawdzasz istnienie:

```php
{% if ($user) : %}
    <p>Witaj, {{ $user->name }}</p>
{% endif; %}
```

## Dostęp do metod TemplateFeature

Wewnątrz każdego szablonu $this jest instancją klasy TemplateFeature, co daje dostęp do przydatnych metod pomocniczych:

Szczegóły w dokumentacji: [TemplateFeature](_Documents/Docs/template-feature.md)

## Przykład kompletnego szablonu

```php
{% extends 'base.phtml' %}

{% block content %}
    <h1>{{ $title }}</h1>
    <p>Witaj w aplikacji DbM Framework!</p>
{% endblock %}
```

## Możliwość zmiany systemu szablonów

DbM Framework pozwala również na pełne zastąpienie wbudowanego systemu szablonów zewnętrznym silnikiem - np. Twig.

Wystarczy zmodyfikować klasę `TemplateEngine` (dziedziczoną przez BaseController) tak, aby używała silnika Twig zamiast własnej składni.

Należy pamiętać, że w takiej sytuacji konstruktor klasy `BaseController` powinien jawnie wywołać `parent::__construct()`, aby poprawnie zainicjalizować mechanizm renderowania Twig.

**Przykład:**

```php
namespace Dbm\Classes;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Psr\Http\Message\ResponseInterface;
use Dbm\Classes\Http\Response;
use Dbm\Classes\Http\Stream;

class TemplateEngine
{
    private Environment $twig;

    public function __construct()
    {
        $loader = new FilesystemLoader(BASE_DIRECTORY . 'templates');
        $this->twig = new Environment($loader, [
            'cache' => BASE_DIRECTORY . 'var/cache',
            'auto_reload' => true,
        ]);
    }

    protected function render(string $template, array $data = []): ResponseInterface
    {
        $content = $this->twig->render($template, $data);
        return new Response(200, ['Content-Type' => 'text/html'], new Stream($content));
    }
}
```

Dzięki temu możesz pisać widoki w .twig, korzystać z dziedziczenia layoutów, filtrów, makr itp. - bez konieczności przebudowy kontrolerów, poza dodaniem `parent::__construct()` w ich konstruktorach.

## Podsumowanie

System szablonów DbM Framework pozwala budować przejrzyste widoki z dziedziczeniem, składnią znaną z większych frameworków i prostym dostępem do zmiennych oraz helperów. Nie wymaga żadnych zewnętrznych bibliotek.
