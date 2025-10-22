# Dokumentacja klasy `Request`

## Klasa `Dbm\Classes\Http\Request`

Zgodność z PSR-7 + rozszerzenia (ExtendedRequestInterface).

---

## Opis

Klasa `Request` jest pełną implementacją rozszerzonego interfejsu **`ExtendedRequestInterface`**, zapewniającego zgodność z **PSR-7** oraz dodatkowe funkcje użyteczne w aplikacjach DbM Framework.

Służy do obsługi żądań HTTP – w tym:
- odczytu nagłówków,
- analizy danych formularzy, JSON, XML i uploadów plików,
- obsługi metod HTTP (GET, POST, PUT, DELETE itp.),
- dostępu do danych serwera i klienta.

Została zaprojektowana tak, by zapewnić wygodny i bezpieczny dostęp do danych pochodzących z `$_GET`, `$_POST`, `$_FILES`, `$_SERVER` i strumienia `php://input`.

---

## Inicjalizacja

### `__construct()`

Tworzy nową instancję `Request` na podstawie zmiennych globalnych PHP.

- Automatycznie odczytuje nagłówki, ciało żądania, dane `GET`, `POST` i `FILES`.
- Buduje obiekt URI na podstawie aktualnego adresu (host, schemat, port, ścieżka, query string).
- Określa metodę HTTP (`GET`, `POST`, itp.).

Przykład:
```php
$request = new Dbm\Classes\Http\Request();
```

---

## Kluczowe metody

### `getParsedBody(): ?array`
Zwraca sparsowane ciało żądania (`POST`, `JSON`, `multipart/form-data`, `x-www-form-urlencoded`).

Obsługiwane typy:
- `application/json`
- `application/x-www-form-urlencoded`
- `multipart/form-data`

Przykład:
```php
$data = $request->getParsedBody();
$email = $data['email'] ?? null;
```

**Zastosowanie:** kontrolery, endpointy API, formularze.

---

### `getAllPost(): array`
Zwraca zawartość `$_POST` w postaci tablicy, bez parsowania JSON lub raw body.

Przykład:
```php
$post = $request->getAllPost();
```

**Zastosowanie:** starsze moduły, formularze, BaseController.

---

### `getAllQuery(): array`
Zwraca parametry `$_GET` (query string).

Przykład:
```php
$page = (int) ($request->getAllQuery()['page'] ?? 1);
```

**Zastosowanie:** paginacja, filtry, wyszukiwarki.

---

### `getBody(): StreamInterface`
Zwraca surowe ciało żądania jako strumień (np. JSON lub XML).

Przykład:
```php
$json = $request->getBody()->__toString();
```

**Zastosowanie:** logowanie, debugowanie, upload plików.

---

### `getHeaders() / hasHeader() / getHeaderLine()`
Dostęp do nagłówków HTTP.

Przykład:
```php
$contentType = $request->getHeaderLine('Content-Type');
```

---

### `getServerParams(): array`
Zwraca dane serwera (`$_SERVER`) – m.in. host, metoda, adres IP, user agent.

---

### `getUploadedFiles(): array`
Zwraca tablicę wszystkich przesłanych plików (`$_FILES`).

### `getUploadedFile(string $key): ?array`
Zwraca informacje o konkretnym pliku po kluczu.

---

## Dodatkowe metody rozszerzające

### `getJsonBody(): ?array`
Zwraca sparsowane dane JSON (jeśli typ `application/json`).

### `getXmlBody(): ?SimpleXMLElement`
Zwraca ciało żądania w formacie XML jako obiekt `SimpleXMLElement`.

### `getContentType(): ?string`
Zwraca nagłówek `Content-Type`.

### `getAuthorizationHeader(): ?string`
Zwraca nagłówek `Authorization` (jeśli istnieje).

---

## Informacje o kliencie i serwerze

| Metoda | Opis |
|--------|------|
| `getClientIp()` | Zwraca adres IP klienta. |
| `getClientPort()` | Zwraca port klienta. |
| `getUserAgent()` | Zwraca wartość `HTTP_USER_AGENT`. |
| `getReferer()` | Zwraca adres referera (jeśli istnieje). |
| `isSecure()` | Sprawdza, czy połączenie jest HTTPS. |
| `getPreferredLanguage(array $langs)` | Zwraca preferowany język z nagłówka `Accept-Language`. |

---

## Dostęp do danych żądania

| Metoda | Opis | Przykład |
|--------|------|----------|
| `getQuery($key, $default)` | Pobiera wartość z query string. | `$request->getQuery('page', 1)` |
| `getPost($key, $default)` | Pobiera wartość z body/POST. | `$request->getPost('email')` |
| `get($key, $default)` | Uniwersalny dostęp do POST/GET. | `$request->get('search')` |

---

## Obsługa metod HTTP

| Metoda | Opis |
|--------|------|
| `getMethod()` | Zwraca bieżącą metodę HTTP. |
| `isMethod(string $method)` | Sprawdza, czy metoda zgadza się z aktualną. |
| `isGet()` / `isPost()` / `isPut()` / `isDelete()` | Skróty dla popularnych metod. |

---

## Obsługa parametrów aplikacji (routingu)

| Metoda | Opis |
|--------|------|
| `setParams(array $params)` | Ustawia parametry (np. z routera). |
| `getParam(string $key)` | Pobiera pojedynczy parametr. |
| `getParams()` | Pobiera wszystkie parametry. |

---

## Tworzenie instancji

### `fromGlobals(): static`
Tworzy obiekt żądania z danych globalnych PHP.

### `capture(): static`
Alias `fromGlobals()` – przechwytuje bieżące żądanie.

Przykład:
```php
$request = Request::capture();
```

---

## Obsługa PUT / PATCH

### `getPutParams(): ?array`
Zwraca dane przesłane metodą PUT lub PATCH (parsowane ze strumienia).

---

## Podsumowanie zastosowań

| Zastosowanie | Zalecane metody |
|---------------|----------------|
| **Kontrolery API** | `getParsedBody()`, `getJsonBody()` |
| **Formularze HTML** | `getParsedBody()`, `getAllPost()` |
| **Filtrowanie i wyszukiwanie** | `getAllQuery()` |
| **Upload plików** | `getUploadedFiles()` |
| **Debugowanie i logowanie** | `getBody()`, `getHeaders()` |
| **Routing i parametry URL** | `getParams()`, `getParam()` |

---

## Przykład użycia

```php
use Dbm\Classes\Http\Request;

$request = Request::capture();

if ($request->isPost()) {
    $data = $request->getParsedBody();
    $email = $data['email'] ?? 'brak';
}

if ($request->isGet()) {
    $page = $request->getQuery('page', 1);
}

if ($request->isJson()) {
    $json = $request->getJsonBody();
}
```

---

## Powiązane interfejsy i klasy

- `Dbm\Psr\Http\Message\ExtendedRequestInterface`
- `Psr\Http\Message\UriInterface`
- `Dbm\Classes\Http\Message`
- `Dbm\Classes\Http\Stream`
- `Dbm\Classes\Http\Uri`

---

## Zgodność

- **PSR-7**: pełna zgodność z metodami PSR-7 (`getMethod()`, `getUri()`, `getHeaders()` itp.)
- **DbM Framework Extension**: rozszerzenia (`getAllPost()`, `getAllQuery()`, `getClientIp()`, `isJson()`, `capture()`)
