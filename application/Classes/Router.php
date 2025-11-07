<?php
/**
 * Application: DbM Framework
 * A lightweight PHP framework for building web applications.
 *
 * @author Artur Malinowski
 * @copyright Design by Malina (All Rights Reserved)
 * @license MIT
 * @link https://www.dbm.org.pl
 */

declare(strict_types=1);

namespace Dbm\Classes;

use Dbm\Classes\DependencyContainer;
use Dbm\Classes\ExceptionHandler;
use Dbm\Classes\Http\Request;
use Dbm\Classes\Http\Response;
use Dbm\Classes\Http\Stream;
use Dbm\Classes\Log\Logger;
use Dbm\Exception\UnauthorizedApiException;
use Dbm\Exception\UnauthorizedRedirectException;
use Dbm\Interfaces\DatabaseInterface;
use Dbm\Interfaces\RouterInterface;
use Dbm\Security\AccessGuard;
use Psr\Http\Message\ResponseInterface;
use Exception;
use ReflectionMethod;
use Throwable;

/**
 * Router obsługujący rejestrowanie tras, grupy, middleware oraz dispatch.
 *
 * Wzorzec użycia:
 *
 * $router->get('/api/articles', [ArticleController::class, 'list'], 'articles_list');
 * $router->post('/api/articles', [ArticleController::class, 'create']);
 *
 * $router->group('/api', function(Router $router) {
 *     $router->get('/users', [UserController::class, 'index']);
 * });
 *
 * Middleware globalne lub dla prefixu:
 *
 * $router->addMiddleware(fn(Request $req) => Auth::check($req) ?: Response::unauthorized(), '/api');
 */
class Router implements RouterInterface
{
    protected array $routes = [];
    protected array $namedRoutes = [];
    protected static ?string $currentRouteName = null;
    /** @var array<int, array{handler: callable, prefix: string|null}> */
    protected array $middlewares = [];
    private string $groupPrefix = '';
    private ?DatabaseInterface $database;
    private ?DependencyContainer $container;
    private Logger $logger;
    private Request $request;
    private AccessGuard $guard;

    public function __construct(?DatabaseInterface $database = null, ?DependencyContainer $container = null, ?Request $request = null)
    {
        $this->container = $container;
        $this->database = $database;
        $this->logger = new Logger();
        $this->request = $request ?? new Request();
        $this->guard = new AccessGuard($database);
    }

