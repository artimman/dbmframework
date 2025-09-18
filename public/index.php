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

// Strict typing
declare(strict_types=1);

// Importing required classes from namespace
use Dbm\Classes\DotEnv;
use Dbm\Classes\ExceptionHandler;
use Dbm\Classes\DependencyContainer;
use Dbm\Classes\Helpers\DebugHelper;
use Dbm\Classes\Router;
use Dbm\Classes\RouterSingleton;
use Dbm\Interfaces\DatabaseInterface;

// Output buffering
ob_start();

// Define constants
define('DS', DIRECTORY_SEPARATOR);
define('BASE_DIRECTORY', dirname(__DIR__) . DS);

// Include core functionalities
require_once BASE_DIRECTORY . 'application' . DS . 'start.php';

// Initialize configuration and autoloading
$pathConfig = BASE_DIRECTORY . '.env';
$pathAutoload = BASE_DIRECTORY . 'vendor' . DS . 'autoload.php';

try {
    // Error handler registration
    set_error_handler('reportingErrorHandler');

    // Registering a global exception handler
    set_exception_handler(function (Throwable $exception) {
        (new ExceptionHandler())->handle($exception, getenv('APP_ENV') ?: 'production');
    });

    // Load configuration
    configurationSettings($pathConfig);

    // Autoloading with or without Composer
    autoloadingWithWithoutComposer($pathAutoload);

    // Registering helpers functions
    if (!function_exists('dump')) {
        function dump(mixed ...$vars): void
        {
            foreach ($vars as $var) {
                DebugHelper::dump($var);
            }
        }
    }

    // Load environment variables
    $dotenv = new DotEnv($pathConfig);
    $dotenv->load();

    // Set error handling based on environment
    $appEnv = getenv('APP_ENV') ?: 'production';
    setupErrorHandling($appEnv);

    // Start session
    initializeSession();

    // Routing
    $routes = require BASE_DIRECTORY . 'application' . DS . 'routes.php';

    if (!is_callable($routes)) {
        throw new Exception("Routes file is not properly configured.");
    }

    // Creating DI Container
    $container = new DependencyContainer();
    // Service registration
    $servicesConfig = require BASE_DIRECTORY . 'application' . DS . 'services.php';
    $servicesConfig($container);

    // Getting Database Instance from DI
    $database = isConfigDatabase() ? $container->get(DatabaseInterface::class) : null;

    // Creating a router with DI
    // V1 without DI: $router = new Router($database);
    $router = new Router($database, $container);
    RouterSingleton::setInstance($router);
    // Launch of routes
    $routes($router);
} catch (Throwable $e) {
    (new ExceptionHandler())->handle($e, getenv('APP_ENV'));
}
