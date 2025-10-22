# Dokumentacja klasy `Response`

## Klasa `Dbm\Classes\Http\Response`

---

## Omówienie
Klasa `Response` reprezentuje odpowiedź HTTP zgodną ze standardem **PSR-7** (`ResponseInterface`), rozszerzoną o funkcjonalność **DbM Framework**.
Zapewnia przejrzyste API do zarządzania kodami stanu HTTP, nagłówkami i strumieniami treści, a także dodatkowe metody pomocnicze, takie jak `send()`, `debug()`, `html()` i `json()`.

---

## Konstruktor

### `__construct(int $statusCode = 200, array $headers = [], ?StreamInterface $body = null)`
Tworzy nową instancję `Response`.

#### Parametry
| Nazwa | Typ | Opis |
|------|------|--------------|
| `$statusCode` | `int` | Kod stanu HTTP (domyślnie: `200`) |
| `$headers` | `array<string, string>` | Tablica nagłówków |
| `$body` | `?StreamInterface` | Treść odpowiedzi (strumień) |

---

## Metody podstawowe (PSR-7)

### `getStatusCode(): int`
Zwraca aktualny kod stanu HTTP.

### `withStatus(int $code, string $reasonPhrase = ''): static`
Klonuje odpowiedź z nowym kodem stanu i (opcjonalnie) frazą uzasadniającą.

### `getReasonPhrase(): string`
Zwraca frazę uzasadniającą odpowiadającą kodowi stanu HTTP.

---

## Metody rozszerzone (DbM Framework)

### `send(): void`
Wysyła odpowiedź HTTP do klienta.

Ta metoda ustawia kod statusu, zwraca nagłówki i wyświetla treść odpowiedzi.

**Przykład:**
```php
$response = Response::json(['success' => true]);
$response->send();
```

---

### `debug(): void`
Wyprowadza na standardowe wyjście wszystkie szczegóły odpowiedzi (kod statusu, nagłówki, treść) i kończy wykonywanie.

Przydatne do debugowania i testowania odpowiedzi.

---

### `html(string $content, int $statusCode = 200, array $headers = []): Response`
Tworzy odpowiedź HTML z kodowaniem UTF-8.

**Przykład:**
```php
return Response::html('<h1>Witaj, świecie!</h1>');
```

---

### `json(array $data, int $statusCode = 200): static`
Tworzy odpowiedź JSON za pomocą `json_encode()` z `JSON_THROW_ON_ERROR`.

**Przykład:**
```php
return Response::json(['message' => 'OK']);
```

---

## Metoda wewnętrzna

### `getDefaultReasonPhrase(int $code): string`
Zwraca domyślną frazę przyczyny dla standardowych kodów HTTP.
Obsługuje popularne kody (200, 201, 204, 301, 302, 400, 401, 403, 404, 405, 409, 422, 500, 503).

---

## Uwagi
- Klasa `Response` rozszerza bazową klasę `Message` i implementuje `ExtendedResponseInterface`.
- Obsługuje łączenie łańcuchowe (`withStatus()` zwraca nową instancję).
- Integruje się z `StreamInterface` w PSR-7 w celu obsługi treści.
