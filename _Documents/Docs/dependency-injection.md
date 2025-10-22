# Dependency Injection – plik `services.php`

DbM Framework wykorzystuje własny, lekki kontener Dependency Injection (`DependencyContainer`), który umożliwia ręczne rejestrowanie i zarządzanie zależnościami w aplikacji. Mechanizm ten zapewnia pełną kontrolę nad instancjami klas i pozwala na elastyczne definiowanie zależności.

Plik rejestracji usług znajduje się tutaj: `application/services.php`

## Rejestracja usług

Usługi są rejestrowane w kontenerze poprzez przekazanie funkcji zwracającej instancję danej klasy. Przykład:

```php
return function (DependencyContainer $container) {
    // Rejestracja interfejsu DatabaseInterface
    $container->set(DatabaseInterface::class, function () {
        return isConfigDatabase() ? new Database() : null;
    });

    // Rejestracja klasy Request
    $container->set(Request::class, function () {
        return new Request();
    });

    // Rejestracja klasy Logger
    $container->set(Logger::class, function () {
        return new Logger();
    });

    // Rejestracja klasy IndexService
    $container->set(IndexService::class, function () {
        return new IndexService();
    });

    // Rejestracja klasy IndexController
    $container->set(IndexController::class, function () use ($container) {
        return new IndexController(
            $container->get(IndexService::class),
            $container->get(DatabaseInterface::class)
        );
    });
};
```

## Użycie w kontrolerach

Zarejestrowane usługi mogą być wstrzykiwane do kontrolerów poprzez konstruktor. Przykład:

```php
class IndexController extends BaseController
{
    public function __construct(
        IndexService $indexService,
        ?DatabaseInterface $database = null
    ) {
        parent::__construct($database);
    }

    public function index(IndexService $indexService): ResponseInterface
    {
        $this->setFlash('messageInfo', 'Your application is now ready and you can start working on a new project. Optionally, proceed to installing the DbM CMS content management system.');

        return $this->render('index/start.phtml', [
            'meta' => $indexService->getMetaIndex(),
        ]);
    }
}
```

W powyższym przykładzie, IndexService oraz DatabaseInterface są wstrzykiwane do konstruktora IndexController dzięki wcześniejszej rejestracji w kontenerze DI.

## Autowiring i dynamiczne tworzenie instancji

DbM Framework, mimo ręcznego mechanizmu DI, obsługuje również wybrane elementy automatycznego wstrzykiwania zależności (autowiring) i dynamicznego tworzenia instancji. Dzięki temu niektóre zależności mogą być rozpoznawane i dostarczane automatycznie, nawet jeśli nie zostały jawnie zarejestrowane w services.php.

### Autowiring metod kontrolera

Parametry metod akcji w kontrolerach (np. public function installer(IndexService $service, Request $request)) mogą być automatycznie rozwiązywane przez kontener, jeśli ich klasy zostały wcześniej zarejestrowane.

Framework wykorzystuje refleksję, by dopasować wymagane argumenty i przekazać je dynamicznie - nawet jeśli nie zostały przekazane przez konstruktor.

**Przykład:** Request może być poprawnie wstrzyknięty do metody kontrolera, jeśli jest zarejestrowany w kontenerze, mimo że nie został przekazany do konstruktora kontrolera.

### Dynamiczne tworzenie instancji klas

W przypadku wywołania metody:

```php
$this->getDIContainer()->get('App\Utility\NavigationUtility');
```

kontener próbuje automatycznie utworzyć instancję klasy, nawet jeśli nie została wcześniej zarejestrowana w services.php.

Ten mechanizm działa, jeśli klasa istnieje (class_exists) i konstruktor klasy nie wymaga niezarejestrowanych zależności.

Pomimo magii autowiringu i automatycznego wstrzykiwania klasa powinna być dodana w pliku services.php:

```php
// Dependencies added 
$container->set(NavigationUtility::class, function () {
    return new NavigationUtility();
});
```

Przykładowe zastosowanie w szablonach:

```html
<ul>
    {% foreach ($this->getDIContainer()->get('App\Utility\NavigationUtility')->headerNavigation() as $item): %}
    <li>
        <a href="{{ $this->path($item['link']) }}">{{ $item['name'] }}</a>
    </li>
    {% endforeach; %}
</ul>
```

### Rekomendacje

Zalecamy jawne rejestrowanie wszystkich usług, które zawierają zależności w konstruktorze lub są używane szerzej (np. w szablonach).

Mechanizmy automatyczne powinny być traktowane jako ułatwienie, nie jako domyślny sposób rejestracji.

## Zalety ręcznego DI

Pełna kontrola - samodzielne definiowanie, które klasy są rejestrowane i w jaki sposób.

Elastyczność - możliwość dostosowania sposobu tworzenia instancji do potrzeb aplikacji.

Brak zewnętrznych zależności - nie jest wymagane korzystanie z zewnętrznych bibliotek do zarządzania zależnościami.

## Gdzie są używane te usługi?

Kontrolery - do obsługi logiki aplikacji i interakcji z użytkownikiem.

Szablony - poprzez klasy pomocnicze, takie jak TemplateFeature.

Inne klasy aplikacji - np. do obsługi bazy danych, logowania, czy obsługi żądań HTTP.

## Podsumowanie

System Dependency Injection w DbM Framework umożliwia efektywne i wydajne zarządzanie zależnościami w aplikacji. Dzięki ręcznej rejestracji usług w pliku services.php, deweloper ma pełną kontrolę nad tym, które klasy są dostępne w kontenerze oraz w jaki sposób tworzone są ich instancje.

Brak automatycznych skanów, refleksji i nadmiarowych warstw przekłada się na prostotę oraz szybkość działania kontenera. W połączeniu z możliwością dynamicznego tworzenia klas i autowiringu metod, rozwiązanie to stanowi lekki i bardzo wydajny mechanizm wstrzykiwania zależności — działa szybko i sprawnie, ponieważ nie ładuje nadmiarowych komponentów.

Framework oferuje również wsparcie dla autowiringu metod kontrolera oraz dynamicznego tworzenia obiektów, co zwiększa elastyczność. Dzięki autowiringowi kod działa "magicznie", ale poprawnie. Dla zachowania przejrzystości i ułatwienia debugowania zaleca się jednak jawne rejestrowanie klas zależnych. Łącząc oba podejścia, DbM Framework zapewnia lekkie, szybkie i niezawodne zarządzanie zależnościami w aplikacji.
