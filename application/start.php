<?php
/**
 * Application: DbM Framework (bootstrap application)
 * A lightweight PHP framework for building web applications.
 *
 * @author Artur Malinowski
 * @copyright Design by Malina (All Rights Reserved)
 * @license MIT
 * @link https://www.dbm.org.pl
 */

use Dbm\Classes\ExceptionHandler;
use Dbm\Classes\Helpers\DebugHelper;
use Dbm\Classes\Router;

function setupErrorHandling(): void
{
    error_reporting(E_ALL);

    ini_set('display_errors', getenv('APP_ENV') === 'production' ? '0' : '1');

    set_error_handler('reportingErrorHandler');

    set_exception_handler(function (Throwable $exception) {
        (new ExceptionHandler())->handle($exception, getenv('APP_ENV') ?: 'production');
    });
}

function reportingErrorHandler(int $errLevel, string $errMessage, string $errFile, int $errLine): void
{
    logErrorToFile($errLevel, $errMessage, $errFile, $errLine);

    $exceptionHandler = new ExceptionHandler();
    $exception = new ErrorException($errMessage, $errLevel, 0, $errFile, $errLine);
    $exceptionHandler->handle($exception, getenv('APP_ENV') ?: 'production');
}

function logErrorToFile(int $errLevel, string $errMessage, string $errFile, int $errLine): void
{
    $basename = 'index';
    $uri = $_SERVER["REQUEST_URI"];
    $dir = str_replace('public', '', dirname($_SERVER['PHP_SELF']));

    if ($uri !== $dir) {
        $basename = str_replace('.html', '', basename($uri));
        $basename = preg_replace('/[\/\\\\\:\*\?\"\<\>\|\=\&]/', '_', $basename);
        $basename = preg_replace('/\s+/', '_', $basename);
        $basename = preg_replace('/[^\w\.\-]/', '', $basename);
    }

    $date = date('Y-m-d H:i:s');
    $file = date('Ymd') . '_' . strtolower($basename) . '.log';
    $dir = BASE_DIRECTORY . 'var' . DS . 'log' . DS;
    $path = $dir . $file;

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $errorHandler = "DATE: $date, level: $errLevel\n File: $errFile on line $errLine\n Message: $errMessage\n";

    file_put_contents($path, $errorHandler, FILE_APPEND);
}

function configurationSettings(string $pathConfig): void
{
    if (!file_exists($pathConfig)) {
        die('CONFIGURATION! Configure the application to run the program, then rename the .env.example file to .env.');
    }
}

/**
 * Autoloading with without Composer
 *
 * @param string $pathComposerAutoload
 * @return void
 */