    public function dispatch(string $uri): void
    {
        try {
            $method = strtoupper($this->request->getServerParams()['REQUEST_METHOD'] ?? 'GET');
            $uri = $this->normalizeUri($uri);
            $route = $this->matchRoute($uri, $method);

            if (!$route) {
                throw new ExceptionHandler("Route not found for {$method} {$uri}", 404);
            }

            // Zapamiętaj nazwę bieżącej trasy (dla helperów typu isActive w TemplateFeature)
            $routeName = $this->routes[$method][$route['uri']]['name'] ?? null;
            self::$currentRouteName = $routeName;
            // Opcjonalnie: $_ENV['CURRENT_ROUTE_NAME'] = $routeName;

            if (!isset($this->routes[$method][$route['uri']])) {
                if ($route['uri'] === '/public') { // TODO! Sprawdź na serwerze zdalnym, dodane dla localhost?
                    header("Location: errors/error-config.html");
                    exit;
                }

                throw new ExceptionHandler("Route not found: {$method} {$route['uri']}", 404);
            }

            // Middlewares
            foreach ($this->middlewares as $middleware) {
                if ($middleware['prefix'] === null || str_starts_with($uri, $middleware['prefix'])) {
                    $response = $middleware['handler']($this->request);

                    if ($response instanceof ResponseInterface) {
                        /** @var \Dbm\Classes\Http\Response|ResponseInterface $response */
                        $response->send();
                        return;
                    }
                }
            }

            // Wywołanie kontrolera
            $controllerName = $this->routes[$method][$route['uri']]['controller'];

            if (!class_exists($controllerName)) {
                throw new ExceptionHandler("Controller not found: $controllerName", 500);
            }

            // TEST dla DI -> DependencyContainer
            $controllerName = $this->resolveController($controllerName);
            $methodName = $this->routes[$method][$route['uri']]['method'];

            if (!method_exists($controllerName, $methodName)) {
                throw new ExceptionHandler("Method not found: $methodName in $controllerName", 500);
            }

            // ACCESS CONTROL - Sprawdzenie uprawnień
            if (isset($this->routes[$method][$route['uri']]['permission'])) {
                $this->guard->checkPermission($this->routes[$method][$route['uri']]['permission']);
            }

            // Pobierz parametry metody za pomocą Reflection
            $reflection = new ReflectionMethod($controllerName, $methodName);
            $methodParams = $reflection->getParameters();

            // Parametry routingu
            $routeParams = $route['params'] ?? [];

            // Połącz dynamiczne parametry trasy z istniejącymi query params
            $this->request->setQueryParams(array_merge($this->request->getQueryParams(), $routeParams));

            // Przygotuj argumenty metody
            $args = [];

            foreach ($methodParams as $param) {
                $paramType = $param->getType();
                $paramName = $param->getName();

                if (isset($routeParams[$paramName])) {
                    // Parametry dynamiczne z URL
                    $args[] = $routeParams[$paramName];
                } elseif ($paramType && !$paramType->isBuiltin()) {
                    // Pobranie zależności z DependencyContainer, jeśli to klasa
                    $args[] = $this->container->get($paramType->getName());
                } elseif ($paramType && $paramType->getName() === Request::class) {
                    // Automatyczne wstrzyknięcie Request
                    $args[] = $this->request;
                } else {
                    // Jeśli nic nie pasuje, ustaw wartość domyślną lub null
                    $args[] = $param->isOptional() ? $param->getDefaultValue() : null;
                }
            }

            $response = $reflection->invokeArgs($controllerName, $args);

            if ($response instanceof ResponseInterface) {
                /** @var \Dbm\Classes\Http\Response|ResponseInterface $response */
                $response->send();
            } else {
                throw new ExceptionHandler("Invalid response from $methodName in $controllerName", 500);
            }
        } catch (UnauthorizedRedirectException $e) {
            header("Location: " . $e->getRedirectUrl());
            exit;
        } catch (UnauthorizedApiException $e) {
            $headers = array_merge(['Content-Type' => 'application/json'], []);
            $data = json_encode(
                ['success' => false, 'message' => $e->getMessage()],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

            $stream = new Stream($data ?? '');
            $response = new Response(401, $headers, $stream);
            $response->send();
            return;
            exit;
        } catch (ExceptionHandler $e) {
            $e->handle($e, getenv('APP_ENV') ?: 'production');
        } catch (Throwable $e) {
            (new ExceptionHandler())->handle($e, getenv('APP_ENV') ?: 'production');
        }
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function group(string $prefix, callable $callback): void
    {
        $previousPrefix = $this->groupPrefix;
        $this->groupPrefix .= rtrim($prefix, '/');
        $this->groupPrefix = rtrim($this->groupPrefix, '/');

        $callback($this);

        $this->groupPrefix = $previousPrefix;
    }

    public function get(string $path, $handler, ?string $name = null): void
    {
        $this->addRoute($this->groupPrefix . $path, $handler, $name, 'GET');
    }

    public function post(string $path, array $handler, ?string $name = null): void
    {
        $this->addRoute($this->groupPrefix . $path, $handler, $name, 'POST');
    }

    public function put(string $path, array $handler, ?string $name = null): void
    {
        $this->addRoute($this->groupPrefix . $path, $handler, $name, 'PUT');
    }

    public function delete(string $path, array $handler, ?string $name = null): void
    {
        $this->addRoute($this->groupPrefix . $path, $handler, $name, 'DELETE');
    }

    /**
     * Używanie w pliku głównym index.php
     */
    public function addMiddleware(callable $middleware, ?string $pathPrefix = null): void
    {
        $this->middlewares[] = ['handler' => $middleware, 'prefix' => $pathPrefix];
    }

    /**
     * Grupuje trasy z określonym wymaganym uprawnieniem.
     *
     * @param string $permission  Wymagane uprawnienie
     * @param callable $callback  Funkcja rejestrująca trasy
     * @param string|null $pathPrefix  Opcjonalny prefix ścieżek (np. '/admin')
     */
    public function guard(string $permission, callable $callback, ?string $pathPrefix = null): void
    {
        // Zapamiętaj, jakie URI są już zarejestrowane (dla każdej metody)
        $beforeUris = [];
        foreach ($this->routes as $method => $routes) {
            $beforeUris[$method] = array_keys($routes);
        }

        // Opcjonalny prefix ścieżek
        $previousPrefix = $this->groupPrefix;
        if ($pathPrefix) {
            $this->groupPrefix .= rtrim($pathPrefix, '/');
        }

        // Wykonaj callback, który doda nowe trasy
        $callback();

        // Przypisanie permission do nowych tras
        foreach ($this->routes as $method => &$routes) {
            $existing = $beforeUris[$method] ?? [];

            foreach ($routes as $uri => &$route) {
                if (!in_array($uri, $existing, true)) {
                    $route['permission'] = $permission;
                }
            }

            unset($route);
        }

        unset($routes);

        $this->groupPrefix = $previousPrefix;
    }

    /**
     * Używane w TemplateFeature {} -> path()
     */
    public function generatePath(string $routeName, array $params = []): string
    {
        // Jeśli podano pełną ścieżkę zamiast nazwy trasy
        if (str_contains($routeName, '/') || str_contains($routeName, '.')) {
            $this->logger->warning("Warning! Attempted to generate a path for a non-route name '{$routeName}'.");
        }

        // Sprawdzenie czy istnieje trasa o podanej nazwie
        if (!isset($this->namedRoutes[$routeName])) {
            throw new ExceptionHandler("Route with name '{$routeName}' not found.", 500);
        }

        // INFO: $method - nie używane, ale może się przydać np.: do sprawdzenia czy dana ścieżka ma być tylko dla POST.
        [$method, $path] = $this->namedRoutes[$routeName];

        // Zamień dynamiczne parametry w ścieżce (np. {id} -> 123)
        foreach ($params as $key => $value) {
            if (strpos($path, '{' . $key . '}') === false) {
                throw new ExceptionHandler("Dynamic parameter '{$key}' not found in route path '{$path}'.", 500);
            }

            $path = str_replace('{' . $key . '}', (string) $value, $path);
        }

        return $path;
    }

    /**
     * Używane w TemplateFeature {} -> path()
     */
    public function generateSeoFriendlyUrl(string $text, int $limit = 120): string
    {
        $hyphen = '-';
        $allowedPattern = "/[^a-zA-Z0-9 ]/";
        $arrayRemove = ['and', 'or', 'to', 'an', 'the', 'is', 'in', 'of', 'on', 'with',
            'at', 'by', 'for', 'etc.', 'a', 'i', 'o', 'u', 'w', 'z', 'na', 'do', 'po',
            'za', 'od', 'dla', 'ku', 'czy', 'by', 'aby', 'oraz', 'lub', 'itp.',
        ];

        // Transliterate text to ASCII
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text = strip_tags($text);
        $text = strtolower($text);
        $text = preg_replace($allowedPattern, '', $text);

        // Remove unwanted words
        if (!empty($arrayRemove)) {
            $removePattern = "/\b(" . implode("|", $arrayRemove) . ")\b/";
            $text = trim(preg_replace($removePattern, '', $text));
        }

        // Limit length of the text
        if (mb_strlen($text) > $limit) {
            $text = trim(preg_replace('~\s+\S+$~', '', substr($text, 0, $limit)));
        }

        // Replace spaces with hyphens
        $text = trim(preg_replace('~\s+~', $hyphen, $text));

        return $text;
    }

    /**
     * Używane w TemplateFeature {} -> isActive()
     */
    public static function getCurrentRouteName(): ?string
    {
        return self::$currentRouteName;
    }

    /**
     * @param array{0: class-string, 1: string} $handler
     */
    private function addRoute(
        string $path,
        array $handler,
        ?string $routeName = null,
        string $method = 'GET'
    ): void {
        $method = strtoupper($method);

        if (isset($this->routes[$method][$path])) {
            throw new ExceptionHandler("Route '{$path}' already exists for method {$method}.", 500);
        }

        if ($routeName && isset($this->namedRoutes[$routeName])) {
            throw new ExceptionHandler("Route name '{$routeName}' must be unique.", 500);
        }

        $this->routes[$method][$path] = [
            'controller' => $handler[0],
            'method' => $handler[1],
            'name' => $routeName,
        ];

        if ($routeName) {
            $this->namedRoutes[$routeName] = [$method, $path];
        }
    }

    private function normalizeUri(string $uri): string
    {
        // Przekierowanie, jeśli adres nie jest katalogiem/plikiem i kończy się ukośnikiem
        $server = $this->request->getServerParams();
        $potentialFile = $server['DOCUMENT_ROOT'] . rtrim($uri, '/');

        if (!is_dir($potentialFile) && !is_file($potentialFile)) {
            if ($uri !== '/' && substr($uri, -1) === '/') {
                $normalizedUri = rtrim($uri, '/');
                header("Location: {$normalizedUri}", true, 301);
                exit;
            }
        }

        // Usuwa skrypt (index.php) z URI
        $scriptName = dirname($server['SCRIPT_NAME']);
        $baseUri = str_replace('\\', '/', $scriptName);

        // Usuwa fragmenty i parametry z URI
        $cleanUri = parse_url($uri, PHP_URL_PATH);

        // Usuwa bazową ścieżkę z URI (np. /public)
        if (count(explode('/', trim($baseUri, '/'))) > 1) {
            $basePath = strstr($scriptName, 'public', true);
            $cleanUri = '/' . ltrim(str_replace($basePath, '', $cleanUri), '/');
        }

        // Zwraca znormalizowany URI
        return '/' . trim($cleanUri, '/');
    }

    private function matchRoute(string $uri, string $httpMethod): ?array
    {
        if (!isset($this->routes[$httpMethod])) {
            return null;
        }

        foreach ($this->routes[$httpMethod] ?? [] as $route => $controllerAction) {
            try {
                // Dopasowanie dynamicznych tras
                $pattern = preg_replace(
                    ['/\\{#\\}/', '/\\{(.*?)\\}/'],
                    ['([a-zA-Z0-9-]+)', '([a-zA-Z0-9-]+(?:\\.[a-zA-Z0-9-]+)*)'],
                    $route
                );

                if (preg_match("#^{$pattern}$#", $uri, $matches)) {
                    array_shift($matches);

                    preg_match_all('/\\{(.*?)\\}/', $route, $paramNames);

                    // Jeśli liczba dopasowanych wartości i nazw parametrów nie jest równa, próbujemy z parsowaniem kropek
                    if (count($paramNames[1]) !== count($matches)) {
                        // Podziel URI na segmenty, aby dopasować parametry
                        $uriParams = substr($uri, strrpos("/$uri", '/'));
                        $uriParams = str_replace('.html', '', $uriParams);
                        $uriParams = explode('.', $uriParams);

                        // Nadpisz dopasowania tylko jeśli liczby się zgadzają
                        if (count($paramNames[1]) === count($uriParams)) {
                            $matches = $uriParams;
                        }
                    }

                    // Łączenie nazw parametrów
                    $params = array_combine($paramNames[1], $matches);

                    return ['uri' => $route, 'params' => $params];
                }
            } catch (Exception $e) {
                $this->logger->critical('Błąd podczas dopasowywania trasy: ' . $e->getMessage());
            }
        }

        return null;
    }

    private function resolveController(string $controllerName)
    {
        if (!$this->container) {
            throw new ExceptionHandler("Dependency container not available.", 500);
        }

        if (!class_exists($controllerName)) {
            throw new ExceptionHandler("Controller class not found: $controllerName", 500);
        }

        return $this->container->get($controllerName);
    }
}
