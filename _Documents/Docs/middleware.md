# Middleware - DbM Framework

Middleware w DbM Framework pozwala wykonywać operacje **przed** lub **po** obsłudze żądania przez kontroler.
Służy m.in. do:
- autoryzacji i uwierzytelniania (np. JWT, token, sesje),
- logowania żądań i błędów,
- obsługi CORS (Cross-Origin Resource Sharing),
- modyfikacji nagłówków lub danych wejściowych.

---

## Plik konfiguracyjny

Wszystkie middleware są rejestrowane w pliku:
```
application/middleware.php
```

Przykład:
```php
<?php

use Dbm\Classes\Log\Logger;
use Dbm\Classes\Router;
use Dbm\Middleware\ApiAuthMiddleware;
use Dbm\Middleware\CorsMiddleware;
use Dbm\Middleware\RequestLoggerMiddleware;

return function (Router $router): void {
    $logger = new Logger();

    // Globalne middleware - działa dla wszystkich tras
    $router->addMiddleware(new CorsMiddleware());

    // Middleware testowe (np. logowanie czasu żądania)
    $router->addMiddleware(new RequestLoggerMiddleware($logger));

    // Middleware tylko dla tras API
    $router->addMiddleware(new ApiAuthMiddleware(), '/api');
};
```

---

## Tworzenie własnego middleware

Każdy middleware powinien implementować prosty interfejs z metodą `__invoke()`:

```php
namespace Dbm\Middleware;

use Dbm\Classes\Http\Request;
use Psr\Http\Message\ResponseInterface;

class ExampleMiddleware
{
    public function __invoke(Request $request): ?ResponseInterface
    {
        // logika przed wywołaniem kontrolera
        error_log('Incoming request: ' . $request->getUri());

        // zwróć null, aby kontynuować obsługę
        // lub ResponseInterface, aby przerwać
        return null;
    }
}
```

---

## Działanie w Routerze

Middleware są wywoływane w kolejności rejestracji:
1. **Globalne middleware** — np. `CorsMiddleware`,
2. **Middleware z prefiksem** (np. `/api`),
3. **Kontroler** (tylko jeśli żadne middleware nie zwróciło odpowiedzi).

---

## Przykład: API Authorization Middleware

```php
namespace Dbm\Middleware;

use Dbm\Classes\Http\Request;
use Dbm\Classes\Http\Response;

class ApiAuthMiddleware
{
    public function __invoke(Request $request): ?ResponseInterface
    {
        $headers = $request->getServerParams();

        if (!isset($headers['HTTP_AUTHORIZATION']) ||
            $headers['HTTP_AUTHORIZATION'] !== 'Bearer ' . getenv('APP_API_TOKEN')) {

            return Response::json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 401);
        }

        return null; // pozwól kontynuować
    }
}
```

---

## Dobre praktyki

- Middleware powinny być **małe, niezależne i testowalne**.  
- Jeśli logika jest bardziej złożona (np. JWT, ACL), rozważ użycie osobnej klasy serwisu.  
- Unikaj modyfikowania danych `$_POST`, `$_GET` itp. bezpośrednio — pracuj na obiekcie `Request`.  
- Zwracaj `ResponseInterface`, aby zatrzymać dalsze przetwarzanie.
