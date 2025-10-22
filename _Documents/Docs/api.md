# API - REST Routing (`application/api.php`)

System API w DbM Framework pozwala tworzyć lekkie, bezpieczne i zgodne z REST interfejsy JSON.  
Każdy endpoint zwraca dane w formacie JSON przy użyciu `jsonResponse()`.

---

## Definiowanie tras API

Trasy API umieszczamy w pliku `application/api.php`.

```php
$router->get('/api/path', [NameApiController::class, 'methodName'], 'api_route_name');
```

---

## Dostępne metody

| Metoda | Cel |
|--------|------|
| `GET` | Pobranie danych |
| `POST` | Utworzenie nowego zasobu |
| `PUT` | Aktualizacja istniejącego zasobu |
| `DELETE` | Usunięcie zasobu |

---

## Przykład grupy REST

```php
$router->group('/api/articles', function (Router $router) {
    $router->get('/', [ArticleApiController::class, 'list'], 'api_articles_list');
    $router->get('/{id}', [ArticleApiController::class, 'get'], 'api_articles_get');
    $router->post('/', [ArticleApiController::class, 'create'], 'api_articles_create');
    $router->put('/{id}', [ArticleApiController::class, 'update'], 'api_articles_update');
    $router->delete('/{id}', [ArticleApiController::class, 'delete'], 'api_articles_delete');
});
```

---

## Format odpowiedzi

Każda odpowiedź API powinna zwracać dane w formacie JSON:

```php
return $this->jsonResponse([
    'success' => true,
    'data' => $articles
], 200);
```

W przypadku błędu:

```json
{
    "success": false,
    "message": "Unauthorized API access!"
}
```

---

## Autoryzacja API

Każdy kontroler API dziedziczy po `BaseApiController`, który obsługuje weryfikację sesji i ról.

```php
protected function authorizeApiAccess(?string $role = null): void
{
    $sessionKey = $this->getSession(getenv('APP_SESSION_KEY'));
    if (empty($sessionKey)) {
        throw new UnauthorizedApiException("Unauthorized API access!");
    }
}
```

Można ją wywołać np. w konstruktorze:
```php
$this->authorizeApiAccess(ConstantConfig::USER_ROLES['A']);
```

---

## Przykładowe wywołania API (AJAX / JS)

```js
fetch('/api/articles')
  .then(res => res.json())
  .then(data => console.log(data));

fetch('/api/articles', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ title: 'Nowy artykuł', content: '...' })
});
```

---

## Middleware

API można rozszerzyć o middleware globalne lub dla prefixu `/api`, np.:

```php
$router->addMiddleware(function ($request) {
    if (!isset($request->getServerParams()['HTTP_AUTHORIZATION'])) {
        return BaseApiController::error('Unauthorized', 401);
    }
    return null;
}, '/api');
```

---

## Podsumowanie

API w DbM Framework to:
- pełna zgodność z REST,
- oddzielny plik `api.php`,
- natywna obsługa JSON i błędów,
- prosty system autoryzacji i middleware.

Lekkość i czytelność - idealne rozwiązanie dla MVP i paneli administracyjnych.
