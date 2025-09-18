# Routing – `application/routes.php`

W DbM Framework routing sprowadza się do jednej rzeczy: **dodania trasy za pomocą `addRoute()`**.  
Reszta (obsługa URI, dispatch itp.) działa automatycznie.

---

## Dodanie nowej trasy

```php
$router->addRoute('/path', [NameController::class, 'methodName'], 'route_name');
```

### Parametry:

/path - ścieżka URL (np. /contact, /blog/{slug})

[NameController::class, 'methodName'] – kontroler i metoda obsługująca żądanie

'route_name' – unikalna nazwa trasy

## Przykłady trasy

```php
$router->addRoute('/', [IndexController::class, 'index'], 'index');
$router->addRoute('/contact', [ContactController::class, 'contact'], 'contact');
$router->addRoute('/blog/{slug}', [BlogController::class, 'blogShow'], 'blog_show');
$router->addRoute('/product/{id}.html', [ProductController::class, 'productView'], 'product_view');
$router->addRoute('/{#}.art.{id}.html', [BlogController::class, 'article_show'], 'article_show');
```

### Dynamiczne segmenty URI
Framework obsługuje dynamiczne fragmenty ścieżek, które są automatycznie parsowane i przekazywane jako argumenty do metody kontrolera.

Wzorzec URI	Opis

/blog/{slug} Dowolny tekst (np. slug artykułu)  
/product/{id} Dowolny identyfikator  
/product/{id}.html Identyfikator zakończony .html  
/{#}.art.{id}.html Dowolny prefix, .art., ID i .html  

## Podsumowanie

DbM Framework oferuje prosty, szybki i manualny sposób dodawania tras bez zbędnych zależności. Wystarczy jedna linijka z addRoute() i metoda kontrolera – a system sam zadba o resztę.
