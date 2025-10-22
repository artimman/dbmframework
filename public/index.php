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
use Dbm\Classes\Router;
use Dbm\Classes\RouterSingleton;
use Dbm\Interfaces\DatabaseInterface;

// Output buffering
ob_start();

// Define constants
define('REQUEST_START_TIME', microtime(true));
define('DS', DIRECTORY_SEPARATOR);
define('BASE_DIRECTORY', dirname(__DIR__) . DS);

// Include core functionalities
require_once BASE_DIRECTORY . 'application' . DS . 'start.php';

// Initialize configuration and autoloading
$pathConfig = BASE_DIRECTORY . '.env';
$pathComposerAutoload = BASE_DIRECTORY . 'vendor' . DS . 'autoload.php';

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
    autoloadingWithWithoutComposer($pathComposerAutoload);

    // Load environment variables
    $dotenv = new DotEnv($pathConfig);
    $dotenv->load();

    // Set error handling based on environment
    $appEnv = getenv('APP_ENV') ?: 'production';
    setupErrorHandling($appEnv);

    // Start session
    initializeSession();

    // Creating DI Container
    $container = new DependencyContainer();
    // Service registration
    $servicesConfig = require BASE_DIRECTORY . 'application' . DS . 'services.php';
    $servicesConfig($container);

    // Getting Database Instance from DI
    $database = isConfigDatabase() ? $container->get(DatabaseInterface::class) : null;

    // Creating router with DI
    $router = new Router($database, $container);
    RouterSingleton::setInstance($router);

    // Start routing
    initializeRouting($router);
} catch (Throwable $e) {
    (new ExceptionHandler())->handle($e, getenv('APP_ENV'));
}
