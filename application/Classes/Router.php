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
use Dbm\Classes\Exceptions\UnauthorizedRedirectException;
use Dbm\Classes\Http\Request;
use Dbm\Classes\Logs\Logger;
use Dbm\Interfaces\DatabaseInterface;
use Dbm\Interfaces\RouterInterface;
use Psr\Http\Message\ResponseInterface;
use Exception;
use ReflectionMethod;
use Throwable;

class Router implements RouterInterface
{
    protected array $routes = [];
    protected array $namedRoutes = [];
    private ?DatabaseInterface $database;
    private ?DependencyContainer $container;
    private Logger $logger;
    private Request $request;

    public function __construct(?DatabaseInterface $database = null, ?DependencyContainer $container = null, ?Request $request = null)
    {
        $this->container = $container;
        $this->database = $database;
        $this->logger = new Logger();
        $this->request = $request ?? new Request();
    }

    public function addRoute(string $route, array $controllerAction, ?string $name = null): void
    {
        if (isset($this->routes[$route])) {
            throw new ExceptionHandler("Route '{$route}' already exists.", 500);
        }

        if ($name && isset($this->namedRoutes[$name])) {
            throw new ExceptionHandler("Route name '{$name}' must be unique.", 500);
        }

        $this->routes[$route] = [
            'controller' => $controllerAction[0],
            'method' => $controllerAction[1],
            'name' => $name,
        ];

        if ($name) {
            $this->namedRoutes[$name] = $route;
        }
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function dispatch(string $uri): void
    {
        try {
            $uri = $this->normalizeUri($uri);
            $route = $this->matchRoute($uri);

            if (!isset($this->routes[$route['uri']])) {
                if ($route['uri'] === '/public') { // TODO! Sprawdź na serwerze zdalnym, dodane dla localhost?
                    header("Location: errors/error-config.html");
                    exit;
                }

                throw new ExceptionHandler('Route not found: ' . $route['uri'], 404);
            }

            $controllerName = $this->routes[$route['uri']]['controller'];
            // TODO! $controllerName = $this->resolveControllerNamespace($controllerName);

            if (!class_exists($controllerName)) {
                throw new ExceptionHandler("Controller not found: $controllerName", 500);
            }

            // V0: $controller = new $controllerName($this->database);
            /* V1: $controller = $this->container
                ? $this->container->get($controllerName)
                : new $controllerName($this->database); */

            // TEST dla DI -> DependencyContainer
            $controllerName = $this->resolveController($controllerName);
            $methodName = $this->routes[$route['uri']]['method'];

            if (!method_exists($controllerName, $methodName)) {
                throw new ExceptionHandler("Method not found: $methodName in $controllerName", 500);
            }

            // Pobierz parametry metody za pomocą Reflection
            $reflection = new ReflectionMethod($controllerName, $methodName);
            $methodParams = $reflection->getParameters();

            // Utwórz obiekt Request
            $request = new Request();
            $routeParams = $route['params'] ?? [];

            // Połącz dynamiczne parametry trasy z istniejącymi query params
            $request->setQueryParams(array_merge($request->getQueryParams(), $routeParams));

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
                    $args[] = $request;
                } else {
                    // Jeśli nic nie pasuje, ustaw wartość domyślną lub null
                    $args[] = $param->isOptional() ? $param->getDefaultValue() : null;
                }
            }

            $response = $reflection->invokeArgs($controllerName, $args);

            if ($response instanceof ResponseInterface) {
                $response->send();
            } else {
                throw new ExceptionHandler("Invalid response from $methodName in $controllerName", 500);
            }
        } catch (UnauthorizedRedirectException $e) {
            header("Location: " . $e->getRedirectUrl());
            exit;
        } catch (ExceptionHandler $e) {
            $e->handle($e, getenv('APP_ENV') ?: 'production');
        } catch (Throwable $e) {
            (new ExceptionHandler())->handle($e, getenv('APP_ENV') ?: 'production');
        }
    }

    public function generatePath(string $name, array $params = []): string
    {
        // Jeśli podano pełną ścieżkę zamiast nazwy trasy
        if (str_contains($name, '/') || str_contains($name, '.')) {
            $this->logger->warning("Warning! Attempted to generate a path for a non-route name '{$name}'.");
        }

        // Sprawdź, czy istnieje trasa o podanej nazwie
        if (!isset($this->namedRoutes[$name])) {
            throw new ExceptionHandler("Route with name '{$name}' not found.", 500);
        }

        // Pobierz ścieżkę zdefiniowaną dla nazwy trasy
        $path = $this->namedRoutes[$name];

        // Zamień dynamiczne parametry w ścieżce (np. {id} -> 123)
        foreach ($params as $key => $value) {
            if (strpos($path, '{' . $key . '}') === false) {
                throw new ExceptionHandler("Dynamic parameter '{$key}' not found in route path '{$path}'.", 500);
            }

            $path = str_replace('{' . $key . '}', (string) $value, $path);
        }

        return $path;
    }

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

    private function matchRoute(string $uri): array
    {
        foreach ($this->routes as $route => $controllerAction) {
            try {
                // Dopasowanie dynamicznych tras
                $pattern = preg_replace([
                    '/\\{#\\}/', // {#} - dowolny ciąg alfanumeryczny + "-"
                    '/\\{(.*?)\\}/' // {param} - dowolny parametr
                ], [
                    '([a-zA-Z0-9-]+)', // Wzorzec dla {#}
                    '([a-zA-Z0-9-]+(?:\\.[a-zA-Z0-9-]+)*)' // Wzorzec dla {param} z obsługą kropek
                ], $route);

                if (preg_match("#^{$pattern}$#", $uri, $matches)) {
                    array_shift($matches); // Usuń pełne dopasowanie

                    // Wyodrębnianie nazw parametrów
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

        return ['uri' => $uri, 'params' => []];
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