function autoloadingWithWithoutComposer(string $pathComposerAutoload): void
{
    if (file_exists($pathComposerAutoload)) {
        require $pathComposerAutoload; // Ścieżka do Composera: vendor/autoload.php
    } else {
        spl_autoload_register(function ($className) {
            // Ignoruj klasy tymczasowe (np. generowane przez silnik templatek)
            if (str_starts_with($className, '__Tpl_')) {
                return;
            }

            // === Mapa przestrzeni nazw do katalogów (PSR-4)
            $namespaceMap = [
                // Globalne przestrzenie nazw aplikacji
                'App' => "src",                     // Właściwy kod aplikacji (np. kontrolery, modele, serwisy)
                'Dbm' => "application",             // Kod frameworka DbM (rdzeń systemu)
                'Lib' => "application/Libraries",   // Własne lub zewnętrzne biblioteki (ogólne, różne)
                'Mod' => "modules",                 // Moduły aplikacji (w tym repozytoria DB; docelowo można zmienić na Repo?!)
                // Alias PSR-4 dla wbudowanej biblioteki (np. DataTables)
                // 'Dbm\\DataTables' => "application/Libraries/DataTables/src",
            ];

            // === Obsługa mapowanych przestrzeni nazw (standard PSR-4)
            $matchedPrefix = null;
            $matchedPath = null;

            foreach ($namespaceMap as $prefix => $baseDir) {
                if (str_starts_with($className, $prefix)) {
                    $matchedPrefix = $prefix;
                    $matchedPath = $baseDir;
                    break;
                }
            }

            if ($matchedPrefix !== null) {
                // Usuwamy prefix namespace z pełnej nazwy klasy
                $relativeClass = substr($className, strlen($matchedPrefix));
                $relativeClass = ltrim($relativeClass, '\\');

                // Budujemy ścieżkę do pliku
                $filePath = BASE_DIRECTORY
                        . str_replace('/', DS, $matchedPath)
                        . DS . str_replace('\\', DS, $relativeClass)
                        . '.php';

                // Załaduj plik, jeśli istnieje i nie jest już załadowany
                if (!class_exists($className, false) && file_exists($filePath)) {
                    require_once $filePath;
                    return;
                }

                if (!file_exists($filePath)) {
                    error_log("Autoloader: Nie znaleziono pliku dla klasy {$className} w ścieżce {$filePath}");
                }

                return;
            }

            // === Wbudowane biblioteki (PSR, PHPMailer, Guzzle itp.)
            if (is_dir(BASE_DIRECTORY . 'libraries')) {
                static $loadedLibraries = [];

                // mapowanie namespace - katalog
                $namespaceLibraries = [
                    'Psr\\Http\\Message\\' => 'libraries/psr/http-message/src',
                    'Psr\\Http\\Client\\' => 'libraries/psr/http-client/src',
                    'Psr\\Log\\' => 'libraries/psr/log/src',
                    'PHPMailer\\PHPMailer' => "libraries/phpmailer/src",
                    'GuzzleHttp\\Promise' => "libraries/guzzlehttp/promise/src", // wymagane dla Guzzle
                    'GuzzleHttp\\Psr7' => "libraries/guzzlehttp/psr7/src", // wymagane dla Guzzle
                    'GuzzleHttp' => "libraries/guzzlehttp/guzzle/src", // wymaga również "http-message" i "http-client"
                ];

                // Sortuj klucze po długości (najpierw najbardziej specyficzne)
                uksort($namespaceLibraries, function ($a, $b) {
                    return strlen($b) - strlen($a);
                });

                foreach ($namespaceLibraries as $prefix => $libraryPath) {
                    if (str_starts_with($className, $prefix) && !class_exists($className, false)) {
                        // Spróbuj PSR-4 ścieżkę
                        $relative = substr($className, strlen($prefix));
                        $relative = ltrim($relative, '\\');
                        $filePathLib = BASE_DIRECTORY
                            . str_replace('/', DS, $libraryPath)
                            . DS . str_replace('\\', DS, $relative)
                            . '.php';

                        if (file_exists($filePathLib)) {
                            require_once $filePathLib;
                            return;
                        }

                        // === Fallback: rekurencyjne wczytanie wszystkich plików biblioteki (wersja testowa warunku) ?
                        if (!isset($loadedLibraries[$prefix])) {
                            $dir = BASE_DIRECTORY . str_replace('/', DS, $libraryPath);

                            if (is_dir($dir)) {

                                $iterator = new RecursiveIteratorIterator(
                                    new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
                                );

                                foreach ($iterator as $file) {
                                    if ($file->isFile() && $file->getExtension() === 'php') {
                                        require_once $file->getPathname();
                                    }
                                }

                                $loadedLibraries[$prefix] = true;
                                return;
                            }

                            error_log("Autoloader: Katalog biblioteki {$dir} nie istnieje dla {$prefix}");
                        }
                    }
                }
            }

            // === Brak dopasowania
            error_log("Autoloader: Nieobsługiwany namespace {$className}");
        });
    }
}

function initializeSession(): void
{
    $isProduction = getenv('APP_ENV') === 'production';

    session_start([
        'cookie_lifetime' => 0,
        'cookie_secure' => $isProduction,
        'cookie_httponly' => true,
        'use_strict_mode' => true,
        'use_only_cookies' => true,
    ]);
}

function isConfigDatabase(): bool
{
    return !empty(getenv('DB_HOST')) && !empty(getenv('DB_NAME')) && !empty(getenv('DB_USER'));
}

/**
 * Initialize routing and middleware.
 *
 * @param Router $router
 * @return void
 */
function initializeRouting(Router $router): void
{
    $webRoutesPath = BASE_DIRECTORY . 'application' . DS . 'routes.php';
    $apiRoutesPath = BASE_DIRECTORY . 'application' . DS . 'api.php';
    $middlewaresPath = BASE_DIRECTORY . 'application' . DS . 'middleware.php';

    try {
        $webRoutes = require $webRoutesPath;
        $apiRoutes = require $apiRoutesPath;
        $middlewares = require $middlewaresPath;
    } catch (Throwable $t) {
        (new ExceptionHandler())->handle($t, getenv('APP_ENV') ?: 'production');
        return;
    }

    if (!is_callable($webRoutes)) {
        $exception = new Exception("File '{$webRoutesPath}' must return a callable (function(Router \$router): void).");
        (new ExceptionHandler())->handle($exception, getenv('APP_ENV') ?: 'production');
        return;
    }
    if (!is_callable($apiRoutes)) {
        $exception = new Exception("File '{$apiRoutesPath}' must return a callable (function(Router \$router): void).");
        (new ExceptionHandler())->handle($exception, getenv('APP_ENV') ?: 'production');
        return;
    }
    if (!is_callable($middlewares)) {
        $exception = new Exception("File '{$middlewaresPath}' must return a callable (function(Router \$router): void).");
        (new ExceptionHandler())->handle($exception, getenv('APP_ENV') ?: 'production');
        return;
    }

    try {
        $webRoutes($router);
        $apiRoutes($router);
        $middlewares($router);
    } catch (Throwable $t) {
        (new ExceptionHandler())->handle($t, getenv('APP_ENV') ?: 'production');
        return;
    }

    // Dispatch request
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $router->dispatch($uri);
}

// ### Registering helper functions
if (!function_exists('dump')) {
    function dump(mixed ...$vars): void
    {
        foreach ($vars as $var) {
            DebugHelper::dump($var);
        }
    }
}
